<?php
/*
 * 说明：
 * 1.此类需要php5.0以上版本
 * 2.此类用于操作MsSQL数据库,php.ini要求扩展php_mysql.dll模块
 */
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Db.class.php';
class MysqlDb extends Db implements DbInterface{
	// 保存所有查询的数量;
	public function __construct($dbConfig){ // 构造函数，初始化变量并执行数据库函数
		$this->mType='mysql';
		$this->initConfig($dbConfig);
	}

	public function __destruct(){
		$this->close();
	}
	// ///////////////////核心操作方法////////////////////////////
	public function connect($dbConfig){
		/* 连接数据库前确保已经关闭，如果没有提供连接参数数组则使用系统上次连接参数； */
		$this->close();

		$connFn=isset($dbConfig['pconnect']) && $dbConfig['pconnect'] ? 'mysql_pconnect' : 'mysql_connect';
		try{
			$this->mConns[$this->mIdentifier]=@$connFn($dbConfig['DSN'], $dbConfig['user'], $dbConfig['pass']);
			if(!$this->mConns[$this->mIdentifier]){
				return false;
			}
			if(!mysql_select_db($dbConfig['dbname'], $this->mConns[$this->mIdentifier])){
				return false;
			}
			mysql_query('set names UTF8', $this->mConns[$this->mIdentifier]);
		}catch(Exception $e){
			$this->debug($e->getMessage());
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
			$q=mysql_query($sql, $this->mConns[$this->mIdentifier]);
			return $this->fetchArray($q, -1, 0, $dxFd);
		}else{
			mysql_query($sql, $this->mConns[$this->mIdentifier]);
			return mysql_affected_rows($this->mConns[$this->mIdentifier]);
		}
	}
	
	// 列出所有表[系统/用户]
	public function tables($type='all'){
		$this->initConnect();
		$vt=$type == 'table' ? 'where ' . $this->mFd('Version') . ' is not null' : ($type == 'view' ? 'where ' . $this->mFd('Version') . ' is null' : '');
		$res=mysql_query('show table status ' . $vt, $this->mConns[$this->mIdentifier]);
		$reArr=array();
		while($r=mysql_fetch_array($res, 2)){
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
		$res=mysql_query('show create table ' . $this->mFd($this->mTb($tb)), $this->mConns[$this->mIdentifier]);
		$DATA=mysql_fetch_array($res, 2);
		$this->freeResult($res);
		return 'DROP TABLE IF EXISTS ' . $this->mFd($this->mTb($tb)) . ";\r\n" . $DATA[1] . ";\r\n" . 'ALTER TABLE ' . $this->mFd($this->mTb($tb)) . ' auto_increment=1' . ";\r\n";
	}
	
	// 获取表的字段信息
	public function getFdInfo($tb,$fd=''){
		$this->initConnect();
		$res=mysql_list_fields($this->mDbCfg['dbname'], $this->mTb($tb), $this->mConns[$this->mIdentifier]);
		$cols=mysql_num_fields($res);
		$reArr=array();
		for($i=0; $i < $cols; $i++){
			$flags=strtolower(mysql_field_flags($res, $i));
			$name=mysql_field_name($res, $i);
			if($fd != '' && $name != $fd){
				continue;
			}
			$reArr[$i]['index']=$i;
			$reArr[$i]['name']=$name;
			$reArr[$i]['ID']=(false !== strpos($flags, 'auto_increment')) ? 1 : 0;
			$reArr[$i]['isKey']=(false !== strpos($flags, 'primary_key')) ? 1 : 0;
			$reArr[$i]['isNull']=(false !== strpos($flags, 'not_null')) ? 0 : 1;
			$reArr[$i]['length']=mysql_field_len($res, $i);
			$reArr[$i]['type']=mysql_field_type($res, $i);
			$reArr[$i]['unsigned']=(false !== strpos($flags, 'unsigned')) ? 'unsigned' : '';
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
			mysql_close($this->mConns[$this->mIdentifier]);
		}
		unset($this->mConns[$this->mIdentifier]);
	}
	// 事务处理函数
	public function trans($type){
		$this->initConnect();
		$sql='';
		switch($type){
			case 'start':
				$sql='begin;set autocommit=0;';
				break;
			case 'end':
				$sql='commit;set autocommit=1;';
				break;
			case 'back':
				$sql='rollback;set autocommit=1;';
				break;
		}
		mysql_query($sql, $this->mConns[$this->mIdentifier]);
	}
	
	// //////////////////////////////////////////////////
	// ////////////////////内部私有方法////////////////////
	
	// 根据记录集返回限定记录
	private function fetchArray($rs,$offset=-1,$pagesize=0,$dxFd=''){
		if($pagesize > 0){
			mysql_data_seek($rs, $offset);
		}else{
			$pagesize=$offset === -1 ? 1 : $offset;
		}
		$info=array();
		while(($r=mysql_fetch_array($rs, 1)) && $pagesize){
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
			mysql_free_result($rs);
		}
	}
}
?>