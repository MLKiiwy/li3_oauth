<?php

namespace li3_oauth\models;

use \lithium\storage\Session;

class TwitterApi extends Consumer {

	public static function config($params = array()) {
		$defaults = array(
			'service' => 'oauth2',
			'scheme' => 'https',
			'port' => '443',
			'host' => 'api.twitter.com',
			'client_id' => null,
			'scope' => 'email',
			'client_secret' => null,
			'success' => null,
		);
		$required = array('client_id', 'client_secret', 'success');
		$params += $defaults;
		foreach($required as $v) {
			if(!isset($params[$v]) || empty($params[$v])) {
				throw new Exception('The parameter : '.$v.' is required in configuration of '.__CLASS__);
			}
		}
		parent::config($params);
	}
}

?>
