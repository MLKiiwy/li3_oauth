<?php

namespace li3_oauth\controllers;

use \lithium\core\Environment;
use \lithium\storage\Session;

use \li3_oauth\models\Consumer;

class TwitterController extends \lithium\action\Controller {
	protected function _init() {
		parent::_init();
		Consumer::config(array(
			'host' => 'api.twitter.com',
			'oauth_consumer_key' => Environment::get('oauth.twitter.consumer_key'),
			'oauth_consumer_secret' => Environment::get('oauth.twitter.consumer_secret')
		));
	}

	public function index() {
		$message = null;
		$token = Session::read('oauth.access');

		if (empty($token) && !empty($this->request->query['oauth_token'])) {
			$this->redirect('Twitter::access');
		}
		if (empty($token)) {
			$this->redirect('Twitter::authorize');
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
			'oauth_callback' => Environment::get('oauth.twitter.callback.success')
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

		$this->redirect('/');
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
			'oauth_callback' => Environment::get('oauth.twitter.callback.success')
		)));
		Session::write('oauth.request', $token);
		if (empty($token)) {
			$this->redirect('Twitter::authorize');
		}
		$this->redirect(Consumer::authenticate($token));
	}
}

?>
