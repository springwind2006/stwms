<?php
defined('IN_MYCMS') or exit('No permission resources.');
class ModelAdmin extends AdminController{
	public $rootPath, $cachePath;

	public function __construct(){
		parent::__construct();
		$this->rootPath=CORE_PATH . 'data' . CD . 'model' . CD;
		$this->cachePath=CACHE_PATH . 'model' . CD;
		$this->xmlDbPath='model';
	}

	public function init(){
		if(isset($_GET['check'])){
			$tbname=$_GET['attr']['tbname'];
			alert($tbname && $this->check($tbname) ? '1' : '0');
		}
		$data=getcache('model', 'model', 'array');
		$tables=$this->getDb()->tables();
		$auths=load::controller('role')->_check_auth('field.init,add,edit,disabled,del,install,uninstall,import,export');
		include template('init', 'model');
	}

	public function add(){
		if(isset($_REQUEST['dosubmit'])){
			$tbname=preg_replace("/[^\w]/", '', safe_replace($_POST['attr']['tbname']));
			$filePath=$this->rootPath . $tbname . '.php';
			$this->getXmlDb()->load($filePath);
			$this->getXmlDb()->create('model', array('field','name','formtype','tips','css','minlength','maxlength','pattern','errortips','setting','msetting','dsetting','issystem','isbase','listorder','disabled','groupids'));
			unset($_POST['attr']['tbname']);
			$this->getXmlDb()->schema('model', $_POST['attr']);
			$this->create_sys_field($_POST['attr']['type'], $_POST['attr']['iscat']);
			$this->getXmlDb()->save($filePath);
			
			// 更新字段缓存
			$fdArr=$this->getXmlDb()->select('model', '*', 'where `id`>=0', 'order by `listorder` desc');
			$newFdArr=array();
			foreach($fdArr as $ky=>$vl){
				$vl['setting']=string2array($vl['setting']);
				$vl['msetting']=string2array($vl['msetting']);
				$vl['dsetting']=string2array($vl['dsetting']);
				$vl['groupids']=trim($vl['groupids']) !== '' ? explode('|', $vl['groupids']) : array();
				$newFdArr[$vl['id']]=$vl;
			}
			unset($fdArr);
			setcache('field_' . $tbname, $newFdArr, 'model', 'array');
			
			$this->_fresh(); // 更新模型缓存
			alert('添加操作成功！');
		}else{
			include template('add', 'model');
		}
	}

	public function edit(){
		if(isset($_GET['dosubmit'])){
			$tbname=preg_replace("/[^\w]/", '', safe_replace($_POST['attr']['tbname']));
			unset($_POST['attr']['type']);
			$this->getXmlDb()->load($this->rootPath . $tbname . '.php');
			$type=$this->getXmlDb()->schema('model', 'type');
			if($type == 2 && !$_SESSION['iscreator']){
				alert('不能修改系统模型!');
			}else{
				unset($_POST['attr']['tbname']);
				$this->getXmlDb()->schema('model', $_POST['attr']);
				$this->_fresh();
				alert('修改操作成功!');
			}
		}else{
			$tbname=safe_replace($_GET['tbname']);
			$this->getXmlDb()->load($this->rootPath . $tbname . '.php');
			$attrs=$this->getXmlDb()->schema('model');
			$attrs['tbname']=$tbname;
			@extract($attrs);
			include template('edit', 'model');
		}
	}

	public function disabled($tbname=''){
		$isInCaller=!empty($tbname);
		$tbname=safe_replace(empty($tbname) ? $_GET['tbname'] : $tbname);
		if(is_file($this->rootPath . $tbname . '.php')){
			$this->getXmlDb()->load($this->rootPath . $tbname . '.php');
			$type=$this->getXmlDb()->schema('model', 'type');
			if($type == 2 && !$_SESSION['iscreator']){
				return $isInCaller ? false : $this->showmessage('操作失败！');
			}
			$this->getXmlDb()->schema('model', array('disabled' => $_GET['disabled']));
			$this->_fresh();
			return $isInCaller ? true : $this->showmessage('操作成功！');
		}else{
			return $isInCaller ? false : $this->showmessage('操作失败！');
		}
	}

