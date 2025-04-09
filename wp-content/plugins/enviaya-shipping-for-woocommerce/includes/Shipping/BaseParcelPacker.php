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

if (!class_exists(__NAMESPACE__ . '\\BaseParcelPacker')):

class BaseParcelPacker
{
	protected $id;
	protected $logger;
	protected $boxes;
	protected $combineBoxes;
	protected $minLength;
	protected $minWidth;
	protected $minHeight;
	protected $minWeight;
	protected $weightAdjustmentPercent;
	protected $weightAdjustment;
	protected $weightUnit;
	protected $dimensionUnit;

	protected $useCubeDimensions;
	protected $defaultPackageType;
	protected $packageTypes;
	protected $itemParents;

	protected $parcels;

	public function __construct($id)
	{
		$this->id = $id;
		$this->boxes = array();
		$this->combineBoxes = false;
		$this->minLength = 0;
		$this->minWidth = 0;
		$this->minHeight = 0;
		$this->minWeight = 0;
		$this->weightAdjustmentPercent = 0;
		$this->weightAdjustment = 0;
		$this->weightUnit = get_option('woocommerce_weight_unit');
		$this->dimensionUnit = get_option('woocommerce_dimension_unit');
		$this->currency = get_option('woocommerce_currency');

		$this->useCubeDimensions = false;
		$this->packageTypes = array();
		$this->defaultPackageType = 'parcel';
		$this->itemParents = array();
		$this->parcels = array();

		$this->logger = &\EnviaYa\WooCommerce\Logger\LoggerInstance::getInstance($this->id);
	}

	protected function getCubeParcel(array $parcel)
	{
		if (!$this->useCubeDimensions || empty($parcel['combined'])) {
			return $parcel;
		}

		$this->logger->debug(__FILE__, __LINE__, "getCubeParcel");

		$cubeSideLength = round(pow($parcel['volume'], 1/3), 2);
		$parcel['length'] = $cubeSideLength;
		$parcel['width'] = $cubeSideLength;
		$parcel['height'] = $cubeSideLength;

		return $parcel;
	}

	public function setSettings(array $settings)
	{
		foreach ($settings as $key => $val) {
			if ($key == 'boxes') {
				$this->setBoxes($val);
			} else if (property_exists($this, $key)) {
				if ($val == 'yes') {
					$this->$key = true;
				} else if ($val == 'no') {
					$this->$key = false;
				} else if (is_bool($this->$key)) {
					$this->$key = filter_var($val, FILTER_VALIDATE_BOOLEAN);
				} else if (is_numeric($this->$key)) {
					$this->$key = $this->toNumber($val);
				} else {
					$this->$key = $val;
				}
			} else if ($key == 'weight_unit') {
				$this->weightUnit = $val;
			} else if ($key == 'dimension_unit') {
				$this->dimensionUnit = $val;
			}
		}
	}

	public function setPackageTypes(array $packageTypes)
	{
		$this->packageTypes = $packageTypes;

		if (!empty($this->packageTypes)) {
			$this->defaultPackageType = current(array_keys($this->packageTypes));
		} else {
			$this->defaultPackageType = 'parcel';
		}
	}

	public function pack(array $packageContents)
	{
		
		$this->logger->debug(__FILE__, __LINE__, "pack");

		$this->parcels = array();
		
		
		foreach ($packageContents as $itemId => $item) {
			
			$product = $item['data'];
			
			
			if (!is_object($product)) {
				$this->logger->debug(__FILE__, __LINE__, "Item is not an object, so skip it");
				continue;

			} else if (!$product->needs_shipping()) {
				$this->logger->debug(__FILE__, __LINE__, "Product does not need to be shipped, so skip it. Product id: " . $product->get_id() . ", type: " . $product->get_type() . ", name: " . $product->get_name());
				continue;

			} else if (isset($this->itemParents[$itemId])) {
				$this->logger->debug(__FILE__, __LINE__, "Product is a child of another product, so skip it. Product id: " . $product->get_id() . ", type: " . $product->get_type() . ", name: " . $product->get_name());

				continue;
			}
			
			$quantity = floatval($item['quantity']);
			
			$this->logger->debug(__FILE__, __LINE__, "Pack product #" . $product->get_id() . ", qty: " . $quantity);

			$this->maybePackProduct($item);
		}
		
		return $this->parcels;
	}

