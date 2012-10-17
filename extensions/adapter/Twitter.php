<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Twitter extends Consumer {

	const PROFILE_BASE = 'https://twitter.com/account/redirect_by_id?id=';

	public static function getProfileUrl(array $data) {
		$url = self::PROFILE_BASE;
		if(isset($data['uid']) && !empty($data['uid'])) {
			$url.=$data['uid'];
		}
		return $url;
	}

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
				$users = $this->getUsers($uids);
				foreach($users as $uid => $user) {
					$friends[$uid] = $user;
				}
			}
		}
		return $friends;
	}

	protected function _formatUser($user) {
		if(is_object($user)) {
			$data = array(
				'uid' => $user->id,
				'username' => $user->name,
				'first_name' => null,
				'last_name' => null,
				'birthday' => null,
				'gender' => null,
				'picture' => $user->profile_image_url
			);
		} else if(is_array($user) && !empty($user)) {
			$data = array(
				'uid' => !empty($user['id']) ? $user['id'] : null,
				'username' => !empty($user['name']) ? $user['name'] : null,
				'first_name' => null,
				'last_name' => null,
				'birthday' => null,
				'gender' => null,
				'picture' => !empty($user['profile_image_url']) ? $user['profile_image_url'] : null
			);
		} else {
			$data = array();
		}
		return $data;
	}

	public function getUsers($users) {
		$users = !(is_array($users)) ? array($users) : $users;
		$data = $this->get('1/users/lookup.json', array('user_id' => implode(',', $users)));
		$users = array();
		if(!empty($data)) {
			foreach($data as $user) {
				$id = false;
				if(is_array($user) && !empty($user['id'])) {
					$id = $user['id'];
				} else if(is_object($user) && !empty($user->id)) {
					$id = $user->id;
				}
				if($id) {
					$users[$id] = $this->_formatUser($user);
				}
			}
		}
		return $users;
	}

	public function basicInfos() {
		$me = $this->me();
		$token = $this->token();
		if(is_array($me)) {
			$me['uid'] = empty($me['uid']) ? null : $me['uid'];
			$me['username'] = empty($me['username']) ? null : $me['username'];
			$me['picture'] = empty($me['picture']) ? null : $me['picture'];
		} else if(is_object($me)) {
			$cp = $me;
			$me = array();
			$me['uid'] = empty($cp->uid) ? null : $cp->uid;
			$me['username'] = empty($cp->username) ? null : $cp->username;
			$me['picture'] = empty($cp->picture) ? null : $cp->picture;
		} else {
			$me = array('uid' => null, 'picture' => null, 'username' => null);
		}
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