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

if (!class_exists(__NAMESPACE__ . '\\AbstractAdapter')):

abstract class EnviaYaAbstractAdapter
{
	protected $id;
	protected $timeout;
	protected $logger;
	protected $test_mode;
	protected $account;
	protected $cod;
	protected $insurance;
	protected $signature;
	protected $alcohol;
	protected $dryIce;
	protected $mediaMail;
	protected $includeDeliveryFee;
	protected $weightUnit;
	protected $dimensionUnit;
	protected $currency;
	protected $currencies;
	protected $statuses;
	protected $completedStatuses;
	protected $contentTypes;
	protected $packageTypes;
	protected $useSellerAddress;
	protected $origin;
	protected $cache;
	protected $cacheExpirationInSecs;
	protected $defaultTariff;
	protected $validateAddress;
	protected $_carriers;
	protected $_services;
	
	public function __construct($id, array $settings = array())
	{
		$this->id = $id;
		$this->timeout = 120;
		$this->sandbox = false;
		$this->cod = false;
		$this->insurance = false;
		$this->signature = false;
		$this->alcohol = false;
		$this->dryIce = false;
		$this->mediaMail = false;
		$this->includeDeliveryFee = false;
		$this->weightUnit = get_option('woocommerce_weight_unit');
		$this->dimensionUnit = get_option('woocommerce_dimension_unit');
		$this->currencies = array('MXN' => __('MXN', 'wc-enviaya-shipping'), 'USD' => __('USD', 'wc-enviaya-shipping'));
		$this->currency = current(array_keys($this->currencies));
		$this->statuses = array();
		$this->completedStatuses = array();
		$this->contentTypes = array();
		$this->packageTypes = array();
		$this->useSellerAddress = false;
		$this->origin = array();
		$this->cache = true;
		$this->cacheExpirationInSecs = 7 * 24 * 60 * 60; // 7 days
		$this->defaultTariff = '';
		$this->validateAddress = false;
		$this->_carriers = array();
		$this->_services = array();

		$this->logger = &\Enviaya\WooCommerce\Logger\LoggerInstance::getInstance($this->id);

		add_filter('http_request_timeout', array($this, 'getRequestTimeout'), 2, PHP_INT_MAX);

		$this->setSettings($settings);
	}

	public function getApiKey()
	{
		return $this->test_mode ? $this->test_api_key : $this->production_api_key;
	}

	public function getEnviayaAccount()
	{
		
		return $this->account;
	}

	public function getBillingAccounts(array $params)
	{	

		$this->logger->debug(__FILE__, __LINE__, 'getBillingAccounts');
		
		$response = $this->sendRequest('get_accounts', 'GET', $params);
	
		$this->logger->debug(__FILE__, __LINE__, 'Response: ' . print_r($response, true));
		
		return $response;
	}

	public function getOriginDirections(array $params)
	{	

		$this->logger->debug(__FILE__, __LINE__, 'getOriginDirections');
		
		$response = $this->sendRequest('directions', 'GET', $params);
		
		$this->logger->debug(__FILE__, __LINE__, 'Response: ' . print_r($response, true));
		
		return $response;
	}

	public function getPostalCodeInformation(array $params)
	{	

		$this->logger->debug(__FILE__, __LINE__, 'getBillingAccounts');
		
		$response = $this->sendRequest('get_postal_code_information', 'GET', $params);
	
		$this->logger->debug(__FILE__, __LINE__, 'Response: ' . print_r($response, true));

		return $response;
	}

	public function getRequestTimeout($timeout, $url)
	{
		$routeUrl = $this->getRouteUrl('');
		if (is_string($routeUrl)) {
			if (strpos($url, $routeUrl) !== false) {
				$timeout = $this->timeout;
			}	
		}
		
		return $timeout;
	}

	public function setSettings(array $settings)
	{

		
		array_walk_recursive($settings, array($this, 'convertBooleanArrayValues'));
		
		foreach ($settings as $key => $val) {
			if (property_exists($this, $key)) {
				$this->$key = $val;
			}
		}
	}

	public function convertBooleanArrayValues(&$val, $key)
	{
		if (is_string($val)) {
			if ($val == 'yes') {
				$val = true;
			} else if ($val == 'no') {
				$val = false;
			} else if (is_bool($val)) {
				$val = boolval($val);
			}
		}
	}

	public function getCurrencies()
	{
		return $this->currencies;
	}

	public function getStatuses()
	{
		return $this->statuses;
	}

	public function getStatusName($status)
	{
		$statusName = $status;
		if (isset($this->statuses[$status])) {
			$statusName = $this->statuses[$status];
		}

		return $statusName;
	}

	public function getCompletedStatuses()
	{
		return $this->completedStatuses;
	}

