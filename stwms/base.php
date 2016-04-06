<?php

/* 【定义网站基础常量】 */
function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT-8');/* 设置时区 */
define('IN_MYCMS',true);
define('SYS_START_TIME',microtime());/* 设置系统开始时间 */
define('NOW_TIME',$_SERVER['REQUEST_TIME']);/* 设置此次请求时间 */
define('PLUGIN_ID','CPID_'.NOW_TIME); /* 记录当前运行插件安装目录为标识，运行插件方法生成全局变量$GLOBALS[PLUGIN_ID]，插件运行完成即销毁 */
!defined('APP_DEBUG')&&define('APP_DEBUG',true);/* 系统默认在开发模式下运行 */
define('DEFAULT_MODULE', 'home');/*系统前端默认模块*/
define('IS_RUNTIME',!APP_DEBUG && defined('BIND_MODULE'));

// 定义当前请求的系统常量
isset($_SERVER['REQUEST_METHOD'])&&define('REQUEST_METHOD',$_SERVER['REQUEST_METHOD']);
defined('REQUEST_METHOD')&&define('IS_GET',REQUEST_METHOD =='GET' ? true : false);
defined('REQUEST_METHOD')&&define('IS_POST',REQUEST_METHOD =='POST' ? true : false);

/* 【定义客户端访问路径】 */
define('SITE_PROTOCOL',(isset($_SERVER['SERVER_PORT'])&&$_SERVER['SERVER_PORT']=='443' ? 'https://' : 'http://'));
define('SITE_PORT', (isset($_SERVER['SERVER_PORT'])&&$_SERVER['SERVER_PORT']!='80' ? ':'.$_SERVER['SERVER_PORT'] : ''));
define('SITE_URL',SITE_PROTOCOL.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'')).SITE_PORT);/* 网站首页地址 */
define('ROOT_FULL_URL',isset($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:substr($_SERVER['PHP_SELF'],0,stripos($_SERVER['PHP_SELF'], '.php')+3));
!defined('ROOT_URL') && define('ROOT_URL',dirname(ROOT_FULL_URL));
define('SYS_ENTRY', basename(ROOT_FULL_URL));
define('UPLOAD_URL',ROOT_URL.'ufs/');/* 上传图片访问路径 */
define('STATIC_URL',ROOT_URL.'statics/');/* 静态文件路径 */
define('SYS_PLUGIN_URL',STATIC_URL.'common/plugins/');/* 系统使用外部插件路径，可以使用来自其它域的插件目录 */

/* 【定义服务器端路径】 */
define('CD',DIRECTORY_SEPARATOR);/* 简化目录分割符 */
define('CORE_PATH',dirname(__FILE__).CD);/* 框架目录 */
!defined('ROOT_PATH') && define('ROOT_PATH',CORE_PATH.'..'.CD);/* 网站根目录路径 */
define('CACHE_PATH',CORE_PATH.'___cache___'.CD);/* 本CMS系统临时文件路径 */
define('UPLOAD_PATH',ROOT_PATH.'ufs'.CD);/* 文件上传目录路径 */
define('STATIC_PATH',ROOT_PATH.'statics'.CD);/* 静态文件服务端访问路径 */

!IS_RUNTIME && load::func('system') && load::func('common');

/**
 * 模块编译函数
 *
 * @param string $module 模块名称
 * @return string
 */
function compile($module){
	$module=strtolower($module);
	$runtime_file=CACHE_PATH.$module.'_runtime.php';
	if(!is_file($runtime_file)){
		$defaults=array(
				'lib/function/system.func.php',
				'lib/function/common.func.php',
				'lib/function/common_'.$module.'.func.php',
				'lib/class/Application.class.php',
				'lib/class/Param.class.php',
				'lib/class/Controller.class.php',
				'lib/class/View.class.php',
				'controller/'.$module.'/'.ucfirst($module).'Controller.php',
		);
		$mconfigs=load::cfg('compile',$module);
		if(is_array($mconfigs)){
			$defaults=array_merge($defaults,$mconfigs);
		}
		$defaults=array_unique($defaults);

		$_content='';
		foreach ($defaults as $file){
			if(is_file(CORE_PATH.$file)){
				$content=trim(substr(php_strip_whitespace(CORE_PATH.$file), 5));
				if ('?>' == substr($content, -2)){
					$content = substr($content, 0, -2);
				}
				$_content.=$content;
			}
		}

		$cfg_file=CORE_PATH.'config'.CD.'app_'.$module.'.cfg.php';
		$config=include(CORE_PATH.'config'.CD.'system.cfg.php');
		if(is_file($cfg_file)){
			$config=array_merge($config,include($cfg_file));
		}
		$_content.="\r\nload::cfg('system',".var_export($config,true).');';
		$_content.="\r\nload::cfg('admin',".var_export(load::cfg('admin'),true).');';
		$_content.="\r\nload::cfg('route',".var_export(load::cfg('route'),true).');';
		!is_dir(CACHE_PATH)&&mkdir(CACHE_PATH,0777,true);
		file_put_contents($runtime_file, '<?php '.$_content);
	}
	return $runtime_file;
}

class load {

	//初始化应用程序
	public static function app(){
		IS_RUNTIME && include(compile(BIND_MODULE));
		return self::cls('Application');
	}
	
	/** 
	 * 加载数据库对象
	 * 可以用以下方式使用：
	 * 方式1：($dbConn array 配置数组,$isNew=1)
	 * 方式2：($dbConn string 配置名称,$dbName string,$isNew=1)
	 * 方式3：($dbConn string 配置名称,$dbName string,$dbPath='dbase' string,$isNew=1)
	 * 
	 * @param multitype $dbConn 数据库类型的字符串或配置数组
	 * @param string $dbName 数据库名称（文件型数据库为文件名前缀）
	 * @param string $dbPath 数据库目录，文件型数据库特有
	 * @param number $isNew 初始化类型，默认为0，为1则表示不使用缓存，返回为新的对象
	 * @return boolean|Ambigous <boolean, DbDriver, multitype:>
	 */
	public static function db($dbConn=NULL,$dbName='',$dbPath='dbase',$isNew=0){
		include_once CORE_PATH.'lib'.CD.'driver'.CD.'Db.class.php';
		
		if($dbConn===0){
			return false;
		}
		if($dbConn=='xml'){
			//加载xml数据库，按照标准参数
			$dsn=is_file($dbName) ? $dbName : (CORE_PATH.'data'.CD.(empty($dbPath) ? 'core' : $dbPath).CD.$dbName.'.php');
			return Db::load(array('type'=>$dbConn,'DSN'=>$dsn),$isNew);
		}else{
			$config=NULL;
			$isNew=is_int($dbName) ? $dbName : (is_int($dbPath) ? $dbPath : $isNew);
			if(empty($dbConn)||(is_array($dbConn)&&!isset($dbConn['type']))){
				$db_conn=load::cfg('system','db_conn');
				$config=load::cfg('database',$db_conn);
			}else if(is_array($dbConn)&&isset($dbConn['type'])){
				$config=&$dbConn;
			}else{
				$config=load::cfg('database',$dbConn);
				if(!$config){
					return false;
				}
			}

			if(in_array($config['type'],array('access','sqlite2','sqlite3'))&&!is_file($config['DSN'])){
				$config['DSN']=CORE_PATH.'data'.CD.(empty($dbPath) ? 'dbase' : $dbPath).CD.(empty($dbName) ? $config['DSN'] : $dbName).'_'.$config['type'].'.php';
			}

			return Db::load($config,$isNew);
		}
	}
	

	/**
	 * 加载模块的基础控制器
	 * 
	 * @param string $name
	 * @param number $initialize
	 * @return Ambigous
	 */
	public static function module($name,$initialize=0){
		$name=strtolower($name);
		$path='controller'.CD.$name;
		$name=ucfirst($name).'Controller';
		return self::cls($name,$initialize,NULL,$path,'');
	}
	
	/**
	 * 加载控制器
	 * 可以指定模块，以“.”分割：“模块名.控制器”
	 * 
	 * @param string $name 类名称
	 * @param number $initialize 是否初始化
	 * @param string $para 传递给类初始化的参赛
	 * @return Ambigous
	 */
	public static function controller($name,$initialize=1,$para=NULL){
		if($pos=strpos($name, '.',1)){
			$m=substr($name, 0 , $pos);
			$path='controller'.CD.strtolower($m); //获取模块路径
			$name=ucfirst(strtolower(substr($name, $pos+1))).ucfirst(strtolower($m)); //获取控制器
			self::module($m); //如果为不同模块，则加载模块
		}else{
			$default_m=defined('STYLE_MODULE') ? STYLE_MODULE : ROUTE_M;
			$path='controller'.CD.strtolower($default_m); //获取模块路径
			$name=ucfirst(strtolower($name)).ucfirst(strtolower($default_m));
		}
		return self::cls($name,$initialize,$para,$path,'');
	}

	
	/**
	 * 加载插件
	 * 
	 * @param unknown $name
	 * @param string $config
	 * @return Ambigous|boolean
	 */
	public static function plugin($name,&$config=NULL){
		$plugins=& getLcache('plugin','core','array','map');
		if(isset($plugins[$name])){
			$config=$plugins[$name];
			return self::cls('plugin_'.$name,1,$config,'plugin'.CD.$config['install_dir'],'');
		}
		return false;
	}
	
	/**
	 * 加载类文件函数
	 * 
	 * @param string $name 类名
	 * @param number $initialize 是否初始化
	 * @param string $para 传递给初始化的类构造函数的参数
	 * @param string $path 路径，默认相对于lib/class
	 * @param string $type 类型，默认为.class
	 * @return Ambigous <>|boolean|Ambigous <Ambigous <boolean, unknown>>
	 */
	public static function cls($name,$initialize=1,$para=NULL,$path='',$type='.class'){
		static $classes=array();
		$path=empty($path) ? 'lib'.CD.'class' : $path;

		$key=md5($path.$name);
		if(isset($classes[$key])){
			if(!empty($classes[$key])){
				return $classes[$key];
			}else{
				return true;
			}
		}
		
		try{
			if(!class_exists($name,false)){
				$inc_path=CORE_PATH.$path.CD.(is_file(CORE_PATH.$path.CD.'MY_'.$name.$type.'.php') ? 'MY_' : '').$name.$type.'.php';
				if(!is_file($inc_path)){
					return false;
				}
				include $inc_path;
			}
			
			$classes[$key]=$initialize ? (is_null($para) ? new $name : new $name($para)) : true;
			return $classes[$key];		
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * 加载函数库
	 * 
	 * @param string $func 函数库名
	 * @param string $path 路径
	 * @return boolean
	 */
	public static function func($func,$path=''){
		static $funcs=array();
		$path=(empty($path) ? 'lib'.CD.'function' : $path).CD.$func.'.func.php';
		$key=md5($path);
		if(isset($funcs[$key])){
			return true;
		}
		if(is_file(CORE_PATH.$path)){
			try{
				include CORE_PATH.$path;
			}catch(Exception $e){
				return false;
			}
		}else{
			$funcs[$key]=false;
			return false;
		}
		$funcs[$key]=true;
		return true;
	}

	/**
	 * 加载配置文件
	 * 
	 * @param string $file 配置文件
	 * @param string $key 要获取的配置键值
	 * @param string $default 默认配置，当获取配置项目失败时该值发生作用
	 * @param string $reload 强制重新加载
	 * @return Ambigous <>|string
	 */
	public static function cfg($file,$key='',$default='',$reload=false){
		static $configs=array();
		
		//如果为第二个参数为数组则直接写入配置
		if(is_array($key)){
			$configs[$file]=isset($configs[$file]) ? array_merge($configs[$file],$key) : $key;
			return null;
		}
		
		if(!$reload&&isset($configs[$file])){
			if(empty($key)){
				return $configs[$file];
			}elseif(isset($configs[$file][$key])){
				return $configs[$file][$key];
			}else{
				return $default;
			}
		}
		$path=CORE_PATH.'config'.CD.$file.'.cfg.php';
		if(is_file($path)){
			try{
				$configs[$file]=include $path;
				if($file=='system'){
					$default_m=defined('STYLE_MODULE') ? STYLE_MODULE : ROUTE_M;
					$mpath=CORE_PATH.'config'.CD.'app_'.$default_m.'.cfg.php';
					if(is_file($mpath)){
						$configs[$file]=array_merge($configs[$file],include($mpath));
					}
				}
			}catch(Exception $e){
				return $default;
			}
		}
		
		if(empty($key)){
			return $configs[$file];
		}elseif(isset($configs[$file][$key])){
			return $configs[$file][$key];
		}else{
			return $default;
		}
	}
	
	/**
	 * 加载系统钩子
	 * 
	 * @param string $name 钩子名称
	 * @param mixed $datas 传递参数
	 */
	public static function hook($name){
		$hooks=& getLcache('hook','core','array','map');
		if($hooks&&isset($hooks[$name])){
			$datas=func_get_args();
			unset($datas[0]);
			foreach(explode(',',$hooks[$name]) as $hook){
				$hookArr=explode('.',$hook);
				$pluginObj=self::plugin($hookArr[0]);
				if($pluginObj&&method_exists($pluginObj,$hookArr[1])){
					call_user_func(array($pluginObj,$hookArr[1]),$datas);
				}
			}
		}
		unset($hooks,$hook,$hookArr);
	}

}

?>