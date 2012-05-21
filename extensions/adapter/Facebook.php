<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Facebook extends Consumer {

	const GRAPH_URL = 'https://graph.facebook.com/';

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

	public function basicInfos() {
		$data = parent::basicInfos();
		$me = $this->me();
		$data['username'] = $me->username;
		$data['first_name'] = $me->first_name; 
		$data['last_name'] = $me->last_name;
		$data['picture'] = Facebook::GRAPH_URL . $me->id . '/picture';
		return $data;
	}
}

?>
