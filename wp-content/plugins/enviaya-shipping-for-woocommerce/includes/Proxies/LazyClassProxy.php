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



namespace EnviaYa\Proxies;

if (!class_exists(__NAMESPACE__ . '\\LazyClassProxy')):

class LazyClassProxy
{
	protected $instance;
	protected $className;

	public function __construct($className, &$instance = null)
	{
		$this->className = $className;
		$this->instance = &$instance;
	}

	public function &getInstance()
	{
		if (!is_object($this->instance)) {
			$this->instance = $this->createInstance();
		}

		return $this->instance;
	}

	public function __call($methodName, $arguments)
	{
		$value = null;
		$instance = &$this->getInstance();
		if (is_object($instance) && method_exists($instance, $methodName)) {
			$value = call_user_func_array(array($instance, $methodName), $arguments);
		}

		return $value;
	}

	public function __get($name)
	{
		$value = null;
		$instance = &$this->getInstance();
		if (is_object($instance)) {
			$value = $instance->$name;
		}

		return $value;
	}

	public function __set($name, $value)
	{
		$instance = &$this->getInstance();
		if (is_object($instance)) {
			$instance->$name = $value;
		}
	}

	public function __isset($name)
	{
		$instance = &$this->getInstance();
		return is_object($instance) && isset($instance->$name);
	}

	public function __unset($name)
	{
		$instance = &$this->getInstance();
		if (is_object($instance) && isset($instance->$name)) {
			unset($instance->$name);
		}
	}

	protected function &createInstance()
	{
		$instance = null;
		if (class_exists($this->className)) {
			$className = $this->className;
			$instance = new $className();
		}

		return $instance;
	}
}

endif;