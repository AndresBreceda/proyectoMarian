<?php
/**
 * NOTICE OF LICENSE.
 *
 * This source file is subject to the following license: REGULAR LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @author    Envía Ya S.A. de C.V
 * @copyright Envía Ya S.A. de C.V https://enviaya.com.mx
 * @license   REGULAR LICENSE
 */

namespace EnviaYa\WooCommerce\Shipping;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\Plugin')):

class Plugin
{
	protected $id;
	protected $main_menu_id;
	protected $title;
	protected $description;
	protected $option_key;
	protected $settings;
	protected $plugin_path;
	protected $version;
	protected $adapter;
	protected $logger;
	protected $page_detector;
	protected $cart_proxy;
	protected $session_proxy;
	protected $errors;
	protected $rates_finder;
	protected $cacheExpirationInSecs = 7 * 24 * 60 * 60;

    public function __construct($plugin_path) 
    {
		$this->id = 'wc-enviaya-shipping';
		$this->title = 'EnvíaYa: Ship with all carriers (Rating, Booking and Tracking)';
		$this->description = 'An powerful plugin to rate multi-carrier shipment services and est. delivery dates during checkout, create shipment labels with one click and track.';
		$this->plugin_path = $plugin_path;
		$this->version = '1.1.7';
		$this->option_key = 'wc_enviaya_shipping_settings';
		$this->settings = array();
		$this->pluginSettings = array();
		$this->errors = array();

		$this->mainMenuId = 'EnviaYa';
		$this->adapter = null;
		$this->parcel_packer = null;
		$this->settingsFormHooks = null;
		$this->logger = &\EnviaYa\WooCommerce\Logger\LoggerInstance::getInstance($this->id); 
		$this->pageDetector = new \EnviaYa\WooCommerce\Utils\PageDetector();
		// initialize proxies so we will always have something to work with
		$this->cartProxy = new \EnviaYa\Proxies\LazyClassProxy('stdClass');
		$this->sessionProxy = new \EnviaYa\Proxies\LazyClassProxy('stdClass');
		$this->countryProxy = new \EnviaYa\Proxies\LazyClassProxy('stdClass');
	}

	protected function init_adapter()
	{
		$this->adapter = new \EnviaYa\WooCommerce\Shipping\Adapter\EnviaYa($this->id);
		$this->rates_finder = new EnviaYaApiRatesFinder($this->id, $this->adapter, $this->get_settings());
				
	}

	protected function init_parcel_packer()
	{
		$this->parcel_packer = new \EnviaYa\WooCommerce\Shipping\BaseParcelPacker($this->id);		
	}


	public function register()
	{

		if (!function_exists('is_plugin_active')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		// do not register when WooCommerce is not enabled
		if (!is_plugin_active('woocommerce/woocommerce.php')) {
			return;
		}
		
		// activation hooks
		register_activation_hook( plugin_basename($this->plugin_path), array($this, 'onActivate') );
		register_deactivation_hook(  plugin_basename($this->plugin_path), array($this, 'onDesactivate') );

		//$woocommerce_version = \WC()->version;

		$this->init_adapter();
		$this->init_parcel_packer();
		$this->load_settings();

		if (is_admin()) {


			\EnviaYa\WooCommerce\Admin\EnviaYaAdmin::instance()->register($this->id, $this->plugin_path,$this->settings,$this->version);
			add_action('admin_menu', array($this, 'onAdminMenu'),10);
			
			add_action('plugins_loaded', array($this,'loadPluginTextdomain'));

			//add_action('admin_notices', array($this, 'onLoadAdminNotices'),10);
			add_action('wp_ajax_get_billing_accounts',  array($this, 'getBillingAccounts'));
			add_action('wp_ajax_get_origin_address',  array($this, 'onGetOriginAddress') );
			add_action('wp_ajax_create_shipment',  array($this, 'onCreateShipment') );
			

			add_filter('woocommerce_get_sections_shipping', array( $this, 'onAddShippingSettingsSectionTab'),10 );

			add_filter('woocommerce_hidden_order_itemmeta', array( $this, 'ey_woocommerce_hidden_order_itemmeta'),10 );
			
			
			add_action( 'add_meta_boxes', array($this, 'add_meta_boxes'),10,2);

			add_action('metabox_success_notice', array($this,'metabox_success_notice_callback'));

			
		}else{

			wp_register_style(
				'wc-enviaya-shipping-methods',
				plugins_url('assets/css/public/shipping-methods.css',$this->plugin_path),
				array(),
				$this->version,
				'all'
			);
			
			wp_enqueue_style( 'wc-enviaya-shipping-methods' );
			
			
			
		}

		
		
		
		
		add_action($this->id . '_check_origin_direction', array($this, 'onCheckOriginDirection') );
		add_filter('plugin_action_links_' . plugin_basename($this->plugin_path), array($this, 'onPluginActionLinks'), 1, 1);
		add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
		add_filter($this->id . '_get_plugin_settings', array($this, 'get_plugin_settings'), 10, 1);
		add_filter($this->id . '_is_enabled', array($this, 'is_enabled'), 10, 1);
		add_filter($this->id . '_init_form_fields', array($this, 'updateFormFields'), 1, 1);
		add_filter($this->id . '_service_name', array($this, 'getDefaultServiceName'), 1, 2);
		add_filter($this->id . '_is_cart', array($this->pageDetector, 'is_cart'), 1, 0);
		add_filter($this->id . '_is_checkout', array($this->pageDetector, 'is_checkout'), 1, 0);
		add_action('plugins_loaded', array($this, 'initLazyClassProxies'), 1, 0);
		
		add_action('wp_loaded', array($this, 'calculateShippingOnCheckout'), 1, 0);
		add_action('woocommerce_after_checkout_validation', array($this, 'onCheckoutValidation'), PHP_INT_MAX, 2);
		add_filter('woocommerce_billing_fields', array($this, 'setRequiredFields'), 10, 1);
		add_filter('woocommerce_shipping_fields', array($this, 'setRequiredFields'), 10, 1);

		if( isset($this->settings['service_title_name']) && !empty($this->settings['service_title_name']) ){
			add_filter( 'woocommerce_shipping_package_name', array($this, 'setShippingTitle'), 10, 1);
		}
		
		add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'displayCarrierLogo'), 10, 2 ); 

