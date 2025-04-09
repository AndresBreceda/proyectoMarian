<?php
// Include necessary files.
require_once plugin_dir_path(__FILE__) . 'includes/skydropx-autoloader.php';

/*
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.skydropx.com/integraciones/woocommerce
 * @since             1.0.0
 * @package           Skydropx
 *
 * @wordpress-plugin
 * Plugin Name:       Skydropx
 * Requires Plugins:  woocommerce
 * Plugin URI:        https://www.skydropx.com/integraciones/woocommerce
 * Description:       Despreocúpate de toda la logística de envíos de tu negocio con una sola herramienta. Cotiza entre más de 30 paqueterías y comienza a enviar desde México y Colombia, hoy.
 * Version:           1.1.3
 * Requires at least: 5.4
 * Requires PHP:      7.0
 * Author:            Skydropx
 * Author URI:        https://skydropx.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       skydropx
 * Domain Path:       /languages
 */


use Skydropx\Api\Skydropx_Api;
use Skydropx\Includes\Skydropx_Repository;
use Skydropx\Includes\Skydropx_Service;

if (! defined('WPINC')) {
    die;
}
defined('ABSPATH') || exit;

// Define plugin constants.
define('SKYDROPX_VERSION', '1.1.3');
define('SKYDROPX_ECOMMERCE_URL', 'https://ecommerce.skydropx.com');
define('SKYDROPX_APP_URL', 'https://app.skydropx.com');
define('SKYDROPX_SHIPPING_METHOD_ID', 'skydropx');
define('SKYDROPX_SHIPPING_ZONE_NAME', 'Skydropx');
define('SKYDROPX_PLUGIN_BASE', plugin_basename(__FILE__));


/**
 * Register images during plugin activation.
 */
function skydropx_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-skydropx-activator.php';
    require_once plugin_dir_path(__FILE__) . 'includes/skydropx-image-handler.php';

    skydropx_check_components();
    skydropx_register_images();

    $activator = new Skydropx_Activator();
    $activator->activate();
}

/**
 * Deactivate the plugin.
 */
function skydropx_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-skydropx-deactivator.php';

    $service = new Skydropx_Service(
        new Skydropx_Repository(),
        new Skydropx_Api()
    );
    $deactivator = new Skydropx_Deactivator($service);
    $deactivator->deactivate();
}

/**
 * Check system requirements.
 */
function skydropx_check_components()
{
    global $wp_version;

    // Perform version checks.
    if (version_compare(PHP_VERSION, '7.0', '<')) {
        $flag = 'PHP';
        $required = '7.0';
        $current = PHP_VERSION;
    } elseif (version_compare($wp_version, '5.4', '<')) {
        $flag = 'WordPress';
        $required = '5.4';
        $current = $wp_version;
    } elseif (! defined('WC_VERSION') || version_compare(WC_VERSION, '4.3', '<')) {
        $flag = 'WooCommerce';
        $required = '4.3';
        $current = defined('WC_VERSION') ? WC_VERSION : 'N/A';
    }

    // Handle failure if requirements are not met.
    if (isset($flag)) {
        deactivate_plugins(SKYDROPX_PLUGIN_BASE);
        wp_die(
            sprintf(
                // Translators: %1$s is the plugin name, %2$s is the required version, %3$s is the current version.
                esc_html__('%1$s requires at least %2$s version %3$s. Current version: %4$s.', 'skydropx'),
                'Skydropx',
                esc_html($flag),
                esc_html($required),
                esc_html($current)
            ),
            esc_html__('Plugin Activation Error', 'skydropx'),
            ['back_link' => true]
        );
    }
}

// Register activation and deactivation hooks.
register_activation_hook(__FILE__, 'skydropx_activate');
register_deactivation_hook(__FILE__, 'skydropx_deactivate');

/**
 * Initialize the plugin.
 */
function skydropx_initializer()
{
    if (! class_exists('Skydropx')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-skydropx.php';
    }

    $plugin = new Skydropx();
    $plugin->run();
}


skydropx_initializer();
