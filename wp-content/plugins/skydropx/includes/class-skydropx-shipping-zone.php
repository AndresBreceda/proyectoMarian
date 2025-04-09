<?php
namespace Skydropx\Includes;

use Skydropx\Helper\Helper;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Ensure ABSPATH is defined, which means we are in a WordPress environment
}

if ( !class_exists( '\WC_Shipping_Zone' ) || !class_exists( '\WC_Shipping_Zones' ) ) {
    exit; // Exit if any of the required WooCommerce shipping classes don't exist
}

/**
 * Class Skydropx Shipping Class
 */
class Skydropx_Shipping_Zone extends \WC_Shipping_Zone {
    const SUPPORTED_COUNTRIES = array('CO', 'MX');

    public function __construct() {
        parent::__construct();
    }

    public function already_exists() {
        return !is_null($this->get_previous_data());
    }

    private function get_previous_data() {
        $zones = \WC_Shipping_Zones::get_zones();

        foreach ((array) $zones as $key => $zone ) {
            if( $zone['zone_name'] === SKYDROPX_SHIPPING_ZONE_NAME) {
                return $zone;
            }
        }

        return null;
    }

    public function create() {
        $country_quotation = strtoupper(get_option('SKYDROPX_COUNTRY_QUOTATION', 'MX'));

        if ($this->already_exists()) {
			return;
		}

        $this->set_zone_name( SKYDROPX_SHIPPING_ZONE_NAME );

        if(in_array($country_quotation, self::SUPPORTED_COUNTRIES)){
            $this->add_location( $country_quotation, 'country' );
        }

        $this->add_shipping_method( SKYDROPX_SHIPPING_METHOD_ID );
    }

    public static function remove() {
        $zones = \WC_Shipping_Zones::get_zones();

        foreach ((array) $zones as $key => $zone ) {
            if( $zone['zone_name'] === SKYDROPX_SHIPPING_ZONE_NAME) {
                \WC_Shipping_Zones::delete_zone($zone['zone_id']);
            }
        }

        // Translators: %s is the name of the shipping zone that was deleted
        Helper::log_info(esc_html__('WC shipping zone deleted', 'skydropx'));
    }
}

?>
