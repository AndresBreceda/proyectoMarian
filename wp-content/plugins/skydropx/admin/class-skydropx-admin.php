<?php

namespace Skydropx\Admin;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Skydropx\Admin\Skydropx_Admin_Notices;
use Skydropx\Helper\Helper;
use Skydropx\Includes\Skydropx_Repository;
use Skydropx\Includes\Skydropx_Service;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://skydropx.com
 * @since      1.0.0
 *
 * @package    Skydropx
 * @subpackage Skydropx/admin
 */
class Skydropx_Admin
{

    private $plugin_name;
    private $version;
    private $repository;
    private $service_manager;

    public function __construct(
        $plugin_name,
        $version,
        Skydropx_Repository $repository,
        Skydropx_Service $service_manager
    ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->repository = $repository;
        $this->service_manager = $service_manager;
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/skydropx-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts()
    {
        // wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/skydropx-admin.js', array( 'jquery' ), $this->version, false );
    }

    public function admin_menu()
    {
        Helper::log_info('Executing admin_menu...');
        $page_title = __('Skydropx', 'skydropx');
        $menu_title = __('Skydropx', 'skydropx');
        $capability = 'activate_plugins';
        $menu_slug  = 'skydropx';
        $function   = [$this, 'skydropx_admin_menu_content'];

        try {
            add_menu_page(
                $page_title,
                $menu_title,
                $capability,
                $menu_slug,
                $function,
                'dashicons-admin-generic'
            );
        } catch (\Exception $e) {
            // Helper::log_error('Error registering admin menu: ' . $e->getMessage());
            Helper::log_error(sprintf(
                // translators: %s Error message when registering the admin menu.
                __('Error registering admin menu: %s', 'skydropx'),
                $e->getMessage()
            ));
        }
    }

    private function validate_permalink_setting_change(
        $permalink_structure,
        $skydropx_visited_permalink_view
    ) {
        global $pagenow;
        if ($pagenow === 'options-permalink.php') {
            update_option('skydropx_visited_permalink_view', true);
        }

        if (!$permalink_structure && $skydropx_visited_permalink_view) {
            update_option('skydropx_visited_permalink_view', false);
        }

        if (!$skydropx_visited_permalink_view || !$permalink_structure) {
            $permalink_page = admin_url('options-permalink.php');
            $message = sprintf(
                '<strong>%s</strong> %s <a href="%s">%s</a> %s',
                __('Skydropx Plugin:', 'skydropx'),
                __('Para que el plugin Skydropx funcione correctamente, es necesario configurar los enlaces permanentes.', 'skydropx'),
                esc_url($permalink_page),
                __('Configuración de enlaces permanentes', 'skydropx'),
                __('Recomendamos seleccionar la opción "Nombre de la entrada" (%postname%) en la configuración.', 'skydropx')
            );
            Skydropx_Admin_Notices::add_error('permalink_structure', $message);
        }
    }

    private function validate_shop_id_setting_change()
    {
        if (
            !get_option('SKYDROPX_SHOP_ID')
            && get_option('permalink_structure')
            && get_option('skydropx_visited_permalink_view')
        ) {
            // If permalinks are active but shop_id is missing, show a warning
            $link_url = esc_url(admin_url('admin.php?page=skydropx'));
            $formatted_message = sprintf(
                '<strong>%s</strong> %s <a href="%s">%s</a>',
                __('Skydropx Plugin:', 'skydropx'),
                __('Por favor, vincula tu tienda con Skydropx en la', 'skydropx'),
                $link_url,
                __('página de configuración.', 'skydropx')
            );
            Skydropx_Admin_Notices::add_warning('missing_shop_id', $formatted_message);
        }
    }

    public function validate_necessary_settings()
    {
        $permalink_structure = get_option('permalink_structure');
        $skydropx_visited_permalink_view = get_option('skydropx_visited_permalink_view', false);

        $this->validate_permalink_setting_change(
            $permalink_structure,
            $skydropx_visited_permalink_view
        );
        $this->validate_shop_id_setting_change();
    }

    /**
     * Render a partial template.
     *
     * @param string $partial_name Name of the partial file (without extension).
     * @param array  $data Associative array of data to pass to the partial.
     */
    public static function render_partial($partial_name, $data = [])
    {
        $partial_path = plugin_dir_path(__FILE__) . '/partials/' . $partial_name . '.php';

        if (file_exists($partial_path)) {
            // Extract data to variables for use in the partial.
            extract($data, EXTR_SKIP);
            include $partial_path;
        } else {
            // Optionally log an error if the partial is not found.
            Helper::log_error(
                sprintf(
                    // translators: Error message when a partial template is not found.
                    __('Partial template not found: %s', 'skydropx'),
                    $partial_name
                )
            );
        }
    }

    private function render_call_to_actions_content(
        $has_permalinks_active,
        $api_keys,
        $has_shop_id_set
    ) {

        $call_to_action_link = admin_url('options-permalink.php');
        $button_text = __('Configurar enlaces permanentes', 'skydropx');


        if (
            $has_permalinks_active && !$has_shop_id_set
            && get_option('skydropx_visited_permalink_view')
        ) {
            // send uninstall request to v3 to try to clean everything in our side, 
            // we only need the domain
            $this->service_manager->remove_from_ecommerce_service();
            // NOTE: here we assume that if the shop_id is not set, 
            // the sync process was not completed
            // so we need to reset the api keys and try again
            [$consumer_key, $consumer_secret] = $this->repository->reset_api_keys();
            $call_to_action_link = $this->service_manager->generate_shop_creation_url(
                $consumer_key,
                $consumer_secret
            );
            $button_text = __('Vincular mi tienda', 'skydropx');
        } elseif ($has_permalinks_active && $has_shop_id_set) {
            $call_to_action_link = SKYDROPX_APP_URL . '/your_connections';
            $button_text = __('Reestablecer permisos en plataforma', 'skydropx');
        }

        $template_data = [
            'button_link' => $call_to_action_link,
            'button_text' => $button_text,
            // if not the first time, show a message to deactivate and 
            // activate the plugin as a workaround
            'is_first_time' => $has_permalinks_active
                && empty($api_keys) && !$has_shop_id_set
            // deactivate and activate the plugin helps to unset configurations on our side
        ];

        return $this->render_partial(
            'complete-installation-view',
            $template_data
        );
    }

    public function skydropx_admin_menu_content()
    {
        $user_id = get_current_user_id();
        $api_keys = $this->repository->fetch_api_keys_by_user($user_id);
        $has_shop_id_set = boolval(get_option('SKYDROPX_SHOP_ID', false));
        $has_permalinks_active = get_option('permalink_structure', false);

        if (empty($api_keys) && $has_shop_id_set) {
            Helper::log_error('No API keys found for user ID: ' . $user_id);
            Skydropx_Admin_Notices::add_warning(
                'missing_api_keys',
                sprintf(
                    '<strong>%s</strong> %s',
                    // translators: Plugin name.
                    __('Skydropx Plugin:', 'skydropx'),
                    // translators: Error message when API keys are not found.
                    __('No se encontraron las claves de API necesarias. Por favor, siga el flujo de reestablecer permisos desde la plataforma de Skydropx. Si el problema persiste, contacte soporte.', 'skydropx')
                )
            );

            //echo notice with warning
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>Por favor, siga el flujo de reestablecer permisos desde la plataforma de Skydropx. Si el problema persiste, contacte soporte.</p>';
            echo '</div>';
        }

        // check for uuiid or not empty value
        if (!$has_permalinks_active || !$has_shop_id_set) {
            return $this->render_call_to_actions_content(
                $has_permalinks_active,
                $api_keys,
                $has_shop_id_set
            );
        }

        $has_errors = Boolval(empty($api_keys['consumer_key'])
            || empty($api_keys['consumer_secret'] || !$has_shop_id_set));
        return $this->render_iframe_content(!$has_errors);
    }

    private function render_iframe_content($is_success = true)
    {
        // Ensure the SKYDROPX_ECOMMERCE_URL constant is defined
        if (!defined('SKYDROPX_ECOMMERCE_URL')) {
            Helper::log_error(
                // translators: Error message when the SKYDROPX_ECOMMERCE_URL constant is not defined.
                __('The SKYDROPX_ECOMMERCE_URL constant is not defined.', 'skydropx')
            );
            return;
        }

        // Determine the iframe path type
        $type_path = $is_success ? 'success' : 'error';

        // Sanitize and encode parameters
        $store_domain =  wp_parse_url(home_url('/'), PHP_URL_HOST);
        $is_complete = $is_success ? 'true' : 'false'; // Convert boolean to 'true' or 'false'
        $iframe_src = esc_url(SKYDROPX_ECOMMERCE_URL
            . '/' . $type_path . '?domain='
            . $store_domain . '&isComplete=' . $is_complete);

        // Generate iframe HTML
        $iframe_html = sprintf(
            '<iframe src="%s" style="width: 100%%; height: 100vh; border: none;"></iframe>',
            $iframe_src
        );

        // Define the allowed tags and attributes
        $allowed_tags = array(
            'iframe' => array(
                'src'    => array(),
                'style'  => array(),
                'width'  => array(),
                'height' => array(),
                'border' => array(),
            ),
        );

        //log is_complete, and iframe_src
        Helper::log_info(sprintf('is_complete: %s, iframe_src: %s', $is_complete, $iframe_src));

        // Safely output the iframe
        echo wp_kses($iframe_html, $allowed_tags);
    }
}
