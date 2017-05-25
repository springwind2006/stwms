<?php
/*
 * 说明： 1.此类需要php5.0以上版本 2.此类用于ado操作ACCESS数据库 3.此类支持事件回溯功能
 */
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Db.class.php';
class AccessDb extends Db implements DbInterface{
	// 保存所有查询的数量;
	public function __construct($dbConfig){ // 构造函数，初始化变量并执行数据库函数
		$this->mType='access';
		$this->initConfig($dbConfig);
	}

	public function __destruct(){
		$this->close();
	}
	// ///////////////////核心操作方法////////////////////////////
	public function connect($dbConfig){
		/* 连接数据库前确保已经关闭，如果没有提供连接参数数组则使用系统上次连接参数； */
		$this->close();
		
		if(!is_file($dbConfig['DSN']) && !$dbConfig['create']){
			return false;
		}
		// 数据库不存在则创建数据库
		try{
			if(!is_file($dbConfig["DSN"])){
				$tfp=fopen($dbConfig['DSN'], "w+");
				$path=$dbConfig['DSN'];
				fclose($tfp);
				unlink($dbConfig['DSN']);
				$conn=@new com('ADOX.Catalog');
				$conn->create('Provider=Microsoft.Jet.OLEDB.4.0;Data Source=' . $path);
				unset($conn);
			}
			// 创建数据库连接对象
			$conn=@new com('adodb.connection', null, '65001');
			if(!$conn){
				return false;
			}
			$conn_strs=array('Provider=Microsoft.Jet.OLEDB.4.0;Data Source=' . realpath($dbConfig['DSN']) . ';Jet OLEDB:Database Password=' . $dbConfig['pass'],'DRIVER={Microsoft Access Driver (*.mdb)};dbq=' . realpath($dbConfig['DSN']) . ';uid=' . $dbConfig['user'] . ';pwd=' . $dbConfig['pass']);
			foreach($conn_strs as $cstr){
				$conn->open($cstr);
				if(!$conn->state){
					continue;
				}else{
					break;
				}
			}
			$this->mConns[$this->mIdentifier]=$conn;
		}catch(Exception $e){
			$this->debug($e->getMessage());
		}
	}

	/*
	 * 功能:查询数据API 1.'select'语句放回查询的记录数组; 2.$ifMod说明是否需要标示查询字段; 注意:此为最原始查询方法，不做表名前置处理
	 */
	public function query($sql,$ifMod=1,$dxFd=''){
		$this->initConnect();
		if($ifMod){
			$sql=str_replace('__PREFIX__', $this->mTbPrefix, $sql);
			$this->mSql($sql);
			$this->mLike($sql);
		}
		$this->mLastSql=$sql;
		if(!$this->mExecSql){
			return false;
		}
		$this->mQueryNum++;
		if(preg_match("/^\s*(select.*)limit\s+([0-9]+)(,([0-9]+))?$/i", $sql, $matchs)){
			$sql=$matchs[1];
			$offset=$matchs[2];
			$pagesize=$matchs[4];
			$q=$this->mConns[$this->mIdentifier]->Execute($sql);
			return $this->fetchArray($q, $offset, $pagesize, $dxFd);
		}else if(preg_match("/^(select.*)$/i", $sql, $matchs)){
			$sql=$matchs[1];
			$q=$this->mConns[$this->mIdentifier]->Execute($sql);
			return $this->fetchArray($q, -1, 0, $dxFd);
		}else{
			$this->mConns[$this->mIdentifier]->Execute($sql, $afNum);
			return $afNum;
		}
	}
	
