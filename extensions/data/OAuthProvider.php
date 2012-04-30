<?php

namespace li3_oauth\extensions\data;

use \lithium\core\Environment;

use Exception;

class OAuthProvider extends \lithium\core\Adaptable {

	const SERVICE_NAME_FACEBOOK = 'facebook';
	const SERVICE_NAME_TWITTER = 'twitter';

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
						'adapter' => 'Consumer',
						'service' => 'oauth2',
						'credentials' => array(
							'development' => array(),
							'production' => array(),
						),
						'scheme' => 'https',
						'port' => '443',
						'host' => 'graph.facebook.com',
						'secondary_host' => 'www.facebook.com',
						'scope' => 'email',
					);
					$required = array('credentials');
				break;

				case self::SERVICE_NAME_TWITTER:
					$defaults = array(
						'adapter' => 'Consumer',
						'service' => 'oauth2',
						'scheme' => 'https',
						'credentials' => array(
							'development' => array(),
							'production' => array(),
						),
						'port' => '443',
						'host' => 'graph.facebook.com',
						'secondary_host' => 'www.facebook.com',
						'scope' => 'email',
					);
					$required = array('credentials');
				break;
			}
			$configurations[$service] += $defaults;
			foreach ($required as $v) {
				if (!isset($configurations[$service][$v]) || empty($configurations[$service][$v])) {
					throw new Exception('The parameter : '.$v.' is required in configuration of '.$service);
				}
				if ($v == 'credentials') {
					$env = Environment::get();
					if (!isset($configurations[$service][$v][$env]) || empty($configurations[$service][$v][$env])) {
						throw new Exception('No credentials set for : '.$service.' in environment '.$env);
					}
				}
			}
		}
		parent::config($configurations);
	}
}

?>
