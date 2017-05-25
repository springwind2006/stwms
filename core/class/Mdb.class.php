<?php
/*
 * 说明：此类用于辅助各种类型的数据库的创建、修改操作
 */
class Mdb{
	private $db=null;
	public function __construct($db){
		$this->db=$db;
	}
	
	public function create($tb,$fdArr){
		$type=$this->dbType();
		$createSQL=array();
		$formtypes=getcache('formtype', 'formtype', 'array');
		$fieldCfgs=load::cfg('fieldtypes');
		
		$fcs=array();
		$createSQL=array();
		$sql='create table `' . $tb . '`';
		
		switch($type){
			case 'sqlite': // sqlite数据库构建方式
				$fcs[]='`id` integer primary key';
				foreach($fdArr as $vl){
					$cFormType=$formtypes[$vl['formtype']];
					$csetting=string2array($cFormType['setting']);
					$dsetting=string2array($vl['dsetting']);
					$cFieldType=$fieldCfgs[$cFormType['field_type']][$type];
					$fcs[]='`' . $vl['field'] . '` ' . $cFieldType . (!empty($csetting['field_maxlen']) ? '(' . min(max(intval($csetting['field_ismlen'] && $dsetting['maxlen'] ? $dsetting['maxlen'] : $csetting['field_maxlen']), 1), 4000) . ')' : '') . (!empty($csetting['field_unsigned']) ? ' unsigned ' : '') . ($dsetting['isnull'] || !empty($csetting['field_default']) ? ' not null ' : '') . (!empty($csetting['field_default']) ? ' default \'' . $csetting['field_default'] . '\' ' : '');
					if($dsetting['isunique']){
						$createSQL[]='create unique index `' . $tb . '_unique_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` );';
					}else if($dsetting['isindex']){
						$createSQL[]='create index `' . $tb . '_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` );';
					}
				}
				$sql.=' (' . implode(',', $fcs) . ')';
				array_unshift($createSQL, $sql);
				break;
			
			case 'mysql': // mysql数据库构建方式
				$fcs[]='`id` int unsigned not null auto_increment';
				foreach($fdArr as $vl){
					$cFormType=$formtypes[$vl['formtype']];
					$csetting=string2array($cFormType['setting']);
					$dsetting=string2array($vl['dsetting']);
					$cFieldType=$fieldCfgs[$cFormType['field_type']][$type];
					$fcs[]='`' . $vl['field'] . '` ' . $cFieldType . (!empty($csetting['field_maxlen']) ? '(' . min(max(intval($csetting['field_ismlen'] && $dsetting['maxlen'] ? $dsetting['maxlen'] : $csetting['field_maxlen']), 1), 21844) . ')' : '') . (!empty($csetting['field_unsigned']) ? ' unsigned ' : '') . ($dsetting['isnull'] || !empty($csetting['field_default']) ? ' not null ' : '') . (!empty($csetting['field_default']) ? ' default \'' . str_replace('\'', '', $csetting['field_default']) . '\' ' : '');
					if($dsetting['isunique']){
						$createSQL[]='create unique index `' . $tb . '_unique_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` )';
					}else if($dsetting['isindex']){
						$createSQL[]='create index `' . $tb . '_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` )';
					}
				}
				$fcs[]='primary key (`id`)';
				$sql.=' (' . implode(',', $fcs) . ')ENGINE=MyISAM  default charset=utf8 auto_increment=1';
				array_unshift($createSQL, $sql);
				break;
			
			case 'mssql': // mssql数据库构建方式
				$fcs[]='`id` int identity (1, 1) not null';
				$createSQL[]='alter table `' . $tb . '`  add constraint `' . $tb . '_id` primary key(`id`)';
				foreach($fdArr as $vl){
					$cFormType=$formtypes[$vl['formtype']];
					$csetting=string2array($cFormType['setting']);
					$dsetting=string2array($vl['dsetting']);
					$cFieldType=$fieldCfgs[$cFormType['field_type']][$type];
					$fcs[]='`' . $vl['field'] . '` ' . $cFieldType . ((!empty($csetting['field_maxlen']) && ($cFieldType == 'nvarchar')) ? '(' . min(max(intval($csetting['field_ismlen'] && $dsetting['maxlen'] ? $dsetting['maxlen'] : $csetting['field_maxlen']), 1), 4000) . ')' : '') . (!empty($csetting['field_unsigned']) ? ' unsigned ' : '') . ($dsetting['isnull'] || !empty($csetting['field_default']) ? ' not null ' : '') . (!empty($csetting['field_default']) ? ' default \'' . $csetting['field_default'] . '\' ' : '');
					if($dsetting['isunique']){
						$createSQL[]='create unique index `' . $tb . '_unique_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` )';
					}else if($dsetting['isindex']){
						$createSQL[]='create index `' . $tb . '_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` )';
					}
				}
				$sql.=' (' . implode(',', $fcs) . ')';
				array_unshift($createSQL, $sql);
				break;
			
			case 'access': // access数据库构建方式
				$fcs[]='`id` counter primary key not null';
				foreach($fdArr as $vl){
					$cFormType=$formtypes[$vl['formtype']];
					$csetting=string2array($cFormType['setting']);
					$dsetting=string2array($vl['dsetting']);
					$cFieldType=$fieldCfgs[$cFormType['field_type']][$type];
					$fcs[]='`' . $vl['field'] . '` ' . $cFieldType . ((!empty($csetting['field_maxlen']) && $cFieldType == 'text') ? '(' . min(max(intval($csetting['field_ismlen'] && $dsetting['maxlen'] ? $dsetting['maxlen'] : $csetting['field_maxlen']), 1), 255) . ')' : '') . (!empty($csetting['field_unsigned']) ? ' unsigned ' : '') . ($dsetting['isnull'] || !empty($csetting['field_default']) ? ' not null ' : '') . (!empty($csetting['field_default']) ? ' default \'' . $csetting['field_default'] . '\' ' : '');
					if($dsetting['isunique']){
						$createSQL[]='create unique index `' . $tb . '_unique_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` );';
					}else if($dsetting['isindex']){
						$createSQL[]='create index `' . $tb . '_' . $vl['field'] . '` on `' . $tb . '` ( `' . $vl['field'] . '` );';
					}
				}
				$sql.=' (' . implode(',', $fcs) . ')';
				array_unshift($createSQL, $sql);
				break;
		}
		return $createSQL;
	}

