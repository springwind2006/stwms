<?php
defined('IN_MYCMS') or exit('No permission resources.');
class ContentAdmin extends AdminController{
	public $models, $modelFields, $categorys, $content_output;
	private $format, $thumb, $images;

	public function __construct(){
		parent::__construct();
		$this->categorys=& getLcache('category', 'core', 'array');
		$this->modelFields=getcache('model', 'model', 'array', 'fields');
		$this->_resetFormat();
	}

	public function init(){
		$this->showmessage('请选择左边栏目，然后操作！', 'alert');
	}

	public function category(){
		$array=$this->categorys;
		$this->models=getcache('model', 'model', 'array');
		$tree=load::cls('Tree');
		
		foreach($array as $k=>$v){
			if($array[$k]['type'] == 2){
				unset($array[$k]); // 消除外链栏目
			}else{
				$model=$array[$k]['model'] ? $array[$k]['model'] : 'page';
				$win_width=intval($this->models[$model]['width']);
				$win_height=intval($this->models[$model]['height']);
				if($array[$k]['type'] == 0){
					$array[$k]['icon_type']='';
					$array[$k]['manage_action']='href="?' . ADMIN_INI . '&c=content&a=manage&catid=' . $array[$k]['id'] . '" target="doMainFrame"';
					$array[$k]['add_icon']='<a href="' . ($array[$k]['setting']['opentype'] ? ('?' . ADMIN_INI . '&c=content&a=manage&catid=' . $array[$k]['id']) : 'javascript:') . '" target="doMainFrame">' . '<img onclick="add_content(' . $array[$k]['id'] . ',' . $array[$k]['setting']['opentype'] . ',\'' . str_replace(array(
							'\'',
							'"'), '', $array[$k]['name']) . '\',' . $win_width . ',' . $win_height . ')" src="' . STATIC_URL . 'common/images/add_content.gif"/>' . '</a>';
				}else{
					$array[$k]['icon_type']='file';
					$array[$k]['manage_action']='href="javascript:" onclick="manage_content(' . $array[$k]['id'] . ',' . $array[$k]['setting']['opentype'] . ',\'' . str_replace(array('\'','"'), '', $array[$k]['name']) . '\',' . $win_width . ',' . $win_height . ')"';
					$array[$k]['add_icon']='';
				}
				$array[$k];
			}
		}
		
		$tree->init($array);
		$strs='<span class=\"$icon_type\">$add_icon<a $manage_action>$name</a></span>';
		$strs2='<span class=\"folder\">$name</span>';
		$select_categorys=$tree->get_treeview(0, 'category_tree', $strs, $strs2, 0, 'treeview filetree', 1);
		include template('category', 'content');
	}

