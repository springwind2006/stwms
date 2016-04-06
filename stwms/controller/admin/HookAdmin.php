<?php
defined('IN_MYCMS') or exit('No permission resources.');
class HookAdmin extends AdminController{
	public $id;

	public function __construct(){
		parent::__construct();
		$this->id=Param::get_para('id');
		$this->xmlDbName='hook';
	}

	public function init(){
		// 检查钩子名称是否重复
		if(Param::get_para('check')){
			$name=$_GET['info']['name'];
			alert($tbname && $this->getXmlDb()->hasFdVl('hook', 'name', $name, ($this->id ? '`id`!=' . $this->id : '')) ? '0' : '1');
		}
		
		// 根据插件名称获取插件方法
		if(Param::get_para('get_plugin_a')){
			$plugin_c=Param::get_para('plugin_c');
			$pluginObj=load::plugin($plugin_c);
			$aArr=$pluginObj ? get_class_methods($pluginObj) : array();
			unset($pluginObj);
			$info=$this->getXmlDb()->getOne('hook', 'id,name,data', 'where `id`=' . $this->id);
			$name=$info['name'];
			unset($info);
			
			$maps=getcache('hook', 'core', 'array', 'map');
			foreach($aArr as $k=>$a){
				if($a == '__construct' || (isset($maps[$name]) && strpos($maps[$name] . ',', $plugin_c . '.' . $a . ',') !== false)){
					unset($aArr[$k]);
				}
			}
			alert(array_values($aArr));
		}
		
		$auths=load::controller('role')->_check_auth('plugin.init,init,add,edit,del');
		if($this->id){
			$info=$this->getXmlDb()->getOne('hook', 'name,data', 'where `id`=' . $this->id);
			$name=$info['name'];
			$data=array();
			if($info['data']){
				foreach(explode(',', $info['data']) as $ca){
					$caArr=explode('.', $ca);
					$data[]=array('c' => $caArr[0],'a' => $caArr[1]);
				}
			}
			unset($info);
			include template('hinit', 'hook');
		}else{
			$auths['add']=$auths['add'] && $_SESSION['iscreator'];
			$auths['del']=$auths['del'] && $_SESSION['iscreator'];
			$data=$this->getXmlDb()->select('hook');
			include template('init', 'hook');
		}
	}

	public function add(){
		if(isset($_POST['info'])){
			if($this->id){ // 动作添加
				if(!$_POST['info']['plugin_c'] || !$_POST['info']['plugin_a']){
					alert('插件或方法不能为空!');
				}
				$maps=getcache('hook', 'core', 'array', 'map');
				$info=$this->getXmlDb()->getOne('hook', 'id,name,data', 'where `id`=' . $this->id);
				$ca=$_POST['info']['plugin_c'] . '.' . $_POST['info']['plugin_a'];
				if(!$info){
					alert('钩子不存在!');
				}
				if(isset($maps[$info['name']]) && strpos($maps[$info['name']] . ',', $ca . ',') !== false){
					alert('此动作已经被添加!');
				}
				
				$datas=explode(',', $info['data']);
				$datas[]=$ca;
				$info['data']=implode(',', array_unique(array_filter($datas)));
				$res=$this->getXmlDb()->update('hook', array('data' => $info['data']), 'where `id`=' . $this->id);
				if($res > 0){
					$this->_fresh();
				}
				alert($res > 0 ? '添加成功！' : '添加失败！');
			}else if($_SESSION['iscreator']){ // 钩子添加
				$_POST['info']['name']=preg_replace("/[^\w]/", '', safe_replace($_POST['info']['name']));
				alert($this->getXmlDb()->insert('hook', $_POST['info']) > 0 ? '添加成功！' : '添加失败！');
			}
		}else{
			if($this->id){
				$plugins=getcache('plugin', 'core', 'array');
				$cArr=array();
				foreach($plugins as $plugin){
					$cArr[]=$plugin['name'];
				}
				include template('hadd', 'hook');
			}else{
				include template('add', 'hook');
			}
		}
	}

