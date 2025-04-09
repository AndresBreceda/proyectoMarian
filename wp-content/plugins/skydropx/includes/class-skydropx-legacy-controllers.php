<?php
namespace Skydropx\Includes;

use Skydropx\Helper\Helper;
use Skydropx\Includes\Skydropx_Service;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Skydropx_legacy_controllers
 * @deprecated - TODO: Remove this when v3 changes endpoints consumption.
 */
class Skydropx_legacy_controllers
{

	const OK    = 'HTTP/1.1 200 OK';
	const ERROR = 'HTTP/1.1 500 ERROR';
    
    protected $service_manager;

    function __construct(Skydropx_Service $service_manager)
    {
        $this->service_manager = $service_manager;
    }

    /**
     * Receives the webhook to enable/disable quotation check with Skydropx.
     *
     * @param string|null $data Webhook JSON data.
     * @param string|null $data_header Header data.
     *
     * @return void
     */
    public static function skydropx_toggle_quotation(string $data = null, string $data_header = null)
    {

        // Fetch JSON data from input stream if $data is null or empty
        $json = (is_null($data) || empty($data)) ? file_get_contents('php://input') : $data;

        // Unslash data to prevent SQL injection
        $json = wp_unslash($json);

        // Decode JSON data
        $data_array = json_decode($json, true);

        // Extract status and country from JSON data
        $status = isset($data_array['status']) ? filter_var($data_array['status'], FILTER_VALIDATE_BOOLEAN) : false;
        $country = isset($data_array['country']) ? sanitize_text_field($data_array['country']) : 'MX';

        header('Content-Type: application/json');

        // Check if required data is missing
        if (is_null($status)) {
            header(self::ERROR);
            $body["success"] = false;
            $body["quotation_status"] = $status;
            echo wp_json_encode($body, JSON_PRETTY_PRINT);
            die();
        }

        // Set transients for status and country
        update_option('SKYDROPX_ENABLE_QUOTATION', $status);
        update_option('SKYDROPX_COUNTRY_QUOTATION', $country);

        // Create shipping zone
        $shipping_zone = new Skydropx_Shipping_Zone();
        $shipping_zone->create();

        // Respond with success
        header(self::OK);
        $body["success"] = true;
        $body["quotation_status"] = $status;
        echo wp_json_encode($body, JSON_PRETTY_PRINT);

        Helper::log_info('Skydropx quotation status updated with success. Current status: ' . $status . ' and country: ' . $country);
        die();
    }

    /**
     * Receives the webhook to set Skydropx configurations.
     *
     * @param string|null $data Webhook JSON data.
     * @param string|null $data_header Header data.
     *
     * @return void
     */
    public static function skydropx_set_configs(string $data = null, string $data_header = null)
    {
        if (is_null($data) || empty($data)) {
            $json = file_get_contents('php://input');
        } else {
            $json = $data;
        }

        $json = wp_unslash($json); // Unslash data to prevent SQL injection.

        header('Content-Type: application/json');
        $data = json_decode($json, true);

        $shop_id = isset($data['shop_id']) ? sanitize_text_field($data['shop_id']) : '';
        $token = isset($data['token']) ? sanitize_text_field($data['token']) : '';

        if (empty($data) || is_null($data)) {
            header(self::ERROR);
            $body["success"] = false;
            echo wp_json_encode($body);
            die();
        }

        update_option('SKYDROPX_SHOP_ID', $shop_id);
        update_option('SKYDROPX_TOKEN', $token);


        header(self::OK);
        $body["version"] = defined('SKYDROPX_VERSION') ? SKYDROPX_VERSION : '';
        $body["success"] = true;
        echo wp_json_encode($body, JSON_PRETTY_PRINT);

        Helper::log_info('Skydropx configurations updated with success. Current Shop ID: ' . $shop_id);
        die();
    }

    /**
     * Retrieves the status of Skydropx quotations.
     *
     * @return void
     */
    public static function skydropx_status_quotation()
    {

        header('Content-Type: application/json');
        header(self::OK);

        $shop_id = get_option('SKYDROPX_SHOP_ID');
        $country = get_option('SKYDROPX_COUNTRY_QUOTATION');
        $has_quotation_enabled = get_option('SKYDROPX_ENABLE_QUOTATION', null);
        $plugin_version = defined('SKYDROPX_VERSION') ? SKYDROPX_VERSION : '';

        $status = is_null($has_quotation_enabled) ? null : boolval($has_quotation_enabled);

        $body = array(
            'shop_id' => $shop_id,
            'country' => $country,
            'status' => $status,
            'plugin_version' => $plugin_version,
        );

        echo wp_json_encode($body, JSON_PRETTY_PRINT);
        die();
    }

    /**
	 * Uninstall plugin by endpoint
	 *
	 * Uninstall the plugin when v3 requests an endpoint
	 *
	 * @since    1.0.0
	 */
	public function deactivate_from_v3() {

		header('Content-Type: application/json');
		try {
			
            $this->service_manager->deactivate_from_v3();

			// Return success response
			header(self::OK);
			$body["success"] = true;
			echo wp_json_encode($body);
			die();
		} catch (\Throwable $th) {
			// Return error response
			header(self::ERROR);
			$body["success"] = false;
			echo wp_json_encode($body);

			// print error message
			$message = $th->getMessage();
			Helper::log_error("Error deactivating plugin: $message");
			die();
		}
	}
}
