<?php
defined('IN_MYCMS') or exit('No permission resources.');
class MenuAdmin extends AdminController{

	public function __construct(){
		parent::__construct();
		$this->xmlDbName='menu';
		$this->xmlDbPath='menu';
	}

	public function init(){
		if(isset($_GET['get_action'])){
			alert($this->getAct($_GET['cls']));
		}
		$tree=load::cls('Tree');
		$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp='&nbsp;&nbsp;&nbsp;';
		$result=getcache('menu', 'menu', 'array');
		$tree->init($result);
		$categorys=$tree->get_array(0);
		$auths=load::controller('role')->_check_auth('add,edit,del,listorder');
		include template('init', 'menu');
	}

	public function add(){
		if(isset($_POST['dosubmit']) || isset($_GET['dosubmit'])){
			$_POST['info']['name']=safe_replace($_POST['info']['name']);
			if(empty($_POST['info']['name'])){
				alert('请输入菜单名称！');
			}else if($this->check($_POST['info']['c'], $_POST['info']['a'])){
				alert('添加失败！已存在文件名及方法名！');
			}else{
				$_POST['info']['listorder']=intval($_POST['info']['listorder'] ? $_POST['info']['listorder'] : 0);
				alert($this->_add($_POST['info']) != -1 ? '添加成功！' : '添加失败！');
			}
		}else{
			$tree=load::cls('Tree');
			$result=getcache('menu', 'menu', 'array');
			$tree->init($result);
			$select_categorys=$tree->get_array(0);
			$clsArr=$this->getCls();
			load::func('file');
			$icons=file_list(ADMIN_STATIC_PATH . 'images' . CD . 'icon' . CD);
			include template('add', 'menu');
		}
	}

	public function edit(){
		if(isset($_GET['dosubmit'])){
			$id=intval($_POST['id']);
			if($this->check($_POST['info']['c'], $_POST['info']['a'], $id)){
				alert('修改失败！已存在类型及方法名！');
			}else{
				$res=$this->getXmlDb()->update('menu', $_POST['info'], 'where `id`=' . $id);
				if($res != 0){
					$this->_fresh();
					alert('修改成功！');
				}else{
					alert('修改失败！');
				}
			}
		}else{
			$tree=load::cls('Tree');
			$id=intval($_GET['id']);
			$r=$this->getXmlDb()->getOne('menu', '*', 'where `id`=' . $id);
			if($r)
				extract($r);
			$result=$this->getXmlDb()->select('menu', '*', 'where `id`!=' . $id . ' and `pid`!=' . $id, 'order by `listorder` desc,`id` asc');
			$tree->init($result);
			$select_categorys=$tree->get_array(0);
			$clsArr=$this->getCls();
			$actArr=$this->getAct($c);
			load::func('file');
			$icons=file_list(ADMIN_STATIC_PATH . 'images' . CD . 'icon' . CD);
			include template('edit', 'menu');
		}
	}

	public function del(){
		$id=intval($_GET['id']);
		$hasChild=$this->getXmlDb()->count('menu', 'where `pid`=' . $id);
		if(!$hasChild && $this->getXmlDb()->delete('menu', 'where `id`=' . $id)){
			$this->_fresh();
			$this->showmessage('删除操作成功！');
		}else{
			$this->showmessage('删除操作失败！该菜单下面有子菜单！');
		}
	}

	public function listorder(){
		if(isset($_POST['dosubmit'])){
			$this->getXmlDb()->trans('start');
			foreach($_POST['listorders'] as $id=>$listorder){
				$this->getXmlDb()->update('menu', array('listorder' => intval($listorder)), 'where `id`=' . $id);
			}
			$this->getXmlDb()->trans('end');
			$this->_fresh();
			$this->showmessage('排序操作成功！');
		}else{
			$this->showmessage('排序操作失败！');
		}
	}

	/* * *************外部接口方法方法************* */
	public function _add($data,$isPlugin=0){
		if(is_array($data)){
			if($isPlugin){
				$pluginid=$this->getXmlDb()->schema('menu', 'pluginid');
				$this->getXmlDb()->trans('start');
				if(is_null($pluginid) || !$this->getXmlDb()->hasFdVl('menu', 'id', $pluginid)){
					$pluginid=$this->getXmlDb()->insert('menu', array('name' => '系统插件','pid' => 0,'display' => 1,'listorder' => 0));
					$this->getXmlDb()->schema('menu', 'pluginid', $pluginid);
				}
				$data['pid']=$pluginid;
				$data['listorder']=0;
				$data['display']=1;
				$res=($pluginid != -1 ? $this->getXmlDb()->insert('menu', $data) : -1);
				$this->getXmlDb()->trans('end');
			}else{
				$res=$this->getXmlDb()->insert('menu', $data);
			}
			if($res != -1){
				$this->_fresh();
			}
			return $res;
		}
		return -1;
	}

