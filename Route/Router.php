<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\Route;

class Router {
	
	protected $adminroute = '';
	protected $systemroute = '';
	protected $route = 'index';
	protected $controller = 'index';
	protected $action = 'index';
	protected $isAdmin  = false;
	protected $config;
	protected $_url;
	protected $opoinkRouter = null;
	
	/*
	 *	determine whether the path is a regex or not
	 */
	protected $route_regex = false;
	protected $controller_regex = false;
	protected $action_regex = false;
	protected $matchedRoute = null;

	public $requestRoute = null;
	public $requestController = null;
	public $requestAction = null;

	public function __construct($config=null){
		if($config){
			$this->setConfig($config);
		}
		$this->_url = new \Of\Http\Url();
		$this->opoinkRouter = new \Opoink\Router\Router();
		$this->init();
	}
	
	public function setConfig($config){
		$this->config = $config;
		$this->adminroute = 'admin'.$this->config['admin'];

		$this->systemroute = 'system';
		if(isset($this->config['system_url'])){
			$this->systemroute .= $this->config['system_url'];
		}
		return $this;
	}

	public function getConfig(){
		return $this->config;
	}

	/*
	*	validate the used domain name
	*	we do not always rely on servers domain settings
	*	we also have to validate it here
	*	the domain can be set in system panel or direct edit 
	*	on /installation_dir/etc/Config.php
	*	always return true on system route to reconfigure the 
	*	for some typo
	*	return bool 
	*/
	public function validateDomain(){
		if($this->config != null && isset($this->config['domains'])){
			$d = $this->config['domains'];

			if($this->systemroute != $this->getRoute(false)) { 
				if(count($d) > 0){
					$domain = $this->_url->getDomain();
					return in_array($domain, $d);
				}
			}
		}

		/*
		*	since there is no set domain
		*	means can use all pointed domain names
		*/
		return true;
	}
	
	/*
	*	return controller class
	*	for specific page
	*/
	public function getControllerClass(){
		$this->setSystemController();
		$controllers = $this->config['controllers'];
		
		$key = $this->getPageName(false);

		$controllerClass = null;
		foreach($controllers as $_key => $_val){
			if(isset($_val[$key])){
				$this->matchedRoute = $_val;
				$controllerClass = $_val[$key];
			} else {
				foreach ($_val as $routeKey => $routeValue) {
					if(is_array($routeValue)){
						if(isset($routeValue['route'])){
							/** 
							 * in this part the router is using a regular expresion
							 * on either route or controller or action
							 * this is the 2nd type of routing of opoink
							 * 
							 * this type will be deprectated soon as we will be using 
							 * the third type instead
							 */
							$this->requestRoute = $this->getRoute(false);
							$this->requestController = $this->getController(false);
							$this->requestAction = $this->getAction(false);
	
							$r = $this->doCompare($this->requestRoute, $routeValue['route'], $routeValue['route_regex']);
							$c = $this->doCompare($this->requestController, $routeValue['controller'], $routeValue['controller_regex']);
							$a = $this->doCompare($this->requestAction, $routeValue['action'], $routeValue['action_regex']);
	
							$this->route_regex = $r;
							$this->controller_regex = $c;
							$this->action_regex = $a;
	
							if($r && $c && $a) {
								$this->matchedRoute = $routeValue;
								$controllerClass = $routeValue['class'];
								break;
							}
						}
						elseif(isset($routeValue['pattern'])){
							/** 
							 * in this part the router is using the full regex
							 * pattern ex: /user/edit/:id/save
							 * this is the third type of routing
							 * currently this type wont work on the xml templating of opoink framework
							 * this can be usefull for rest API request
							 */
							$params = $this->opoinkRouter->getMatch($routeValue);
								
							if(is_array($params)){
								foreach($params as $key => $value){
									$_GET[$key] = $value;
								}
								$controllerClass = $routeValue['class'];
								break;
							}
						}
					}
				}
			}
			if($controllerClass){
				break;
			}
		}
		return $controllerClass;
	}

	/**
	 * do the comparison using regular expression or simply ==
	 * return boolean
	 * @param $requestValue string, camefrom the request url
	 * @param $configValue regex or a string value that came from the saved config file
	 */
	protected function doCompare($requestValue, $configValue, $isRegex){
		if(strtolower($requestValue) == strtolower($configValue)){
			return true;
		}
		if($isRegex){
			if( preg_match("/^\/.+\/[a-z]*$/i",$configValue)) {
				$match = (bool)preg_match($configValue, $requestValue);
				return $match;
			}
		}
		return false;
	}
	
