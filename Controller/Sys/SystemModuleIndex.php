<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/

namespace Of\Controller\Sys;

class SystemModuleIndex extends Sys {

	
	protected $pageTitle = 'Opoink Module List';

	/**
	 * \Of\ModManager\Module
	 */
	protected $_modManager;

	/**
	 * array list of modules
	 */
	protected $modules;

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

		$modules = $this->_request->getParam('modules');
		if($modules){
			$this->getModules();
		} else {
			return $this->renderHtml();
		}
	}

	protected function getModules(){
		$this->modules = $this->_modManager->getAll();
		$this->jsonEncode($this->modules);
	}
}