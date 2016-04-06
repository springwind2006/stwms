<?php

// ////////////////////数据库操作辅助类///////////////////////
class Db{
	// API公共属性
	public $mIsLoop=1; // 获取偏移ID字段时是否循环获取
	public $mIsAutoPara=1; // 是否开启自动识别select条件参数,开启自动参数必须采用操作标识符:where/order/limit/group
	public $mQueryNum=0; //执行查询的数量
	public $mExecSql=true; //是否执行查询
	protected $mConns=array(); // 数据库内部连接
	protected $mDbCfg; // 保存数据库配置信息和操作指针
	protected $mFilterHandle; //查询数据过滤函数句柄
	protected $mType; //数据库类型
	protected $mIdentifier; //连接标识
	protected $mTbPrefix; //数据表前缀
	protected $mLastSql=''; //最后一次执行的查询语句
	
	//私有属性
	private static $msCache=array();
	
	// 加载数据库操作对象
	public static function load($config,$isNew=0){
		$type=(is_array($config) ? strtolower($config['type']) : 'xml');
		$class_path=dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . ucfirst($type) . '.class.php';
		if(!is_file($class_path)){
			return false;
		}
		
		include_once $class_path;
		
		if($type != 'xml' || ($type == 'xml' && !is_array($config))){
			$isNew=0;
		}
		
		if(!$isNew){
			$identifier=md5(serialize($config));
			if(!isset(self::$msCache[$identifier])){
				self::$msCache[$identifier]=new DbDriver($type, $config);
			}
			return self::$msCache[$identifier];
		}else{
			return new DbDriver($type, $config);
		}
	}
	
