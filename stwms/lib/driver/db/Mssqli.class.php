<?php
/*
 * 说明：
 * 1.此类需要php5.0以上版本
 * 2.此类用于操作MsSQL数据库,php.ini要求扩展php_mssql.dll模块
 */
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Db.class.php';
class MssqliDb extends Db implements DbInterface{
	// 保存所有查询的数量;
	public function __construct($dbConfig){ // 构造函数，初始化变量并执行数据库函数
		$this->mType='mssqli';
		$this->initConfig($dbConfig);
	}

	public function __destruct(){
		$this->close();
	}
	// ///////////////////核心操作方法////////////////////////////
	public function connect($dbConfig){
		/* 连接数据库前确保已经关闭，如果没有提供连接参数数组则使用系统上次连接参数； */
		$this->close();
		
		$connFn=isset($dbConfig['pconnect']) && $dbConfig['pconnect'] ? 'mssql_pconnect' : 'mssql_connect';
		try{
			$this->mConns[$this->mIdentifier]=@$connFn($dbConfig['DSN'], $dbConfig['user'], $dbConfig['pass']);
			if(!$this->mConns[$this->mIdentifier]){
				return false;
			}
			if(!mssql_select_db($dbConfig['dbname'], $this->mConns[$this->mIdentifier])){
				return false;
			}
		}catch(Exception $e){
			$this->debug($e->getMessage());
		}
	}

