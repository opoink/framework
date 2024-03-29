<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\Controller\Sys;

class SystemLoginIndex extends Sys {
	
	protected $pageTitle = 'Opoink Login';
	protected $email = '';
	protected $password = '';
	
	protected $_systemAdmin;
	protected $_password;
	
	public function __construct(
		\Of\Session\SystemSession $SystemSession,
		\Of\Session\FormSession $FormSession,
		\Of\Http\Request $Request,
		\Of\Http\Url $Url,
		\Of\Std\Message $Message,
		\Of\Std\Password $Password,
		\Of\Db\Entity\SystemAdmin $SystemAdmin
	){
		parent::__construct($SystemSession,$FormSession,$Request,$Url,$Message);
		$this->_systemAdmin = $SystemAdmin;
		$this->_password = $Password;
		
	}
	
	public function run(){
		$errorRedirect = (bool)$this->getParam('isredirect');

		$this->requireInstalled();
		$this->requireNotLogin($errorRedirect);
		
		$this->email = $this->getParam('email');
		$this->password = $this->getParam('password');
		
		$response = [];
		if($this->email != ''){
			$user = $this->_systemAdmin->getByColumn(['email' => $this->email]);
			if($user){
				$verify = $this->_password->setPassword($this->password)->setHash($user->getData('password'))->verify();
				if($verify){
					$this->_systemSession->setAsLogedIn($user->getData('id'));
					$response['error'] = 0;
					$response['message'] = 'Welcome back ' . $user->getData('firstname');

					$this->jsonEncode($response);
				} else {
					header("HTTP/1.0 400 Bad Request");
					echo 'Invalid email or password';
					die;
				}
			} else {
				header("HTTP/1.0 400 Bad Request");
				echo 'Invalid email or password';
				die;
			}
		}
		return $this->renderHtml();
	}
	
}