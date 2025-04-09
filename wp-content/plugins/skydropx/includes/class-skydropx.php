<?php

defined('ABSPATH') || exit;
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://skydropx.com
 * @since      1.0.0
 *
 * @package    Skydropx
 * @subpackage Skydropx/includes
 */

use Skydropx\Admin\Skydropx_Admin;
use Skydropx\Admin\Skydropx_Admin_Notices;
use Skydropx\Api\Skydropx_Api;
use Skydropx\Helper\Helper;
use Skydropx\Includes\Skydropx_i18n;
use Skydropx\Includes\Skydropx_legacy_controllers;
use Skydropx\Includes\Skydropx_Repository;
use Skydropx\Includes\Skydropx_Service;
use Skydropx\Routes\Skydropx_Routes;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Skydropx
 * @subpackage Skydropx/includes
 * @author     Skydropx <hola@skydropx.com>
 */
class Skydropx
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Skydropx_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	protected $repository;
	protected $admin_notices;
	protected $api;
	protected $service;
	protected $router;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('SKYDROPX_VERSION')) {
			$this->version = SKYDROPX_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'Skydropx';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - SkydropxV3Loader. Orchestrates the hooks of the plugin.
	 * - SkydropxV3i18n. Defines internationalization functionality.
	 * - SkydropxV3Admin. Defines all hooks for the admin area.
	 * - SkydropxV3Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-skydropx-loader.php';

		// Hook to WooCommerce's shipping method initialization
		add_action('woocommerce_shipping_init', function () {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-skydropx-shipping-method.php';
		});

		$this->loader = new Skydropx_Loader();
		$this->repository = new Skydropx_Repository();
		$this->admin_notices = new Skydropx_Admin_Notices();
		$this->api = new Skydropx_Api();
		$this->service = new Skydropx_Service($this->repository, $this->api);
		$this->router = new Skydropx_Routes($this->repository, $this->service);
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the SkydropxV3i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Skydropx_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Define the hooks for the public-facing side.
	 */
	private function define_public_hooks()
	{

		// Register public endpoints
		$this->loader->add_action('rest_api_init', $this->router, 'register_routes');

		$this->load_legacy_routes(); //TODO: Remove this when v3 fully implement new routes support
		Helper::log_info(
			// Translators: message to indicate that the public hooks/endpoints are defined
			__('Skydropx public hooks defined', 'skydropx')
		);
	}


	public static function add_shipping_method($shipping_methods)
	{
		//indicates which class need to be loaded to check for quotation
		$shipping_methods[SKYDROPX_SHIPPING_METHOD_ID] = Skydropx_Shipping_Method::class;
		return $shipping_methods;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{

		// --- Shipment Method
		$this->loader->add_filter('woocommerce_shipping_methods', $this, 'add_shipping_method');
		Helper::log_info('Skydropx filter added');

		Helper::log_info('Skydropx loader running...');
		$this->loader->run();

		Helper::log_info('Skydropx loader finished');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Skydropx_Admin(
			$this->get_plugin_name(),
			$this->get_version(),
			$this->repository,
			$this->service
		);

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu');
		$this->loader->add_action('admin_notices', $this->admin_notices, 'output_notices');
		$this->loader->add_action('admin_init', $plugin_admin, 'validate_necessary_settings');


		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}


	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Skydropx_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	public static function get_store_urls()
	{
		//Get current domain
		$urlparts = wp_parse_url(home_url());
		$domain = $urlparts['host'];
		if (isset($urlparts['port'])) {
			$domain = $domain . ':' . $urlparts['port'];
		}
		if (isset($urlparts['path'])) {
			$domain = $domain . $urlparts['path'];
		}

		if (defined('SKYDROPX_ECOMMERCE_URL')) {
			$skydropx_ecommerce_v3_url = SKYDROPX_ECOMMERCE_URL;
		}

		if (defined('SKYDROPX_APP_URL')) {
			$skydropx_app_url = SKYDROPX_APP_URL;
		}

		return array($skydropx_ecommerce_v3_url, $domain, $skydropx_app_url);
	}

	private function load_legacy_routes()
	{
		$controller = new Skydropx_legacy_controllers(
			$this->service
		);
		// --- Webhooks
		//Update if check quotation on skydropx
		$this->loader->add_action( 'woocommerce_api_skydropx-quotation-toggle',Skydropx_legacy_controllers::class, 'skydropx_toggle_quotation' );
		//Current status for check quotation on skydropx
		$this->loader->add_action( 'woocommerce_api_skydropx-quotation-status', Skydropx_legacy_controllers::class, 'skydropx_status_quotation' );
		//Set skydropx configs
		$this->loader->add_action( 'woocommerce_api_skydropx-configs', Skydropx_legacy_controllers::class, 'skydropx_set_configs' );
		// uninstall plugin from v3
		$this->loader->add_action( 'woocommerce_api_skydropx-uninstall', $controller, 'deactivate_from_v3' );
	}
}
