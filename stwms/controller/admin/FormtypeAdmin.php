<?php
defined('IN_MYCMS') or exit('No permission resources.');
class FormtypeAdmin extends AdminController{
	public $rootPath, $cachePath, $xmldb_user;

	public function __construct(){
		parent::__construct();
		$this->rootPath=CORE_PATH . 'data' . CD . 'core' . CD;
		$this->cachePath=CACHE_PATH . 'formtype' . CD;
	}

	public function init(){
		if(isset($_GET['check'])){
			$name=$this->mtype($_GET['info']['type']);
			alert(!empty($name) && $this->check($name) ? '1' : '0');
		}
		$data=getcache('formtype', 'formtype', 'array');
		$DBFieldTypes=load::cfg('fieldtypes');
		$auths=load::controller('role')->_check_auth('add,edit,del,code,disabled');
		include template('init', 'formtype');
	}

	public function add(){
		if(isset($_POST['dosubmit'])){
			$_POST['info']['type']=$this->mtype($_POST['info']['type']);
			if($this->check($_POST['info']['type'])){
				$_POST['info']['setting']=array2string($_POST['setting']);
				$_POST['info']=base64($_POST['info'], 'encode', 'add_form,edit_form,form,input,output,update');
				$_POST['info']['system']=$_SESSION['iscreator'];
				$this->getOpXmlDb()->insert('formtype', $_POST['info']);
				$this->_fresh();
				$this->showmessage('添加操作成功！');
			}else{
				$this->showmessage('字段名称已经存在！');
			}
		}else{
			$DBFieldTypes=load::cfg('fieldtypes');
			$fieldSets=$this->getfieldSets();
			include template('add', 'formtype');
		}
	}

	public function edit(){
		$type=$this->mtype($_GET['type']);
		$datas=getcache('formtype', 'formtype', 'array');
		if(isset($_GET['dosubmit'])){
			$_POST['info']['type']=$this->mtype($_POST['info']['type']);
			$_POST['info']['setting']=array2string($_POST['setting']);
			$info=$datas[$type];
			unset($datas);
			$issys=!empty($info['system']) ? 1 : 2;
			if($issys == 1 && !$_SESSION['iscreator']){
				alert('0');
			}else{
				$_POST['info']=base64($_POST['info'], 'encode', 'add_form,edit_form,form,input,output,update');
				if($this->using($_POST['info']['type'])){ // 不允许修改正在使用的类型名称
					unset($_POST['info']['type']);
				}
				if(isset($_POST['info']['system'])){
					unset($_POST['info']['system']);
				}
				$res=$this->getOpXmlDb($issys)->update('formtype', $_POST['info'], 'where `type`=\'' . $type . '\'');
				$this->_fresh();
				alert($res ? '1' : '0');
			}
		}else{
			$info=$datas[$type];
			unset($datas);
			$issys=!empty($info['system']) ? 1 : 2;
			// 判断此类型是否正在使用，如果没有使用则可以进行类型名称修改
			$type_using=$this->using($info['type']);
			$info=$this->getOpXmlDb($issys)->getOne('formtype', '*', 'where `type`=\'' . $type . '\'');
			$info['setting']=string2array($info['setting']);
			
			$info=base64($info, 'decode', 'add_form,edit_form,form,input,output,update');
			@extract($info);
			$DBFieldTypes=load::cfg('fieldtypes');
			$fieldSets=$this->getfieldSets();
			
			// 重新设置变量值
			$field_type=$info['field_type'];
			$checkArr=array('maxlen','default','unsigned','index');
			foreach($checkArr as $fVar){
				if(isset($info['setting']['field_' . $fVar]) && isset($fieldSets[$field_type][$fVar])){
					$fieldSets[$field_type][$fVar]=$info['setting']['field_' . $fVar];
				}
			}
			@extract($info['setting']);
			unset($info['setting']);
			include template('edit', 'formtype');
		}
	}

	public function del(){
		$type=$this->mtype($_GET['type']);
		if(!$this->using($type)){
			$datas=getcache('formtype', 'formtype', 'array');
			$info=$datas[$type];
			unset($datas);
			$issys=!empty($info['system']) ? 1 : 2;
			if($issys == 1 && !$_SESSION['iscreator']){
				$this->showmessage('不能删除系统字段！');
			}else{
				$this->getOpXmlDb($issys)->delete('formtype', 'where `type`=\'' . $type . '\'');
				$this->_fresh();
				$this->showmessage('删除成功！');
			}
		}else{
			$this->showmessage('此类型正在被使用，无法删除！');
		}
	}

	public function code(){
		if($_SESSION['iscreator']){
			if(isset($_GET['dosubmit'])){
				$_POST['info']=base64($_POST['info'], 'encode');
				$res=$this->getOpXmlDb(1)->update('code', $_POST['info'], 'where `id`=1');
				if($res){
					$this->_fresh();
				}
				alert($res ? '1' : '0');
			}else{
				$codeInfo=base64($this->getOpXmlDb(1)->getOne('code', '*', 'where `id`=1'), 'decode');
				@extract($codeInfo);
				include template('code', 'formtype');
			}
		}else{
			$this->showmessage('权限不足！');
		}
	}

