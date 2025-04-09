<?php
namespace Skydropx\Routes;

use Skydropx\Helper\Helper;
use Skydropx\Includes\Skydropx_Repository;
use Skydropx\Includes\Skydropx_Service;
use Skydropx\Includes\Skydropx_Shipping_Zone;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
class Skydropx_Routes {

    /**
     * @since 1.0.0
     * @var array $routes The list of REST API routes.
     * @access protected
     */
    protected $routes;

    protected $repository;
    protected $service_manager;

    public function __construct(
        Skydropx_Repository $repository,
        Skydropx_Service $service_manager
    ) {
        // Define the routes
        $this->repository = $repository;
        $this->service_manager = $service_manager;
        $this->routes = $this->build_routes_definition();
    }

    private function build_routes_definition() {
        return [
            [
                'method' => 'POST',
                'callback' => [$this, 'toggle_quotation'],
                'path' => 'quotation-toggle',
            ],
            [
                'method' => 'GET',
                'callback' => [$this, 'status_quotation'],
                'path' => 'quotation-status',
            ],
            [
                'method' => 'POST',
                'callback' => [$this, 'set_configs'],
                'path' => 'configs',
            ],
            [
                'method' => 'POST',
                'callback' => [$this, 'skydropx_uninstall_plugin'],
                'path' => 'uninstall',
            ]
        ];
    }

    /**
     * Register the REST API routes during the rest_api_init hook.
     */
    public function register_routes() {
        foreach ($this->routes as $route) {
            register_rest_route(
                'skydropx/v1',
                '/' . $route['path'],
                [
                    'methods' => $route['method'],
                    'callback' => $route['callback'],
                    'permission_callback' => [$this, 'skydropx_verify_api_keys']
                ]
            );
        }
    }

    /**
     * Callback for the /quotation-toggle endpoint.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function toggle_quotation(\WP_REST_Request $request) {
        try {
            $data = $request->get_json_params();
            $validation_rules = [
                'status' => 'boolean',
                'country' => 'string'
            ];

            $validated_data = $this->sanitize_and_validate_json_input($data, $validation_rules);
            if (isset($validated_data['error'])) {
                return new \WP_REST_Response([
                    'success' => false,
                    'status' => 'error',
                    'message' => $validated_data['error']
                ], 400); // Bad request
            }

            $status = filter_var($validated_data['status'], FILTER_VALIDATE_BOOLEAN);
            $country = sanitize_text_field($validated_data['country']);

            update_option('SKYDROPX_ENABLE_QUOTATION', $status);
            update_option('SKYDROPX_COUNTRY_QUOTATION', $country);

            $shipping_zone = new Skydropx_Shipping_Zone();
            $shipping_zone->create();

            return new \WP_REST_Response([
                'success' => true,
                'quotation_status' => $status
            ], 200);
        } catch (\Throwable $th) {
            Helper::log_error($th->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }


    /**
     * Callback for the /configs endpoint.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function set_configs(\WP_REST_Request $request) {
        try {
            $data = $request->get_json_params();
            $validation_rules = [
                'shop_id' => 'string',
                'token' => 'string'
            ];
    
            $validated_data = $this->sanitize_and_validate_json_input($data, $validation_rules);
            if (isset($validated_data['error'])) {
                return new \WP_REST_Response([
                    'success' => false,
                    'status' => 'error',
                    'message' => $validated_data['error']
                ], 400);
            }
    
            $shop_id = sanitize_text_field($validated_data['shop_id']);
            $token = sanitize_text_field($validated_data['token']);
    
            update_option('SKYDROPX_SHOP_ID', $shop_id);
            update_option('SKYDROPX_TOKEN', $token);
    
            return new \WP_REST_Response([
                'success' => true,
                'version' => defined('SKYDROPX_VERSION') ? SKYDROPX_VERSION : ''
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            $message = esc_html($th->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'status' => 'error',
                'message' => $message
            ], 500);
        }
    }

    /**
     * Callback for the /quotation-status endpoint.
     *
     * @return \WP_REST_Response
     */
    public function status_quotation() {
        $shop_id = get_option('SKYDROPX_SHOP_ID');
        $country = get_option('SKYDROPX_COUNTRY_QUOTATION');
        $has_quotation_enabled = get_option('SKYDROPX_ENABLE_QUOTATION');
        $plugin_version = defined('SKYDROPX_VERSION') ? SKYDROPX_VERSION : '';

        return new \WP_REST_Response([
            'shop_id' => $shop_id,
            'country' => $country,
            'status' => boolval($has_quotation_enabled),
            'plugin_version' => $plugin_version
        ], 200);
    }