	// 检查PHP环境，以确定所支持的数据库类型
	public static function check(){
		$drivers=glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . '*.class.php');
		$supports=array();
		foreach($drivers as $driver){
			$type=strtolower(basename($driver, '.class.php'));
			switch($type){
				case 'mysqli':
					if(class_exists('mysqli',false)){
						$supports[]='mysqli';
					}
					break;
				case 'mysql':
					if(function_exists('mysql_close')){
						$supports[]='mysql';
					}
					break;
				case 'mssql':
					if(class_exists('com',false)){
						$conn=new com('adodb.connection', null, '65001');
						if($conn){
							$supports[]='mssql';
							$supports[]='access';
							unset($conn);
						}
					}
					break;
				case 'mssqli':
					if(function_exists('mssql_close')){
						$supports[]='mssqli';
					}
					break;
				case 'sqlite3':
					if(class_exists('PDO',false) && in_array('sqlite', PDO::getavailabledrivers())){
						$supports[]='sqlite3';
					}
					break;
				case 'sqlite2':
					if(function_exists('sqlite_close')){
						$supports[]='sqlite2';
					}else if(class_exists('PDO',false) && in_array('sqlite2', PDO::getavailabledrivers())){
						$supports[]='sqlite2';
					}
					break;
				case 'xml':
					if(function_exists('xml_set_object')){
						$supports[]='xml';
					}
					break;
			}
		}
		return $supports;
	}

	public static function getlimit($page,$pagesize,$total){
		if($total < $pagesize){
			$page_count=1;
		}else{
			if(!($total % $pagesize)){
				$page_count=$total / $pagesize;
			}else{
				$page_count=floor($total / $pagesize) + 1;
			}
		}
		if($page > $page_count){
			$page=1;
		}
		if($total <= $pagesize){
			return 'limit 0,' . $total;
		}
		return 'limit ' . ($page - 1) * $pagesize . ',' . $pagesize;
	}
	
	public static function debug($msg){
		
	}
	
	// ///////////////////////////////////所有数据库对象公用方法/////////////////////////////////////////
	
	
	// 选择数据API
	public function select($tb,$data='*',$where='',$order='',$limit='',$group='',$dxFd=''){
		if($this->mIsAutoPara){ // 开启自动参数要求参数必须带操作标识符，扫描所有参数
			$paraArr=func_get_args();
			$this->autoPara($where, $order, $limit, $group, $paraArr);
			unset($paraArr);
		}else{
			$this->WOLG($where, 'where');
			$this->WOLG($order, 'order by');
			$this->WOLG($limit, 'limit');
			$this->WOLG($group, 'group by');
		}
		$this->mLike($where);
		$sql='select ' . $this->mFd($data) . ' from ' . $this->mFd($this->mTb($tb)) . $where . $group . $order . $limit;
		return $this->query($sql, 0, $dxFd);
	}
	
	// 统计制定条件的记录条数
	public function count($tb,$where='',$fd=null){
		return $this->statistics('count', $tb,$where,$fd);
	}
	public function sum($tb,$fd,$where=''){
		return $this->statistics('sum', $tb,$where,$fd);
	}
	public function max($tb,$fd,$where=''){
		return $this->statistics('max', $tb,$where,$fd);
	}
	public function min($tb,$fd,$where=''){
		return $this->statistics('min', $tb,$where,$fd);
	}
	public function avg($tb,$fd,$where=''){
		return $this->statistics('avg', $tb,$where,$fd);
	}
	public function getField($tb,$fd,$where='',$order='',$limit='',$group='',$para=null){
		if(empty($tb)||empty($fd)){return null;}
		$fds=($fd=='*' ? $this->fields($tb) : explode(',', $fd));
		$len=count($fds);
		if(is_int($para)){
			$limit='limit '.$para;
		}
		$handle=null;
		$dxfd=$fds[0];
		if($len==1){
			if(is_null($para)){//返回第一个字段的值
				$data=$this->getOne($tb,$fd,$where,$order,$limit,$group);
				return $data[$fd];
			}else{//返回满足条件的字段值的数组
				$handle=create_function('$data', 'return $data[\''.$fd.'\'];');
				$dxfd='';
			}
		}elseif($len==2){//返回关联数组
			$handle=create_function('$data', 'return $data[\''.$fds[1].'\'];');
		}elseif(is_string($para)){
			$handle=create_function('$data', 'unset($data[\''.$fds[0].'\']);return implode(\''.str_replace('\'','\\\'',$para).'\', $data);');
		}
		if(is_null($handle)){
			return $this->select($tb,$fd,$where,$order,$limit,$group,$dxfd);
		}
		$this->setFilter($handle);
		$datas=$this->select($tb,$fd,$where,$order,$limit,$group,$dxfd);
		$this->unsetFilter();
		return $datas;
	}
	
	// 获取一条记录
	public function getOne($tb,$data='*',$where='',$order='',$limit='limit 0,1',$group=''){
		if($this->mIsAutoPara){
			$paraArr=func_get_args();
			$this->autoPara($where, $order, $limit, $group, $paraArr);
			unset($paraArr);
		}else{
			$this->WOLG($where, 'where');
			$this->WOLG($order, 'order by');
			$this->WOLG($limit, 'limit');
			$this->WOLG($group, 'group by');
		}
		$this->mLike($where);
		$sql='select ' . $this->mFd($data) . ' from ' . $this->mFd($this->mTb($tb)) . $where . $group . $order . $limit;
		$rs=$this->query($sql, 0);
		return is_array($rs) && isset($rs[0]) ? $rs[0] : null;
	}
	// 插入数据 api
	public function insert($tb,$datas,$replace=false){
		if(!is_array($datas) || $tb == '' || count($datas) == 0){
			return false;
		}
		$fdArr=$this->fields($tb);
		$nDatas=array();
		$ID=$this->getIDKy($tb);
		foreach($datas as $ky=>$vl){
			if(in_array($ky, $fdArr) && $ky != $ID){
				$nDatas[$ky]=$vl; // !!过滤不存在的字段及自增键
			}
		}
		unset($datas);
		if(empty($nDatas)){
			return -1;
		}
		$cmd=$replace ? 'replace into' : 'insert into';
		$sql=$cmd . ' ' . $this->mFd($this->mTb($tb)) . '(' . $this->mFd(array_keys($nDatas)) . ') values (' . $this->mVl(array_values($nDatas)) . ')';
		return $this->query($sql, 0);
	}
	// 更新数据 api
	public function update($tb,$datas,$where=''){
		$this->WOLG($where, 'where');
		$this->mLike($where);
	
		$field='';
		if(is_string($datas) && $datas != ''){
			$field=$datas;
		}else if(is_array($datas) && count($datas) > 0){
			$fields=array();
			$fdArr=$this->fields($tb);
			$ID=$this->getIDKy($tb);
			foreach($datas as $k=>$v){
				if(!in_array($k, $fdArr) || $k == $ID){
					continue;// 过滤不存在的字段及自增键
				}
				
				switch(substr($v, 0, 2)){
					case '+=':
						$nv=substr($v, 2);
						if(is_numeric($nv)){
							$fields[]=$this->mFd($k) . '=' . $this->mFd($k) . '+' . $nv;
						}else{
							continue;
						}
						break;
					case '-=':
						$nv=substr($v, 2);
						if(is_numeric($nv)){
							$fields[]=$this->mFd($k) . '=' . $this->mFd($k) . '+' . $nv;
						}else{
							continue;
						}
						break;
					default:
						$fields[]=$this->mFd($k) . '=' . $this->mVl($v);
				}
			}
				
			$field=implode(',', $fields);
		}else{
			return false;
		}
		if(empty($field)){
			return 0;
		}
		$sql='update ' . $this->mFd($this->mTb($tb)) . ' set ' . $field . $where;
		return $this->query($sql, 0);
	}
	// 更新数据 api
	public function delete($tb,$where=''){
		$this->WOLG($where, 'where');
		$this->mLike($where);
		$sql='delete from ' . $this->mFd($this->mTb($tb)) . ' ' . $where;
		return $this->query($sql, 0);
	}
	
	// 删除指定的表
	public function dropTb($tb){
		return $this->query('drop table ' . $this->mFd($this->mTb($tb)));
	}
	
	// 查询表中是否有指定字段
	public function hasField($tb,$fd){
		return in_array($fd, $this->fields($tb), true);
	}
	// 查询表中是否有指定字段值
	public function hasFdVl($tb,$fd,$vl,$cnd=''){
		if($cnd != ''){
			$cnd=preg_replace("/^\s*and/i", '', trim($cnd));
			$cnd=' and ' . $cnd;
		}
		return !!$this->getOne($tb, $fd, 'where ' . $this->mFd($fd) . '=' . $this->mVl($vl) . ' ' . $cnd);
	}
	
	// 列出指定表的所有字段
	public function fields($tb){
		static $caches=array();
		if(!isset($caches[$tb])){
			foreach($this->getFdInfo($tb) as $vl){
				$caches[$tb][]=$vl['name'];
			}
		}
		return $caches[$tb];
	}
	
	// 获取表标识行名称，被设置为自增，通常为主键
	function getIDKy($tb){
		static $caches=array();
		if(!isset($caches[$tb])){
			$caches[$tb]='id';
			$fdArr=$this->getFdInfo($tb);
			foreach($fdArr as $vl){
				if($vl['ID']){
					$caches[$tb]=$vl['name'];
				}
			}
		}
		return $caches[$tb];
	}
	// 根据指定条件和排序获取自动增长的键名的最大值，最小值，上一个值和下一个值
	public function getID($tb,$type='min',$id='',$where='',$order=''){
		if($type != 'min' && $type != 'max' && $id == ''){
			return false;
		}
		$this->WOLG($where, 'where');
		$this->mLike($where);
		$this->WOLG($order, 'order by');
		$ID=$this->getIDKy($tb);
		$fdStr='';
		preg_match_all("/`(\w+)`/i", $order, $morder, PREG_PATTERN_ORDER);
		for($ci=0, $max=count($morder[0]); $ci < $max; $ci++){
			if($morder[0][$ci] != $ID){
				$fdStr=',' . str_replace('`', '', $morder[0][$ci]);
			}
		}
		$fdStr=$ID . $fdStr;
		$idArr=$this->select($tb, $fdStr, $where, $order);
		$minid=0;
		$maxid=0;
		$curIdArr=array();
		$indexIdArr=array();
		foreach($idArr as $fky=>$fdArr){
			array_push($curIdArr, intval($fdArr[$ID]));
			if(!$minid){
				$minid=intval($fdArr[$ID]);
			}
			if($fdArr[$ID] > $maxid){
				$maxid=intval($fdArr[$ID]);
			}
			if($fdArr[$ID] < $minid){
				$minid=intval($fdArr[$ID]);
			}
		}
		$indexIdArr=array_flip($curIdArr);
		$id=intval($id);
		$id=$id < $minid ? $minid : ($id > $maxid ? $maxid : $id);
		switch($type){
			case 'min':
				return $minid;
				break;
			case 'max':
				return $maxid;
				break;
			case 'next':
				if($indexIdArr[$id] + 1 > count($indexIdArr) - 1){
					return ($this->mIsLoop ? $curIdArr[0] : false);
				}else{
					return $curIdArr[$indexIdArr[$id] + 1];
				}
				break;
			case 'pre':
				if($indexIdArr[$id] - 1 < 0){
					return ($this->mIsLoop ? $curIdArr[count($indexIdArr) - 1] : false);
				}else{
					return $curIdArr[$indexIdArr[$id] - 1];
				}
				break;
		}
		return $minid;
	}	
	
	// 获取最后一条添加的字段值
	public function lastInsert($tb,$fd=''){
		$id=$this->getIDKy($tb);
		$id=!empty($fd) && empty($id) ? $fd : $id;
		$fd=empty($fd) ? $id : $fd;
		$info=$this->getOne($tb, ($id == $fd ? $id : $id . ',' . $fd), 'where `' . $id . '`>=0', 'order by `' . $id . '` desc');
		return (is_array($info) && isset($info[$fd]) ? $info[$fd] : -1);
	}
	
	// 以下方式用于设置过滤函数，慎用！！
	public function setFilter($handle){
		$this->mFilterHandle=$handle;
	}

	public function unsetFilter(){
		$this->mFilterHandle=NULL;
	}

	/*
	 * 功能：修改查询表的前置符，支持单表或多表处理
	 * 说明：多表格式示例=》
	 * =》tbcommodities,tbcommodities.tbshopid=tbshops.id|left
	 */
	public function mTb($tb){
		if(strpos($tb, ',') === false){
			$ctb=str_replace('`', '', $tb);
			if($this->mTbPrefix == '' || strpos($ctb, $this->mTbPrefix) === 0){
				return $tb;
			}else{
				return $this->mTbPrefix . $ctb;
			}
		}else{
			$tb=str_replace(' ', '', $tb);
			$att_tbs=array();
			$tb_sql='';
			foreach(explode(',', $tb) as $ky=>$vl){
				if(!$ky){
					$att_tbs[]=$vl;
					$tb_sql.=$this->mFd($this->mTb($vl));
				}else{
					$atts=explode('|', $vl);
					$fds=explode('=', $atts[0]);
					$catt_tb='';
					foreach($fds as $fd){
						$tf=explode('.', $fd);
						if(!in_array($tf[0], $att_tbs)){
							$att_tbs[]=$tf[0];
							$catt_tb=$tf[0];
						}
					}
					if($catt_tb){
						$tb_sql.=' ' . (isset($atts[1]) ? $atts[1] : 'inner') . ' join ';
						$tb_sql.=$this->mFd($this->mTb($catt_tb)) . ' on ' . $this->mFd($fds[0]) . '=' . $this->mFd($fds[1]);
					}
				}
			}
			return $tb_sql;
		}
	}

	/*
	 * 功能：修正数据库字段信息
	 * 说明：能够处理单表，多表，函数（单参数），别名
	 */
	public function mFd($fd){
		if(is_string($fd)){
			if('*' == $fd || false !== strpos($fd, '=')){ // 过滤部分关键字
				return $fd;
			}
			
			if(false !== strpos($fd, ',')){ // 处理分号分隔的多关键字
				return $this->mFd(explode(',', $fd));
			}
			
			// 存在别名处理，如info.id as mid
			if(strpos(strtolower($fd), ' as ') !== false){
				$sfd=substr($fd, 0, strpos(strtolower($fd), ' as '));
				$efd=trim(substr($fd, strpos(strtolower($fd), ' as ') + 4));
				return (strpos($sfd, '\'') !== false || is_numeric($sfd) ? $sfd : $this->mFd($sfd)) . ' as ' . $this->mFd($efd);
			}
			
			// 对函数的处理 aaa(sss);4,7
			if(strpos($fd, '(') !== false){
				$fn=substr($fd, 0, strpos($fd, '('));
				$spos=strpos($fd, '(') + 1;
				$epos=strpos($fd, ')');
				return $fn . '(' . $this->mFd(substr($fd, $spos, $epos - $spos)) . ')';
			}
			
			// 多表查询
			if(strpos($fd, '.') !== false){
				$fds=explode('.', $fd);
				$fds[0]=$this->mFd($this->mTb($fds[0]));
				$fds[1]=$this->mFd($fds[1]);
				return implode('.', $fds);
			}
			
			// 处理纯字符串
			$fd=str_replace(array('[',']','`'), '', $fd);
			if(false !== strpos($this->mType, 'mssql') || $this->mType == 'access'){
				return '[' . $fd . ']';
			}else if(false !== strpos($this->mType, 'mysql')){
				return '`' . $fd . '`';
			}
			return $fd;
		}else if(is_array($fd)){ // 处理数组
			$fd_str='';
			foreach($fd as $vl){
				$fd_str.=',' . $this->mFd($vl);
			}
			return substr($fd_str, 1);
		}
		return $fd;
	}
	
	// 对字段值两边加引号以及处理添加的值字段，以保证数据库安全
	public function mVl($vl){
		if(is_array($vl)){
			$vl_str='';
			foreach($vl as $iky=>$ivl){
				$vl_str.=',' . $this->mVl($ivl);
			}
			return substr($vl_str, 1);
		}else{
			if(is_string($vl) && trim($vl) === ''){
				return 'null';
			}
			// mysql数据库的处理
			if(strpos($this->mType, 'mysql') !== false){
				$this->initConnect();
				$fn=$this->mType . '_real_escape_string';
				if(get_magic_quotes_gpc()){
					$vl=stripslashes($vl);
				}
				if($this->mType == 'mysql'){
					return '\'' . $fn($vl,$this->mConns[$this->mIdentifier]) . '\'';
				}else{
					return '\'' . $fn($this->mConns[$this->mIdentifier], $vl) . '\'';
				}
				// sqlite,mssql,access数据库处理
			}elseif(strpos($this->mType, 'sqlite') !== false || strpos($this->mType, 'mssql') !== false){
				if(get_magic_quotes_gpc()){
					$vl=stripslashes($vl);
				}
				return '\'' . str_replace('\'', '\'\'', $vl) . '\'';
				// 其余类型直接消除系统默认添加
			}else if($this->mType == 'access'){
				if(get_magic_quotes_gpc()){
					$vl=stripslashes($vl);
				}
				return is_int($vl) ? $vl : '\'' . str_replace('\'', '\'\'', $vl) . '\'';
			}else{
				return '\'' . (get_magic_quotes_gpc() ? stripslashes($vl) : $vl) . '\'';
			}
		}
	}
	
	//获取最后一次执行的SQL语句
	public function getLastSql(){
		return $this->mLastSql;
	}
	
	/*
	 * 功能:恢复数据库
	 * 参数:restore($fPath='',$mType='');
	 * 说明:支持两种恢复方式:
	 * 1.从文件恢复.默认方式,$fPath为文件名,$mType为file或不设置;
	 * 2.从字符串恢复.$fPath为字符串,$mType为text;
	 */
	public function restore($fPath='',$mType=''){
		if(function_exists('set_magic_quotes_runtime')){
			set_magic_quotes_runtime(0);
		}
		if($fPath == ''){
			// 用于获取数组目录中的*.[type].db文件
			foreach(array('./','../') as $odir){
				if($handle=@opendir($odir)){
					while(false !== ($file=readdir($handle))){
						if($file != '.' && $file != '..' && preg_match("/\." . $this->mType . "\.\w+\s*$/i", $file)){
							if(is_file($odir . $file)){
								$fPath=$odir . $file;
								break 2;
							}
						}
					}
					@closedir($handle);
				}
			}
		}
		if($this->support($this->mType, 'file')){ // file型的数据库，执行文件恢复
			if(!is_file($fPath)){
				return false;
			}
			if($mType == '' || realpath($mType) == realpath($this->mDbCfg['DSN'])){
				$this->close();
				@unlink($this->mDbCfg['DSN']);
				copy($fPath, $this->mDbCfg['DSN']);
				$this->connect($this->mDbCfg);
			}else{
				@unlink($mType);
				copy($fPath, $mType);
			}
			return true;
		}else{ // web型的数据库，执行SQL恢复
			$snum=0;
			$this->trans('start'); // 开启事务加速恢复数据库
			
			if($mType != 'text'){
				// 从文件恢复
				if(!is_file($fPath)){
					return $snum;
				}
				$handle=fopen($fPath, 'r');
				$sql='';
				$lmax=filesize($fPath);
				if($handle){
					while(!feof($handle)){
						$bf=trim(fgets($handle, $lmax));
						if($bf && substr($bf, 0, 2) != '--' && substr($bf, 0, 2) != '/*'){
							if(substr($bf, -1, 1) != ';' && substr($bf, -2, 2) != 'GO'){
								$sql.=trim($bf);
							}else{
								if(substr($bf, -2, 2) != 'GO'){
									$sql.=trim($bf);
								}
								$this->query($sql, 0);
								$sql='';
							}
						}
					}
					fclose($handle);
				}
			}else{
				// 从字符串恢复
				$sqls=preg_split("/(?:(?:GO)|;\n)|(?:(?:GO)|;\r\n)|(?:(?:GO)|;\r)/", $fPath);
				unset($fPath); // MsSQL/MSSQL的语法分割符同时匹配
				foreach($sqls as $svl){
					if(trim($svl) != ''){
						$this->query(trim($svl), 0);
						$snum++;
					}
				}
				unset($sqls);
			}
			
			$this->trans('end');
			
			return $snum;
		}
	}

	/*
	 * 功能:备份数据库
	 * 参数:backup($tb='',$tp='all',$file='');
	 * 说明:
	 * 1.文件名选择:如果不提供$file参数,自动搜索所有参数，匹配'/[\/\\]/'即识别为路径；
	 * 2.备份类型选择:$tp为struct时只导出表结构；$tp为data时只导出表数据；$tp为all时导出表结构和数据;
	 * 3.备份表选择:$tb参数不匹配'/[\/\\]/'即识别为表名；
	 * 4.备份数据库类型:根据系统参数，自动识别数据库类型；
	 * 5.备份数据处理方式:file型数据库-直接放回；为web型数据库-提供文件保存路径，写入文件，否则返回字符串
	 */
	public function backup($tb='',$tp='all',$file=''){
		if($this->support($this->mType, 'file')){ // file型的数据库，执行文件恢复
			if($tb != ''){
				if(is_file($tb)){
					$path_parts=pathinfo($tb);
					$new_tb=$path_parts['dirname'] . '/bak' . $path_parts['basename'];
					unset($path_parts);
				}else{
					$new_tb=$tb;
				}
				copy($this->mDbCfg['DSN'], $new_tb);
			}else{
				copy($this->mDbCfg['DSN'], date('Ymd', time()) . 'bak.' . $this->mType . '.php');
			}
			return true;
		}
		$tb=preg_match("/[\\/\\\]/", $tb) ? '' : $tb;
		$tp=preg_match("/[\\/\\\]/", $tp) ? 'all' : $tp;
		if($file == ''){
			for($dx=0, $args=func_get_args(), $max=count($args); $dx < $max; $dx++){
				if(preg_match("/[\\/\\\]/", $args[$dx])){
					$file=$args[$dx];
				}
			}
		}
		if($tb !== ''){ // 只导出某些表，以","分割
			$tbs=array();
			$all_tbs=$this->tables();
			foreach(explode(',', str_replace(' ', '', $tb)) as $vl){
				if(in_array($vl, $all_tbs)){
					array_push($tbs, $vl);
				}
			}
		}else{ // 导出所有表
			$tbs=$this->tables();
		}
		
		$cnt_str='-- <?php die(0);?>' . "\r\n" . '-- [' . $this->mType . ']' . "\r\n";
		if($file != ''){
			$fp=fopen($file, 'w+');
			fwrite($fp, $cnt_str);
		}
		$spliter=(strpos($this->mType, 'mssql') !== false ? "\r\nGO" : ';') . "\r\n";
		// ///以下遍历所有表结构及数据
		foreach($tbs as $tb_name){
			if($tp != 'data'){ /* 生成表结构 */
				$res=$this->tbCreate($tb_name);
				if($fp){
					fwrite($fp, $res);
				}else{
					$cnt_str.=$res;
				}
				unset($res);
			}
			if($tp != 'struct'){ /* 生成表数据 */
				$line_num=$all_cnt=$this->count($tb_name);
				$pagesize=100;
				$pkey=$this->getIDKy($tb_name);
				$kys=array_diff($this->fields($tb_name), array($pkey));
				array_walk($kys, array($this,'mFd'));
				$sql='insert into ' . $this->mFd($tb_name) . ' (' . implode(',', $kys) . ') values ';
				$cnum=0; // 记录总的查询行数
				for($mi=0; $mi < $all_cnt; $mi+=$pagesize){
					$res=$this->query('select * from ' . $this->mFd($tb_name) . ' limit ' . $mi . ',' . $pagesize);
					if(!empty($res)){
						foreach($res as $DATA){
							$line_num--;
							$values=array();
							foreach($DATA as $k=>$v){
								if($k == $pkey){
									continue;
								}
								$values[]=is_string($v) ? (trim($v) == '' ? 'NULL' : $this->mVl($v)) : $v;
							}
							if($fp){
								// 写入文件
								if(strpos($this->mType, 'mysql') !== false && !$cnum){
									fwrite($fp, $sql . "\r\n");
								}else if(strpos($this->mType, 'mssql') !== false){
									fwrite($fp, $sql);
								}
								fwrite($fp, '(' . implode(',', $values) . ')' . (strpos($this->mType, 'mysql') !== false && $line_num ? ",\r\n" : $spliter));
							}else{
								// 返回字符串
								if(strpos($this->mType, 'mysql') !== false && !$cnum){
									$cnt_str.=$sql . "\r\n";
								}else if(strpos($this->mType, 'mssql') !== false){
									$cnt_str.=$sql;
								}
								$cnt_str.='(' . implode(',', $values) . ')' . (strpos($this->mType, 'mysql') !== false && $line_num ? ",\r\n" : $spliter);
							}
							unset($values);
							$cnum++;
						}
					}
					unset($res);
				}
			}
		}
		if($fp){
			fclose($fp);
		}
		return $cnt_str;
	}
	
	// ////////////////////////////////////////////////////////////////////
	// //////////////////////////内部通用，继承的类可用方法////////////////////
	
	// 数据过滤方法，用于设置查询数据的内部过滤
	protected function filterData($data){
		if(is_null($this->mFilterHandle)){
			return $data;
		}
		if(
			(is_string($this->mFilterHandle) && function_exists($this->mFilterHandle)) || 
			is_array($this->mFilterHandle)
		){
			return call_user_func($this->mFilterHandle, $data);
		}
		return $data;
	}
	
	// 自动选择操作参数
	protected function autoPara(&$where,&$order,&$limit,&$group,$oriParas){
		$this->mSql($oriParas);
		$is_matchs=array('where' => 0,'order' => 0,'limit' => 0,'group' => 0);
		foreach($oriParas as $sid=>$para){
			if($sid > 1){
				if(preg_match("/^\s*where/i", $para)){
					$where=' ' . $this->mTbs($para);
					$is_matchs['where']=1;
				}
				if(preg_match("/^\s*order\s+by/i", $para)){
					$order=' ' . $this->mTbs($para);
					$is_matchs['order']=1;
				}
				if(preg_match("/^\s*limit/i", $para)){
					$limit=' ' . $this->mTbs($para);
					$is_matchs['limit']=1;
				}
				if(preg_match("/^\s*group\s+by/i", $para)){
					$group=' ' . $this->mTbs($para);
					$is_matchs['group']=1;
				}
			}
		}
		if(!$is_matchs['where']){
			$where='';
		}
		if(!$is_matchs['order']){
			$order='';
		}
		if(!$is_matchs['limit']){
			$limit='';
		}
		if(!$is_matchs['group']){
			$group='';
		}
	}

	protected function mmTb($matches){
		return $this->mFd($this->mTb($matches[1])) . '.' . $this->mFd($matches[2]);
	}

	protected function mTbs($str,$pat="/[\[`]?(\w+)[^\w]*\.[^\w]*(\w+)[\]`]?/i"){
		if(strpos($str, '.') === false){
			return $str;
		}
		return preg_replace_callback($pat, array($this,'mmTb'), $str);
	}
	
	// 修正数据库查询语句
	protected function WOLG(&$str,$kw){
		$this->mSql($str);
		return $str=$this->mTbs($str == '' ? '' : ' ' . (strpos(strtolower($str), strtolower($kw)) !== false ? $str : $kw . ' ' . $str));
	}
	
	// 修正查询关键字标记（注：主要用于替换MySQL数据库用的`字段标记，使所有数据库的关键字支持`标记）
	protected function mSql(&$strs){
		if(isset($this->mType) && (false !== strpos($this->mType, 'mssql') || $this->mType == 'access' || false !== strpos($this->mType, 'sqlite'))){
			$str=(false !== strpos($this->mType, 'sqlite')) ? "\${1}" : "[\${1}]";
			if(is_array($strs)){
				foreach($strs as $k=>$v){
					$strs[$k]=preg_replace("/`(\w+)`/", $str, $v);
				}
			}else{
				$strs=preg_replace("/`(\w+)`/", $str, $strs);
			}
		}
		return $strs;
	}
	
	//修订查询语句中like关键字匹配符号
	protected function mLike(&$where){
		if($this->mType == 'access' && stripos($where, ' like ') !== false){
			$where=str_replace('$', '*', $where);
		}
	}
	
	// 规范配置文件的参数设置
	protected function initConfig(&$cfgArr){
		$default=array('DSN'=>'','user'=>'','pass'=>'','dbname'=>'','pconnect'=>false,'create'=>false,'pre'=>'');
		$cfgArr['DSN']=(false !== strpos($this->mType, 'mysql') && !empty($cfgArr['DSN'])) ? str_replace(',', ':', $cfgArr['DSN']) : $cfgArr['DSN'];
		$this->mTbPrefix=trim($cfgArr['pre']);
		unset($cfgArr['pre'],$cfgArr['create']);
		$this->mIdentifier=md5(serialize($cfgArr));
		$cfgArr=array_merge($default,$cfgArr);
		$this->mDbCfg=$cfgArr;
	}
	
	protected function initConnect(){
		if(!isset($this->mConns[$this->mIdentifier])){
			$this->connect($this->mDbCfg);
		}
		if(empty($this->mConns[$this->mIdentifier])){
			exit(0);
		}
	}
	
	
	// ////////////////////////////////////////////////////////////////////
	// //////////////////////////私有方法，仅在本类使用////////////////////////
	
	// 检查是否是支持的数据库类别
	private function support($dbType='',$type=''){
		$types=array('file' => array('text','xml','access','dbase','sqlite2','sqlite2'),'web' => array('mssql','mssqli','mysql','mysqli','oracle'));
		if($dbType == '' && $type == ''){
			return $types;
		}
		if($type == '' || !array_key_exists($type, $types)){
			return in_array($dbType, $types['file']) || in_array($dbType, $types['web']);
		}else{
			return in_array($dbType, $types[$type]);
		}
	}
	
	private function statistics($type,$tb,$where='',$fd=null){
		$this->WOLG($where, 'where');
		$this->mLike($where);
		$sql='select '.$type.'('.(empty($fd) ? '*' : $this->mFd($fd)).') as '.$this->mFd('cnum').' from ' . $this->mFd($this->mTb($tb)) . ' ' . $where;
		$reses=$this->query($sql, 0);
		return $reses ? floatval($reses[0]['cnum']) : 0;
	}
	
}


