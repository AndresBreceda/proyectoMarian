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

namespace EnviaYa\WooCommerce\Shipping\Adapter;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\EnviaYa')):

class EnviaYa extends EnviaYaAbstractAdapter
{
	protected $production_api_key;
	protected $test_api_key;
	protected $enviaya_account;

	public function __construct($id, array $settings = array())
	{
		
		$this->production_api_key = null;
		$this->test_api_key = null;
		$this->enviaya_account = null;
		
		parent::__construct($id, $settings);

		$this->currencies = array(
			'MXN' => __('MXN', $this->id),
			'USD' => __('USD', $this->id),
			'EUR' => __('EUR', $this->id),
		);

		$this->statuses = array(
			'IN_CREATION ' => __('shipment_status_in_creation', $this->id),
			'PENDING_CUSTOMS_INFORMATION ' => __('shipment_status_pending_customs_information', $this->id),
			'WAITING_FOR_CARRIER_CONFIRMATION ' => __('shipment_status_waiting_for_carrier_confirmation', $this->id),
			'SHIPMENT_CREATED ' => __('shipment_status_shipment_created', $this->id),
			'IN_TRANSIT ' => __('shipment_status_in_transit', $this->id),
			'HOLD_AT_LOCATION ' => __('shipment_status_hold_at_location', $this->id),
			'BOOKING_IN_PROCESS ' => __('shipment_status_booking_in_process', $this->id),
			'IN_CUSTOMS_CLEARANCE ' => __('shipment_status_in_customs_clearance', $this->id),
			'INCIDENT ' => __('shipment_status_incident', $this->id),
			'DELIVERED ' => __('shipment_status_delivered', $this->id),
			'STORE_PICKUP ' => __('shipment_status_store_pickup', $this->id),
			'CANCELLED ' => __('shipment_status_cancelled', $this->id),
			'BOOKING_ERROR ' => __('shipment_status_booking_error', $this->id),
		);

		$this->shipment_type = array(
			'DOCUMENT' => __('Document', $this->id),
			'PACKAGE' => __('Package', $this->id),
		);


	}

	public function validate(array $settings)
	{
		$errors = array();

		$this->setSettings($settings);

		$apiTokenKey = 'liveApiToken';
		$apiTokenName = __('Live API Token', $this->id);
		if ($settings['sandbox'] == 'yes') {
			$apiTokenKey = 'testApiToken';
			$apiTokenName = __('Test API Token', $this->id);
		}

		if (empty($settings[$apiTokenKey])) {
			$errors[] = sprintf('<strong>%s:</strong> %s', $apiTokenName, __('is required for the integration to work', $this->id));
		} else if (!$this->validateActiveApiToken()) {
			$errors[] = sprintf('<strong>%s:</strong> %s', $apiTokenName, __('is invalid', $this->id));
		}

		return $errors;
	}

	public function getIntegrationFormFields()
	{
		$formFields = array(
			'enviaya_terms' => array(
				'type' => 'title',
				'description' => sprintf(
					'<div class="notice notice-info inline"><p>%s<br/>%s</p></div>',
					__('Please note that new EnvíaYa! accounts require manual verification.', $this->id),
					__('If plugin has suddently stopped returning shipping rates then you will have to email to support@goenviaya.com to re-activate your account.', $this->id)
				),
			),

			'liveApiToken' => array(
				'title' => __('Live API Token', $this->id),
				'type' => 'text',
			),
			'testApiToken' => array(
				'title' => __('Sandbox / Test API Token', $this->id),
				'type' => 'text',
			),
			'labelFileType' => array(
				'title' => __('Shipping Label Format', $this->id),
				'type' => 'select',
				'options' => array(
					'PNG' => 'PNG', 
					'PNG_2.3x7.5' => 'PNG_2.3x7.5', 
					'PDF' => 'PDF',
					'PDF_2.3x7.5' => 'PDF_2.3x7.5',
					'PDF_4x6' => 'PDF_4x6',
					'PDF_4x8' => 'PDF_4x8',
					'PDF_A4' => 'PDF_A4',
					'PDF_A6' => 'PDF_A6',
					'ZPLII' => 'ZPLII'
				),
				'default' => 'PDF'
			),
		);

		return $formFields;
	}

