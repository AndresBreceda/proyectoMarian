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

namespace EnviaYa\WooCommerce\Admin;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\EnviaYa')):

class EnviaYaAdmin
{
	protected static $instance = null;
	protected $id;
	protected $plugin_path;
	protected $mainMenuId;
	protected $isRegistered;
	protected $preview_label;
	protected $settings;
	protected $version;

	static public function instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new EnviaYaAdmin();
		}

		return self::$instance;
	}

	public function __construct()
	{
		$this->mainMenuId = 'enviaya';
		$this->isRegistered = false;
	

		
	}


	public function register($id, $plugin_path,$settings,$version)
	{
		
		if ($this->isRegistered) {
			return;
		}

		$this->version = $version;

		$this->settings = $settings;


		add_action('admin_enqueue_scripts', array($this, "onAdminEnqueueScripts"),10);
		
		$this->id = $id;
		$this->plugin_path = $plugin_path;		
		$this->isRegistered = true;
		
		$this->preview_label = array(
			array(
				'carrier_name' => 'FedEx',
				'carrier_logo_url' => plugins_url('/assets/images/carriers/svg/fedex.svg', dirname(dirname(str_replace('phar://', '', __FILE__)))) ,
				'carrier_service_name' => array(
					__('National Saver', 'wc-enviaya-shipping'),
					__('National Next Day', 'wc-enviaya-shipping'),
					
				),
				'dynamic_service_name' => array(
					sprintf(__('Saver shipment %s days', 'wc-enviaya-shipping'),'5'),
					__('Next day', 'wc-enviaya-shipping'),
				),
				'delivery_date' => array(
					date_i18n("d F Y", strtotime("+5 day")),
					date_i18n("d F Y", strtotime("+1 day")),
					
				),	
				'delivery_days' => array(
					'5 '.__('days', 'wc-enviaya-shipping'),
					'1 '.__('day', 'wc-enviaya-shipping'),
					
				),
			),
			array(
				'carrier_name' => 'Estafeta',
				'carrier_logo_url' => plugins_url('/assets/images/carriers/svg/estafeta.svg', dirname(dirname(str_replace('phar://', '', __FILE__)))) ,
				'carrier_service_name' => array(
					__('Terrestrial', 'wc-enviaya-shipping'),
					__('Next day', 'wc-enviaya-shipping'),
					
				),
				'dynamic_service_name' => array(
					sprintf(__('Saver shipment %s days', 'wc-enviaya-shipping'),'5'),
					__('Next day', 'wc-enviaya-shipping'),
				),
				'delivery_date' => array(
					date_i18n("d F Y", strtotime("+5 day")),
					date_i18n("d F Y", strtotime("+1 day")),
					
				),	
				'delivery_days' => array(
					'5 '.__('days', 'wc-enviaya-shipping'),
					'1 '.__('day', 'wc-enviaya-shipping'),
				),		
			),
			array(
				'carrier_name' => 'Redpack',
				'carrier_logo_url' => plugins_url('/assets/images/carriers/svg/redpack.svg', dirname(dirname(str_replace('phar://', '', __FILE__)))) ,
				'carrier_service_name' => array(
					__('Saver', 'wc-enviaya-shipping'),
					__('Express', 'wc-enviaya-shipping'),
					
				),
				'dynamic_service_name' => array(
					sprintf(__('%s Days Delivery', 'wc-enviaya-shipping'),'5'),
					__('Next day', 'wc-enviaya-shipping'),
				),
				'delivery_date' => array(
					
					date_i18n("d F Y", strtotime("+5 day")),
					date_i18n("d F Y", strtotime("+1 day")),
				),	
				'delivery_days' => array(
					'5 '.__('days', 'wc-enviaya-shipping'),
					'1 '.__('day', 'wc-enviaya-shipping'),
				),	
			),
		);

	}

	public function metabox_success_notice_callback(){

	}

	public function onAdminEnqueueScripts(){

		global $wp_query;
		
		$deps = array( 'jquery', 'selectWoo' );
		
		wp_register_script(
			'wc-enviaya-shipping-settings',
			plugins_url('assets/js/admin/settings.js',$this->plugin_path),
			$deps,
			'22052024',
			true
		);
		
		wp_localize_script('wc-enviaya-shipping-settings', 'enviaya_ajax', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'previewData' => $this->preview_label,
			'settings' => $this->settings,
		));

		wp_localize_script('wc-enviaya-shipping-settings', 'i18n', array(
			'select2_no_results' => __('No results found', 'wc-enviaya-shipping'), 
			'select2_searching' => __('Searching…', 'wc-enviaya-shipping'), 
			'search_for_direction' => __('Search a direction', 'wc-enviaya-shipping'),
			'the_origin_was_updated_successfully' => __('the_origin_was_updated_successfully', 'wc-enviaya-shipping'),
			'success' => __('success', 'wc-enviaya-shipping'),
		));

		wp_register_style(
			'wc-enviaya-shipping-settings',
			plugins_url('assets/css/admin/settings.css',$this->plugin_path),
			array(),
			'070220231803',
			'all'
		);

		if(get_current_screen()->id == 'woocommerce_page_wc-settings'){
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		}

		wp_enqueue_script( 'wc-enviaya-shipping-settings' );
		wp_enqueue_style( 'wc-enviaya-shipping-settings' );
		
	}

	

}

endif;