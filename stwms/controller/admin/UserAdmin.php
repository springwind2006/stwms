<?php
defined('IN_MYCMS') or exit('No permission resources.');
class UserAdmin extends AdminController{
	public $uinfo=array();

	public function __construct(){
		parent::__construct();
		$this->xmlDbName='user';
	}

	public function init(){
		$data=getcache('user', 'users', 'array');
		foreach($data as $k=>$v){
			$data[$k]['username']=sys_auth($v['username'], 'DECODE');
		}
		$roles=getcache('role', 'users', 'array');
		$auths=load::controller('role')->_check_auth('add,edit,del,lock,listorder,uinfo,view');
		include template('init', 'user');
	}

	public function add(){
		if(isset($_GET['dosubmit'])){
			if(empty($_POST['info']['username']) || empty($_POST['info']['password'])){
				alert('添加用户信息失败！用户名或密码不能为空！');
			}else if($_POST['info']['password'] != $_POST['repassword']){
				alert('两次输入密码不一致！');
			}else{
				if($this->check($_POST['info']['username'])){
					alert('添加用户信息失败！已存在此用户名！');
				}else{
					$_POST['info']['username']=sys_auth($_POST['info']['username']);
					$_POST['info']['password']=sys_auth(md5($_POST['info']['password']));
					$insertId=$this->getXmlDb()->insert('user', $_POST['info']);
					if($insertId != -1){
						$this->_fresh();
					}
					alert('添加成功！');
				}
			}
		}else{
			$roles=getcache('role', 'users', 'array');
			if($_SESSION['roleid'] != 2 && $_SESSION['roleid'] != 1){
				unset($roles[2]);
			}
			foreach($roles as $k=>$v){
				$v['is_audit']=load::controller('role')->_check_auth('content', 'audit', 0, $k) ? 1 : 0;
				$roles[$k]=$v;
			}
			$audits=$this->_getAudit();
			include template('add', 'user');
		}
	}

	public function edit(){
		$id=intval(isset($_GET['id']) ? $_GET['id'] : $_POST['id']);
		$r=$this->uinfo($id);
		if(isset($_GET['dosubmit'])){
			if(empty($_POST['info']['username'])){
				alert('用户名不能为空！');
			}else if(!empty($_POST['info']['password']) && $_POST['info']['password'] != $_POST['repassword']){
				alert('两次输入密码不一致！');
			}else{
				if($r['username'] != $_POST['info']['username'] && $this->check($_POST['info']['username'])){
					unset($r);
					alert('修改用户信息失败！已存在此用户名！');
				}else{
					unset($r);
					$_POST['info']['username']=sys_auth($_POST['info']['username']);
					if(empty($_POST['info']['password'])){
						unset($_POST['info']['password']);
					}else{
						$_POST['info']['password']=sys_auth(md5($_POST['info']['password']));
					}
					
					// 不能对超级管理员或当前登录用户进行权限设置
					if($id == 1 || $id === $_SESSION['userid']){
						unset($_POST['info']['roleid']);
					}
					$_POST['info']['audit']=intval($_POST['info']['audit']);
					if($this->getXmlDb()->update('user', $_POST['info'], 'where `id`=' . $id)){
						$this->_fresh();
						alert('修改成功！');
					}else{
						alert('修改失败！');
					}
				}
			}
		}else{
			if($r)
				extract($r);
			$roles=getcache('role', 'users', 'array');
			if($_SESSION['roleid'] != 2 && $_SESSION['roleid'] != 1){
				unset($roles[2]);
			}
			foreach($roles as $k=>$v){
				$v['is_audit']=load::controller('role')->_check_auth('content', 'audit', 0, $k) ? 1 : 0;
				$roles[$k]=$v;
			}
			$audits=$this->_getAudit();
			include template('edit', 'user');
		}
	}

	public function del(){
		$id=intval($_GET['id']);
		if($id && $id != 1 && $id != $_SESSION['userid']){
			if($this->getXmlDb()->delete('user', 'where `id`=' . $id)){
				$this->_fresh();
				$this->showmessage('删除操作成功！');
			}else{
				$this->showmessage('删除操作失败！');
			}
		}else{
			$this->showmessage('删除操作错误！');
		}
	}

	public function lock(){
		$id=intval($_GET['id']);
		if($id && $id != 1 && $id != $_SESSION['userid']){
			if($this->getXmlDb()->update('user', array('lock' => intval($_GET['lock'])), 'where `id`=' . $id)){
				$this->_fresh();
				$this->showmessage('操作成功！');
			}else{
				$this->showmessage('操作失败！');
			}
		}else{
			$this->showmessage('操作错误！');
		}
	}

