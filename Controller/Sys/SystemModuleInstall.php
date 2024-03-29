<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\Controller\Sys;

class SystemModuleInstall extends Sys {
	
	protected $pageTitle = 'Opoink Module List';
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
		if($validate){
			$availableModules = $this->_request->getParam('availableModule');

			/** we will use this global variable to easily get the installation result */
			$_GET['module_install_result'] = [];

			$installed = $this->_modManager->setDi($this->_di)->installModule($availableModules);
			
			if($installed){
				$installedCount = count($installed);
				$s = ($installedCount > 1) ? 's' : '';
	
				$response['error'] = 0;
				$response['message'] = $installedCount.' Module' . $s . ' successfully installed';
				$response['installed_module'] = $installed[0];
			} else {
				$response['error'] = 1;
				$response['message'] = 'No module was installed';
				$response['installed_module'] = null;
			}

			$response['module_install_result'] = $_GET['module_install_result'];
			$this->jsonEncode($response);
		} else {
			$this->returnError('400', 'Invalid formkey request');
		}
	}
}