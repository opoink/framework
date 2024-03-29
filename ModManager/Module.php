<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\ModManager;

use \Of\Constants;

class Module {
	
	protected $moduleDir;
	protected $_config;
	protected $_di;
	protected $_extensionEntity;
	protected $_request;

	public function __construct(
		\Of\Db\Entity\Extension $extensionEntity,
		\Of\Http\Request $Request
	){
		$this->_extensionEntity = $extensionEntity;
		$this->_request = $Request;
		$this->moduleDir = ROOT . DS . 'App' . DS . 'Ext';

		$config = ROOT . DS . 'etc' . DS. 'Config.php';
		if(file_exists($config)){
			$this->_config = include($config);
		}
	}
	
	public function setDi($di){
		$this->_di = $di;
		return $this;
	}

	public function getAll(){
		return [
			'installed' => $this->getInstalled(),
			'uninstalled' => $this->getUnInstalled()
		];
	}

	protected function getInstalled(){
		$mods = [];
		if(isset($this->_config['modules'])){
			$vendors = $this->_config['modules'];
			foreach($vendors as $keyVendor => $modules){
				foreach($modules as $module){
					$vendor = ucfirst($keyVendor);
					$module = ucfirst($module);

					$moduleConfig = $this->moduleDir . DS . $vendor . DS . $module . DS . 'Config.php';
					if(file_exists($moduleConfig)){
						$savedModule = $this->_extensionEntity->getByColumn(['vendor' => $vendor, 'extension' => $module]);
						if($savedModule){
							$config = include($moduleConfig);
							$config['installed_version'] = $savedModule->getData('version');
							$mods[$vendor . '_' . $module] = $config;
						}
					}
				}
			}
		}
		return $mods;
	}

	protected function getUnInstalled(){
		$mods = [];
		if(is_dir($this->moduleDir)){
			$vendors = $this->getDirs($this->moduleDir);
			foreach($vendors as $vendor){
				$modules = $this->getDirs($this->moduleDir . DS . $vendor);
				foreach($modules as $module){
					$vendor = ucfirst($vendor);
					$module = ucfirst($module);

					$moduleConfig = $this->moduleDir . DS . $vendor . DS . $module . DS . 'Config.php';
					if(file_exists($moduleConfig)){
						if(isset($this->_config['modules'])){
							$_configModules = $this->_config['modules'];

							if(isset($_configModules[$vendor])){
								$v = $_configModules[$vendor];
								
								if(!in_array($module, $v)){
									$mods[$vendor . '_' . $module] = include($moduleConfig);
								} 
							} else {
								$mods[$vendor . '_' . $module] = include($moduleConfig);
							}
						} else {
							$mods[$vendor . '_' . $module] = include($moduleConfig);
						}
					}
				}
			}
		}
		return $mods;
	}

	protected function getDirs($target){
		$dirs = [];
		if(is_dir($target)){
			$availableDirs = scandir($target);
			unset($availableDirs[0]);
			unset($availableDirs[1]);
			sort($availableDirs);
			$dirs = $availableDirs;
		}
		return $dirs;
	}

