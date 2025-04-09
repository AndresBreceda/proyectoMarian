<?php
namespace Skydropx\Includes;

defined('ABSPATH') || exit;
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://skydropx.com
 * @since      1.0.0
 *
 * @package    Skydropx
 * @subpackage Skydropx/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Skydropx
 * @subpackage Skydropx/includes
 * @author     Skydropx <hola@skydropx.com>
 */
class Skydropx_i18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'skydropx',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
