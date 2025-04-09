<?php

defined('ABSPATH') || exit;

/**
 * Fired during plugin activation
 *
 * @link       https://skydropx.com
 * @since      1.0.0
 *
 * @package    Skydropx
 * @subpackage Skydropx/includes
 */

use Skydropx\Admin\Skydropx_Admin_Notices;
use Skydropx\Helper\Helper;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Skydropx
 * @subpackage Skydropx/includes
 * @author     Skydropx <hola@skydropx.com>
 */

class Skydropx_Activator
{
	public function activate()
	{
		try {
			Helper::log_info('Starting plugin activation ' . SKYDROPX_PLUGIN_BASE);

			if (!get_option('permalink_structure')) {
				Helper::log_error(
					// translators: %s refers to the permalink redirect URL.
					__('Plugin activation incomplete. Permalink structure needs to be updated.', 'skydropx')
				);

				$this->set_permalink();
				return false;
			}
	
			$this->notify_user_activation_success();
			return true;
		} catch (\Throwable $th) {
			$message = esc_html($th->getMessage());

			// translators: %s refers to the error message during plugin activation.
			Helper::log_error(sprintf(__('Error activating plugin: %s', 'skydropx'), $message));

			return false;
		}
	}

	/**
	 * Set the permalink structure to /%postname%/ if it is not configured.
	 * This is necessary for the plugin to work correctly. As it allows us to consume the API.
	 */
	private function set_permalink()
	{

		if (!get_option('permalink_structure')) {
			// Note:  Appears that to use wc-api we need to visit the permalink 
			// settings page in order to enable api calls
			// However, this is not the case for the new v3 endpoints

			global $wp_rewrite; 
			//Write the rule
			$wp_rewrite->set_permalink_structure('/%postname%/'); 

			//Set the option
			update_option( "rewrite_rules", FALSE ); 
			update_option( "skydropx_visited_permalink_view", false);

			//Flush the rules and tell it to write htaccess
			$wp_rewrite->flush_rules( true );
			return admin_url('options-permalink.php');
		}
		return null;
	}

    private function notify_user_activation_success() {
		$setup_url = admin_url('admin.php?page=skydropx');
       
		Skydropx_Admin_Notices::add_warning(
			'missing_shop_id',
			sprintf(
				'<strong>%s</strong> %s <a href="%s">%s</a>',
				// translators: Plugin name.
				__('Skydropx Plugin:', 'skydropx'),
				// translators: Success message after activating the plugin.
				__('Activado con éxito. Por favor, termina de vincular tu tienda con Skydropx en la', 'skydropx'),
				esc_url($setup_url),
				// translators: Link text.
                __('página de configuración.', 'skydropx')
			)
		);

		Helper::log_info(
			// translators: message after activating the plugin.
			__('Plugin activated successfully.', 'skydropx')
		);
    }

}