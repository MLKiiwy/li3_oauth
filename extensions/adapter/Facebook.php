<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Facebook extends Consumer {

	public function me() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$data = $this->get('/me');
		return $data;
	}

	public function userId() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$token = $this->token();
		return $token['user_id'];
	}

	public function friends($userId = null) {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$userId = empty($userId) ? $this->userId() : $userId;
		$data = $this->get('1/friends/ids.json', array('user_id' => $userId));
		return $data;
	}

}

?>