	public function getCacheKey(array $params)
	{

		return parent::getCacheKey($params);
	}

	protected function getRequestBody(&$headers, &$params)
	{
		$headers['Content-Type'] = 'application/json';

		return json_encode($params);
	}

	protected function getRatesParams(array $inParams)
	{
		$params = array();
		$params['async'] = false;
		$params['mode'] = $this->sandbox ? 'test' : 'production';
		$params['extra']['is_return'] = false;
		if (!empty($inParams['return'])) {
			$params['extra']['is_return'] = filter_var($inParams['return'], FILTER_VALIDATE_BOOLEAN);
		}

		if (!empty($inParams['order_id'])) {
			$params['extra']['reference_1'] = $inParams['order_id'];
			$params['metadata'] = sprintf('Order %s', $inParams['order_id']);
		}
		if (!empty($inParams['order_number'])) {
			$params['extra']['reference_2'] = $inParams['order_number'];
		}

		if ($this->isInsuranceRequested($inParams) && !empty($inParams['value'])) {
			$params['extra']['insurance'] = array(
				'amount' => $inParams['value'],
				'currency' => $this->getRequestedCurrency($inParams),
			);
		}

		if ($this->isSignatureRequested($inParams)) {
			$params['extra']['signature_confirmation'] = 'STANDARD';
		}
		
		$inParams['origin'] = $this->getRequestedOrigin($inParams);

		if (!empty($inParams['origin'])) {
			$this->logger->debug(__FILE__, __LINE__, 'From Address: ' . print_r($inParams['origin'], true));

			$params['address_from'] = $this->getCachedAddress($inParams['origin']);
		}

		if (!empty($inParams['destination'])) {
			$this->logger->debug(__FILE__, __LINE__, 'To Address: ' . print_r($inParams['destination'], true));

			$params['address_to'] = $this->getCachedAddress($inParams['destination']);
		}

		$params['parcels'] = $this->getCachedParcelInfo($inParams);

		if (isset($inParams['origin']['country']) 
			&& isset($inParams['destination']['country'])
			&& $inParams['origin']['country'] != $inParams['destination']['country']) {
			$params['customs_declaration'] = $this->getCachedCustomsInfo($inParams);
		}

		return $params;
	}

	protected function getCachedParcelInfo(array $inParams)
	{
		$parcel = $this->prepareParcelInfo($inParams);

		$cacheKey = $this->getCacheKey($parcel);
		$parcelId = $this->getCacheValue($cacheKey);
		if (!empty($parcelId)) {
			$this->logger->debug(__FILE__, __LINE__, 'Found previous cached parcel ID: ' . $parcelId . ', so re-use it');
			
			return $parcelId;
		}

		return $parcel;
	}

	protected function prepareParcelInfo(array $inParams)
	{
		$this->logger->debug(__FILE__, __LINE__, 'prepareParcelInfo');

		if (!empty($inParams['type']) && $inParams['type'] != 'parcel' && isset($this->packageTypes[$inParams['type']])) {
			$parcel['template'] = $inParams['type'];
		}

		$parcel['weight'] = 0;
		$parcel['length'] = 0;
		$parcel['width'] = 0;
		$parcel['height'] = 0;

		if (isset($inParams['weight'])) {
			$parcel['weight'] = round($inParams['weight'], 2);
		}

		if (isset($inParams['length'])) {
			$parcel['length'] = round($inParams['length'], 2);
		}

		if (isset($inParams['width'])) {
			$parcel['width'] = round($inParams['width'], 2);
		}

		if (isset($inParams['height'])) {
			$parcel['height'] = round($inParams['height'], 2);
		}

		$dimensionUnit = $this->dimensionUnit;
		if (isset($inParams['dimension_unit']) && in_array($inParams['dimension_unit'], array('m', 'cm', 'mm', 'in'))) {
			$dimensionUnit = $inParams['dimension_unit'];
		}

		$parcel['distance_unit'] = $dimensionUnit;

		$weightUnit = $this->weightUnit;
		if (isset($inParams['weight_unit']) && in_array($inParams['weight_unit'], array('g', 'kg', 'lbs', 'oz'))) {
			$weightUnit = $inParams['weight_unit'];
		}

		if ($weightUnit == 'lbs') {
			$weightUnit = 'lb';
		}

		$parcel['mass_unit'] = $weightUnit;

		return array($parcel);
	}