	/*
	 * 功能:查询数据API
	 * 1.'select'语句放回查询的记录数组;
	 * 2.$ifMod说明是否需要标示查询字段;
	 * 注意:此为最原始查询方法，不做表名前置处理,
	 * 由于传递给$sql需要转码为GB2312从而解决数据入口问题
	 *
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
		if(preg_match("/^\s*(select.*)limit\s+([0-9]+)(,([0-9]+))?$/i", $sql, $matchs)){
			$sql=$matchs[1];
			$offset=$matchs[2];
			$pagesize=$matchs[4];
			$q=mssql_query(iconv('UTF-8', 'GB2312', $sql), $this->mConns[$this->mIdentifier]);
			return $this->fetchArray($q, $offset, $pagesize, $dxFd);
		}else if(preg_match("/^(select.*)$/i", $sql, $matchs)){
			$sql=$matchs[1];
			$q=mssql_query(iconv('UTF-8', 'GB2312', $sql), $this->mConns[$this->mIdentifier]);
			return $this->fetchArray($q, -1, 0, $dxFd);
		}else{
			mssql_query(iconv('UTF-8', 'GB2312', $sql), $this->mConns[$this->mIdentifier]);
			return mssql_rows_affected($this->mConns[$this->mIdentifier]);
		}
	}
	
	// 列出所有表[系统/用户]
	public function tables($type='all'){
		$this->initConnect();
		// mssql数据库获取
		switch($type){
			case 'system':
				$sql='SELECT name FROM sysobjects WHERE xtype = \'S\'';
				break;
			case 'table':
				$sql='SELECT name FROM sysobjects WHERE (OBJECTPROPERTY(id, \'IsMSShipped\') = 0) AND (xtype = \'U\')';
				break;
			case 'view':
				$sql='SELECT name FROM sysobjects WHERE (OBJECTPROPERTY(id, \'IsMSShipped\') = 0) AND (xtype = \'V\')';
				break;
			default:
				$sql='SELECT name FROM sysobjects WHERE ((OBJECTPROPERTY(id, \'IsMSShipped\') = 0) AND (xtype = \'U\')) or (xtype = \'S\') or ((OBJECTPROPERTY(id, \'IsMSShipped\') = 0) AND (xtype = \'V\'))';
				break;
		}
		$rs=mssql_query($sql, $this->mConns[$this->mIdentifier]);
		$reArr=array();
		while($r=$this->toArray($rs)){
			if($type == 'user' && $this->mTbPrefix != ''){
				if(strpos($r['name'], $this->mTbPrefix) === 0){
					$reArr[]=$r['name'];
				}
			}else{
				$reArr[]=$r['name'];
			}
		}
		$this->freeResult($rs);
		return $reArr;
	}
	// 导出指定表结构
	public function tbCreate($tb){
		$fdIArr=$this->getFdInfo($tb);
		$infoArr=array();
		$kyArr=array();
		$alterArr=array();
		foreach($fdIArr as $infos){
			if(strpos($infos['type'], 'char') !== false || $infos['type'] == 'varbinary'){
				$width='(' . $infos['length'] . ') ';
			}else if($infos['type'] == 'numeric' || $infos['type'] == 'decimal'){
				$width='(' . $infos['precision'] . ',' . $infos['scale'] . ') ';
			}else{
				$width='';
			}
			$infoArr[]='[' . $infos['name'] . '] [' . $infos['type'] . '] ' . $width . ($infos['ID'] ? ' IDENTITY (1, 1) ' : ' ') . (trim($infos['collation']) ? ' COLLATE ' . $infos['collation'] : '') . ' ' . ($infos['isNull'] ? 'NULL' : 'NOT NULL');
			
			if($infos['isKey']){ // 主键定义
				$kyArr[]='[' . $infos['name'] . ']';
			}
			if($infos['default']){ // 默认值定义
				$alterArr[]='CONSTRAINT [DF_info_' . $infos['name'] . '] DEFAULT (' . $infos['default'] . ') FOR [' . $infos['name'] . ']';
			}
		}
		if(!empty($kyArr)){
			$alterArr[]='CONSTRAINT [PK_' . $this->mTb($tb) . '] PRIMARY KEY CLUSTERED (' . implode(',', $kyArr) . ')';
		}
		
		$dUser='[dbo].';
		$spliter="\r\nGO\r\n";
		$drop='IF EXISTS (SELECT * FROM dbo.sysobjects WHERE id = object_id(N\'' . $dUser . '[' . $this->mTb($tb) . ']\') AND OBJECTPROPERTY(id,N\'IsUserTable\') = 1) DROP TABLE ' . $dUser . '[' . $this->mTb($tb) . ']' . $spliter;
		$create='CREATE TABLE [' . $this->mTb($tb) . '](' . implode(',', $infoArr) . ') ON [PRIMARY]' . $spliter;
		$alter=empty($alterArr) ? '' : 'ALTER TABLE ' . $dUser . '[' . $this->mTb($tb) . '] ADD ' . implode(',', $alterArr) . ' ON [PRIMARY]' . $spliter;
		return $drop . $create . $alter;
	}
	
	// 获取表的字段信息
	public function getFdInfo($tb,$fd=''){
		$this->initConnect();
		$sql='SELECT [index]=a.colorder,[name]=a.name,[ID]=case when COLUMNPROPERTY( a.id,a.name, \'IsIdentity\')=1 then 1 else 0 end,[isKey]=case when exists(SELECT 1 FROM sysobjects where xtype=\'PK\' and name in (SELECT name FROM sysindexes WHERE indid in(SELECT indid FROM sysindexkeys WHERE id = a.id AND colid=a.colid))) then 1 else 0 end,[type]=b.name,[length]=a.prec,[byte]=a.length,[precision]=COLUMNPROPERTY(a.id,a.name,\'PRECISION\'),[scale]=isnull(COLUMNPROPERTY(a.id,a.name,\'Scale\'),0),[isNull]=case when a.isnullable=1 then 1 else 0 end,[default]=isnull(e.text,\' \'),[collation]=a.collation FROM syscolumns a left join systypes b on a.xtype=b.xusertype inner join sysobjects d on a.id=d.id and d.xtype=\'U\' and d.name <> \'dtproperties\' left join syscomments e on a.cdefault=e.id where d.name= \'' . $this->mTb($tb) . '\' ' . ($fd != '' ? 'and a.name=\'' . $fd . '\'' : '') . ' order by a.colorder,a.id';
		$rs=$this->mConns[$this->mIdentifier]->Execute($sql);
		$info=array();
		while(!$rs->EOF){
			$r=$this->toArray($rs);
			if(!$r){
				break;
			}
			$info[]=$r;
		}
		$this->freeResult($rs);
		return $info;
	}

	// 压缩及修复数据库
	public function compress(){
		return false;
	}
	
	// 关闭数据库连接
	public function close(){
		if(isset($this->mConns[$this->mIdentifier]) && $this->mConns[$this->mIdentifier]){
			mssql_close($this->mConns[$this->mIdentifier]);
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
			mssql_data_seek($rs, $offset);
		}else{
			$pagesize=$offset === -1 ? 1 : $offset;
		}
		$info=array();
		while(($r=$this->toArray($rs)) && $pagesize){
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
			mssql_free_result($rs);
		}
	}
	
	// 根据记录集产生数组,$type=1:关联索引；$type=2:数字索引；$type=3:数字和关联索引；
	private function toArray($rs,$type=1){
		$reArr=mssql_fetch_array($rs, $type);
		if(is_array($reArr)){
			foreach($reArr as $ky=>$vl){
				// 此处对MSSQL存储的字符集进行转码操作，解决数据出口
				$reArr[$ky]=is_string($vl) ? iconv('GB2312', 'UTF-8', $vl) : $vl;
			}
			return $reArr;
		}else{
			return false;
		}
	}
}
?>