class DbDriver{
	private $mDb;
	private $mType;

	public function __construct($type,$config){
		$this->mType=strtolower($type);
		$db_name=ucfirst($this->mType) . 'Db';
		$this->mDb=new $db_name($config);
	}

	public function __call($fn,$args){
		return call_user_func_array(array($this->mDb,$fn), $args);
	}

	public function __get($name){
		return $this->mDb->$name;
	}

	public function __set($name,$value){
		return $this->mDb->$name=$value;
	}

	public function lastInsert($tb,$fd=''){
		return $this->mDb->lastInsert($tb, $fd);
	}

	public function setFilter($fn){
		return $this->mDb->setFilter($fn);
	}

	public function unsetFilter(){
		return $this->mDb->unsetFilter();
	}

	public function mTb($tb){
		return $this->mDb->mTb($tb);
	}

	public function mFd($fd){
		return $this->mDb->mFd($fd);
	}

	public function mVl($vl){
		return $this->mDb->mVl($vl);
	}

	public function getLastSql(){
		return $this->mDb->getLastSql();
	}
	
	public function connect($dbConfig){
		return $this->mDb->connect($dbConfig);
	}

	public function query($sql,$ifMod=1,$dxFd=''){
		return $this->mDb->query($sql, $ifMod, $dxFd);
	}

