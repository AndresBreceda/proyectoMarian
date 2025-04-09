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

namespace EnviaYa\WooCommerce\AutoLoader;

if (!class_exists(__NAMESPACE__ . '\\AutoLoader')):

class AutoLoader
{
	protected $namespace;
	protected $includePath;

	public function __construct($includePath, $namespace)
	{
		
		$this->namespace = trim($namespace, '\\') . '\\';
		$this->includePath = $includePath;
		
	}

	public function autoload($class)
	{
		
		if (strpos($class, $this->namespace) === 0) {
			$filePath = $this->includePath . '/' . str_replace('\\', '/', substr($class, strlen($this->namespace))) . '.php';
			
			if (file_exists($filePath)) {
				include_once($filePath);
			}
		}
	}

	public function register()
	{
		
		
		spl_autoload_register(array($this, 'autoload'),true);
	}
}

endif;