	public function del($tbname='',$forceDel=0){
		$isInCaller=!empty($tbname);
		$tbname=safe_replace(empty($tbname) ? $_GET['tbname'] : $tbname);
		if(is_file($this->rootPath . $tbname . '.php')){
			$this->getXmlDb()->load($this->rootPath . $tbname . '.php');
			$type=$this->getXmlDb()->schema('model', 'type');
			if(!$forceDel && $type == 2 && !$_SESSION['iscreator']){
				return $isInCaller ? false : alert('不能删除系统模型!');
			}else{
				// 删除此模型结构
				@unlink($this->rootPath . $tbname . '.php');
				
				if(!is_file($this->rootPath . $tbname . '.php')){
					// 删除此模型的字段缓存
					delcache('field', 'model', $tbname);
					
					// 删除此模型的实体数据表
					$tb=$this->getDb()->mTb($tbname);
					$this->getDb()->dropTb($tb);
					if(!in_array($tb, $this->getDb()->tables())){
						$this->_fresh();
						return $isInCaller ? true : $this->showmessage('操作成功！', 'auto');
					}
				}
			}
			return $isInCaller ? false : $this->showmessage('操作失败！', 'auto');
		}else{
			return $isInCaller ? false : $this->showmessage('操作失败！', 'auto');
		}
	}

	public function install($tbname='',$isFresh=1){
		$isInCaller=!empty($tbname);
		$tbname=safe_replace(empty($tbname) ? $_GET['tbname'] : $tbname);
		$tb=$this->getDb()->mTb($tbname);
		$this->getXmlDb()->load($this->rootPath . $tbname . '.php');
		$fdArr=$this->getXmlDb()->select('model', 'id,field,formtype,dsetting,listorder', 'where `id`>=0', 'order by `listorder` desc');
		
		if(!empty($fdArr)){
			$mdb=load::cls('Mdb');
			$createSQLs=$mdb->create($tb, $fdArr);
			foreach($createSQLs as $sql){
				$this->getDb()->query($sql);
			}
			if(in_array($tb, $this->getDb()->tables())){
				$this->getXmlDb()->schema('model', array('isinstall' => 1));
				if($isFresh){
					$this->_fresh();
				}
				return $isInCaller ? true : $this->showmessage('安装成功！', act_url('model', 'init'));
			}else{
				return $isInCaller ? false : $this->showmessage('安装失败！', act_url('model', 'init'));
			}
		}else{
			return $isInCaller ? false : $this->showmessage('此模型为空！请添加字段后安装！', act_url('model', 'init'));
		}
	}

	public function uninstall($tbname=''){
		$isInCaller=!empty($tbname);
		$isSucess=false;
		$tbname=safe_replace(empty($tbname) ? $_GET['tbname'] : $tbname);
		if(is_file($this->rootPath . $tbname . '.php')){
			$this->getXmlDb()->load($this->rootPath . $tbname . '.php');
			$type=$this->getXmlDb()->schema('model', 'type');
			if($type != 2 || $_SESSION['iscreator']){
				$tb=$this->getDb()->mTb($tbname);
				$this->getDb()->dropTb($tb);
				if(!in_array($tb, $this->getDb()->tables())){
					$this->getXmlDb()->schema('model', array('isinstall' => 0));
					$this->_fresh();
					$isSucess=true;
				}
			}
		}
		return $isInCaller ? $isSucess : $this->showmessage($isSucess ? '卸载成功！' : '卸载失败！', act_url('model', 'init'));
	}