		add_filter('woocommerce_checkout_update_order_review', array($this, 'clear_wc_shipping_rates_cache'));

		
		add_action( 'enviaya_after_order_created', array($this,'enviaya_after_order_created_callback'),10,2 );


		add_action('template_redirect', array($this,'wcv_download_label'));

		$woocommerce_version = get_option( 'woocommerce_version' );

		if($woocommerce_version >= 8.6){
			//echo '<pre>';var_dump();echo '</pre>';exit();
			require_once(__DIR__.'/../../disable-woocommerce-block.php');

		}
		

	}



	public function custom_checkout_radio_buttons($checkout) {
		
		echo '<div style="display:none">';
		woocommerce_form_field('custom_enviaya_shipping_insurance', array(
			'type' => 'radio',
			'class' => array('my-field-class form-row-wide'),
			'label' => __('Custom Radio Button Label'),
			'options' => array(
				'yes' => __('Quiero asegurar mi envío'),
				'no' => __('No quiero asegurar mi envío.')
			),
			'default' => 'yes'
		), $checkout->get_value('custom_enviaya_shipping_insurance'));
		echo '</div>';
	

		
	}

	public function enviaya_after_order_created_callback( $order_id, $order_status ) {

		$wc_order = wc_get_order( $order_id );
		$wc_order->update_status($order_status);


	}
	

	public function my_custom_checkout_field_process( $cart ) {
		
		$shipping_methods = $rates = WC()->session->get('shipping_for_package_0')['rates'];
		$chosen_shipping_method = $_POST['shipping_method'][0];


		if (isset($shipping_methods[$chosen_shipping_method])) {

			$method_id = $shipping_methods[$chosen_shipping_method]->method_id;
			
			if ($method_id == 'wc-enviaya-shipping' ) {
				
				parse_str($_POST['post_data'], $post_data);
				
				if(empty($post_data)){
					$post_data = $_POST;
				}

				if($post_data['custom_enviaya_shipping_insurance'] == 'yes'){

					$fee = number_format($cart->get_subtotal()*0.05,2,'.','');

					WC()->cart->add_fee( 'Seguro contra decomiso', (float)$fee, true, '' );

				}else{
					$fees = WC()->cart->get_fees();
					
					foreach ($fees as $key => $fee) {
						if($fees[$key]->name === __( "Seguro contra decomiso")) {
							unset($fees[$key]);
						}
					}
					WC()->cart->fees_api()->set_fees($fees);
				}

				
			}
		}
	}

		

	public function wcv_download_label(){

		if ( isset( $_GET['wcv_shipping_label'] ) ) {

			$vendor_id = get_current_user_id();
			
			$order_id  = $_GET['wcv_shipping_label'];

			if ( ! \WCVendors_Pro_Dashboard::check_object_permission( 'order', absint( $_GET['wcv_shipping_label'] ) ) ) {
				return false;
			}
			
			$wc_order = wc_get_order( $order_id );

			if(count($wc_order->get_items( 'shipping' )) > 0) {
				
				$item_shipping = current($wc_order->get_items( 'shipping' ));

				if(!empty($item_shipping->get_meta('booking'))){
					
					$shipment = $item_shipping->get_meta('booking');
					$url = $shipment['label_share_link'];

					header("Location: ".$url, TRUE, 301);
					exit;

				}else{
					echo "Not shipping label found.";
					exit;
					
				}

			}

		}
		
	}
	public function metabox_success_notice_callback($msg){
		
		echo '<div class="ey-alert alert-danger" role="alert">
				'.$msg.'
			</div>';
				
	}

	
	

	public function onUpdateOrder($order_id){
		
		/*
		$wc_order =  wc_get_order( $order_id );

		if( isset($_POST['create_shipment']) && $_POST['create_shipment'] == "1" && isset($_POST['shipping_rate']) ){
			
			if(count($wc_order->get_items('shipping')) > 0) {
				$item_shipping = current($wc_order->get_items('shipping'));
			}
		
			
			if(!$item_shipping->meta_exists('booking')) {

				$shipment = $this->createShipment($wc_order,$_POST['shipping_rate']);
				
				# VL CUSTOM
				if( isset($shipment['id']) && !is_null($shipment['id'])  ){
					do_action('enviaya_after_order_created', $order_id, 'completed' );
					
				}
				
				

			}
			

		}
		*/

	}

	private function createShipment($order, $shipping_rate){

		if(count($order->get_items( 'shipping' )) > 0) {
			$item_shipping = current($order->get_items('shipping'));
			woocommerce_update_order_item_meta($item_shipping->get_id(), 'booking', "");	
		}

		$replace_content = $this->settings['use_fixed_content_description'] == 'yes';

		$shipping_rate_arr = explode(":",$shipping_rate);
		
		$parcels = $this->packOrder($order,$replace_content);
		
		$author_id = null;
		if(isset($this->settings['enable_wcvendors_integration'])){
			$author_id = isset(current($parcels)['author_id']) && $this->settings['enable_wcvendors_integration'] == 'yes' ? current($parcels)['author_id'] : null;
		}

		if(is_null($author_id)){
			foreach($parcels as $k => $parcel){
				$parcels[$k]['author_id'] = NULL;
				
			}
		}
		
		$params = array(
			'carrier' => $shipping_rate_arr[0],
			'carrier_service_code' => $shipping_rate_arr[1],
			'shipment' => array(
				'shipment_type' => 'Package',
				'parcels' => $parcels,
			),
			'origin_direction' => $this->getSenderAddress($author_id),
			'destination_direction' => $this->getShippingAddress($order),
		);
		
		$response = $this->adapter->createShipment($params);
		
		if(isset($item_shipping) && isset($response['response'])){
			woocommerce_update_order_item_meta($item_shipping->get_id(), 'booking', $response['response']);
			if(isset($response['response']['id'])){
				$order->update_status( 'completed' );
				woocommerce_update_order_item_meta($item_shipping->get_id(), 'enviaya_shipment_id', $response['response']['id']);
			}
			

		}

		return (isset($response['response'])) ? $response['response'] : NULL;

		
	}

	private function getShippingAddress($order){

		$address = array();

		$data_shipping = $order->get_data()['shipping'];
		$data_billing = $order->get_data()['billing'];
		
		$fieldsMap = array(
			'first_name' => 'full_name',
			'last_name' => 'full_name',
			'address_1' => 'direction_1',
			'address_2' => 'neighborhood',
			'city' => 'city',
			'postcode' => 'postal_code',
			'state' => 'state_code',
			'country' => 'country_code',
			'phone' => 'phone',
		);
		
		foreach ($fieldsMap as $field => $toKey) {
			
			$value = null;

			if (!empty($data_shipping[$field])) {
				$value = sanitize_text_field($data_shipping[$field]);
			}
			
			if (empty($value)) {
				$value = sanitize_text_field($data_billing[$field]);
			}

			if (!empty($value)) {
				if (isset($address[$toKey])) {
					$address[$toKey].= ' ';
				}

				$address[$toKey].=$value;
			}

		}
		
		return $address;
		
	}

	private function getSenderAddress($author_id = null){

		$origin_direction = array();

		if(is_null($author_id)){

			foreach($this->settings as $k => $v){
				if( strpos($k, 'origin_direction_') !== false ){
					$key = explode('origin_direction_',$k)[1];
					if($key != 'id'){
						if(!is_null($v) && !empty($k)){
							$origin_direction[$key] = $v;
						}
					}
				}
			}

		}else{
				

			$data = get_user_meta($author_id);
			
			// make sure that we have sanitized the input
			if (empty($data)) {
				$data = array();
			} else {
				$data = wc_clean($data);
			}
		
			$fieldsMap = array(
				'first_name' => 'full_name',
				'last_name' => 'full_name',
				'address_1' => 'direction_1',
				'address_2' => 'direction_2',
				'city' => 'city',
				'postcode' => 'postal_code',
				'state' => 'state_code',
				'country' => 'country_code',
				'phone' => 'phone',
			);
			
			foreach ($fieldsMap as $field => $toKey) {
				$value = null;

				if (empty($value) && !empty($data["shipping_{$field}"][0])) {
					$value = sanitize_text_field($data["shipping_{$field}"][0]);
				}
				
				if (empty($value) && !empty($data["billing_{$field}"][0])) {
					$value = sanitize_text_field($data["billing_{$field}"][0]);
				}

				
				if (!empty($value)) {
					if (isset($origin_direction[$toKey])) {
						$origin_direction[$toKey].= ' ';
					}

					$origin_direction[$toKey].=$value;
				}
			}

			
		}
		

		
		return $origin_direction;
		
	}


	public function add_meta_boxes($args1,$args2)
	{
		
		if($args2->post_type == "shop_order"){

			add_meta_box( 
				'woocommerce-enviaya_meta_box', 
				'EnvíaYa!',
				array($this, 'enviaya_meta_box'), 
				'shop_order', 
				'side', 
				'high' 
			);

		}
		

	}

	private function packOrder($order, $replace_content = false){
		
		$parcels = array();
		
		foreach($order->get_items() as $item_line){

			$product = $item_line->get_product();
			
			$post = get_post($product->id);
			$parcelItem['author_id'] = $post->post_author;
			
			$parcelItem = $this->parcel_packer->toParcelItem($product);
			$parcelItem['quantity'] = floatval($item_line->get_quantity());

			$parcel = array(
				'author_id' => $post->post_author,
			);

			if( isset($parcelItem['length']) && isset($parcelItem['width']) && isset($parcelItem['height']) ){
				$parcel['length'] = $parcelItem['length'];
				$parcel['width'] = $parcelItem['width'];
				$parcel['height'] = $parcelItem['height'];
				$parcel['dimension_unit'] = get_option('woocommerce_dimension_unit');
	
			}
			
			if(isset($parcelItem['weight'])){
				$parcel['weight'] = $parcelItem['weight'];
				$parcel['weight_unit'] = get_option('woocommerce_weight_unit');
			}
			
			if (!empty($parcel)) {
				$parcel['quantity'] = $parcelItem['quantity'];
				$parcel['content'] = $parcelItem['name'];
				$parcel['value'] = $parcelItem['value'];
				$parcel['value_currency'] = get_option('woocommerce_currency');

				if($replace_content){
					$parcel['content'] = $this->settings['fixed_content_description'];
				}
				
				$parcels[] = $parcel;
			}
			
		}
		
		return $parcels;

	}

	public function enviaya_meta_box($post)
	{	
			
			$order_id = get_the_ID();
			$order = wc_get_order( $order_id );

			if(!is_null($order)) {
				if(count($order->get_items('shipping')) > 0) {
					$item_shipping = current($order->get_items('shipping'));
					
					if($item_shipping->meta_exists('booking')) {

						$booking  = $item_shipping->get_meta('booking');
						
						if( empty($booking['errors']) && ( isset($booking['id']) && !empty($booking['id']) ) )  { ?>
										
										<ul class="order_actions submitbox">
											<li class="wide">
											<label for="add_order_note"><?php echo esc_html_e('Status', 'wc-enviaya-shipping') ?></label>
											<p style="margin-top:0px;margin-bottom:0px;"><?php echo esc_html($booking['shipment_status']) ?></p>
											</li>
											<li class="wide">
											<label for="add_order_note"><?php echo esc_html_e('Carrier name', 'wc-enviaya-shipping') ?></label>
											<p style="margin-top:0px;margin-bottom:0px;"><?php echo esc_html($booking['carrier']) ?></p>
											</li>
											<li class="wide">
											<label for="add_order_note"><?php echo esc_html_e('Tracking no', 'wc-enviaya-shipping') ?></label>
											<p style="margin-top:0px;margin-bottom:0px;"><?php echo esc_html($booking['carrier_shipment_number']) ?></p>
											</li>
											<li class="wide">
											<label for="add_order_note"><?php echo esc_html_e('Enviaya shipment number', 'wc-enviaya-shipping') ?></label>
											<p style="margin-top:0px;margin-bottom:0px;"><a target="_blank" href="<?php echo esc_url($booking['id']."/show_details")?>"><?php echo esc_html($booking['enviaya_shipment_number']) ?></p>
											</li>
											<li class="wide">
											<a  target="_blank"  class="button save_order button-primary" href="<?php echo esc_url($booking['label_share_link']) ?>" ><?php echo esc_html(__('Download Label', 'wc-enviaya-shipping')) ?></a>
											</li>
										</ul>
									<?php
						} else {
							
							if(isset($bookin['errors'])){
								foreach($booking['errors'] as $err){
									do_action('metabox_success_notice', $err);
								}
							}
							

							$parcels = $this->packOrder($order);

							$author_id = isset(current($parcels)['author_id']) && $this->settings['enable_wcvendors_integration'] == 'yes' ? current($parcels)['author_id'] : null;

							$destination_direction_short = array(
								'country_code' => $order->get_shipping_country(),
								'postal_code' => $order->get_shipping_postcode(),
							);

							$origin_direction = $this->getSenderAddress($author_id);

							$origin_direction_short = array(
								'country_code' => $origin_direction['country_code'],
								'postal_code' => $origin_direction['postal_code']
							);


							$parcels_short = array_map(function ($v) {
								return array(
									'length' => $v['length'],
									'width' => $v['width'],
									'height' => $v['height'],
									'dimension_unit' => $v['dimension_unit'],
									'weight' => $v['weight'],
									'weight_unit' => $v['weight_unit'],
									'quantity' => $v['quantity'],
								);
							}, $parcels);


							$params = array(
								'shipment' => array(
									'shipment_type' => 'Package',
									'parcels' => $parcels_short,
								),
								'origin_direction' => $origin_direction_short,
								'destination_direction' => $destination_direction_short,
							);
							
							$cacheKey = $this->adapter->getCacheKey($params);
							
							$rates = $this->adapter->getCacheValue($cacheKey);
							
							if(empty($rates) || is_null($rates)) {

								$request_params = array_merge(
									array(
										'enviaya_account' => $this->adapter->getEnviayaAccount(),
										'api_key' =>  $this->adapter->getApiKey(),
										'api_application_id' => 1,
									),
									$params
	
								);
								$settings = $this->get_settings();
								$this->rates_finder->setSettings($settings);
								$rates = $this->rates_finder->findShippingRates($request_params);
								
								if(!empty($rates) && !is_null($rates)) {
									$this->adapter->setCacheValue($cacheKey, $rates, $this->cacheExpirationInSecs);	
								}
							}

							$shipping_method = reset($order->get_shipping_methods());
							
							if(!empty($shipping_method->get_meta('carrier_name')) && !empty($shipping_method->get_meta('carrier_service_code'))) {
								$selected = $shipping_method->get_meta('carrier_name')."/".$shipping_method->get_meta('carrier_service_code');
							}

							if(!empty($rates)) { ?>
											<?php if(isset($_GET['message'])):?>
												<?php foreach($booking['errors'] as $err):?>
													<?php do_action('metabox_success_notice', $err)?>
												<?php endforeach;?>
											<?php endif;?>
											<div id="ey-loading-metabox" style="display:none">
											<img src="<?php echo plugins_url('assets/images/enviaya-loading.gif', dirname(dirname(str_replace('phar://', '', __FILE__))))?>" />
											
											</div>
											<ul class="order_actions submitbox">
												<li class="wide">
												<label for="add_order_note"><?php echo esc_html_e('Select a shipping service:', 'wc-enviaya-shipping') ?></label>
													<select name="shipping_rate">
														<?php foreach($rates as $rate):?>
								
															<?php if(isset($rate['meta_data']['carrier_name']) && isset($rate['meta_data']['carrier_service_code'])): ?>
																<option value="<?php echo $rate['meta_data']['carrier_name'].":".$rate['meta_data']['carrier_service_code'] ?>" <?php selected($selected, $rate['meta_data']['carrier_name']."/".$rate['meta_data']['carrier_service_code']) ?>><?php echo $rate['label']?> ( <?php echo number_format((float)$rate['meta_data']['total_amount'], 2)?> <?php echo $rate['meta_data']['currency']?> )</option>
															<?php endif;?>
														<?php endforeach;?>
													</select>
												</li>
												<li class="wide">
												<button type="button" class="button save_order button-primary wc-enviaya-shipping-create-shipment" name="create_shipment" value="<?php echo esc_html(__('Create shipment', 'wc-enviaya-shipping')) ?>"><?php echo esc_html(__('Create shipment', 'wc-enviaya-shipping')) ?></button>
												</li>
											</ul>
										<?php
							}
						}
					} else {
						
						$parcels = $this->packOrder($order);

						$author_id = isset(current($parcels)['author_id']) && $this->settings['enable_wcvendors_integration'] == 'yes' ? current($parcels)['author_id'] : null;

						$destination_direction_short = array(
							'country_code' => $order->get_shipping_country(),
							'postal_code' => $order->get_shipping_postcode(),
						);

						$origin_direction = $this->getSenderAddress($author_id);

						$origin_direction_short = array(
							'country_code' => $origin_direction['country_code'],
							'postal_code' => $origin_direction['postal_code']
						);


						$parcels_short = array_map(function ($v) {
							return array(
								'length' => $v['length'],
								'width' => $v['width'],
								'height' => $v['height'],
								'dimension_unit' => $v['dimension_unit'],
								'weight' => $v['weight'],
								'weight_unit' => $v['weight_unit'],
								'quantity' => $v['quantity'],
							);
						}, $parcels);


						$params = array(
							'shipment' => array(
								'shipment_type' => 'Package',
								'parcels' => $parcels_short,
							),
							'origin_direction' => $origin_direction_short,
							'destination_direction' => $destination_direction_short,
						);


						$cacheKey = $this->adapter->getCacheKey($params);

						$rates = $this->adapter->getCacheValue($cacheKey);
						
						
						if(empty($rates) || is_null($rates)) {

							$request_params = array_merge(
								array(
									'enviaya_account' => $this->adapter->getEnviayaAccount(),
									'api_key' =>  $this->adapter->getApiKey(),
									'api_application_id' => 1,
								),
								$params

							);
							$settings = $this->get_settings();
							$this->rates_finder->setSettings($settings);
							$rates = $this->rates_finder->findShippingRates($request_params);
							
							if(!empty($rates) && !is_null($rates)) {
								$this->adapter->setCacheValue($cacheKey, $rates, $this->cacheExpirationInSecs);	
							}
						}
				
						
						$shipping_method = reset($order->get_shipping_methods());
						
						if(!empty($shipping_method->get_meta('carrier_name')) && !empty($shipping_method->get_meta('carrier_service_code'))) {
							$selected = $shipping_method->get_meta('carrier_name')."/".$shipping_method->get_meta('carrier_service_code');
						}
						
						if(!empty($rates)) { ?>
										<div id="ey-loading-metabox" style="display:none">
											<img src="<?php echo plugins_url('assets/images/enviaya-loading.gif', dirname(dirname(str_replace('phar://', '', __FILE__))))?>" />
											
										</div>
										<ul class="order_actions submitbox">
											<li class="wide">
											<label for="add_order_note"><?php echo esc_html_e('Select a shipping service:', 'wc-enviaya-shipping') ?></label>
												<select name="shipping_rate">
													<?php foreach($rates as $rate):?>
														
														<?php if(isset($rate['meta_data']['carrier_name']) && isset($rate['meta_data']['carrier_service_code'])): ?>
															<?php if(!is_null($selected)):?>
																<option value="<?php echo $rate['meta_data']['carrier_name'].":".$rate['meta_data']['carrier_service_code'] ?>" <?php selected($selected, $rate['meta_data']['carrier_name']."/".$rate['meta_data']['carrier_service_code']) ?>><?php echo $rate['label']?> ( <?php echo number_format((float)$rate['meta_data']['total_amount'], 2)?> <?php echo $rate['meta_data']['currency']?> )</option>
															<?php else:?>
																<option value="<?php echo $rate['meta_data']['carrier_name'].":".$rate['meta_data']['carrier_service_code'] ?>" ><?php echo $rate['label']?> ( <?php echo number_format((float)$rate['meta_data']['total_amount'], 2)?> <?php echo $rate['meta_data']['currency']?> )</option>
															<?php endif?>	
														<?php endif;?>
													<?php endforeach;?>
												</select>
											</li>
											<li class="wide">
											<button id="wc-enviaya-shipping-create-shipment" type="submit" class="button save_order button-primary wc-enviaya-shipping-create-shipment" name="create_shipment" value="<?php echo esc_html(__('Create shipment', 'wc-enviaya-shipping')) ?>"><?php echo esc_html(__('Create shipment', 'wc-enviaya-shipping')) ?></button>
											</li>
										</ul>
			
									<?php
						}

					}
				}
			}
			
		}

	public function ey_woocommerce_hidden_order_itemmeta($arr){
	
		return array_merge($arr,array(
			'rate_id',
			'shipment_id',
			'dynamic_service_name',
			'date',
			'carrier_name',
			'carrier_service_name',
			'carrier_service_code',
			'carrier_logo_url',
			'estimated_delivery',
			'est_transit_time_hours',
			'net_shipping_amount',
			'net_surcharges_amount',
			'net_total_amount',
			'vat_amount',
			'vat_rate',
			'total_amount',
			'currency',
			'list_total_amount',
			'list_net_amount',
			'list_vat_amount',
			'list_total_amount_currency',
			'enviaya_service_name',
			'enviaya_service_code'
		));
		
	}

	public function clear_wc_shipping_rates_cache(){
		$packages = WC()->cart->get_shipping_packages();
	
		foreach ($packages as $key => $value) {
			$shipping_session = "shipping_for_package_$key";
	
			unset(WC()->session->$shipping_session);
		}
	}

	public function displayCarrierLogo( $label, $method ) { 

		if($method->get_method_id() == 'wc-enviaya-shipping'){
			
			$rate_id = $method->get_meta_data()['rate_id'];
			
			if($rate_id != 'enviaya-local-pickup'){

				$carrier_logo_height_map = array(
					'99minutos' => '20px',
					'sendex' => '20px',
					'redpack' => '9px',
					'ampm' => '18px',
					'fedex' => '12px',
					'ups' => '25px',
					'ivoy' => '18px',
				);

				$carrier = strtolower($method->get_meta_data()['carrier_name']);
				
				$carrier_logo_height = isset($carrier_logo_height_map[$carrier]) ? $carrier_logo_height_map[$carrier] : '10px';
				$logo_src = plugins_url('/assets/images/carriers/svg/'.$carrier.'.svg', dirname(dirname(str_replace('phar://', '', __FILE__))));
				if( isset($this->settings['display_carrier_logo']) && $this->settings['display_carrier_logo'] == 'yes'){
					$label = '<img class="ey-carrier-logo" src="'.$logo_src.'" style="height:'.$carrier_logo_height.'; margin-right:5px; display:inline"/><span class="ey-label-text">'.$label.'</span>'; 
				}

			}

		}	
		
		return $label;
	}

	public function setShippingTitle( $name ) {

		return $this->settings['service_title_name'];

	}

	protected function load_settings()
	{		
		$this->init_settings();
		
		$this->settings = array_merge($this->settings, (array)get_option($this->option_key, array()));
		
		$this->logger->setEnabled($this->settings['debug'] == 'yes');

		$this->adapter->setSettings($this->settings);
	}

	protected function init_settings()
	{

		$this->settings = array(
			'test_mode' => 'no',
			'enabled' => 'yes',
			'fetch_rates_page_condition' => 'cart',
			'display_carrier_logo' => 'no',
			'display_carrier_name' => 'yes',
			'display_service_name' => 'dynamic_service_name',
			'display_delivery_time' => 'no_delivery_time',
			'enable_standard_flat_rate' => 'no',
			'contingency_standard_shipping_title' => __('Standard shipping', 'wc-enviaya-shipping'),
			'standard_flat_rate' => 100,
			'enable_express_flat_rate' => 'no',
			'contingency_express_shipping_title' => __('Express Flat Rate', 'wc-enviaya-shipping'),
			'express_flat_rate' => 150,
			'use_fixed_content_description' => 'no',
			'fixed_content_description' => '',
			'automatic_booking_shipment' => 'no' ,
			'enable_dokan_integration' => 'no',
			'enable_wcvendors_integration' => 'no',
			'enable_shipping_zones' => 'no',
			'debug' => 'no',
			'cache' => 'yes',
			'cache_expiration_in_secs' => 12 * 60 * 60,
			'timeout' => 10,
			'remove_settings' => 'no',
			'enable_origins_by_product' => 'no'

			
		);
		
	}

	public function get_plugin_settings()
	{
		return $this->settings;
	}

	public function onActivate()
	{

		if (wp_next_scheduled ( $this->id . '_check_origin_direction' )) { 
            wp_schedule_event(time(), 'daily', $this->id . '_check_origin_direction');
        }
		

		$old_settings = get_option('woocommerce_enviaya_settings');
		$new_settings = get_option('wc_enviaya_shipping_settings');
		if($old_settings){
			
			
			if(isset($old_settings['api_key_production']) && !empty($old_settings['api_key_production'])){
				$new_settings['production_api_key'] = $old_settings['api_key_production'];
			}

			if(isset($old_settings['api_key_test']) && !empty($old_settings['api_key_test'])){
				$new_settings['test_api_key'] = $old_settings['api_key_test'];
			}

			if(isset($old_settings['enabled_test_mode']) && !empty($old_settings['enabled_test_mode'])){
				$new_settings['test_mode'] = $old_settings['enabled_test_mode'];
			}

			if(isset($old_settings['enviaya_account']) && !empty($old_settings['enviaya_account'])){
				$new_settings['account'] = $old_settings['enviaya_account'];
			}

			if(isset($old_settings['account_id']) && !empty($old_settings['account_id'])){
				$new_settings['account_id'] = $old_settings['account_id'];
			}

			if(isset($old_settings['origin_address']) && !empty($old_settings['origin_address'])){
				
				$origin_address = json_decode(str_replace('||', '"', $old_settings['origin_address']),true);
				foreach($origin_address as $k => $v){

					if(!is_null($v) && !empty($v)){
						$key = 'origin_direction_'.$k;
						$new_settings[$key] = $v;
					
					}
					
				}		

			}

			update_option('wc_enviaya_shipping_settings', $new_settings);
			delete_option('woocommerce_enviaya_settings');

		}
	}

	public function onDesactivate(){
		
		if (wp_next_scheduled ( $this->id . '_check_origin_direction' )) { 
            wp_clear_scheduled_hook( $this->id . '_check_origin_direction' );
        }

		if( isset($this->settings['remove_settings']) && $this->settings['remove_settings'] == 'yes' ){

			delete_option('wc_enviaya_shipping_settings');
			delete_option('woocommerce_enviaya_settings');

			

		}

		

	}

	public function onLoadAdminNotices(){
		
		if( isset($this->settings['updated_origin_direction']) && is_null( $_COOKIE['ENVIAYA_NO_UPDATE_ORIGIN_ADDRESS'] ) ){
			if( $this->settings['updated_origin_direction']['notice'] == 'yes' ){
				
				$update_url = wp_nonce_url(
					add_query_arg( array('do_update_origin_address' => 'true', 'subtab' => 'sender' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=' . $this->id ) ),
					'update_origin_address_nonce',
				);
				
				$contact_keys = ['title', 'phone', 'email'];
				$direction_keys = ['direction_1', 'direction_2', 'neighborhood', 'district', 'postal_code', 'city', 'state_code', 'country_code'];
				
				$current_woo_direction_arr = json_decode($this->settings['updated_origin_direction']['current_woo_direction'],true);
				
				$current_woo_direction_arr['title'] = (!is_null($current_woo_direction_arr['company'])) ? $current_woo_direction_arr['full_name']." ( ".$current_woo_direction_arr['company']." )" : $current_woo_direction_arr['full_name']; 
				
				$current_enviaya_direction_arr = json_decode($this->settings['updated_origin_direction']['current_enviaya_direction'],true);
				
				$current_enviaya_direction_arr['title'] = (!is_null($current_enviaya_direction_arr['company'])) ? $current_enviaya_direction_arr['full_name']." ( ".$current_enviaya_direction_arr['company']." )" : $current_enviaya_direction_arr['full_name']; 
				
				$current_woo_direction_txt = "";
				
				$current_enviaya_direction_txt = "";

				foreach($contact_keys as $k){

					if( isset($current_woo_direction_arr[$k]) && !is_null($current_woo_direction_arr[$k]) ){

						$current_woo_direction_txt.=$current_woo_direction_arr[$k];
						$current_woo_direction_txt.="<br/>"; 
      
					}

					if( isset($current_enviaya_direction_arr[$k]) && !is_null($current_enviaya_direction_arr[$k]) ){

						$current_enviaya_direction_txt.=$current_enviaya_direction_arr[$k];
						$current_enviaya_direction_txt.="<br/>"; 
      
					}
				}

				$current_woo_direction_txt.="<br/>"; 
				$current_enviaya_direction_txt.="<br/>"; 
				
				foreach($direction_keys as $k){

					if( isset($current_woo_direction_arr[$k]) && !is_null($current_woo_direction_arr[$k]) ){
						$current_woo_direction_txt.=$current_woo_direction_arr[$k];
						
						if($k == 'postal_code' ){
							$current_woo_direction_txt.=" "; 
						}elseif($k == 'city'){
							$current_woo_direction_txt.=", "; 
						}else{
							$current_woo_direction_txt.="<br/>"; 
						}
      
					}

					if( isset($current_enviaya_direction_arr[$k]) && !is_null($current_enviaya_direction_arr[$k]) ){
						$current_enviaya_direction_txt.=$current_enviaya_direction_arr[$k];
						
						if($k == 'postal_code' ){
							$current_enviaya_direction_txt.=" "; 
						}elseif($k == 'city'){
							$current_enviaya_direction_txt.=", "; 
						}else{
							$current_enviaya_direction_txt.="<br/>"; 
						}
      
					}

				}
				
				$nonce =  wp_create_nonce('ajax-nonce');
				echo    esc_html('<div class="notice notice-warning is-dismissible" id="origin_direction_change_notice">
							<p style="display:flex; align-items:center"><img src="'.plugins_url('assets/images/enviaya-icon.png', dirname(dirname(str_replace('phar://', '', __FILE__)))).'"><strong style="margin-left:10px"> '.__('The origin direction has changed from EnvíaYa account','wc-enviaya-shipping').'</strong></p>
							<p>'.__('The origin direction has changed from EnvíaYa account and It is different to origin direction configured on plugin.','wc-enviaya-shipping').'</p>
							<p><strong>'.__('Current origin direction on plugin','wc-enviaya-shipping').':</strong></p>
							<p>'.strtoupper($current_woo_direction_txt).'</p>
							<p><strong>'.__('Current origin direction on EnvíaYa app','wc-enviaya-shipping').':</strong></p>
							<p>'.strtoupper($current_enviaya_direction_txt).'</p>
							<p>'.__('Do you want to update the origin direction configured on plugin?','wc-enviaya-shipping').'</p>
							<p class="submit">
								<a href="'. esc_url($update_url) .'" class="wc-update-now button-primary">'.__('Yes, update','wc-enviaya-shipping').'</a>
								<a onclick="myFunction()" href="#no_update" class="button-secondary">'.__('No, no update','wc-enviaya-shipping').'</a>
							</p>
						</div>
						<script>
							function myFunction() {
								jQuery.post("'.admin_url('admin-ajax.php').'", {action:"do_no_update_origin_address", nonce:"'.$nonce.'"}).done(function( res ) { jQuery("#origin_direction_change_notice").remove()  })
							}
						</script>');

						
			}
		}
		
	}

	public function setRequiredFields($fields)
	{

		return $fields;
	}

	
	public function onCheckOriginDirection(){

		if( is_null( $_COOKIE['ENVIAYA_NO_UPDATE_ORIGIN_ADDRESS'] ) && isset($this->settings['origin_direction_id'])  ){

				$id = $this->settings['origin_direction_id'];
				
				$params = array(
					'api_key' => $this->adapter->getApiKey(),
					'get_destinations' => 'false',
				);
				
				$result = $this->adapter->getOriginDirections($params);

				if (!empty($result['error']['message'])) {
					return false;
				}

				if(isset($result['response']['directions'])){
					$directions = $result['response']['directions'];
				}else{
					$directions = $result['directions'];
				}
				
				$direction = null;
				foreach($directions as $direction){
					if($id == $direction['id']){
						$direction = $direction;
						break;
					}
				}

				if(!is_null($direction)){

					$flag = false;

					$settings = $this->settings;
					
					$origin_direction_wp = array();
					foreach($direction as $k => $v){

						$key = "origin_direction_".$k;

						if(isset($settings[$key])){

							$origin_direction_wp[$key] = $settings[$key];
							if($settings[$key] != $v){

								$flag = true;
								
							}
							
						}

					}
					
					if($flag){

						$settings['updated_origin_direction'] = array(
							'notice' => 'yes',
							'current_woo_direction' => json_encode($origin_direction_wp),
							'current_enviaya_direction' => json_encode($direction),
						);
	
						$settings = apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $settings);
						
						update_option('wc_enviaya_shipping_settings', $settings);
	
					}


				}

		}
		
	}
	

	public function onCheckoutValidation($postedData, $checkoutErrors)
	{		
		if ($this->settings['validateAddress'] != 'yes') {
			return;
		}
		
		$validationErrors = $this->sessionProxy->get($this->id . '_validationErrors');
		if (empty($validationErrors)) {
			$validationErrors = array();
		}

		$this->logger->debug(__FILE__, __LINE__, 'onCheckoutValidation: ' . print_r($validationErrors, true));

		foreach ($validationErrors as $fieldKey => $errors) {
			$errorPrefix = '';
			if ($fieldKey == 'origin') {
				$errorPrefix = __('From Address:', 'wc-enviaya-shipping');
			} else if ($fieldKey == 'destination') {
				$errorPrefix = __('Shipping Address:', 'wc-enviaya-shipping');
			}

			foreach ($errors as $idx => $error) {
				$checkoutErrors->add($this->id . '_validation_error_' . $idx, sprintf('<strong>%s</strong> %s', $errorPrefix, $error));
			}
		}
	}

	public function onAdminMenu()
	{
		
		add_menu_page(
			'EnvíaYa! for WooCommerce',
			'EnvíaYa!',
			'manage_options',
			'admin.php?page=wc-settings&tab=shipping&section=' . $this->id,
			'',
			plugins_url('assets/images/enviaya-icon.png', dirname(dirname(str_replace('phar://', '', __FILE__)))),
			26
		);

		add_submenu_page('woocommerce', 'EnvíaYa!', 'EnvíaYa!', 'manage_options', 'admin.php?page=wc-settings&amp;tab=shipping&amp;section=wc-enviaya-shipping');

	}

	

	public function onPluginActionLinks($links)
	{
		$link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=shipping&section=' . $this->id), __('Settings', 'wc-enviaya-shipping'));
		array_unshift($links, $link);
		return $links;
	}


	public function updateFormFields($formFields)
	{
		
		return $this->adapter->updateFormFields($formFields);
	}

	public function getDefaultServiceName($name, $service)
	{
		if (empty($name)) {
			$services = $this->adapter->getServices();
			if (!empty($services[$service])) {
				$name = $services[$service];
			}
		}

		return $name;
	}

	public function initLazyClassProxies()
	{
		// it can't work in the ADMIN or when WC is undefined
		if (!function_exists('WC')) {
			return;
		}

		if(is_admin()){
			$this->countryProxy = new \EnviaYa\Proxies\LazyClassProxy('WC_Countries');
		}else{		
			$this->cartProxy = new \EnviaYa\Proxies\LazyClassProxy('WC_Cart', WC()->cart);
			$this->sessionProxy = new \EnviaYa\Proxies\LazyClassProxy(apply_filters('woocommerce_session_handler', 'WC_Session_Handler'), WC()->session);
		}
		
	}

	public function calculateShippingOnCheckout()
	{
		if ($this->settings['fetch_rates_page_condition'] != 'checkout') {
			return;
		}

		if (!apply_filters($this->id . '_is_checkout', false)) {
			$this->sessionProxy->set($this->id . '_' . __FUNCTION__, false);
			return;
		}

		$this->logger->debug(__FILE__, __LINE__,  __FUNCTION__);

		$packages = $this->cartProxy->get_shipping_packages();
		if (is_array($packages) && !empty($packages) && !$this->sessionProxy->get($this->id . '_' . __FUNCTION__)) {
			foreach ($packages as $packageKey => $package) {
				$sessionKey = 'shipping_for_package_' . $packageKey;
				$this->sessionProxy->set($sessionKey, null);
			}
	
			$this->cartProxy->calculate_shipping();
			$this->cartProxy->calculate_totals();	

			$this->sessionProxy->set($this->id . '_' . __FUNCTION__, true);
		}
	}

	public function loadPluginTextdomain(){

		load_plugin_textdomain('wc-enviaya-shipping', false, 'wc-enviaya-shipping/languages');
		
	}

    public function add_shipping_method($methods)
	{
		
		$methods[$this->id] = '\EnviaYa\WooCommerce\Shipping\EnviaYaShippingMethod';
		
		return $methods;
	}

	public function is_enabled($enabled = false)
	{
		
		if ($this->settings['enabled'] == 'yes') {
			$enabled = true;
		}

		return $enabled;
	}

	public function onAddShippingSettingsSectionTab( $section )
	{

		$section['wc-enviaya-shipping'] = 'EnvíaYa!';
		
		return $section;
	}

	public function onCreateShipment(){
		
		$action = sanitize_text_field($_POST['action']);
		if($action == 'create_shipment' && isset($_POST['order_id']) &&  isset($_POST['shipping_rate']) ) {

			$order_id = $_POST['order_id'];
			$shipping_rate = $_POST['shipping_rate'];

			$wc_order =  wc_get_order( $order_id );
			
			if(count($wc_order->get_items('shipping')) > 0) {
				$item_shipping = current($wc_order->get_items('shipping'));
			}
			
			if(!$item_shipping->meta_exists('booking')) {
				$shipment = $this->createShipment($wc_order,$shipping_rate);
			}else{
				$booking = $item_shipping->get_meta('booking');
				
				if( (is_array($booking) && isset($booking['errors'])) || empty($booking) ){
					$shipment = $this->createShipment($wc_order,$shipping_rate);
				}
				
			}
			
			if( !is_null($shipment) ){
				if(empty($shipment['errors'])){
					wp_send_json_success($shipment);
					
	
				}else{
					wp_send_json_error($shipment['errors'],400);
				}
			}else{
				wp_send_json_error(array('errors' => []),400);
			}
			
			
		}
		
	}

	public function onGetOriginAddress(){

		$action = sanitize_text_field($_POST['action']);

		$api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
		
		if($action === 'get_origin_address' && !empty($api_key) ) {

			$params = array(
				'api_key' => $api_key,
				'get_destinations' => 'false',
			);

			$full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';

			if(!empty($full_name)){
				$params['full_name'] = $full_name;
			}else{
				$params['per_page'] = '50';
			}
			
			$result = $this->adapter->getOriginDirections($params);
			
			if (!empty($result['error']['message'])) {
				wp_send_json_error($result['error']['message'],$result['error']['code']) ;
			}			
			
			
			if(isset($result['response']['directions'])){
				$directions = $result['response']['directions'];
			}else{
				$directions = $result['directions'];
			}

			
			
			$this->logger->debug(__FILE__, __LINE__, 'directions: ' . print_r($directions, true));
			
			wp_send_json_success($directions);
			
		}
		
	}

	public function getBillingAccounts(){

		
		$action = sanitize_text_field($_POST['action']);
		
		if($action == 'get_billing_accounts' ) {
			
			$api_key = sanitize_text_field($_POST['api_key']);
			
			$result = $this->adapter->getBillingAccounts(array(
				'api_key' => $api_key,
				'function' => __FUNCTION__
			));
			
			
			if (!empty($result['error']['message'])) {
				wp_send_json_error($result['error']['message'],400) ;
			}			
			
			if(isset($result['response']['enviaya_accounts'])){
				$accounts = $result['response']['enviaya_accounts'];
			}else{
				$accounts = $result['enviaya_accounts'];
			}
			
		
			$this->logger->debug(__FILE__, __LINE__, 'accounts: ' . print_r($accounts, true));
			
			wp_send_json_success($accounts);
		}
	}

	public function get_settings()
	{
		$settings = $this->settings;

		return $settings;
	}

};


endif;