	public function installModule($availableModules){
		$moduleDir = ROOT . DS . 'App' . DS . 'Ext';

		if(!isset($this->_config['modules'])){
			$this->_config['modules'] = [];
		}

		if(!isset($this->_config['controllers'])){
			$this->_config['controllers'] = [];
		}

		$installedModule = [];
		foreach($availableModules as $module){
			$m = explode('_', $module);
			if(count($m) == 2){
				list($vendor, $module) = $m;
				$vendor = ucfirst($vendor);
				$module = ucfirst($module);

				$fullName = $vendor . '_' . $module;

				$target = $moduleDir . DS . $vendor . DS . $module . DS . 'Config.php';
				if(file_exists($target)){
					$moduleConfig = include($target);

					$result = [
						'vendor' => $vendor,
						'module' => $module
					];

					if(!isset($this->_config['modules'][$vendor])){
						$this->_config['modules'][$vendor] = [];
					} 
						
					if(!isset($this->_config['modules'][$vendor][$module])){
						$this->_config['modules'][$vendor][] = $module;
					
						if(isset($moduleConfig['controllers'])){
							$this->_config['controllers'][$fullName] = $moduleConfig['controllers'];
						}
						
						/** 
						 * this old schema type will going to be depricated on the future release 
						 * encourage the dev to use the new type of schema instead
						 */
						$installSchema = $moduleDir . DS . $vendor . DS . $module . DS . 'Schema' . DS . 'Install.php';
						if(file_exists($installSchema)){
							$installSchema = $this->_di->make("$vendor\\$module\Schema\Install");
							$installSchema->setAdapter()->createSchema();
							$result['schema_installed'] = true;
							$result['error_messages'][] = 'The usage of the old install schema is deprecated and will be removed on a future release around 2023. please use the new JSON type schema';
						}
						$UpgradeSchema = $moduleDir . DS . $vendor . DS . $module . DS . 'Schema' . DS . 'Upgrade.php';
						if(file_exists($UpgradeSchema)){
							$UpgradeSchema = $this->_di->make("$vendor\\$module\Schema\Upgrade");
							$UpgradeSchema->setAdapter()->upgradeSchema("0.0.0", $moduleConfig['version']);
							$result['upgrade_schema_installed'] = true;
							$result['error_messages'][] = 'The usage of the old upgrade schema is deprecated and will be removed on a future release around 2023. please use the new JSON type schema';
						}
						/** end old schema */

						/**
						 * this schema will be the new migration
						 */
						try {
							$migration = $this->_di->make("Of\Database\Migration\Migrate");

							/** init() return a table name that was just intalled or updated */
							$tableNames = $migration->setDi($this->_di)->setConfig($this->_config)->setVendorName($vendor)->setModuleName($module)->init(true);
							$result['schema_table_installed_or_update'] = $tableNames;

							$this->_extensionEntity->setDatas([
								'vendor' => $vendor,
								'extension' => $module,
								'version' => $moduleConfig['version'],
								'status' => \Of\Std\Status::ENABLED
							])->__save();
							
							$installedModule[] = $result;
							
							$_GET['module_install_result'][] = [
								'message' => $vendor.'_' .$module.': Installed successully.',
							];
						} catch(\Exception $e){
							$result['error_messages'][] = $e->getMessage();
							$_GET['module_install_result'][] = [
								'message' => $module . ': ' . $e->getMessage()
							];
						}
					}
				} else {
					$_GET['module_install_result'][] = [
						'message' => $module . ': Config.php file does not exist.'
					];
				}
			} else {
				$_GET['module_install_result'][] = [
					'message' => $module . ': is invalid module name'
				];
			}
		}

		if(count($installedModule) > 0){
			$newConfig = '<?php' . PHP_EOL;
			$newConfig .= 'return ' . var_export($this->_config, true) . PHP_EOL;
			$newConfig .= '?>';
			
			$_writer = new \Of\File\Writer();
			$_writer->setDirPath(ROOT . DS . 'etc')
			->setData($newConfig)
			->setFilename('Config')
			->setFileextension('php')
			->write();
			return $installedModule;
		}
		return null;
	}

	public function moduleAction(){
		if(isset($this->_config['modules'])){
			$action = strtolower($this->_request->getParam('action'));
			$intalledModule = $this->_request->getParam('intalledModule');
			$result = [];
			foreach($intalledModule as $module){
				$m = explode('_', $module);
				if(count($m) == 2){
					list($vendor, $module) = $m;

					if($action === 'upgrade'){
						$result[] = $this->upgradeModule($vendor, $module);
					}
					elseif($action === 'uninstall'){
						$result[] = $this->uninstallModule($vendor, $module);
					}
				}
			}

			return $result;
		}
	}

