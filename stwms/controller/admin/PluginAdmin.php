<?php
defined('IN_MYCMS') or exit('No permission resources.');
if(!defined('PLUGIN_PATH')){
	define('PLUGIN_PATH', CORE_PATH . 'plugin' . CD);
}
class PluginAdmin extends AdminController{

	public function __construct(){
		parent::__construct();
		$this->xmlDbName='plugin';
	}

	public function init(){
		$data=getcache('plugin', 'core', 'array');
		$auths=load::controller('role')->_check_auth('hook.init,disabled,install,urlset,export,uninstall,admin');
		$auths['export']=$auths['export'] && function_exists('gzcompress') && function_exists('pack');
		include template('init', 'plugin');
	}

	/*
	 * 功能：安装插件
	 * 说明：安装过程根据提交参数安装过程分为上传、解压、安装、清理，同时前台界面显示安装进度
	 * 服务器返回码：
	 * 0->上传文件失败；
	 * 1->不允许上传的文件类型；
	 * 2->解压失败；
	 * 3->解压成功；
	 * 4->插件已经存在；
	 * 5->插件安装失败；
	 * 6->插件安装成功；
	 * 7->重复安装插件；
	 * 8->测试运行失败；
	 */
	public function install(){
		if(Param::get_para('dosubmit')){
			@set_time_limit(0); // 修改为不限制超时时间(默认为30秒)
			
			$uploadPath=CACHE_PATH . 'plugin' . CD . 'cache' . CD;
			if(!is_dir($uploadPath)){
				@mkdir($uploadPath, 0777, true);
			}
			switch(Param::get_para('type')){
				case 'upload': // 上传插件
					$fext=fileext($_FILES['Filedata']['name']);
					if($fext == 'zip'){
						$sourceName='temp' . (string)time() . rand(1000, 9999);
						$zipfile=$uploadPath . $sourceName . '.' . $fext;
						if(move_uploaded_file($_FILES['Filedata']['tmp_name'], $zipfile)){
							$md5str=md5_file($zipfile);
							$info=$this->getXmlDb()->getOne('plugin', '*', 'where `md5`=\'' . $md5str . '\'');
							if(empty($info)){
								echo $sourceName;
							}else{
								@unlink($zipfile);
								echo '7';
							}
						}else{
							echo '0';
						}
					}else{
						echo '1';
					}
					break;
				case 'unzip': // 解压
					$sourceName=Param::get_para('sourceName');
					$forceInstall=Param::get_para('force_install', 0);
					$fext='zip';
					$zipfile=$uploadPath . $sourceName . '.' . $fext;
					$savepath=$uploadPath . $sourceName;
					
					if(!is_file($zipfile)){
						echo '{"code":"2"}';
					}else{
						$zipObj=load::cls('PHPZip');
						$array=$zipObj->GetZipInnerFilesInfo($zipfile);
						$failfiles=array();
						$configDir=NULL;
						
						for($i=0, $max=count($array); $i < $max; $i++){
							if($array[$i]['folder'] == 0){
								if(basename($array[$i]['filename']) == 'config.php'){
									$configDir=dirname($array[$i]['filename']);
								}
								if(!$zipObj->unZip($zipfile, $savepath, $i) > 0){
									$failfiles[]=$array[$i]['filename'];
								}
							}
						}
						
						// 以下代码生成安装目录，并返回到下一步提供安装
						$install_dir=NULL;
						if(!is_null($configDir)){
							$plugin_path=$savepath . CD . $configDir . CD;
							if(is_file($plugin_path . 'config.php')){
								$cfg=include $plugin_path . 'config.php';
								if(is_array($cfg) && isset($cfg['install_dir'])){
									$install_dir=preg_replace("/[^\w]/i", '', $cfg['install_dir']);
									if(is_dir(CORE_PATH . 'plugin' . CD . $install_dir) && $forceInstall){
										$addNum=0;
										do{
											$addNum++;
										}while(is_dir(CORE_PATH . 'plugin' . CD . $install_dir . $addNum));
										$install_dir=$install_dir . $addNum;
									}
								}
							}
						}
						
						if(empty($failfiles) && !is_null($configDir) && !is_null($install_dir)){
							$_SESSION[$sourceName]=md5_file($zipfile);
							echo '{"code":"3","install_dir":"' . $install_dir . '","configDir":"' . $configDir . '","sourceName":"' . $sourceName . '","md5":"' . $_SESSION[$sourceName] . '"}';
						}else{
							echo '{"code":"2"}';
							load::func('dir');
							dir_delete($savepath);
						}
						// 清除上传插件痕迹
						@unlink($zipfile);
					}
					break;
				case 'install': // 安装
					$sourceName=Param::get_para('sourceName');
					$configDir=Param::get_para('configDir');
					$md5str=Param::get_para('md5');
					$install_dir=Param::get_para('install_dir');
					;
					$savepath=$uploadPath . $sourceName;
					$plugin_path=$savepath . CD . $configDir . CD; // 插件实际路径
					
					load::func('dir');
					
					$cfg=is_file($plugin_path . 'config.php') ? include ($plugin_path . 'config.php') : NULL;
					
					if(is_array($cfg) && !empty($cfg['name']) && isset($_SESSION[$sourceName]) && $_SESSION[$sourceName] == $md5str){
						$cfg['install_dir']=$install_dir;
						if(!is_dir(CORE_PATH . 'plugin' . CD . $install_dir)){
							$resMove=false;
							$ifsuceess=false;
							if(is_dir($plugin_path . 'statics' . CD)){
								$resMove=@rename($plugin_path . 'statics' . CD, STATIC_PATH . 'plugin' . CD . $install_dir);
							}
							$resMove=@rename($plugin_path, CORE_PATH . 'plugin' . CD . $install_dir);
							if($resMove){
								$controller=load::controller('plugin_' . $cfg['name'], 0, NULL, 'plugin' . CD . $install_dir);
								if($controller){
									$installDbOk=true;
									// 后续操作一：安装数据库
									if(is_dir(CORE_PATH . 'plugin' . CD . $install_dir . CD . 'data' . CD)){
										chdir(CORE_PATH . 'plugin' . CD . $install_dir . CD . 'data' . CD);
										$plugin_models=glob('*.model');
										$isInstalls=array();
										foreach($plugin_models as $k=>$plugin_model){
											// ！说明：此插件模型会使用插件名称作为前缀
											$model=$cfg['name'] . '_' . basename($plugin_model, '.model');
											if(is_file(CORE_PATH . 'data' . CD . 'model' . CD . $model . '.php')){
												break;
											}else if(@copy(CORE_PATH . 'plugin' . CD . $install_dir . CD . 'data' . CD . $plugin_model, CORE_PATH . 'data' . CD . 'model' . CD . $model . '.php')){
												@rename(CORE_PATH . 'plugin' . CD . $install_dir . CD . 'data' . CD . $plugin_model, CORE_PATH . 'plugin' . CD . $install_dir . CD . 'data' . CD . $model . '.model.php');
												$isInstalls[]=CORE_PATH . 'data' . CD . 'model' . CD . $model . '.php';
											}
										}
										
										// 部分模型安装不成功将会导致安装失败
										if(count($plugin_models) != count($isInstalls)){
											foreach($isInstalls as $file){
												@unlink($file);
											}
											$installDbOk=false;
										}else{
											$model_module=load::controller('model');
											foreach($isInstalls as $file){
												$model=basename($file, '.php');
												$model_module->install($model);
											}
										}
									}
									
									if($installDbOk){
										$cfg['md5']=$md5str;
										$cfg['disabled']=$this->getXmlDb()->hasFdVl('plugin', 'name', $cfg['name']) ? 1 : 0;
										$cfg['entry']=!empty($cfg['entry']) ? $cfg['entry'] : 'init'; // 默认入口方法
										$this->getXmlDb()->trans('start');
										$methods=get_class_methods('plugin_' . $cfg['name']);
										$insertID=in_array('m_' . $cfg['entry'], $methods) ? $this->getXmlDb()->insert('plugin', $cfg) : -1;
										if($insertID != -1){
											// 后续操作二：将菜单配置与内置方法合并
											$opmenus=array('c' => $cfg['name'],'pluginid' => $insertID);
											$entryID=-1;
											foreach($methods as $method){
												if(stripos($method, 'm_') === 0){
													$cmethod=substr($method, 2);
													$opmenus['a']=$cmethod;
													$opmenus['name']=is_array($cfg['menu']) && array_key_exists($cmethod, $cfg['menu']) ? $cfg['menu'][$cmethod] : $cmethod;
													$actMenuId=$this->getXmlDb()->insert('menu', $opmenus);
													if($cfg['entry'] == $cmethod){
														$entryID=$actMenuId;
														$this->getXmlDb()->update('plugin', array('entry' => $entryID), 'where `id`=' . $insertID);
													}
												}
											}
											$this->getXmlDb()->trans('end');
											
											if($entryID != -1){
												// 后续操作三：添加菜单到系统菜单
												$menuObj=load::controller('menu');
												$menuObj->_add(array('name' => $cfg['alias'],'c' => 'plugin','a' => 'admin','data' => 'actionid=' . $entryID), 1);
												
												// 后续操作四：设置系统钩子
												if(isset($cfg['hook']) && $cfg['hook']){
													load::controller('hook')->_install($cfg['name'], $cfg['hook']);
												}
												
												$this->_fresh();
												$ifsuceess=true;
												echo '6'; // 安装成功
											}else{
												echo '5';
											}
										}else{
											echo '5';
										}
									}else{
										echo '5';
									}
								}else{
									echo '5';
								}
							}else{
								echo '5'; // 移动安装文件失败，安装失败
							}
							if(!$ifsuceess){
								dir_delete(STATIC_PATH . 'plugin' . CD . $install_dir);
								dir_delete(CORE_PATH . 'plugin' . CD . $install_dir);
							}
						}else{
							echo '4'; // 安装目录已经存在，安装失败
						}
					}else{
						echo '5'; // 安装配置文件不存在，安装失败
					}
					dir_delete($savepath);
					unset($_SESSION[$sourceName]); // 注：当包含的插件文件出错不会执行此操作
					break;
				case 'clear': // 执行安装失败后清理工作
					load::func('dir');
					$sourceName=Param::get_para('sourceName');
					$configDir=Param::get_para('configDir');
					$install_dir=Param::get_para('install_dir');
					$md5str=Param::get_para('md5');
					$savepath=$uploadPath . $sourceName;
					if(isset($_SESSION[$sourceName]) && $_SESSION[$sourceName] == $md5str){
						dir_delete($savepath);
						dir_delete(STATIC_PATH . 'plugin' . CD . $install_dir);
						dir_delete(CORE_PATH . 'plugin' . CD . $install_dir);
						unset($_SESSION[$sourceName]);
					}
					break;
			}
			
			@set_time_limit(30);
		}else{
			$isSupport=function_exists('gzcompress') && function_exists('pack') ? 1 : 0;
			include template('install', 'plugin');
		}
	}

