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

namespace EnviaYa\WooCommerce\Logger;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\\LoggerInstance')):

class LoggerInstance
{
	private static $instances = array();

	public static function &getInstance($id)
	{
		if (empty(self::$instances[$id])) {
			self::$instances[$id] = new Logger($id);
		}

		return self::$instances[$id];
	}
}

endif;