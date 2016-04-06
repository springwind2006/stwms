<?php
defined('IN_MYCMS') or exit('No permission resources.');
class PositionAdmin extends AdminController{
	public $categorys;

	public function __construct(){
		parent::__construct();
		$this->categorys=getcache('category', 'core', 'array', 'base');
		$this->xmlDbName='position';
	}

	public function init(){
		if(isset($_GET['get_cats'])){
			$model=(isset($_GET['model']) ? $_GET['model'] : '');
			alert($this->getcats($model));
		}
		$data=getcache('position', 'core', 'array');
		$models=getcache('model', 'model', 'array');
		foreach($data as $ky=>$vl){
			$cnum=empty($vl['content']) ? 0 : count(explode(',', $vl['content']));
			$vl['modelname']=empty($models[$vl['model']]['name']) ? '全部' : $models[$vl['model']]['name'];
			$vl['catname']=empty($this->categorys[$vl['catid']]['name']) ? '全部' : $this->categorys[$vl['catid']]['name'];
			$vl['maxnum']=$cnum . '/' . $vl['maxnum'];
			$data[$ky]=$vl;
		}
		$auths=load::controller('role')->_check_auth('add,edit,del,viewdata,listorder');
		include template('init', 'position');
	}

	public function add(){
		if(isset($_GET['dosubmit'])){
			$res=$this->getXmlDb()->insert('position', $_POST['info']);
			if($res != -1){
				$this->_fresh();
				alert('添加成功');
			}else{
				alert('添加失败');
			}
		}else{
			$allModels=getcache('model', 'model', 'array');
			$models=array();
			foreach($allModels as $model){
				if($model['type'] != 2){
					$models[$model['tbname']]=$model['name'];
				}
			}
			unset($allModels);
			$all_categorys=$this->getcats();
			include template('add', 'position');
		}
	}

	public function edit(){
		$id=intval($_GET['id']);
		if(isset($_GET['dosubmit'])){
			$res=$this->getXmlDb()->update('position', $_POST['info'], 'where `id`=' . $id);
			if($res){
				$this->_fresh();
				alert('修改成功');
			}else{
				alert('修改失败');
			}
		}else{
			$info=$this->getXmlDb()->getOne('position', '*', 'where `id`=' . $id);
			$allModels=getcache('model', 'model', 'array');
			$models=array();
			foreach($allModels as $model){
				if($model['type'] != 2){
					$models[$model['tbname']]=$model['name'];
				}
			}
			unset($allModels);
			$all_categorys=$this->getcats($info['model'], $info['catid']);
			include template('edit', 'position');
		}
	}

	public function del(){
		$id=intval($_GET['id']);
		$res=$this->getXmlDb()->delete('position', 'where `id`=' . $id);
		if($res){
			$this->_fresh();
			$this->showmessage('删除操作成功！', 'auto');
		}else{
			$this->showmessage('删除操作失败！', 'auto');
		}
	}

	public function delitem(){
		$posid=intval($_GET['posid']);
		$models=explode('.', $_GET['id']);
		$model=$models[0];
		$id=intval($models[1]);
		$this->showmessage($this->_delete($posid, $id, $model) ? '删除操作成功！' : '删除操作失败！', 'auto');
	}

	public function viewdata(){
		$models=getcache('model', 'model', 'array');
		$modelFields=getcache('model', 'model', 'array', 'fields');
		$id=intval($_GET['id']);
		$data=array();
		$info=$this->getXmlDb()->getOne('position', '*', 'where `id`=' . $id);
		if(!empty($info) && $info['content']){
			$posids=explode(',', $info['content']);
			$corder=count($posids);
			$fields=array('id','catid','title');
			foreach($posids as $cids){
				$mods=explode('.', $cids);
				$cfds=array_intersect($fields, $modelFields[$mods[0]]);
				$data[$cids]=$this->getDb()->getOne($mods[0], $cfds, 'where `id`=' . $mods[1]);
				$cmodel=empty($this->categorys[$data[$cids]['catid']]['model']) ? 'page' : $this->categorys[$data[$cids]['catid']]['model'];
				$data[$cids]['modelname']=$models[$cmodel]['name'];
				$data[$cids]['catname']=$this->categorys[$data[$cids]['catid']]['name'];
				$data[$cids]['listorder']=$corder--;
			}
		}
		$auths=load::controller('role')->_check_auth('add,delitem,itemorder');
		include template('viewdata', 'position');
	}

