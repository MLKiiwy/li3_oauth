<?php

namespace li3_oauth\extensions\data;

use \lithium\core\Environment;

use Exception;

class OAuthProvider extends \lithium\core\Adaptable {

	const SERVICE_NAME_FACEBOOK = 'facebook';
	const SERVICE_NAME_TWITTER = 'twitter';
	const SERVICE_NAME_GMAIL = 'gmail';

	/**
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * @var string
	 */
	protected static $_adapters = 'adapter';

	public static function __callStatic($method, $params) {
		$name = array_shift($params);
		return static::adapter($name)->invokeMethod($method, $params);
	}

	public static function config($configurations = array()) {
		foreach ($configurations as $service => $config) {
			switch ($service) {
				case self::SERVICE_NAME_FACEBOOK:
					$defaults = array(
						'adapter' => 'Facebook',
						'service' => 'oauth2',
						'credentials' => array(
							'development' => array(),
							'production' => array(),
						),
						'scheme' => 'https',
						'port' => '443',
						'host' => 'graph.facebook.com',
						'authorize_host' => 'www.facebook.com',
						'scope' => 'email',
						'long_life_access' => false
					);
					$required = array('credentials');
				break;

				case self::SERVICE_NAME_GMAIL:
					$defaults = array(
						'adapter' => 'Gmail',
						'service' => 'oauth2',
						'credentials' => array(
							'development' => array(),
							'production' => array(),
						),
						'scheme' => 'https',
						'port' => '443',
						'host' => 'www.googleapis.com/oauth2',
						'authorize_host' => 'accounts.google.com/o/oauth2',
						'token_host' => 'accounts.google.com/o/oauth2',
						'authenticate' => '/auth',
						'authorize' => '/auth',
						'access' => '/token',
						'authenticate' => '/auth',
						'request' => '/oauth/request_token',
						'logout' => '/oauth/request_token',
						'scope' => '',
						'long_life_access' => false,
						'token_access_method' => 'POST'
					);
					$required = array('credentials');
				break;

				case self::SERVICE_NAME_TWITTER:
					$defaults = array(
						'adapter' => 'Twitter',
						'service' => 'oauth',
						'credentials' => array(
							'development' => array(),
							'production' => array(),
						),
						'host' => 'api.twitter.com',
						'scope' => 'email',
						'scheme' => 'https',
						'port' => '443',
					);
					$required = array('credentials');
				break;

				default:
					$defaults = array(
						'adapter' => 'Basic',
						'service' => 'oauth',
						'credentials' => array(
							'development' => array(),
							'production' => array(),
						),
						'scope' => '',
						'scheme' => 'http',
						'port' => '80',
					);

					$required = array('credentials', 'host');
				break;
			}
			$configurations[$service] += $defaults;
			$configurations[$service]['name'] = $service;	// Setting name
			foreach ($required as $v) {
				if (!isset($configurations[$service][$v]) || empty($configurations[$service][$v])) {
					throw new Exception('The parameter : '.$v.' is required in configuration of '.$service);
				}
				/*
				if ($v == 'credentials') {
					$env = Environment::get();
					if (!isset($configurations[$service][$v][$env]) || empty($configurations[$service][$v][$env])) {
						throw new Exception('No credentials set for : '.$service.' in environment '.$env);
					}
				}
				*/
			}
		}
		parent::config($configurations);
	}
}

?>
