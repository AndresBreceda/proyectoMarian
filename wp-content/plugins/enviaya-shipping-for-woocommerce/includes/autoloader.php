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

require_once(__DIR__ . '/AutoLoader/AutoLoader.php');

(new \EnviaYa\WooCommerce\AutoLoader\AutoLoader(__DIR__, 'EnviaYa\\WooCommerce\\'))->register();
(new \EnviaYa\WooCommerce\AutoLoader\AutoLoader(__DIR__, 'EnviaYa\\'))->register();