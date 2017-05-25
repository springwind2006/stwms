<?php
defined('IN_MYCMS') or exit('No permission resources.');
class RolemenuAdmin extends AdminController{
	var $roleid=-1;
	var $role=NULL;

	public function __construct(){
		parent::__construct();
		$this->roleid=intval(isset($_GET['roleid']) ? $_GET['roleid'] : 0);
	}

	public function init(){
		$roles=getcache('role', 'users', 'array');
		$rolename=$roles[$this->roleid]['name'];
		$allMenus=$this->getRole()->_get_menu($this->roleid);
		$tree=load::cls('Tree');
		$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp='&nbsp;&nbsp;&nbsp;';
		$tree->init($allMenus);
		$categorys=$tree->get_array(0);
		$auths=load::controller('role')->_check_auth('role.init,add,edit,del,move,resetmenu,display,listorder');
		include template('init', 'rolemenu');
	}

	public function add(){
		if(isset($_POST['dosubmit']) || isset($_GET['dosubmit'])){
			$_POST['info']['name']=safe_replace($_POST['info']['name']);
			if(empty($_POST['info']['name'])){
				alert('请输入菜单名称！');
			}else{
				if($_POST['info']['type'] == 2){
					$url=trim($_POST['info']['url']);
					if(empty($url)){
						alert('请输入外链地址！');
					}else if(!preg_match("/^http:\/\//i", $url)){
						$url='http://' . $url;
					}
					$_POST['info']['data']=$url;
					unset($_POST['info']['c'], $_POST['info']['a'], $_POST['info']['url']);
				}
				
				$_POST['info']['listorder']=intval($_POST['info']['listorder'] ? $_POST['info']['listorder'] : 0);
				$res=$this->getOpXmlDb()->insert('menu', $_POST['info']);
				if($res != -1){
					$this->_fresh();
					alert('添加成功！');
				}else{
					alert('添加失败！');
				}
			}
		}else{
			$tree=load::cls('Tree');
			$result=getcache('rolemenu', 'menu', 'array', $this->roleid);
			$tree->init($result);
			$select_categorys=$tree->get_array(0);
			
			$allUsableMenus=$this->getRole()->_get_menu($this->roleid, 1);
			foreach($allUsableMenus as $k=>$v){
				$allUsableMenus[$k]['isused']=$this->checkUsed($v, $result);
			}
			$tree->init($allUsableMenus);
			$usable_menus=$tree->get_array(0);
			load::func('file');
			$icons=file_list(ADMIN_STATIC_PATH . 'images' . CD . 'icon' . CD);
			include template('add', 'rolemenu');
		}
	}

	public function edit(){
		if(isset($_GET['dosubmit'])){
			$id=intval($_GET['id']);
			$res=$this->getOpXmlDb()->update('menu', $_POST['info'], 'where `id`=' . $id);
			if($res != 0){
				$this->_fresh();
				alert('修改成功！');
			}else{
				alert('修改失败！');
			}
		}else{
			$tree=load::cls('Tree');
			$id=intval($_GET['id']);
			$info=$this->getOpXmlDb()->getOne('menu', '*', 'where `id`=' . $id);
			if($info)
				extract($info);
			
			$result=$this->getOpXmlDb()->select('menu', '*', 'where `id`!=' . $id . ' and `pid`!=' . $id, 'order by `listorder` desc,`id` asc');
			$tree->init($result);
			$select_categorys=$tree->get_array(0);
			load::func('file');
			$icons=file_list(ADMIN_STATIC_PATH . 'images' . CD . 'icon' . CD);
			include template('edit', 'rolemenu');
		}
	}

	public function del(){
		if(!is_array($_GET['id'])){
			$_GET['id']=intval($_GET['id']);
			$hasChild=$this->getOpXmlDb()->count('menu', 'where `pid`=' . $_GET['id']);
			if(!$hasChild && $this->getOpXmlDb()->delete('menu', 'where `id`=' . $_GET['id'])){
				$this->_fresh();
				$this->showmessage('删除操作成功！');
			}else{
				$this->showmessage('删除操作失败！该菜单下面有子菜单！');
			}
		}else{
			$mCount=0;
			$this->getOpXmlDb()->trans('start');
			foreach($_GET['id'] as $id){
				$mCount+=$this->getOpXmlDb()->delete('menu', 'where `id`=' . $id);
			}
			if($mCount){
				$this->getOpXmlDb()->trans('end');
				$this->_fresh();
				alert('1');
			}
			alert('0');
		}
	}

