<?php
namespace Skydropx\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Skydropx API
 *
 * @package  Skydropx\Api
 */
class Skydropx_Api extends Skydropx_api_connector implements Skydropx_api_interface {
	const APPLICATION_JSON = 'application/json';

	/**
	 * Get Base API Url
	 *
	 * @return string
	 */
	public function get_base_url() {
		if ( defined( 'SKYDROPX_ECOMMERCE_URL' ) ) {
			return SKYDROPX_ECOMMERCE_URL . '/api';
		}
	}
}