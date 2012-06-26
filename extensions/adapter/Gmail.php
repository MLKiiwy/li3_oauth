<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Gmail extends Consumer {

	const PROFILE_BASE = "https://plus.google.com/";

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
				$this->_sessionWrite(self::SESSION_STATE_KEY, $token['oauth_token']);

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
					'response_type' => 'code',
				);

				$options += $defaults;

				// Check required
				$this->_checkRequired($options, array('client_id', 'state', 'redirect_uri'));

				return $this->_service->url('authorize', array('params' => $options));
			break;
		}
	}

	protected function _requestToken($type, array $options = array()) {
		if(!isset($options['params']['grant_type'])) {
			$options['params']['grant_type'] = 'authorization_code';
		}
		return parent::_requestToken($type, $options);
	}

	public static function getProfileUrl(array $data) {
		$url = self::PROFILE_BASE;
		if(isset($data['uid']) && !empty($data['uid'])) {
			$url.=$data['uid'];
		}
		return $url;
	}

	public function basicInfos() {
		$me = $this->me();
		$token = $this->token();
		$me['access_token'] = $token['access_token'];
		$me['oauth_secret'] = '';
		$me['date_token_expiration'] = $token['date_token_expiration'];
		return $me;
	}

	public function me() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$me = $this->get('/v1/userinfo');
		if(empty($me) || !is_array($me)) {
			return false;
		}
		$data['uid'] = $me['id'];
		$data['username'] = $me['name'];
		$data['email'] = $me['email'];
		$data['first_name'] = $me['given_name'];
		$data['last_name'] = $me['family_name'];
		$data['picture'] = $me['picture'];
		$data['gender'] = $me['gender'];
		return $data;
	}

	public function userId() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		if(empty($this->_userId)) {
			$me = $this->me();
			$this->_userId = $me['uid'];
		}
		return $this->_userId;
	}

	public function friends(array $options = array()) {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$token = $this->token();
		$options =  array(
			'host' => 'www.google.com/m8/feeds',
			'port' => null
		);
		$data = $this->get('/contacts/default/full/', array('max-results' => 1000),$options);
		if($data) {
			$xml = simplexml_load_string($data);
			$friends = array();
			foreach($xml->entry as $entry) {
				$name = $entry->title->__toString();
				foreach($entry->xpath('gd:email') as $email) {
					$strMail = (string) $email['address'];
					$name = (empty($name)) ? $strMail : $name;
					$friend = array(
						'uid' => strtolower($strMail),
						'username' => $name,
						'first_name' => '',
						'last_name' => '',
						'gender' => null,
						'picture' => null,
					);
					$friends[$strMail] = $friend;
				}
			}
		}
		return $friends;
	}

	public function getUsers($users) {
		// TODO
		return array();
	}

	
	public function checkTokenValidity($uid = null) {
		if(!$this->isAuthentificated()) {
			return false;
		}
		// TODO
		return true;
	}
}

?>