	/*
	*	set all system controller
	*	ex. system_index_index controller
	*/
	protected function setSystemController(){
		$sysRoute = 'system';
		if(isset($this->config['system_url'])){
			$sysRoute .= $this->config['system_url'];
		}
		$sysControllers = [
			$sysRoute.'_index_index' => 'Of\\Controller\\Sys\\SystemIndexIndex',
			$sysRoute.'_login_index' => 'Of\\Controller\\Sys\\SystemLoginIndex',
			$sysRoute.'_login_forgetpassword' => 'Of\\Controller\\Sys\\SystemLoginForgetpassword',
			$sysRoute.'_login_resetpassword' => 'Of\\Controller\\Sys\\SystemLoginResetpassword',
			$sysRoute.'_logout_index' => 'Of\\Controller\\Sys\\SystemLogoutIndex',
			$sysRoute.'_install_index' => 'Of\\Controller\\Sys\\SystemInstallIndex',
			$sysRoute.'_install_requirement' => 'Of\\Controller\\Sys\\SystemInstallRequirement',
			$sysRoute.'_install_database' => 'Of\\Controller\\Sys\\SystemInstallDatabase',
			$sysRoute.'_install_formkey' => 'Of\\Controller\\Sys\\SystemInstallFormkey',
			$sysRoute.'_install_saveadmin' => 'Of\\Controller\\Sys\\SystemInstallSaveadmin',
			$sysRoute.'_install_opoinkbmodule' => 'Of\\Controller\\Sys\\SystemInstallOpoinkbmodule',
			$sysRoute.'_install_saveadminurl' => 'Of\\Controller\\Sys\\SystemInstallSaveadminurl',
			$sysRoute.'_settings_index' => 'Of\\Controller\\Sys\\SystemSettingsIndex',
			$sysRoute.'_module_action' => 'Of\\Controller\\Sys\\SystemModuleAction',
			$sysRoute.'_module_addcontroller' => 'Of\\Controller\\Sys\\SystemModuleAddcontroller',
			$sysRoute.'_module_create' => 'Of\\Controller\\Sys\\SystemModuleCreate',
			$sysRoute.'_module_edit' => 'Of\\Controller\\Sys\\SystemModuleEdit',
			$sysRoute.'_module_index' => 'Of\\Controller\\Sys\\SystemModuleIndex',
			$sysRoute.'_module_install' => 'Of\\Controller\\Sys\\SystemModuleInstall',
			$sysRoute.'_module_save' => 'Of\\Controller\\Sys\\SystemModuleSave',
			$sysRoute.'_module_update' => 'Of\\Controller\\Sys\\SystemModuleUpdate',
			$sysRoute.'_user_index' => 'Of\\Controller\\Sys\\SystemUserIndex',
			$sysRoute.'_user_save' => 'Of\\Controller\\Sys\\SystemUserSave',
			$sysRoute.'_cache_index' => 'Of\\Controller\\Sys\\SystemCacheIndex',
			$sysRoute.'_cache_action' => 'Of\\Controller\\Sys\\SystemCacheAction',
			$sysRoute.'_static_vue' => 'Of\\Controller\\Sys\\SystemStaticVue',
			$sysRoute.'_static_css' => 'Of\\Controller\\Sys\\SystemStaticCss',
			$sysRoute.'_database_addforeignkey' => 'Of\\Controller\\Sys\\SystemDbAddForeignkey',
			$sysRoute.'_database_index' => 'Of\\Controller\\Sys\\SystemDatabaseIndex',
			$sysRoute.'_database_savefield' => 'Of\\Controller\\Sys\\SystemDatabaseSavefield',
			$sysRoute.'_database_dropfield' => 'Of\\Controller\\Sys\\SystemDatabaseDropfield',
			$sysRoute.'_database_newtablejsonschema' => 'Of\\Controller\\Sys\\SystemDatabaseNewTableJsonSchema',
			$sysRoute.'_database_droptable' => 'Of\\Controller\\Sys\\SystemDatabaseDroptable',
			$sysRoute.'_database_saveinstalldata' => 'Of\\Controller\\Sys\\SystemDatabaseSaveInstallData',
			$sysRoute.'_database_deleteinstalldata' => 'Of\\Controller\\Sys\\SystemDatabaseDeleteInstallData',
			$sysRoute.'_database_saveconstraint' => 'Of\\Controller\\Sys\\SystemDatabaseSaveConstraint',
			$sysRoute.'_database_dropconstraint' => 'Of\\Controller\\Sys\\SystemDatabaseDropConstraint',
		];
		$this->config['controllers']['Systems_Controllers'] = $sysControllers;
	}
	