	public function upgradeModule($vendor, $module){
		$v = ucfirst($vendor);
		$m = ucfirst($module);
		$moduleRoot = $this->moduleDir . DS . $v . DS . $m;
		$config = $moduleRoot . DS . 'Config.php';
		$result = [
			'vendor' => $v,
			'module' => $m,
			'error' => 1,
			'message' => [],
		];
		if(file_exists($config)){
			$config = include($config);
			
			$savedModule = $this->_extensionEntity->getByColumn(['vendor' => $v, 'extension' => $m]);
			if($savedModule){
				$fullName = $v . '_' . $m;
				$result['error'] = 0;
				try {

					if(file_exists(Constants::EXT_DIR.DS.$v.DS.$m.DS.'Schema'.DS.'Upgrade.php')){
						/** 
						 * this old schema type will going to be depricated on the future release 
						 * encourage the dev to use the new type of schema instead
						 */
						$upgradeSchema = $this->_di->make($v.'\\'.$m.'\\Schema\\Upgrade');
						$upgradeSchema->setAdapter()->upgradeSchema($savedModule->getData('version'), $config['version']);
						
						$result['message'][] = [
							'type' => 'danger',
							'message' => 'The usage of the old upgrade schema is deprecated and will be removed on a future release around 2023. please use the new JSON type schema'
						];
					}

					/**
					 * this schema will be the new migration
					 */
					try {
						$migration = $this->_di->make("Of\Database\Migration\Migrate");

						/** init() return a table name that was just intalled or updated */
						$tableNames = $migration->setDi($this->_di)->setConfig($this->_config)->setVendorName($v)->setModuleName($m)->init(false);
						$result['schema_table_installed_or_update'] = $tableNames;

						$result['message'][] = [
							'type' => 'success',
							'message' => 'Database schema upgraded.'
						];
					} catch(\Exception $e){
						$result['error_messages'][] = $e->getMessage();
					}
				} catch(\Exception $e) {
					$result['message'][] = [
						'type' => 'success',
						'message' => 'No upgrade in database made'
					];
					$result['message'][] = [
						'type' => 'danger',
						'message' => $e->getMessage()
					];
				}
				
				if(isset($config['controllers'])){
					$this->_config['controllers'][$fullName] = $config['controllers'];
				}

				$savedModule->setData('version',$config['version'])->__save();
				$this->saveConfig();
			} else {
				$result['message'][] = [
					'type' => 'danger',
					'message' => 'Module not yet installed.'
				];
			}
		} else {
			$result['message'][] = [
				'type' => 'danger',
				'message' => 'Config for vendor ' . $v . ' and module ' . $m . ' not found.'
			];
		}

		return $result;
	}

	public function uninstallModule($vendor, $module){
		$v = ucfirst($vendor);
		$m = ucfirst($module);
		$result = [
			'vendor' => $v,
			'module' => $m,
			'error' => 1,
			'message' => [
				'type' => 'danger',
				'message' => 'Vendor ' . $v . ' and module ' . $m . ' not yet installed.'
			],
		];
		$savedModule = $this->_extensionEntity->getByColumn(['vendor' => $v, 'extension' => $m]);
		
		if($savedModule){
			if($savedModule->delete()){
				$fullName = $v . '_' . $m;
				if(isset($this->_config['modules'])){
					if(isset($this->_config['modules'][$vendor])){
						$mod = array_search($module,$this->_config['modules'][$vendor]);
						if (is_bool($mod) === false) {
							unset($this->_config['modules'][$vendor][$mod]);
						}

						if(count($this->_config['modules'][$vendor]) <= 0){
							unset($this->_config['modules'][$vendor]);
						}
						if(isset($this->_config['controllers'])){
							if(isset($this->_config['controllers'])){
								if(isset($this->_config['controllers'][$fullName])){
									unset($this->_config['controllers'][$fullName]);
								}
							}
						}
						$result['message'] = [
							'type' => 'success',
							'message' => 'Vendor ' . $v . ' and module ' . $m . ' successfully uninstalled.'
						];

						$this->saveConfig();
					}
				}
			} else {
				$result['message'] = [
					'type' => 'danger',
					'message' => 'Vendor ' . $v . ' and module ' . $m . ' failed to uninstall.'
				];
			}
		}

		return $result;
	}

	private function saveConfig(){
		$newConfig = '<?php' . PHP_EOL;
		$newConfig .= 'return ' . var_export($this->_config, true) . PHP_EOL;
		$newConfig .= '?>';
		
		$_writer = new \Of\File\Writer();
		$_writer->setDirPath(ROOT . DS . 'etc')
		->setData($newConfig)
		->setFilename('Config')
		->setFileextension('php')
		->write();
	}
}