	// 列出所有表[系统/用户]
	public function tables($type='all'){
		$this->initConnect();
		// 其他数据库获取
		$tbRes=$this->mConns[$this->mIdentifier]->OpenSchema(20);
		$tbArr=array();
		while(!$tbRes->EOF){
			switch($type){
				case 'system': // 查询系统表
					if($tbRes->Fields['TABLE_TYPE']->Value == 'SYSTEM TABLE'){
						$tbArr[]=$tbRes->Fields['TABLE_NAME']->Value;
					}
					break;
				case 'table': // 查询用户表
					if($tbRes->Fields['TABLE_TYPE']->Value == 'TABLE'){
						if($this->mTbPrefix != ''){
							if(strpos($tbRes->Fields['TABLE_NAME']->Value, $this->mTbPrefix) === 0){
								$tbArr[]=$tbRes->Fields['TABLE_NAME']->Value;
							}
						}else{
							$tbArr[]=$tbRes->Fields['TABLE_NAME']->Value;
						}
					}
					break;
				case 'view': // 查询用户表
					if($tbRes->Fields['TABLE_TYPE']->Value == 'VIEW'){
						if($this->mTbPrefix != ''){
							if(strpos($tbRes->Fields['TABLE_NAME']->Value, $this->mTbPrefix) === 0){
								$tbArr[]=$tbRes->Fields['TABLE_NAME']->Value;
							}
						}else{
							$tbArr[]=$tbRes->Fields['TABLE_NAME']->Value;
						}
					}
					break;
				default: // 查询所有表
					$tbArr[]=$tbRes->Fields['TABLE_NAME']->Value;
					break;
			}
			$tbRes->MoveNext();
		}
		$this->freeResult($tbRes);
		return $tbArr;
	}
	// 导出指定表结构
	public function tbCreate($tb){
		$fdArr=$this->getFdInfo($tb);
		$fdInfos=array();
		foreach($fdArr as $cFd){
			$fdInfos[]='[' . $cFd['name'] . '] ' . $cFd['type'] . ($cFd['length'] != '' ? '(' . $cFd['length'] . ')' : '') . ($cFd['isKey'] ? ' primary key' : '') . ($cFd['isNull'] ? '' : ' not null') . ($cFd['default'] ? ' default ' . $cFd['default'] : '');
		}
		return 'create table [' . $this->mTb($tb) . '](' . implode(',', $fdInfos) . ')' . "\r\n";
	}
	
	// 获取表的字段信息
	/*
	 * 此方法有待改进之处：自增字段默认为主键
	 */
	public function getFdInfo($tb,$fd=''){
		$this->initConnect();
		// 获取主键
		$kyRes=$this->mConns[$this->mIdentifier]->OpenSchema(28, array(null,null,$this->mTb($tb)));
		$kyFd=$kyRes["COLUMN_NAME"]->value;
		$kyRes->Close();
		$kyRes=null;
		
		$fdArr=array(); // 记录字段信息的数组
		                // 类型映射
		$typeArr=array(
				2 => 'Short',
				3 => 'Long',
				4 => 'Single',
				5 => 'Double',
				6 => 'Currency',
				7 => 'dateTime',
				11 => 'Bit',
				13 => 'dateTime',
				17 => 'Byte',
				72 => 'GUID',
				128 => 'Binary',
				129 => 'Char',
				130 => 'Char',
				131 => 'Double',
				133 => 'dateTime',
				135 => 'dateTime',
				200 => 'Text',
				201 => 'Text',
				202 => 'Text',
				203 => 'Memo',
				204 => 'Binary',
				205 => 'Binary');
		
		// 从结果集中获取类型
		$fdRs=$this->mConns[$this->mIdentifier]->Execute('select top 1 * from ' . $this->mTb($tb));
		for($i=0; $i < $fdRs->Fields->Count; $i++){
			if($fd != '' ? ($fdRs->Fields[$i]->name == $fd) : 1){
				$fdArr[$i]['ID']=($fdRs->Fields[$i]->Properties['ISAUTOINCREMENT']->Value) ? 1 : 0;
				$fdArr[$i]['type']=$fdArr[$i]['ID'] ? 'Counter' : $typeArr[$fdRs->Fields[$i]->type];
				$cDX++;
			}
		}
		$fdRs->Close();
		$fdRs=null;
		
		// 从架构中获取
		$fdRs=$this->mConns[$this->mIdentifier]->OpenSchema(4);
		while(!$fdRs->EOF){
			if($fdRs->Fields['TABLE_NAME']->Value == $this->mTb($tb) && ($fd != '' ? ($fdRs->Fields['COLUMN_NAME']->Value == $fd) : 1)){
				$cDX=$fdRs->Fields['ORDINAL_POSITION']->Value - 1;
				$fdArr[$cDX]['index']=$fdRs->Fields['ORDINAL_POSITION']->Value;
				$fdArr[$cDX]['name']=$fdRs->Fields['COLUMN_NAME']->Value;
				$fdArr[$cDX]['isNull']=$fdRs->Fields['IS_NULLABLE']->Value;
				$fdArr[$cDX]['flag']=$fdRs->Fields['COLUMN_FLAGS']->Value;
				$fdArr[$cDX]['default']=trim($fdRs->Fields['COLUMN_DEFAULT']->Value); //
				$fdArr[$cDX]['length']=($fdRs->Fields['DATA_TYPE']->Value != 11 && $fdRs->Fields['CHARACTER_MAXIMUM_LENGTH']->Value) ? $fdRs->Fields['CHARACTER_MAXIMUM_LENGTH']->Value : '';
				$fdArr[$cDX]['isKey']=($fdArr[$cDX]['name'] == $kyFd ? 1 : 0);
			}
			$fdRs->MoveNext();
		}
		$fdRs->Close();
		$fdRs=null;
		
		return $fdArr;
	}
	
