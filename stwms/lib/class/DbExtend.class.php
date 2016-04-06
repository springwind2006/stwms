<?php
class DbExtend{
	private $db;
	private $tb=null;
	private $options=array();
	private $isFetch=false;
	private $data=array();

	public function __construct(DbDriver $db){
		$this->db=$db;
		$this->db->mIsAutoPara=0;
	}
	public function __call($name,$args){
		if(stripos($name, 'getBy')===0){ //根据字段的值查询数据
			$fd=substr($name, 5);
			$vl=$args[0];
			$where=$this->db->mFd($fd).'='.$this->db->mVl($vl);
			$ispop=!in_array($where, $this->options['where']);
			$this->where($where);
			$arrs=$this->select();
			$ispop&&array_pop($this->options['where']);
			return $arrs;
		}else if(stripos($name, 'getFieldBy')===0){ //针对某个字段查询并返回某个字段的值
			$fd=substr($name, 10);
			$vl=$args[0];
			$where=$this->db->mFd($fd).'='.$this->db->mVl($vl);
			$ispop=!in_array($where, $this->options['where']);
			$this->where($where);
			$oldfd=$this->options['field'];
			$this->field($args[1]);
			$arrs=$this->find();
			$ispop&&array_pop($this->options['where']);
			$this->field($oldfd);
			return $arrs ? $arrs[$args[1]] : null;
		}
	}
	
	public function __get($name){
		return $this->data[$name];
	}
	public function __set($name,$value){
		$this->data[$name]=$value;
	}
	public function __isset($name){
		return isset($this->data[$name]);
	}

	///////////////////////连贯操作方法///////////////////////
	public function clear(){
		$this->tb=null;
		$this->isFetch=false;
		$this->options=array();
		$this->options['where']=array();
		$this->data=array();
		return $this;
	}

	public function table($ctable){
		$this->tb=$this->db->mTb($ctable);
		return $this;
	}

	public function field($cfield){
		$this->options['field']=$this->db->mFd($cfield);
		return $this;
	}

	public function where($cwhere){
		if(is_array($cwhere)){
			foreach($cwhere as $k=> $v){
				$cwhere[$k]=$this->db->mFd($k).'='.$this->db->mVl($v);
			}
			$this->options['where'][]='('.implode(' and ', $cwhere).')';
		}else{
			$this->options['where'][]='('.$cwhere.')';
		}
		$this->options['where']=array_unique($this->options['where']);
		return $this;
	}

	public function order($corder){
		$this->options['order']=$corder;
		return $this;
	}

	public function limit($start,$len=-1){
		$this->options['limit']=is_numeric($start)&&$len!=-1 ?  $start.','.$len : $start;
		return $this;
	}

	public function group($cgroup){
		$this->options['group']=$cgroup;
		return $this;
	}

	public function fetchSql($isFetch=true){
		$this->isFetch=$isFetch;
		$this->db->mExecSql=!$isFetch;
		return $this;
	}
	public function data($data){
		$this->data=array_merge($this->data,$data);
		return $this;
	}
	public function create($data=null){
		$fields=!empty($this->tb) ? $this->db->fields($this->tb):null;
		if(is_null($data)){
			$data=$_POST;
		}
		if(is_array($fields)){
			foreach($data as $k=>$v){
				if(!in_array($k, $fields)){
					unset($data[$k]);
				}
			}
		}
		if(is_array($data)&&!empty($data)){
			$this->data($data);
			return true;
		}
		return false;
	}
	
	///////////////////////返回结果方法///////////////////////
	public function find($options=array()){
		if(empty($this->tb)){return false;}
		if(!empty($options)){
			$this->options=array_merge($this->options,$options);
		}
		$field=!empty($this->options['field']) ? $this->options['field'] : '*';
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		$order=!empty($this->options['order']) ? 'order by '.$this->options['order']:'';
		$limit=!empty($this->options['limit']) ? 'limit '.$this->options['limit']:'limit 0,1';
		$group=!empty($this->options['group']) ? 'group by '.$this->options['group']:'';
		if($this->isFetch){
			$this->db->getOne($this->tb,$field,$where,$order,$limit,$group);
			$this->fetchSql(false);
			return $this->db->getLastSql();
		}else{
			return $this->db->getOne($this->tb,$field,$where,$order,$limit,$group);
		}
	}