	public function listorder(){
		if(isset($_POST['dosubmit'])){
			$isSuccess=0;
			foreach($_POST['listorders'] as $id=>$listorder){
				$isSuccess=$this->getXmlDb()->update('user', array('listorder' => $listorder), 'where `id`=' . $id);
			}
			if($isSuccess){
				$this->_fresh();
				$this->showmessage('排序操作成功！');
			}else{
				$this->showmessage('排序操作失败！');
			}
		}else{
			$this->showmessage('排序操作失败！');
		}
	}

	/**
	 * *
	 * 功能:获取根据id获取用户信息,提供$id变量则供内部调用，返回数组，否则供外部调用；
	 */
	public function uinfo($id=''){
		$userid=is_numeric($id) ? intval($id) : $_SESSION['userid'];
		if($userid){
			$roles=getcache('role', 'users', 'array');
			$uinfos=$this->getXmlDb()->getOne('user', '*', 'where `id`=' . $userid);
			$uinfos['username']=sys_auth($uinfos['username'], 'DECODE');
			$uinfos['rolename']=$roles[$uinfos['roleid']]['name'];
			$uinfos['userid']=$userid;
		}else{
			$uinfos=array();
			$creator=load::cfg('admin', 'creator');
			$uinfos['username']=sys_auth($creator['username'], 'DECODE');
			$uinfos['rolename']='系统创世人';
			$uinfos['roleid']=1;
			$uinfos['userid']=0;
		}
		if($id !== ''){
			return $uinfos;
		}else{
			@extract($uinfos);
			include template('uinfo', 'user');
		}
	}

	public function view(){
		$data=$this->getXmlDb()->select('user', '*', 'where `id`>0 and `roleid`=' . intval($_GET['roleid']), 'order by `listorder` asc');
		foreach($data as $k=>$vl){
			$data[$k]['username']=sys_auth($vl['username'], 'DECODE');
		}
		$roles=getcache('role', 'users', 'array');
		include template('view', 'user');
	}

	/* 外部接口方法 */
	public function _fresh(){
		$data=$this->getXmlDb()->select('user', '*', 'where `id`>0', 'order by `listorder` asc');
		$reData=array();
		foreach($data as $v){
			$reData[$v['id']]=$v;
		}
		unset($data);
		setcache('user', $reData, 'users', 'array');
	}
	
	// 用登录登陆
	public function _login($url){
		if(isset($_GET['dosub'])){
			if(isset($_SESSION['userid']) && isset($_SESSION['roleid']) && isset($_SESSION['iscreator'])){
				// 锁屏解锁
				$userinfos=$this->uinfo($_SESSION['userid']);
				if(!empty($_GET['password']) && $status=$this->check($userinfos['username'], $_GET['password'])){
					die($status != 2 ? '1' : '0');
				}
				die('0');
			}
			if(!isset($_SESSION['maxloginfailedtimes'])){
				$maxloginfailedtimes=load::cfg('system', 'maxloginfailedtimes');
				$_SESSION['maxloginfailedtimes']=$maxloginfailedtimes;
			}
			
			if($_SESSION['maxloginfailedtimes'] <= 0){
				$system=load::cfg('system');
				if(!isset($_SESSION['minrefreshtime'])){
					$_SESSION['minrefreshtime']=time();
				}
				if(time() - $_SESSION['minrefreshtime'] >= $system['minrefreshtime']){
					$_SESSION['maxloginfailedtimes']=$system['maxloginfailedtimes'];
					unset($_SESSION['minrefreshtime']);
				}else{
					$tryAfterTime=$system['minrefreshtime'] - (time() - $_SESSION['minrefreshtime']);
					die('{"msg":"登录已锁定！请于' . timeformat($tryAfterTime) . '后重试","code":"1"}');
				}
			}
			$_SESSION['maxloginfailedtimes']--;
			
			if(strtolower($_SESSION['checkcode']) != strtolower($_GET['checkcode'])){
				echo '{"msg":"验证码错误!","code":"1"}';
			}else if(empty($_GET['username'])){
				echo '{"msg":"请输入用户名！","code":"1"}';
			}else if(empty($_GET['password'])){
				echo '{"msg":"请输入密码！","code":"1"}';
			}else if($status=$this->check($_GET['username'], $_GET['password'])){
				if($status == 2){
					echo '{"msg":"此用户已经锁定！","code":"1"}';
				}else{
					if(!$this->uinfo['iscreator']){
						if($this->getXmlDb()->update('user', array('ip' => getIP(),'time' => time(),'lastip' => $this->uinfo['ip'],'lasttime' => $this->uinfo['time']), 'where `id`=' . $this->uinfo['id'])){
							$this->_fresh();
						}
					}
					$_SESSION['userid']=intval($this->uinfo['id']);
					$_SESSION['roleid']=intval($this->uinfo['roleid']);
					$_SESSION['iscreator']=$this->uinfo['iscreator'];
					unset($_SESSION['maxloginfailedtimes'], $_SESSION['minrefreshtime']);
					echo '{"msg":"登录成功！","code":"0","url":"' . $url . '"}';
				}
			}else{
				echo '{"msg":"登录失败！' . ($_SESSION['maxloginfailedtimes'] ? '还剩' . $_SESSION['maxloginfailedtimes'] . '次尝试机会' : '') . '","code":"2"}';
			}
		}else{
			$this->_logout();
			include template('login', 'user');
		}
	}

