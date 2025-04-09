<?php
/**
 * NOTICE OF LICENSE.
 *
 * This source file is subject to the following license: REGULAR LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @author    Envía Ya S.A. de C.V
 * @copyright Envía Ya S.A. de C.V https://enviaya.com.mx
 * @license   REGULAR LICENSE
 */

namespace EnviaYa\WooCommerce\Shipping;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\EnviaYaApiRatesFinder')):

class EnviaYaApiRatesFinder
{
	
	protected $id;
	protected $adapter;
	protected $display_carrier_name;
	protected $display_service_name;
	protected $display_delivery_time;
	protected $display_carrier_logo;

	protected $error;
	protected $validationErrors;
	protected $logger;


	function __construct($id,$adapter, $settings = array())
	{
		$this->id = $id;
		$this->adapter = $adapter;

		$this->error = null;
		$this->validationErrors = array();

		$this->logger = &\EnviaYa\WooCommerce\Logger\LoggerInstance::getInstance($this->id);

		$this->setSettings($settings);
		
	}

	public function setSettings(array $settings)
	{
		foreach ($settings as $key => $val) {
			if (property_exists($this, $key)) {
				if ($val == 'yes') {
					$this->$key = true;
				} else if ($val == 'no') {
					$this->$key = false;
				} else if (is_bool($val)) {
					$this->$key = boolval($val);
				} else {
					$this->$key = $val;
				}
			}
		}
	}

	public function findShippingRates($params = array())
	{
		
		if (empty($params['destination_direction']['country_code'])) {
			$this->error = __('Destination country code is required',  'wc-enviaya-shipping');
			return null;
		}
		
		if (empty($params['destination_direction']['postal_code'])) {
			$this->error = __('Destination postal code is required',  'wc-enviaya-shipping');
			return null;
		}
		foreach( $params['shipment']['parcels'] as $parcel ){

			if (empty($parcel['weight'])) {
				$this->error = __('Parcel weight must be larger than 0',  'wc-enviaya-shipping');
				$this->logger->debug(__FILE__, __LINE__, "Parcel weight must be larger than 0");

				return null;
			}

		}
		
		$rates = $this->getPackageShippingRates($params);
		
		return $rates;
	}

	private function getPackageShippingRates(array $params)
	{

		$this->error = null;
		$this->validationErrors = array();
		
		$result = $this->adapter->getRates($params);
		
		$subsidy = ( isset($result['shipment']['subsidy']) ) ? $result['shipment']['subsidy'] : false;
		
		if (!empty($result['error']['message'])) {
			$this->error = $result['error']['message'];
		}
		
		if (!isset($result['shipment']['rates'])) {
			$this->logger->debug(__FILE__, __LINE__, 'No rates have been found');
			
			return null;
		}

		
		$rates = array();
		foreach ($result['shipment']['rates'] as $rate) {
		
			$rates[] = $this->prepareRate($rate,$subsidy);
		}

		
		return $rates;
	}

	private function prepareRate($rate, $subsidy = false)
	{
	
		
		
		$rate_id = $rate['rate_id'];
		$cost = $rate['cost'];

		
		if($rate_id == 0 && !is_null($rate['shipment_id'])){ // Store Pickup

			$rate_id = 'enviaya-local-pickup';
			$cost = 0;
			$label = $rate['enviaya_service_name'];

			$meta_data = array(
				
				'rate_id' => $rate_id,
				'shipment_id' => $rate['shipment_id'],
				'dynamic_service_name' => $label,
				'date' => $rate['date'], 
				'net_shipping_amount' => $cost, 
				'net_surcharges_amount' => $cost, 
				'net_total_amount' => $cost, 
				'vat_amount' => $cost, 
				'vat_rate' => $cost, 
				'enviaya_service_name' => $label,
			);

		}else{

			if($this->display_service_name != 'no_service_name'){
				$label = $rate[$this->display_service_name];
			}
	
			$labelPreffix = '';
			if ($this->display_carrier_name && !empty($rate['carrier'])) {
				$labelPreffix .= $rate['carrier'];
			}
	
			if (!empty($labelPreffix)) {
				$label = $labelPreffix.' '.$label;
			}
	
			
			$labelSuffix = '';
	
			if ($this->display_delivery_time == 'delivery_date' && !empty($rate['estimated_delivery'])) {
				
				$labelSuffix .= date_i18n("d F Y", strtotime($rate['estimated_delivery'])) ;
				
			}
	
			if ($this->display_delivery_time == 'delivery_days' && !empty($rate['est_transit_time_hours'])) {
	
				if( $rate['est_transit_time_hours'] < 24 ){
				
					$time_hours = round($rate['est_transit_time_hours'], 1);
		
					if ($time_hours == 1) {
						$labelSuffix .= $time_hours . ' ' . __('hour', 'wc-enviaya-shipping');
					} else {
						$labelSuffix .= $time_hours . ' ' . __('hours', 'wc-enviaya-shipping');
					}
					
				}else{
		
					$days = round( $rate['est_transit_time_hours'] / 24, 1);
		
					if ($days == 1) {
						$labelSuffix .= $days . ' ' . __('day', 'wc-enviaya-shipping');
					} else {
						$labelSuffix .= $days . ' ' . __('days', 'wc-enviaya-shipping');
					}
		
				}
	
			}
	
			if (!empty($labelSuffix)) {
				$label .= ' ( ' . $labelSuffix . ' )';
			}
			
	
			
			if(isset($rate['additional_configuration']['free_shipping'])){
				
				$label = "Envío Gratis"; 
				$cost = 0;

				
			}else{

				if($subsidy){
					$cost = $cost + $subsidy['total_amount'];
	
					if($cost < 0 ){
						$cost = 0;
					}
				}

			}
			

			$meta_data = array(
				'rate_id' => $rate_id,
				'shipment_id' => $rate['shipment_id'],
				'dynamic_service_name' => $rate['dynamic_service_name'], 
				'date' => $rate['date'], 
				'carrier_name' => $rate['carrier'], 
				'carrier_service_name' => $rate['carrier_service_name'], 
				'carrier_service_code' => $rate['carrier_service_code'], 
				'carrier_logo_url' => $rate['carrier_logo_url'], 
				'estimated_delivery' => $rate['estimated_delivery'], 
				'est_transit_time_hours' => $rate['est_transit_time_hours'], 
				'net_shipping_amount' => $rate['net_shipping_amount'], 
				'net_surcharges_amount' => $rate['net_surcharges_amount'], 
				'net_total_amount' => $rate['net_total_amount'], 
				'vat_amount' => $rate['vat_amount'], 
				'vat_rate' => $rate['vat_rate'], 
				'total_amount' => $rate['total_amount'], 
				'currency' => $rate['currency'], 
				'list_total_amount' => $rate['list_total_amount'],
				'list_net_amount' => $rate['list_net_amount'], 
				'list_vat_amount' => $rate['list_vat_amount'], 
				'list_total_amount_currency' => $rate['list_total_amount_currency'], 
				'enviaya_service_name' => $rate['enviaya_service_name'], 
				'enviaya_service_code' => $rate['enviaya_service_code'], 
			);

		}
		
		$woo_rate = array(
			'id' => $rate_id,
			'label' => $label,
			'cost' => $cost,
			'meta_data' => $meta_data
		);
		
		
		return $woo_rate;
	}

	public function getError()
	{
		return $this->error;
	}

	public function getValidationErrors()
	{
		return $this->validationErrors;
	}
}

endif;