	public function getPageName($ucfirst=true, $delimiter='_'){
		$key = '';
		if($this->isAdmin){
			$key .= 'admin_';
		}
		$key .= $this->getRoute($ucfirst).$delimiter.$this->getController($ucfirst).$delimiter.$this->getAction($ucfirst);
		return $key;
	}

	/*
	 * check if the route is regex or not
	 * if it is regex route will be converted as sha1 to become string
	 * return the route from the url
	 */
	public function getRoute($ucfirst=true){
		if(is_array($this->matchedRoute) && isset($this->matchedRoute['route_regex'])){
			if($this->matchedRoute['route_regex'] == true){
				$this->route = 'Reg'.sha1($this->matchedRoute['route']);
			}
		}

		if($ucfirst){
			$o = ucfirst($this->route);
		} else {
			$o = strtolower($this->route);
		}
		return $o;
	}
	
	/*
	 * check if the controller is regex or not
	 * if it is regex controller will be converted as sha1 to become string
	 * return the controller from the url
	 */
	public function getController($ucfirst=true){
		if(is_array($this->matchedRoute) && isset($this->matchedRoute['controller_regex'])){
			if($this->matchedRoute['controller_regex'] == true){
				$this->controller = 'Reg'.sha1($this->matchedRoute['controller']);
			}
		}

		if($ucfirst){
			$o = ucfirst($this->controller);
		} else {
			$o = strtolower($this->controller);
		}
		return $o;
	}
	
	/*
	 * check if the action is regex or not
	 * if it is regex action will be converted as sha1 to become string
	 * return the action from the url
	 */
	public function getAction($ucfirst=true){
		if(is_array($this->matchedRoute) && isset($this->matchedRoute['action_regex'])){
			if($this->matchedRoute['action_regex'] == true){
				$this->action = 'Reg'.sha1($this->matchedRoute['action']);
			}
		}

		if($ucfirst){
			$o = ucfirst($this->action);
		} else {
			$o = strtolower($this->action);
		}
		return $o;
	}

	protected function init(){
		$path = ltrim($_SERVER['REQUEST_URI'], "/");
		$path =	explode('?', $path);
		$paths = explode("/",ltrim($path[0], "/"));
		
		if($this->validatePath($paths, 0)){
			if($paths[0] === $this->adminroute){
				if($this->validatePath($paths, 1)){
					$this->route = $paths[1];
					if($this->validatePath($paths, 2)){
						$this->controller = $paths[2];
						if($this->validatePath($paths, 3)){
							$this->action = $paths[3];
						}
					}
				}
				$this->isAdmin = true;
			} else {
				$this->route = $paths[0];
				if($this->validatePath($paths, 1)){
					$this->controller = $paths[1];
					if($this->validatePath($paths, 2)){
						$this->action = $paths[2];
					}
				}
			}
		}
		
		// $this->setQuery($paths);
		$this->phpInput($paths);
	}
	
	public function getAdminRoute(){
		if($this->isAdmin){
			return $this->adminroute;
		} else {
			return false;
		}
	}
	
	protected function validatePath($paths, $path){
		$o = false;
		if(isset($paths[$path])){
			if($paths[$path] != null || $paths[$path] != ''){
				$o = true;
			}
		}
		return $o;
	}
	
	protected function setQuery($paths){
		$pathNo = 3;
		
		if($this->isAdmin){
			$pathNo += 1;
		}
		
		if(count($paths) > 3){
			$k = $v = null;
			foreach($paths as $key => $val){
				if($key <= ($pathNo - 1)){
					continue;
				}
				if($k == null && $v == null){
					$k = $val;
				}
				elseif($k != null && $v == null){
					if($val != '' || $val != null){
						$_GET[$k] = $val;
						$k = $v = null;
					}
				}
			}
		}
	}
	
		
	protected function phpInput(){
		$phpInput = file_get_contents("php://input");
		if($phpInput){
			$postdata = json_decode($phpInput, true);
			if($postdata) {
				foreach($postdata as $key => $val){
					$_GET[$key] = $val;
				}
			}
		}
	}

	public function getCurrentRoute(){
		return [
			'route' => $this->requestRoute ? $this->requestRoute : $this->route,
			'controller' => $this->requestController ? $this->requestController : $this->controller,
			'action' => $this->requestAction ? $this->requestAction : $this->action
		];
	}
}
?>