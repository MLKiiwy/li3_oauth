<?php

namespace li3_oauth\extensions\adapter;

use Exception;

class Facebook extends Consumer {

	const GRAPH_URL = 'https://graph.facebook.com/';
	const WWW_URL = 'https://www.facebook.com/';
	const PROFILE_BASE = 'http://www.facebook.com';

	const LIKE_STATUS_LIKE = 2;
	const LIKE_STATUS_NOT_LIKED = 1;
	const LIKE_STATUS_UNKNOW = 0;

	public static function getAvatarUrl($uid, $width = false) {
		$url = Facebook::GRAPH_URL . $uid . '/picture';
		if (!empty($width)) {
			$url .= '?width=' . $width . '&amp;height=' . $width;
		}
		return $url;
	}

	public static function getProfileUrl(array $data) {
		$url = self::PROFILE_BASE;
		if(isset($data['uid']) && !empty($data['uid'])) {
			$url.="/profile.php?id=".$data['uid'];
		}
		return $url;
	}

	public function me() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$me = $this->get('/me');
		if(empty($me) || empty($me['id'])) {
			return false;
		}
		$data['uid'] = $me['id'];
		$data['username'] = !empty($me['name']) ? $me['name'] : '';
		$data['first_name'] = !empty($me['first_name']) ? $me['first_name'] : '';
		$data['last_name'] = !empty($me['last_name']) ? $me['last_name'] : '';
		$data['picture'] = self::getAvatarUrl($me['id']);
		$data['email'] = !empty($me['email']) ? $me['email'] : '';
		if(isset($me['gender']) && !empty($me['gender'])) {
			$data['gender'] = $me['gender'];
		}
		if(isset($me['birthday']) && !empty($me['birthday'])) {
			$d = explode('/', $me['birthday']);
			if (!empty($d[2])) {
				$data['birthday'] = $d[2] . '-' . $d[0] . '-' . $d[1];
			}
		}
		return $data;
	}

	public function userId() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$token = $this->token();
		if(empty($token['uid'])) {
			$me = $this->me();
			$token['uid'] = $me['uid'];
			$this->token($token);
		}
		return $token['uid'];
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
		if(empty($data['data'])) {
			return array();
		}
		$friends = array();
		foreach($data['data'] as $friend) {
			$friends[$friend['id']] = array('username' => $friend['name'], 'uid' => $friend['id']);
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
		if($data) {
			$user = array(
				'uid' => $data['id'],
				'username' => $data['name'],
				'first_name' => $data['first_name'],
				'last_name' => $data['last_name'],
				'gender' => null,
				'picture' => Facebook::GRAPH_URL . $data['id'] . '/picture',
			);
			if(isset($data['gender']) && !empty($data['gender'])) {
				$user['gender'] = $data['gender'];
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
		$me = $this->me();
		$token = $this->token();
		$me['access_token'] = $token['access_token'];
		$me['oauth_secret'] = '';
		$me['date_token_expiration'] = $token['date_token_expiration'];
		return $me;
	}

	public function addOpenGraph($action, $params) {
		return $this->post('/me/' . $action, $params);
	}

	public function updateOpengraph($id, $params) {
		return $this->post($id, $params);
	}

	public function removeOpenGraph($idAction) {
		return $this->delete($idAction);
	}

	public function checkTokenValidity($uid = null) {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$me = $this->get('/me');
		if(empty($me) || empty($me['id'])) {
			// Destroy session
			$this->clean();
			return false;
		}
		if(!empty($uid) && $uid !== $me['id']) {
			$this->clean();
			return false;
		}
		return true;
	}

	public function getLongLiveToken() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		$token = $this->token();
		try {
			$data = $this->_requestToken('access', array('params' => array('fb_exchange_token' => $token['access_token'], 'grant_type' => 'fb_exchange_token')));
			$this->token($data);
			return true;
		} catch (Exception $e) {
			throw new Exception('cannot get long live access token');
		}
		return false;
	}

	public function desauthorize() {
		if(!$this->isAuthentificated()) {
			return false;
		}
		return $this->delete('/me/permissions');
	}

	public function permissions() {
		if(!$this->isAuthentificated()) {
			return array();
		}
		$permissions = $this->get('/me/permissions');
		return (!empty($permissions['data'][0])) ? $permissions['data'][0] : array();
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

	protected static function _base64UrlDecode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}

	protected function _getSignedRequest() {
		global $_REQUEST, $_COOKIE;
		$signedRequest = array();
		$cookieName = 'fbsr_' . $this->_config['client_id'];
		if (!empty($_REQUEST['signed_request'])) {
			$signedRequest = $this->_parseSignedRequest($_REQUEST['signed_request']);
		} else if (!empty($_COOKIE[$cookieName])) {
			$signedRequest = $this->_parseSignedRequest($_COOKIE[$cookieName]);
		}
		return $signedRequest;
	}

	protected function _parseSignedRequest($signed_request) {
		list($encoded_sig, $payload) = explode('.', $signed_request, 2);

		// decode the data
		$sig = self::_base64UrlDecode($encoded_sig);
		$data = json_decode(self::_base64UrlDecode($payload), true);

		if (empty($data['algorithm']) || strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
			throw new Exception('Unknown algorithm. Expected HMAC-SHA256');
		}

		// check sig
		$expected_sig = hash_hmac('sha256', $payload, $this->_config['client_secret'], $raw = true);
		if ($sig !== $expected_sig) {
			throw new Exception('Bad Signed JSON signature!');
		}

		return $data;
	}

	public function isLiked($pageId) {
		if(!$this->isAuthentificated()) {
			return self::LIKE_STATUS_UNKNOW;
		}
		$token = $this->token();
		$url = "https://api.facebook.com/method/pages.isFan?format=json&access_token=" . $token['access_token'];
		$url.="&page_id=" . $pageId;

		try {
			$content = @file_get_contents($url);
			if(!empty($content)) {
				$data = json_decode($content, true);
				if($data === true ) {
					return self::LIKE_STATUS_LIKE;
				} else if($data === false) {
					return self::LIKE_STATUS_NOT_LIKED;
				}
			}
		} catch(Exception $e) {
			// Ignore
		}

		return self::LIKE_STATUS_UNKNOW;
	}

	// TODO Remove

	public function getLoginStatusUrl($params=array()) {
		$defaults = array(
			'api_key' => $this->_config['client_id'],
			'no_session' => null,
			'no_user' => null,
			'ok_session' => null,
			'session_version' => 3,
		);
		$params += $defaults;
		$this->_checkRequired($params, array('no_session', 'no_user', 'ok_session'));
		$url = self::WWW_URL . 'extern/login_status.php?' . http_build_query($params, null, '&');
	}
}

?>
