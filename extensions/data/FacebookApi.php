<?php

namespace li3_oauth\extensions\data;

use \lithium\core\Environment;

use ConfigException;

class FacebookApi extends \lithium\core\Adaptable {

	/**
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * @var string
	 */
	protected static $_adapters = 'adapter';

	public static function __callStatic($method, $params) {
		$name = Environment::get();
		return static::adapter($name)->invokeMethod($method, $params);
	}

	public static function config($configurations = array()) {
		foreach($configurations as $name => $config) {
			$defaults = array(
				'adapter' => 'Consumer',
				'service' => 'oauth2',
				'scheme' => 'https',
				'port' => '443',
				'host' => 'graph.facebook.com',
				'secondary_host' => 'www.facebook.com',
				'client_id' => null,
				'scope' => 'email',
				'client_secret' => null,
				'success' => null,
			);
			$required = array('client_id', 'client_secret', 'success');
			$configurations[$name] += $defaults;
			foreach($required as $v) {
				if(!isset($configurations[$name][$v]) || empty($configurations[$name][$v])) {
					throw new ConfigException('The parameter : '.$v.' is required in configuration of '.__CLASS__);
				}
			}
		}
		parent::config($configurations);
	}
}

?>