	public function import(){
		if(Param::get_para('dosubmit')){
			unset($_POST['attr']['type']);
			$_POST['attr']=safe_replace($_POST['attr']);
			$tbname=preg_replace("/[^\w]/", '', safe_replace($_POST['attr']['tbname']));
			$tmpPath=CACHE_PATH . 'model_' . $tbname . time() . rand(100, 900) . '.php';
			$filePath=$this->rootPath . $tbname . '.php';
			$tb=$this->getDb()->mTb($tbname);
			
			if(is_file($filePath) || in_array($tb, $this->getDb()->tables())){
				alert('1');
			}
			
			function_exists('set_time_limit') && @set_time_limit(0);
			
			if(move_uploaded_file($_FILES['Filedata']['tmp_name'], $tmpPath)){
				$is_schema=true;
				$fields='';
				$cLnum=0;
				$lsize=20;
				$isInstall=false;
				$fp=fopen($tmpPath, 'r');
				$this->getDb()->trans('start');
				while(!feof($fp)){
					$buffer=trim(fgets($fp));
					if($buffer == '<start>'){
						$is_schema=false;
						$isInstall=$this->install($tbname, 0);
						$this->getXmlDb()->schema('model', 'db_conn', load::cfg('system', 'db_conn'));
						$fieldArr=array('id');
						foreach($this->getXmlDb()->select('model', 'id,field', 'order by `listorder` desc') as $fd){
							$fieldArr[]=$fd['field'];
						}
						$fields='`' . implode('`,`', $fieldArr) . '`';
						unset($fieldArr);
					}
					if($buffer){
						if($is_schema){
							file_put_contents($filePath, $buffer . "\r\n", FILE_APPEND);
						}else if($isInstall && $buffer != '<start>'){
							$this->getDb()->query('insert into `' . $tb . '` (' . $fields . ') values ' . str_replace('\r\n', "\r\n", $buffer));
							unset($buffer);
							$cLnum++;
							if(!($cLnum % $lsize)){
								$this->getDb()->trans('end');
								$this->getDb()->trans('start');
							}
						}
					}
				}
				$this->getDb()->trans('end');
				fclose($fp);
				@unlink($tmpPath);
				
				if($is_schema){
					$this->getXmlDb()->load($filePath);
				}
				$this->getXmlDb()->schema('model', $_POST['attr']);
				$this->_fresh();
				alert($is_schema ? '2' : '3');
			}
			alert('0');
		}else{
			include template('import', 'model');
		}
	}

	public function export(){
		$tbname=preg_replace("/[^\w]/", '', $_GET['tbname']);
		$sourcePath=$this->rootPath . $tbname . '.php';
		$tmpPath=CACHE_PATH . 'model_' . $tbname . time() . rand(100, 900) . '.php';
		function_exists('set_time_limit') && @set_time_limit(0);
		if(copy($sourcePath, $tmpPath)){
			// 如果模型安装了则导出模型数据
			if(in_array($this->getDb()->mTb($tbname), $this->getDb()->tables()) && ($allcount=$this->getDb()->count($tbname))){
				$this->getXmlDb()->load($tmpPath);
				$this->getXmlDb()->schema('model', 'db_conn', load::cfg('system', 'db_conn'));
				
				$fieldArr=array('id');
				foreach($this->getXmlDb()->select('model', 'id,field', 'order by `listorder` desc') as $fd){
					$fieldArr[]=$fd['field'];
				}
				$fields=implode(',', $fieldArr);
				unset($fieldArr, $this->xmlDb);
				
				$lsize=20;
				$search=array("\x00","\x0a","\x0d","\x1a"); // \x08\\x09, not required
				$replace=array('\0','\n','\r','\Z');
				$ctrl="\r\n";
				$fp=fopen($tmpPath, 'a+');
				fwrite($fp, $ctrl . '<start>' . $ctrl);
				for($i=0; $i < $allcount; $i+=$lsize){
					foreach($this->getDb()->select($tbname, $fields, '', '', 'limit ' . $i . ',' . $lsize) as $row){
						$values=array();
						foreach($row as $j=>$item){
							if(!isset($item) || is_null($item)){
								$values[]='NULL';
							}else{
								$values[]='\'' . str_replace($search, $replace, $this->sqlAddslashes($item)) . '\'';
							}
						}
						$sql='(' . implode(',', $values) . ')' . $ctrl;
						fwrite($fp, $sql);
						unset($values);
					}
				}
				fclose($fp);
			}
			
			$fileInfo=array('path' => $tmpPath,'name' => $tbname . '.model','ext' => 'xml');
			$api=load::controller('api');
			$api->download($fileInfo);
			@unlink($tmpPath);
		}
	}

