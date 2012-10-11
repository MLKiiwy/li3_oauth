<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Basic extends Consumer {

	public function me() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$u = $this->getUsers($this->userId());
		$u = (count($u) == 1 ) ? $u[$this->userId()] : array();
		return $u;
	}

	public function userId() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$token = $this->token();
		return $token['user_id'];
	}

	public function friends(array $options = array()) {
		return array();
	}

	public function getUsers($users) {
		return array();
	}

	public function basicInfos() {
		$me = $this->me();
		$token = $this->token();
		$data = array(
			'uid' => $me['uid'],
			'access_token' => ($token) ? $token['oauth_token'] : '', 
			'oauth_secret' => ($token) ? $token['oauth_token_secret'] : '', 
			'username' => $me['username'], 
			'first_name' => null, 
			'last_name' => null, 
			'picture' => $me['picture'], 
		);
		return $data;
	}

	public function checkTokenValidity($uid = null) {
		if(!$this->isAuthentificated()) {
			return false;
		}
		// TODO
		return true;
	}

	public function permissions() {
		return array();
	}

}

?>