	public function getContentTypes()
	{
		return $this->contentTypes;
	}

	public function getPackageTypes($destination = array())
	{
		return $this->packageTypes;
	}

	public function getShipmentUrl($shipment)
	{
		return null;
	}

	public function canClaimInsurance($shipment)
	{
		return false;
	}

	public function getClaimInsuranceUrl($shipment)
	{
		return null;
	}

	public function getCarriers()
	{
		return $this->_carriers;
	}

	public function getCarrierName($carrier)
	{
		$carrierName = $carrier;
		if (isset($this->_carriers[$carrier])) {
			$carrierName = $this->_carriers[$carrier];
		}

		return $carrierName;
	}

	public function getServices()
	{
		return $this->_services;
	}

	public function isReady($shipment)
	{
		return false;
	}

	public function canBuy($shipment)
	{
		return false;
	}

	public function canDelete($shipment)
	{
		return false;
	}

	public function canRefund($shipment)
	{
		return false;
	}

	public function hasCustomItemsFeature()
	{
		return false;
	}

	public function hasTariffFeature()
	{
		return false;
	}

	public function hasUseSellerAddressFeature()
	{
		return false;
	}

	public function hasReturnLabelFeature()
	{
		return false;
	}

	public function hasAddressValidationFeature()
	{
		return false;
	}

	public function hasLinkFeature()
	{
		return false;
	}

	public function hasMediaMailFeature()
	{
		return false;
	}

	public function hasOriginFeature()
	{
		return false;
	}

	public function hasInsuranceFeature()
	{
		return false;
	}

	public function hasSignatureFeature()
	{
		return false;
	}

	public function hasAlcoholFeature()
	{
		return false;
	}

	public function hasDryIceFeature()
	{
		return false;
	}

	public function hasCodFeature()
	{
		return false;
	}

	public function hasDisplayDeliveryTimeFeature()
	{
		return false;
	}

	public function hasDisplayTrackingTypeFeature()
	{
		return false;
	}

	public function hasUpdateShipmentsFeature()
	{
		return false;
	}

	public function hasCreateShipmentFeature()
	{
		return false;
	}

	public function hasImportShipmentsFeature()
	{
		return false;
	}

	public function hasFreightClassFeature()
	{
		return false;
	}

	public function hasCreateOrderFeature()
	{
		return false;
	}

	public function hasCreateManifestsFeature()
	{
		return false;
	}

	public function hasCarriersFeature()
	{
		return false;
	}

	public function updateFormFields($formFields)
	{
		return $formFields;
	}

	public function validate(array $settings)
	{
		return array();
	}

	public function getList(array $parms)
	{
		return array('error' => array('message' => 'getList is not implemented'));
	}

	public function get($shipmentId)
	{
		return array('error' => array('message' => 'get is not implemented'));
	}

	public function createShipment(array $params)
	{
		
		$this->logger->debug(__FILE__, __LINE__, 'createShipment');
		
		$params['function'] = __FUNCTION__;
		$params['api_key'] = $this->getApiKey();
		$params['enviaya_account'] = $this->getEnviayaAccount();
		
		$this->logger->debug(__FILE__, __LINE__, 'CREATE SHIPMENT REQUEST: ' . print_r($params, true));
		
		$response = $this->sendRequest('shipments', 'POST', $params);
		
		if (!empty($response['shipment']['id']) && !empty($response['shipment']['rates'])) {
			//$this->logger->debug(__FILE__, __LINE__, 'Cache shipment for the future');
			//$this->setCacheValue($cacheKey, $response);
		}

		

		$this->logger->debug(__FILE__, __LINE__, 'CREATE SHIPMENT RESPONSE: ' . print_r($response, true));

		return $response;
	}

	public function getRates(array $params)
	{
		
		$this->logger->debug(__FILE__, __LINE__, 'getRates');

		$cacheKey = $this->getCacheKey($params);
		
		$response = $this->getCacheValue($cacheKey);
		
		if (empty($response)) {
			$params['function'] = __FUNCTION__;
			
			$response = $this->sendRequest('rates', 'POST', $params);
			
			if (!empty($response['shipment']['id']) && !empty($response['shipment']['rates'])) {
				$this->logger->debug(__FILE__, __LINE__, 'Cache rates for the future');
		
				//$this->setCacheValue($cacheKey, $response);
			}

		} else {
			$this->logger->debug(__FILE__, __LINE__, 'Found previously returned rates, so return them');
		}

		$this->logger->debug(__FILE__, __LINE__, 'Response: ' . print_r($response, true));
		
		return $response;
	}

	public function create(array $params)
	{
		return array('error' => array('message' => 'create is not implemented'));
	}