	protected function getCachedCustomsInfo(array $inParams)
	{
		$customsInfo = $this->prepareCustomsInfo($inParams);

		$cacheKey = $this->getCacheKey($customsInfo);
		$customsInfoId = $this->getCacheValue($cacheKey);
		if (!empty($customsInfoId)) {
			$this->logger->debug(__FILE__, __LINE__, 'Found previous cached customs info ID: ' . $customsInfoId . ', so re-use it');
			
			return $customsInfoId;
		}

		return $customsInfo;
	}

	protected function prepareCustomsInfo(array $inParams)
	{
		$this->logger->debug(__FILE__, __LINE__, 'prepareCustomsInfo');

		$customsInfo = array(
			'certify' => true,
			'non_delivery_option' => 'RETURN'
		);

		if (!empty($inParams['origin']['name'])) {
			$customsInfo['certify_signer'] = trim($inParams['origin']['name']);
		} else if (!empty($inParams['origin']['company'])) {
			$customsInfo['certify_signer'] = trim($inParams['origin']['company']);
		} else {
			$customsInfo['certify_signer'] = 'Shipper';
		}

		if (!empty($inParams['order_number'])) {
			$customsInfo['invoice'] = $inParams['order_number'];
		}

		$customsInfo['contents_type'] = 'MERCHANDISE';
		if (!empty($inParams['contents']) && !empty($this->contentTypes[$inParams['contents']])) {
			$customsInfo['contents_type'] = $inParams['contents'];
		}

		if (isset($inParams['description'])) {
			$customsInfo['contents_explanation'] = $inParams['description'];
		}

		$defaultOriginCountry = '';
		if (isset($inParams['origin']['country'])) {
			$defaultOriginCountry = strtoupper($inParams['origin']['country']);
		}

		if (!empty($inParams['items']) && is_array($inParams['items'])) {
			$customsInfo['items'] = $this->prepareCustomsItems($inParams['items'], $defaultOriginCountry);
		}

		$this->logger->debug(__FILE__, __LINE__, 'Customs Info: ' . print_r($customsInfo, true));

		return $customsInfo;
	}

	protected function prepareCustomsItems(array $itemsInParcel, $defaultOriginCountry)
	{
		$this->logger->debug(__FILE__, __LINE__, 'prepareCustomsItems');

		$customsItems = array();

		foreach ($itemsInParcel as $itemInParcel) {
			if (empty($itemInParcel['country'])) {
				$itemInParcel['country'] = $defaultOriginCountry;
			}

			$customsItem = $this->prepareCustomsItem($itemInParcel);
			if (!empty($customsItem)) {
				$customsItems[] = $customsItem;
			}
		}
		
		return $customsItems;
	}

	protected function prepareCustomsItem($itemInParcel)
	{
		if (empty($itemInParcel['name']) || 
			!isset($itemInParcel['weight']) || 
			empty($itemInParcel['quantity']) ||
			!isset($itemInParcel['value'])) {
			$this->logger->debug(__FILE__, __LINE__, 'Item is invalid, so skip it ' . print_r($itemInParcel, true));

			return false;
		}

		$this->logger->debug(__FILE__, __LINE__, 'Customs Item: ' . print_r($itemInParcel, true));

		$weight = $itemInParcel['weight'] * $itemInParcel['quantity'];
		$value = $itemInParcel['value'] * $itemInParcel['quantity'];

		$tariff = $this->defaultTariff;
		if (!empty($itemInParcel['tariff'])) {
			$tariff = $itemInParcel['tariff'];
		}

		$weightUnit = $this->weightUnit;
		if ($weightUnit == 'lbs') {
			$weightUnit = 'lb';
		}

		$description = preg_replace('/[^\w\d\s]/', '?', utf8_decode($itemInParcel['name']));

		$customsItem = array(
			'description' => substr($description, 0, min(self::MAX_DESCRIPTION_LENGTH, strlen($description))),
			'quantity' => $itemInParcel['quantity'],
			'value_amount' => round($value, 3),
			'value_currency' => $this->currency,
			'net_weight' => round($weight, 3),
			'mass_unit' => $weightUnit,
			'origin_country' => $itemInParcel['country'],
			'tariff_number' => $tariff
		);

		return $customsItem;
	}

