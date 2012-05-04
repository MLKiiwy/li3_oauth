<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Facebook extends Consumer {

	public function __call($method, $params) {
		if(in_array($method, array('get', 'post', 'put', 'delete')) && $this->isAuthentificated()) {
			$token = $this->token();
			for($i=0; $i<3; $i++) {
				if(!isset($params[$i])) {
					$params[$i] = array();
				}
			}
			$params[2] += array('token' => $token);
		}
		return $this->_service->invokeMethod($method, $params);
	}

	public function me() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$token = $this->token();
		$data = $this->get('/me');
		return $data;
	}

	public function userId() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$me = $this->me();
		return $me['id'];
	}

	public function friends($userId = null) {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$userId = empty($userId) ? $this->userId() : $userId;
		$data = $this->get('friends/ids.json');
		return $data;
	}

}

?>
