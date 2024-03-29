<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\Controller;

class Error extends \Of\Controller\Sys\Sys {
	protected $error = [
		'_404' => 'Page Not Found',
		'_500' => 'Internal Server Error',
		'_503' => 'Service Unavailable',
		'_301' => 'Moved Permanently',
		'_302' => 'Temporary Redirect',
		'_400' => 'Bad Request',
		'_401' => 'Unauthorized',
		'_502' => 'Bad Gateway',
	];

	protected $_config;

	public function __construct(
		\Of\Session\SystemSession $SystemSession,
		\Of\Session\FormSession $FormSession,
		\Of\Http\Request $Request,
		\Of\Http\Url $Url,
		\Of\Std\Message $Message,
		\Of\Config $Config
	){
		$this->_config = $Config->getConfig();
		parent::__construct($SystemSession, $FormSession, $Request, $Url, $Message);
	}

	public function _run($code=404){
		$title = '';
		if(isset($this->error['_'.$code])){
			$title = $this->error['_'.$code];
		}

		header("HTTP/1.0 " . $code . " " . $title);
		if(file_exists(ROOT.DS.'public'.DS.'vuedist'.DS.'index.html')){
			include(ROOT.DS.'public'.DS.'vuedist'.DS.'index.html');
		} else {
			$sysDir = ROOT.DS.'vendor'.DS.'opoink'.DS.'framework'.DS.'View'.DS.'Sys'.DS;
	
			$layoutFile = $sysDir.'Layout'.DS.'error.phtml';
			$tplFile = $sysDir.'Templates'.DS.'error'.DS.'error'.$code.'.phtml';
			$template = '';
			if(file_exists($tplFile)){
				ob_start();
					include($tplFile);
					$template = ob_get_contents();
				ob_end_clean();
			}
	
			if(file_exists($layoutFile)){
				include($layoutFile);
			}
			die;
		}
	}
}

?>