	protected function maybePackProduct($item)
	{	
		
		$product = $item['data'];
		

		$parcelItem = $this->toParcelItem($product);
		
		if (empty($item)) {
			return false;
		}
		
		$parcelItem['quantity'] = floatval($item['quantity']);


		$parcelItem['author_id'] = get_post_field( 'post_author', $product->get_id() );
		
		
		$this->packSingleItem($parcelItem);

		return true;
	}

	protected function packSingleItem(array $item)
	{
		$this->logger->debug(__FILE__, __LINE__, "Pack single item as a parcel");

		$parcel = array();
		$copyKeys = array('quantity','width', 'height', 'length', 'weight', 'value','name','author_id');
		foreach ($copyKeys as $key) {
			if (isset($item[$key])) {
				$parcel[$key] = $item[$key];
			}
		}
		
		
		$this->addParcel($parcel);
	}

	public function toParcelItem($product)
	{
		
		$this->logger->debug(__FILE__, __LINE__, "toParcelItem");

		if (!is_object($product)) {
			$this->logger->debug(__FILE__, __LINE__, "Invalid product");

			return array();
		}
	
		$productId = $product->get_id();

		$item = array();
		$item['id'] = $productId;
		$item['name'] = $product->get_name();
		$item['value'] = $this->toNumber($product->get_price());

		$item['length'] = $this->toNumber($product->get_length());
		if (empty($item['length'])) {
			$item['length'] = $this->toNumber(get_post_meta($productId, '_length', true));
		}

		$item['width'] = $this->toNumber($product->get_width());
		if (empty($item['width'])) {
			$item['width'] = $this->toNumber(get_post_meta($productId, '_width', true));
		}

		$item['height'] = $this->toNumber($product->get_height());
		if (empty($item['height'])) {
			$item['height'] = $this->toNumber(get_post_meta($productId, '_height', true));
		}

		$item['weight'] = $this->toNumber($product->get_weight());
		if (empty($item['weight'])) {
			$item['weight'] = $this->toNumber(get_post_meta($productId, '_weight', true));
		}

		$this->logger->debug(__FILE__, __LINE__, "Item: " . print_r($item, true));
		
		$item['weight'] = ($item['weight'] > 0) ? $item['weight'] : .020;
		$item['height'] = ($item['height'] > 0) ? $item['height'] : .020;
		$item['width'] = ($item['width'] > 0) ? $item['width'] : .020;
		$item['length'] = ($item['length'] > 0) ? $item['length'] : .020;

		return $item;
	}

	protected function addParcel(array $item)
	{
		
		$this->logger->debug(__FILE__, __LINE__, 'Add parcel to the pile');

		$parcel = array(
			'author_id' => $item['author_id'],
		);

		if( isset($item['length']) && isset($item['width']) && isset($item['height']) ){
			$parcel['length'] = $item['length'];
			$parcel['width'] = $item['width'];
			$parcel['height'] = $item['height'];
			$parcel['dimension_unit'] = $this->dimensionUnit;

		}
		
		if(isset($item['weight'])){
			$parcel['weight'] = $item['weight'];
			$parcel['weight_unit'] = $this->weightUnit;
		}

		if(!empty($parcel)){
			$parcel['quantity'] = $item['quantity'];
			$parcel['content'] = $item['name'];
			$parcel['value'] = $item['value'];
			$parcel['value_currency'] = $this->currency;


			$this->parcels[] =  $parcel;

		}else{
			$this->logger->debug(__FILE__, __LINE__, "Unable to add parcel: " . print_r($parcel, true));
		}

	}

	protected function toNumber($value)
	{
		$number = 0;
		$value = preg_replace('/[^\d\.]/i', '', $value);
		if (is_numeric($value)) {
			$number = floatval($value);
		}

		//$this->logger->debug(__FILE__, __LINE__, "value: $value -> $number");

		return round($number, 2);
	}


}

endif;
 