<?php

namespace app\controllers;

use \li3_oauth\extensions\data\OAuthProvider;

use \lithium\core\Environment;
use \lithium\net\http\Router;
use \lithium\storage\Session;

class OAuthApiController extends AppBaseController {

	/**
	 *
	 */
	protected $_currentApi = null;

	/**
	 *
	 */
	protected $_publicActions = array('index', 'callback');

	/**
	 *
	 */
	protected function _init() {
		parent::_init();

		if (isset($this->request->name)) {
			$this->_currentApi = $this->request->name;

			if (empty($this->_currentApi)) {
				return $this->_404();
			}
		}
	}

	protected function _getRedirectUri() {
		$host = Environment::get('host');
		return Router::match(array('controller' => 'oAuthApi', 'action' => 'callback2', 'name' => $this->_currentApi), null, array('absolute' => true, 'host' => $host));
	}

	public function index() {
		if (!OAuthProvider::isAuthentificated($this->_currentApi)) {
			$url = OAuthProvider::authenticate($this->_currentApi, array('callback' => $this->_getRedirectUri()));
			$this->redirect($url);
		}
		else {
			$result = OAuthProvider::me($this->_currentApi);
			$message = print_r($result, true);
		}

		return compact('message');
	}

	public function callback() {
		// Authentificate
		if (!OAuthProvider::isAuthentificated($this->_currentApi)) {
			$result = OAuthProvider::getAccessToken($this->_currentApi, array('request' => $this->request, 'callback' => $this->_getRedirectUri()));
			if($result !== true) {
				// Error
				var_export($result);
				die();
			}
		} else {
			// Error
		}

		// Redirect to index
		$this->redirect(array('controller' => 'oAuthApi', 'action' => 'index', 'name' => $this->_currentApi));
	}

	public function reset() {
		Session::delete('li3_auth');

		OAuthProvider::clean($this->_currentApi);
	}

}

?>
