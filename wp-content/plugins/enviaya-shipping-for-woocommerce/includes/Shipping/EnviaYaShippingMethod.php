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

if (!class_exists(__NAMESPACE__ . '\\EnviaYaShippingMethod')){

	class EnviaYaShippingMethod extends \WC_Shipping_Method
	{

		protected $adapter;
		protected $weight_unit;
		protected $dimension_unit;
		protected $cacheExpirationInSecs = 7 * 24 * 60 * 60; // 7 days
		protected $rates_finder;
		protected $parcel_packer;
		protected $cart_proxy;
		protected $countries_proxy;
		protected $customer_proxy;
		protected $session_proxy;
		protected $preview_label;


		public function __construct()
		{
			parent::__construct();
			

			$this->id = 'wc-enviaya-shipping';

			$this->adapter = new \EnviaYa\WooCommerce\Shipping\Adapter\EnviaYa($this->id);

			$this->weight_unit = get_option('woocommerce_weight_unit');
			$this->dimension_unit = get_option('woocommerce_dimension_unit');

			$this->parcel_packer = null;

			$this->supports = array(
				'settings',
			);

			$this->cart_proxy = new \EnviaYa\Proxies\LazyClassProxy('WC_Cart', WC()->cart);
			$this->countries_proxy = new \EnviaYa\Proxies\LazyClassProxy('WC_Countries', WC()->countries);
			$this->customer_proxy = new \EnviaYa\Proxies\LazyClassProxy('WC_Customer', WC()->customer);
			$this->session_proxy = new \EnviaYa\Proxies\LazyClassProxy(apply_filters('woocommerce_session_handler', 'WC_Session_Handler'), WC()->session);

			

			$this->init();
		}

		public function admin_options(){
			
		
			?>
			
			<div id="poststuff" class="woocommerce-reports-wide">
				<div class="postbox">
					<div class="stats_range">
						<ul class="wc-enviaya-shipping-tab-menu">
							<li data-tab="access" class="active"><a href="javascript:;"><?php echo esc_html( __('Access', 'wc-enviaya-shipping') )?></a></li>
							<li data-tab="rating"><a href="javascript:;"><?php echo esc_html( __('Rating', 'wc-enviaya-shipping') )?></a></li>
							<li data-tab="advanced" ><a href="javascript:;"><?php echo esc_html( __('Advanced Configuration', 'wc-enviaya-shipping') )?></a></li>
							<?php if($this->settings['enable_origins_by_product'] == "yes") :?>
								<li data-tab="locations" ><a href="javascript:;"><?php echo esc_html( __('Locations', 'wc-enviaya-shipping') )?></a></li>
							<?php endif;?>
							<li data-tab="sender" ><a href="javascript:;"><?php echo esc_html( __('Sender Address', 'wc-enviaya-shipping') )?></a></li>
							<li data-tab="technical" ><a href="javascript:;"><?php echo esc_html( __('Technical', 'wc-enviaya-shipping') )?></a></li>
							<li data-tab="status" ><a href="javascript:;"><?php echo esc_html( __('Status', 'wc-enviaya-shipping') )?></a></li>
						</ul>
					</div>
					<div id="enviaya_admin_notices_container" class="inside">
					</div>
					<div class="inside">
						<div class="main">
						
							<div class="chart-container">
								<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-access-tab-content">
									<?php $this->form_fields = $this->getAccessFormFields();parent::admin_options(); ?>
								</div>
								<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-rating-tab-content" style="display:none">
									<?php $this->form_fields = $this->getRatingFormFields();parent::admin_options(); ?>
								</div>
								<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-advanced-tab-content" style="display:none">
									<?php $this->form_fields = $this->getAdavancedFormFields();parent::admin_options(); ?>
								</div>
								<?php if($this->settings['enable_origins_by_product'] == "yes") :?>
									<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-locations-tab-content" style="display:none">
										<?php include_once dirname( __FILE__ ) . '/../Admin/views/html-admin-tab-locations.php'; ?>
									</div>
								<?php endif;?>
								<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-zones-tab-content" style="display:none">
								</div>
								<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-sender-tab-content" style="display:none">
									<?php $this->form_fields = $this->getSenderFormFields();parent::admin_options(); ?>
								</div>
								<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-technical-tab-content" style="display:none">
									<?php $this->form_fields = $this->getTechnicalFormFields();parent::admin_options(); ?>
								</div>
								<div class="wc-enviaya-shipping-tab-content" id="wc-enviaya-shipping-status-tab-content" style="display:none">
								<?php $this->form_fields = $this->getStatusFormFields();parent::admin_options(); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php 
			
			if (!isset($this->settings['production_api_key']) || empty($this->settings['production_api_key'])) {

			?>
			<a href="#TB_inline?width=600&height=550&inlineId=wizard-setup-modal" style="display: none;" id="thickbox" class="thickbox">click here</a>
			<div  id="wizard-setup-modal" style="display:none">
				
					<div class="wizard-setup-step" id="wizard-setup-step-1">
						<h2><?php echo esc_html(__("Hey there! Let’s get started and configure your new shipping plugin.", 'wc-enviaya-shipping')) ?></h2>
						<p><?php echo esc_html(__("First of all, we will need your API keys to get started. You can get it here:", 'wc-enviaya-shipping'))?> <a href="<?php echo esc_url('https://app.enviaya.com.mx/api_keys')?> " target="_blank"><?php echo esc_html(__("Get my API keys", 'wc-enviaya-shipping')) ?></a></p>
						<table class="form-table">
							<tbody>
							<tr valign="top">
									<th>
										<h3 class="wc-settings-sub-title " id="woocommerce_wc-enviaya-shipping_access_title"><?php echo esc_html(__('API Keys', 'wc-enviaya-shipping')) ?></h3>
									</th>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="woocommerce_wc-enviaya-shipping_production_api_key"><?php echo esc_html(__('API Key', 'wc-enviaya-shipping')) ?></label>
									</th>	
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text">
												<span><?php echo esc_html(__('API Key', 'wc-enviaya-shipping')) ?></span>
											</legend>
											<input class="input-text regular-input required" type="text" name="woocommerce_wc-enviaya-shipping_production_api_key" id="woocommerce_wc-enviaya-shipping_production_api_key" style="<?php echo esc_attr('width:100%') ?>">
										</fieldset>
									</td>			
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="woocommerce_wc-enviaya-shipping_test_api_key"><?php echo esc_html(__('Test API Key', 'wc-enviaya-shipping')) ?></label>
									</th>	
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text">
												<span><?php echo esc_html(__('Test API Key', 'wc-enviaya-shipping')) ?></span>
											</legend>
											<input class="input-text regular-input required" type="text" name="woocommerce_wc-enviaya-shipping_test_api_key" id="woocommerce_wc-enviaya-shipping_test_api_key" style="<?php echo esc_attr('width:100%') ?>">
										</fieldset>
									</td>			
								</tr>
								<tr valign="top">
									<th>
										<label for="woocommerce_wc-enviaya-shipping_test_mode"><?php echo esc_html(__('Enable Test Mode', 'wc-enviaya-shipping')) ?></label>
									</th>	
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html(__('Enable Test Mode', 'wc-enviaya-shipping')) ?></span></legend>
											<label for="woocommerce_wc-enviaya-shipping_test_mode" style="<?php echo esc_attr('width:100%') ?>">
												<input class="" type="checkbox" name="woocommerce_wc-enviaya-shipping_test_mode" id="woocommerce_wc-enviaya-shipping_test_mode">
												
											</label>
										</fieldset>
									</td>			
								</tr>
							</tbody>
						</table>
						<table class="form-table" style="<?php echo esc_attr('position: absolute;bottom: 0px;width: calc(100% - 30px);') ?>">     
							<tbody>
								<tr valign="top">
									<td><button class="button button-primary wizard-setup-next" style="<?php echo esc_attr('float:right') ?>"><?php echo esc_html(__('Continue', 'wc-enviaya-shipping')) ?></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="wizard-setup-step" id="wizard-setup-step-2" style="display:none">
						<h2><?php echo esc_html(__("Billing account", 'wc-enviaya-shipping')) ?></h2>
						<p><?php echo esc_html(__("Please select which account you want to your stores shipments to be billed to. If you want to manage more than one billing account (for example for internal reporting reasons).", 'wc-enviaya-shipping'))?></p>
						<p><?php echo esc_html(__("You can create additional billing accounts here:", 'wc-enviaya-shipping'))?> <a href="<?php echo esc_url('https://app.enviaya.com.mx/accounts')?> " target="_blank" ><?php echo esc_html(__("Get billing accounts", 'wc-enviaya-shipping'))?> </a></p>
						<table class="form-table">
							<tbody>
								<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="woocommerce_wc-enviaya-shipping_account"><?php echo esc_html(__("Billing account", 'wc-enviaya-shipping')) ?></label>
								</th>
								<td class="forminp">
									<fieldset>
									<legend class="screen-reader-text">
										<span><?php echo esc_html(__("Billing account", 'wc-enviaya-shipping')) ?></span>
									</legend>
									<select  style="<?php echo esc_attr('width:100%') ?>" class="select required" name="woocommerce_wc-enviaya-shipping_account" id="woocommerce_wc-enviaya-shipping_account" ></select>
									</fieldset>
								</td>
								</tr>
							</tbody>
						</table>
						<table class="form-table" style="<?php echo esc_attr('position: absolute;bottom: 0px;width: calc(100% - 30px);') ?>">     
							<tbody>
								<tr valign="top">
									<td>
										<button class="button button-primary wizard-setup-next" style="<?php echo esc_attr('float:right') ?>"><?php echo esc_html(__('Continue', 'wc-enviaya-shipping')) ?></button>
										<button class="button wizard-setup-back" style="<?php echo esc_attr('float:right; margin-right:10px') ?>"><?php echo esc_html(__('Back', 'wc-enviaya-shipping')) ?></button>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="wizard-setup-step" id="wizard-setup-step-3" style="display:none">
						<h2><?php echo esc_html(__("Sender address", 'wc-enviaya-shipping')) ?></h2>
						<p><?php echo esc_html(__("Thanks! Now that we know your API key, we will need you to enter a sender address. You can do so here:", 'wc-enviaya-shipping')) ?> <a href="<?php echo esc_url('https://app.enviaya.com.mx/directions')?>"><?php echo esc_html(__("Enter sender address", 'wc-enviaya-shipping')) ?>.</a> <?php echo esc_html(__("Once you have entered a sender address, you can choose it from this list:", 'wc-enviaya-shipping')) ?> </p>
						<table class="form-table">
							<tbody>
								<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="woocommerce_wc-enviaya-shipping_sender_address"><?php echo esc_html(__("Sender Address", 'wc-enviaya-shipping')) ?></label>
								</th>
								<td class="forminp">
									<fieldset>
									<legend class="screen-reader-text">
										<span><?php echo esc_html(__("Sender Address", 'wc-enviaya-shipping')) ?></span>
									</legend>
									<select  style="<?php echo esc_attr('width:100%') ?>" class="select required" name="woocommerce_wc-enviaya-shipping_sender_address" id="woocommerce_wc-enviaya-shipping_sender_address" ></select>
									</fieldset>
								</td>
								</tr>
							</tbody>
						</table>
						<table class="form-table" style="<?php echo esc_attr('position: absolute;bottom: 0px;width: calc(100% - 30px);') ?>">     
							<tbody>
								<tr valign="top">
									<td>
										<button class="button button-primary wizard-setup-next" style="<?php echo esc_attr('float:right') ?>"><?php echo esc_html(__('Continue', 'wc-enviaya-shipping')) ?></button>
										<button class="button wizard-setup-back" style="<?php echo esc_attr('float:right; margin-right:10px') ?>"><?php echo esc_html(__('Back', 'wc-enviaya-shipping')) ?></button>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="wizard-setup-step" id="wizard-setup-step-4" style="display:none">
						<h2><?php echo esc_html(__("Carrier and services configuration.", 'wc-enviaya-shipping')) ?></h2>
						<p><?php echo esc_html(__("You most likely do not want to show more than 2-4 shipment options to your store's customers. In order to avoid displaying a big number of shipment services and improve rating response times, you can define shipment filters for your billing account. Typical filters are:", 'wc-enviaya-shipping')) ?></p>

							<ul class='ul-info'>
								<li><?php echo esc_html(__("Enable only certain carriers", 'wc-enviaya-shipping')) ?></li>
								<li><?php echo esc_html(__("Enable only certain services", 'wc-enviaya-shipping')) ?></li>
								<li><?php echo esc_html(__("Only show the cheapest, quickest or best cost-benefit relation service", 'wc-enviaya-shipping')) ?></li>
							</ul>
							<p><a href="<?php echo esc_url('https://app.enviaya.com.mx/carriers/edit#enviaya-account')?>" target="_blank"><?php echo esc_html(__("You can configure your shipment filters here.", 'wc-enviaya-shipping')) ?></a></p>
							<table class="form-table" style="<?php echo esc_attr('position: absolute;bottom: 0px;width: calc(100% - 30px);') ?>">     
								<tbody>
									<tr valign="top">
										<td>
											<button class="button button-primary wizard-setup-next" style="<?php echo esc_attr('float:right') ?>"><?php echo esc_html(__('Continue', 'wc-enviaya-shipping')) ?></button>
											<button class="button wizard-setup-back" style="<?php echo esc_attr('float:right; margin-right:10px') ?>"><?php echo esc_html(__('Back', 'wc-enviaya-shipping')) ?></button>
										</td>
									</tr>
								</tbody>
							</table>
					</div>
					<div class="wizard-setup-step" id="wizard-setup-step-5" style="display:none">
						<table class="form-table" style="<?php echo esc_attr('position: absolute;bottom: 0px;width: calc(100% - 30px);') ?>">
							<h2><?php echo esc_html(__("Congratulations, you are ready to ship.", 'wc-enviaya-shipping')) ?></h2>
							<p><?php echo esc_html(__("You are all set. Please feel free to check any of the other configurations you can do in our plugin, or just enjoy your new, fully automated shipping process.", 'wc-enviaya-shipping')) ?></p>  
							<tbody>
								<tr valign="top">
									<td>
										<button  class="button button-primary wizard-setup-finish" style="<?php echo esc_attr('float:right') ?>"><?php echo esc_html(__('Finish', 'wc-enviaya-shipping')) ?></button>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
			
			</div>
			<?php }
		}

		protected function init()
		{
			
			
			$this->initSettings(); 
			//$this->initFormFields(); 

			remove_all_actions('woocommerce_update_options_shipping_' . $this->id);
			add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			
			if ('yes' == $this->get_option('enable_shipping_zones', 'no')) {
				$this->supports[] = 'shipping-zones';
			}

			$this->initLogger();
			$this->initAdapter();

			//re-initialize settings
			$this->initSettings(); 

			// re-initialize fields
			$this->initFormFields();

			$this->initParcelPacker();
			//$this->initCustomsDeclaration();

		}


		protected function initCustomsDeclaration()
		{
			$this->createCustomsDeclaration();
			$this->customsDeclaration->setSettings($this->get_settings());
		}
		
		protected function initParcelPacker()
		{
			$this->createParcelPacker();
			$this->parcel_packer->setSettings($this->get_settings());
		}

		protected function createParcelPacker()
		{
			$this->parcel_packer = new BaseParcelPacker($this->id);
		}

		protected function createCustomsDeclaration()
		{
			$this->customsDeclaration = new CustomsDeclaration($this->id);
		}


		public function initSettings()
		{
			$this->settings = apply_filters($this->id . '_get_plugin_settings', array());
			if (empty($this->settings)) {
				parent::init_settings();
			}
		}
		
		protected function initLogger()
		{
			$this->logger = &\EnviaYa\WooCommerce\Logger\LoggerInstance::getInstance($this->id);
		}

		protected function initAdapter()
		{
			$this->adapter->setSettings($this->settings);
			$this->rates_finder = new EnviaYaApiRatesFinder($this->id, $this->adapter, $this->get_settings());
		}


		public function is_enabled()
		{
			return apply_filters($this->id . '_is_enabled', false);
		}

		protected function initFormFields()
		{

			if (!function_exists('is_plugin_active')) {
				require_once(ABSPATH . '/wp-admin/includes/plugin.php');
			}

			$this->instance_form_fields = array();

			$this->form_fields += $this->getAccessFormFields();
			$this->form_fields += $this->getRatingFormFields();
			$this->form_fields += $this->getAdavancedFormFields();
			$this->form_fields += $this->getSenderFormFields();
			$this->form_fields += $this->getTechnicalFormFields();

			$this->form_fields = apply_filters($this->id . '_init_form_fields', $this->form_fields);

			
		}

		protected function getLocationFormFields(){

			$formFields = array(
				'locations_title' => array(
					'title' => __('Locations', 'wc-enviaya-shipping'),
					'type' => 'title',
				),
			);

			return $formFields;
		}

		protected function getStatusFormFields(){

			$enviaya_plugin_data = get_plugin_data(plugin_dir_path(dirname(dirname(__FILE__))) . 'wc-enviaya-shipping.php');

			$formFields = array(
				'status_ey_title' => array(
					'title' => __('EnvíaYa environment', 'wc-enviaya-shipping'),
					'type' => 'title',
				),
				'status_ey_enabled' => array(
					'title' => __('EnvíaYa enabled ', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => $this->get_option('enabled',  __('Yes', 'wc-enviaya-shipping') ) ? __('Yes', 'wc-enviaya-shipping') :  __('No', 'wc-enviaya-shipping'),
					'custom_attributes' => array(
						'readonly' => true,
					),
				),
				'status_ey_version' => array(
					'title' => __('EnvíaYa version', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => $enviaya_plugin_data['Version'],
					'custom_attributes' => array(
						'readonly' => true,
					),
				),
				'status_wp_title' => array(
					'title' => __('WordPress environment', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
				),
				'status_wp_url' => array(
					'title' => __('WordPress address (URL)', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => get_bloginfo('url'),
					'custom_attributes' => array(
						'readonly' => true,
					),
				),
				'status_wp_version' => array(
					'title' => __('WordPress version', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => get_bloginfo('version'),
					'custom_attributes' => array(
						'readonly' => true,
					),
				),
				'status_wp_multi' => array(
					'title' => __('WordPress multisite', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => is_multisite() ? __('Yes', 'wc-enviaya-shipping') :  __('No', 'wc-enviaya-shipping'),
					'custom_attributes' => array(
						'readonly' => true,
					),
				),
				'status_wp_lang' => array(
					'title' => __('WordPress language', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => get_locale(),
					'custom_attributes' => array(
						'readonly' => true,
					),
				),
				'status_woo_title' => array(
					'title' => __('WooCommerce environment', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
				),	
				'status_woo_version' => array(
					'title' => __('WooCommerce version', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => WC_VERSION,
					'custom_attributes' => array(
						'readonly' => true,
					),
				),
				'status_wp_products_weight' => array(
					'title' => __('Products do not have Weight set', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'ey-subtitle',
				),
				'status_wp_products_dimensions' => array(
					'title' => __('Products do not have Length, Width or Height set', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'ey-subtitle',
				),
				'status_server_title' => array(
					'title' => __('Server environment', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
				),
				'status_server_software' => array(
					'title' => __('Server info', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => $_SERVER['SERVER_SOFTWARE'],
					'custom_attributes' => array(
						'readonly' => true,
					),
				),	
				'status_server_phpversion' => array(
					'title' => __('PHP version', 'wc-enviaya-shipping'),
					'type' => 'text',
					'default' => phpversion(),
					'custom_attributes' => array(
						'readonly' => true,
					),
				),	
				
			);
			
			return $formFields;
		}

		protected function getTechnicalFormFields(){

			$formFields = array(
				'technical_title' => array(
					'title' => __('Technical', 'wc-enviaya-shipping'),
					'type' => 'title',
				),
				'debug' => array(
					'title' => __('Debug Mode', $this->id),
					'label' => __('Log plugin activities', $this->id),
					'description' => sprintf('%s <a href="%s" target="_blank">%s</a> %s <strong>%s</strong><br/>%s <strong>%s</strong>',
										__('Log can be found in', $this->id), 
										admin_url('admin.php?page=wc-status&tab=logs'),
										__('WooCommerce -> Status -> Logs', $this->id),
										__(' with the name'),
										basename(\WC_Log_Handler_File::get_log_file_path($this->id)),
										__('It can also be found in', $this->id),
										\WC_Log_Handler_File::get_log_file_path($this->id)
								),
					'type' => 'checkbox',
				),
				'cache' => array(
					'title' => __('Use Cache', $this->id),
					'label' => __('Enable caching of API responses', $this->id),
					'type' => 'checkbox',
					'description' => __('Caching improves performance and helps to reduce API usage by preventing duplicate requests to the service. You can try to disable it for debug purposes.', $this->id),
				),
				'cache_expiration_in_secs' => array(
					'title' => __('Cache Expiration (secs)', $this->id),
					'type' => 'number',
					'description' => __('Found rates, addresses and billing accounts will cached for a given amount of seconds and help to reduce number of required API requests. For production we recommend to set this value for at least a few days.', $this->id),
				),
				'timeout' => array(
					'title' => __('Request Timeout (secs)', $this->id),
					'type' => 'number',
					'description' => __('Defines for how long plugin should wait for a response after API request has been sent.', $this->id),
				),
				'remove_settings' => array(
					'title' => __('Remove all settings', $this->id),
					'type' => 'checkbox',
					'label' => __('Remove all settings and data when uninstalling the plugin.', $this->id),
					'description' => __('This option will remove all the plugins data from your database on uninstall. Please use this option with care, as it will affect all orders which have been shipped using the plugins functionality. Some of the orders shipping data will be lost.', $this->id),
				),
			);

			return $formFields;

		}

		protected function getRatingFormFields(){

			$api_conf_url = '#';
			$conf_url = '#';

			if(!empty($this->settings['account_id'])){
				$api_conf_url = "https://app.enviaya.com.mx/accounts/edit/".$this->settings['account_id']."#api_configuration";
				$conf_url = "https://app.enviaya.com.mx/accounts/edit/".$this->settings['account_id']."#configuration";
			}

		
			$formFields = array(
				'rating_title' => array(
					'title' => __('Rating', 'wc-enviaya-shipping'),
					'type' => 'title',
				),
				'enabled' => array(
					'title' => __('Enable Rating', 'wc-enviaya-shipping'),
					'type' => 'select',
					'description' => __('Display live shipping rates on cart and checkout pages.', 'wc-enviaya-shipping'),
					'options'     => array(
						'no'  => __('No', 'wc-enviaya-shipping'),
						'yes' => __('Yes', 'wc-enviaya-shipping'),
					),
				),
				'fetch_rates_page_condition' => array(
					'title' => __('Page Condition', $this->id),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options' => array(
						'any' => __('Any', $this->id),
						'cart' => __('Cart and Checkout', $this->id),
						'checkout' => __('Checkout', $this->id)
					),
					'description' => __('Plugin will attempt to fetch live shipping rates only on pages that meet specified condition', $this->id),
				),
				'service_display_options_title' => array(
					'title' => __('Shipping Services Display', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
				),	
				'service_title_name' => array(
					'title' => __('Title', 'wc-enviaya-shipping'),
					'type' => 'text',
					'description' =>  __("This is the title shown to the store customers on top of the list of the shipping services during checkout. You can leave it blank if you don't want to show any title.", 'wc-enviaya-shipping'),
				),
				'display_carrier_logo' => array(
					'title' => __('Display Carrier Logo', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options'     => array(
						'no'  => __('No', 'wc-enviaya-shipping'),
						'yes' => __('Yes', 'wc-enviaya-shipping'),
					),
				),
				'display_carrier_name' => array(
					'title' => __('Carrier name', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options'     => array(
						'no'  => __('Don\'t show carrier name', 'wc-enviaya-shipping'),
						'yes' => __('Show carrier name', 'wc-enviaya-shipping'),
					),
					'description' =>  __('This option defines whether the carrier name is included in the shipping service names displayed on the cart and checkout page.', 'wc-enviaya-shipping'),
				),
				'display_service_name' => array(
					'title' => __('Service name', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options'     => array(
						'no_service_name' =>  __('Do not show service name', 'wc-enviaya-shipping'),
						'dynamic_service_name'  => __('Use dynamic service name', 'wc-enviaya-shipping'),
						'carrier_service_name' => __('Use carrier service name', 'wc-enviaya-shipping'),
					),
					'description' =>  '<b>'.__('Dynamic service name', 'wc-enviaya-shipping').': </b>'.__('The dynamic service name is dynamically created based on the transit time. A service which arrives today will be called \'Same Day Delivery\', a service which arrives tomorrow \'1 Day Delivery\' and so on.', 'wc-enviaya-shipping').'</br></br><b>'.__('Carrier service name', 'wc-enviaya-shipping').': </b>'.__('The carrier service name is the senderal service name used by the carrier.', 'wc-enviaya-shipping'),
				),
				'display_delivery_time' => array(
					'title' => __('Delivery time', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options'     => array(
						'no_delivery_time' =>  __('Do not show delivery time', 'wc-enviaya-shipping'),
						'delivery_date'  => __('Show estimated delivery date', 'wc-enviaya-shipping'),
						'delivery_days' => __('Show transit time (days)', 'wc-enviaya-shipping'),
					),
					'description' =>  __('This defines if and how the estimated delivery date will be shown on the cart and checkout page.', 'wc-enviaya-shipping'),
				),
				'display_preview' => array(
					'title' => __('Preview', 'wc-enviaya-shipping'),
					'type' => 'hidden',
					'description' =>  'loading...',
				),
				'free_shipping_options_title' => array(
					'title' => __('Free Shipping', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
					'description' => sprintf(__('If you want to offer free shipping to your customers, you can enable here: <a href=%s>Shops & API</a>', 'wc-enviaya-shipping'),$api_conf_url)
				),
				'contingency_shipping_tittle' => array(
					'title' => __('Contingency Shipping Services', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
					'description' => __('This option defines how the shipping services are displayed on the cart and checkout pages. Please note: If you enable the advanced design, which includes the carrier logo and some other tweaks, we need to hook into your theme. In case you notice any visual design problems, please use the default design.', 'wc-enviaya-shipping')
				),
				'enable_standard_flat_rate' => array(
					'title' => __('Enable standard shipping (Contingency only)', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options'     => array(
						'no'  => __('No'),
						'yes' => __('Yes'),
					),
				),	
				'contingency_standard_shipping_title' => array(
					'title' => __('Standard shipping', 'wc-enviaya-shipping'),
					'type' => 'Text',
					'description' =>  __('Standard shipping service name', 'wc-enviaya-shipping'),
				),
				'standard_flat_rate' => array(
					'title' => __('Standard Flat Rate', 'wc-enviaya-shipping'),
					'type' => 'Number',
				),
				'enable_express_flat_rate' => array(
					'title' => __('Enable express shipping (Contingency only)', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options'     => array(
						'no'  => __('No'),
						'yes' => __('Yes'),
					),
				),	
				'contingency_express_shipping_title' => array(
					'title' => __('Express shipping', 'wc-enviaya-shipping'),
					'type' => 'Text',
					'description' =>  __('Express shipping service name', 'wc-enviaya-shipping'),
				),
				'express_flat_rate' => array(
					'title' => __('Express Flat Rate', 'wc-enviaya-shipping'),
					'type' => 'Number',
				),
				'additional_account_configurations_title' => array(
					'title' => __('Additional account configurations', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
					'description' => sprintf(
						'<ul class="ul-info">
							<li><a href=%s target="_blank">'.__('Shipping Insurance','wc-enviaya-shipping').'</a></li>
							<li><a href=%s target="_blank">'.__('Store Pickup','wc-enviaya-shipping').'</a></li>
							<li><a href=%s target="_blank">'.__('Standard packaging dimensions','wc-enviaya-shipping').'</a></li>
							<li><a href=%s target="_blank">'.__('Shipment parcel quantities (One vs many)','wc-enviaya-shipping').'</a></li>	
						</ul>',
						$conf_url,
						$api_conf_url,
						$conf_url,
						$api_conf_url
					),

				),
				'carrier_and_services_configuration_title' => array(
					'title' => __('Carrier and services configuration.', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
					'description' => sprintf(__("You most likely do not want to show more than 2-4 shipment options to your store's customers. In order to avoid displaying a big number of shipment services and improve rating response times, you can define shipment filters for your billing account. Typical filters are: <ul class='ul-info'><li>Enable only certain carriers</li><li>Enable only certain services</li><li>Only show the cheapest, quickest or best cost-benefit relation service</li></ul><div class=url><a href=%s target=_blank>You can configure your shipment filters here.</a></div>", 'wc-enviaya-shipping'),"https://app.enviaya.com.mx/carriers/edit#enviaya-account"),
				),
				'subsidies_title' => array(
					'title' => __('Shipment subsidies', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
					'description' => sprintf(__("Shipment service prices can be subsidiezed by you if needed. This way, more economic shipping prices will be available to your customers, depending on their minimum order amount.<br>You can configure subsidies for your account <a target=_blank href=%s>here</a>.", 'wc-enviaya-shipping'),"https://enviaya.com.mx/shipping/subsidy_amounts"),
				),


			);

			return $formFields;
		}

		protected function getSenderFormFields(){

			$formFields = array(

				'sender_notice' => array(
					'type' => 'title',
					'description' => '<div class="notice notice-error inline hide-all"><p><strong>'.__('An error ocurred',$this->id).'</strong><br/><span class="notice-body"></span></p></div>'
				),
				/*
				'source_data_title' => array(
					'title' => __('Sender Address', 'wc-enviaya-shipping'),
					'type' => 'title',
				),

				'sender_source_data' => array(
					'title' => __('Source data', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options'     => array(
						'woo'  => __('Use WooCommerce store address as origin address', 'wc-enviaya-shipping'),
						'enviaya' => __('Use an EnviaYa address as origin address', 'wc-enviaya-shipping'),
					),
				),
				*/
				'sender_title' => array(
					'title' => __('Sender Address', 'wc-enviaya-shipping'),
					'type' => 'title',
					'description' =>__('What is the address of the place from where parcels are going to be shipped?', 'wc-enviaya-shipping'),
				),
				
				/*
				'origin_direction[full_name]' => array(
					'title' => __('Full Name', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[company]' => array(
					'title' => __('Company', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[direction_1]' => array(
					'title' => __('Address Line 1', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[direction_2]' => array(
					'title' => __('Address Line 2', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[postal_code]' => array(
					'title' => __('Postal code', 'wc-enviaya-shipping'),
					'type' => 'text',
				),


				'origin_direction[neighborhood]' => array(
					'title' => __('Neighborhood', 'wc-enviaya-shipping'),
					'type' => 'text', 
				),

				'origin_direction[district]' => array(
					'title' => __('District', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[city]' => array(
					'title' => __('City', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[state_code]' => array(
					'title' => __('State', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[country_code]' => array(
					'title' => __('Country', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[phone]' => array(
					'title' => __('Phone number', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'origin_direction[tax_id]' => array(
					'title' => 'RFC',
					'type' => 'text',
				),

				'origin_direction[email]' => array(
					'title' => __('Email', 'wc-enviaya-shipping'),
					'type' => 'text',
				),
				*/
				
				'sender_address' => array(
					'title' => __('Sender Address', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options' => array(),
					'description' => '<a href=https://app.enviaya.com.mx/directions/new?origin=true target=_blank>'.__('Add sender address', 'wc-enviaya-shipping').'</a>'
				),
				
				'contact_information' => array(
					'title' => __('Contact Information', 'wc-enviaya-shipping'),
					'type' => 'textarea',
					'disabled' => true,
					'rows' => 5,
				),

				'shipping_address' => array(
					'title' => __('Shipping Address', 'wc-enviaya-shipping'),
					'type' => 'textarea',
					'disabled' => true,
				),
				

				
			);

			if( (isset($this->settings['origin_direction_id']) && !empty($this->settings['origin_direction_id'])) ){
				$formFields['sender_address']['options'] = array(
					json_encode(array(
						'id' => $this->settings['origin_direction_id'], 
						'phone' => ( isset($this->settings['origin_direction_phone']) && !empty($this->settings['origin_direction_phone']) ) ? $this->settings['origin_direction_phone'] : null, 
						'full_name' => ( isset($this->settings['origin_direction_full_name']) && !empty($this->settings['origin_direction_full_name']) ) ? $this->settings['origin_direction_full_name'] : null, 
						'direction_2' => ( isset($this->settings['origin_direction_direction_2']) && !empty($this->settings['origin_direction_direction_2']) ) ? $this->settings['origin_direction_direction_2'] : null, 
						'direction_1' => ( isset($this->settings['origin_direction_direction_1']) && !empty($this->settings['origin_direction_direction_1']) ) ? $this->settings['origin_direction_direction_1'] : null, 
						'neighborhood' => ( isset($this->settings['origin_direction_neighborhood']) && !empty($this->settings['origin_direction_neighborhood']) ) ? $this->settings['origin_direction_neighborhood'] : null, 
						'district' => ( isset($this->settings['origin_direction_district']) && !empty($this->settings['origin_direction_district']) ) ? $this->settings['origin_direction_district'] : null, 
						'city' => ( isset($this->settings['origin_direction_city']) && !empty($this->settings['origin_direction_city']) ) ? $this->settings['origin_direction_city'] : null, 
						'postal_code' =>( isset($this->settings['origin_direction_postal_code']) && !empty($this->settings['origin_direction_postal_code']) ) ? $this->settings['origin_direction_postal_code'] : null, 
						'state' => ( isset($this->settings['origin_direction_state']) && !empty($this->settings['origin_direction_state']) ) ? $this->settings['origin_direction_state'] : null, 
						'state_code' => ( isset($this->settings['origin_direction_state_code']) && !empty($this->settings['origin_direction_state_code']) ) ? $this->settings['origin_direction_state_code'] : null, 
						'country_code' => ( isset($this->settings['origin_direction_country_code']) && !empty($this->settings['origin_direction_country_code']) ) ? $this->settings['origin_direction_country_code'] : null,
					)) => $this->settings['origin_direction_full_name']." ( ". $this->settings['origin_direction_postal_code']." )",
				);
			}

			/*
			
			if( (isset($this->settings['origin_adress_id']) && !empty($this->settings['origin_adress_id'])) ){
				
				$origin_adress = array_filter($this->settings, function($v, $k) {
					return substr($k, 0, strlen('origin_adress_')) === 'origin_adress_';
				}, ARRAY_FILTER_USE_BOTH);
				
				$origin_adress2 = array();
				foreach($origin_adress as $k => $v){

					$k2 = str_replace('origin_adress_','',$k);
					if($k == 'origin_adress_id'){
						$k2 = 'origin_adress_id';
					}
					
					$origin_adress2[$k2] = $v;
				}
				

				$origin_adress2['id'] = json_encode($origin_adress2);
				$formFields['sender_address']['options'] = array(
					json_encode($origin_adress2) => $origin_adress2['full_name']
				);

			}
			*/
			return $formFields;
		}
		
		protected function getAccessFormFields(){
			
			
			$errors_title=array(
				'access' => sprintf('<strong>%s</strong>', __('Access validation errors', 'wc-enviaya-shipping')),
				'advanced' => sprintf('<strong>%s</strong>', __('Advanced settings validation errors', 'wc-enviaya-shipping')),
			);

			$form_fields_errors = array();
		

			if(!empty($this->errors)){
			
				$errors_desc = '';
				foreach($this->errors as $key => $errors){
					
					$errors_desc .= sprintf('<p>%s: </p>', $errors_title[$key]);
					$errors_desc .= '<ul class="error-group-list">';

					foreach($errors as $error){
						$errors_desc.= sprintf('<li >%s</li>',$error);
					}

					$errors_desc .= '</ul>';

				}
			
				$form_fields_errors['documentation_title'] = array(
					'description' => sprintf(
						'<div class="notice notice-error inline">%s</div>',
						$errors_desc,
					),
					'type' => 'title'
				);


			}
			
			/*
			$wizard_setup_html = preg_replace( "/\r|\n/", "",
				'<div id="wizard-setup-modal" >
					<div id="wizard-setup-step-1">
						<h2>%s</h2>
						<p>%s <a href="https://app.enviaya.com.mx/api_keys" target="_blank">%s</a></p>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="woocommerce_wc-enviaya-shipping_production_api_key">%s</label>
									</th>	
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text">
												<span>%s</span>
											</legend>
											<input type="text" />
										</fieldset>
									</td>			
								</tr>
								
							</tbody>
						</table>
					</div>
				</div>'
			);
			
			$wizard_setup_html = str_replace(array("\r", "\n"), '', $wizard_setup_html);

			$wizard_setup_html = sprintf($wizard_setup_html,
				__("Hey there! Let’s get started and configure your new shipping plugin.",'wc-enviaya-shipping'),
				__("First of all, we will need your API keys to get started. You can get it here:",'wc-enviaya-shipping'),
				__("Get my API keys",'wc-enviaya-shipping'),
				__('API Key', 'wc-enviaya-shipping'),
				__('API Key', 'wc-enviaya-shipping')
			);

			
			echo wp_kses($wizard_setup_html,wp_kses_allowed_html('strip'));

			
			
			/*
			$wizard_setup_html = sprintf($wizard_setup_html,
				__("Hey there! Let’s get started and configure your new shipping plugin.",'wc-enviaya-shipping'),
				__("First of all, we will need your API keys to get started. You can get it here:",'wc-enviaya-shipping'),
				__("Get my API keys",'wc-enviaya-shipping'),
				__('API Key', 'wc-enviaya-shipping'),
				__('API Key', 'wc-enviaya-shipping')
			);
			*/

			

			$form_fields = array(

				'access_title' => array(
					'title' => __('API keys', 'wc-enviaya-shipping'),
					'type' => 'title',
				),
				'production_api_key' => array(
					'title' => __('API Key', 'wc-enviaya-shipping'),
					'type' => 'text',
					'description' => __('You can either find your API Key in your user profile or click on the link to obtain your API', 'wc-enviaya-shipping').': <a href="https://app.enviaya.com.mx/api_keys" target="_blank">'.__('Get your API Key', 'wc-enviaya-shipping').'</a>.',
				),
				'test_api_key' => array(
					'title' => __('Test API Key', 'wc-enviaya-shipping'),
					'type' => 'text',
				),
				'test_mode' => array(
					'title' => __('Enable Test Mode', 'wc-enviaya-shipping'),
					'label' => __('No valid shipments will be created or charged in test mode.', 'wc-enviaya-shipping'),
					'type' => 'checkbox',
				),	
				'account_title' => array(
					'title' => __('Billing Account', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
					'description' => sprintf(__('Please select which account you want to your stores shipments to be billed to. If you want to manage more than one billing account (for example for internal reporting reasons), you can create additional billing accounts <a href="%s" target="_blank">here</a>.', 'wc-enviaya-shipping'),'https://app.enviaya.com.mx/settings#accounts')
				),		
				'account' => array(
					'title' => __('Billing Account', 'wc-enviaya-shipping'),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'options' => array(),
					'description' => '<a id="get_billing_accounts" href="#no"><span class="dashicons-before dashicons-update" aria-hidden="true"></span> '.__('Get billing accounts', 'wc-enviaya-shipping').'</a>',
				),
				
			);

			
			if( (isset($this->settings['account']) && !empty($this->settings['account'])) ){
				$form_fields['account']['options'] = array(
					json_encode(array('id' => $this->settings['account_id'], 'account' => $this->settings['account'], 'alias' => $this->settings['account_alias'] )) => $this->settings['account_alias']." ( ". $this->settings['account']." )",
				);
			}
			
			return array_merge($form_fields_errors,$form_fields);
		}

		protected function getAdavancedFormFields(){

			$formFields = array(
				'rating_title' => array(
					'title' => __('Advanced Configuration', 'wc-enviaya-shipping'),
					'type' => 'title',
				),
				
				'automatic_booking_shipment' => array(
					'title' => __('Book shipments automatically', 'wc-enviaya-shipping'),
					'type' => 'select',
					'description' => __('When this option is enabled, shipments will be booked automatically when an order is paid. Please note: Instant , same day shipment providers are never booked automatically.', 'wc-enviaya-shipping'),
					'options'     => array(
						'no'  => __('No', 'wc-enviaya-shipping'),
						'yes' => __('Yes', 'wc-enviaya-shipping'),
					),
				),
				'use_fixed_content_description' => array(
					'title' => __('Use fixed shipment content description', 'wc-enviaya-shipping'),
					'type' => 'select',
					'description' => __('By default our plugin submits the names of the articles of the order as the shipment content description. You can override this behavior by defining a fixed content description here.', 'wc-enviaya-shipping'),
					'options'     => array(
						'no'  => __('No', 'wc-enviaya-shipping'),
						'yes' => __('Yes', 'wc-enviaya-shipping'),
					),
				),
				'fixed_content_description' => array(
					'title' => __('Fixed shipment content description', 'wc-enviaya-shipping'),
					'type' => 'text',
				),

				'enable_origins_by_product_title' => array(
					'title' => __('Origins by product', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
				),
				'enable_origins_by_product' => array(
					'title' => __('Enable origins by product', 'wc-enviaya-shipping'),
					'type' => 'select',
					'options'     => array(
						'no'  => __('No', 'wc-enviaya-shipping'),
						'yes' => __('Yes', 'wc-enviaya-shipping'),
					),
					'description' => __('Allow associating products with specific warehouse locations (origin addresses). This association can be done for specific products, by categories, tags, or attributes, resulting in more efficient and streamlined shipping operations.', 'wc-enviaya-shipping'),
					'custom_attributes' => array(),
				),
				'dokan_integration_options_title' => array(
					'title' => __('Dokan integration', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
				),
				'enable_dokan_integration' => array(
					'title' => __('Enable Dokan integration', 'wc-enviaya-shipping'),
					'type' => 'select',
					'options'     => array(
						'no'  => __('No', 'wc-enviaya-shipping'),
						'yes' => __('Yes', 'wc-enviaya-shipping'),
					),
					'description' => __('Provides integration with Dokan Multivendor Marketplace, so vendors can print shipping labels for their orders.', 'wc-enviaya-shipping'),
					'custom_attributes' => array(),
				),
				'wcvendors_integration_options_title' => array(
					'title' => __('WCVendors integration', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
				),
				'enable_wcvendors_integration' => array(
					'title' => __('Enable WCVendors integration', 'wc-enviaya-shipping'),
					'type' => 'select',
					'options'     => array(
						'no'  => __('No', 'wc-enviaya-shipping'),
						'yes' => __('Yes', 'wc-enviaya-shipping'),
					),
					'description' => __('Provides integration with WC Vendors Marketplace, so vendors can print shipping labels for their orders.', 'wc-enviaya-shipping'),
					'custom_attributes' => array(),
				),
				'shipping_zones_title' => array(
					'title' => __('Shipping Zones', 'wc-enviaya-shipping'),
					'type' => 'title',
					'class' => 'hr',
					'description' => sprintf('<span>%s</span>', __('If this option is enabled, then live shipping rates will not be displayed until you will add plugin to the relevant shipping zones. Please note that WooCommerce does not support overlapping shipping zones.', 'wc-enviaya-shipping')),
				),
				'enable_shipping_zones' => array(
					'title' => __('Shipping Zones', $this->id),
					'label' => __('Control availability of this shipping method with Shipping Zones.', 'wc-enviaya-shipping'),
					'type' => 'checkbox',
					'default' => 'no',
					
				),

			);

			// Is Dokan enabled
			$searchword = 'dokan';
			$dokan_matches = array_filter(array_keys(get_plugins()), function($var) use ($searchword) { 
				return preg_match("/\b$searchword\b/i", $var); 
			}); 
			
			$dokan_is_active = false;
			foreach($dokan_matches as $k => $v){
				if( is_plugin_active($v) ){
					$dokan_is_active = true;
					break;		
				}
			}

			//Is WCVendors enabled
			$searchword = 'wcvendors';
			$wcvendors_matches = array_filter(array_keys(get_plugins()), function($var) use ($searchword) { 
				return preg_match("/\b$searchword\b/i", $var); 
			}); 

			$wcvendors_is_active = false;
			foreach($wcvendors_matches as $k => $v){
				if( is_plugin_active($v) ){
					$wcvendors_is_active = true;
					break;		
				}
			}

			$settings = $this->settings;
			$update_settings = false;
		
			if ( !$dokan_is_active)  {		
				$formFields['enable_dokan_integration']['description'] = '<span style="color:#d63638">'.__('Dokan plugin is required', 'wc-enviaya-shipping').'</span>';
				$formFields['enable_dokan_integration']['custom_attributes']['disabled'] = true;

				if($settings['enable_dokan_integration'] == 'yes'){
					$update_settings = true;
					$settings['enable_dokan_integration'] = 'no';
				}
			}

			if ( !$wcvendors_is_active)  {		
				$formFields['enable_wcvendors_integration']['description'] = '<span style="color:#d63638">'.__('WCVendors plugin is required', 'wc-enviaya-shipping').'</span>';
				$formFields['enable_wcvendors_integration']['custom_attributes']['disabled'] = true;

				if($settings['enable_wcvendors_integration'] == 'yes'){
					$update_settings = true;
					$settings['enable_wcvendors_integration'] = 'no';
				}
			}
		
			if($update_settings){
				update_option($this->get_option_key(), $settings);
			}
			
			return $formFields;
		}

		protected function can_calculate_shipping()
		{

			$page_condition = $this->get_option('fetch_rates_page_condition', '');
			
			$can_calculate_shipping  = false;
			
			if (!empty($page_condition)) {
				
				if ($page_condition == 'cart') {
					$can_calculate_shipping = apply_filters($this->id . '_is_cart', false) || apply_filters($this->id . '_is_checkout', false);
				} else if ($page_condition == 'checkout') {
					$can_calculate_shipping = apply_filters($this->id . '_is_checkout', false);
				} else if ($page_condition == 'any') {
					$can_calculate_shipping = true;
				}
			}

			
			/*CUSTOM
			$customer_address = $this->getCustomerAddress();
			if(isset($customer_address['postal_code'])){

				$exclude_cp = "01000,01010,01020,01028,01029,01030,01030,01040,01048,01049,01050,01060,01060,01070,01080,01089,01090,01090,01090,01100,01100,01109,01110,01110,01110,01110,01116,01120,01120,01120,01120,01120,01125,01130,01130,01130,01130,01139,01140,01140,01150,01150,01159,01160,01160,01160,01160,01170,01170,01180,01180,01180,01184,01200,01209,01210,01219,01220,01220,01220,01220,01220,01220,01230,01230,01230,01230,01230,01239,01240,01250,01250,01250,01250,01250,01259,01259,01260,01260,01260,01260,01260,01269,01269,01270,01270,01270,01270,01270,01270,01275,01276,01278,01279,01280,01280,01280,01280,01280,01285,01289,01290,01290,01296,01296,01296,01298,01299,01299,01310,01320,01330,01340,01376,01376,01376,01376,01377,01379,01389,01400,01400,01407,01408,01410,01410,01419,01420,01420,01430,01440,01450,01450,01450,01450,01460,01470,01470,01480,01490,01490,01500,01500,01504,01505,01506,01507,01508,01509,01510,01510,01510,01520,01520,01520,01520,01520,01530,01537,01538,01539,01539,01540,01540,01540,01540,01540,01548,01549,01550,01550,01550,01560,01560,01560,01560,01566,01567,01569,01580,01580,01585,01586,01587,01588,01589,01590,01600,01600,01610,01610,01618,01618,01619,01619,01620,01630,01630,01630,01630,01640,01645,01650,01650,01650,01650,01650,01650,01650,01700,01700,01700,01700,01700,01700,01708,01708,01710,01710,01720,01729,01730,01730,01740,01740,01740,01750,01750,01750,01759,01760,01760,01760,01770,01770,01780,01780,01788,01789,01790,01790,01800,01807,01810,01820,01820,01830,01840,01849,01849,01856,01857,01859,01860,01870,01900,01904,01940,01990,02000,02008,02010,02010,02010,02020,02020,02040,02040,02050,02050,02050,02060,02060,02070,02070,02070,02070,02070,02080,02080,02090,02099,02100,02100,02109,02109,02110,02110,02120,02125,02127,02128,02129,02130,02140,02150,02150,02160,02169,02200,02230,02240,02240,02240,02240,02250,02260,02300,02310,02320,02330,02340,02350,02360,02400,02400,02400,02409,02410,02419,02420,02420,02430,02440,02450,02450,02459,02460,02460,02470,02479,02480,02490,02490,02500,02510,02510,02519,02520,02525,02530,02540,02600,02630,02630,02640,02650,02660,02670,02680,02700,02710,02710,02718,02719,02720,02720,02729,02730,02739,02750,02750,02750,02760,02760,02770,02780,02790,02800,02810,02810,02830,02830,02840,02840,02850,02860,02870,02900,02910,02920,02930,02940,02950,02960,02970,02980,02980,02990,03000,03010,03020,03020,03023,03023,03027,03028,03100,03100,03103,03104,03109,03199,03200,03219,03220,03229,03230,03240,03300,03303,03310,03313,03319,03320,03330,03339,03340,03400,03410,03420,03430,03440,03500,03510,03520,03530,03540,03550,03560,03570,03580,03590,03600,03610,03620,03630,03640,03650,03660,03700,03710,03720,03730,03740,03800,03809,03810,03818,03819,03820,03840,03849,03900,03910,03920,03930,03940,03949,04000,04009,04010,04020,04030,04040,04100,04110,04120,04120,04129,04200,04210,04230,04239,04240,04250,04259,04260,04260,04260,04260,04270,04280,04300,04307,04309,04310,04310,04317,04318,04319,04319,04320,04320,04320,04326,04327,04330,04330,04330,04330,04330,04330,04330,04336,04337,04338,04340,04340,04340,04350,04350,04350,04350,04350,04359,04360,04360,04368,04369,04370,04370,04370,04380,04380,04390,04390,04400,04410,04410,04420,04440,04440,04440,04450,04460,04470,04470,04480,04480,04480,04480,04489,04490,04490,04500,04510,04513,04513,04519,04530,04535,04600,04610,04620,04630,04640,04650,04650,04660,04660,04700,04700,04710,04718,04719,04719,04720,04729,04730,04730,04738,04738,04739,04800,04800,04810,04815,04830,04830,04840,04843,04849,04850,04859,04870,04870,04890,04890,04890,04899,04908,04908,04909,04909,04909,04910,04918,04919,04920,04921,04929,04930,04938,04939,04940,04950,04960,04970,04980,04980,04980,05000,05009,05010,05020,05030,05030,05039,05050,05060,05100,05110,05118,05119,05120,05126,05128,05129,05130,05200,05214,05219,05220,05230,05238,05240,05249,05260,05268,05269,05270,05280,05310,05320,05330,05330,05330,05330,05348,05349,05360,05370,05379,05400,05410,05410,05500,05520,05530,05600,05610,05619,05620,05700,05710,05730,05750,05760,05780,06000,06007,06010,06018,06020,06030,06038,06039,06040,06050,06057,06058,06060,06065,06066,06067,06068,06070,06079,06080,06090,06100,06140,06170,06171,06179,06199,06200,06200,06220,06240,06250,06270,06280,06300,06309,06350,06357,06400,06430,06450,06470,06479,06500,06589,06597,06598,06599,06600,06606,06609,06691,06692,06693,06694,06695,06696,06698,06699,06700,06704,06707,06720,06724,06725,06727,06728,06729,06740,06760,06780,06796,06797,06798,06800,06820,06840,06850,06860,06870,06880,06890,06900,06920,06995,07000,07010,07010,07010,07020,07040,07050,07058,07059,07060,07069,07070,07070,07070,07080,07089,07090,07090,07100,07100,07109,07110,07119,07119,07130,07130,07140,07140,07140,07140,07144,07144,07149,07150,07150,07160,07164,07164,07164,07164,07170,07180,07180,07180,07183,07187,07188,07189,07190,07199,07200,07207,07208,07209,07210,07214,07220,07220,07224,07230,07239,07240,07248,07248,07248,07248,07248,07249,07249,07250,07259,07268,07269,07270,07279,07280,07280,07290,07300,07300,07309,07310,07320,07320,07320,07320,07323,07326,07327,07328,07329,07330,07340,07340,07349,07350,07350,07359,07360,07360,07363,07369,07370,07380,07380,07400,07410,07410,07420,07420,07430,07440,07450,07455,07456,07456,07457,07457,07458,07459,07460,07469,07470,07480,07500,07509,07509,07510,07520,07530,07530,07540,07548,07549,07549,07550,07560,07570,07580,07600,07620,07630,07640,07650,07660,07670,07680,07700,07700,07700,07707,07708,07709,07710,07720,07730,07730,07730,07738,07739,07740,07750,07754,07755,07760,07770,07770,07780,07780,07790,07790,07800,07810,07820,07820,07830,07838,07839,07840,07840,07850,07850,07858,07859,07860,07860,07869,07870,07870,07880,07889,07890,07890,07890,07899,07900,07909,07910,07918,07919,07920,07920,07930,07939,07940,07950,07958,07959,07960,07960,07960,07969,07969,07970,07979,07979,07980,07980,07990,08000,08009,08010,08020,08029,08030,08040,08040,08100,08160,08170,08180,08188,08189,08200,08210,08220,08230,08240,08300,08310,08320,08400,08400,08400,08420,08500,08510,08520,08560,08580,08600,08610,08619,08620,08620,08620,08650,08700,08710,08720,08730,08760,08760,08760,08770,08800,08810,08820,08820,08830,08840,08900,08910,08920,08930,08930,09000,09000,09000,09000,09000,09000,09000,09009,09010,09020,09030,09040,09040,09060,09060,09070,09080,09080,09089,09090,09099,09100,09110,09120,09130,09140,09150,09150,09160,09160,09170,09180,09200,09207,09208,09208,09208,09208,09208,09208,09208,09208,09208,09208,09209,09209,09210,09210,09210,09219,09220,09226,09227,09227,09228,09229,09230,09230,09230,09230,09233,09239,09240,09249,09250,09250,09250,09260,09270,09280,09290,09290,09290,09300,09310,09310,09310,09319,09320,09330,09350,09359,09360,09360,09360,09360,09360,09366,09368,09369,09369,09369,09400,09400,09400,09410,09410,09410,09410,09420,09420,09429,09430,09430,09437,09438,09440,09440,09440,09450,09460,09470,09479,09480,09500,09500,09510,09519,09520,09520,09530,09550,09560,09570,09577,09577,09577,09578,09579,09579,09600,09608,09609,09620,09630,09630,09630,09630,09630,09630,09630,09630,09630,09630,09630,09630,09630,09630,09630,09637,09638,09640,09640,09648,09660,09670,09680,09689,09690,09696,09698,09700,09700,09700,09700,09700,09700,09700,09704,09704,09704,09704,09704,09704,09705,09705,09706,09706,09708,09708,09709,09710,09719,09720,09720,09730,09730,09740,09740,09750,09750,09750,09759,09760,09760,09767,09768,09769,09770,09779,09780,09780,09780,09780,09780,09780,09789,09790,09800,09800,09800,09800,09800,09800,09800,09800,09800,09800,09800,09809,09810,09810,09810,09810,09819,09820,09820,09820,09820,09820,09828,09829,09829,09830,09830,09830,09830,09830,09830,09836,09837,09838,09839,09839,09839,09839,09840,09849,09850,09850,09850,09850,09850,09850,09850,09850,09850,09850,09850,09850,09856,09856,09857,09857,09857,09858,09859,09860,09860,09860,09860,09860,09860,09860,09860,09860,09860,09864,09868,09868,09869,09870,09870,09870,09870,09870,09880,09880,09880,09880,09880,09880,09885,09885,09889,09890,09890,09897,09897,09898,09899,09900,09900,09900,09910,09910,09920,09930,09940,09960,09960,09960,09960,09960,09960,09960,09960,09960,09960,09960,09969,09970,09979,10000,10010,10010,10020,10100,10110,10130,10200,10210,10290,10300,10309,10320,10330,10340,10350,10360,10368,10369,10369,10370,10378,10379,10380,10400,10500,10508,10509,10580,10589,10600,10610,10620,10630,10640,10640,10640,10650,10660,10700,10710,10720,10730,10740,10800,10810,10820,10830,10840,10840,10900,10910,10920,10926,11000,11000,11000,11000,11000,11000,11000,11000,11009,11040,11100,11100,11109,11200,11200,11209,11210,11219,11220,11220,11230,11239,11240,11250,11259,11260,11260,11270,11280,11289,11290,11290,11300,11310,11311,11320,11320,11330,11340,11350,11360,11370,11379,11400,11410,11410,11420,11430,11430,11440,11440,11450,11450,11450,11460,11460,11460,11460,11470,11470,11479,11480,11480,11489,11490,11490,11500,11501,11510,11511,11520,11529,11530,11540,11550,11560,11580,11587,11590,11600,11610,11619,11619,11640,11649,11650,11700,11800,11800,11810,11820,11830,11840,11850,11850,11860,11869,11870,11910,11920,11930,11950,12000,12000,12000,12000,12000,12000,12000,12009,12070,12080,12100,12100,12100,12100,12100,12110,12110,12200,12200,12200,12200,12250,12300,12400,12400,12400,12410,12500,12600,12700,12800,12910,12920,12930,12940,12950,13000,13009,13010,13020,13030,13040,13050,13060,13060,13060,13063,13070,13070,13080,13090,13093,13094,13099,13100,13100,13110,13119,13120,13120,13120,13120,13120,13123,13129,13150,13180,13200,13209,13210,13219,13219,13220,13229,13230,13250,13270,13270,13273,13278,13278,13278,13280,13300,13300,13300,13300,13300,13300,13310,13315,13316,13316,13316,13317,13317,13318,13319,13319,13360,13360,13360,13363,13400,13410,13410,13419,13420,13429,13430,13440,13440,13440,13450,13450,13460,13508,13508,13509,13510,13520,13529,13530,13530,13540,13540,13545,13546,13549,13550,13550,13559,13600,13610,13625,13630,13640,13700,13700,14000,14000,14009,14010,14017,14020,14030,14038,14038,14039,14039,14040,14040,14049,14050,14060,14070,14070,14070,14070,14080,14080,14090,14098,14100,14100,14100,14100,14100,14108,14108,14110,14110,14120,14129,14130,14140,14141,14148,14149,14150,14160,14200,14200,14208,14209,14210,14219,14219,14220,14220,14230,14239,14240,14240,14248,14250,14250,14250,14250,14260,14260,14266,14267,14268,14269,14270,14273,14275,14276,14300,14300,14300,14307,14308,14308,14309,14310,14310,14310,14320,14324,14325,14326,14326,14327,14328,14328,14329,14330,14330,14330,14334,14335,14335,14336,14337,14338,14338,14339,14340,14340,14350,14356,14357,14357,14358,14360,14360,14360,14370,14370,14370,14370,14376,14377,14378,14380,14380,14386,14387,14388,14389,14390,14390,14390,14390,14399,14400,14406,14408,14409,14410,14420,14420,14420,14426,14426,14427,14427,14428,14429,14430,14430,14438,14439,14440,14449,14449,14449,14450,14456,14460,14470,14470,14476,14479,14480,14490,14500,14520,14529,14550,14600,14608,14609,14610,14620,14620,14629,14630,14630,14639,14640,14643,14646,14647,14647,14647,14650,14651,14653,14654,14654,14655,14657,14658,14659,14700,14710,14720,14730,14734,14735,14735,14737,14738,14739,14740,14748,14748,14749,14750,14760,14780,14790,14900,15000,15010,15020,15100,15120,15200,15210,15220,15220,15230,15240,15250,15260,15270,15280,15290,15299,15300,15309,15310,15320,15330,15339,15340,15350,15370,15380,15390,15400,15410,15420,15430,15440,15450,15460,15470,15500,15510,15520,15530,15540,15600,15610,15620,15630,15640,15640,15650,15660,15669,15670,15680,15700,15710,15720,15730,15740,15750,15800,15810,15820,15829,15830,15840,15850,15860,15870,15900,15909,15940,15950,15960,15968,15970,15980,15990,16000,16000,16000,16010,16010,16010,16010,16010,16010,16010,16019,16020,16020,16029,16030,16030,16030,16030,16030,16034,16035,16035,16036,16038,16039,16040,16040,16050,16050,16050,16057,16058,16059,16059,16060,16070,16070,16070,16070,16080,16080,16080,16080,16090,16090,16090,16090,16095,16098,16099,16100,16105,16200,16210,16220,16240,16240,16244,16247,16248,16300,16310,16320,16330,16340,16400,16410,16420,16428,16429,16430,16440,16443,16450,16457,16459,16510,16513,16513,16514,16514,16514,16515,16520,16520,16530,16530,16533,16550,16550,16600,16604,16605,16606,16607,16609,16610,16614,16615,16616,16617,16620,16629,16629,16630,16640,16710,16720,16739,16740,16749,16750,16770,16776,16776,16776,16780,16780,16780,16780,16780,16780,16797,16799,16800,16808,16809,16810,16810,16810,16810,16810,16810,16810,16813,16819,16819,16840,16840,16850,16850,16860,16866,16869,16870,16880,16880,16880,16888,16888,16888,16889,16900,16908,16909,16910";
				$exclude_cp_arr = explode(",",$exclude_cp);
				if(in_array($customer_address['postal_code'],$exclude_cp_arr)){
					$can_calculate_shipping = false;
				}
			}
			*/
			
			return $can_calculate_shipping;
		
		}

		protected function syncSettings()
		{

			$settings = $this->get_settings();

			$this->adapter->setSettings($settings);
			$this->parcel_packer->setSettings($settings);
			$this->rates_finder->setSettings($settings);
		}

		public function calculate_shipping($package = array())
		{
			
			
			if (!$this->is_enabled()) {
				return;
			}
			/*
			if (!$this->can_calculate_shipping()) {
				return;
			}
			*/
			if (empty($package) || empty($package['contents'])) {

				if (empty($package)) {
					$package = array();
				}
				
				$package['contents'] = $this->cart_proxy->get_cart();	

				if (empty($package['contents'])) {

					return;
				}
			}
			
			$this->syncSettings();
			
			$parcels = $this->parcel_packer->pack($package['contents']);
			
			$rates = $this->findShippingRatesForParcels($package, $parcels);
			
			foreach ($rates as $rate) {
				// add rate ID prefix so woocommerce blocks checkout will support this shipping method
				//$rate['id'] = $this->id . ':' . $rate['id'];
				//$this->logger->debug(__FILE__, __LINE__, "Add rate: " . print_r($rate, true));
				
				$this->add_rate($rate);
			}
			



		}

		protected function combineRates(array $rates, array $parcelRates, array $parcel)
		{
			$this->logger->debug(__FILE__, __LINE__, 'combineRates');

			foreach ($rates as $service => $rate) {
				if (empty($parcelRates[$service])) {
					$this->logger->debug(__FILE__, __LINE__, $service . ' is not present in parcel rates, so remove it');

					unset($rates[$service]);
				}
			}

			foreach ($parcelRates as $service => $parcelRate) {
				$rate = array();

				if (empty($rates[$service])) {
					$rate = $parcelRate;
					$rate['meta_data']['parcels'] = array();
				} else {
					$rate = $rates[$service];
					$rate['cost'] += $parcelRate['cost'];
				}

				if (isset($parcel['seller_id'])) {
					$rate['meta_data']['seller_id'] = $parcel['seller_id'];
				}

				if (isset($parcel['vendor_id'])) {
					$rate['meta_data']['vendor_id'] = $parcel['vendor_id'];
				}

				$rate['meta_data']['parcels'][] = $parcel;
				$rate['meta_data']['service'] = $service;

				$rates[$service] = $rate;
			}

			$this->logger->debug(__FILE__, __LINE__, 'Combined Rates: ' . print_r($rates, true));

			return $rates;
		}

		protected function prepareParcel($package, $parcel)
		{
			return $parcel;
		}

		protected function findShippingRatesForParcels(array $package, array $parcels)
		{
			
			
			$shipment_parcels = array();
			foreach ($parcels as $parcelIdx => $parcel) {
				
				$shipment_parcels[] = $this->prepareParcel($package, $parcel);
			
			}

			
			$rates = $this->findShippingRates($shipment_parcels);		
			
			

			return $rates;
		}


		protected function getSenderAddress($author_id = null){

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
						if (isset($address[$toKey])) {
							$origin_direction[$toKey].= ' '.$value;
						}else{
							$origin_direction[$toKey]=$value;
						}	
		
					}
				}

				
			}
		
			

			
			return $origin_direction;
			
		}

		protected function displayValidationErrors($validationErrors)
		{
			$this->logger->debug(__FILE__, __LINE__, "Display Validation Errors");

			if (empty($validationErrors)) {
				return;
			}

			foreach ($validationErrors as $fieldKey => $errors) {
				$fieldLabel = '';
				if ($fieldKey == 'origin') {
					$fieldLabel = __('From Address: ', 'wc-enviaya-shipping');
				} else if ($fieldKey == 'destination') {
					$fieldLabel = __('To Address: ', 'wc-enviaya-shipping');
				}

				foreach ($errors as $error) {
					wc_add_notice($fieldLabel . $error, 'error');
				}
			}
		}

		protected function findShippingRates($parcels)
		{

			$author_id = isset(current($parcels)['author_id']) && $this->settings['enable_wcvendors_integration'] == 'yes' ? current($parcels)['author_id'] : null;

			$destination_direction = $this->getCustomerAddress();

			$destination_direction_short = array(
				'country_code' => $destination_direction['country_code'],
				'postal_code' => isset($destination_direction['postal_code']) ? $destination_direction['postal_code'] : NULL
			);

			$origin_direction = $this->getSenderAddress($author_id);
			
			if(str_word_count($origin_direction['full_name']) < 2){
				$origin_direction['full_name'] = 'EY '.$origin_direction['full_name'];
			}
			
			$origin_direction_short = array(
				'country_code' => (isset($origin_direction['country_code'])) ? $origin_direction['country_code'] : "",
				'postal_code' => (isset($origin_direction['postal_code'])) ? $origin_direction['postal_code'] : "",
			);

			
			$parcels_short = array_map(function($v){
				return array(
					'length' => $v['length'],
					'width' => $v['width'],
					'height' => $v['height'],
					'dimension_unit' => $v['dimension_unit'],
					'weight' => $v['weight'],
					'weight_unit' => $v['weight_unit'],
					'quantity' => $v['quantity'],
				);
			},$parcels);
			
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
			
			if (empty($rates)) {
				

				$request_params = array_merge(
					array(
						'enviaya_account' => $this->settings['account'],
						'api_key' =>  $this->adapter->getApiKey(),
						'api_application_id' => 1,
					),
					array(
						'shipment' => array(
							'shipment_type' => 'Package',
							'parcels' => $parcels,
						),
						'origin_direction' => $origin_direction,
						'destination_direction' => $destination_direction,
					),
					array(
						'locale' => get_locale(),
						'currency' => $this->adapter->getCurrency(),
						'order_total_amount' => $this->cart_proxy->subtotal,
						'timeout' => (int)$this->settings['timeout'] - 1,
					)
				);
			
				$rates = $this->rates_finder->findShippingRates($request_params);
				$errorMessage = $this->rates_finder->getError();
				
				if (!empty($errorMessage)) {
					$this->debug($errorMessage);
				}
				
				$validationErrors = $this->rates_finder->getValidationErrors();
				$this->logger->debug(__FILE__, __LINE__, 'Validation Errors: ' . print_r($validationErrors, true));
		
				$this->session_proxy->set($this->id . '_validationErrors', $validationErrors);

				if (empty($rates)) {
					$this->logger->debug(__FILE__, __LINE__, 'no shipping rates have been found');
					return array();
				}

				$this->logger->debug(__FILE__, __LINE__, 'We will cache result for ' . $this->cacheExpirationInSecs . ' secs');
				$this->adapter->setCacheValue($cacheKey, $rates, $this->cacheExpirationInSecs);	
				
			} else {
				$this->logger->debug(__FILE__, __LINE__, 'Cached shipping rates have been found');
			}
			
			$rates = $this->sortShippingRates($rates);
			$this->logger->debug(__FILE__, __LINE__, 'Rates: ' . print_r($rates, true));

			return $rates;
		}

		protected function getCustomerAddress()
		{
			$address = array();
			$data = array();
			
			if(isset($_POST['post_data'])){
				parse_str(wp_unslash($_POST['post_data']), $data);
			}

		
			
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

				if (empty($value) && !empty($data["billing_{$field}"])) {
					$value = sanitize_text_field($data["billing_{$field}"]);
				}

				if (empty($value) && !empty($data["shipping_{$field}"])) {
					$value = sanitize_text_field($data["shipping_{$field}"]);
				}
				
				
				if (empty($value)) {
					$value = $this->customer_proxy->{"get_billing_{$field}"}();
				}

				if (empty($value)) {
					$value = $this->customer_proxy->{"get_shipping_{$field}"}();
				}
				
				
				
				
				if (!empty($value)) {
					if (isset($address[$toKey])) {
						$address[$toKey].= ' ';
					}

					if (isset($address[$toKey])) {
						$address[$toKey].=$value;
					}else{
						$address[$toKey]=$value;
					}

	
				}
			}
			
			return $address;
		}

		protected function sortShippingRates($rates)
		{
			if (!empty($rates) && is_array($rates)) {
				$this->logger->debug(__FILE__, __LINE__, 'sortShippingRatess');
				
				$rates = $this->adapter->sortRates($rates);
			}

			return $rates;
		}

		protected function validate(array $settings)
		{
			
			return true;
			$errors = array();

			$errorsAccess = $this->validateAccessRequirements($settings);
			
			if( !empty($errorsAccess) ){
				
				$errors['access'] = $errorsAccess;

			}

			$errorsAdvanced = $this->validateAdvancedRequirements($settings);

			if( !empty($errorsAdvanced) ){
				
				$errors['advanced'] = $errorsAdvanced;

			}

			if(!empty($errors)){
				
				$this->errors = $errors;
			}

			return  count($this->errors) > 0 ? false : true;
			
			
		}

		protected function validateAdvancedRequirements(array $settings)
		{
			
			$errors = array();

			if ( $settings['enable_dokan_integration'] == 'yes' &&  !is_plugin_active('dokan-lite/dokan.php')) {
				$errors[] = sprintf('<strong>%s:</strong> %s', __('Enable Dokan integration', 'wc-enviaya-shipping'), __('Dokan plugin is required.', 'wc-enviaya-shipping'));
			}

			if($settings['use_fixed_content_description'] == 'yes' && empty($settings['fixed_content_description'])){
				$errors[] = sprintf('<strong>%s:</strong> %s', __('Use fixed shipment content description', 'wc-enviaya-shipping'), __('Fixed shipment content description is required.', 'wc-enviaya-shipping'));
			}
			

			

			

			return $errors;

		}

		protected function validateAccessRequirements(array $settings)
		{

			$errors = array();

			if(empty($settings['production_api_key'])){
				$errors[] = sprintf('<strong>%s:</strong> %s', __('API keys', 'wc-enviaya-shipping'), __('API key is required.', 'wc-enviaya-shipping'));
				
			}

			if($settings['test_mode'] == 'yes' && empty($settings['test_api_key'])){
				$errors[] = sprintf('<strong>%s:</strong> %s', __('API keys', 'wc-enviaya-shipping'), __('Test API key should be filled.', 'wc-enviaya-shipping'));
			}

			if(empty($settings['account'])){
				$errors[] = sprintf('<strong>%s:</strong> %s', __('Billing Account', 'wc-enviaya-shipping'), __('Billing account is required.', 'wc-enviaya-shipping'));
			}

			return $errors;
		}

		protected function validateAdapterRequirements(array $settings)
		{
			
			$errors = $this->adapter->validate($settings);
			if (!empty($errors) && is_array($errors)) {
				foreach ($errors as $error) {
					$this->add_error($error);
				}
			}
		}

		protected function validateCacheRequirements(array $settings)
		{
			if (empty($settings['cache']) || $settings['cache'] != 'yes') {
				$error = sprintf('<strong>%s:</strong> %s', __('Use Cache', 'wc-enviaya-shipping'), __('is disabled, please use it only for debugging purposes', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}

			if (empty($settings['cacheExpirationInSecs']) || $settings['cacheExpirationInSecs'] < 60) {
				$error = sprintf('<strong>%s:</strong> %s', __('Cache Expiration (sec)', 'wc-enviaya-shipping'), __('is too short, please set it to 60 or more seconds', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}
		}

		protected function validateOriginRequirements(array $settings)
		{
			if (!$this->adapter->hasOriginFeature() || (!empty($settings['useSellerAddress']) && $settings['useSellerAddress'] == 'yes')) {
				return;
			}

			if (empty($settings['origin']['name']) && empty($settings['origin']['company'])) {
				$error = sprintf('<strong>%s:</strong> %s', __('From Address', 'wc-enviaya-shipping'), __('Name or Company should be filled', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}

			if (empty($settings['origin']['phone'])) {
				$error = sprintf('<strong>%s:</strong> %s', __('From Address', 'wc-enviaya-shipping'), __('Phone is required', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}

			if (empty($settings['origin']['country'])) {
				$error = sprintf('<strong>%s:</strong> %s', __('From Address', 'wc-enviaya-shipping'), __('Country is required', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}

			if (empty($settings['origin']['city'])) {
				$error = sprintf('<strong>%s:</strong> %s', __('From Address', 'wc-enviaya-shipping'), __('City is required', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}

			if (empty($settings['origin']['postcode'])) {
				$error = sprintf('<strong>%s:</strong> %s', __('From Address', 'wc-enviaya-shipping'), __('Zip / Postal Code is required to be able to get shipping rates', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}
		
			if (empty($settings['origin']['address'])) {
				$error = sprintf('<strong>%s:</strong> %s', __('From Address', 'wc-enviaya-shipping'), __('Address 1 is required', 'wc-enviaya-shipping'));
				$this->add_error($error);
			}
		}

		protected function validateProductShippingRequirements(array $settings)
		{
			if (empty($settings['validateProducts']) || $settings['validateProducts'] == 'no') {
				return;
			}

			$cacheKey = $this->adapter->getCacheKey(array('invalid_product_ids'));
			$invalidProductIds = $this->adapter->getCacheValue($cacheKey);
			if (!is_array($invalidProductIds)) {
				$invalidProductIds = $this->getInvalidProductIds();
				$this->adapter->setCacheValue($cacheKey, $invalidProductIds, 360);
			}

			if (is_array($invalidProductIds) && !empty($invalidProductIds[0])) {
				$error = sprintf(
					'%s: <strong>%s</strong>', 
					__('The following products do not have Weight set', 'wc-enviaya-shipping'), 
					implode(', ', $invalidProductIds[0])
				);

				$this->add_error($error);	
			}

			if (empty($this->settings['boxes']) && is_array($invalidProductIds) && !empty($invalidProductIds[1])) {
				$error = sprintf(
					'%s: <strong>%s</strong>', 
					__('The following products do not have Length, Width or Height set', 'wc-enviaya-shipping'), 
					implode(', ', $invalidProductIds[1])
				);

				$this->add_error($error);
			}

		}

		protected function validateShippingZones(array $settings)
		{
			if (empty($settings['shippingZones']) ||  $settings['shippingZones'] == 'no' || !class_exists('\WC_Shipping_Zones')) {
				return;
			}

			$isNotFound = true;
			$zones = \WC_Shipping_Zones::get_zones();
			foreach ($zones as $zone) {
				if (!empty($zone['shipping_methods']) && is_array($zone['shipping_methods'])) {
					foreach ($zone['shipping_methods'] as $instance_id => $shippingMethod) {
						if ($shippingMethod->id == $this->id) {
							$isNotFound = false;
							break;
						}
					}	
				}
			}

			if ($isNotFound) {
				$this->add_error(__('Plugin is not added to any Shipping Zone', 'wc-enviaya-shipping'));
			}
		}

		protected function hasProductWeight($product)
		{
			if (!$product->get_weight() && !get_post_meta($product->get_id(), '_weight', true)) {
				return false;
			}

			return true;
		}

		protected function hasProductDimensions($product)
		{
			if (!$product->get_length() && !get_post_meta($product->get_id(), '_length', true)) {
				return false;
			}

			if (!$product->get_width() && !get_post_meta($product->get_id(), '_width', true)) {
				return false;
			}

			if (!$product->get_height() && !get_post_meta($product->get_id(), '_height', true)) {
				return false;
			}

			return true;
		}

		protected function getInvalidProductIds()
		{
			$args = array(
				'type' => array('simple', 'variation'),
				'status' => 'publish',
				'visibility' => 'visible',
				'limit' => 10,
				'page' => 1
			);

			$noWeightProductIds = array();
			$noDimensionsProductIds = array();

			do {
				$products = wc_get_products($args);
		
				foreach ($products as $product) {
					if (is_object($product) && $product->needs_shipping()) {
						$productId = $product->get_id();
		
						if ($product->get_type() == 'variation') {
							if (wc_get_product($product->get_parent_id())) {
								$productId = $product->get_parent_id();
							} else {
								continue;
							}
						}
		
						if (!$this->hasProductWeight($product)) {
							$noWeightProductIds[$productId] = $productId;
						}
			
						if (!$this->hasProductDimensions($product)) {
							$noDimensionsProductIds[$productId] = $productId;
						}
					}
				}

				$args['page']++;

			} while(!empty($products));
			
			return array($noWeightProductIds, $noDimensionsProductIds);
		}

		protected function debug($message, $type = 'notice')
		{

			if (!empty($this->settings['debug']) && $this->settings['debug'] == 'yes' && current_user_can('administrator')) {
				wc_add_notice(sprintf('%s: %s', $this->method_title, $message), $type);
			}

		}

		public function get_option_key(){
			return 'wc_enviaya_shipping_settings';
		}

		public function get_settings()
		{
			$settings = $this->settings;

			return $settings;
		}

		public function init_instance_settings()
		{
			$this->instance_settings = get_option($this->get_instance_option_key(), null);

			// If there are no settings defined, use defaults.
			if (!is_array($this->instance_settings)) {
				$this->instance_settings = $this->getDefaultValues($this->get_instance_form_fields());
			}

			if (isset($this->settings['shippingZones']) && 'yes' == $this->settings['shippingZones']) {
				parent::init_instance_settings();
			} else {
				$this->instance_settings = array();
			}
		}

		protected function getDefaultValues($fields)
		{
			$values = array();
			if (is_array($fields)) {
				foreach ($fields as $key => $field) {
					if (isset($field['default'])) {
						$this->setKeyValue($values, $key, $field['default']);
					}
				}	
			}

			return $values;
		}

		public function process_admin_options($values = array())
		{
			$success = false;
			
			$values = empty($values) ? $this->get_post_data() : $values;
			
			if ( is_array($values) && $this->validate($values)) {

					$this->settings = array_merge($this->settings, $values);
					
					$values = apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings);
					
					if (!empty($values) && is_array($values)) {

						
						$values = array_filter($values, function($v, $k) {
							return !empty($v) && in_array($k,array(
								'production_api_key',
								'test_api_key',
								'test_mode',
								'account',
								'account_id',
								'account_alias',
								'enabled',
								'fetch_rates_page_condition',
								'service_title_name',
								'display_carrier_logo',
								'display_carrier_name',
								'display_service_name',
								'display_delivery_time',
								'enable_standard_flat_rate',
								'standard_shipping_title',
								'standard_flat_rate',
								'enable_express_flat_rate',
								'express_shipping_title',
								'express_flat_rate',
								'automatic_booking_shipment',
								'use_fixed_content_description',
								'fixed_content_description',
								'enable_origins_by_product',
								'enable_dokan_integration',
								'enable_wcvendors_integration',
								'enable_shipping_zones',
								'sender_address',
								'origin_direction_country_code',
								'origin_direction_state_code',
								'origin_direction_state',
								'origin_direction_postal_code',
								'origin_direction_city',
								'origin_direction_district',
								'origin_direction_neighborhood',
								'origin_direction_direction_1',
								'origin_direction_direction_2',
								'origin_direction_full_name',
								'origin_direction_phone',
								'origin_direction_id',
								'debug',
								'cache',
								'cache_expiration_in_secs',
								'timeout',
								'remove_settings',


								
							));
						}, ARRAY_FILTER_USE_BOTH);
						

						if ( $values['enable_dokan_integration'] == 'yes' &&  !is_plugin_active('dokan-lite/dokan.php')) {
							$values['enable_dokan_integration'] = 'no';
						}
				
						
						$enviaya_account = json_decode($values['account'],true);
						
						if($enviaya_account){
							
							if( isset($enviaya_account['id']) && !empty($enviaya_account['id']) ){
								$values['account_id'] = $enviaya_account['id'];
							}

							if( isset($enviaya_account['account']) && !empty($enviaya_account['account']) ){
								$values['account'] = $enviaya_account['account'];
							}

							if( isset($enviaya_account['alias']) && !empty($enviaya_account['alias']) ){
								$values['account_alias'] = $enviaya_account['alias'];
							}

						}

						$origin_direction = json_decode($values['sender_address'],true);
						
						
						if( isset($origin_direction['id']) && !empty($origin_direction['id']) ){
							$values['origin_direction_id'] = $origin_direction['id'];
						}

						if( isset($origin_direction['phone']) && !empty($origin_direction['phone']) ){
							$values['origin_direction_phone'] = $origin_direction['phone'];
						}

						if( isset($origin_direction['full_name']) && !empty($origin_direction['full_name']) ){
							$values['origin_direction_full_name'] = $origin_direction['full_name'];
						}

						if( isset($origin_direction['direction_2']) && !empty($origin_direction['direction_2']) ){
							$values['origin_direction_direction_2'] = $origin_direction['direction_2'];
						}

						if( isset($origin_direction['direction_1']) && !empty($origin_direction['direction_1']) ){
							$values['origin_direction_direction_1'] = $origin_direction['direction_1'];
						}

						if( isset($origin_direction['neighborhood']) && !empty($origin_direction['neighborhood']) ){
							$values['origin_direction_neighborhood'] = $origin_direction['neighborhood'];
						}

						if( isset($origin_direction['district']) && !empty($origin_direction['district']) ){
							$values['origin_direction_district'] = $origin_direction['district'];
						}

						if( isset($origin_direction['city']) && !empty($origin_direction['city']) ){
							$values['origin_direction_city'] = $origin_direction['city'];
						}

						if( isset($origin_direction['postal_code']) && !empty($origin_direction['postal_code']) ){
							$values['origin_direction_postal_code'] = $origin_direction['postal_code'];
						}

						if( isset($origin_direction['state']) && !empty($origin_direction['state']) ){
							$values['origin_direction_state'] = $origin_direction['state'];
						}

						if( isset($origin_direction['state_code']) && !empty($origin_direction['state_code']) ){
							$values['origin_direction_state_code'] = $origin_direction['state_code'];
						}
						
						if( isset($origin_direction['country_code']) && !empty($origin_direction['country_code']) ){
							$values['origin_direction_country_code'] = $origin_direction['country_code'];						
						}

						unset($values['sender_address']);
						
						$this->settings = array_merge($this->settings, $values);

						$success = update_option($this->get_option_key(), $this->settings);

					}
			}


			return $success;
		}

		public function get_post_data()
		{
			$fields = array();
			
			if (empty($this->instance_id)) {
				$this->init_settings();
				$fields = $this->get_form_fields();
			} else {
				// Check we are processing the correct form for this instance.
				if (!isset($_REQUEST['instance_id']) || absint($_REQUEST['instance_id']) !== absint($this->instance_id)) { // WPCS: input var ok, CSRF ok.
					return false;
				}
				
				$this->init_instance_settings();
				$fields = $this->get_instance_form_fields();
			}
			
			$data = parent::get_post_data();

			$values = array();
			foreach ($fields as $key => $field) {
				$isEnabled = true;
				if (isset($field['custom_attributes']['disabled']) && $field['custom_attributes']['disabled'] == 'yes') {
					$isEnabled = false;
				}

				if ('title' !== $this->get_field_type($field) && $isEnabled) {
					try {
						$value = $this->get_field_value($key, $field, $data);

						$this->setKeyValue($values, $key, $value);

					} catch (\Exception $e) {
						$this->add_error($e->getMessage());
					}
				}
			}

			return $values;
		}


		protected function setKeyValue(&$values, $key, $value)
		{
			$keyParts = explode('[', $key);
			$option = &$values;

			for ($idx = 0; $idx < count($keyParts); ++$idx) {
				$keyPart = trim($keyParts[$idx], ']');
				$option = &$option[$keyPart];
			}

			$option = $value;
		}

		protected function getKeyValue($values, $key)
		{

			if (empty($values) || empty($key)) {
				return null;
			}

			$keyParts = explode('[', $key);
			$option = &$values;

			for ($idx = 0; !is_null($option) && $idx < count($keyParts); ++$idx) {
				$keyPart = trim($keyParts[$idx], ']');

				if (isset($option[$keyPart])) {
					$option = &$option[$keyPart];
				} else {
					$option = null;
				}
			}

			return $option;
		}

		protected function getOrderStatuses()
		{
			return array();
		}

		public function get_field_value($key, $field, $postData = array())
		{
			$value = $this->getKeyValue($postData, $this->get_field_key($key));
			
			// Use filter defined for a given field
			if (!empty($field['filter'])) {
				$filter = $field['filter'];
				$filterOptions = isset($field['filter_options']) ? $field['filter_options'] : array();

				if (empty($value)) {
					if (empty($field['optional']) && $field['type'] != 'checkbox') {
						$value = false;
					}
				} else {
					$value = filter_var($value, $filter, $filterOptions);
				}

				if ($value === false && !empty($field['type']) && $field['type'] != 'checkbox') {
					throw new \Exception(sprintf('<strong>%s</strong> %s', $field['title'], __('is invalid', $this->id)));
				}

				return $value;
			}

			$type = $this->get_field_type($field);

			$fieldKeyMethodName = 'validate_' . $key . '_field';
			$fieldTypeMethodName = 'validate_' . $type . '_field';
			$fieldKeyFilterName = 'validate_' . $this->id . '_' . $key . '_field';
			$fieldTypeFilterName = 'validate_' . $this->id . '_' . $type . '_field';

			// Look for a validate_FIELDID_field method for special handling
			if (is_callable(array($this, $fieldKeyMethodName))) {
				return $this->{$fieldKeyMethodName}($key, $value);
			}

			// Look for a validate_FIELDTYPE_field method
			if (is_callable(array($this, $fieldTypeMethodName))) {
				return $this->{$fieldTypeMethodName}($key, $value);
			}

			// Look for validate_FIELDID_field filter
			if (has_filter($fieldKeyFilterName)) {
				return apply_filters($fieldKeyFilterName, $value, $key);
			}

			// Look for validate_FIELDTYPE_field filter
			if (has_filter($fieldTypeFilterName)) {
				return apply_filters($fieldTypeFilterName, $value, $key);
			}

			// Fallback to text
			return $this->validate_text_field($key, $value);
		}

		public function get_instance_option($key, $defaultValue = null)
		{
			if (empty($this->instance_settings)) {
				$this->init_instance_settings();
			}

			$value = $this->getKeyValue($this->instance_settings, $key);
			
			if (is_null($value) && is_null($defaultValue)) {
				$fields = $this->get_instance_form_fields();
				if (isset($fields[$key])) {
					$value = $this->get_field_default($fields[$key]);
				}
			}

			if (!is_null($defaultValue) && (is_null($value) || $value === '')) {
				$value = $defaultValue;
			}

			return $value;
		}

		public function get_option($key, $defaultValue = null)
		{

			if (empty($this->settings)) {
				$this->init_settings();
			}

			$value = $this->getKeyValue($this->settings, $key);
			
			if (is_null($value) && is_null($defaultValue)) {
				$fields = $this->get_form_fields();
				if (isset($fields[$key])) {
					$value = $this->get_field_default($fields[$key]);
				}
			}
			
			if (!is_null($defaultValue) && (is_null($value) || $value === '')) {
				$value = $defaultValue;
			}
			
			return $value;
		}

		public function generate_settings_html($formFields = array(), $echo = true)
		{
			if (empty($formFields)) {
				$formFields = $this->get_form_fields();
			}

			$html = '';
			foreach ($formFields as $key => $field) {
				$html .= $this->generate_field_html($key, $field);
			}

			if ($echo) {
				echo esc_html($html);
			}

			return $html;
		}

		public function generate_field_html($key, $field = array())
		{

			$field = apply_filters('generate_' . $this->id . '_field_data', $field, $key);

			$type = $this->get_field_type($field);
			$value = $this->get_option($key, null);
			if (isset($value)) {
				$field['default'] = $value;
			}

			$methodName = 'generate_' . $type . '_html';
			
			$html = '';
			if (method_exists($this, $methodName)) {
				$html = $this->{$methodName}($key, $field);
			} else {
				$html = $this->generate_text_html($key, $field);
			}

			$filterName = 'generate_' . $this->id . '_' . $type . '_html';
			if (has_filter($filterName)) {
				$html = apply_filters($filterName, $html, $key, $field);
			}

			return $html;
		}

		public function get_field_key($key)
		{
			$fieldKey = $this->plugin_id . $this->id . '_' . $key;
			$fieldKey = apply_filters('get_' . $this->id . '_field_key', $fieldKey, $key);

			return $fieldKey;
		}

	}

}