	public function manage(){
		$catid=intval($_GET['catid']);
		if(isset($_GET['type']) && $_GET['type'] == 'check_title'){
			$model=$this->categorys[$catid]['model'] ? $this->categorys[$catid]['model'] : 'page';
			$title=$_POST['title'];
			$field=safe_replace($_GET['field']);
			$id=intval($_GET['id']);
			$where='`catid`=' . $catid . (!empty($id) ? ' and `id`!=' . $id : '');
			alert($this->getDb()->hasFdVl($model, $field, $title, $where) ? '1' : '0');
		}
		
		load::cls('Form', 0);
		$categorys=&$this->categorys;
		if(!$categorys[$catid]['type'] && !empty($categorys[$catid]['model'])){
			/**
			 * **[一般栏目]***
			 */
			$istrash=isset($_GET['trash']) && $_GET['trash'] ? 1 : 0; // 是否是回收站内容
			
			$audit_levels=intval($categorys[$catid]['setting']['audit']); // 审核审核步数
			$fields=getcache('field', 'model', 'array', $categorys[$catid]['model']);
			$tolists=array(); // 显示列表字段
			$searchs=array(); // 搜索字段
			$orders=array(); // 排序字段
			$datetimes=array(); // 日期的字段
			$sfields=array('id','catid','isimages','status'); // 所有显示的字段
			$isListorder=false;
			$fieldArrs=get_formtype_fields($fields, 'posid');
			$position_field=$fieldArrs['posid'][0];
			unset($fieldArrs);
			$order_field=isset($_GET['order_field']) && array_key_exists($_GET['order_field'], $fields) ? $_GET['order_field'] : '';
			$order_type=isset($_GET['order_type']) && in_array($_GET['order_type'], array(0,1,2)) ? $_GET['order_type'] : 0;
			$is_push=0;
			foreach($categorys as $cID=>$cVL){
				if($catid != $cID && $cVL['model'] == $categorys[$catid]['model'] && load::controller('role')->_check_auth('content', 'add', $cID)){
					$is_push=1;
					break;
				}
			}
			$this->models=getcache('model', 'model', 'array');
			$win_width=intval($this->models[$categorys[$catid]['model']]['width']);
			$win_height=intval($this->models[$categorys[$catid]['model']]['height']);
			
			foreach($fields as $k=>$vl){
				if(!empty($fields[$k]['msetting']['istolist']) && $fields[$k]['field'] != 'listorder'){
					$tolists[]=$k;
					$sfields[]=$k;
				}
				if($fields[$k]['field'] == 'listorder'){
					$isListorder=true;
				}
				if(!empty($fields[$k]['msetting']['issearch'])){
					if($fields[$k]['formtype'] == 'datetime'){
						$datetimes[]=$k;
					}else{
						$searchs[]=$k;
					}
				}
				if(!empty($fields[$k]['msetting']['isorder'])){
					$orders[]=$k;
					$sfields[]=$k;
				}
			}
			
			if($isListorder){
				$sfields[]='listorder';
			}
			
			if(!empty($position_field)){
				$sfields[]=$position_field;
			}
			
			$sfields=array_unique($sfields);
			
			$_GET=safe_replace($_GET);
			
			// 处理排序字段
			$listorder_str=$order_field && $order_type ? '`' . $order_field . '` ' . ($order_type == 1 ? 'desc' : 'asc') . ', ' : ($isListorder ? '`listorder` desc,' : '');
			
			$condtions='where `status`' . ($istrash ? '=' : '!=') . '0';
			$condtions.=(array_key_exists('catid', $fields) ? ' and `catid`=' . $catid : '');
			// 处理是否推荐
			if(!empty($position_field) && isset($_GET['s_position'])){
				$s_position=intval($_GET['s_position']);
				if($s_position != -1 && in_array($s_position, array(-1,0,1))){
					$condtions.=' and `' . $position_field . '`=' . $s_position;
				}
			}
			
			// 处理时间
			if(!empty($datetimes) && (!empty($_GET['s_begintime']) || !empty($_GET['s_endtime']))){
				if(!empty($_GET['s_begintime']) && !empty($_GET['s_endtime'])){
					$startTime=strtotime($_GET['s_begintime'] . ' 00:00:00');
					$endTime=strtotime($_GET['s_endtime'] . ' 00:00:00');
					if($startTime > $endTime){
						$condtions.=' and `' . $_GET['s_datetime'] . '`>=' . $endTime;
						$condtions.=' and `' . $_GET['s_datetime'] . '`<=' . $startTime;
						$beginTime=$_GET['s_begintime'];
						$_GET['s_begintime']=$_GET['s_endtime'];
						$_GET['s_endtime']=$beginTime;
					}else if($startTime == $endTime){
						$condtions.=' and `' . $_GET['s_datetime'] . '`>=' . $startTime;
						$_GET['s_endtime']='';
					}else{
						$condtions.=' and `' . $_GET['s_datetime'] . '`>=' . $startTime;
						$condtions.=' and `' . $_GET['s_datetime'] . '`<=' . $endTime;
					}
				}else if(!empty($_GET['s_begintime'])){
					$condtions.=' and `' . $_GET['s_datetime'] . '`>=' . strtotime($_GET['s_begintime'] . ' 00:00:00');
				}else if(!empty($_GET['s_endtime'])){
					$condtions.=' and `' . $_GET['s_datetime'] . '`<=' . strtotime($_GET['s_endtime'] . ' 00:00:00');
				}
			}
			
			// 处理关键字
			$s_kw=isset($_GET['s_kw']) ? trim(safe_replace($_GET['s_kw'])) : '';
			if(array_key_exists($_GET['s_type'], $fields) && $s_kw !== ''){
				$s_kw=preg_replace("/\s+/", '%', $s_kw);
				$condtions.=' and `' . $_GET['s_type'] . '` like \'%' . $s_kw . '%\'';
			}
			
			$pageType='page';
			$cpage=isset($_GET[$pageType]) ? $_GET[$pageType] : 1;
			$total=$this->getDb()->count($categorys[$catid]['model'], $condtions);
			$total_isImage=$this->getDb()->count($categorys[$catid]['model'], $condtions . ' and `isimages`=1');
			
			// 显示方式:1或0为列表显示，2为缩略图显示，如果有图片的信息未超半数，强制使用列表显示
			$viewType=isset($_GET['view_type']) ? intval($_GET['view_type']) : 1;
			if($total_isImage < ($total / 2)){
				$viewType=0;
			}
			
			// 从客户端获取分页的大小
			$psize=(isset($_SESSION['custom']['lsize']) && isset($_SESSION['custom']['tsize']) ? ($viewType != 2 ? $_SESSION['custom']['lsize'] : $_SESSION['custom']['tsize']) : 16);
			$limit=$this->getDb()->getlimit($cpage, $psize, $total);
			$this->_resetFormat(1, ($viewType != 2 ? 0 : 1), 0);
			$this->getDb()->setFilter(array($this,'_setOutput'));
			$infos=$this->getDb()->select($categorys[$catid]['model'], ($viewType != 2 ? implode(',', $sfields) : '*'), $condtions, 'order by ' . $listorder_str . '`id` desc', $limit);
			$this->getDb()->unsetFilter();
			if($viewType == 2){
				$fieldArrs=get_formtype_fields($fields, 'title');
				$title_field=$fieldArrs['title'][0];
				unset($fieldArrs);
			}
			
			$pages=getpage(array('total' => $total,'cPage' => $cpage,'size' => $psize,'type' => $pageType), 5);
			
			$auth_add=load::controller('role')->_check_auth('content', 'add', $catid);
			$auth_edit=load::controller('role')->_check_auth('content', 'edit', $catid);
			$auth_trash=load::controller('role')->_check_auth('content', 'trash', $catid);
			
			$arr_auths=!$istrash ? array('listorder' => '排序','trash' => '回收站','del' => '永久删除','audit' => '重新审核','push' => '推送') : array('trash' => '恢复','del' => '永久删除');
			foreach($arr_auths as $k=>$v){
				if(($k == 'listorder' && !$isListorder) || ($k == 'audit' && !$audit_levels) || ($k == 'push' && !$is_push) || !load::controller('role')->_check_auth('content', $k, $catid)){
					unset($arr_auths[$k]);
				}
			}
			include template('manage', 'content');
		}else if($categorys[$catid]['type'] == 1){
			/**
			 * **[单页栏目]***
			 */
			$this->edit();
		}
	}