	public function delete($shipmentId)
	{
		return array('error' => array('message' => 'delete is not implemented'));
	}

	public function buy($shipmentId)
	{
		return array('error' => array('message' => 'buy is not implemented'));
	}

	public function refund($shipmentId)
	{
		return array('error' => array('message' => 'refund is not implemented'));
	}

	public function createOrder(array $params)
	{
		return array('error' => array('message' => 'createOrder is not implemented'));
	}

	public function createManifests(array $shipmentIds, $shipTime = 'now')
	{
		return array('error' => array('message' => 'createManifests is not implemented'));
	}

	public function getCurrency()
	{
		$storeCurrencyCode = get_option('woocommerce_currency', $this->getDefaultCurrency());
		
		return $storeCurrencyCode;
	}

	public function getDefaultCurrency()
	{
		$defaultCurrencyCode = $this->currency;
		$storeCurrencyCode = get_option('woocommerce_currency', '');
		
		foreach ($this->currencies as $currencyCode => $currencyName) {
			if (strtolower($storeCurrencyCode) == strtolower($currencyCode)) {
				$defaultCurrencyCode = $currencyCode;
				break;
			}
		}

		return $defaultCurrencyCode;
	}
	
	public function setCacheValue($cacheKey, $value, $cacheExpirationInSecs = null)
	{
		if (is_null($cacheExpirationInSecs)) {
			$cacheExpirationInSecs = $this->cacheExpirationInSecs;
		}

		$success = true;
		if (!empty($cacheKey)) {
			delete_transient($cacheKey);
			$success = set_transient($cacheKey, $value, $cacheExpirationInSecs);
		}

		return $success;
	}

	public function getCacheValue($cacheKey, $useCache = null)
	{
		if (!is_bool($useCache)) {
			$useCache = $this->cache;
		}

		$value = null;
		if (!empty($cacheKey) && $useCache) {
			$value = get_transient($cacheKey);
		}
		
		return $value;
	}

	public function deleteCacheValue($cacheKey)
	{
		$success = true;
		if (!empty($cacheKey)) {
			$success = delete_transient($cacheKey);
		}

		return $success;
	}

	public function getCacheKey(array $params)
	{
		if (isset($params['function'])) {
			unset($params['function']);
		}

		$jsonData = json_encode($params);
		$cacheKey = md5($jsonData) . '_enviaya' . ($this->test_mode ? '_test' : '_production');

		$this->logger->debug(__FILE__, __LINE__, 'getCacheKey: ' . $jsonData);
		$this->logger->debug(__FILE__, __LINE__, 'cacheKey: ' . $cacheKey);

		return $cacheKey;
	}
	
	public function sortRates(array $rates)
	{
		uasort($rates, function ($rate1, $rate2) { return $rate1['cost'] > $rate2['cost'] ? 1 : -1; });

		return $rates;
	}

	protected function sendRequest($route, $method = 'GET', array $params = array())
	{
		$url = $this->getRouteUrl($route);
		
		if ($url === false) {
			return false;
		}
		
		if (!in_array($method, array('GET', 'POST', 'DELETE', 'PATCH', 'PUT'))) {
			$method = 'GET';
		}

		$headers = array();

		$requestParams = $params;
		$this->addHeadersAndParams($headers, $requestParams);
		
		$this->logger->debug(__FILE__, __LINE__, "Request Params: " . print_r($requestParams, true));

		$data = array();
		if ($method == 'GET') {
			if (!empty($requestParams)) {
				if (strpos($url, '?') === false) {
					$url .= '?';
				} else {
					$url .= '&';
				}
				
				$url .= http_build_query($requestParams);
			}
		} else {
			$data = $this->getRequestBody($headers, $requestParams);
		}

		$response = $this->wpHttpRequest($url, $method, $headers, $data);
		
		$response = $this->parseResponse($response);
		
		$this->logger->debug(__FILE__, __LINE__, "Parsed Response: " . print_r($response, true));

		$response = $this->getResponse($response, $params);
		
		$this->logger->debug(__FILE__, __LINE__, "Response: " . print_r($response, true));

		return $response;
	}

	protected function wpHttpRequest($url, $method, array $headers, $data)
	{
		$params = array(
			'method' => $method,
			'timeout' => 120,
			'sslverify' => false,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
		);

		if (strtoupper($method) != 'GET') {
			$params['body'] = $data;
		}

		$this->logger->debug(__FILE__, __LINE__, 'url: ' . $url);
		$this->logger->debug(__FILE__, __LINE__, 'params: ' . print_r($params, true));

		$response = wp_remote_post($url, $params);
		
		if (is_wp_error($response)) {
            $this->logger->debug(__FILE__, __LINE__, $response->get_error_message());

            return null;
		}
		
		$responseBody = $response['http_response']->get_data();
		
		$this->logger->debug(__FILE__, __LINE__, 'response body: ' . $responseBody);

		return $responseBody;
	}

