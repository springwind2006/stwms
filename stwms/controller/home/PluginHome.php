<?php
defined('IN_MYCMS') or exit('No permission resources.');
if(!defined('PLUGIN_PATH')){
	define('PLUGIN_PATH', CORE_PATH . 'plugin' . CD);
}
class PluginHome extends HomeController{

	public function __construct(){
		parent::__construct();
		$this->xmlDbName='plugin';
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
	
	/* * ******前台界面访问插件方法******* */
	public function call(){
		if($this->site['app']){
			$name=Param::get_para('plugin_c');
			$action=Param::get_para('plugin_a', 'index');
			if(strpos($action, 'm_') !== 0 && strpos($action, '_') !== 0){
				$controller=load::plugin($name, $cfg);
				if($controller && method_exists($controller, $action)){
					$GLOBALS[PLUGIN_ID]=$cfg['install_dir'];
					call_user_func(array($controller,$action), $this->site);
				}
			}
		}
	}

}

?>