<?php

use Skydropx\Api\Skydropx_Api;
use Skydropx\Helper\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 *
 * This class will handle all about the quotation on Skydropx
 */
class Skydropx_Shipping_Method extends \WC_Shipping_Method {
	/**
	 * Default constructor
	 *
	 * @param int $instance_id Shipping Method Instance from Order
	 * @return void
	 */
	public $instance_id;
	public $api;
	public $id;
	public $method_title;
	public $method_description;
	public $supports;

	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );
		$this->api         		  = new Skydropx_Api();
		$this->id                 = SKYDROPX_SHIPPING_METHOD_ID;
		$this->instance_id        = \absint( $instance_id );
		$this->method_title       = __( 'Skydropx', 'skydropx' );
		$this->method_description = __( 'Permite a tus clientes calcular el costo del envío usando Skydropx.', 'skydropx' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);
		$this->init();
	}

    /**
	 * Init user set variables.
	 *
	 * @return void
	 */
	public function init() {
        $this->instance_form_fields = include plugin_dir_path( __FILE__ ) . 'settings.php';
		$this->title                = $this->get_option( 'title' );

		// Save settings in admin if you have any defined
		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
	}

    public function calculate_shipping( $package = array() ) {
		/**
		 * Check if quotation is enabled
		 */
		$SKYDROPX_ENABLE_QUOTATION = boolval(get_option('SKYDROPX_ENABLE_QUOTATION'));
		Helper::log($SKYDROPX_ENABLE_QUOTATION);
		if (!$SKYDROPX_ENABLE_QUOTATION) {
			return;
		}

		$endpoint = '/v3/rates/woocommerce/mx/checkout?shop_id=' . esc_attr(get_option('SKYDROPX_SHOP_ID')); //If SKYDROPX_SHOP_ID is not set, shop_id will be false.
		$rateDefaults = array(
			'label'    => $this->get_option( 'title' ), // Label for the rate
			'cost'     => '0', // Amount for shipping or an array of costs (for per item shipping)
			'taxes'    => '', // Pass an array of taxes, or pass nothing to have it calculated for you, or pass 'false' to calculate no tax for this method
			'calc_tax' => 'per_order', // Calc tax per_order or per_item. Per item needs an array of costs passed via 'cost'
			'package'  => $package,
			'term'     => '',
		);

		Helper::log(__('Quotation', 'skydropx'));

		/**
		 * Prepare all fields for make the request
		 */
		$items = Helper::get_items_from_cart( WC()->cart );
		if ( $items === false ) {
			Helper::log(__('No items', 'skydropx'));

			return;
		}

		$zipTo = $package['destination']['postcode'];
		$addressTo = $package['destination'];
		if ( empty( $zipTo ) ) {
			Helper::log(__('No Zipcode for destination', 'skydropx'));
			return;
		}
		$addressTo['zip_code'] = $addressTo['postcode'];

		$zipFrom = get_option( 'woocommerce_store_postcode' );
		// The main address pieces:
		$addressFrom['address']     = get_option( 'woocommerce_store_address' );
		$addressFrom['address_2']   = get_option( 'woocommerce_store_address_2' );
		$addressFrom['city']        = get_option( 'woocommerce_store_city' );
		// The country/state
		$store_raw_country = get_option( 'woocommerce_default_country' );
		// Split the country/state
		$split_country = explode( ":", $store_raw_country );
		// Country and state separated:
		$addressFrom['country'] = $split_country[0];
		$addressFrom['state']   = $split_country[1];
		$addressFrom['zip_code'] = $zipFrom;
		if ( empty( $zipFrom ) ) {
			Helper::log(__('No Zipcode for origin', 'skydropx'));
			return;
		}

		$grouped_items = Helper::group_items( $items );
				$total_weight  = 0;
		$total_height  = 0;
		$total_width   = 0;
		$total_length  = 0;
		$items = array();
		foreach ( $grouped_items as $item ) {

			$itemToQuote['id'] = $item['id'];
			$itemToQuote['weight'] = floatval($item['weight']);
			$itemToQuote['quantity'] = floatval($item['quantity']);
			$itemToQuote['height'] = floatval($item['height']);
			$itemToQuote['width'] = floatval($item['width']);
			$itemToQuote['length'] = floatval($item['length']);
			array_push($items, $itemToQuote);

			$total_weight = $total_weight + floatval( $item['weight'] ) * floatval( $item['quantity'] );
			$total_height = ( $total_height < floatval( $item['height'] ) )? floatval( $item['height'] ) : $total_height ;
			$total_width  = $total_width + floatval( $item['width'] ) * floatval( $item['quantity'] );
			$total_length = ( $total_length < floatval( $item['length'] ) )? floatval( $item['length'] ) : $total_length ;
		}

		// The request have all needed data, from addresses, items to the calculated parcel to use (the same as conexa)
		$body['address_from'] = $addressFrom;
		$body['zip_from'] =  $zipFrom;
		$body['address_to'] =  $addressTo;
		$body['zip_to'] = $zipTo;
		$body['items'] = $items;
		$body['parcel'] = array(
			'weight' => $total_weight,
			'height' => $total_height,
			'width'  => $total_width,
			'length' => $total_length,
		);

		$headers                 = array();
		$headers['Content-Type'] = 'application/json';

		$api = new Skydropx_Api();

		// Translators: %s is the JSON-encoded body of the shipping data.
		Helper::log(sprintf(__('Calculating shipping for: %s', 'skydropx'), wp_json_encode($body, JSON_UNESCAPED_UNICODE)));

		//Send request to e-commerce service
		$res = $api->post( $endpoint, $body, $headers );

		$response = $this->handle_response( $res );
		if ( $response ) {
			$data = $res['rates'];
			//Parse each quotation
			foreach ( $data as $quote ) {
				$days = intval( $quote['estimated_deliver_days'] );
				if ( $days > 1 ) {
					// Translators: %s is the number of days until the order arrives.
					$promiseString = sprintf( __( 'El pedido llega en %s días.', 'skydropx' ), $days );
				}
				if ( $days == 1 ) {
					$promiseString = __( 'El pedido llega mañana.', 'skydropx' );
				}
				if( $days == 0 ) {
					$promiseString = __( 'El pedido llega hoy.', 'skydropx' );
				}

				$rate          = $rateDefaults;
				$rate['id']    = $this->get_rate_id() . '_' . esc_attr($quote['service_provider']) . '_' . esc_attr($quote['service_code']);
				$rate['label'] = esc_html($quote['service_name']) . ' - ' . esc_html($promiseString);
				$rate['cost']  = floatval( $quote['total_price'] );
				if ( floatval( $quote['total_price'] ) === 0 || empty( floatval( $quote['total_price'] ) ) ) {
					$rate['label'] .= __(' - ¡Gratis!', 'skydropx');
				}
				$rate['meta_data'] = array(
					'provider'                 => esc_html($quote['service_provider']),
					'service_level_name' 	   => esc_html($quote['service_name']),
					'service_level_code' 	   => esc_html($quote['service_code']),
					'days'          		   => esc_attr($quote['estimated_deliver_days']),
					'total_pricing'            => floatval($quote['total_price']),
					'currency_local'           => esc_html($quote['currency']),
				);
				$this->add_rate( $rate );
			}
			return;
		}
	}

	protected function handle_response( $response ) {
		if ( ! isset( $response['rates'] ) ) {
			return false;
		}
		return $response;
	}

}
