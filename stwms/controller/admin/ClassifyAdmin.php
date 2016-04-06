<?php
defined('IN_MYCMS') or exit('No permission resources.');
class ClassifyAdmin extends AdminController{
	private $maxLevel;

	public function __construct(){
		parent::__construct();
		$this->maxLevel=10;
		$this->xmlDbName='classify';
	}

	public function init(){
		$data=getcache('classify', 'classify', 'array');
		foreach($data as $k=>$v){
			$data[$k]['count']=$this->getDb()->count('type', 'where `cid`=' . $v['id']);
		}
		
		$auths=load::controller('role')->_check_auth('type.init,add,edit,del');
		include template('init', 'classify');
	}

	public function add(){
		if(isset($_GET['dosubmit'])){
			$_POST['info']['catids']=is_array($_POST['catids']) ? implode(',', $_POST['catids']) : '';
			$cid=$this->getXmlDb()->insert('classify', $_POST['info']);
			$this->_fresh($cid, 1);
			alert('添加成功！');
		}else{
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;&nbsp;';
			$category=getcache('category', 'core', 'array', 'base');
			// 获取模型
			foreach($category as $cid=>$v){
				if($v['type'] || !get_formtype_fields($category[$cid]['model'], 'classid')){
					unset($category[$cid]);
				}
			}
			$tree->init($category);
			$categorys=$tree->get_array(0);
			include template('add', 'classify');
		}
	}

	public function edit(){
		$cid=intval($_GET['cid']);
		if(isset($_GET['dosubmit'])){
			$_POST['info']['catids']=is_array($_POST['catids']) ? implode(',', $_POST['catids']) : '';
			$this->getXmlDb()->update('classify', $_POST['info'], 'where `id`=' . $cid);
			$this->_fresh($cid, 1);
			alert('修改成功！');
		}else{
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;&nbsp;';
			$category=getcache('category', 'core', 'array', 'base');
			$classes=getcache('classify', 'classify', 'array');
			@extract($classes[$cid]);
			
			$catids=explode(',', $catids);
			// 获取模型
			foreach($category as $k=>$v){
				if($v['type'] || !($typeFields=get_formtype_fields($category[$k]['model'], 'classid,typeid'))){
					unset($category[$k]);
				}else{
					if(isset($typeFields['typeid'])){
						$category[$k]['usetypeid']=in_array($v['id'], $catids) ? 0 : $this->getUseTypeID($classes, $v['id']);
					}
				}
			}
			$tree->init($category);
			$categorys=$tree->get_array(0);
			$auths=load::controller('role')->_check_auth('add,edit,del');
			include template('edit', 'classify');
		}
	}

	public function del(){
		$cid=intval($_GET['cid']);
		$res=$this->getXmlDb()->delete('classify', 'where `id`=' . $cid);
		if($res){
			$this->getDb()->delete('type', 'where `cid`=' . $cid);
			$this->_fresh();
			delcache('classify', 'classify', $cid);
			$this->showmessage('删除操作成功！{s}秒后返回...', act_url('classify', 'init'));
		}else{
			$this->showmessage('删除操作失败！{s}秒后返回...', act_url('classify', 'init'));
		}
	}

	/* * ****内部接口方法**** */
	public function _fresh($cid='',$flushAll=0){
		if(empty($cid)){
			$infos=$this->getXmlDb()->select('classify', '*', 'where `id`>0', 'order by `listorder` desc,`id` desc');
			$data=array();
			foreach($infos as $vl){
				$classid=intval($vl['id']);
				$data[$classid]=$vl;
			}
			unset($infos);
			setcache('classify', $data, 'classify', 'array');
		}else{
			if($flushAll){
				$this->_fresh();
			}
			$dtcount=$this->getXmlDb()->count('classify', 'where `id`=' . $cid);
			if($dtcount){
				$infos=$this->getDb()->select('type', '*', 'where `cid`=' . $cid, 'order by `listorder` desc,`id` asc');
				$data=array();
				foreach($infos as $vl){
					$vl['id']=intval($vl['id']);
					$vl['pid']=intval($vl['pid']);
					$vl['cid']=intval($vl['cid']);
					$vl['arrcid']=get_childs($vl['id'], $infos, false);
					$data[$vl['id']]=$vl;
				}
				setcache('classify_' . $cid, $data, 'classify', 'array');
				unset($infos, $data);
				$allInfos=$this->getDb()->select('type', 'id,cid', 'where `id`>=0', 'order by `listorder` desc,`id` asc');
				$map_arr=array();
				foreach($allInfos as $vl){
					$map_arr[$vl['id']]=intval($vl['cid']);
				}
				setcache('classify_map', $map_arr, 'classify', 'array');
			}
		}
	}

	public function _getTypeName($typeid,$cache=0){
		static $cacheArr=array();
		if(isset($cacheArr[$typeid])){
			return $cacheArr[$typeid];
		}
		
		$typeids=array_filter(explode(',', $typeid));
		$typeArr=array();
		foreach($typeids as $type){
			$info=$this->getDb()->getOne('type', '*', 'where `id`=' . $type);
			if(!empty($info)){
				$typeArr[$type]=$info['name'];
			}
		}
		if($cache){
			$cacheArr[$typeid]=$typeArr;
		}
		return $typeArr;
	}

	/* * ****私有方法**** */
	private function getUseTypeID(&$classes,$catid){
		foreach($classes as $classify){
			if($classify['catids'] == $catid || strpos($classify['catids'] . ',', $catid . ',') !== false){
				return $classify['id'];
			}
		}
		return 0;
	}
}

?>