	public function _logout($url=''){
		unset($_SESSION['userid']);
		unset($_SESSION['roleid']);
		unset($_SESSION['iscreator']);
		if($url){
			header('location:' . $url);
		}
	}

	public function _fastmenu($userid=0,$act='get',$menuid=''){
		if($userid === 0){
			return false;
		}
		switch($act){
			case 'get':
				$fastMenuInfo=$this->getXmlDb()->getOne('user', 'id,roleid,fast', 'where `id`=' . $userid);
				$fastMenu=empty($fastMenuInfo['fast']) ? array() : explode('|', $fastMenuInfo['fast']);
				if(!empty($fastMenu)){
					$allMenus=load::controller('rolemenu')->_get_menu($fastMenuInfo['roleid'], 'list');
					$refastMenu=array();
					foreach($fastMenu as $mid){
						if(isset($allMenus[$mid])){
							$refastMenu[]=$allMenus[$mid];
						}
					}
				}
				return $refastMenu;
				break;
			case 'add':
				if(!empty($menuid)){
					$fastMenuInfo=$this->getXmlDb()->getOne('user', 'id,fast', 'where `id`=' . $userid);
					$fastMenu=empty($fastMenuInfo['fast']) ? array() : explode('|', $fastMenuInfo['fast']);
					if(is_array($_GET['menuid'])){
						$fastMenu=array_merge($fastMenu, $_GET['menuid']);
					}else{
						$fastMenu[]=intval($_GET['menuid']);
					}
					$fastMenu=array_unique($fastMenu);
					$this->getXmlDb()->update('user', array('fast' => implode('|', $fastMenu)), 'where `id`=' . $userid);
					$this->_fresh();
				}
				return $this->_fastmenu($userid, 'get');
				break;
			case 'del':
				if(!empty($menuid)){
					$fastMenuInfo=$this->getXmlDb()->getOne('user', 'id,fast', 'where `id`=' . $userid);
					$fastMenu=empty($fastMenuInfo['fast']) ? array() : explode('|', $fastMenuInfo['fast']);
					
					$newFastMenu=array();
					foreach($fastMenu as $mid){
						if((is_array($_GET['menuid']) && !in_array($mid, $_GET['menuid'])) || (!is_array($_GET['menuid']) && $mid != $_GET['menuid'])){
							$newFastMenu[]=$mid;
						}
					}
					$newFastMenu=array_unique($newFastMenu);
					$this->getXmlDb()->update('user', array('fast' => implode('|', $newFastMenu)), 'where `id`=' . $userid);
					$this->_fresh();
				}
				return $this->_fastmenu($userid, 'get');
				break;
		}
	}

	/**
	 * *******内部私有方法*********
	 */
	private function check($user,$pass=''){
		$creator=load::cfg('admin', 'creator');
		if(empty($pass)){
			if($user === sys_auth($creator['username'], 'DECODE')){
				return 1;
			}
		}else{
			if($user === sys_auth($creator['username'], 'DECODE') && md5($pass) === sys_auth($creator['password'], 'DECODE')){
				$this->uinfo=array('id' => 0,'roleid' => 1,'iscreator' => 1);
				return 1;
			}
		}
		
		$data=getcache('user', 'users', 'array');
		foreach($data as $v){
			if(empty($pass)){
				if($user === sys_auth($v['username'], 'DECODE')){
					return 1;
				}
			}else{
				if($user === sys_auth($v['username'], 'DECODE') && md5($pass) === sys_auth($v['password'], 'DECODE')){
					$v['iscreator']=0;
					$this->uinfo=$v;
					return $v['lock'] ? 2 : 1;
				}
			}
		}
		return 0;
	}

	public function _getAudit($userid=NULL){
		if(is_null($userid)){
			return array(0 => '无',1 => '初审',2 => '复审',3 => '终审');
		}else if($userid == -1 || $userid == 1){
			return 3;
		}else{
			$users=getcache('user', 'users', 'array');
			return $users[$userid]['audit'];
		}
	}
}

?>