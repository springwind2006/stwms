<?php
/*
 * 说明： 1.此类需要php5.0以上版本 2.此类用于操作MsSQL数据库,php.ini要求扩展php_mysqli.dll模块 3.此类支持事件回溯功能
 */
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Db.class.php';
class MysqliDb extends Db implements DbInterface{
	// 保存所有查询的数量;
	public function __construct($dbConfig){ // 构造函数，初始化变量并执行数据库函数
		$this->mType='mysqli';
		$this->initConfig($dbConfig);
	}

	public function __destruct(){
		$this->close();
	}
	// ///////////////////核心操作方法////////////////////////////
	public function connect($dbConfig){
		/* 连接数据库前确保已经关闭，如果没有提供连接参数数组则使用系统上次连接参数； */
		$this->close();
		
		$DSNS=explode(':', $dbConfig['DSN']);
		try{
			$this->mConns[$this->mIdentifier]=@mysqli_connect($DSNS[0], $dbConfig['user'], $dbConfig['pass'], $dbConfig['dbname'], $DSNS[1]);
			if(!$this->mConns[$this->mIdentifier]){
				return false;
			}
			mysqli_query($this->mConns[$this->mIdentifier], 'set names UTF8');
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
		}
		$this->mLastSql=$sql;
		if(!$this->mExecSql){
			return false;
		}
		$this->mQueryNum++;
		if(stripos(trim($sql), 'select ') === 0){
			$q=mysqli_query($this->mConns[$this->mIdentifier], $sql);
			return $this->fetchArray($q, -1, 0, $dxFd);
		}else{
			mysqli_query($this->mConns[$this->mIdentifier], $sql);
			return mysqli_affected_rows($this->mConns[$this->mIdentifier]);
		}
	}
	
	// 列出所有表[系统/用户]
	public function tables($type='all'){
		$this->initConnect();
		$vt=$type == 'table' ? 'where ' . $this->mFd('Version') . ' is not null' : ($type == 'view' ? 'where ' . $this->mFd('Version') . ' is null' : '');
		$res=mysqli_query($this->mConns[$this->mIdentifier], 'show table status ' . $vt);
		$reArr=array();
		while($r=mysqli_fetch_array($res, 2)){
			if($type == 'user' && $this->mTbPrefix != ''){
				if(strpos($r[0], $this->mTbPrefix) === 0){
					array_push($reArr, $r[0]);
				}
			}else{
				array_push($reArr, $r[0]);
			}
		}
		$this->freeResult($res);
		return $reArr;
	}
	// 导出指定表结构
	public function tbCreate($tb){
		$this->initConnect();
		$res=mysqli_query($this->mConns[$this->mIdentifier], 'show create table ' . $this->mFd($this->mTb($tb)));
		$DATA=mysqli_fetch_array($res, 2);
		$this->freeResult($res);
		return 'DROP TABLE IF EXISTS ' . $this->mFd($this->mTb($tb)) . ";\r\n" . $DATA[1] . ";\r\n" . 'ALTER TABLE ' . $this->mFd($this->mTb($tb)) . ' auto_increment=1' . ";\r\n";
	}
	
	// 获取表的字段信息
	public function getFdInfo($tb,$fd=''){
		$this->initConnect();
		$res=mysqli_query($this->mConns[$this->mIdentifier], 'select * from ' . $this->mFd($this->mTb($tb)) . ' limit 1');
		$fds=mysqli_fetch_fields($res);
		$reArr=array();
		$consArr=get_defined_constants();
		$typeArr=array();
		$flagArr=array();
		foreach($consArr as $ky=>$vl){
			if(strpos($ky, 'MYSQLI_') === 0){
				if(strpos($ky, 'MYSQLI_TYPE_') === 0){
					$typeArr[$vl]=str_replace('MYSQLI_TYPE_', '', $ky);
				}
				if(strpos($ky, '_FLAG') !== false){
					$flagArr[str_replace(array('MYSQLI_','_FLAG'), '', $ky)]=$vl;
				}
			}
		}
		foreach($fds as $i=>$cfd){
			$flags=$cfd->flags;
			$name=$cfd->name;
			if($fd != '' && $name != $fd){
				continue;
			}
			$reArr[$i]['index']=$i;
			$reArr[$i]['name']=$name;
			// 相关映射详见附加说明信息
			$reArr[$i]['ID']=($flags & $flagArr['AUTO_INCREMENT']) ? 1 : 0;
			$reArr[$i]['isKey']=($flags & $flagArr['PRI_KEY']) ? 1 : 0;
			$reArr[$i]['isNull']=($flags & $flagArr['NOT_NULL']) ? 0 : 1;
			$reArr[$i]['length']=$cfd->max_length;
			$reArr[$i]['type']=$typeArr[$cfd->type];
		}
		$this->freeResult($res);
		return $reArr;
	}
	
	// 压缩及修复数据库
	public function compress(){
		return false;
	}
	
	// 关闭数据库连接
	public function close(){
		if(isset($this->mConns[$this->mIdentifier]) && $this->mConns[$this->mIdentifier]){
			mysqli_close($this->mConns[$this->mIdentifier]);
		}
		unset($this->mConns[$this->mIdentifier]);
	}
	
	// 事务处理函数
	public function trans($type){
		$this->initConnect();
		switch($type){
			case 'start':
				mysqli_autocommit($this->mConns[$this->mIdentifier], false);
				break;
			case 'end':
				mysqli_commit($this->mConns[$this->mIdentifier]);
				mysqli_autocommit($this->mConns[$this->mIdentifier], true);
				break;
			case 'back':
				mysqli_rollback($this->mConns[$this->mIdentifier]);
				mysqli_autocommit($this->mConns[$this->mIdentifier], true);
				break;
		}
	}
	
	// //////////////////////////////////////////////////
	// ////////////////////内部私有方法////////////////////
	
	// 根据记录集返回限定记录
	private function fetchArray($rs,$offset=-1,$pagesize=0,$dxFd=''){
		if($pagesize > 0){
			mysqli_data_seek($rs, $offset);
		}else{
			$pagesize=$offset === -1 ? 1 : $offset;
		}
		$info=array();
		while(($r=mysqli_fetch_array($rs, 1)) && $pagesize){
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
		if(is_resource($rs)){
			mysqli_free_result($rs);
		}
	}
}
// /--------------附加说明信息----------------
/*
 * getFdInfo($tb,$fd)方法相关说明： flags映射-> NOT_NULL:1 PRI_KEY:2 UNIQUE_KEY:4 MULTIPLE_KEY:8 BLOB:16 UNSIGNED:32 ZEROFILL:64 AUTO_INCREMENT:512 TIMESTAMP:1024 SET:2048 NUM:32768 PART_KEY:16384 GROUP:32768 type映射-> DECIMAL:0 TINY:1 SHORT:2 LONG:3 FLOAT:4 DOUBLE:5 NULL:6 TIMESTAMP:7 LONGLONG:8 INT24:9 DATE:10 TIME:11 DATETIME:12 YEAR:13 NEWDATE:14 ENUM:247 SET:248 TINY_BLOB:249 MEDIUM_BLOB:250 LONG_BLOB:251 BLOB:252 VAR_STRING:253 STRING:254 CHAR:1 INTERVAL:247 GEOMETRY:255 NEWDECIMAL:246 BIT:16
 */
?>