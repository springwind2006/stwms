<?php
defined('IN_MYCMS') or exit('No permission resources.');
class FieldAdmin extends AdminController{
	public $rootPath, $cachePath, $tbName, $isInstall, $type, $iscat, $catNeedFields;

	public function __construct(){
		parent::__construct();
		$this->rootPath=CORE_PATH . 'data' . CD . 'model' . CD;
		$this->cachePath=CACHE_PATH . 'model' . CD;
		$this->tbName=isset($_GET['tbname']) ? $_GET['tbname'] : (isset($_POST['tbname']) ? $_POST['tbname'] : '');
		$this->xmlDb=$this->getXmlDb($this->tbName, 'model', 3);
		$this->isInstall=empty($this->tbName) || in_array($this->getDb()->mTb($this->tbName), $this->getDb()->tables());
		$this->type=$this->xmlDb->schema('model', 'type');
		$this->iscat=intval($this->xmlDb->schema('model', 'iscat'));
		$this->catNeedFields=$this->getCatNeedFields();
	}
	
	// ////////////字段操作系列方法//////////////
	public function init(){
		if(isset($_GET['get_setting_form'])){
			$this->_get_setting_form($_GET);
			die(0);
		}
		if(isset($_GET['check'])){
			alert($this->_check($_GET));
		}
		$data=getcache('field', 'model', 'array', $this->tbName);
		$models=getcache('model', 'model', 'array');
		$win_width=intval($models[$this->tbName]['width']);
		$win_height=intval($models[$this->tbName]['height']);
		$tbname=$models[$this->tbName]['tbname'];
		$name=$models[$this->tbName]['name'];
		unset($models);
		$type=$this->type;
		$auths=load::controller('role')->_check_auth('add,edit,preview,del,disabled,listorder');
		$auths['add']=$auths['add'] && ($this->type != 2 || $_SESSION['iscreator']);
		include template('init', 'field');
	}

