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

namespace EnviaYa\WooCommerce\Condition;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\AbstractCondition')):

abstract class AbstractCondition
{
	protected $optionsOperator;
	protected $itemsOperator;

	public function __construct()
	{
		$this->reset();
	}

	public function reset()
	{
		$this->optionsOperator = 'and';
		$this->itemsOperator = 'and';
	}

	public function setOptionsOperator($optionsOperator)
	{
		$this->optionsOperator = $this->parseOperator($optionsOperator);
	}

	public function setItemsOperator($itemsOperator)
	{
		$this->itemsOperator = $this->parseOperator($itemsOperator);
	}
	
	protected function isMatched($operator, $numberOfMatches, $numberOfItems)
	{
		$isMatched = false;
	
		if ($operator == 'or' && $numberOfMatches > 0) {
			$isMatched = true;
		} else if ($numberOfMatches == $numberOfItems) {
			$isMatched = true;
		}
			
		return $isMatched;
	}

	private function parseOperator($operator)
	{
		return strtolower($operator) == 'or' ? 'or' : 'and';
	}
	
	public abstract function getType();
	public abstract function getAvailableOptions();
	public abstract function match(array $items, array $options);
}

endif;