    /**
     * Helper function to sanitize and validate JSON input data.
     *
     * @param array $input
     * @param array $validation_rules
     * @return array
     */
    private function sanitize_and_validate_json_input($input, $validation_rules) {
        $errors = [];
        $data = [];

        foreach ($validation_rules as $field => $type) {
            if (!isset($input[$field])) {
                $errors[] = "'$field' is required.";
            } else {
                switch ($type) {
                    case 'boolean':
                        $data[$field] = filter_var($input[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if (is_null($data[$field])) {
                            $errors[] = "'$field' must be a boolean.";
                        }
                        break;
                    case 'string':
                        $data[$field] = sanitize_text_field($input[$field]);
                        break;
                }
            }
        }

        if (!empty($errors)) {
            return ['error' => $errors];
        }

        return $data;
    }

    public function skydropx_uninstall_plugin(\WP_REST_Request $request) {
        try {
            // Call the deactivation function
            $this->service_manager->deactivate_from_v3();
    
            // Return a successful response
            $response = [
                'success' => true,
                // Translators: Message displayed when the plugin is deactivated successfully.
                'message' => __('Plugin desactivado correctamente.', 'skydropx')
            ];
    
            return new \WP_REST_Response($response, 200);
        } catch (\Throwable $th) {
            // Capture and log the error
            $message = esc_html($th->getMessage());

            Helper::log_error(sprintf(
                // Translators: %s is the error message that occurred while deactivating the plugin.
                __('Error deactivating plugin: %s', 'skydropx'), 
                $message
            ));
    
            // Return an error response
            $response = [
                'success' => false,
                'message' => __('An error occurred while deactivating the plugin', 'skydropx')
            ];
    
            return new \WP_REST_Response($response, 500);
        }
    }

    function skydropx_verify_api_keys(\WP_REST_Request $request) {
        // Retrieve the Authorization header
        $auth_header = $request->get_header('Authorization');
        if (!$auth_header || stripos($auth_header, 'Basic ') !== 0) {
            return Helper::log_error(
                // Translators: Message displayed when the Authorization header is missing or invalid.
                __('Authorization header is missing or invalid.', 'skydropx')
            );
        }
    
        // Decode the Base64-encoded credentials
        $encoded_credentials = substr($auth_header, 6); // Remove 'Basic ' prefix
        $decoded_credentials = base64_decode($encoded_credentials);
    
        if (!$decoded_credentials || strpos($decoded_credentials, ':') === false) {
            $response = [
                'success' => false,
                'message' => __('Invalid Authorization header.', 'skydropx')
            ];
            return new \WP_REST_Response(
                $response,
                403
            );
        }
    
        // Extract consumer key and secret
        list($consumer_key, $consumer_secret) = explode(':', $decoded_credentials, 2);
    
        // Cache query result to avoid repetitive database hits
        $cache_key = 'skydropx_api_key_' . md5($consumer_key);
        $cached_result = wp_cache_get($cache_key, 'skydropx');
    
        if (!$cached_result) {
            $result = $this->repository->validate_api_key($consumer_key);

            // Cache the result for subsequent requests
            if ($result) {
                wp_cache_set($cache_key, $result, 'skydropx', 300); // Cache for 5 minutes
            } else {
                $result = null;
            }
        } else {
            $result = $cached_result;
        }
    
        // Validate API credentials
        if (!$result || $result->consumer_secret !== $consumer_secret) {
            $response = [
                'success' => false,
                'message' => __('Invalid API credentials.', 'skydropx')
            ];
            return new \WP_REST_Response(
                $response,
                403
            );
        }
    
        // All checks passed
        return true;
    }
}