	public function select($tb,$data='*',$where='',$order='',$limit='',$group='',$dxFd=''){
		return $this->mDb->select($tb, $data, $where, $order, $limit, $group, $dxFd);
	}

	public function getOne($tb,$data='*',$where='',$order='',$limit='limit 0,1',$group=''){
		return $this->mDb->getOne($tb, $data, $where, $order, $limit, $group);
	}

	public function insert($tb,$datas,$replace=false){
		return $this->mDb->insert($tb, $datas, $replace);
	}

	public function update($tb,$datas,$where=''){
		return $this->mDb->update($tb, $datas, $where);
	}

	public function delete($tb,$where=''){
		return $this->mDb->delete($tb, $where);
	}

	public function dropTb($tb){
		return $this->mDb->dropTb($tb);
	}

	public function tables($type='all'){
		return $this->mDb->tables($type);
	}

	public function tbCreate($tb){
		return $this->mDb->tbCreate($tb);
	}

	public function fields($tb){
		return $this->mDb->fields($tb);
	}

	public function getFdInfo($tb,$fd=''){
		return $this->mDb->getFdInfo($tb, $fd);
	}

	public function hasField($tb,$fd){
		return $this->mDb->hasField($tb, $fd);
	}

	public function hasFdVl($tb,$fd,$vl,$cnd=''){
		return $this->mDb->hasFdVl($tb, $fd, $vl, $cnd);
	}