	public function disabled(){
		$type=$this->mtype($_GET['type']);
		if(!$this->using($type)){
			$datas=getcache('formtype', 'formtype', 'array');
			$info=$datas[$type];
			unset($datas);
			$issys=!empty($info['system']) ? 1 : 2;
			if($issys == 1 && !$_SESSION['iscreator']){
				$this->showmessage('不能操作系统字段！');
			}else{
				$res=$this->getOpXmlDb($issys)->update('formtype', array('disabled' => $_GET['disabled']), 'where `type`=\'' . $type . '\'');
				if($res){
					$this->_fresh();
				}
				$this->showmessage($res ? '操作成功！' : '操作失败！');
			}
		}else{
			$this->showmessage('此类型正在被使用，无法操作！');
		}
	}

	/* * *********内部接口方法********** */
	public function _fresh(){
		$tpArr=array();
		if(!is_dir($this->cachePath)){
			@mkdir($this->cachePath, 0777, true);
		}
		
		$formHandles=array();
		$formClass=array('form' => getcache('formtype', 'formtype', 'file', 'form', 0),'input' => getcache('formtype', 'formtype', 'file', 'input', 0),'output' => getcache('formtype', 'formtype', 'file', 'output', 0),'update' => getcache('formtype', 'formtype', 'file', 'update', 0));
		
		$codeFields=array('add_form','edit_form','form','input','output','update');
		$codeInfo=base64($this->getOpXmlDb(1)->getOne('code', '*', 'where `id`=1'), 'decode');
		// 开始代码缓存
		foreach($formClass as $ctype=>$fpath){
			$formHandles[$ctype]=fopen($fpath, 'w');
			fwrite($formHandles[$ctype], '<?php ' . "\n" . 'class formtype_' . $ctype . ' {' . "\n");
			fwrite($formHandles[$ctype], $codeInfo[$ctype] . "\n\n" . '////////////////////////////////////////////////////////////////' . "\n");
		}
		
		$infos=array_merge($this->getOpXmlDb(1)->select('formtype'), $this->getOpXmlDb(2)->select('formtype'));
		$dxNum=1;
		foreach($infos as $vl){
			// 获取表单信息
			$attArr=array();
			$codeArr=array();
			$vl['setting']=string2array($vl['setting']);
			$vl['id']=$dxNum++;
			foreach($vl as $k=>$v){
				if(!in_array($k, $codeFields)){
					$attArr[$k]=$v;
				}else{
					$codeArr[$k]=base64($v, 'decode');
				}
			}
			$tpArr[$vl['type']]=$attArr;
			
			// 写入代码缓存
			foreach($formClass as $ctype=>$fpath){
				$cnt=trim($codeArr[$ctype]);
				if(!empty($cnt)){
					fwrite($formHandles[$ctype], rtrim($codeArr[$ctype]) . "\n\n");
				}
			}
		}
		
		// 结束代码缓存
		foreach($formClass as $ctype=>$fpath){
			fwrite($formHandles[$ctype], '}' . "\n" . '?>' . "\n");
			fclose($formHandles[$ctype]);
		}
		
		// 写入表单缓存
		setcache('formtype', $tpArr, 'formtype', 'array');
	}

	/* * *********内部私有方法********** */
	private function using($type){
		if(trim($type) === ''){
			return true;
		}
		$models=getcache('model', 'model', 'array');
		$modelPath=CORE_PATH . 'data' . CD . 'model' . CD;
		$xmlModelDb=load::db('xml', '', 'model', 1);
		foreach($models as $m){
			$xmlModelDb->load($modelPath . $m['tbname'] . '.php');
			$re=$xmlModelDb->getOne('model', '*', 'where `formtype`=\'' . $type . '\'');
			if(!empty($re)){
				return true;
			}
		}
		unset($xmlModelDb);
		return false;
	}

	private function getOpXmlDb($issys=0){
		$isSysDb=$issys ? ($issys == 1) : $_SESSION['iscreator'];
		if($isSysDb && is_null($this->xmldb)){
			$this->xmldb=load::db('xml', 'formtype', 'core', 1);
		}else if(!$isSysDb && is_null($this->xmldb_user)){
			if(!is_file($this->rootPath . 'formtype_user.php')){
				file_put_contents($this->rootPath . 'formtype_user.php', '<?xml version="1.0" encoding="UTF-8"?><root/>', LOCK_EX);
			}
			$this->xmldb_user=load::db('xml', 'formtype_user', 'core', 1);
			$this->xmldb_user->create('formtype', 'id|type|name|desc|field_type|setting|disabled|system|add_form|edit_form|form|input|output|update');
		}
		return $isSysDb ? $this->xmldb : $this->xmldb_user;
	}
	
	// 获取字段设置
	private function getfieldSets(){
		return array(
				'varchar' => array('maxlen' => 255,'default' => '','index' => 0,'ismlen' => 0),
				'tinyint' => array('default' => '','index' => 1),
				'text' => array(),
				'smallint' => array('default' => '','unsigned' => 0,'index' => 1),
				'int' => array('default' => '','unsigned' => 0,'index' => 0),
				'float' => array('default' => '','unsigned' => 0,'index' => 0));
	}
	
	// 对字段名称重新修改
	private function mtype($tp){
		$tp=preg_replace("/[^\w]/", '', $tp);
		if(strpos($tp, '_') === 0){
			$tp=preg_replace("/^_+/", '', $tp);
		}
		return $tp;
	}

	private function check($name){
		$formatypes=getcache('formtype', 'formtype', 'array');
		return !array_key_exists($name, $formatypes);
	}
}

?>