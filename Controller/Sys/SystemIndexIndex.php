<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\Controller\Sys;

class SystemIndexIndex extends Sys {

	protected $pageTitle = 'Opoink Dashboard';

	public function run(){
		$this->requireInstalled();
		$this->requireLogin();

		return $this->renderHtml();
	}
}