	public function add(){
		$catid=intval($_GET['catid']);
		if(isset($_POST['dosubmit']) || isset($_POST['dosubmit_continue'])){
			
			include getcache('formtype', 'formtype', 'file', 'input');
			$content_input=new formtype_input($this->categorys[$catid]['model'], $catid);
			$datas=$content_input->_get($_POST['info']);
			!is_array($datas) && $this->showmessage($datas);
			
			// 设置系统基本数据
			$this->setBaseValue($datas, $content_input->fields, $this->categorys[$catid], ROUTE_A);
			
			$res=$this->getDb()->insert($this->categorys[$catid]['model'], $datas);
			if($res > 0){
				$insertId=$this->getDb()->lastInsert($this->categorys[$catid]['model'], 'id');
				$this->getDb()->insert('hits', array('hitsid' => $insertId,'catid' => $catid,'views' => 0,'yesterdayviews' => 0,'dayviews' => 0,'weekviews' => 0,'monthviews' => 0,'viewtime' => NOW_TIME,'status' => $datas['status']));
				include getcache('formtype', 'formtype', 'file', 'update');
				$content_update=new formtype_update($this->categorys[$catid]['model'], $insertId, $catid, $this->categorys);
				$content_update->_update($_POST['info'], $datas);
				
				// 钩子设置
				$datas['id']=$insertId;
				$datas['catid']=$catid;
				load::hook('add', $datas);
			}
			
			if(isset($_POST['dosubmit'])){ // 保存后自动关闭或返回管理
				if($_GET['opentype']){ // 打开方式为对话框或新窗口时保存后自动关闭
					$this->showmessage('添加成功！{s}秒后自动关闭', 'close', 3000);
				}else{ // 打开方式为内置时保存后返回管理
					$this->showmessage('添加成功！{s}秒后返回管理', act_url('content', 'manage', 'catid=' . $catid));
				}
			}else{ // 保存后继续发表
				$this->showmessage('添加成功！{s}秒后返回继续发表', $_SERVER['HTTP_REFERER']);
				// $this->showmessage('添加成功！3秒后返回继续发表');
			}
		}else{
			load::cls('Form', 0);
			include getcache('formtype', 'formtype', 'file', 'form');
			$model=trim($this->categorys[$catid]['model']) == '' ? 'page' : $this->categorys[$catid]['model'];
			$content_form=new formtype_form($model, $catid, $this->categorys);
			
			$forminfos=$content_form->_get();
			$formValidator=$content_form->_getValidator();
			unset($content_form);
			include template('add', 'content');
			header("Cache-control: private");
		}
	}

