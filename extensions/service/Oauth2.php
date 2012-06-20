<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_oauth\extensions\service;

use li3_oauth\models\Consumer;

/**
 * Oauth service class for handling requests/response to consumers and from providers
 *
 *
 */
class Oauth2 extends \lithium\net\http\Service {

	protected $_autoConfig = array('classes' => 'merge');

	/**
	 * Fully-namespaced class references
	 *
	 * @var array
	 */
	protected $_classes = array(
		'media'    => '\lithium\net\http\Media',
		'request'  => '\lithium\net\http\Request',
		'response' => '\lithium\net\http\Response',
		'socket'   => '\lithium\net\socket\Context',
	);

	/**
	 * Constructor
	 *
	 * @param array $config
	 *              - host: the oauth domain
	 *              - oauth_consumer_secret: secret from oauth service provider
	 *              - oauth_consumer_key: key from oauth service provider
	 *              - authorize: path to authorize  url
	 *              - request_token: path to request token url
	 *              - access_token: path to access token url
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'scheme' => 'http',
			'scope' => 'email',
			'host' => 'localhost',
			'access' => '/oauth/access_token',
			'authorize_host' => '',
			'token_host' => '',
			'proxy' => false,
			'port' => 80,
			'authorize' => '/dialog/oauth',
			'authenticate' => '/dialog/oauth',
			'request' => '/oauth/request_token',
			'logout' => '/oauth/request_token',
			'client_id' => 'key',
			'client_secret' => 'secret',
			'restponse_type' => 'code',
			'state' => '',
			'grant_type' => '',
			'next' => '',
			'logout' => '/logout.php',
			'token_access_method' => 'GET'
		);
		$config += $defaults;

		parent::__construct($config);
	}

	/**
	 * If a key is set returns the value of that key
	 * Without a key it will return config array
	 *
	 * @param string $key eg `oauth_consumer_key`
	 * @return void
	 */
	public function config($key = null) {
		if (isset($this->_config[$key])) {
			return $this->_config[$key];
		}
		if ($key !== null) {
			return $key;
		}
		return $this->_config;
	}

	/**
	 * Requests a token of a particular type
	 *
	 * @param string $query
	 * @return array parameters sent from the response body
	 */
	public function token($type, array $options = array()) {
		$defaults = array('params' => array(
			'client_id', 'client_secret'
		));
		$options = array_merge_recursive($options, $defaults);
		$this->_parseParams($options);
		$url = $this->_config[$type];
		if(!empty($this->_config['token_host'])) {
			$options['host'] = $this->_config['token_host'];
		}
		if($this->_config['token_access_method'] == 'POST') {
			$result = parent::post($url, array(), $options);
		} else {
			$result = parent::get($url, array(), $options);
		}
		return $result;
	}

	/**
	 * Send request with the given options and data. The token should be part of the options.
	 *
	 * @param string $method
	 * @param string $path
	 * @param array $data encoded for the request
	 * @param array $options oauth parameters
	 *              - headers : send parameters in the header. (default: true)
	 *              - realm : the realm to authenticate. (default: app directory name)
	 * @return mixed the response from api call
	 */
	public function send($method, $path = null, $data = array(), array $options = array()) {
		
		self::_parseParams($options);
		
		$data += $options['params'];
		$defaults = array('headers' => true, 'realm' => basename(LITHIUM_APP_PATH));
		$url = $this->config($path);
	
		if(!isset($options['host']) || empty($options['host'])) {
			$options['host'] = $this->_config['proxy'] ?: $this->_config['host'];
		}

		$response = parent::send($method, $url, $data, $options);

		$hasToken = (strpos($response, 'access_token=') !== false);
		$isJson = (strpos($response, '{') === 0);
		if ($hasToken && !$isJson) {
			return $this->_decode($response);
		} else if($isJson) {
			return json_decode($response, true);
		}
		return $response;
	}

	/**
	 * A utility method to return an authorize or authenticate url for redirect
	 *
	 * @param string $url the url key for the required url
	 * @param array $options
	 *              - `token`: (array) adds the access_token to the query params
	 *              - `usePort`: (boolean) use the port in the signature base string
	 *              - `params`: (array) use these as additional parameters on the url
	 * @return string the full url
	 */
	public function url($url = null, array $options = array()) {
		$defaults = array(
			'token' => array('access_token' => false), 
			'usePort' => false, 
			'params' => array()
		);
		$options += $defaults;

		$secondaryHost = array('authorize', 'authenticate', 'logout');
		$host = $this->_config['host'];

		if (isset($this->_config['authorize_host']) && !empty($this->_config['authorize_host']) && in_array($url, $secondaryHost)) {
			$host = $this->_config['authorize_host'];
		}

		$url = $url ? $this->config($url) : null;

		self::_parseParams($options);
		
		$params = !empty($options['params'])? '?' . http_build_query($options['params']) : '';

		$base = $host;
		$base .= ($options['usePort']) ? ":{$this->_config['port']}" : null;
		return "{$this->_config['scheme']}://" . str_replace('//', '/', "{$base}/{$url}{$params}");
	}

	/**
	 * A utility method to return an authorize or authenticate url for redirect
	 *
	 * @param array $options contains the 'params' sub-array
	 * @return void
	 */
	protected function _parseParams(&$options) {
		$defaults = array('params' => array());
		$options += $defaults;

		if (isset($options['token']['access_token']) && $options['token']['access_token']) {
			$options['params']['access_token'] = $options['token']['access_token'];
		}

		if (isset($options['code']) && $options['code']) {
			$options['params']['code'] = $options['code'];
		}

		foreach($options['params'] as $key => $value) {

			if (isset($this->_config[$value]) && $this->_config[$value]) {
				$options['params'][$value] = $this->_config[$value];
				unset($options['params'][$key]);
			}
		}
	}

	/**
	 * Decodes the response body.
	 *
	 * @param string $query
	 * @return array parameters sent from the response body
	 */
	protected function _decode($query = null) {
		parse_str($query, $data);
		return $data;
	}
}

?>