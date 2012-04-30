<?php

namespace li3_oauth\extensions\adapter;

use \lithium\core\Environment;
use \lithium\storage\Session;

use Exception;

class Consumer extends \lithium\core\Object {

	const SESSION_STATE_KEY = 'li3_oauth.consumer.state';

	/**
	 * Holds an instance of the oauth service class
	 *
	 * @see \li3_oauth\extensions\services\Oauth
	 */
	protected $_service = null;

	protected $_config;

	protected $_token = null;

	protected static $_classes = array(
		'oauth' => '\li3_oauth\extensions\service\Oauth',
		'oauth2' => '\li3_oauth\extensions\service\Oauth2'
	);

	protected $_defaults = array(
		'service' => 'oauth',
	);

	public function __construct(array $config = array()) {
		$this->_config = $config;
		$this->_config += $this->_defaults;

		// Getting lithium environnement for settings credentials
		$env = Environment::get();
		if(!isset($this->_config['credentials']) || !isset($this->_config['credentials'][$env]) || empty($this->_config['credentials'][$env])) {
			throw new Exception('Credential for : '.$env.' not defined');
		}
		$credentials = $this->_config['credentials'][$env];
		$this->_config += $credentials;
		unset($this->_config['credentials']);

		parent::__construct($this->_config);

		// Create instance of service
		$this->_service = new static::$_classes[$this->_config['service']]($this->_config);
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
	public function authorize(array $options = array()) {
		$defaults = array(
			'client_id' => $this->_config['client_id'],
			'redirect_uri' => false,
			'scope' => $this->_config['scope'],
			'state' => $this->_generateUniqueString(),
			);
		$options += $defaults;
		// Save state value into user session
		$this->_saveState($options['state']);
		// Check required
		$this->_checkRequired($options, array('client_id', 'state', 'redirect_uri'));
		return $this->_service->url('authorize', array('params' => $options));
		
	}

	public function isAuthentificated() {
		return !empty($this->_token);
	}

	/**
	 * get url from remote authenticated endpoint along with token
	 *
	 * @param array $token
	 * @param array $options
	 * @return string
	 */
	public function authenticate(array $options = array()) {

		$defaults = array(
			'code' => false,
			'state' => false,
			);
		$options += $defaults;

		// Check required
		$this->_checkRequired($options, array('code', 'state'));

		// Check state
		if(!$this->_checkState($options['state'])) {
			throw new Exception('state doesn\'t match');
		}

		// Getting the token
		$data = $this->token('access', array('code' => $options['code']));
		$data = json_decode($data);
		
		if(isset($data['error'])) {
			// Error
			throw new Exception($data['error']['type']." : ".$data['error']['message']);
		}

		var_export($data);

		// TODO 

		// Erase state data
		$this->_clearState();

		$token = 'a';
		$this->setToken($token);

		return true;
	}

	protected function _checkRequired(array $options, array $keys) {
		foreach ($keys as $value) {
			if(!isset($options[$value]) || empty($options[$value])) {
				throw new Exception('Parameter '.$value.' is not set or empty');
			}
		}
	}

	protected function _generateUniqueString() {
		return md5(time() + rand(0,100));
	}

	protected function _saveState($state) {
		// Save into user session
		Session::write(self::SESSION_STATE_KEY, $state);
	}

	protected function _checkState($state) {
		$value = Session::read(self::SESSION_STATE_KEY);
		return $value == $state;
	}

	protected function _clearState() {
		Session::delete(self::SESSION_STATE_KEY);
	}

	protected function setToken($token) {
		$this->_token = $token;
	}

	/*
	public function store($key, $value) {
		return $this->_service->storage->write($key, $value);
	}

	public function fetch($key) {
		return $this->_service->storage->read($key);
	}

	public function delete($key) {
		return $this->_service->storage->remove($key);
	}
	*/

	public function config($key) {
		return isset($this->_config[$key]) ? $this->_config($key) : false;
	}
}

?>