	public function urlset(){
		if(Param::get_para('check')){
			$simple_url=trim(Param::get_para('simple_url'), ' /\\');
			alert($simple_url && is_dir(ROOT_PATH . $simple_url . CD) ? '0' : '1');
		}
		$id=intval(Param::get_para('id'));
		$cfg=$this->getXmlDb()->getOne('plugin', '*', 'where `id`=' . $id);
		if(Param::get_para('dosubmit')){
			$simple_url=trim($_POST['simple_url'], ' /\\');
			$simple_url=($simple_url !== '' ? $simple_url . '/' : '');
			$res=$this->create_simpleurl($cfg, $simple_url) && $this->getXmlDb()->update('plugin', array('url' => $simple_url), 'where `id`=' . $id);
			if($res){
				$this->_fresh();
			}
			die($res ? '保存成功' : '保存失败！');
		}else{
			include template('urlset', 'plugin');
		}
	}

	public function export(){
		$id=intval(Param::get_para('id'));
		$cfg=$this->getXmlDb()->getOne('plugin', '*', 'where `id`=' . $id);
		load::func('dir');
		$zipObj=load::cls('PHPZip');
		$tempPath=CACHE_PATH . 'plugin' . CD . 'cache' . CD . 'temp' . (string)time() . rand(1000, 9999) . CD;
		$core_path=PLUGIN_PATH . $cfg['install_dir'] . CD;
		$static_path=STATIC_PATH . 'plugin' . CD . $cfg['install_dir'] . CD;
		
		// 创建目录及文件拷贝
		if(mkdir($tempPath, 0777, true) && dir_copy($core_path, $tempPath)){
			if(is_file($static_path) && mkdir($tempPath . 'statics' . CD, 0777, true) && dir_copy($static_path, $tempPath . 'statics' . CD)){
				// 对模型的处理
				if(is_dir($tempPath . 'data' . CD) && ($models=glob($tempPath . 'data' . CD . '*.model.php'))){
					foreach($models as $model){
						@rename($model, $tempPath . 'data' . CD . substr(basename($model), 0, -4));
					}
				}
				$zipObj->ZipAndDownload($tempPath, $cfg['name'] . '.zip');
			}
		}
		
		// 清除临时目录
		dir_delete($tempPath);
	}
	
