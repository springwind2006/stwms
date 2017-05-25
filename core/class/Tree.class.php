<?php
/**
 * 通用的树型类，可以生成任何树型结构
 */
class Tree{
	public $icon=array('│','├─','└─'); // 生成树型结构所需修饰符号，可以换成图片
	public $nbsp="&nbsp;";
	private $ret;
	private $arr;
 // 生成树型结构所需要的2维数组
	
	/**
	 * 功能：构造函数，初始化类 参数：init(array 2维数组)，例如： array( 1 => array('id'=>'1','pid'=>0,'name'=>'一级栏目一'), 2 => array('id'=>'2','pid'=>0,'name'=>'一级栏目二'), 3 => array('id'=>'3','pid'=>1,'name'=>'二级栏目一'), 4 => array('id'=>'4','pid'=>1,'name'=>'二级栏目二'), 5 => array('id'=>'5','pid'=>2,'name'=>'二级栏目三'), 6 => array('id'=>'6','pid'=>3,'name'=>'三级栏目一'), 7 => array('id'=>'7','pid'=>3,'name'=>'三级栏目二') )
	 */
	public function init(&$arr){
		$this->arr=&$arr;
		$this->ret='';
		return is_array($arr);
	}

	/**
	 * 功能：得到父级数组 参数：get_parent(int 当前级别id) 返回：array
	 */
	public function get_parent($myid){
		$newarr=array();
		if(!isset($this->arr[$myid])){
			return false;
		}
		$pid=$this->arr[$this->arr[$myid]['pid']]['pid'];
		if(is_array($this->arr)){
			foreach($this->arr as $id=>$a){
				if($a['pid'] == $pid)
					$newarr[$id]=$a;
			}
		}
		return $newarr;
	}

	/**
	 * 功能：得到子级数组 参数：get_child(int 当前级别id) 返回：array
	 */
	public function get_child($myid){
		$a=$newarr=array();
		if(is_array($this->arr)){
			foreach($this->arr as $id=>$a){
				if($a['pid'] == $myid)
					$newarr[$id]=$a;
			}
		}
		return $newarr ? $newarr : false;
	}

	/**
	 * 功能：得到当前位置数组 参数：get_pos(int 当前级别id,引用的数组) 返回：array
	 */
	public function get_pos($myid,&$newarr){
		$a=array();
		if(!isset($this->arr[$myid]))
			return false;
		$newarr[]=$this->arr[$myid];
		$pid=$this->arr[$myid]['pid'];
		if(isset($this->arr[$pid])){
			$this->get_pos($pid, $newarr);
		}
		if(is_array($newarr)){
			krsort($newarr);
			foreach($newarr as $v){
				$a[$v['id']]=$v;
			}
		}
		return $a;
	}

	/**
	 * 功能：得到树型结构 参数：get_tree( int ID 表示获得这个ID下的所有子级, string 生成树型结构的基本代码，例如："<option value=\$id \$selected>\$spacer\$name</option>", int 被选中的ID，比如在做树型下拉框的时候需要用到 ) 返回：数组
	 */
	public function get_tree($myid,$str,$sid=0,$adds='',$str_group=''){
		$number=1;
		$childs=$this->get_child($myid);
		if(is_array($childs)){
			$total=count($childs);
			foreach($childs as $id=>$value){
				$j=$k='';
				if($number == $total){
					$j.=$this->icon[2];
				}else{
					$j.=$this->icon[1];
					$k=$adds ? $this->icon[0] : '';
				}
				$spacer=$adds ? $adds . $j : '';
				$selected=$id == $sid ? 'selected' : '';
				@extract($value);
				$rowid=get_parents($id, $this->arr, 'id', '_', true);
				$pid == 0 && $str_group ? eval("\$nstr=\"$str_group\";") : eval("\$nstr=\"$str\";");
				$this->ret.=$nstr;
				$nbsp=$this->nbsp;
				$this->get_tree($id, $str, $sid, $adds . $k . $nbsp, $str_group);
				$number++;
			}
		}
		return $this->ret;
	}

	public function get_array($myid,$adds='',&$reArr=NULL){
		$number=1;
		if(empty($reArr)){
			$reArr=array();
		}
		$childs=$this->get_child($myid);
		if(is_array($childs)){
			$total=count($childs);
			foreach($childs as $id=>$value){
				$j=$k='';
				$id=$value['id'];
				if($number == $total){
					$j.=$this->icon[2];
				}else{
					$j.=$this->icon[1];
					$k=$adds ? $this->icon[0] : '';
				}
				$value['spacer']=$adds ? $adds . $j : '';
				$value['rowid']=get_parents($id, $this->arr, 'id', '_', true);
				$value['level']=substr_count($value['rowid'], '_') + 1;
				$reArr[$id]=$value;
				$this->get_array($id, $adds . $k . $this->nbsp, $reArr);
				$number++;
			}
		}
		return $reArr;
	}

	/**
	 * 功能：同上一方法类似,但允许多选
	 */
	public function get_tree_multi($myid,$str,$sid=0,$adds=''){
		$number=1;
		$childs=$this->get_child($myid);
		if(is_array($childs)){
			$total=count($childs);
			foreach($childs as $id=>$a){
				$j=$k='';
				if($number == $total){
					$j.=$this->icon[2];
				}else{
					$j.=$this->icon[1];
					$k=$adds ? $this->icon[0] : '';
				}
				$spacer=$adds ? $adds . $j : '';
				
				$selected=$this->have($sid, $id) ? 'selected' : '';
				@extract($a);
				eval("\$nstr=\"$str\";");
				$this->ret.=$nstr;
				$this->get_tree_multi($id, $str, $sid, $adds . $k . '&nbsp;');
				$number++;
			}
		}
		return $this->ret;
	}

