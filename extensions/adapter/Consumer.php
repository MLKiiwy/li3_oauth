<?php

namespace li3_oauth\extensions\adapter;

use \lithium\core\Environment;

class Consumer extends \lithium\core\Object {

	/**
	 * Holds an instance of the oauth service class
	 *
	 * @see \li3_oauth\extensions\services\Oauth
	 */
	protected $_service = null;

	protected static $_classes = array(
		'oauth' => '\li3_oauth\extensions\service\Oauth',
		'oauth2' => '\li3_oauth\extensions\service\Oauth2'
	);

	protected $_defaults = array(
		'service' => 'oauth',
	);

	public function __construct(array $config = array()) {
		$config += $this->_defaults;

		// Getting lithium environnement for settings credentials
		$env = Environment::get();
		if(!isset($config['credentials']) || !isset($config['credentials'][$env]) || empty($config['credentials'][$env])) {
			throw new ConfigException('Credential for : '.$env.' not defined');
		}
		$credentials = $config['credentials'][$env];
		$config += $credentials;
		unset($config['credentials']);

		parent::__construct($config);

		// Create instance of service
		$this->_service = new static::$_classes[$config['service']]($config);
	}

	/**
	 * Magic method to pass through HTTP methods. i.e.`Consumer::post()`
	 *
	 * @param string $method
	 * @param string $params
	 * @return mixed
	 */
	public function __call($method, $params) {
		return $this->_service->invokeMethod($method, $params);
	}

	/**
	 * Signs and Sends a post request to the request token endpoint with optional params
	 *
	 * @param string $type the type of token to get. request|access
	 * @param array $options optional params for the request
	 *              - `method`: POST
	 *              - `oauth_signature_method`: HMAC-SHA1
	 * @return string
	 */
	public function token($type, array $options = array()) {
		return $this->_service->token($type, $options);
	}

	/**
	 * get url from remote authorization endpoint along with request params
	 *
	 * @param array $token
	 * @param array $options
	 * @return string
	 */
	public function authorize(array $token, array $options = array()) {
		return $this->_service->url('authorize', compact('token') + $options);
	}

	/**
	 * get url from remote authenticated endpoint along with token
	 *
	 * @param array $token
	 * @param array $options
	 * @return string
	 */
	public function authenticate(array $token, array $options = array()) {
		return $this->_service->url('authenticate', compact('token') + $options);
	}

	/**
	 * undocumented function
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function store($key, $value) {
		return $this->_service->storage->write($key, $value);
	}

	/**
	 * undocumented function
	 *
	 * @param string $key
	 * @return void
	 */
	public function fetch($key) {
		return $this->_service->storage->read($key);
	}

	/**
	 * undocumented function
	 *
	 * @param string $key
	 * @return void
	 */
	public function delete($key) {
		return $this->_service->storage->remove($key);
	}

	public function serviceConfig($key) {
		return $this->_service->config($key);
	}
}

?>