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

if (!class_exists(__NAMESPACE__ . '\\AbstractItemCondition')):

abstract class AbstractItemCondition extends AbstractCondition
{
	protected $optionsOperator;
	protected $itemsOperator;

	public function match(array $items, array $options)
	{
		if (empty($items) || empty($options)) {
			return false;
		}
	
		$numberOfItems = count($items);
		$numberOfMatches = 0;
	
		foreach ($items as $item) {
			if (isset($item['data']) && is_object($item['data']) && $this->matchItem($item, $options)) {
				$numberOfMatches++;
	
				if ($this->itemsOperator == 'or') {
					break;
				}
			}
		}
	
		return $this->isMatched($this->itemsOperator, $numberOfMatches, $numberOfItems);
	}
	
	protected abstract function matchItem(array $item, array $options);
}

endif;