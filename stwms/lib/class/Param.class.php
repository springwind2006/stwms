<?php
class Param{
	private $route_config='';

	public function __construct(){
		if(get_magic_quotes_gpc()){
			$_POST=slashes($_POST, 0);
			$_GET=slashes($_GET, 0);
			$_REQUEST=slashes($_REQUEST, 0);
			$_COOKIE=slashes($_COOKIE, 0);
		}
		$this->route_config=load::cfg('route', SITE_URL) ? load::cfg('route', SITE_URL) : load::cfg('route', 'default');
		if($sessionid=self::get_para('PHPSESSID')){
			session_id($sessionid);
		}
		if(isset($this->route_config['param'])){
			if(is_array($this->route_config['param'])){
				$_GET=array_merge($this->route_config['param'],$_GET);
			}else{
				parse_str($this->route_config['param'],$param);
				$_GET=array_merge($param,$_GET);
			}
		}
		
		return true;
	}
	
	// 检查路由参数是否匹配
	public function check(){
		$c=$this->get_para('c');
		$a=$this->get_para('a');
		
		/*
		 * 注：c和a参数具有优先权，只有同时不提供c和a参数，且提供了plugin_c参数时， 才会自动将c设置为plugin，将a设置为call；
		 */
		if(empty($c) && empty($a) && $this->get_para('plugin_c')){
			$c='plugin';
			$a=defined('ADMIN_INI') ? 'admin' : 'call';
		}else if(defined('ADMIN_INI')){
			empty($c) && ($c='admin');
			empty($a) && ($a='index');
		}
		
		//定义模块变量
		define('ROUTE_M', defined('ADMIN_INI') ? load::cfg('admin','class') : (defined('BIND_MODULE') ? BIND_MODULE : $this->get_para('m', DEFAULT_MODULE)));
		!defined('ADMIN_INI') && load::cfg('admin','class')==ROUTE_M && exit('No permission resources.');
		
		
		// 直接处理类似/index.php?message.html的URL，message.html为模板名称
		$spage='';
		if(empty($c) && empty($a)){
			foreach($_GET as $k=>$v){
				$rPos=strripos($k, '_html');
				if($rPos !== false && strtolower(substr($k, $rPos)) == '_html'){
					$spage=substr($k, 0, $rPos);
				}
			}
		}
		if(empty($spage)){
			define('ROUTE_C', defined('BIND_CONTROLLER') ? BIND_CONTROLLER : $this->route_c($c));
			define('ROUTE_A', defined('BIND_ACTION') ? BIND_ACTION : $this->route_a($a));
			return true;
		}else{
			return $spage;
		}
	}
	
	// 设置访问类
	public function route_c($c=NULL){
		$c=is_null($c) ? self::get_para('c') : $c;
		if(empty($c)){
			return $this->route_config['c'];
		}else{
			return $c;
		}
	}
	// 设置访问方法
	public function route_a($a=NULL){
		$a=is_null($a) ? self::get_para('a') : $a;
		if(empty($a)){
			return $this->route_config['a'];
		}else{
			return $a;
		}
	}
	
	// 获取参数，非空值
	public static function get_para($para,$default=''){
		return isset($_GET[$para]) && !empty($_GET[$para]) ? $_GET[$para] : (isset($_POST[$para]) && !empty($_POST[$para]) ? $_POST[$para] : $default);
	}
	
	//检查参数是否存在
	public static function check_para($para){
		return isset($_GET[$para]) || isset($_POST[$para]);
	}
}
?>