	public function add(){
		if($this->type == 2 && !$_SESSION['iscreator']){
			$this->showmessage('不能添加系统模型字段！', $_SERVER['HTTP_REFERER']);
		}
		if(isset($_POST['dosubmit'])){
			$_POST['info']['setting']=array2string($_POST['setting']);
			$_POST['info']['msetting']=array2string($_POST['msetting']);
			$_POST['info']['dsetting']=array2string($_POST['dsetting']);
			
			$_POST['info']['field']=preg_replace("/[^\w]/", '', $_POST['info']['field']);
			if(strpos($_POST['info']['field'], '_') === 0){
				$_POST['info']['field']=preg_replace("/^_+/", '', $_POST['info']['field']);
			}
			
			$_POST['info']['groupids']=implode('|', $_POST['info']['groupids']);
			
			$insertId=$this->xmlDb->insert('model', $_POST['info']);
			if($this->isInstall){ // 对实体数据库进行操作
				$mdb=load::cls('Mdb');
				$addSQLs=$mdb->add($this->getDb()->mTb($this->tbName), $this->xmlDb, $insertId);
				foreach($addSQLs as $sql){
					$this->getDb()->query($sql);
				}
			}
			if($insertId != -1){
				$this->_fresh();
			}
			$this->showmessage($insertId != -1 ? '添加字段成功！' : '添加字段失败！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}else{
			$formTypes=getcache('formtype', 'formtype', 'array');
			foreach($formTypes as $ky=>$vl){
				if($vl['disabled']){
					unset($formTypes[$ky]);
				}
			}
			$roles=getcache('role', 'users', 'array');
			foreach($roles as $cK=>$cV){
				foreach($cV as $fdky=>$fdvl){
					if($fdky != 'id' && $fdky != 'name'){
						unset($roles[$cK][$fdky]);
					}
				}
				$roles[$cK]['checked']=0;
			}
			$roles[-1]=array('id' => -1,'name' => '授权用户','checked' => 1);
			$roles[0]=array('id' => 0,'name' => '游客','checked' => 1);
			$models=getcache('model', 'model', 'array');
			$win_width=intval($models[$this->tbName]['width']);
			$win_height=intval($models[$this->tbName]['height']);
			$tbname=$models[$this->tbName]['tbname'];
			$name=$models[$this->tbName]['name'];
			unset($models);
			include template('add', 'field');
		}
	}

	public function edit(){
		$id=intval($_GET['id']);
		if(isset($_REQUEST['dosubmit'])){
			if(isset($_POST['setting'])){
				$_POST['info']['setting']=array2string($_POST['setting']);
			}
			if(isset($_POST['msetting'])){
				$_POST['info']['msetting']=array2string($_POST['msetting']);
			}
			if(isset($_POST['dsetting'])){
				$_POST['info']['dsetting']=array2string($_POST['dsetting']);
			}
			
			$_POST['info']['field']=preg_replace("/[^\w]/", '', $_POST['info']['field']);
			if(strpos($_POST['info']['field'], '_') === 0){
				$_POST['info']['field']=preg_replace("/^_+/", '', $_POST['info']['field']);
			}
			$_POST['info']['groupids']=implode('|', $_POST['info']['groupids']);
			
			if($this->iscat && in_array($_POST['info']['field'], $this->catNeedFields)){
				$this->showmessage('不能修改栏目必须字段字段！', $_SERVER['HTTP_REFERER']);
			}
			
			$info=$this->xmlDb->getOne('model', '*', 'where `id`=' . $id);
			
			if($info['issystem'] || ($this->type == 2 && !$_SESSION['iscreator'])){
				unset($_POST['info']['field'], $_POST['info']['formtype'], $_POST['info']['dsetting'], $_POST['info']['issystem']);
			}
			
			$res=$this->xmlDb->update('model', $_POST['info'], 'where `id`=' . $id);
			if($res){
				$this->_fresh();
			}
			$this->showmessage($res > 0 ? '更新字段成功！' : '更新字段失败！', $_SERVER['HTTP_REFERER']);
		}else{
			$info=$this->xmlDb->getOne('model', '*', 'where `id`=' . $id);
			$setting=string2array($info['setting']);
			$msetting=string2array($info['msetting']);
			$dsetting=string2array($info['dsetting']);
			$msetable=$this->getmsetting($info['formtype']);
			$info['groupids']=trim($info['groupids']) !== '' ? explode('|', $info['groupids']) : array();
			
			$xmlFormtypeDb=load::db('xml', 'formtype', 'core', 1);
			$xmlFormtypeInfo=$xmlFormtypeDb->getOne('formtype', 'id,edit_form', 'where `type`=\'' . $info['formtype'] . '\'');
			unset($xmlFormtypeDb);
			$xmlFormtypeInfo['edit_form']=base64($xmlFormtypeInfo['edit_form'], 'decode');
			
			ob_start();
			eval('?>' . $xmlFormtypeInfo['edit_form']); // 直接执行在当前环境中PHP字符串代码
			unset($codeObj);
			$info['setting']=trim(ob_get_contents());
			ob_end_clean();
			unset($xmlFormtypeInfo);
			
			$formtypes=getcache('formtype', 'formtype', 'array');
			$maxlen=intval($formtypes[$info['formtype']]['setting']['field_maxlen']);
			$ismlen=intval($formtypes[$info['formtype']]['setting']['field_ismlen']);
			if($this->isInstall || $info['issystem'] || ($this->type == 2 && !$_SESSION['iscreator'])){
				$info['formtype_tl']=$formtypes[$info['formtype']]['name'];
				unset($formtypes);
			}else{
				foreach($formTypes as $ky=>$vl){
					if($vl['disabled']){
						unset($formTypes[$ky]);
					}
				}
			}
			$roles=getcache('role', 'users', 'array');
			foreach($roles as $cK=>$cV){
				foreach($cV as $fdky=>$fdvl){
					if($fdky != 'id' && $fdky != 'name'){
						unset($roles[$cK][$fdky]);
					}
				}
				$roles[$cK]['checked']=(in_array($roles[$cK]['id'], $info['groupids']) ? 1 : 0);
			}
			$roles[-1]=array('id' => -1,'name' => '授权用户','checked' => (in_array(-1, $info['groupids']) ? 1 : 0));
			$roles[0]=array('id' => 0,'name' => '游客','checked' => (in_array(0, $info['groupids']) ? 1 : 0));
			$auth_add=load::controller('role')->_check_auth('field', 'add') && ($this->type != 2 || $_SESSION['iscreator']);
			$models=getcache('model', 'model', 'array');
			$win_width=intval($models[$this->tbName]['width']);
			$win_height=intval($models[$this->tbName]['height']);
			$tbname=$models[$this->tbName]['tbname'];
			$name=$models[$this->tbName]['name'];
			unset($models);
			$type=$this->type;
			$isInstall=$this->isInstall;
			include template('edit', 'field');
		}
	}

	public function preview(){
		load::cls('Form', 0);
		include getcache('formtype', 'formtype', 'file', 'form');
		$model=trim($_GET['tbname']) == '' ? 'page' : $_GET['tbname'];
		$content_form=new formtype_form($model);
		
		$forminfos=$content_form->_get();
		$formValidator=$content_form->_getValidator();
		$setting=string2array($category['setting']);
		unset($content_form);
		include template('preview', 'field');
		header("Cache-control: private");
	}

	public function del(){
		if($this->type == 2 && !$_SESSION['iscreator']){
			$this->showmessage('不能删除系统模型字段！', $_SERVER['HTTP_REFERER']);
		}
		$delId=intval($_GET['id']);
		$info=$this->xmlDb->getOne('model', 'id,field', 'where `id`=' . $delId);
		if(empty($info)){
			$this->showmessage('删除操作失败！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}
		if($this->iscat && in_array($info['field'], $this->catNeedFields)){
			$this->showmessage('不能删除栏目必须字段！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}
		if($info['issystem']){
			$this->showmessage('不能删除系统字段！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}
		
		if($this->isInstall){ // 对实体数据库进行操作
			$mdb=load::cls('Mdb');
			$delSQLs=$mdb->del($this->getDb()->mTb($this->tbName), $this->xmlDb, $delId);
			foreach($delSQLs as $sql){
				$this->getDb()->query($sql);
			}
		}
		$res=$this->xmlDb->delete('model', 'where `id`=' . $delId); // 对映射字段进行操作
		if($res){
			$this->_fresh();
		}
		$this->showmessage($res > 0 ? '删除操作成功！' : '删除操作失败！', act_url('field', 'init', 'tbname=' . $this->tbName));
	}

	public function disabled(){
		if($this->type == 2 && !$_SESSION['iscreator']){
			$this->showmessage('不能操作系统模型字段！', $_SERVER['HTTP_REFERER']);
		}
		$id=intval($_GET['id']);
		$info=$this->xmlDb->getOne('model', 'id,field', 'where `id`=' . $id);
		if(empty($info)){
			$this->showmessage('操作失败！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}
		if($this->iscat && in_array($info['field'], $this->catNeedFields)){
			$this->showmessage('不能操作栏目字段！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}
		if($info['issystem']){
			$this->showmessage('不能操作系统字段！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}
		$res=$this->xmlDb->update('model', array('disabled' => intval($_GET['disabled'])), 'where `id`=' . $id);
		if($res){
			$this->_fresh();
		}
		$this->showmessage($res ? '操作成功！' : '操作失败！', act_url('field', 'init', 'tbname=' . $this->tbName));
	}

	public function listorder(){
		if(isset($_POST['dosubmit'])){
			$this->xmlDb->trans('start');
			foreach($_POST['listorders'] as $id=>$listorder){
				$this->xmlDb->update('model', array('listorder' => $listorder), 'where `id`=' . $id);
			}
			$this->xmlDb->trans('end');
			$this->_fresh();
			$this->showmessage('排序操作成功！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}else{
			$this->showmessage('排序操作失败！', act_url('field', 'init', 'tbname=' . $this->tbName));
		}
	}

	/* * ******外部接口方法******* */
	public function _get_setting_form($cfg){
		$xmlFormtypeDb=load::db('xml', 'formtype', 'core', 1);
		$xmlFormtypeInfo=$xmlFormtypeDb->getOne('formtype', 'id,add_form', 'where `type`=\'' . $cfg['formtype'] . '\'');
		unset($xmlFormtypeDb);
		$xmlFormtypeInfo['add_form']=base64($xmlFormtypeInfo['add_form'], 'decode');
		
		ob_start();
		eval('?>' . $xmlFormtypeInfo['add_form']); // 直接执行在当前环境中PHP字符串代码
		unset($codeObj);
		$add_form=trim(ob_get_contents());
		ob_end_clean();
		
		$msetting=$this->getmsetting($cfg['formtype']);
		$formTypes=getcache('formtype', 'formtype', 'array');
		$formSetting=string2array($formTypes[$cfg['formtype']]['setting']);
		unset($formTypes);
		header('Content-Type:text/xml;charset=utf-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		include template('get_setting_form', 'field');
	}

	public function _check($cfg){
		$field=safe_replace($cfg['info']['field']);
		if($field == 'id'){
			return '0';
		}else{
			$gFds=$this->xmlDb->getOne('model', '*', 'where `field`=\'' . $field . '\'' . (isset($cfg['id']) ? ' and `id`!=' . intval($cfg['id']) : ''));
			return (!is_array($gFds) ? '1' : '0');
		}
	}

	public function _fresh($tb=''){
		$cList=array();
		if(!empty($tb)){ // 缓存指定参数表名
			$cList[]=$tb . '.php';
		}else if(!empty($this->tbName)){ // 缓存当前表名
			$cList[]=$this->tbName . '.php';
		}else{ // 缓存所有字段名
			load::func('file');
			$cList=file_list($this->rootPath);
		}
		
		$model_fields=getcache('model', 'model', 'array', 'fields');
		$tpArr=array();
		foreach($cList as $file){
			$this->xmlDb->load($this->rootPath . $file);
			$fdArr=$this->xmlDb->select('model', '*', 'where `id`>=0', 'order by `listorder` desc,`id` asc');
			$newFdArr=array();
			foreach($fdArr as $ky=>$vl){
				$vl['setting']=string2array($vl['setting']);
				$vl['msetting']=string2array($vl['msetting']);
				$vl['dsetting']=string2array($vl['dsetting']);
				$vl['groupids']=trim($vl['groupids']) !== '' ? explode('|', $vl['groupids']) : array();
				$newFdArr[$vl['field']]=$vl;
			}
			unset($fdArr);
			$tbname=($pos=strrpos($file, ".")) === false ? $file : substr($file, 0, $pos);
			$model_fields[$tbname]=array_keys($newFdArr);
			array_unshift($model_fields[$tbname], 'id');
			setcache('field_' . $tbname, $newFdArr, 'model', 'array');
		}
		setcache('model_fields', $model_fields, 'model', 'array'); // 同时更新模型字段映射
	}

	/* * ****内部私有方法***** */
	private function getmsetting($ftype){
		$msetting=array();
		$notOrderForms=array('image','images','classid','downfiles','catids','keyword','catid','description');
		$notSearchForms=array('image','images','downfiles','catid','catids');
		
		$cFormtype=getcache('formtype', 'formtype', 'array');
		$field_type=$cFormtype[$ftype]['field_type'];
		$msetting['istolist']=$ftype == 'editor' || $ftype == 'catid' || $ftype == 'catids' ? 0 : 1;
		$msetting['isorder']=$msetting['istolist'] && $field_type != 'text' && !in_array($ftype, $notOrderForms) ? 1 : 0;
		$msetting['issearch']=!in_array($ftype, $notSearchForms) ? 1 : 0;
		$msetting['isshow']=$msetting['istolist'] || $msetting['isorder'] || $msetting['issearch'] ? 1 : 0;
		return $msetting;
	}

	private function getCatNeedFields(){
		$sysfield=load::cfg('fields');
		$CatNeeds=array();
		foreach($sysfield as $fd){
			if($fd['iscatneed']){
				$CatNeeds[]=$fd['field'];
			}
		}
		return $CatNeeds;
	}
}

?>