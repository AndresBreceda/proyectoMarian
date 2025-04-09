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

namespace EnviaYa\WooCommerce\Utils;

defined('ABSPATH') || exit; // Exit if accessed directly

if (!class_exists(__NAMESPACE__ . '\\PageDetector')):

class PageDetector
{
	public function is_cart()
	{
		
		$is_cart = false;
		if (function_exists('wc_get_cart_url')) {
			$is_cart = $this->isRequestForUrl(wc_get_cart_url());
		}

		return $is_cart;
	}

	public function is_checkout()
	{
		$is_checkout = false;
		if (function_exists('wc_get_checkout_url')) {
			$is_checkout = $this->isRequestForUrl(wc_get_checkout_url());
		}

		return $is_checkout;
	}

	private function isRequestForUrl($url)
	{
		$isRequestForUrl = false;
		$url = rtrim($url, '/');

		if (defined('DOING_AJAX') && DOING_AJAX) {
			if (isset($_SERVER['HTTP_REFERER']) && $url == rtrim(preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER']), '/')) {
				$isRequestForUrl = true;
			}	
		} else {
			$requestedUrl = sprintf('%s://%s%s',
				(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http'),
				isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
				isset($_SERVER['REQUEST_URI']) ? preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']) : ''
			);

			$requestedUrl = rtrim($requestedUrl, '/');
			
			if ($url == $requestedUrl) {
				$isRequestForUrl = true;
			}
		}
		
		return $isRequestForUrl;
	}
}

endif;