	// 禁用插件
	public function disabled(){
		$disabled=intval($_GET['disabled']);
		$id=intval($_GET['id']);
		if($this->getXmlDb()->update('plugin', array('disabled' => $disabled), 'where `id`=' . $id)){
			if(!$disabled){
				$this->getXmlDb()->update('plugin', array('disabled' => 1), 'where `name`=\'' . $_GET['name'] . '\' and `id`!=' . $id);
			}
			$this->_fresh();
			
			if($disabled){ // 卸载钩子
				load::controller('hook')->_uninstall($_GET['name']);
			}else{ // 安装钩子
				$info=$this->getXmlDb()->getOne('plugin', 'id,name,install_dir', 'where `id`=' . $id);
				$cfg=include (PLUGIN_PATH . $info['install_dir'] . CD . 'config.php');
				unset($info, $this->xmlDb);
				if(isset($cfg['hook']) && $cfg['hook']){
					load::controller('hook')->_install($cfg['name'], $cfg['hook']);
				}
			}
			
			$this->showmessage('操作成功！', act_url('plugin', 'init'));
		}else{
			$this->showmessage('操作失败！', act_url('plugin', 'init'));
		}
	}
	
	// 卸载插件
	public function uninstall(){
		$id=intval($_GET['id']);
		$cfg=$this->getXmlDb()->getOne('plugin', '*', 'where `id`=' . $id);
		if(!empty($cfg)){
			$models=glob(CORE_PATH . 'plugin' . CD . $cfg['install_dir'] . CD . 'data' . CD . '*.model.php');
			if($this->getXmlDb()->delete('plugin', 'where `id`=' . $id) && $this->getXmlDb()->delete('menu', 'where `pluginid`=' . $id)){
				load::controller('menu')->_delplugin($cfg['entry']); // 删除添加的插件菜单
				if($models){
					$model_module=load::controller('model');
					foreach($models as $file){
						$model=basename($file, '.model.php');
						$model_module->del($model, 1);
					}
				}
				
				// 删除目录
				load::func('dir');
				if($cfg['url'] && is_dir(ROOT_PATH . $cfg['url'])){
					$actions=$this->get_plugin_methods($cfg);
					foreach($actions as $action){
						@unlink(ROOT_PATH . $cfg['url'] . $action . CD . 'index.php');
						dir_delete(ROOT_PATH . $cfg['url'] . $action . CD, 1);
					}
					dir_delete(ROOT_PATH . $cfg['url'], 1);
				}
				dir_delete(STATIC_PATH . 'plugin' . CD . $cfg['install_dir'] . CD);
				dir_delete(CORE_PATH . 'plugin' . CD . $cfg['install_dir'] . CD);
				dir_delete(CACHE_PATH . 'template' . CD . 'plugin' . CD . $cfg['install_dir'] . CD);
				
				$this->_fresh();
				load::controller('hook')->_uninstall($cfg['name']);
				$this->showmessage('卸载操作成功！', act_url('plugin', 'init'));
			}else{
				$this->showmessage('卸载操作失败！', act_url('plugin', 'init'));
			}
		}else{
			$this->showmessage('卸载操作失败！', act_url('plugin', 'init'));
		}
	}
	
