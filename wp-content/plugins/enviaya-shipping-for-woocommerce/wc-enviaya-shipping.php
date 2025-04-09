<?php
/**
 * Plugin Name: EnvíaYa: Ship with all carriers (Rating, Booking and Tracking)
 * Plugin URI: https://enviaya.com.mx/es/ecommerce/woocommerce/
 * Description: An powerful plugin to rate multi-carrier shipment services and est. delivery dates during checkout, create shipment labels with one click and track.
 * Author: Envia Ya S.A. de C.V
 * Version: 2.1.4
 * Tested up to: 6.5.5
 * Requires PHP: 7.2
 * WC requires at least: 8.0
 * WC tested up to: 9.0.2
 * Author: EnvíaYa S.A DE C.V
 * Author URI: https://enviaya.com.mx/
 * Developer: EnvíaYa S.A DE C.V
 * Developer URI: https://enviaya.com.mx/
 * Text Domain: wc-enviaya-shipping
 * Domain Path: /languages
 *
 * Copyright: © 2024 EnvíaYa S.A DE C.V, México.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace EnviaYa\WooCommerce\Shipping;

defined('ABSPATH') || exit;

require_once(__DIR__ . '/includes/autoloader.php');



(new Plugin(__FILE__))->register();