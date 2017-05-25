<?php
/*
 * 说明： 1.此类需要php5.0以上版本 2.此类用于操作sqlite2数据库，需要php_pdo.dll,php_sqlite.dll
 */
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Db.class.php';
class Sqlite2Db extends Db implements DbInterface{
	// 保存所有查询的数量;
	public function __construct($dbConfig){ // 构造函数，初始化变量并执行数据库函数
		$this->mType='sqlite2';
		$this->initConfig($dbConfig);
	}

	public function __destruct(){
		$this->close();
	}
	// ///////////////////核心操作方法////////////////////////////
	public function connect($dbConfig){
		/* 连接数据库前确保已经关闭，如果没有提供连接参数数组则使用系统上次连接参数； */
		$this->close();
		
		// 数据库文件不存在且没有设置自动创建，则返回；
		if(!is_file($dbConfig['DSN']) && !$dbConfig['create']){
			return false;
		}
		$connFn=isset($dbConfig['pconnect']) && $dbConfig['pconnect'] ? 'sqlite_popen' : 'sqlite_open';
		try{
			$this->mConns[$this->mIdentifier]=@$connFn($dbConfig['DSN']);
			if(!$this->mConns[$this->mIdentifier]){
				return false;
			}
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
			$q=sqlite_query($this->mConns[$this->mIdentifier], $sql);
			return $this->fetchArray($q, -1, 0, $dxFd);
		}else{
			sqlite_query($this->mConns[$this->mIdentifier], $sql);
			return sqlite_changes($this->mConns[$this->mIdentifier]);
		}
	}
	
	// 列出所有表[系统/用户]
	public function tables($type='all'){
		$this->initConnect();
		$vt=($type == 'table' || $type == 'view') ? ('where type=\'' . $type . '\'') : '';
		$tbRes=sqlite_query($this->mConns[$this->mIdentifier], 'select tbl_name from sqlite_master ' . $vt);
		$reArr=array();
		while($tbs=sqlite_fetch_array($tbRes, 1)){
			if($this->mTbPrefix != '' && strpos($tbs['tbl_name'], $this->mTbPrefix) !== 0){
				continue;
			}
			$reArr[]=$tbs['tbl_name'];
		}
		$this->freeResult($tbRes);
		return array_unique($reArr);
	}
	// 导出指定表结构
	public function tbCreate($tb){
		$this->initConnect();
		$tbRes=sqlite_query($this->mConns[$this->mIdentifier], 'select * from sqlite_master where tbl_name=\'' . $this->mTb($tb) . '\'');
		$tbs=sqlite_fetch_array($tbRes, 1);
		$this->freeResult($tbRes);
		return $tbs['sql'];
	}
	
	// 获取表的字段信息
	public function getFdInfo($tb,$fd=''){
		$this->initConnect();
		$tbSQL=$this->tbCreate($tb);
		$fdArr=array();
		preg_match("/^[^\(]+\((.+?)\)[^\)]*$/", str_replace(array("\r","\n"), '', $tbSQL), $match);
		$fdInfos=explode(',', $match[1]);
		foreach($fdInfos as $dx=>$cfd){
			$ifArr=preg_split("/\s+/", $cfd);
			if($fd != '' && $ifArr[0] != $fd){
				continue;
			}
			preg_match("/^(\w+)(\((\d+)\))?$/", $ifArr[1], $tLen);
			$fdArr[$dx]['index']=$dx;
			$fdArr[$dx]['name']=$ifArr[0];
			$fdArr[$dx]['type']=$tLen[1];
			if(isset($tLen[3])){
				$fdArr[$dx]['length']=$tLen[3];
			}
			array_shift($ifArr);
			array_shift($ifArr);
			$flag=strtoupper(implode(' ', $ifArr));
			$fdArr[$dx]['isNull']=strpos($flag, 'NOT NULL') !== false ? 0 : 1;
			$fdArr[$dx]['isKey']=strpos($flag, 'PRIMARY KEY') !== false ? 1 : 0;
			$fdArr[$dx]['ID']=($fdArr[$dx]['isKey'] && strtoupper($fdArr[$dx]['type']) == 'INTEGER') || strpos($flag, 'AUTOINCREMENT') !== false ? 1 : 0;
			if(strpos($flag, 'DEFAULT') !== false){
				preg_match("/DEFAULT\s+(.+?)$/i", $flag, $defaults);
				$fdArr[$dx]['default']=str_replace(array('\'','"'), '', $defaults[1]) . '';
			}else{
				$fdArr[$dx]['default']='';
			}
			unset($ifArr);
			unset($tLen);
			unset($flag);
			unset($defaults);
		}
		return $fdArr;
	}
	
	// 压缩及修复数据库
	public function compress(){
		$this->initConnect();
		return sqlite_query($this->mConns[$this->mIdentifier], "VACUUM");
	}
	
	// 关闭数据库连接
	public function close(){
		if(isset($this->mConns[$this->mIdentifier]) && $this->mConns[$this->mIdentifier]){
			sqlite_close($this->mConns[$this->mIdentifier]);
		}
		unset($this->mConns[$this->mIdentifier]);
	}
	// 事务处理函数
	public function trans($type){
		return false;
	}
	
	// //////////////////////////////////////////////////
	// ////////////////////内部私有方法////////////////////
	
	// 根据记录集返回限定记录
	private function fetchArray($rs,$offset=-1,$pagesize=0,$dxFd=''){
		if($pagesize > 0){
			sqlite_seek($rs, $offset);
		}else{
			$pagesize=$offset === -1 ? 1 : $offset;
		}
		$info=array();
		while(($r=sqlite_fetch_array($rs, 1)) && $pagesize){
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
		return true;
	}
}
?>