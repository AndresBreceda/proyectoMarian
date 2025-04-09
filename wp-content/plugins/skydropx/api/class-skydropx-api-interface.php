<?php
/**
 * Class API Interface
 *
 * @package  Skydropx\Api
 */
namespace Skydropx\Api;

interface Skydropx_api_interface {
	public function get( string $endpoint, array $body = array(), array $headers = array());
	public function post( string $endpoint, array $body = array(), array $headers = array());
	public function put( string $endpoint, array $body = array(), array $headers = array());
	public function delete( string $endpoint, array $body = array(), array $headers = array());
}
