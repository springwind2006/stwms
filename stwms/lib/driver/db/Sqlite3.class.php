<?php
/*
 * 说明：
 * 1.此类需要php5.0以上版本
 * 2.此类用于操作sqlite3或sqlite3需要在php.ini 中扩展 php_pdo.dll、php_pdo_sqlite.dll的支持
 * 3.此类支持事件回溯功能
 */
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Db.class.php';
class Sqlite3Db extends Db implements DbInterface{
	// 保存所有查询的数量;
	public function __construct($dbConfig){ // 构造函数，初始化变量并执行数据库函数
		$this->mType='sqlite3';
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
		$dArr=array();
		if(defined('PDO_ATTR_PERSISTENT')){
			$dArr[PDO_ATTR_PERSISTENT]=$dbConfig['DSN'] == ':memory:' ? true : false;
			$dArr[PDO_ATTR_PERSISTENT]=$dbConfig['pconnect'] ? true : false;
		}
		$dbPath=strtolower($dbConfig['DSN']) == ':memory:' ? $dbConfig['DSN'] : $dbConfig['DSN'];
		try{
			$this->mConns[$this->mIdentifier]=@new PDO("sqlite2:" . $dbPath, $dbConfig['user'], $dbConfig['pass'], $dArr);
		}catch(PDOException $e){
		}
		if(!isset($this->mConns[$this->mIdentifier]) || !$this->mConns[$this->mIdentifier]){
			try{
				$this->mConns[$this->mIdentifier]=@new PDO("sqlite:" . $dbPath, $dbConfig['user'], $dbConfig['pass'], $dArr);
			}catch(PDOException $e){
				$this->debug($e->getMessage());
			}
		}
	}

	/*
	 * 功能:查询数据API
	 * 1.'select'语句放回查询的记录数组;
	 * 2.$ifMod说明是否需要标示查询字段;
	 * 注意:此为最原始查询方法，不做表名前置处理
	 */
	public function query($sql,$ifMod=1,$dxFd=''){
		$this->initConnect();
		if($ifMod){
			$sql=str_replace('__PREFIX__', $this->mTbPrefix,$sql);
			$this->mSql($sql);
		}
		$this->mLastSql=$sql;
		
		if(!$this->mExecSql){
			return false;
		}
		
		$this->mQueryNum++;
		if(stripos(trim($sql),'select ')===0){
			$q=$this->mConns[$this->mIdentifier]->query($sql);
			return $this->fetchArray($q, -1, 0, $dxFd);
		}else{
			
			return $this->mConns[$this->mIdentifier]->exec($sql);
		}
	}
	
	// 列出所有表[系统/用户]
	public function tables($type='all'){
		$this->initConnect();
		$vt=($type == 'table' || $type == 'view') ? ('where type=\'' . $type . '\'') : '';
		$tbRes=$this->mConns[$this->mIdentifier]->query('select tbl_name from sqlite_master ' . $vt);
		$reArr=array();
		while($tbs=$tbRes->fetch(2)){
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
		$tbRes=$this->mConns[$this->mIdentifier]->query('select * from sqlite_master where tbl_name=\'' . $this->mTb($tb) . '\'');
		$tbs=$tbRes->fetch(2);
		$this->freeResult($tbRes);
		return $tbs['sql'];
	}
	
	// 获取表的字段信息
	public function getFdInfo($tb,$fd=''){
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
			$fdArr[$dx]['length']=isset($tLen[3]) ? $tLen[3] : '';
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
		return $this->mConns[$this->mIdentifier]->exec("VACUUM");
	}
	
	// 关闭数据库连接
	public function close(){
		if(isset($this->mConns[$this->mIdentifier]) && $this->mConns[$this->mIdentifier]){
			$this->mConns[$this->mIdentifier]=null;
		}
		unset($this->mConns[$this->mIdentifier]);
	}
	// 事务处理函数
	public function trans($type){
		$this->initConnect();
		switch($type){
			case 'start':
				$this->mConns[$this->mIdentifier]->beginTransaction();
				break;
			case 'end':
				$this->mConns[$this->mIdentifier]->commit();
				break;
			case 'back':
				$this->mConns[$this->mIdentifier]->rollBack();
				break;
		}
	}
	
	// //////////////////////////////////////////////////
	// ////////////////////内部私有方法////////////////////
	
	// 根据记录集返回限定记录
	private function fetchArray($rs,$offset=-1,$pagesize=0,$dxFd=''){
		$info=array();
		if(!$rs){return $info;}
		if($pagesize > 0){
			$rs->fetch(2, PDO::FETCH_ORI_ABS, $offset);
		}else{
			$pagesize=$offset === -1 ? 1 : $offset;
		}
		
		while(($r=$rs->fetch(2)) && $pagesize){
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
		if($rs){
			$rs->closeCursor();
		}
	}
}
?>