	public function add($tb,$xmlDb,$id){
		$type=$this->dbType();
		$formtypes=getcache('formtype', 'formtype', 'array');
		$fieldCfgs=load::cfg('fieldtypes');
		$infos=$xmlDb->getOne('model', 'id,field,formtype,dsetting', 'where `id`=' . $id);
		$dsetting=string2array($infos['dsetting']);
		
		if($type == 'sqlite'){
			$fdArr=$xmlDb->select('model', 'id,field,formtype,dsetting,listorder', 'where `id`>=0', 'order by `listorder` desc');
			$oldFields[]='`id`';
			foreach($fdArr as $fd){
				if($fd['id'] != $id){
					$oldFields[]='`' . $fd['field'] . '`';
				}
			}
			$oldField=implode(',', $oldFields);
			unset($oldFields);
			$addSQL=$this->create($tb, $fdArr);
			unset($fdArr);
			array_unshift($addSQL, 'drop table `' . $tb . '`');
			array_unshift($addSQL, 'create temporary table `' . $tb . '_temp` as select * from `' . $tb . '`');
			$addSQL[]='insert into `' . $tb . '`(' . $oldField . ') select ' . $oldField . ' from `' . $tb . '`_temp';
			return $addSQL;
		}else{
			if($dsetting['issubtable']){
				//1.判断副表是否存在，如果不存在则新建副表
				//$tb=$tb.'_data';
			}
			//主表上的字段处理
			$cFormType=$formtypes[$infos['formtype']];
			$csetting=string2array($cFormType['setting']);//字段基础信息设置
			$cFieldType=$fieldCfgs[$cFormType['field_type']][$type];//实际映射的数据库字段类型
			
			$fdInfo='alter table `' . $tb . '` add `' . $infos['field'] . '` ' . $cFieldType;
			if($type == 'mssql'){
				$fdInfo.=((!empty($csetting['field_maxlen']) && ($cFieldType == 'nvarchar')) ? '(' . min(max(intval($csetting['field_ismlen'] && $dsetting['maxlen'] ? $dsetting['maxlen'] : $csetting['field_maxlen']), 1), 4000) . ')' : '');
			}else if($type == 'access'){
				$fdInfo.=((!empty($csetting['field_maxlen']) && $cFieldType == 'text') ? '(' . min(max(intval($csetting['field_ismlen'] && $dsetting['maxlen'] ? $dsetting['maxlen'] : $csetting['field_maxlen']), 1), 255) . ')' : '');
			}else{
				$fdInfo.=(!empty($csetting['field_maxlen']) ? '(' . min(max(intval($csetting['field_ismlen'] && $dsetting['maxlen'] ? $dsetting['maxlen'] : $csetting['field_maxlen']), 1), 21844) . ')' : '');
			}
			$fdInfo.=(!empty($csetting['field_unsigned']) ? ' unsigned ' : '') . ($dsetting['isnull'] || !empty($csetting['field_default']) ? ' not null ' : '') . (!empty($csetting['field_default']) ? ' default \'' . $csetting['field_default'] . '\' ' : '');
			$addSQL[]=$fdInfo;
			
			if($dsetting['isunique']){
				$addSQL[]='create unique index `' . $tb . '_unique_' . $infos['field'] . '` on `' . $tb . '` ( `' . $infos['field'] . '` );';
			}else if($dsetting['isindex']){
				$addSQL[]='create index `' . $tb . '_' . $infos['field'] . '` on `' . $tb . '` ( `' . $infos['field'] . '` );';
			}
			return $addSQL;
		}
	}

	public function del($tb,$xmlDb,$id){
		$type=$this->dbType();
		if($type == 'sqlite'){
			$fdArr=$xmlDb->select('model', 'id,field,formtype,isunique,iscore,listorder', 'where `id`!=' . $id, 'order by `listorder` desc');
			$oldFields=array();
			$oldFields[]='`id`';
			foreach($fdArr as $fd){
				$oldFields[]='`' . $fd['field'] . '`';
			}
			$oldField=implode(',', $oldFields);
			unset($oldFields);
			$addSQL=$this->create($tb, $fdArr);
			unset($fdArr);
			array_unshift($addSQL, 'drop table `' . $tb . '`');
			array_unshift($addSQL, 'create temporary table `' . $tb . '_temp` as select * from `' . $tb . '`');
			$addSQL[]='insert into `' . $tb . '`(' . $oldField . ') select ' . $oldField . ' from `' . $tb . '`_temp';
			return $addSQL;
		}else{
			$addSQL=array();
			$infos=$xmlDb->getOne('model', 'id,field', 'where `id`=' . $id);
			$addSQL[]='alter table `' . $tb . '` drop column `' . $infos['field'] . '`';
			return $addSQL;
		}
	}

	private function dbType(){
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
}
?>