	// 压缩及修复数据库
	public function compress(){
		$this->initConnect();
		$trueDir=$this->mDbCfg['DSN'];
		$ptInfo=pathinfo($trueDir);
		$dirname=dirname($trueDir);
		$tempName='/tmp' . time() . '.' . $ptInfo['extension'];
		$prov='Provider=Microsoft.Jet.OLEDB.4.0;Data Source=';
		$yaObj=new com('JRO.JetEngine');
		$this->close();
		$yaObj->CompactDatabase($prov . $trueDir, $prov . $dirname . $tempName);
		unlink($trueDir);
		rename($dirname . $tempName, $trueDir);
		$this->connect($this->mDbCfg); // 恢复数据库连接
	}
	
	// 关闭数据库连接
	public function close(){
		if(isset($this->mConns[$this->mIdentifier]) && $this->mConns[$this->mIdentifier]){
			$this->mConns[$this->mIdentifier]->close();
		}
		$this->mConns[$this->mIdentifier]=null;
		unset($this->mConns[$this->mIdentifier]);
	}
	// 事务处理函数
	public function trans($type){
		$this->initConnect();
		switch($type){
			case 'start':
				$this->mConns[$this->mIdentifier]->BeginTrans();
				break;
			case 'end':
				$this->mConns[$this->mIdentifier]->CommitTrans();
				break;
			case 'back':
				$this->mConns[$this->mIdentifier]->RollbackTrans();
				break;
		}
	}
	
	// //////////////////////////////////////////////////
	// ////////////////////内部私有方法////////////////////
	
	// 根据记录集返回限定记录
	private function fetchArray($rs,$offset=-1,$pagesize=0,$dxFd=''){
		if($pagesize > 0){
			$rs->Move($offset);
		}else{
			$pagesize=$offset === -1 ? 1 : $offset;
		}
		$info=array();
		while(!$rs->EOF && $pagesize){
			$r=$this->toArray($rs);
			if(!$r){
				break;
			}
			if(!empty($dxFd) && array_key_exists($dxFd, $r)){
				$info[$r[$dxFd]]=$this->filterData($r);
			}else{
				$info[]=$this->filterData($r);
			}
			if($offset !== -1){
				$pagesize--;
			}
		}
		$this->freeResult($rs);
		return $info;
	}
	
	// 释放记录集
	private function freeResult($rs){
		try{
			$rs->close();
		}catch(Exception $e){
		}
	}
	
	// 根据记录集产生数组,$type=1:关联索引；$type=2:数字索引；$type=3:数字和关联索引；
	private function toArray($rs,$type=1){
		if($rs->EOF){
			return false;
		}
		$reArr=array();
		for($i=0; $i < $rs->Fields->Count; $i++){
			$fielddata=$rs->Fields[$i]->Value;
			switch($type){
				case 1:
					$reArr[$rs->Fields[$i]->Name]=is_string($fielddata) ? trim($fielddata) : $fielddata;
					break;
				case 2:
					$reArr[]=is_string($fielddata) ? trim($fielddata) : $fielddata;
					break;
				default:
					$reArr[]=is_string($fielddata) ? trim($fielddata) : $fielddata;
					$reArr[$rs->Fields[$i]->Name]=is_string($fielddata) ? trim($fielddata) : $fielddata;
					break;
			}
		}
		$rs->MoveNext();
		return $reArr;
	}
}
?>