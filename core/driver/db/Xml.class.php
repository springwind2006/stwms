<?php
/*
 * 说明： 1.系统维护一个默认id主键; 2.所有字段必须带`符号; 3.可以从文件或字符串加载XML对象;
 */
class XmlDb extends DOMDocument{
	public $dbpath, $sType='file', $status=false;
	public $mIsAutoPara=1;
	public $mIsLoop=1;
	public $forceSup=1; // 强制查询字段加上`号
	public $mQueryNum=0; // 保存所有查询的数量;
	public $mLastSql=''; // 最后一次执行的查询语句
	public $mExecSql=true; // 是否执行查询
	public $ifSave=1;

	public function __construct($dbConfig=''){
		$this->mType='xml';
		parent::__construct();
		$this->connect($dbConfig);
	}

	public function __destruct(){
	}
	
	// 连接数据库类
	public function connect($dbConfig){
		$this->load(is_array($dbConfig) ? $dbConfig['DSN'] : $dbConfig);
	}
	// 重写基类的数据查询类
	public function query($sql,$ifMod=1,$dxFd=''){
		preg_match("/^[^\w]*(\w+)[^\w\*]*(.+)*/i", strtolower($sql), $ssql);
		if(!$this->status){
			return false;
		}
		switch($ssql[1]){
			case 'select':
				preg_match("/^(.+)[^\w]*from[^\w]*(\w+)[^\w]*(.+)?$/i", $ssql[2], $sqlsplit);
				if(empty($sqlsplit) || count($sqlsplit) < 3)
					return false;
				$fields=preg_replace("/[^\w\*]+/i", ',', trim($sqlsplit[1]));
				$fields=preg_replace("/\*+/i", '*', trim($fields));
				$tb=trim($sqlsplit[2]);
				preg_match("/(?:where\s*`?\w+`?\s*\blike\b\s*'?[^\s]+'?\s*)|(?:where\s*`?\w+`?\s*[<>=]{1,2}\s*'?\w+'?\s*)/i", $sqlsplit[3], $mwhere);
				preg_match("/order[^\w]*by[^\w]*\w+[^\w]*\w+/i", $sqlsplit[3], $morder);
				preg_match("/limit\s*\d+\s*,\s*\d+/i", $sqlsplit[3], $mlimit);
				return $this->select($tb, $fields, trim($mwhere[0]), trim($morder[0]), trim($mlimit[0]), '', $dxFd);
				break;
			case 'insert':
				preg_match("/^(?:into)?\s*(\w+)\s*\((.*?)\)\s*values\s*\((.*?)\)/i", $ssql[2], $minsert);
				$fdArr=preg_split("/[,\s]+/", $minsert[2]);
				$vlArr=preg_split("/[,\s]+/", $minsert[3]);
				foreach($fdArr as $k=>$v){
					$fdArr[$k]=preg_replace("/(?:^\s*`)|(`\s*$)/", '', $fdArr[$k]);
					$vlArr[$k]=preg_replace("/(?:^\s*')|('\s*$)/", '', $vlArr[$k]);
				}
				return $this->insert($minsert[1], array_combine($fdArr, $vlArr));
				break;
			case 'update':
				preg_match("/^([a-zA-Z]\w*)\s*set\s*(.*?)\s*(where.*)/i", $ssql[2], $mupdate);
				$fvArr=explode(',', $mupdate[2]);
				$fdArr=array();
				$vlArr=array();
				foreach($fvArr as $vl){
					$tarr=explode('=', trim($vl));
					$fdArr[]=preg_replace("/(?:^\s*`)|(`\s*$)/", '', $tarr[0]);
					$vlArr[]=preg_replace("/(?:^\s*')|('\s*$)/", '', $tarr[1]);
				}
				if(!$mupdate[3]){
					$mupdate[3]='where id>=0';
				}
				return $this->update($mupdate[1], array_combine($fdArr, $vlArr), trim($mupdate[3]));
				break;
			case 'delete':
				preg_match("/^from\s*(\w*)\s*(where.*?)$/i", $ssql[2], $mdelete);
				if(!$mdelete[2]){
					$mdelete[2]='where id>=0';
				}
				return $this->delete($mdelete[1], $mdelete[2]);
				break;
			case 'create':
				preg_match("/^table\s*(\w*)\((.*?)\)$/i", $ssql[2], $mcreate);
				return $this->create($mcreate[1], $mcreate[2]);
				break;
			case 'show':
				if(preg_match("/^tables\s*$/i", $ssql[2]))
					return $this->tables();
				break;
			case 'vacuum':
				$this->compress();
				break;
		}
	}
	
