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
		$data = parent::me();
		if($data === false) {
			return array();
		}
		$me = $this->get('/me');
		$data['uid'] = $me->id;
		$data['username'] = $me->username;
		$data['first_name'] = $me->first_name;
		$data['last_name'] = $me->last_name;
		$data['picture'] = Facebook::GRAPH_URL . $me->id . '/picture';
		if(isset($me['gender']) && !empty($me['gender'])) {
			$data['gender'] = $me['gender'];
		}
		if(isset($me['birthday']) && !empty($me['birthday'])) {
			$d = explode('/', $me['birthday']);
			$data['birthday'] = $d[2] . '-' . $d[0] . '-' . $d[1];
		}
		return $data;
	}

	public function userId() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$me = $this->me();
		return $me['id'];
	}

	public function friends(array $options = array()) {
		$defaults = array(
			'userId' => $this->userId(),
			'full' => false
		);
		$options += $defaults;
		if(!$this->isAuthentificated()) {
			return false;
		}
		$data = $this->get($options['userId'] . '/friends');
		$friends = array();
		foreach($data as $friend) {
			$friends[$friend->id] = array('username' => $friend->name, 'uid' => $friend->id);
		}
		if($options['full']) {
			foreach($friends as $k => $v) {
				$u = $this->getUsers($k);
				if(!empty($u)) {
					$friends[$k] = $u[$k];
				}
			}
		}
		return $friends;
	}

	public function _getUser($user) {
		$data = $this->get($user);
		$user = array();
		if($data && $data->type == 'user') {
			$user = array(
				'uid' => $data->id,
				'username' => $data->username,
				'first_name' => $data->first_name,
				'last_name' => $data->last_name,
				'gender' => null,
				'picture' => Facebook::GRAPH_URL . $data->id . '/picture',
			);
			if(isset($data->gender) && !empty($data->gender)) {
				$user['gender'] = $data->gender;
			}
		}
		return $user;
	}

	public function getUsers($users) {
		$users = !(is_array($users)) ? array($users) : $users;
		$ret = array();
		foreach($users as $idUser) {
			$ret[$idUser] = $this->_getUser($idUser);
		}
		return $ret;
	}

	public function basicInfos() {
		$data = parent::basicInfos();
		$me = $this->me();
		$data['username'] = $me['username'];
		$data['first_name'] = $me['first_name']; 
		$data['last_name'] = $me['last_name'];
		$data['picture'] = $me['picture'];
		return $data;
	}

	// -> Copy from sdk facebook
	// TODO : Check if lithium can do it itself ?

	public function getRemoveRequest() {
		$request = $this->_getSignedRequest();
		$ret = array();
		if($request && isset($request['user_id']) && !empty($request['user_id'])) {
			$ret = array('uid' => $request['user_id']);
		}
		return $ret;
	}

	protected function _getSignedRequest() {
		global $_REQUEST, $_COOKIE;
		$signedRequest = array();
		$cookieName = 'fbsr_' . $this->_config['client_id'];
		if (isset($_REQUEST['signed_request'])) {
			$signedRequest = $this->_parseSignedRequest($_REQUEST['signed_request']);
		} else if (isset($_COOKIE[$cookieName])) {
			$signedRequest = $this->_parseSignedRequest($_COOKIE[$cookieName]);
		}
		return $this->signedRequest;
	}

	protected function _parseSignedRequest($signed_request) {
		list($encoded_sig, $payload) = explode('.', $signed_request, 2);

		// decode the data
		$sig = self::base64UrlDecode($encoded_sig);
		$data = json_decode(self::base64UrlDecode($payload), true);

		if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
			throw new Exception('Unknown algorithm. Expected HMAC-SHA256');
		}

		// check sig
		$expected_sig = hash_hmac('sha256', $payload, $this->_config['client_secret'], $raw = true);
		if ($sig !== $expected_sig) {
			throw new Exception('Bad Signed JSON signature!');
		}

		return $data;
	}
}

?>
