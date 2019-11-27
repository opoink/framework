<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/

namespace Of\Controller\Sys;

class SystemModuleAddcontroller extends Sys {

	
	protected $pageTitle = 'Opoink Module Update';
	protected $_configManager;
	protected $_controller;
	protected $_validator;
	protected $_xml;

	public function __construct(
		\Of\Session\SystemSession $SystemSession,
		\Of\Session\FormSession $FormSession,
		\Of\Http\Request $Request,
		\Of\Http\Url $Url,
		\Of\Std\Message $Message,
		\Of\ModManager\Config $_Config,
		\Of\ModManager\Controller $Controller,
		\Of\ModManager\Validator $Validator,
		\Of\ModManager\Xml $Xml
	){
		parent::__construct($SystemSession,$FormSession,$Request,$Url,$Message);
		$this->_configManager = $_Config;
		$this->_controller = $Controller;
		$this->_validator = $Validator;
		$this->_xml = $Xml;
	}

	

	public function run(){
		$this->requireInstalled();
		$this->requireLogin();
		$module_ver = $this->_request->getParam('module_ver');

		$vendor_name = ucfirst($this->_request->getParam('vendor_name'));
		$module_name = ucfirst($this->_request->getParam('module_name'));

		$redirectUrl = '/system/module/edit/mod/'.$vendor_name.'_'.$module_name;

		$config = $this->_validator->checkExist($vendor_name, $module_name);
		if($config){
			$route = ucfirst($this->_request->getParam('controller_route'));
			$conroller = ucfirst($this->_request->getParam('controller_controller'));
			$action = ucfirst($this->_request->getParam('controller_action'));
			$controller_type = $this->_request->getParam('controller_type');

			if(!strlen($route)){
				$route = "Index";
			}
			if(!strlen($conroller)){
				$conroller = "Index";
			}
			if(!strlen($action)){
				$action = "Index";
			} 

			if($route == 'system' || $route == 'System'){
				$this->_message->setMessage('System route is reserved for Opoink\'s developer panel.', 'danger');
			} else {
				$conType = 'public';
				$xmlFilename = strtolower($route.'_'.$conroller.'_'.$action);
				$controllerClass = "\\".$vendor_name."\\".$module_name."\\Controller\\".$route."\\".$conroller."\\".$action;

				if($controller_type == 'admin'){
					$conType = 'admin';
					$xmlFilename = 'admin_'.strtolower($route.'_'.$conroller.'_'.$action);
					$controllerClass =  "\\".$vendor_name."\\".$module_name."\\Controller\\Admin\\".$route."\\".$conroller."\\".$action;
				}

				$create =$this->_controller->setVendor($vendor_name)
				->setModule($module_name)
				->setRoute($route)
				->setController($conroller)
				->setAction($action)
				->create($conType);

				if($create){
					/** create controller xml layout here */
					$body = "\t\t".'<container xml:id="main_container" htmlId="main_container" htmlClass="main_container" weight="1">' . PHP_EOL;
						$body .= "\t\t\t".'<template xml:id="sample_template" vendor="'.$vendor_name.'" module="'.$module_name.'" template="sample_template.phtml" cacheable="1" max-age="604800"/>' . PHP_EOL;
					$body .= "\t\t".'</container>' . PHP_EOL;

					$this->_xml->setVendor($vendor_name)
					->setModule($module_name)
					->setFileName($xmlFilename)
					->create(true, false, 999, $body);
					/** end create controller xml layout here */

					/** create the sample template here */
					$target = $target = ROOT.DS.'App'.DS.'Ext'.DS.$vendor_name.DS.$module_name.DS.'View'.DS.'Template';
					$data = "<p>".$route." ".$conroller." ".$action." works</p>";
					$_writer = new \Of\File\Writer();
					$_writer->setDirPath($target)
					->setData($data)
					->setFilename('sample_template')
					->setFileextension('phtml')
					->write();
					/** end create the sample template here */

					$config['controllers'][$xmlFilename] = $controllerClass;

					$this->_configManager->setConfig($config)
					->createConfig();

					$this->_message->setMessage('New conroller created.', 'success');
				} else {
					$this->_message->setMessage('Cannot create, controller is already existing.', 'danger');
				}
			}
		} else {
			$this->_message->setMessage('Module name is not existing.', 'danger');
		}

		$this->_url->redirectTo($this->getUrl($redirectUrl));
	}
}