	// 选择(select) api
	public function select($tb,$data='*',$where='`id`>=0',$order='`id` asc',$limit='',$group='',$dxFd=''){
		if($this->mIsAutoPara){ // 开启自动参数要求参数必须带操作标识符，扫描所有参数
			$paraArr=func_get_args();
			$this->mSql($tb, $paraArr);
			$this->autoPara($where, $order, $limit, $group, $paraArr);
			unset($paraArr);
		}else{
			$this->WOLG($where, 'where');
			$this->mSql($tb, $where);
			$this->WOLG($order, 'order by');
			$this->mSql($tb, $order);
			$this->WOLG($limit, 'limit');
			$this->mSql($tb, $limit);
			$this->WOLG($group, 'group by');
			$this->mSql($tb, $group);
		}
		if(trim($where) == ''){
			$where='`id`>=0';
		}
		$where=str_replace(array('&&','||'), array(' and ',' or '), $where);
		$where=preg_replace("/\s*where\s+/i", '', trim($where));
		$order=preg_replace("/\s*order\s+by\s+/i", '', trim($order));
		$limit=preg_replace("/\s*limit\s+/i", '', trim($limit));
		
		$fdArr=$this->fields($tb);
		if($data != '*'){
			$sFdArr=array_intersect((is_array($data) ? $data : preg_split("/\s*,\s*/", $data)), $fdArr);
			if(empty($sFdArr)){
				return false;
			}
			if(!in_array('id', $sFdArr)){
				array_push($sFdArr, 'id');
			}
		}
		foreach($fdArr as $ky=>$vl){
			$where=str_replace('`' . $vl . '`', 'v' . $ky, $where);
		}
		$oArr=$this->xQuery('//root/' . $tb . '/it[' . $where . ']');
		$rArr=array();
		
		$isOrder=$oArr->length && !empty($order) && preg_match_all("/`?(\w+)`?\s+(asc|desc)\s*/i", strtolower($order), $rorders);
		$isLimit=$oArr->length && !empty($limit);
		$isMKey=false; // 检查是否排序键名是否为数字
		$sortArrs=array();
		$curDx=0;
		if($isOrder){
			$rorders=array_combine($rorders[1], $rorders[2]);
		}
		foreach($oArr as $cNode){
			$orArr=array();
			for($n=0, $nMax=$this->getLength($cNode->childNodes); $n < $nMax; $n++){
				$curVL=$cNode->childNodes->item($n)->nodeValue;
				$curVL=is_numeric($curVL) ? intval($curVL) : htmlspecialchars_decode($curVL);
				if($data == '*'){
					$orArr[$fdArr[$n]]=$curVL;
				}else if(in_array($fdArr[$n], $sFdArr)){
					$orArr[$fdArr[$n]]=$curVL;
				}
			}
			
			$cIndex=!empty($dxFd) && array_key_exists($dxFd, $orArr) ? (($isMKey=$isOrder && is_numeric($orArr[$dxFd])) ? '_' . $orArr[$dxFd] : $orArr[$dxFd]) : $curDx;
			if($isOrder){
				foreach($rorders as $sfd=>$stp){
					if(array_key_exists($sfd, $orArr)){
						$sortArrs[$sfd][$cIndex]=$orArr[$sfd];
					}
				}
			}
			$rArr[$cIndex]=$this->filterData($orArr);
			unset($orArr);
			$curDx++;
		}
		
		// 排序操作
		if($isOrder){
			$sortArrParas=array();
			foreach($sortArrs as $sKy=>$sArr){
				$sortArrParas[]=$sArr;
				$sortArrParas[]=strtoupper($rorders[$sKy]) == 'DESC' ? SORT_DESC : SORT_ASC;
			}
			unset($sortArrs, $rorders);
			
			$sortArrParas[]=&$rArr;
			
			call_user_func_array('array_multisort', $sortArrParas);
			unset($sortArrParas);
		}
		// limit操作
		if($isLimit){
			$itemsnum=count($rArr);
			$limit=strtolower($limit);
			preg_match("/^\s*(\d+)\s*,\s*(\d+)\s*$/i", $limit, $rlimit);
			if(!empty($rlimit)){
				$snum=intval($rlimit[1]);
				$lenum=intval($rlimit[2]);
				$snum=($snum < 0) ? 0 : ($snum <= $itemsnum ? $snum : $itemsnum);
			}
			$isLimit=(isset($snum) && isset($lenum));
			if($isLimit){
				$i=0;
				foreach($rArr as $ky=>$vl){
					if($i < $snum || $i > ($snum + $lenum - 1)){
						unset($rArr[$ky]);
					}else if($isMKey){
						$rArr[substr($ky, 1)]=$vl;
						unset($rArr[$ky]);
					}
					$i++;
				}
			}
		}
		if(!$isLimit && $isMKey){
			foreach($rArr as $ky=>$vl){
				$rArr[substr($ky, 1)]=$vl;
				unset($rArr[$ky]);
			}
		}
		
		return $rArr;
	}