	/* * *********内部接口方法********** */
	
	// 更新模型缓存
	public function _fresh(){
		load::func('file');
		$cList=file_list($this->rootPath);
		$tpArr=array();
		$filedsArr=array();
		$sortArr=array();
		foreach($cList as $file){
			$result=$this->getXmlDb()->load($this->rootPath . $file);
			if(!$result){
				continue;
			}
			$ckey=basename($file, '.php');
			$tpArr[$ckey]=$this->getXmlDb()->schema('model');
			$tpArr[$ckey]['tbname']=$ckey;
			$sortArr[$ckey]=$tpArr[$ckey]['type'];
			
			$datas=$this->getXmlDb()->select('model', 'id,field');
			$filedsArr[$ckey][]='id';
			foreach($datas as $info){
				$filedsArr[$ckey][]=$info['field'];
			}
			unset($datas);
		}
		array_multisort($sortArr, SORT_DESC, $tpArr);
		setcache('model', $tpArr, 'model', 'array');
		setcache('model_fields', $filedsArr, 'model', 'array');
	}

	/* * *********私有方法********** */
	/*
	 * 功能：创建系统字段，在新建模型的时候调用
	 * 说明：系统字段包括catid,typeid,title,url,status,listorder,username,addtime,edittime
	 * 当模型为应用到栏目且模型类别为自定义或系统时，会自动创建catid字段
	 */
	private function create_sys_field($type,$iscat){
		if($type && !$iscat){
			return false;
		}
		$sysfield=load::cfg('fields');
		$this->getXmlDb()->trans('start');
		foreach($sysfield as $fd){
			if($iscat && $type && !$fd['iscatneed']){
				continue; // 排除非栏目必须字段，创建栏目必须字段，此字段创建后不能删除或修改
			}
			if(isset($fd['msetting']) && !empty($fd['msetting'])){
				$fd['msetting']=array2string($fd['msetting']);
			}
			if(isset($fd['dsetting']) && !empty($fd['dsetting'])){
				$fd['dsetting']=array2string($fd['dsetting']);
			}
			if(isset($fd['setting']) && !empty($fd['setting'])){
				$fd['setting']=array2string($fd['setting']);
			}
			$this->getXmlDb()->insert('model', $fd);
		}
		$this->getXmlDb()->trans('end');
	}

	/*
	 * 功能：获取数据库类型
	 */
	private function getSQLDbType(){
		$db_conn=load::cfg('system', 'db_conn');
		$db_cfg=load::cfg('database', $db_conn);
		if(strpos($db_cfg['type'], 'sqlite') === 0){
			return 'sqlite';
		}else if(strpos($db_cfg['type'], 'mssql') === 0){
			return 'mssql';
		}else if(strpos($db_cfg['type'], 'mysql') === 0){
			return 'mysql';
		}else{
			return $db_cfg['type'];
		}
	}

	private function check($name){
		load::func('file');
		$fileArr=file_list($this->rootPath);
		return !in_array($name . '.php', $fileArr);
	}

	private function sqlAddslashes($a_string='',$is_like=false,$crlf=false,$php_code=false){
		$a_string=$is_like ? str_replace('\\', '\\\\\\\\', $a_string) : str_replace('\\', '\\\\', $a_string);
		if($crlf){
			$a_string=str_replace("\n", '\n', $a_string);
			$a_string=str_replace("\r", '\r', $a_string);
			$a_string=str_replace("\t", '\t', $a_string);
		}
		$a_string=$php_code ? str_replace('\'', '\\\'', $a_string) : str_replace('\'', '\'\'', $a_string);
		return $a_string;
	}
}

?>