	protected function getCachedAddress($options)
	{
		$addr = $this->prepareAddress($options);

		$cacheKey = $this->getCacheKey($addr);
		$addrId = $this->getCacheValue($cacheKey);
		if (!empty($addrId)) {
			$this->logger->debug(__FILE__, __LINE__, 'Found previous cached address ID: ' . $addrId . ', so re-use it');

			$addr = $addrId;
		}

		return $addr;
	}
	
	protected function prepareAddress($options)
	{
		$addr = array('validate' => $this->validateAddress);

		$addr['is_residential'] = true;

		if (!empty($options['name'])) {
			$addr['name'] = $options['name'];
		} else {
			$addr['name'] = 'Resident';
		}

		if (!empty($options['company'])) {
			$addr['company'] = $options['company'];
			$addr['is_residential'] = false;

			if (empty($options['name'])) {
				$addr['name'] = $options['company'];
			}
		}

		if (isset($options['email'])) {
			$addr['email'] = $options['email'];
		}

		if (isset($options['phone'])) {
			if (is_array($options['phone'])) {
				$options['phone'] = current($options['phone']);
			}
			
			$addr['phone'] = $options['phone'];
		}

		if (isset($options['email'])) {
			if (is_array($options['email'])) {
				$options['email'] = current($options['email']);
			}
			
			$addr['email'] = $options['email'];
		}

		if (isset($options['country'])) {
			$addr['country'] = strtoupper($options['country']);
		}

		if (isset($options['state'])) {
			$addr['state'] = $options['state'];
		}

		if (isset($options['postcode'])) {
			$addr['zip'] = $options['postcode'];
		}

		if (isset($options['city'])) {
			$addr['city'] = $options['city'];
		}

		if (!empty($options['address'])) {
			$addr['street1'] = $options['address'];
		}

		if (isset($options['address_2'])) {
			$addr['street2'] = $options['address_2'];
		}

		return $addr;
	}

	protected function getValidationErrors($addressField, $addressType)
	{
		if (empty($addressField['validation_results']) || !empty($addressField['validation_results']['is_valid'])) {
			$this->logger->debug(__FILE__, __LINE__, 'Address is valid');

			return array();
		}
		
		$this->logger->debug(__FILE__, __LINE__, 'Address is invalid: ' . print_r($addressField['validation_results'], true));

		$validationErrors = array();

		foreach ($addressField['validation_results']['messages'] as $error) {
			$errorMessage = $this->getErrorMessage($error);
			$validationErrors[$addressType][] = $errorMessage;
		}

		return $validationErrors;
	}