	public function edit(){
		$catid=intval($_GET['catid']);
		$category=$this->categorys[$catid];
		$id=!$category['type'] && isset($_GET['id']) ? max(intval($_GET['id']), 0) : -1;
		$model=$this->categorys[$catid]['model'] ? $this->categorys[$catid]['model'] : 'page';
		if(isset($_GET['edit_field']) && isset($_GET['edit_value'])){
			if($catid > 0 && $id > 0 && $this->getDb()->hasField($model, $_GET['edit_field'])){
				include getcache('formtype', 'formtype', 'file', 'input');
				$content_input=new formtype_input($model, $catid, $id);
				$datas=$content_input->_get(array($_GET['edit_field'] => $_GET['edit_value']), 1);
				$res=is_array($datas) && ($this->getDb()->update($model, $datas, 'where `catid`=' . $catid . ' and `id`=' . $id) > 0);
				
				// 钩子设置
				if($res){
					$datas['id']=$id;
					$datas['catid']=$catid;
					load::hook('edit', $datas);
				}
				$this->showmessage($res ? '操作成功！' : '操作失败！', $_SERVER['HTTP_REFERER']);
			}else{
				$this->showmessage('操作失败！', $_SERVER['HTTP_REFERER']);
			}
		}
		
		if(isset($_POST['dosubmit']) || isset($_POST['dosubmit_continue'])){
			include getcache('formtype', 'formtype', 'file', 'input');
			$content_input=new formtype_input($model, $catid, $id);
			$datas=$content_input->_get($_POST['info']);
			!is_array($datas) && $this->showmessage($datas);
			// 设置系统基本数据
			$this->setBaseValue($datas, $content_input->fields, $this->categorys[$catid], ROUTE_A);
			if($id != -1){
				$res=$this->getDb()->update($model, $datas, 'where `catid`=' . $catid . ' and `id`=' . $id);
			}else{
				if($this->getDb()->hasFdVl($model, 'catid', $catid)){
					$res=$this->getDb()->update($model, $datas, 'where `catid`=' . $catid);
				}else{
					$res=$this->getDb()->insert($model, $datas);
					$id=$this->getDb()->lastInsert($model, 'id');
					$this->getDb()->insert('hits', array('hitsid' => $id,'catid' => $catid,'views' => 0,'yesterdayviews' => 0,'dayviews' => 0,'weekviews' => 0,'monthviews' => 0,'viewtime' => NOW_TIME));
				}
			}
			
			include getcache('formtype', 'formtype', 'file', 'update');
			$content_update=new formtype_update($model, $id, $catid, $this->categorys);
			$content_update->_update($_POST['info'], $datas);
			
			if($_GET['opentype']){
				$js=$_GET['opentype'] == 1 ? 'top.doMainFrame.location.reload();' : 'opener.location.reload();';
			}
			
			if($res > 0){
				$datas['id']=$id;
				$datas['catid']=$catid;
				load::hook('edit', $datas);
			}
			
			if(isset($_POST['dosubmit'])){ // 保存后自动关闭或返回管理
				if($_GET['opentype']){ // 打开方式为对话框或新窗口时保存后自动关闭
					$this->showmessage('保存成功！{s}秒后自动关闭', 'close', 3000, '', $js);
				}else{
					$this->showmessage('保存成功！{s}秒后返回管理', act_url('content', 'manage', 'catid=' . $catid));
				}
			}else{ // 保存后继续发表
				$this->showmessage('保存成功！{s}秒后返回修改', $_SERVER['HTTP_REFERER'], 1250, '', $js);
			}
		}else{ // 保存单页或一般栏目页修改
			load::cls('Form', 0);
			include getcache('formtype', 'formtype', 'file', 'form');
			$content_form=new formtype_form($model, $catid, $this->categorys);
			$data=$this->getDb()->getOne($model, '*', 'where `catid`=' . $catid . ($id != -1 ? ' and `id`=' . $id : ''));
			$forminfos=$content_form->_get($data);
			unset($data);
			$formValidator=$content_form->_getValidator();
			unset($content_form);
			include template('edit', 'content');
			header("Cache-control: private");
		}
	}