	public function select($options=array()){
		if(empty($this->tb)){return false;}
		if(!empty($options)){
			$this->options=array_merge($this->options,$options);
		}
		$field=!empty($this->options['field']) ? $this->options['field'] : '*';
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		$order=!empty($this->options['order']) ? 'order by '.$this->options['order']:'';
		$limit=!empty($this->options['limit']) ? 'limit '.$this->options['limit']:'';
		$group=!empty($this->options['group']) ? 'group by '.$this->options['group']:'';
		if($this->isFetch){
			$this->db->select($this->tb,$field,$where,$order,$limit,$group);
			$this->fetchSql(false);
			return $this->db->getLastSql();
		}else{
			return $this->db->select($this->tb,$field,$where,$order,$limit,$group);
		}
	}
	public function delete($options=array()){
		if(empty($this->tb)){return false;}
		if(!empty($options)){
			$this->options=array_merge($this->options,$options);
		}
		
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		$is_exec=(!empty($where) || $options===true);
		if($this->isFetch){
			$is_exec&&$this->db->delete($this->tb,$where);
			$this->fetchSql(false);
			return $this->db->getLastSql();
		}else{
			return $is_exec&&$this->db->delete($this->tb,$where);
		}
	}

	public function save($data=array(), $options=array()){
		if(empty($this->tb)){return false;}
		if(!empty($options)){
			$this->options=array_merge($this->options,$options);
		}
		$this->data($data);
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		if(empty($where)){
			$pky=$this->db->getIDKy($this->tb);
			if(isset($this->data[$pky])){
				$where='where '.$this->db->mFd($pky).'='.$this->db->mVl($this->data[$pky]);
			}
		}
		if($this->isFetch){
			$this->db->update($this->tb,$this->data, $where);
			$this->fetchSql(false);
			return $this->db->getLastSql();
		}else{
			$result=$this->db->update($this->tb,$this->data, $where);
			$this->data=array();
			return $result;
		}
	}

	public function add($data=array(), $options=array(), $replace=false){
		if(is_array($options) && !empty($options)){
			$this->options=array_merge($this->options,$options);
		}
		if(is_bool($options)){
			$replace=$options;
		}
		
		if(empty($this->tb)){return false;}
		$this->data($data);
		if($this->isFetch){
			$this->db->insert($this->tb, $this->data,$replace);
			$this->fetchSql(false);
			return $this->db->getLastSql();
		}else{
			$result=$this->db->insert($this->tb, $this->data,$replace);
			$this->data=array();
			return $result > 0 ? $this->db->lastInsert($this->tb,$this->db->getIDKy($this->tb)) : false;
		}
	}

	public function query($sql,$ifMod=1,$dxFd=''){
		if($this->isFetch){
			$this->db->query($sql,$ifMod,$dxFd);
			$this->fetchSql(false);
			return $this->db->getLastSql();
		}else{
			return $this->db->query($sql,$ifMod,$dxFd);
		}
	}
	
	public function count($fd=''){
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		return $this->db->count($this->tb,$where,$fd);
	}
	
	public function sum($fd){
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		return $this->db->sum($this->tb,$fd,$where);
	}
	
	public function max($fd){
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		return $this->db->max($this->tb,$fd,$where);
	}
	
	public function min($fd){
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		return $this->db->min($this->tb,$fd,$where);
	}
	
	public function avg($fd){
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		return $this->db->avg($this->tb,$fd,$where);
	}
	
	public function getField($fd,$para=null){
		if(empty($this->tb)){return false;}
		$where=!empty($this->options['where']) ? 'where '.implode(' and ', $this->options['where']) : '';
		$order=!empty($this->options['order']) ? 'order by '.$this->options['order']:'';
		$limit=!empty($this->options['limit']) ? 'limit '.$this->options['limit']:'';
		$group=!empty($this->options['group']) ? 'group by '.$this->options['group']:'';
		if($this->isFetch){
			$this->db->getField($this->tb,$fd,$where,$order,$limit,$group,$para);
			$this->fetchSql(false);
			return $this->db->getLastSql();
		}else{
			return $this->db->getField($this->tb,$fd,$where,$order,$limit,$group,$para);
		}
	}
	
	public function setField($fd,$vl=null){
		return is_array($fd) ? $this->save($fd) : (!is_null($vl) ? $this->save(array($fd=>$vl)) : false);
	}
	
	public function setInc($fd,$vl){
		return is_numeric($vl) ? $this->setField($fd,'+='.$vl) : false;
	}
	public function setDec($fd,$vl){
		return is_numeric($vl) ? $this->setField($fd,'-='.$vl) : false;
	}
	public function startTrans(){
		$this->db->trans('start');
	}
	public function commit(){
		$this->db->trans('end');
	}
	public function rollback(){
		$this->db->trans('back');
	}

}