	protected function getShipmentResponse($response, array $params)
	{
		
		$currency = $this->getRequestedCurrency($params);
		
		$rates_response = array();
		
		if( is_array($response) ){
			foreach($response as $carrier_name => $carrier_rates ){
				if( $carrier_name != 'warning' && $carrier_name != 'errors' ){
					if($carrier_name == 'store_pickup'){
						$rates_response = array_merge($rates_response, array($carrier_rates) );
					}
					elseif($carrier_name == 'subsidy'){
						$subsidy = $carrier_rates;
					}
					else{
						$rates_response = array_merge($rates_response, $carrier_rates);
					}
					
				}
			}
		}
		
	
		if ( !empty($rates_response) ) {
			$rates = array();

			$shipment_id = '';

			foreach ($rates_response as $rate) {

				if(empty($shipment_id)){
					$shipment_id = $rate['shipment_id'];
				}

				$id = $rate['rate_id'];

				$rate['cost'] = $rate['total_amount'];
				
				$rates[$id] = $rate;

			}
			
			$shipment['id'] = $shipment_id;
			$shipment['ship_date'] = date('c');
			$shipment['rates'] = $this->sortRates($rates);

			if(isset($subsidy)){
				$shipment['subsidy'] = $subsidy;
			}

		}
		
		$newResponse = array();
		if (!empty($shipment)) {
			$newResponse['shipment'] = $shipment;
		}

		
		
		return $newResponse;
	}

	protected function getRatesResponse($response, array $params)
	{
		
		$newResponse = $this->getShipmentResponse($response, $params);
		if (!empty($newResponse['shipment']['id'])) {
			//$this->setShipmentCacheValues($response, $params);
		}
		
		return $newResponse;
	}

	protected function setShipmentCacheValues($response, array $params)
	{
		$this->logger->debug(__FILE__, __LINE__, 'setShipmentCacheValues');

		if (isset($response['address_from']) && $this->isResponseObjectValid($response['address_from'])) {
			$addrId = $response['address_from']['object_id'];
			$this->logger->debug(__FILE__, __LINE__, 'Cache from address ID: ' . $addrId);

			$origin = array();
			if (!empty($params['origin'])) {
				$origin = $params['origin'];
			}
			if (empty($origin) && !empty($this->origin)) {
				$origin = $this->origin;
			}
	
			if (!empty($origin)) {
				$addr = $this->prepareAddress($origin);
				$cacheKey = $this->getCacheKey($addr);
				$this->setCacheValue($cacheKey, $addrId);	
			}
		}

		if (isset($response['address_to']) && $this->isResponseObjectValid($response['address_to'])) {
			$addrId = $response['address_to']['object_id'];
			$this->logger->debug(__FILE__, __LINE__, 'Cache to address ID: ' . $addrId);
			
			if (!empty($params['destination'])) {
				$addr = $this->prepareAddress($params['destination']);
				$cacheKey = $this->getCacheKey($addr);
				$this->setCacheValue($cacheKey, $addrId);	
			}
		}

		if (isset($response['parcels'][0]) && $this->isResponseObjectValid($response['parcels'][0])) {
			$parcelId = $response['parcels'][0]['object_id'];
			$this->logger->debug(__FILE__, __LINE__, 'Cache parcel ID: ' . $parcelId);

			$parcel = $this->prepareParcelInfo($params);
			$cacheKey = $this->getCacheKey($parcel);

			$this->setCacheValue($cacheKey, $parcelId);
		}

		if (isset($response['customs_declaration']) && $this->isResponseObjectValid($response['customs_declaration'])) {
			$customsInfoId = $response['customs_declaration']['object_id'];
			$this->logger->debug(__FILE__, __LINE__, 'Cache customs info ID: ' . $customsInfoId);

			$customsInfo = $this->prepareCustomsInfo($params);
			$cacheKey = $this->getCacheKey($customsInfo);

			$this->setCacheValue($cacheKey, $customsInfoId);
		}
	}

	protected function isResponseObjectValid($object)
	{
		$isValid = false;

		if (!empty($object['object_id']) && !empty($object['object_state']) && $object['object_state'] == "VALID") {
			$isValid = true;
		}

		return $isValid;
	}

	protected function addHeadersAndParams(&$headers, &$params)
	{
		
	}

	public function getServices()
	{
		return $this->_services;
	}

	public function getOriginDirectionById($direction_id,$params){

		$this->logger->debug(__FILE__, __LINE__, 'getOriginDirectionById '.$direction_id);
		
		$response = $this->sendRequest('directions', 'GET', $params);
		
		$this->logger->debug(__FILE__, __LINE__, 'Response: ' . print_r($response, true));
		
		return $response;

	}


}

endif;
