<?php
defined('IN_MYCMS') or exit('No permission resources.');
!IS_RUNTIME && load::func('content');
class AdminController extends Controller{
	protected $db=null;
	protected $xmlDb=null;
	protected $xmlDbName=null;
	protected $xmlDbPath;
	public function __construct(){
		$this->xmlDbName=ROUTE_C;
		$this->xmlDbPath='core';
	}
	
	public function _default($c,$a){
		if(strtolower($c)=='admin' && $a){
			if(method_exists($this, $a)){
				$this->_AuthLogin($c,$a);
			}else{
				$this->showmessage('访问的方法不存在！', 'auto');
			}
		}
	}

	/**
	 * 权限检查
	 *
	 * @param unknown $c
	 * @param string $a
	 * @param string $actid
	 * @param string $roleid
	 * @return number 返回结果有三种，0-》没有登录；1-》已经登录且具有操作权限；2-》已经登录且但不具有操作权限
	 */
	public function _AuthLogin($c,$a){
		$isAuth=0;
		if(($c == 'admin' && $a == 'login') || ($c == 'api' && $a == 'code')){
			$isAuth=1; // 对用户管理系统，开放权限检查
		}else if(isset($_SESSION['roleid'])){
			$isAuth=($c == 'admin' || $c == 'api' || load::controller('admin.role')->_check_auth($c, $a, $actid, $roleid) ? 1 : 2); // 此处通过用户单项权限验证，通过权限
		}
		switch($isAuth){
			case 0:
				$this->login();
				break;
			case 1:
				call_user_func(array($this,$a));
				break;
			case 2:
				$this->showmessage('操作权限不足！', 'auto');
				break;
		}
	}
	
	// 系统首页
	public function index(){
		$uInfos=load::controller('user')->uinfo($_SESSION['userid']);
		@extract($uInfos);
		// 获取主菜单
		$mainMenu=load::controller('rolemenu')->_get_menu($_SESSION['roleid'], 'array');
		// 获取快捷菜单
		$fastMenu=load::controller('user')->_fastmenu($_SESSION['userid'], 'get');
		include template('index', 'admin');
	}
	
	// 系统主页
	public function init(){
		$uInfos=load::controller('user')->uinfo($_SESSION['userid']);
		@extract($uInfos);
		$helpPath=CORE_PATH . 'data' . CD . 'system' . CD . 'help.txt';
		$helptext=is_file($helpPath) ? nl2br(file_get_contents($helpPath)) : '';
		unset($helpPath);
		include template('init', 'admin');
	}

	public function map(){
		$menuArray=load::controller('rolemenu')->_get_menu($_SESSION['roleid'], 'array');
		include template('map', 'admin');
	}

	public function login(){
		load::controller('user')->_login(ROOT_URL . SYS_ENTRY . '?' . ADMIN_INI);
	}

	public function logout(){
		load::controller('user')->_logout(act_url('admin', 'login'));
	}

	public function getmenu(){
		$roleid=(isset($_GET['roleid']) ? $_GET['roleid'] : $_SESSION['roleid']);
		$type=(isset($_GET['type']) && $_GET['type'] == 'json' ? 'json' : 'html');
		echo load::controller('rolemenu')->_get_menu($roleid, $type);
	}

	public function fastmenu(){
		$actMethods=array('get','add','del');
		$method=isset($_GET['act']) && in_array($_GET['act'], $actMethods) ? $_GET['act'] : 'get';
		$menuid=isset($_GET['menuid']) ? $_GET['menuid'] : '';
		echo make_json(load::controller('user')->_fastmenu($_SESSION['userid'], $method, $menuid));
	}
	
	public function showmessage($message,$url_forward='goback',$ms=1250,$dialog='',$returnjs=''){
		include template('message', true);
		exit(0);
	}
	
	/**
	 * 获取系统数据库
	 *
	 * @param string $dbconn
	 * @param string $dbname
	 * @param string $dbpath
	 * @param number $isNew
	 * @return Ambigous <boolean, Ambigous>
	 */
	protected function getDb($dbconn=NULL,$dbname='',$dbpath='dbase',$isNew=0){
		if(is_null($this->db)){
			$this->db=load::db($dbconn,$dbname,$dbpath,$isNew);
		}
		return $this->db;
	}
	
	/**
	 * 获取系统内核数据库
	 *
	 * @param string $name
	 * @param string $path
	 * @param number $isNew
	 * @return Ambigous <boolean, Ambigous>
	 */
	protected function getXmlDb($name='',$path='',$isNew=0){
		if(is_null($this->xmlDb)){
			$dbName=($name=='' ? (is_null($this->xmlDbName) ? ROUTE_C : $this->xmlDbName) : $name);
			$dbPath=($path=='' ? (is_null($this->xmlDbPath) ? 'core' : $this->xmlDbPath) : $path);
			$this->xmlDb=load::db('xml', $dbName, $dbPath, $isNew);
		}
		return $this->xmlDb;
	}	
	
}

?>