	public function getID($tb,$type='min',$id='',$where='',$order=''){
		return $this->mDb->getID($tb, $type, $id, $where, $order);
	}

	function getIDKy($tb){
		return $this->mDb->getIDKy($tb);
	}

	public function count($tb,$where='',$fd=null){
		return $this->mDb->count($tb, $where , $fd);
	}
	
	public function sum($tb,$fd,$where=''){
		return $this->mDb->sum($tb , $fd, $where);
	}
	
	public function max($tb,$fd,$where=''){
		return $this->mDb->max($tb , $fd, $where);
	}
	
	public function min($tb,$fd,$where=''){
		return $this->mDb->min($tb , $fd, $where);
	}
	
	public function avg($tb,$fd,$where=''){
		return $this->mDb->avg($tb , $fd, $where);
	}
	
	public function getField($tb,$fd,$where='',$order='',$limit='',$group='',$para=null){
		return $this->mDb->getField($tb, $fd, $where, $order, $limit, $group, $para);
	}
	
	public function compress(){
		return $this->mDb->compress();
	}

	public function close(){
		return $this->mDb->close();
	}

	public function trans($type){
		return $this->mDb->trans($type);
	}
}



interface DbInterface{

	public function connect($dbConfig);

	public function query($sql,$ifMod=1,$dxFd='');

	public function tables($type='all');

	public function tbCreate($tb);

	public function getFdInfo($tb,$fd='');

	public function compress();

	public function close();

	public function trans($type);
}

?>