<?php

namespace li3_oauth\controllers;

use \li3_oauth\models\Consumer;
use \lithium\storage\Session;

class TweetController extends \lithium\action\Controller {

	protected function _init() {
		parent::_init();
		Consumer::config(array(
			'host' => 'api.twitter.com',
			'oauth_consumer_key' => 'your_consumer_key',
			'oauth_consumer_secret' => 'your_consumer_secret'
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
		Session::delete('oauth.request');
		Session::delete('oauth.access');
		$token = Consumer::token('request', array('params' => array(
			'oauth_callback' => 'http://local.moodpik.com/tweet/success'
		)));
		if (is_string($token)) {
			return $token;
		}
		Session::write('oauth.request', $token);
		$this->redirect(Consumer::authorize($token));
	}
	
	public function success() {
		$token = $this->request->query;
		unset($token['url']);
		$token += Session::read('oauth.request');
		$access = Consumer::token('access', compact('token'));
		Session::write('oauth.access', $access);
		$this->redirect('Tweet::feed');
	}
	

	public function feed() {
		$token = Session::read('oauth.access');
		$items = Consumer::get('/1/statuses/home_timeline.json', array(), compact('token'));
		echo $items;
		exit;
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


	public function login() {
		Session::delete('oauth.request');
		Session::delete('oauth.access');
		$token = Consumer::token('request', array('params' => array(
			'oauth_callback' => 'http://local.moodpik.com/tweet/success'
		)));
		Session::write('oauth.request', $token);
		if (empty($token)) {
			$this->redirect('Tweet::authorize');
		}
		$this->redirect(Consumer::authenticate($token));
	}
}

?>