<?php

defined('ABSPATH') || exit;
// LINK: https://core.trac.wordpress.org/ticket/52506
// Review to solve issue with WordPress.DB.PreparedSQL.InterpolatedNotPrepared	Warning
/**
 * Fired during plugin deactivation
 *
 * @link       https://skydropx.com
 * @since      1.0.0
 *
 * @package    Skydropx
 * @subpackage Skydropx/includes
 */

use Skydropx\Helper\Helper;
use Skydropx\Includes\Skydropx_Service;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Skydropx
 * @subpackage Skydropx/includes
 * @author     Skydropx <hola@skydropx.com>
 */
class Skydropx_Deactivator
{
	private $skydropx_service;

	public function __construct(Skydropx_Service $skydropx_service) {
		$this->skydropx_service = $skydropx_service;
	}
	
	/**
	 * Deactivate the plugin.
	 *
	 * @since 1.0.0
	 */
	public function deactivate()
	{
		try {
			Helper::log_info(
				// Translators: Deactivating plugin...
				__('Desactivando plugin...', 'skydropx')
			);

			// $res = self::remove_from_ecommerce_service();
			$res = $this->skydropx_service->remove_from_ecommerce_service();

			if ($res && !isset($res['errors'])) {
				$this->skydropx_service->remove_skydropx_from_site();
				Helper::log_info(
					// Translators: Plugin deactivated successfully.
					__('Plugin desactivado correctamente.', 'skydropx')
				);
			} else {
				// Translators: %s is the response indicating that WooCommerce uninstallation failed.
				Helper::log_error(sprintf(__('WC uninstallation failed... %s', 'skydropx'), wp_json_encode($res, JSON_PRETTY_PRINT)));
			}
		} catch (\Throwable $th) {
			$message = esc_html($th->getMessage());
			// Translators: %s is the error message encountered during plugin deactivation.
			Helper::log_error(sprintf(__('Error deactivating plugin: %s', 'skydropx'), $message));
		}
	}

}