	// 管理系统运行插件框架
	public function admin(){
		$actionid=intval(Param::get_para('actionid', 0));
		$c='';
		$a='';
		$pluginid=0;
		$menus=NULL;
		$pluginsMap=& getLcache('plugin', 'core', 'array', 'map');
		if($actionid){
			$menus=getcache('plugin', 'core', 'array', 'menu');
			$pluginid=isset($menus[$actionid]) ? $menus[$actionid]['pluginid'] : 0;
			if($pluginid){
				$c=$menus[$actionid]['c'];
				$a=$menus[$actionid]['a'];
			}
		}else if($c=Param::get_para('plugin_c', '')){
			$a=Param::get_para('plugin_a');
		}
		
		if($c && isset($pluginsMap[$c])){
			if(empty($a)){
				if(!$menus){
					$menus=getcache('plugin', 'core', 'array', 'menu');
				}
				$a=$menus[$pluginsMap[$c]['entry']]['a'];
			}
			unset($menus);
			
			$controller=load::plugin($c, $cfg);
			if($controller){
				$method='m_' . $a;
				if(method_exists($controller, $method)){
					$GLOBALS[PLUGIN_ID]=$cfg['install_dir'];
					call_user_func(array($controller,$method));
				}else{
					$this->showmessage('系统插件类操作方法不存在！', act_url('admin', 'init'));
				}
			}else{
				$this->showmessage('系统插件调用错误！', act_url('admin', 'init'));
			}
		}else{
			$this->showmessage('系统插件未安装或已经被禁用！', act_url('admin', 'init'));
		}
	}

