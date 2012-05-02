<?php

namespace li3_oauth\extensions\adapter;

use \lithium\core\Environment;
use \lithium\storage\Session;

use Exception;

class Consumer extends \lithium\core\Object {

	const SESSION_PREFIX = 'li3_oauth';

	const SESSION_STATE_KEY = 'state';
	const SESSION_TOKEN_KEY = 'token';

	/**
	 * Holds an instance of the oauth service class
	 *
	 * @see \li3_oauth\extensions\services\Oauth
	 */
	protected $_service = null;

	protected $_token = null;

	protected static $_classes = array(
		'oauth' => '\li3_oauth\extensions\service\Oauth',
		'oauth2' => '\li3_oauth\extensions\service\Oauth2'
	);

	protected $_defaults = array(
		'service' => 'oauth',
	);

	public function __construct(array $config = array()) {

		$config += $this->_defaults;

		$this->_checkRequired($config, array('name'));

		// Getting lithium environnement for settings credentials
		$env = Environment::get();
		if(!isset($config['credentials']) || !isset($config['credentials'][$env]) || empty($config['credentials'][$env])) {
			throw new Exception('Credential for : '.$env.' not defined');
		}
		$credentials = $config['credentials'][$env];
		$config += $credentials;
		unset($config['credentials']);

		parent::__construct($config);

		// Create instance of service
		$this->_service = new static::$_classes[$this->_config['service']]($this->_config);

		// Load token
		$this->token();
	}

	public function token($token = null) {
		if(empty($token)) {
			// Read
			if(empty($this->_token)) {
				// Read session
				$sessionToken = $this->_sessionRead(self::SESSION_TOKEN_KEY);
				if(!empty($sessionToken)) {
					// Check this token
					// TODO
					$this->_token = $sessionToken;
				}
			}
			return $this->_token;
		}

		$this->_token = $token;

		// Store
		$this->_sessionWrite(self::SESSION_TOKEN_KEY, $this->_token);
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
	protected function _requestToken($type, array $options = array()) {
		return $this->_service->token($type, $options);
	}

	/**
	 * get url from remote authorization endpoint along with request params
	 *
	 * @param array $token
	 * @param array $options
	 * @return string
	 */
	public function authenticate(array $options = array()) {
		switch($this->_config['service']) {
			case 'oauth':
				
				$this->_checkRequired($options, array('callback'));

				// Ask for a request token
				$token = $this->_requestToken('request', array('params' => array('oauth_callback' => $options['callback'])));
				
				// Check the token
				if(!$token || is_string($token)) {
					// TODO !
					throw new Exception('no token given : '.$token);
				}

				// Save token
				$this->_sessionWrite(self::SESSION_STATE_KEY, $token);

				// Ok save the token 
				return $this->_service->url('authenticate', array('token' => $token));
			break;

			case 'oauth2':
				// Rename callback into redirect_uri
				$options = $this->_arrayRename($options, array('callback' => 'redirect_uri'));

				$defaults = array(
					'client_id' => $this->_config['client_id'],
					'redirect_uri' => false,
					'scope' => $this->_config['scope'],
					'state' => $this->_generateUniqueState(),
				);

				$options += $defaults;

				// Check required
				$this->_checkRequired($options, array('client_id', 'state', 'redirect_uri'));

				return $this->_service->url('authorize', array('params' => $options));
			break;
		}
	}

	public function isAuthentificated() {
		$token = $this->token();
		return !empty($token);
	}

	protected function _arrayRename($data, $rename) {
		foreach ($rename as $k => $v) {
			if(isset($data[$k])) {
				if(isset($data[$v])) {
					throw new Exception('cannot rename data in array : ' . $k . ' into ' . $v . ' is already set');
				}
				$data[$v] = $data[$k];
				unset($data[$k]);
			}
		}
		return $data;
	}

	/**
	 * get url from remote authenticated endpoint along with token
	 *
	 * @param array $token
	 * @param array $options
	 * @return string
	 */
	public function getAccessToken(array $options = array()) {

		$this->_checkRequired($options, array('request','callback'));

		switch($this->_config['service']) {
			case 'oauth':
				// Get data from query request
				$this->_checkRequired($options['request']->query, array('oauth_token', 'oauth_verifier'));

				// Check state
				if(!$this->_checkState($options['request']->query['oauth_token'])) {
					throw new Exception('state doesn\'t match');
				}

				$data = $this->_requestToken('access', array('token' => $options['request']->query));

				$this->token($data);

				return true;
			break;

			case 'oauth2':

				// Get data from query request
				$this->_checkRequired($options['request']->query, array('code', 'state'));

				// Check state
				if(!$this->_checkState($options['request']->query['state'])) {
					throw new Exception('state doesn\'t match');
				}

				// Getting the token
				try {
					$data = $this->_requestToken('access', array('code' => $options['request']->query['code'], 'params' => array('redirect_uri' => $options['callback'])));
				} catch (Exception $e) {
					throw new Exception('cannot get access token');
				}
				

				if(isset($data['error'])) {
					// Error
					d($data);
					throw new Exception($data['error']['type']." : ".$data['error']['message']);
				}

				// Erase state data
				$this->_clearState();

				$this->token($data);

				return true;
			break;
		}

	}

	protected function _checkRequired(array $options, array $keys) {
		foreach ($keys as $value) {
			if(!isset($options[$value]) || empty($options[$value])) {
				throw new Exception('Parameter '.$value.' is not set or empty');
			}
		}
	}

	protected function _generateUniqueState() {
		$state = md5(time() + rand(0,100));
		$this->_sessionWrite(self::SESSION_STATE_KEY, $state);
		return $state;
	}

	protected function _checkState($state) {
		$value = $this->_sessionRead(self::SESSION_STATE_KEY);
		return $value == $state;
	}

	protected function _clearState() {
		return $this->_sessionDelete(self::SESSION_STATE_KEY);
	}

	// Sessions saving

	protected function _getFullSessionKey($key) {
		return self::SESSION_PREFIX . '.' . $this->_config['name'] . '.' . $key;
	}

	protected function _sessionWrite($key, $value) {
		return Session::write($this->_getFullSessionKey($key), $value);
	}

	protected function _sessionRead($key) {
		return Session::read($this->_getFullSessionKey($key));
	}

	protected function _sessionDelete($key) {
		return Session::delete($this->_getFullSessionKey($key));
	}

	public function config($key) {
		return isset($this->_config[$key]) ? $this->_config($key) : false;
	}

	public function clean() {
		$this->_token = null;
		$this->_sessionDelete(self::SESSION_TOKEN_KEY);
	}
}

?>