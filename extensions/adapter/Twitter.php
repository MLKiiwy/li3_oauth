<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Twitter extends Consumer {

	public function me() {
		$data = parent::me();
		if($data === false) {
			return array();
		}
		$u = $this->getUser($this->userId());
		return (count($u) == 1 ) ? $u[$this->userId()] : array();
	}

	public function userId() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$token = $this->token();
		return $token['user_id'];
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
		$data = $this->get('1/friends/ids.json', array('user_id' => $options['userId']));
		$friends = array();
		foreach($data->ids as $uid) {
			$friends[$uid] = array('uid' => $uid);
		}
		if($options['full']) {
			$blocs = array();
			$uids = array();
			$i = 0;
			foreach($data->ids as $uid) {
				$uids[] = $uid;
				if($i == 100) {
					$blocs[] = $uids;
					$uids = array();
					$i = 0;
				}
				$i++;
			}
			$blocs[] = $uids;
			foreach($blocs as $uids) {
				$users = $this->getUser($uids);
				foreach($users as $uid => $user) {
					$friends[$uid] = $user;
				}
			}
		}
		return $friends;
	}

	protected function _formatUser($user) {
		$data = array(
			'uid' => $user->id,
			'username' => $user->name,
			'first_name' => null,
			'last_name' => null,
			'birthday' => null,
			'gender' => null,
			'picture' => $user->profile_image_url
		);
		return $data;
	}

	public function getUser($users) {
		$users = !(is_array($users)) ? array($users) : $users;
		$data = $this->get('1/users/lookup.json', array('user_id' => implode(',', $users)));
		$users = array();
		if(!empty($data)) {
			foreach($data as $user) {
				$users[$user->id] = $this->_formatUser($user);
			}
		}
		return $users;
	}

	public function basicInfos() {
		$data = parent::basicInfos();
		$me = $this->me();
		$data['username'] = $me['username'];
		$data['picture'] = $me['picture'];
		return $data;
	}

}

?>