	public function getOne($tb,$data='*',$where='`id`>=0',$order='`id` asc',$limit='limit 0,1',$group=''){
		$datas=$this->select($tb, $data, $where, $order, $limit='', $group='');
		return (is_array($datas) ? $datas[0] : false);
	}
	// 插入数据 api
	public function insert($tb,$datas,$replace=false){
		$tbNode=$this->xQuery('//root/' . $tb)->item(0);
		$fdArr=explode('|', $tbNode->getAttribute('fd'));
		$cNode=$this->createElement('it');
		$insertID=-1;
		foreach($fdArr as $ky=>$fd){
			$cFNode=$this->createElement('v' . $ky);
			if($fd != 'id' || ($replace && isset($datas[$fd]))){
				$cFNode->nodeValue=htmlspecialchars($datas[$fd]);
				if($fd == 'id'){
					$insertID=intval($datas[$fd]);
				}
			}else{
				$autoid=$tbNode->getAttribute('autoid');
				if(!empty($autoid)){
					$insertID=intval($autoid) + 1;
				}else{
					$count=$this->getLength($tbNode->childNodes);
					$insertID=$count ? intval($tbNode->childNodes->item($count - 1)->getElementsBytagName('v' . $ky)->item(0)->nodeValue) + 1 : 1;
				}
				$tbNode->setAttribute('autoid', $insertID);
				$cFNode->nodeValue=$insertID;
			}
			$cNode->appendChild($cFNode);
			unset($cFNode);
		}
		$this->xQuery('//root/' . $tb)->item(0)->appendChild($cNode);
		$this->save();
		return $insertID;
	}
	// 更新
	public function update($tb,$datas,$where='`id`>=0'){
		$fdArr=$this->fields($tb);
		$upKys=array_keys($datas);
		$this->WOLG($where, 'where');
		if(trim($where) == ''){
			$where='`id`>=0';
		}
		$where=str_replace(array('&&','||'), array(' and ',' or '), $where);
		$where=preg_replace("/\s*where\s+/i", '', trim($where));
		$this->mSql($tb, $where);
		foreach($fdArr as $ky=>$vl){
			$where=str_replace('`' . $vl . '`', 'v' . $ky, $where);
		}
		$oArr=$this->xQuery('//root/' . $tb . '/it[' . $where . ']');
		$updateIDs=0;
		foreach($oArr as $cNode){
			for($n=0, $nMax=$this->getLength($cNode->childNodes); $n < $nMax; $n++){
				if(in_array($fdArr[$n], $upKys) && $fdArr[$n] != 'id'){
					$cNode->childNodes->item($n)->nodeValue=htmlspecialchars($datas[$fdArr[$n]]);
				}
			}
			$updateIDs++;
		}
		$this->save();
		return $updateIDs;
	}
	// 删除
	public function delete($tb,$where='`id`>=0'){
		$fdArr=$this->fields($tb);
		$this->WOLG($where, 'where');
		if(trim($where) == ''){
			$where='`id`>=0';
		}
		$where=str_replace(array('&&','||'), array(' and ',' or '), $where);
		$where=preg_replace("/\s*where\s+/i", '', trim($where));
		$this->mSql($tb, $where);
		foreach($fdArr as $ky=>$vl){
			$where=str_replace('`' . $vl . '`', 'v' . $ky, $where);
		}
		
		// 使之支持in语句解析
		if(strpos($where, '(') !== false && strpos($where, ')') !== false){
			preg_match("/\s*(\w+)/i", $where, $whereFD);
			preg_match("/\((.+?)\)/i", $where, $whereArr);
			$wArr=preg_split("/[,\s]+/", $whereArr[1]);
			foreach($wArr as $ck=>$cv){
				$wArr[$ck]=$whereFD[1] . '=' . $cv;
			}
			$where=implode(' or ', $wArr);
		}
		$oArr=$this->xQuery('//root/' . $tb . '/it[' . $where . ']');
		$deleteIDs=0;
		foreach($oArr as $cNode){
			$cNode->parentNode->removeChild($cNode);
			$deleteIDs++;
		}
		$this->save();
		return $deleteIDs;
	}
	// 删除表
	public function dropTb($tb){
		$tbInfo=$this->xQuery('//root/' . $tb);
		if($tbInfo && $tbInfo->item(0)){
			$tbInfo->item(0)->parentNode->removeChild($tbInfo->item(0));
		}
		$this->save();
	}
	// 获取所有表名
	public function tables($type='user'){
		$tbNArr=$this->xQuery('//root')->item(0)->childNodes;
		$tbArr=array();
		foreach($tbNArr as $node){
			array_push($tbArr, $node->tagName);
		}
		return $tbArr;
	}
	// 获取指定表结构
	public function tbCreate($tb){
		if(!in_array($tb, $this->tables())){
			return false;
		}
		$fdArr=$this->fields($tb);
		return 'create table ' . $tb . '(' . implode(',', $fdArr) . ');' . "\r\n";
	}
	// 获取表的所有字段名称
	public function fields($tb){
		$tbInfo=$this->xQuery('//root/' . $tb)->item(0);
		return explode('|', $tbInfo->getAttribute('fd'));
	}