	public function listorder(){
		if(isset($_POST['dosubmit'])){
			$this->getXmlDb()->trans('start');
			foreach($_POST['listorders'] as $id=>$listorder){
				$this->getXmlDb()->update('position', array('listorder' => intval($listorder)), 'where `id`=' . $id);
			}
			$this->getXmlDb()->trans('end');
			$this->_fresh();
			$this->showmessage('排序操作成功！', 'auto');
		}else{
			$this->showmessage('排序操作失败！', 'auto');
		}
	}

	public function itemorder(){
		$id=intval($_GET['posid']);
		$cListorder=array();
		foreach($_POST['listorders'] as $ky=>$vl){
			$cListorder[$ky]=$vl;
		}
		arsort($cListorder);
		if($this->getXmlDb()->update('position', array('content' => implode(',', array_keys($cListorder))), 'where `id`=' . $id)){
			$this->_fresh();
			$this->showmessage('排序操作成功！', 'auto');
		}else{
			$this->showmessage('排序操作失败！', 'auto');
		}
	}

	/* * ****内部接口方法**** */
	public function _fresh(){
		$infos=$this->getXmlDb()->select('position', '*', 'where `id`>=0', 'order by `listorder` desc,`id` asc');
		$data=array();
		foreach($infos as $vl){
			$data[intval($vl['id'])]=$vl;
		}
		unset($infos);
		setcache('position', $data, 'core', 'array');
	}

	public function _update($posid,$id,$model,$catid=''){
		$info=$this->getXmlDb()->getOne('position', '*', 'where `id`=' . $posid);
		$cntArr=empty($info['content']) ? array() : explode(',', $info['content']);
		$model_id=$model . '.' . $id;
		$maxnum=intval($info['maxnum']);
		if(!in_array($model_id, $cntArr)){
			if($info['isreplace']){
				array_unshift($cntArr, $model_id);
				if($maxnum && count($cntArr) > $maxnum){
					array_splice($cntArr, $maxnum);
				}
			}else if(count($cntArr) < $maxnum){
				array_unshift($cntArr, $model_id);
			}
			$this->getXmlDb()->update('position', array('content' => implode(',', $cntArr)), 'where `id`=' . $posid);
			$this->_fresh();
		}
	}

	public function _delete($posid,$id,$model,$catid=''){
		$info=$this->getXmlDb()->getOne('position', 'id,content', 'where `id`=' . $posid);
		$cntArr=empty($info['content']) ? array() : explode(',', $info['content']);
		$model_id=$model . '.' . $id;
		if(in_array($model_id, $cntArr)){
			$dx=array_search($model_id, $cntArr);
			unset($cntArr[$dx]);
			$this->getXmlDb()->update('position', array('content' => implode(',', $cntArr)), 'where `id`=' . $posid);
			$this->_fresh();
			return true;
		}
		return false;
	}

	/* * ****私有方法**** */
	public function getcats($model='',$sid=-1){
		$tree=load::cls('Tree');
		$result=getcache('category', 'core', 'array', 'base');
		$array=array();
		foreach($result as $r){
			if($r['type']){
				continue;
			}
			if($model != '' && $model != $r['model']){
				continue;
			}
			$r['selected']=$r['id'] == $sid ? 'selected' : '';
			$array[]=$r;
		}
		$str="<option value='\$id' \$selected>\$spacer \$name</option>";
		$tree->init($array);
		return $tree->get_tree(0, $str);
	}
}

?>