	public function edit(){
		if(isset($_POST['info'])){
			if($nid=intval(Param::get_para('nid'))){
				$info=$this->getXmlDb()->getOne('hook', 'id,data', 'where `id`=' . $this->id);
				$datas=explode(',', $info['data']);
				unset($info);
				$datas[$nid - 1]=$_POST['info']['plugin_c'] . '.' . $_POST['info']['plugin_a'];
				$res=$this->getXmlDb()->update('hook', array('data' => implode(',', array_unique($datas))), 'where `id`=' . $this->id);
				if($res > 0){
					$this->_fresh();
				}
				alert($res > 0 ? '动作修改成功！' : '动作修改失败！');
			}else{
				unset($_POST['info']['name'], $_POST['info']['data']);
				alert($this->getXmlDb()->update('hook', $_POST['info'], 'where `id`=' . $this->id) > 0 ? '修改成功！' : '修改失败！');
			}
		}else{
			if($nid=intval(Param::get_para('nid'))){
				$info=$this->getXmlDb()->getOne('hook', 'id,data', 'where `id`=' . $this->id);
				$cHook=$info['data'];
				$datas=explode(',', $info['data']);
				$data=explode('.', $datas[$nid - 1]);
				$plugin_c=$data[0];
				$plugin_a=$data[1];
				unset($info, $datas, $data);
				
				$plugins=getcache('plugin', 'core', 'array');
				$cArr=array();
				foreach($plugins as $plugin){
					$cArr[]=$plugin['name'];
				}
				unset($plugins);
				
				$pluginObj=load::plugin($plugin_c);
				$aArr=$pluginObj ? get_class_methods($pluginObj) : array($plugin_a);
				unset($pluginObj);
				foreach($aArr as $k=>$a){
					if($a == '__construct' || ($plugin_a != $a && !empty($cHook) && strpos($cHook . ',', $plugin_c . '.' . $a . ',') !== false)){
						unset($aArr[$k]);
					}
				}
				include template('hedit', 'hook');
			}else{
				$data=$this->getXmlDb()->getOne('hook', '*', 'where `id`=' . $this->id);
				@extract($data);
				unset($data);
				include template('edit', 'hook');
			}
		}
	}

	public function del(){
		if($nid=intval(Param::get_para('nid'))){
			$info=$this->getXmlDb()->getOne('hook', 'id,data', 'where `id`=' . $this->id);
			$datas=explode(',', $info['data']);
			unset($datas[$nid - 1]);
			$res=$this->getXmlDb()->update('hook', array('data' => implode(',', $datas)), 'where `id`=' . $this->id);
			if($res > 0){
				$this->_fresh();
			}
			$this->showmessage($res > 0 ? '删除成功！' : '删除失败！', act_url('hook', 'init', 'id=' . $this->id));
		}else if($_SESSION['iscreator']){
			$res=$this->getXmlDb()->delete('hook', 'where `id`=' . $this->id);
			if($res > 0){
				$this->_fresh();
			}
			$this->showmessage($res > 0 ? '删除成功！' : '删除失败！', act_url('hook', 'init'));
		}
	}

	public function _install($name,$cfgs){
		$num=0;
		$this->getXmlDb()->trans('start');
		foreach($cfgs as $hook=>$action){
			if($action){
				$info=$this->getXmlDb()->getOne('hook', 'id,name,data', 'where `name`=\'' . $hook . '\'');
				$datas=explode(',', $info['data']);
				foreach(explode(',', $action) as $a){
					$datas[]=$name . '.' . $a;
				}
				$datas=array_unique(array_filter($datas));
				$num+=$this->getXmlDb()->update('hook', array('data' => implode(',', $datas)), 'where `name`=\'' . $hook . '\'');
			}
		}
		$this->getXmlDb()->trans('end');
		if($num){
			$this->_fresh();
			unset($this->db);
		}
		return $num;
	}

	public function _uninstall($name){
		$infos=$this->getXmlDb()->select('hook', 'id,data');
		$num=0;
		$this->getXmlDb()->trans('start');
		foreach($infos as $info){
			if($info['data'] && strpos($info['data'], $name . '.') !== false){
				$datas=explode(',', $info['data']);
				foreach($datas as $k=>$ca){
					if(strpos($ca, $name . '.') !== false){
						unset($datas[$k]);
					}
				}
				$num+=$this->getXmlDb()->update('hook', array('data' => implode(',', $datas)), 'where `id`=' . $info['id']);
			}
		}
		$this->getXmlDb()->trans('end');
		if($num){
			$this->_fresh();
			unset($this->db);
		}
		return $num;
	}

	public function _fresh(){
		$datas=$this->getXmlDb()->select('hook');
		$data=array();
		foreach($datas as $r){
			if($r['data']){
				$data[$r['name']]=$r['data'];
			}
		}
		setcache('hook_map', $data, 'core', 'array');
	}
}

?>