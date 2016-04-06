<?php
defined('IN_MYCMS') or exit('No permission resources.');
load::controller('classify', 0);
class TypeAdmin extends ClassifyAdmin{
	private $cid, $isedit, $ismanage, $isadmin, $typeInfo;

	public function __construct(){
		parent::__construct();
		$this->cid=intval(Param::get_para('cid'));
		$this->isedit=intval(isset($_GET['isedit']) ? $_GET['isedit'] : 1);
		$this->ismanage=!isset($_GET['isedit']) ? 1 : 0;
		$this->isadmin=Param::get_para('isadmin', 0);
		$data=getcache('classify', 'classify', 'array');
		$this->typeInfo=$data[$this->cid];
		unset($data);
	}

	public function init(){
		$limitLevel=$this->typeInfo['level'];
		$result=getcache('classify', 'classify', 'array', $this->cid);
		if(isset($_GET['classid'])){
			$classid=intval($_GET['classid']);
			echo get_parents($classid, $result, 'name', ' > ', true);
		}else{
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;│ ','&nbsp;&nbsp;├─ ','&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;';
			$tree->init($result);
			$data=$tree->get_array(0);
			$isedit=$this->isedit;
			$auths=load::controller('role')->_check_auth('classify.init,add,edit,del,desc,listorder');
			include template($this->isadmin ? 'admin' : 'select', 'type');
		}
	}

	public function add(){
		if(!$this->isadmin){ // 可编辑选择界面添加
			$res=$this->getDb()->insert('type', $_POST['info']);
			$pid=intval($_POST['info']['pid']);
			$cid=intval($_POST['info']['cid']);
			$level=1;
			if($res){
				$this->_fresh($this->cid);
				while($pid){
					$info=$this->getDb()->getOne('type', 'id,pid,cid', 'where `cid`=' . $cid . ' and `id`=' . $pid);
					$pid=intval($info['pid']);
					unset($info);
					$level++;
				}
			}
			alert('{"id":' . ($res ? $this->getDb()->lastInsert('type') : -1) . ',"addsub":' . ($level < $this->typeInfo['level'] ? 1 : 0) . '}');
		}else if(isset($_POST['dosubmit']) || isset($_GET['dosubmit'])){ // 管理界面添加处理
			$_POST['info']['name']=safe_replace($_POST['info']['name']);
			if(empty($_POST['info']['name'])){
				alert('请输入分类名称！');
			}else{
				$_POST['info']['listorder']=intval($_POST['info']['listorder']);
				$_POST['info']['cid']=$this->cid;
				$res=$this->getDb()->insert('type', $_POST['info']);
				if($res){
					$this->_fresh($this->cid);
				}
				alert($res ? '添加成功！' : '添加失败！');
			}
		}else{ // 管理界面添加
			$pid=intval(Param::get_para('pid'));
			$tree=load::cls('Tree');
			$result=getcache('classify', 'classify', 'array', $this->cid);
			$tree->init($result);
			$str="<option value='\$id' \$selected>\$spacer \$name</option>";
			$select_types=$tree->get_tree(0, $str, $pid);
			include template('add', 'type');
		}
	}

	public function edit(){
		if(!$this->isadmin){ // 可编辑选择界面编辑
			$id=intval($_POST['info']['id']);
			$_POST['info']['listorder']=intval($_POST['info']['listorder']);
			$res=$this->getDb()->update('type', $_POST['info'], 'where `id`=' . $id . ' and `cid`=' . $this->cid);
			if($res){
				$this->_fresh($this->cid);
			}
			alert($res ? 'success' : 'failed');
		}else if(isset($_POST['dosubmit']) || isset($_GET['dosubmit'])){ // 管理界面编辑处理
			$id=intval(Param::get_para('id'));
			$_POST['info']['name']=safe_replace($_POST['info']['name']);
			if(empty($_POST['info']['name'])){
				alert('请输入分类名称！');
			}else{
				$res=$this->getDb()->update('type', $_POST['info'], 'where `id`=' . $id . ' and `cid`=' . $this->cid);
				if($res){
					$this->_fresh($this->cid);
				}
				alert($res ? '修改成功！' : '修改失败！');
			}
		}else{ // 管理界面编辑
			$id=intval(Param::get_para('id'));
			$result=getcache('classify', 'classify', 'array', $this->cid);
			$pid=$result[$id]['pid'];
			@extract($result[$id]);
			unset($result[$id]);
			$tree=load::cls('Tree');
			$tree->init($result);
			$str="<option value='\$id' \$selected>\$spacer \$name</option>";
			$select_types=$tree->get_tree(0, $str, $pid);
			include template('edit', 'type');
		}
	}

	public function desc(){
		$id=intval(Param::get_para('id'));
		if(!isset($_GET['dosubmit'])){
			$info=$this->getDb()->getOne('type', '*', 'where `id`=' . $id . ' and `cid`=' . $this->cid);
			if($this->isedit){
				echo '<textarea id="describe_area" style="width:250px;height:100px;">' . htmlspecialchars($info['describe']) . '</textarea>';
			}else{
				echo '<div style="width:250px;height:100px;">' . (empty($info['describe']) ? '该分类暂无描述！' : htmlspecialchars($info['describe'])) . '</textarea>';
			}
		}else{
			$res=$this->getDb()->update('type', $_POST, 'where `id`=' . $id . ' and `cid`=' . $this->cid);
			if($res){
				$this->_fresh($this->cid);
			}
			alert($res ? 'success' : 'failed');
		}
	}

	public function del(){
		$id=intval($_GET['id']);
		$hasChild=$this->getDb()->count('type', 'where `pid`=' . $id . ' and `cid`=' . $this->cid);
		if($hasChild){
			$this->isadmin ? $this->showmessage('此分类下有子分类，删除失败！', act_url('type', 'init', 'isadmin=1&cid=' . $this->cid)) : alert('haschild');
		}else{
			$res=$this->getDb()->delete('type', 'where `id`=' . $id . ' and `cid`=' . $this->cid);
			if($res){
				$this->_fresh($this->cid);
			}
			$this->isadmin ? $this->showmessage($res ? '删除操作成功！' : '删除操作失败！', act_url('type', 'init', 'isadmin=1&cid=' . $this->cid)) : alert($res ? 'success' : 'failed');
		}
	}

	public function listorder(){
		$dosubmit=isset($_GET['dosubmit']) ? 1 : (isset($_POST['dosubmit']) ? 2 : 0);
		if($dosubmit){
			$alltypes=getcache('classify', 'classify', 'array', $this->cid);
			$this->getDb()->trans('start');
			foreach($_POST['listorders'] as $id=>$listorder){
				if($alltypes[$id]['listorder'] != $listorder){
					$this->getDb()->update('type', array('listorder' => $listorder), 'where `id`=' . $id . ' and `cid`=' . $this->cid);
				}
			}
			$this->getDb()->trans('end');
			$this->_fresh($this->cid);
			$dosubmit == 1 ? alert('排序操作成功！') : $this->showmessage('排序操作成功！', act_url('type', 'init', 'isadmin=1&cid=' . $this->cid));
		}else{
			$dosubmit == 1 ? alert('排序操作失败！') : $this->showmessage('排序操作失败！', act_url('type', 'init', 'isadmin=1&cid=' . $this->cid));
		}
	}
}

?>