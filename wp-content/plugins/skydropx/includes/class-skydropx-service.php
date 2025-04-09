<?php

namespace Skydropx\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Skydropx\Api\Skydropx_Api;
use Skydropx\Helper\Helper;


class Skydropx_Service
{
	private $repository;
	private $api;

	public function __construct(Skydropx_Repository $repository, Skydropx_Api $api)
	{
		$this->repository = $repository;
		$this->api = $api;
	}

	public function generate_shop_creation_url($consumer_key, $consumer_secret)
	{
		//get urls
		$skydropx_app_url = SKYDROPX_APP_URL;
		$store_domain = wp_parse_url(home_url('/'), PHP_URL_HOST);

		return add_query_arg(
			array(
				'domain' => $store_domain,
				'api_secret_key' => $consumer_secret,
				'api_key' => $consumer_key,
			),
			$skydropx_app_url . '/store_connections/v3/woocommerce/install'
		);
	}


	public function remove_from_ecommerce_service()
	{
		$shop_id = get_option('SKYDROPX_SHOP_ID');
		$urlparts = wp_parse_url(home_url());
		$endpoint = '/v3/shop/webhook/mx/woocommerce/uninstall_app';
		$domain = $urlparts['host'];
		if (isset($urlparts['port'])) {
			$domain .= ':' . $urlparts['port'];
		}
		if (isset($urlparts['path'])) {
			$domain .= rtrim($urlparts['path'], '/');
		}

		// Send uninstall request
		$res = $this->api->post(
			esc_url($endpoint),
			['domain' => $domain, 'shop_id' => $shop_id],
			['Content-Type' => 'application/json']
		);

		// Translators: %s is the response from the API call to uninstall WooCommerce.
		Helper::log_info(sprintf(__('Request to V3: %s', 'skydropx'), wp_json_encode($res, JSON_PRETTY_PRINT)));

		return $res;
	}

	public function remove_skydropx_from_site(){
		$this->repository->delete_api_keys();

		//delete global variables
		delete_option('SKYDROPX_SHOP_ID');
		delete_option('SKYDROPX_ENABLE_QUOTATION');

		$this->repository->skydropx_clean_webhooks();
		Skydropx_Shipping_Zone::remove();

		Helper::log_info('Skydropx removed from site');
	}

	/**
	 * Uninstall plugin 
	 *
	 * @since 1.0.0
	 */
	public function deactivate_from_v3()
	{
		$this->remove_skydropx_from_site(); //remove skydropx from site without calling external services

		deactivate_plugins( SKYDROPX_PLUGIN_BASE, true ); //Remove skydropx plugin without calling deactivation hooks

		Helper::log_info(__('Plugin uninstalled!', 'skydropx'));
	}
}