	public function getFdInfo($tb,$fd=''){
	}

	public function hasField($tb,$fd){
		return in_array($fd, $this->fields($tb), true);
	}

	public function hasFdVl($tb,$fd,$vl,$cnd=''){
		if($cnd != ''){
			$cnd=preg_replace("/^\s*and/i", '', trim($cnd));
			$cnd=' and ' . $cnd;
		}
		return !!$this->getOne($tb, $fd, 'where `' . $fd . '`=' . $this->trimStr($vl) . ' ' . $cnd);
	}
	// 根据指定条件和排序获取自动增长的键名的最大值，最小值，上一个值和下一个值
	public function getID($tb,$type='min',$id='',$where='',$order=''){
		if($type != 'min' && $type != 'max' && $id == ''){
			return false;
		}
		$this->WOLG($where, 'where');
		$this->WOLG($order, 'order by');
		$ID=$this->getIDKy($tb);
		if($ID === false){
			return false;
		}
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

	function getIDKy($tb,$autoKy=false){
		return 'id';
	}

	public function count($tb,$where='where `id`>=0'){
		$fdArr=$this->fields($tb);
		$this->WOLG($where, 'where');
		$where=str_replace(array('&&','||'), array(' and ',' or '), $where);
		$where=preg_replace("/\s*where\s+/i", '', trim($where));
		$this->mSql($tb, $where);
		
		foreach($fdArr as $ky=>$vl){
			$where=str_replace('`' . $vl . '`', 'v' . $ky, $where);
		}
		return $this->getLength($this->xQuery('//root/' . $tb . '/it[' . $where . ']'));
	}

	public function compress(){
	}

	public function close(){
	}
	// 事务处理函数
	public function trans($type){
		switch($type){
			case 'start':
				$this->ifSave=0;
				break;
			case 'end':
				$this->ifSave=1;
				$this->save();
				break;
		}
	}
	
	// /////////////////////////////XML数据库特有方法///////////////////////////////////////
	// 数据加载类
	public function load($dpath,$options=NULL){
		$fromFile=true;
		if(preg_match("/<.+?>/", $dpath)){
			$this->sType='string';
			$this->status=$this->loadXML($this->simple($dpath));
			$fromFile=false;
		}else if(is_file($dpath)){
			$this->sType='file';
			$this->status=$this->loadXML($this->simple(file_get_contents($dpath)));
			$fromFile=true;
		}else{
			$this->sType='string';
			$this->status=$this->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root/>");
			$fromFile=false;
		}
		$this->dbpath=$fromFile ? $dpath : ':memory:';
		return $this->status;
	}
	// 创建数据库
	public function create($tb,$fields){
		if(in_array($tb, $this->tables())){
			return false;
		}
		if(is_string($fields)){
			$fields=preg_split("/[^\w]+/", $fields);
		}
		if(!is_array($fields) || empty($fields)){
			return false;
		}
		$fields=array_diff($fields, array('id'));
		array_unshift($fields, 'id'); // 确保id值排在第一个主键位置
		$tbNode=$this->createElement($tb);
		$tbNode->setAttribute('fd', implode('|', $fields));
		$this->xQuery('//root')->item(0)->appendChild($tbNode);
		$this->save();
		return true;
	}
	// 获取长度
	function getLength($vtype){
		if(is_array($vtype)){
			return count($vtype);
		}else if(is_string($vtype)){
			return strlen($vtype);
		}else{
			return $vtype->length;
		}
	}
	// 简化XML数据,去除注释或换行字符
	function simple($content){
		$content=str_replace(array("\r\n","\r","\n"), '', $content); // 去除换行符
		return preg_replace(array("/<!--.*?-->/","/>\s+/"), array('','>'), $content); // 去除注释语句和标签间空白
	}
	// 保存数据
	public function save($toPath=''){
		if(!empty($toPath)){
			file_put_contents($toPath, "<!--<?php die(0);?>-->\r\n" . $this->saveXML());
		}else{
			if($this->sType == 'file'){
				if($this->ifSave){
					file_put_contents($this->dbpath, "<!--<?php die(0);?>-->\r\n" . $this->saveXML());
				}
			}else{
				return $this->saveXML();
			}
		}
	}
	// 内部XPath执行查询
	public function xQuery($str,$node=''){
		$xpath=new DOMXPath($this);
		return $node == '' ? $xpath->evaluate($str) : $xpath->evaluate($str, $node);
	}
	// 此方法为字段名强制加上`,使之符合操作规定
	function mSql($tb,&$str){
		if(!$this->forceSup || (is_string($str) && strpos($str, '`') !== false)){
			return $str;
		}
		if(is_array($str)){
			foreach($str as $ck=>$cv){
				$str[$ck]=$this->mSql($tb, $cv);
			}
			return $str;
		}
		if($str == ''){
			return $str;
		}
		return ($str=preg_replace("/([^'`\w])(" . implode('|', $this->fields($tb)) . ")([^'`\w])/i", "\${1}`\${2}`\${3}", $str));
	}
	// 消除影响查询的字符
	function trimStr($vl){
		if(is_array($vl)){
			foreach($vl as $ck=>$cv){
				$vl[$ck]=$this->trimStr($vl);
			}
			return $vl;
		}
		$vl=str_replace('\\', '', $vl);
		return ($vl=is_numeric($vl) ? $vl : ('\'' . str_replace('\'', '\'\'', $vl) . '\''));
	}
	// 获取或设置数据库框架属性
	function schema($tb,$ky='',$vl='',$type='table'){
		if(empty($type) || empty($tb) || ($type == 'table' && !in_array($tb, $this->tables()))){
			return false;
		}
		if($type == 'table'){
			$cNode=$this->xQuery('//root/' . $tb)->item(0);
		}else{
			$cNode=$this->xQuery('//' . $tb)->item(0);
		}
		if(empty($ky)){ // 获取所有属性
			$arriarr=$cNode->attributes;
			$allarr=array();
			foreach($arriarr as $kys=>$vls){
				if($type == 'table' && $kys == 'fd'){
					continue;
				}
				$allarr[$kys]=htmlspecialchars_decode($vls->nodeValue);
			}
			return $allarr;
		}else if(empty($vl)){ // 获取指定键名的属性
			if($vl !== NULL){
				if(is_array($ky)){ // 如果$ky为数组，这根据键值对设置
					foreach($ky as $kys=>$vls){
						$cNode->setAttribute($kys, htmlspecialchars($vls));
					}
					return $this->save();
				}else{ // $ky为字符串则返回对应的键值
					$vlArr=$this->schema($tb);
					return $vlArr[$ky];
				}
			}else if($type != 'table' || $ky != 'fd'){
				$cNode->removeAttribute($ky);
				return $this->save();
			}
		}else{ // 设置指定键名的属性
			if($type == 'table' && $ky == 'fd'){
				return false;
			}
			$cNode->setAttribute($ky, htmlspecialchars($vl));
			return $this->save();
		}
	}
	// 设置根节点属性，用于全局
	function root($ky='',$vl=''){
		return $this->schema('root', $ky, $vl, 'root');
	}
	
	// /////////////////////////////补充其它类型数据库类型从Db类继承的方法///////////////////////////////////////
	// 自动选择操作参数
	public function autoPara(&$where,&$order,&$limit,&$group,$oriParas){
		$hasMatch=array('where' => 0,'order' => 0,'limit' => 0,'group' => 0);
		foreach($oriParas as $sid=>$para){
			if($sid > 1){
				if(preg_match("/^\s*where/i", $para)){
					$where=' ' . $para;
					$hasMatch['where']=1;
				}
				if(preg_match("/^\s*order\s+by/i", $para)){
					$order=' ' . $para;
					$hasMatch['order']=1;
				}
				if(preg_match("/^\s*limit/i", $para)){
					$limit=' ' . $para;
					$hasMatch['limit']=1;
				}
				if(preg_match("/^\s*group\s+by/i", $para)){
					$group=' ' . $para;
					$hasMatch['group']=1;
				}
			}
		}
		if(!$hasMatch['where']){
			$where='';
		}
		if(!$hasMatch['order']){
			$order='';
		}
		if(!$hasMatch['limit']){
			$limit='';
		}
		if(!$hasMatch['group']){
			$group='';
		}
	}
	// 获取最后一条添加的字段值
	public function lastInsert($tb,$fd=''){
		$id=$this->getIDKy($tb);
		$id=empty($id) && !empty($fd) ? $fd : $id;
		$fd=empty($fd) ? $id : $fd;
		$info=$this->getOne($tb, ($id == $fd ? $id : $id . ',' . $fd), 'where `' . $id . '`>=0', 'order by `' . $id . '` desc');
		return (is_array($info) && isset($info[$fd]) ? $info[$fd] : -1);
	}
	// 以下方式用于设置过滤函数，会作用域toArray()函数，慎用！！
	public function setFilter($fn){
		$this->filterFunc=$fn;
	}

	public function unsetFilter(){
		$this->filterFunc=NULL;
	}

	public function filterData($data){
		if(is_null($this->filterFunc)){
			return $data;
		}
		if($data && ((is_string($this->filterFunc) && function_exists($this->filterFunc)) || is_array($this->filterFunc))){
			return call_user_func($this->filterFunc, $data);
		}
		return $data;
	}
	// 自动附加操作语句
	function WOLG(&$str,$kw){
		$str=preg_replace("/\s+/", ' ', $str);
		$kw=preg_replace("/\s+/", ' ', $kw);
		return $str=($str == '' ? '' : (strpos(strtolower($str), strtolower($kw)) !== false ? ' ' . $str : ' ' . $kw . ' ' . $str));
	}
}
?>