	public function _delplugin($actId){
		$pluginid=$this->getXmlDb()->schema('menu', 'pluginid');
		if(!is_null($pluginid)){
			$res=$this->getXmlDb()->delete('menu', 'where `pid`=' . $pluginid . ' and `data`=\'actionid=' . $actId . '\'');
			$hasChild=$this->getXmlDb()->count('menu', 'where `pid`=' . $pluginid);
			if(!$hasChild){
				$this->getXmlDb()->schema('menu', 'pluginid', NULL);
				$this->getXmlDb()->delete('menu', 'where `id`=' . $pluginid);
			}
			if($res != -1){
				$this->_fresh();
			}
		}
		return 0;
	}

	public function _getMenuInfoByActionID($actionID,$fd){
		$pluginid=$this->getXmlDb()->schema('menu', 'pluginid');
		$info=$this->getXmlDb()->getOne('menu', '*', 'where `pid`=' . $pluginid . ' and `data`=\'actionid=' . $actionID . '\'');
		return $info[$fd];
	}

	public function _getPluginMenuInfo(){
		$pluginid=$this->getXmlDb()->schema('menu', 'pluginid');
		$info=$this->getXmlDb()->select('menu', '*', 'where `pid`=' . $pluginid);
		foreach($info as $ky=>$vl){
			$actionid=intval(str_replace('actionid=', '', $info[$ky]['data']));
			unset($info[$ky]['data']);
			$info[$ky]['actionid']=$actionid;
		}
		return $info;
	}

	public function _fresh(){
		$data=$this->getXmlDb()->select('menu', '*', 'where `id`>=0', 'order by `listorder` desc,`id` asc');
		$mapArr=array();
		$allArr=array();
		$displayArr=array();
		foreach($data as $k=>$v){
			$v['type']=1;
			$allArr[$v['id']]=$v;
			if($v['display']){
				$displayArr[$v['id']]=$v;
			}
			if(!empty($v['c']) || !empty($v['a'])){
				if($v['c'] == 'plugin' && $v['a'] == 'admin' && !empty($v['data'])){
					continue;
				}else{
					$mapArr[$v['c'] . $v['a']]=$v['id'];
				}
			}
		}
		unset($data);
		setcache('menu_map', $mapArr, 'menu', 'array');
		setcache('menu', $allArr, 'menu', 'array');
		setcache('menu_display', $displayArr, 'menu', 'array');
	}

	/* * *************内部私有方法************* */
	private function getCls(){
		$clsArr=glob(dirname(__FILE__) . CD . '*.php');
		foreach($clsArr as $ky=>$fl){
			$fl=strtolower(basename($fl, 'Admin.php'));
			if($fl == 'api' || $fl == 'admin'){
				unset($clsArr[$ky]);
			}else{
				$flArr=$this->getAct($fl);
				$isuse=1;
				foreach($flArr as $mth){
					if(!$mth['isuse']){
						$isuse=$mth['isuse'];
					}
				}
				$clsArr[$ky]=array('c' => $fl,'isuse' => $isuse);
			}
		}
		return $clsArr;
	}

	private function getAct($c){
		$class_methods=array();
		if(load::controller($c, 0)){
			$methods=get_class_methods($c);
			foreach($methods as $ky=>$method){
				if(stripos($method, '_') !== 0){
					$a=substr($method, 2);
					$class_methods[]=array('a' => $a,'isuse' => ($this->check($c, $a) ? 1 : 0));
				}
			}
			unset($methods);
		}
		return $class_methods;
	}

	private function check($c,$a,$id=''){
		if(empty($c) && empty($a)){
			return false;
		}
		if(!empty($id)){
			$re=$this->getXmlDb()->getOne('menu', 'id,pid,c,a', 'where `id`=' . $id);
			$pluginid=$this->getXmlDb()->schema('menu', 'pluginid');
			if($re['pid'] == $pluginid){
				return false;
			}
		}
		$sql='where `c`=\'' . $c . '\' and `a`=\'' . $a . '\'' . (empty($id) ? '' : ' and `id`!=' . $id);
		$re=$this->getXmlDb()->getOne('menu', 'id,pid,c,a', $sql);
		return !empty($re);
	}
}

?>