	public function display($templateFile='',$charset='',$contentType='',$content=''){
		$id=intval($_GET['id']);
		$res=$this->getOpXmlDb()->update('menu', array('display' => intval($_GET['display'])), 'where `id`=' . $id);
		if($res != 0){
			$this->_fresh();
			$this->showmessage('操作成功！{s}秒后返回', $_SERVER['HTTP_REFERER']);
		}else{
			$this->showmessage('操作失败！{s}秒后返回', $_SERVER['HTTP_REFERER']);
		}
	}

	public function listorder(){
		if(isset($_POST['dosubmit'])){
			$this->getOpXmlDb()->trans('start');
			foreach($_POST['listorders'] as $id=>$listorder){
				$this->getOpXmlDb()->update('menu', array('listorder' => intval($listorder)), 'where `id`=' . $id);
			}
			$this->getOpXmlDb()->trans('end');
			$this->_fresh();
			$this->showmessage('排序操作成功！');
		}else{
			$this->showmessage('排序操作失败！');
		}
	}

	public function move(){
		$pid=intval($_GET['pid']);
		if(is_array($_GET['id'])){
			$arr=getcache('rolemenu', 'menu', 'array', $this->roleid);
			$idstr='';
			$mCount=0;
			$this->getOpXmlDb()->trans('start');
			foreach($_GET['id'] as $cid){
				$id=intval($cid);
				if($id != $pid && !in_array($pid, get_childs($id, $arr, false))){
					$mCount+=$this->getOpXmlDb()->update('menu', array('pid' => $pid), 'where `id`=' . $id);
				}
			}
			if($mCount){
				$this->getOpXmlDb()->trans('end');
				$this->_fresh();
				alert('1');
			}
			alert('0');
		}
	}

	public function resetmenu(){
		delcache('rolemenu', 'menu', $this->roleid);
		@unlink(CORE_PATH . 'data' . CD . 'menu' . CD . 'rolemenu_' . $this->roleid . '.php');
		header('Location:' . act_url('rolemenu', 'init', 'roleid=' . $this->roleid));
	}

	/* 外部接口方法 */
	
	/*
	 * 功能：根据根据用户角色获取用户菜单，返回数组、json、HTML格式
	 */
	public function _get_menu($roleid='',$type=''){
		$roleid=is_numeric($roleid) ? intval($roleid) : $this->roleid;
		$type=(!$type && isset($_GET['type']) ? $_GET['type'] : $type);
		$result=(($roleid == 1 || $roleid == 2) ? getcache('menu', 'menu', 'array', 'display') : $this->getRole()->_get_menu($roleid));
		if($result){
			switch($type){
				case 'array':
					return $this->get_tree_nodes(0, $result);
					break;
				case 'json':
					return make_json($this->get_tree_nodes(0, $result));
					break;
				case 'list':
					return $result;
					break;
				default:
					$tree=load::cls('Tree');
					$tree->init($result);
					return $tree->get_tree(0, "<option value='\$id'>\$spacer \$name</option>");
					break;
			}
		}
	}

	public function _fresh($roleid=-1){
		if($this->getOpXmlDb($roleid)->sType == 'file'){
			$roles=$this->getOpXmlDb($roleid)->select('menu', '*', 'where `id`>=0', 'order by `listorder` desc,`id` asc');
			$reData=array();
			foreach($roles as $v){
				$reData[$v['id']]=$v;
			}
			unset($roles);
			setcache('rolemenu_' . ($roleid == -1 ? $this->roleid : $roleid), $reData, 'menu', 'array');
		}
	}

	/* 内部私有方法 */
	private function getOpXmlDb($roleid=-1){
		$roleid=($roleid == -1 ? $this->roleid : $roleid);
		if(is_null($this->xmlDb)){
			$this->xmlDb=load::db('xml', 'rolemenu_' . $roleid, 'menu', 1);
		}
		return $this->xmlDb;
	}

	private function getRole(){
		if(is_null($this->role)){
			$this->role=load::controller('role');
		}
		return $this->role;
	}

	private function checkUsed($v,&$result){
		foreach($result as $cv){
			if(!empty($cv['c']) && $cv['c'] == $v['c'] && $cv['a'] == $v['a'] && $cv['data'] == $v['data']){
				return true;
			}
		}
		return false;
	}

	/**
	 * 按照层级生成树形节点
	 *
	 * @param number $cid 栏目id
	 * @param array &$inArr 引用的栏目数组
	 * @param string $subname 子节点键名
	 * @return array
	 *
	 */
	private function get_tree_nodes($cid,&$inArr,$subname='submenu'){
		$cArr=get_childs($cid, $inArr);
		$reArr=array();
		foreach($cArr as $id){
			$reArr[$id]=$inArr[$id];
			$iArr=$this->get_tree_nodes($id, $inArr, $subname);
			if(!empty($iArr)){
				$reArr[$id][$subname]=$iArr;
			}
		}
		return $reArr;
	}
}

?>