	/**
	 * 功能：获取树型目录 参数：(integer $myid 要查询的ID, string $str 第一种HTML代码方式, string $str2 第二种HTML代码方式, integer $sid 默认选中, integer $adds 前缀 )
	 */
	public function get_tree_category($myid,$str,$str2,$sid=0,$adds=''){
		$number=1;
		$childs=$this->get_child($myid);
		if(is_array($childs)){
			$total=count($childs);
			foreach($childs as $id=>$a){
				$j=$k='';
				if($number == $total){
					$j.=$this->icon[2];
				}else{
					$j.=$this->icon[1];
					$k=$adds ? $this->icon[0] : '';
				}
				$spacer=$adds ? $adds . $j : '';
				
				$selected=$this->have($sid, $id) ? 'selected' : '';
				@extract($a);
				if(empty($html_disabled)){
					eval("\$nstr=\"$str\";");
				}else{
					eval("\$nstr=\"$str2\";");
				}
				$this->ret.=$nstr;
				$this->get_tree_category($id, $str, $str2, $sid, $adds . $k . '&nbsp;');
				$number++;
			}
		}
		return $this->ret;
	}

	/**
	 * 功能:同上一类方法，jquery treeview 风格，可伸缩样式（需要treeview插件支持） 参数：get_treeview( $myid 表示获得这个ID下的所有子级, $effected_id 需要生成treeview目录数的id, $str 末级样式, $str2 目录级别样式, $showlevel 直接显示层级数，其余为异步显示，0为全部显示, $style 目录样式 默认 filetree 可增加其他样式如'filetree treeview-famfamfam', $currentlevel 计算当前层级，递归使用 适用改函数时不需要用该参数, $recursion 递归使用 外部调用时为FALSE )
	 */
	function get_treeview($myid,$effected_id='example',$str="<span class='file'>\$name</span>",$str2="<span class='folder'>\$name</span>",$showlevel=0,$style='filetree ',$currentlevel=0,$recursion=FALSE){
		$childs=$this->get_child($myid);
		if(!defined('EFFECTED_INIT')){
			$effected=' id="' . $effected_id . '"';
			define('EFFECTED_INIT', 1);
		}else{
			$effected='';
		}
		
		$placeholder='<ul><li><span class="placeholder"></span></li></ul>';
		if(!$recursion)
			$this->str.='<ul' . $effected . '  class="' . $style . '">';
		$childsLen=count($childs);
		
		foreach($childs as $id=>$a){
			$childsLen--;
			@extract($a);
			$hasChild=$this->get_child($id);
			
			$hitarea='';
			$floder_status='';
			if(!$childsLen){
				if($hasChild && $showlevel != $currentlevel){
					$floder_status=' class="collapsable"';
					$hitarea='<div class="hitarea"></div>';
				}else if($hasChild && $showlevel == $currentlevel){
					$floder_status=' class="expandable"';
					$hitarea='<div class="hitarea expandable-hitarea"></div>';
				}else{
					$floder_status=' class="last"';
				}
			}else{
				if($hasChild && $showlevel != $currentlevel){
					$floder_status=' class="collapsable"';
					$hitarea='<div class="hitarea"></div>';
				}else if($hasChild && $showlevel == $currentlevel){
					$floder_status=' class="expandable"';
					$hitarea='<div class="hitarea expandable-hitarea"></div>';
				}
			}
			
			$this->str.=$recursion ? '<ul><li' . $floder_status . ' id=\'' . $id . '\'>' . $hitarea : '<li' . $floder_status . ' id=\'' . $id . '\'>' . $hitarea;
			$recursion=FALSE;
			if($hasChild){
				eval("\$nstr=\"$str2\";");
				$this->str.=$nstr;
				if($showlevel == 0 || ($showlevel > 0 && $showlevel > $currentlevel)){
					$this->get_treeview($id, $effected_id, $str, $str2, $showlevel, $style, $currentlevel + 1, TRUE);
				}elseif($showlevel > 0 && $showlevel == $currentlevel){
					$this->str.=$placeholder;
				}
			}else{
				eval("\$nstr=\"$str\";");
				$this->str.=$nstr;
			}
			$this->str.=$recursion ? '</li></ul>' : '</li>';
		}
		
		if(!$recursion)
			$this->str.='</ul>';
		return $this->str;
	}

	/**
	 * 功能：获取子栏目json 参数：creat_sub_json($myid 当前id, $str 字符串)
	 */
	public function creat_sub_json($myid,$str=''){
		$sub_cats=$this->get_child($myid);
		$n=0;
		if(is_array($sub_cats))
			foreach($sub_cats as $c){
				$data[$n]['id']=$c['catid'];
				if($this->get_child($c['catid'])){
					$data[$n]['liclass']='hasChildren';
					$data[$n]['children']=array(array('text' => '&nbsp;','classes' => 'placeholder'));
					$data[$n]['classes']='folder';
					$data[$n]['text']=$c['name'];
				}else{
					if($str){
						@extract($c);
						eval("\$data[$n]['text']=\"$str\";");
					}else{
						$data[$n]['text']=$c['name'];
					}
				}
				$n++;
			}
		return json_encode($data);
	}

	private function have($list,$item){
		return (strpos(',,' . $list . ',', ',' . $item . ','));
	}
}
?>