	/* * ******外部接口方法******* */
	
	// 根据入口获取菜单信息
	public function _getInfoByEntry($entryID,$fd='id'){
		$info=$this->getXmlDb()->getOne('plugin', '*', 'where `entry`=' . $entryID);
		$res=array();
		if(!empty($info)){
			$infos=$this->getXmlDb()->select('menu', '*', 'where `pluginid`=' . $info['id']);
			foreach($infos as $v){
				$res[]=($fd == 'all' ? $v : $v[$fd]);
			}
		}
		return $res;
	}
	
	// 调用插件，提供给插件标签处的调用，应用于前台代码
	public function _call($datas,$method_pre='_tag_'){
		$c=$datas['name'];
		$method=$method_pre . (isset($datas['action']) && $datas['action'] ? $datas['action'] : 'index');
		$controller=load::plugin($c, $cfg);
		if($controller && method_exists($controller, $method)){
			$GLOBALS[PLUGIN_ID]=$cfg['install_dir'];
			return call_user_func(array($controller,$method), $datas);
		}
		return false;
	}	
	
	// 刷新缓存
	public function _fresh(){
		$infos=$this->getXmlDb()->select('plugin', '*', 'where `id`>=0', 'order by `id` desc');
		$infos_menu=$this->getXmlDb()->select('menu', '*', 'where `id`>=0', 'order by `id` desc');
		$data=$data_menu=$data_map=$data_camap=$data_url=array();
		foreach($infos as $vl){
			$data[intval($vl['id'])]=$vl;
			if(empty($vl['disabled'])){
				$data_map[$vl['name']]=array('id' => $vl['id'],'entry' => $vl['entry'],'install_dir' => $vl['install_dir']);
			}
			$data_url[$vl['name']]=$vl['url'];
		}
		foreach($infos_menu as $vl){
			$data_menu[intval($vl['id'])]=$vl;
			$ca=$vl['c'] . $vl['a'];
			$data_camap[$ca]=$vl['id'];
		}
		unset($infos, $infos_menu);
		setcache('plugin', $data, 'core', 'array');
		setcache('plugin_map', $data_map, 'core', 'array');
		setcache('plugin_url', $data_url, 'core', 'array');
		setcache('plugin_menu', $data_menu, 'core', 'array');
		setcache('plugin_camap', $data_camap, 'core', 'array');
	}