	public function del(){
		$id=isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : -1);
		$catid=isset($_GET['catid']) ? $_GET['catid'] : (isset($_POST['catid']) ? $_POST['catid'] : -1);
		if($id == -1 || $catid == -1 || !isset($this->categorys[$catid])){
			$this->showmessage('删除参数提交错误！{s}秒后返回', $_SERVER['HTTP_REFERER']);
		}else{
			$ids=is_array($id) ? $id : array($id);
			unset($id);
			$successNum=0;
			$this->getDb()->trans('start');
			foreach($ids as $id){
				$id=intval($id);
				if($this->getDb()->delete($this->categorys[$catid]['model'], 'where `id`=' . $id) > 0){
					$this->getDb()->delete('hits', 'where `catid`=' . $catid . ' and `hitsid`=' . $id);
					$successNum++;
				}
			}
			$this->getDb()->trans('end');
			
			// 设置钩子
			if($successNum > 0){
				load::hook('del', array($catid,$ids));
			}
			
			$this->showmessage('删除成功' . $successNum . '项！{s}秒后返回管理', $_SERVER['HTTP_REFERER']);
		}
	}

	public function listorder(){
		$catid=isset($_GET['catid']) ? $_GET['catid'] : (isset($_POST['catid']) ? $_POST['catid'] : -1);
		if($catid != -1 && isset($this->categorys[$catid])){
			$orderNum=0;
			if($this->getDb()->hasField($this->categorys[$catid]['model'], 'listorder')){
				$this->getDb()->trans('start');
				foreach($_POST['listorders'] as $id=>$listorder){
					$orderNum+=$this->getDb()->update($this->categorys[$catid]['model'], array('listorder' => $listorder), 'where `id`=' . $id);
				}
				$this->getDb()->trans('end');
			}
			$this->showmessage('此次排序操作成功！共影响' . $orderNum . '项', $_SERVER['HTTP_REFERER']);
		}else{
			$this->showmessage('排序操作失败！', $_SERVER['HTTP_REFERER']);
		}
	}

	public function trash(){
		$catid=intval(Param::get_para('catid'));
		$trash=intval($_GET['trash']);
		$id=isset($_POST['id']) ? $_POST['id'] : intval($_GET['id']);
		$ids=is_array($id) ? $id : array($id);
		unset($id);
		$num=0;
		
		if(isset($this->categorys[$catid])){
			if($trash){ // 从回收站中恢复到审核状态
				foreach($ids as $k=>$id){
					$ids[$k]=$catid . '_' . $id;
					$info=$this->getDb()->getOne('hits', 'id,status,hitsid,catid', 'where `hitsid`=' . $id . ' and `catid`=' . $catid);
					if($this->getDb()->update($this->categorys[$catid]['model'], array('status' => $info['status']), 'where `catid`=' . $catid . ' and `id`=' . $id)){
						$num++;
					}
					unset($info);
				}
				if($num){
					// 钩子设置
					load::hook('trash', array($trash,$ids));
					$this->showmessage('已成功将' . $num . '条信息移出回收站！', $_SERVER['HTTP_REFERER']);
				}
			}else{ // 放入回收站
				foreach($ids as $k=>$id){
					$ids[$k]=$catid . '_' . $id;
					if($this->getDb()->update($this->categorys[$catid]['model'], array('status' => 0), 'where `catid`=' . $catid . ' and `id`=' . $id)){
						$num++;
					}
				}
				if($num){
					// 钩子设置
					load::hook('trash', array($trash,$ids));
					$this->showmessage('已成功将' . $num . '条信息放入回收站！', $_SERVER['HTTP_REFERER']);
				}
			}
		}
		$this->showmessage('操作失败！', $_SERVER['HTTP_REFERER']);
	}

	public function audit(){
		$models=getcache('model', 'model', 'array');
		$categorys=&$this->categorys;
		$audit_type=intval(Param::get_para('audit_type'));
		$audit_auth=load::controller('user')->_getAudit($_SESSION['userid']);
		if($audit_auth < $audit_type){
			$this->showmessage('无审核权限！', $_SERVER['HTTP_REFERER']);
		}
		
		if(!$audit_type && is_array($_POST['id'])){
			// 重新审核
			$catid=intval($_GET['catid']);
			$num=0;
			if(isset($categorys[$catid]) && !empty($catid)){
				$idArr=array();
				foreach($_POST['id'] as $k=>$id){
					$idArr[]=intval($id);
					$_POST['id'][$k]=$catid . '_' . $id;
				}
				$rLevel=intval($categorys[$catid]['setting']['audit']) + 1;
				$num=$this->getDb()->update('hits', array('status' => $rLevel), 'where `catid`=' . $catid . ' and `hitsid` in(' . implode(',', $idArr) . ')');
				$this->getDb()->update($categorys[$catid]['model'], array('status' => $rLevel), 'where `catid`=' . $catid . ' and `status`>0 and `id` in(' . implode(',', $idArr) . ')');
				
				// 钩子设置
				if($num > 0){
					load::hook('audit', array($audit_type,$_POST['id']));
				}
			}
			
			$this->showmessage('已经成功将' . $num . '条信息加入审核流程！', $_SERVER['HTTP_REFERER']);
		}else if($audit_type && is_array($_POST['ids'])){
			// 开始审核
			$infos=array();
			$num=0;
			foreach($_POST['ids'] as $v){
				$vs=explode('_', $v);
				$catid=intval($vs[0]);
				$id=intval($vs[1]);
				$audit_levels=intval($categorys[$catid]['setting']['audit']);
				$rLevel=max(1, ($audit_levels + 1 - $audit_type));
				if($this->getDb()->update('hits', array('status' => $rLevel), 'where `catid`=' . $catid . ' and `hitsid`=' . $id)){
					$this->getDb()->update($categorys[$catid]['model'], array('status' => $rLevel), 'where `catid`=' . $catid . ' and `id`=' . $id . ' and `status` >0');
					$num++;
				}
				unset($vs);
			}
			
			// 钩子设置
			if($num > 0){
				load::hook('audit', array($audit_type,$_POST['ids']));
			}
			$this->showmessage('成功审核' . $num . '项！', act_url('content', 'audit'));
		}
		
		// 钩子设置
		$hooks=getcache('hook', 'core', 'array', 'map');
		if($hooks && isset($hooks['add'])){
			foreach(explode(',', $hooks['add']) as $hook){
				$hookArr=explode('.', $hook);
				$datas['id']=$insertId;
				call_user_func(array(load::plugin($hookArr[0]),$hookArr[1]), $datas);
			}
		}
		unset($hooks, $hook, $hookArr);
		
		// 权限管理
		$condtions='where `status`>1';
		$pageType='page';
		$cpage=isset($_GET[$pageType]) ? $_GET[$pageType] : 1;
		// 从客户端获取分页的大小
		$psize=(isset($_SESSION['custom']['lsize']) ? $_SESSION['custom']['lsize'] : 16);
		$total=$this->getDb()->count('hits', $condtions);
		$limit=$this->getDb()->getlimit($cpage, $psize, $total);
		$pages=getpage(array('total' => $total,'cPage' => $cpage,'size' => $psize,'type' => $pageType), 5);
		
		$infos=$this->getDb()->select('hits', 'hitsid,catid,status', 'where `status`>1', 'order by `id` desc', $limit);
		$audits=load::controller('user')->_getAudit();
		$audits_state=array('未审核','已初审','已复审');
		$title_form=array('title','text','description','editor');
		foreach($infos as $k=>$v){
			$v['id']=$v['hitsid'];
			$catid=intval($v['catid']);
			$title_fd='';
			$forms=get_formtype_fields($categorys[$catid]['model'], $title_form);
			foreach($title_form as $cfd){
				if(isset($forms[$cfd])){
					$title_fd=',' . $forms[$cfd][0] . ' as title';
					break;
				}
			}
			
			$info=$this->getDb()->getOne($categorys[$catid]['model'], 'id,catid' . $title_fd . ',status', 'where `id`=' . $v['id']);
			$audit_levels=intval($categorys[$catid]['setting']['audit']); // 栏目审核步数
			$s_status=intval($v['status']);
			
			$title_arr=array();
			if(stripos($info['title'], '<span') === 0){
				$patern="/^<span\s*style=\"display:inline;(.*?)\">/i";
				preg_match($patern, $info['title'], $matches);
				$title_arr['style']=$matches[1];
				$title_arr['value']=preg_replace("/<[^<>]+>/", '', $info['title']);
			}else{
				$title_arr['value']=stripos($info['title'], '<') === false ? $info['title'] : preg_replace("/<[^<>]+>/", '', $info['title']);
			}
			
			$v['title']=$title_arr;
			$v['istrash']=$info['status'] != 0 ? 0 : 1;
			$v['catname']=$categorys[$catid]['name'];
			$v['audit_status']=$audit_levels + 1 - $s_status;
			
			$v['audit_steps']=$audit_levels;
			$v['modelname']=$models[$categorys[$catid]['model']]['name'];
			$v['auth_edit']=load::controller('role')->_check_auth('content', 'edit', $catid);
			$v['auth_trash']=load::controller('role')->_check_auth('content', 'trash', $catid);
			$v['auth_del']=load::controller('role')->_check_auth('content', 'del', $catid);
			$infos[$k]=$v;
			unset($info);
		}
		include template('audit', 'content');
	}

	public function push(){
		$catid=intval(Param::get_para('catid'));
		$categorys=&$this->categorys;
		if(isset($_GET['dosubmit'])){
			if(!empty($_POST['catids']) && is_array($_POST['catids']) && !empty($_POST['ids'])){
				$cat_count=0;
				$id_count=0;
				$model=$categorys[$catid]['model'];
				$form_fields=get_formtype_fields($model, 'posid');
				$posid_field=isset($form_fields['posid']) ? $form_fields['posid'][0] : NULL;
				$ids=explode(',', $_POST['ids']);
				
				unset($form_fields);
				$this->getDb()->trans('start');
				foreach($_POST['catids'] as $push_catid){
					$push_catid=intval($push_catid);
					// 判断推送栏目ID是否存在和是否为同一模型
					if(isset($categorys[$push_catid]) && $categorys[$push_catid]['model'] == $model && load::controller('role')->_check_auth('content', 'add', $push_catid)){
						$cat_count++;
						foreach($ids as $cid){
							$id=intval($cid);
							$info=$this->getDb()->getOne($model, '*', 'where `catid`=' . $catid . ' and `id`=' . $id);
							if(!empty($info)){
								unset($info['id']);
								$info['catid']=$push_catid; // 设置新的ID；
								$info['hits']=0; // 清空访问统计；
								if($posid_field){ // 清空推荐
									$info[$posid_field]=0;
								}
								
								$in_res=$this->getDb()->insert($model, $info);
								if($in_res != -1 && $in_res){
									$id_count++;
									$lastInsertId=$this->getDb()->lastInsert($model);
									if($lastInsertId != -1){
										// 添加到统计表
										$this->getDb()->insert('hits', array('hitsid' => $lastInsertId,'catid' => $push_catid,'views' => 0,'yesterdayviews' => 0,'dayviews' => 0,'weekviews' => 0,'monthviews' => 0,'viewtime' => NOW_TIME,'status' => $info['status']));
									}
								}
								unset($info);
							}
						}
					}
				}
				$this->getDb()->trans('end');
				if($id_count){
					// 钩子设置
					load::hook('push', array($catid,$_POST['catids'],$ids));
				}
				alert('成功推荐' . $id_count . '条信息到' . $cat_count . '个栏目！');
			}
			alert('0');
		}else{
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;&nbsp;';
			$category=getcache('category', 'core', 'array', 'base');
			$check_catids=empty($_GET['catids']) ? array() : explode(',', $_GET['catids']);
			foreach($category as $cid=>$v){
				if($v['type'] || $cid == $catid || $categorys[$cid]['model'] != $categorys[$catid]['model'] || !load::controller('role')->_check_auth('content', 'add', $cid)){
					unset($category[$cid]);
				}else{
					$v['count']=!empty($v['arrcid']) ? '-' : $this->getDb()->count('hits', 'where `catid`=' . $cid);
					$v['disabled']=!empty($v['arrcid']) ? 'disabled="disabled"' : '';
					$v['checked']=in_array($cid, $check_catids) ? 'checked="checked"' : '';
					$category[$cid]=$v;
				}
			}
			$str="<tr>
					<td align='center'><input type='checkbox' \$checked \$disabled name='catids[]'  value='\$id'></td>
				  <td align='center'> \$id </td>
				  <td align='left'> \$spacer\$name </td>
				  <td align='center'> \$count </td>
			  </tr>";
			$tree->init($category);
			$categorys=$tree->get_tree(0, $str);
			include template('push', 'content');
		}
	}

	public function tocats(){
		echo 'tocats';
	}

	/**
	 * ********************内部私有方法*******************
	 */
	private function getOutputFormat($field,$v,$position_field,$dx,$s_kw,$searchColor='red',$thumbHeight=15){
		$maxchars=intval($field['msetting']['maxchars']);
		$style=empty($field['msetting']['align']) ? '' : ' text-align:' . $field['msetting']['align'] . ';';
		$val=$old_val='';
		$isthumb=0;
		$ispos=0;
		$isaudit=0;
		$isAdd=false;
		switch($field['formtype']){
			case 'title':
				$style.=$v[$field['field']]['style'];
				$val=$v[$field['field']]['value'];
				$isthumb=$v['isimages'] ? 1 : 0;
				$ispos=!empty($position_field) && $v[$position_field] ? 1 : 0;
				$isaudit=$v['status'] > 1 ? 1 : 0;
				break;
			// 以下5种类型为转换数据
			case 'image':
				$maxchars=0;
				$val='<img title="点击查看大图" style="cursor:pointer;" onclick="view_images(\'' . $v[$field['field']] . '\')" src="' . act_url('api', 'thumb', 'f=' . urlencode($v[$field['field']]) . '&h=' . $thumbHeight, 1) . '"/>';
				break;
			case 'images':
				$maxchars=0;
				$imgCount=count($v[$field['field']]);
				$imgurls=array();
				foreach($v[$field['field']] as $cimg){
					$imgurls[]='\'' . $cimg['url'] . '\'';
				}
				if($imgCount){
					$val='<a href="javascript:" onclick="view_images([' . implode(',', $imgurls) . '])" >查看(共' . $imgCount . '张)</a>';
				}else{
					$val='无图片';
				}
				unset($imgCount, $imgurls);
				break;
			case 'classid':
				$val=implode('/', load::controller('classify')->_getTypeName($v[$field['field']], 1));
				break;
			case 'typeid':
				$val=implode('/', load::controller('classify')->_getTypeName($v[$field['field']], 1));
				break;
			case 'downfiles':
				$maxchars=0;
				$fileCount=count($v[$field['field']]);
				if($fileCount){
					$val='<a href="javascript:"> 共' . $fileCount . '个</a>';
				}else{
					$val='无文件';
				}
				unset($fileCount);
				break;
			case 'keyword':
				$val=implode('，', $v[$field['field']]);
				break;
			case 'bool':
				$maxchars=0;
				if($field['setting']['isedit']){
					$acturl=act_url('content', 'edit', 'catid=' . $v['catid'] . '&id=' . $v['id'] . '&edit_field=' . $field['field']);
					$val=$v[$field['field']] ? '<a href="' . $acturl . '&edit_value=0" style="color:green;">√</a>' : '<a href="' . $acturl . '&edit_value=1" style="color:red;">×</a>';
				}else{
					$val=$v[$field['field']] ? '是' : '否';
				}
				break;
			default:
				$val=$v[$field['field']];
				break;
		}
		
		if($maxchars){
			$old_val=$val;
			$val=cut_str($val, $maxchars, true, $isAdd);
		}
		if(isset($_GET['s_type']) && $_GET['s_type'] == $dx && !empty($s_kw)){
			if(strpos($s_kw, '%') === false){
				$val=str_replace($s_kw, '<font color="' . $searchColor . '">' . $s_kw . '</font>', $val);
			}else{
				$s_kwArr=explode('%', $s_kw);
				foreach($s_kwArr as $ckey){
					if($ckey){
						$val=str_replace($ckey, '<font color="' . $searchColor . '">' . $ckey . '</font>', $val);
					}
				}
			}
		}
		if($isAdd){
			$val='<span title="' . $old_val . '">' . $val . '</span>';
		}
		return array('style' => $style,'val' => $val,'isthumb' => $isthumb,'ispos' => $ispos,'isaudit' => $isaudit);
	}

	private function getOutput($catid=-1){
		if(is_null($this->content_output) && $catid != -1){
			include_once getcache('formtype', 'formtype', 'file', 'output');
			$this->content_output=new formtype_output($this->categorys[$catid]['model'], $catid, $this->categorys);
		}
		return $this->content_output;
	}

	/*
	 * 功能：设置系统基本数据 说明：系统基本数据包括
	 */
	private function setBaseValue(&$datas,&$fields,&$category,$type){
		$fieldArr=array('catid','isimages','status','userid','addtime','edittime','hits','allimages','listorder');
		$editArr=array('status','userid','addtime','hits','listorder'); // 编辑时不需要设置的字段
		foreach($fieldArr as $field){
			if(array_key_exists($field, $fields) && $fields[$field]['isbase'] && !$fields[$field]['disabled'] && !array_key_exists($field, $datas) && ($type == 'add' || !in_array($field, $editArr))){
				switch($field){
					case 'catid': // 设置栏目ID
						$datas[$field]=$category['id'];
						break;
					case 'status': // 设置状态值
						$datas[$field]=intval($category['setting']['audit']) + 1;
						break;
					case 'userid': // 设置用户ID
						$datas[$field]=$_SESSION['userid'];
						break;
					case 'addtime': // 设置添加时间
						$datas[$field]=NOW_TIME;
						break;
					case 'edittime': // 设置编辑时间
						$datas[$field]=NOW_TIME;
						break;
					case 'hits': // 初始化点击量
						$datas[$field]=0;
						break;
					case 'isimages': // 判断是否带有图片
						$datas[$field]=$this->get_images($datas, $fields, 1);
						break;
					case 'allimages': // 判断是否带有图片
						$allimgs=$this->get_images($datas, $fields, 0);
						$datas[$field]=empty($allimgs) ? '' : array2string($allimgs);
						unset($allimgs);
						break;
					case 'listorder':
						$datas[$field]=0;
						break;
				}
			}
		}
	}

	private function setBaseField(&$paras,$atts=array('catid','id')){
		$field=isset($paras['field']) ? $paras['field'] : '*';
		if($field != '*'){
			return array_unique(array_merge($atts, explode(',', str_replace(' ', '', $field))));
		}
		return $field;
	}

	/*
	 * 功能：检查内容中的是否含有指定类型的数据 说明：通过内部循环检查字段数据中是否含有指定类型的数据,可以检查图片或音视频数据
	 */
	private function check(&$datas,&$fields,$type='img'){
		static $cfds=array();
		if(!empty($cfds)){
			foreach($cfds as $fd=>$formtype){
				if($formtype == 'editor'){
					switch($type){
						case 'img':
							if(stripos($datas[$fd], '<img') !== false){
								return true;
							}
							break;
						case 'object':
							if(stripos($datas[$fd], '<object') !== false){
								return true;
							}
							break;
					}
				}else if($type == 'img' && ($formtype == 'image' || $formtype == 'images')){
					if(!empty($datas[$fd])){
						return true;
					}
				}
			}
		}else{
			foreach($fields as $v){
				if($v['formtype'] == 'editor'){
					$cfds[$v['field']]='editor';
					switch($type){
						case 'img':
							if(stripos($datas[$v['field']], '<img') !== false){
								return true;
							}
							break;
						case 'object':
							if(stripos($datas[$v['field']], '<object') !== false){
								return true;
							}
							break;
					}
				}else if($type == 'img' && ($v['formtype'] == 'image' || $v['formtype'] == 'images')){
					$cfds[$v['field']]=$v['formtype'];
					if(!empty($datas[$v['field']])){
						return true;
					}
				}
			}
		}
		return false;
	}

	private function get_images(&$datas,&$fields,$isCheck=1,$forms=NULL){
		$forms=is_null($forms) ? array('image','images','editor') : $forms;
		$formtypes=get_formtype_fields($fields, $forms);
		$imgs=array();
		if(!empty($formtypes)){
			foreach($forms as $cForm){
				if(isset($formtypes[$cForm])){
					if($cForm == 'editor'){
						foreach($formtypes[$cForm] as $cfd){
							if(stripos($datas[$cfd], '<img') !== false){
								if(preg_match_all("/(href|src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $datas[$cfd], $matches)){
									foreach($matches[3] as $matche){
										$smileyImgPos=stripos($matche, 'editor/plugins/smiley/images/');
										if($smileyImgPos !== false && strripos($matche, '/') == ($smileyImgPos + 28)){
											continue; // 此处排除编辑器中表情图片
										}
										if($isCheck){
											return 1;
										}else{
											$imgs[]=$matche;
										}
									}
								}
								unset($matches);
							}
						}
					}else{
						foreach($formtypes[$cForm] as $cfd){
							if(!empty($datas[$cfd])){
								if($isCheck){
									return 1;
								}else{
									if(is_array($datas[$cfd])){
										foreach($datas[$cfd] as $cdt){
											$imgs[]=$cdt['url'];
										}
									}else{
										$imgs[]=$datas[$cfd];
									}
								}
							}
						}
					}
				}
			}
		}
		return $isCheck ? 0 : $imgs;
	}

	/**
	 * ********************内部接口方法[标签方法]*******************
	 */
	public function _resetFormat($isformat=0,$isthumb=0,$isimages=0){
		$this->format=$isformat;
		$this->thumb=$isthumb;
		$this->images=$isimages;
	}

	public function _setOutput($data){
		if(!empty($data)){
			// 处理格式化
			if($this->format){
				$data=$this->getOutput($data['catid'])->_get($data, $data['catid']);
			}
			
			// 处理图片
			$isthumb=$this->thumb && empty($data['thumb']);
			$isimages=$this->images && !isset($data['allimages']);
			if(($isthumb || $isimages) && isset($data['catid']) && isset($this->categorys[$data['catid']])){
				$images=$this->get_images($data, $this->categorys[$data['catid']]['model'], 0);
				if($isthumb){
					$data['thumb']=$images[0];
				}
				if($isimages){
					$data['allimages']=$images;
				}
			}
			
			// 处理URL
			load::controller('admin.urlrule')->_setShowURL($data);
		}
		return $data;
	}

	public function _toDataID($data){
		return intval($data['id']);
	}
}

?>