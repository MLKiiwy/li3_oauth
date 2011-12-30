<?php

namespace li3_oauth\controllers;

use \li3_oauth\models\Consumer;
use \lithium\storage\Session;

class FacebookController extends \lithium\action\Controller {

	protected function _init() {
		parent::_init();
		Consumer::config(array(
			'service' => 'oauth2',
			'scheme' => 'https',
			'port' => '443',
			'host' => 'graph.facebook.com',
			'secondary_host' => 'www.facebook.com',
			'client_id' => 'your_client_id',
			'scope' => 'email,read_stream',
			'client_secret' => 'your_client_secret',
			'success' => 'http://localhost/facebook/success',
		));
	}

	public function index() {
		$message = null;
		$token = Session::read('oauth.access');

		if (empty($token) && !empty($this->request->query['oauth_token'])) {
			$this->redirect('Tweet::access');
		}
		if (empty($token)) {
			$this->redirect('Tweet::authorize');
		}
		if (!empty($this->request->data)) {
			$result = Consumer::post('/statuses/update.json',
				$this->request->data,
				compact('token')
			);
			$message = json_decode($result);
		}
		return compact('message');
	}

	public function authorize() {
		$url = Consumer::url('authorize', array('params' => array(
			'scope', 'client_id', 'redirect_uri' => Consumer::serviceConfig('success')
		)));
		$this->redirect($url);
	
	}

	public function success() {
		$code = $this->request->query['code'];
		$access = Consumer::token('access', compact('code') + array('params' => array(
			'redirect_uri' => Consumer::serviceConfig('success')
		)));
		Session::delete('oauth.access');
		Session::write('oauth.access', $access);
		$this->redirect('Facebook::feed');
	}

	public function feed() {
		if (!$token = Session::read('oauth.access')) {
			return $this->redirect('Facebook::authorize');
		}
		$items = Consumer::get('/me/feed', array(), compact('token'));
		return $items;
	}
	
	public function post() {
		$token = Session::read('oauth.access');
		$result = Consumer::post('/1/statuses/update.json',
			array('status' => 'Testing my status'),
			compact('token')
		);
		echo $result;
		exit;
	}

}

?>