	/* * ******私有方法******* */
	private function create_simpleurl($cfg,$url){
		load::func('dir');
		// 删除目录：$cfg['url']
		$class_methods=NULL;
		if($cfg['url'] !== '' && $url != $cfg['url'] && is_dir(ROOT_PATH . $cfg['url'])){
			$class_methods=$this->get_plugin_methods($cfg);
			foreach($class_methods as $method){
				$mDir=ROOT_PATH . $cfg['url'] . $method . CD;
				if(is_file($mDir . 'index.php')){
					@unlink($mDir . 'index.php');
					dir_delete($mDir, 1);
				}
			}
			dir_delete(ROOT_PATH . $cfg['url'], 1);
		}
		
		// 如果已经存在目录，则不创建
		if($url !== '' && is_dir(ROOT_PATH . $url)){
			return false;
		}
		
		// 创建目录：$url
		if($url !== '' && ($cfg['url'] === '' || ($url != $cfg['url'] || !is_dir(ROOT_PATH . $url)))){
			if(is_null($class_methods)){
				$class_methods=$this->get_plugin_methods($cfg);
			}
			
			// 计算相对路径
			$arr_core=explode(CD, rtrim(CORE_PATH, CD));
			$arr_curr=explode(CD, str_replace('/', CD, ROOT_PATH . $url));
			foreach($arr_curr as $ky=>$vl){
				if($arr_curr[$ky] != $arr_core[$ky]){
					break;
				}else{
					unset($arr_core[$ky], $arr_curr[$ky]);
				}
			}
			$inc_path=str_repeat('../', count($arr_curr)) . implode('/', $arr_core);
			$sptor=str_repeat('../', substr_count($url, '/') + 1);
			$contents='<?php' . "\r\n" . '$_GET[\'plugin_c\']=\'' . $cfg['name'] . '\';$_GET[\'plugin_a\']=\'{plugin_a}\';' . "\r\n" . 'define(\'ROOT_URL\',\'' . ROOT_URL . '\');' . "\r\n" . 'define(\'ROOT_PATH\',dirname(__FILE__).DIRECTORY_SEPARATOR.\'' . $sptor . '\');' . "\r\n" . 'include \'' . $inc_path . '/base.php\';' . "\r\n" . 'load::app();' . "\r\n" . '?>';
			
			foreach($class_methods as $method){
				$mDir=ROOT_PATH . $url . $method . CD;
				if(!is_dir($mDir)){
					@mkdir($mDir, 0777, true);
				}
				if(is_dir($mDir)){
					file_put_contents($mDir . 'index.php', str_replace('{plugin_a}', $method, $contents));
				}
			}
		}
		return true;
	}

	private function get_plugin_methods($cfg){
		$name='plugin_' . $cfg['name'];
		include_once (PLUGIN_PATH . $cfg['install_dir'] . CD . $name . '.php');
		$class_methods=get_class_methods($name);
		foreach($class_methods as $k=>$v){
			if(strpos($v, '_') === 0 || strpos($v, 'm_') === 0){
				unset($class_methods[$k]);
			}
		}
		return $class_methods;
	}
}

?>