<?php
class Application{
	public function __construct(){
		load::cls('Controller',0);
		$param=load::cls('Param');
		$admin=load::cfg('admin');

		// 检查是否进入管理系统模块
		Param::check_para($admin['ini'])&&$admin['url']===SYS_ENTRY&&is_dir(CORE_PATH.'controller'.CD.$admin['class'])&&define('ADMIN_INI', $admin['ini'])&&session_start();
		
		//检查是否为应用类型请求，例如：请求的URL为/?test.html情况则返回test，为直接模版调用方式
		$res=$param->check();
		
		//设置应用配置
		$this->setConfig();

		//应用初始化
		$this->init($res);
	}
	
	/**
	 * 应用初始化
	 */
	private function init($res){
		$isca=($res === true);
		$m=defined('STYLE_MODULE') ? STYLE_MODULE : ROUTE_M;
		$c=$isca ? ROUTE_C : $res;
		$a=$isca ? ROUTE_A : '';
		$is_base=load::module($m);
		
		// 钩子设置
		load::hook(defined('ADMIN_INI') ? 'init' : 'start',$m,$c,$a);
		
		//调用控制器方法，控制器方法不能以“_”开头
		if($isca&&($controller=load::controller($c)) && strpos($a, '_') !== 0 && method_exists($controller, $a)){
			defined('ADMIN_INI') ? $controller->_AuthLogin($c,$a) : $controller->$a();
			return true;
		}
		
		//使用模块的默认控制器处理请求
		if($is_base){
			$mc=ucfirst(strtolower($m)).'Controller';
			$controller=new $mc();
			method_exists($controller, '_default') && $controller->_default($c,$a);
		}
		return false;
	}
	
	
	/**
	 * 初始化系统模块配置
	 */
	private function setConfig(){
		//设置错误处理函数
		C('errorlog') ? set_error_handler('my_error_handler') : error_reporting(APP_DEBUG ? E_ALL^E_NOTICE^E_WARNING^E_STRICT : E_ERROR|E_PARSE);
		//设置本地时差
		function_exists('date_default_timezone_set')&&date_default_timezone_set(C('timezone'));
		
		//配置应用常量
		if(defined('ADMIN_INI')){
			define('ADMIN_TEMPLATE_PATH', CORE_PATH . 'template' . CD . C('admin.class') . CD . C('style') . CD);
			define('ADMIN_STATIC_URL', STATIC_URL . C('admin.class') . '/' . C('style') . '/');
			define('ADMIN_STATIC_PATH', STATIC_PATH . C('admin.class') . CD . C('style') . CD);
		}else{
			self::setStyle();
		}
		
		//定义是否为ajax请求
		define('IS_AJAX',((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty(Param::get_para(load::cfg('system','VAR_AJAX_SUBMIT','ajax')))) ? true : false);
	}
	
	/**
	 * 设置除管理模块外的模块名称和风格
	 * 
	 * @param number $isDynamic
	 */
	public static function setStyle($isDynamic=true){
		!defined('STYLE_MODULE') && define('STYLE_MODULE',defined('BIND_MODULE') ? BIND_MODULE : ($isDynamic ? Param::get_para('m',DEFAULT_MODULE):DEFAULT_MODULE));
		if(!defined('STYLE_URL')){
			$system_static=load::cfg('system', 'static');
			define('STYLE_URL', strpos($system_static, 'http://') === 0 ? $system_static : STATIC_URL . STYLE_MODULE .'/' . $system_static);
		}
		//自动加载函数模块函数
		!IS_RUNTIME && load::func('common_'.STYLE_MODULE);
	}
}
?>