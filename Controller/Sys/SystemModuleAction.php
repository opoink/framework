<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\Controller\Sys;

class SystemModuleAction extends Sys {
	
	protected $pageTitle = 'Opoink Module Action';
	protected $_modManager;
	
	public function __construct(
		\Of\Session\SystemSession $SystemSession,
		\Of\Session\FormSession $FormSession,
		\Of\Http\Request $Request,
		\Of\Http\Url $Url,
		\Of\Std\Message $Message,
		\Of\ModManager\Module $modManager
	){
		parent::__construct($SystemSession,$FormSession,$Request,$Url,$Message);
		$this->_modManager = $modManager;
	}
	
	public function run(){
		$this->requireInstalled();
		$this->requireLogin();
		
		$validate = $this->validateFormKey();
		$action = strtolower($this->_request->getParam('action'));
		
		if($validate){
			$intalledModule = $this->_request->getParam('intalledModule');
			$upgradeds = $this->_modManager->setDi($this->_di)->moduleAction();

			/** we will use this global variable to easily get the installation result */
			$_GET['module_install_result'] = [];

			$response = [];
			foreach($upgradeds as $key => $val){
				$response['message'] = [];

				if($action == 'upgrade'){
					$response['message'][] = $val['vendor'] . '_' . $val['module'] . ' module successfully Upgraded';
					foreach($val['message'] as $msg){
						$response['message'][] = $msg['message'];
					}
					if(isset($val['error_messages'])){
						if( is_array($val['error_messages']) ){
							foreach($val['error_messages'] as $msg){
								$response['message'][] = $msg;
							}
						}
					}
				}
				if($action == 'uninstall'){
					$response['message'][] = $val['vendor'] . '_' . $val['module'] . ' module successfully uninstalled';
				}
			}
			
			$response['module_install_result'] = $_GET['module_install_result'];
			$this->jsonEncode($response);
		} else {
			$this->returnError('400', 'Invalid request.');
		}
	}
}