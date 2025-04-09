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

if (!class_exists(__NAMESPACE__ . '\\Rule')):

class Rule
{
	protected $conditions;

	public function __construct()
	{
		$this->conditions = array();
	}

	public function addCondition(AbstractCondition $condition)
	{
		$this->conditions[$condition->getType()] = $condition;
	}

	public function match(array $items, array $rules)
	{
		$matchResults = array();

		foreach ($rules as $ruleName => $params) {
			if (!empty($params) && count($params) >= 2) {
				$conditionType = $params[0];
				if (isset($this->conditions[$conditionType])) {
					$condition = &$this->conditions[$conditionType];
					$condition->reset();
					
					if (count($params) > 2) {
						$condition->setItemsOperator($params[2]);
					}
					if (count($params) > 3) {
						$condition->setOptionsOperator($params[3]);
					}
					
					$options = &$params[1];
					$matchResults[$ruleName] = $condition->match($items, $options);
				}
			}
		}

		return $matchResults;
	}
}

endif;