	protected function addHeadersAndParams(&$headers, &$params)
	{
	}

	protected function getRequestBody(&$headers, &$params)
	{
		return $params;
	}

	protected function parseResponse($response)
	{
		if (is_string($response)) {
			$response = json_decode($response, true);
		}
		
		if(isset($response['response'])){
			$response = $response['response'];
		}
		
		return $response;
	}

	protected function addFormFieldsAt($formFields, $newFormFields, $fieldKey = '', $offset = 0)
	{
		$pos = array_search($fieldKey, array_keys($formFields)) + $offset;

		$formFields = array_slice($formFields, 0, $pos, true)
			+ $newFormFields
			+ array_slice($formFields, $pos, count($formFields) - $pos, true);

		return $formFields;
	}

	protected function getRequestedCurrency(array $inParams)
	{
		
		$currency = $this->currency;
		if (isset($inParams['currency'])) {
			$currency = strtoupper($inParams['currency']);
			if (isset($this->currencies[$currency])) {
				$currency = strtoupper($inParams['currency']);
			}
		}
		
		return $currency;
	}

	protected function getRequestedOrigin(array $inParams)
	{
		$origin = $this->origin;
		if (!empty($inParams['origin'])) {
			$origin = $inParams['origin'];
		}

		return $origin;
	}

	protected function isInsuranceRequested(array $inParams)
	{
		$insurance = $this->insurance;
		if (isset($inParams['insurance'])) {
			$insurance = filter_var($inParams['insurance'], FILTER_VALIDATE_BOOLEAN);
		}
		
		return $insurance;
	}

	protected function isSignatureRequested(array $inParams)
	{
		$signature = $this->signature;
		if (isset($inParams['signature'])) {
			$signature = filter_var($inParams['signature'], FILTER_VALIDATE_BOOLEAN);
		}
		
		return $signature;
	}

	protected function isAlcoholRequested(array $inParams)
	{
		$alcohol = $this->alcohol;
		if (isset($inParams['alcohol'])) {
			$alcohol = filter_var($inParams['alcohol'], FILTER_VALIDATE_BOOLEAN);
		}
		
		return $alcohol;
	}

	protected function isDryIceRequested(array $inParams)
	{
		$dryIce = $this->dryIce;
		if (isset($inParams['dryIce'])) {
			$dryIce = filter_var($inParams['dryIce'], FILTER_VALIDATE_BOOLEAN);
		}
		
		return $dryIce;
	}

	protected function isCodRequested(array $inParams)
	{
		$cod = $this->cod;
		if (isset($inParams['cod'])) {
			$cod = filter_var($inParams['cod'], FILTER_VALIDATE_BOOLEAN);
		}
		
		return $cod;
	}

	protected function getRequestParams(array $inParams)
	{
		$this->logger->debug(__FILE__, __LINE__, 'getRequestParams: ' . print_r($inParams, true));
		
		$params = $inParams;
		
		if (!empty($inParams['function']) && $inParams['function'] == 'getRates') {
			$params = $this->getRatesParams($inParams);
		}
		
		return $params;
	}

	protected function getRouteUrl($route)
	{
		$routeUrl = sprintf('https://enviaya.com.mx/api/v1/%s', $route);

		return $routeUrl;
	}

	protected function getResponse($response, array $params)
	{
		
		
		$function = '';
		if (!empty($params['function'])) {
			$function = $params['function'];
		}
		
		$newResponse = array('response' => $response, 'params' => $params);
		
		if (isset($response['errors'])) {
			$newResponse['error']['message'] = $this->getErrorMessage($response['errors']);
		}
		
		if ($function == 'getRates') {
		
			$newResponse = $this->getRatesResponse($response, $params);
		}
		
		
		return $newResponse;
	}

	protected function getErrorMessage($error)
	{
		if (isset($error['object_id']) || isset($error['results']) || isset($error['tracking_number'])) {
			return '';
		}

		if (isset($error['__all__'])) {
			$error = $error['__all__'];
		}
		
		if (is_string($error)) {
			return $error;
		}

		if (isset($error['text'])) {
			return $error['text'];
		}

		$message = '';
		if (is_array($error)) {
			foreach ($error as $key => $val) {
				if (!empty($message)) {
					$message .= "\n";
				}

				if (!is_numeric($key)) {
					$message .= $key . ' -> ';
				}

				$message .= $this->getErrorMessage($val);
			}	
		}
		
		return trim($message);
	}

	// Abstract methods
	public abstract function getIntegrationFormFields();

}

endif;