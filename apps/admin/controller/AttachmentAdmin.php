<?php
defined('IN_MYCMS') or exit('No permission resources.');
load::func('dir');
class AttachmentAdmin extends AdminController{

	public function __construct(){
		parent::__construct();
		$this->imgext=array('jpg','gif','png','bmp','jpeg','tiff');
		$this->roleid=intval($_SESSION['roleid']);
		$this->userid=-intval($_SESSION['userid']);
	}
	
	// /////////////////////////内部管理方法///////////////////////////////
	public function init(){
		$categorys=getcache('category', 'core', 'array');
		$catnames=array();
		foreach($categorys as $k=>$v){
			$catnames[$k]=$v['name'];
		}
		unset($categorys);
		
		$models=getcache('model', 'model', 'array');
		$modelnames=array();
		foreach($models as $k=>$v){
			$modelnames[$k]=$v['name'];
		}
		unset($models);
		
		$pagetype='p';
		$cpage=isset($_GET[$pagetype]) ? $_GET[$pagetype] : 1;
		$viewType=isset($_GET['view_type']) ? intval($_GET['view_type']) : 1;
		$psize=(isset($_SESSION['custom']['lsize']) && isset($_SESSION['custom']['tsize']) ? ($viewType != 2 ? $_SESSION['custom']['lsize'] : $_SESSION['custom']['tsize']) : 16);
		
		$_GET=safe_replace($_GET);
		$condtions='where `id`>=0';
		// 处理时间
		if(!empty($_GET['s_begintime']) || !empty($_GET['s_endtime'])){
			if(!empty($_GET['s_begintime']) && !empty($_GET['s_endtime'])){
				$startTime=strtotime($_GET['s_begintime'] . ' 00:00:00');
				$endTime=strtotime($_GET['s_endtime'] . ' 00:00:00');
				if($startTime > $endTime){
					$condtions.=' and `uploadtime`>=' . $endTime;
					$condtions.=' and `uploadtime`<=' . $startTime;
					$beginTime=$_GET['s_begintime'];
					$_GET['s_begintime']=$_GET['s_endtime'];
					$_GET['s_endtime']=$beginTime;
				}else if($startTime == $endTime){
					$condtions.=' and `uploadtime`>=' . $startTime;
					$_GET['s_endtime']='';
				}else{
					$condtions.=' and `uploadtime`>=' . $startTime;
					$condtions.=' and `uploadtime`<=' . $endTime;
				}
			}else if(!empty($_GET['s_begintime'])){
				$condtions.=' and `uploadtime`>=' . strtotime($_GET['s_begintime'] . ' 00:00:00');
			}else if(!empty($_GET['s_endtime'])){
				$condtions.=' and `uploadtime`<=' . strtotime($_GET['s_endtime'] . ' 00:00:00');
			}
		}
		
		// 处理关键字
		if(isset($_GET['s_name'])){
			$_GET['s_name']=trim($_GET['s_name']);
			if(!empty($_GET['s_name'])){
				$s_kw=preg_replace("/\s+/", '%', $_GET['s_name']);
				$condtions.=' and `filename` like \'%' . $s_kw . '%\'';
			}
		}
		if(isset($_GET['s_status']) && $_GET['s_status'] != -1){
			$condtions.=' and `status` ' . ($_GET['s_status'] == 0 ? '=' : '!=') . ' 0';
		}
		
		$total=$this->getDb()->count('attachments', $condtions);
		$limit=$this->getDb()->getlimit($cpage, $psize, $total);
		$data=$this->getDb()->select('attachments', '*', $condtions, 'order by `id` desc', $limit);
		$pages=getpage(array('total' => $total,'cPage' => $cpage,'size' => $psize,'type' => $pagetype), 5);
		$auths=load::controller('role')->_check_auth('del,update');
		include template('init', 'attachment');
	}

	public function del(){
		$delids=array();
		if(isset($_POST['id'])){
			foreach($_POST['id'] as $did){
				$delids[]=intval($did);
			}
		}else if(isset($_GET['id'])){
			$delids[]=intval($_GET['id']);
		}
		
		$delNum=0;
		$delThumbNum=0;
		foreach($delids as $delid){
			$info=$this->getDb()->getOne('attachments', '*', 'where `status`=0 and `id`=' . $delid);
			if($info){
				$image=UPLOAD_PATH . $info['filepath'];
				$image_dir=dirname($image);
				$res=$this->getDb()->delete('attachments', 'where `status`=0 and `id`=' . $delid);
				if($res){
					@unlink($image);
					$thumbs=glob($image_dir . '/*' . basename($image));
					if($thumbs){
						foreach($thumbs as $thumb){
							@unlink($thumb);
							$delThumbNum++;
						}
					}
					$delNum++;
				}
				dir_delete($image_dir, 1);
			}
		}
		
		$this->showmessage('删除成功' . $delNum . '张图片,' . $delThumbNum . '张缩略图！', 'auto');
	}

	public function update(){
		$old_url=$_GET['old_url'];
		if(!isset($_GET['setmodel'])){
			$models=array_keys(getcache('model', 'model', 'array'));
			$tables=$this->getDb()->tables();
			$check_forms=array('image','images','editor');
			$setModels=array();
			foreach($models as $key=>$model){
				if(!in_array($this->getDb()->mTb($model), $tables)){
					unset($models[$key]);
				}else{
					$fields_arr=get_formtype_fields($model, $check_forms);
					$fields=array();
					foreach($fields_arr as $ky=>$vl){
						if(!in_array($ky, $check_forms)){
							unset($fields_arr[$ky]);
						}else{
							foreach($vl as $fd){
								$fields[$fd]=$ky;
							}
						}
					}
					if(!empty($fields)){
						$setModels[]=array($model,$fields);
					}
				}
			}
			setcache('update_model_' . $_SESSION['userid'], $setModels, 'attachment', 'array');
			$this->showmessage('开始更新地址...', act_url('attachment', 'update', 'setmodel=1&old_url=' . urlencode($old_url)), 200);
		}
		
		$index=isset($_GET['index']) ? intval($_GET['index']) : 0;
		$page=isset($_GET['page']) ? intval($_GET['page']) : 0;
		$models=getcache('update_model_' . $_SESSION['userid'], 'attachment', 'array');
		
		if(!isset($models[$index])){
			delcache('update_model_' . $_SESSION['userid'], 'attachment');
			$this->showmessage('所有地址更新完成', act_url('attachment', 'init'), 200);
		}
		
		$model=$models[$index][0];
		$forms=&$models[$index][1];
		$pagesize=10;
		
		$fields=array_keys($forms);
		$fields[]='id';
		
		$limit='limit ' . ($page * $pagesize) . ',' . (($page + 1) * $pagesize);
		$data=$this->getDb()->select($model, $fields, 'where `id`>=0', 'order by `id` asc', $limit);
		if(empty($data)){
			$index++;
			$this->showmessage('模型 ' . $model . ' 更新完成！', act_url('attachment', 'update', 'setmodel=1&old_url=' . urlencode($old_url) . '&index=' . $index), 200);
		}else{
			// 地址处理
			$this->getDb()->trans('start');
			foreach($data as $vl){
				$this->getDb()->update($model, $this->replace($vl, $forms, $old_url), 'where `id`=' . $vl['id']);
			}
			$this->getDb()->trans('end');
			$this->showmessage('模型 ' . $model . ' 更新:' . ($page * $pagesize + 1) . '-' . (($page + 1) * $pagesize) . '条信息！', act_url('attachment', 'update', 'setmodel=1&old_url=' . urlencode($old_url) . '&index=' . $index . '&page=' . (++$page)), 200);
		}
	}
	
	// /////////////////////////文件上传访问方法///////////////////////////////
	public function swfupload(){
		if(isset($_POST['dosubmit']) && $_POST['dosubmit']){
			if($_POST['swf_auth_key'] != upload_key($_POST['swf_auth_sec'], 1))
				// || ($_POST['isadmin']==0 && !$grouplist[$_POST['groupid']]['allowattachment']) /*此处为附件权限校验*/
				exit();
			load::cls('Uploadfile', 0, 0);
			$uploadfile=new Uploadfile($_POST['model'], $_POST['catid']);
			$userid=intval($_POST['userid']);
			$uploadfile->set_userid($userid);
			$aids=$uploadfile->upload('Filedata', $_POST['filetype_post'], intval($_POST['file_size_limit']) * 1024, 0, array(intval($_POST['thumb_width']),intval($_POST['thumb_height']),intval($_POST['auto_cut']),intval($_POST['watermark_enable']),intval($_POST['md5_check'])));
			
			if($aids[0] != -1){
				$filename=$uploadfile->uploadedfiles[0]['filename'];
				$authKey='0'; /* * ******!!!此处为外部上传安全认证秘钥******** */
				if($uploadfile->uploadedfiles[0]['isimage']){
					// 若为图片，则直接显示图片
					echo $aids[0] . ',' . $uploadfile->uploadedfiles[0]['filepath'] . ',' . $uploadfile->uploadedfiles[0]['isimage'] . ',' . $filename . ',' . $uploadfile->uploadedfiles[0]['isupload'] . ',' . $authKey;
				}else{
					// 若为非图片文件则显示文件图标
					$fileext=$uploadfile->uploadedfiles[0]['fileext'];
					if($fileext == 'zip' || $fileext == 'rar'){
						$fileext='rar';
					}elseif($fileext == 'doc' || $fileext == 'docx'){
						$fileext='doc';
					}elseif($fileext == 'xls' || $fileext == 'xlsx'){
						$fileext='xls';
					}elseif($fileext == 'ppt' || $fileext == 'pptx'){
						$fileext='ppt';
					}elseif($fileext == 'flv' || $fileext == 'swf' || $fileext == 'rm' || $fileext == 'rmvb'){
						$fileext='flv';
					}else{
						$fileext='do';
					}
					echo $aids[0] . ',' . $uploadfile->uploadedfiles[0]['filepath'] . ',' . $fileext . ',' . $filename . ',' . $uploadfile->uploadedfiles[0]['isupload'] . ',' . $authKey;
				}
				exit();
			}else{
				echo '0,' . $uploadfile->error();
				exit();
			}
		}else{
			extract($this->getswfinit($_GET['args']));
			$swf_auth_key=$_GET['authkey'];
			$swf_auth_sec=md5($_GET['args']);
			$att_not_used_total=$this->att_not_used_count();
			$isadmin=intval($_GET['isadmin']);
			$userid=$this->userid;
			include template('swfupload', 'attachment', true);
		}
	}

	public function attachlist(){
		$pagetype='p';
		$cpage=isset($_GET[$pagetype]) ? $_GET[$pagetype] : 1;
		$psize=12;
		$where='where `isimage`=1' . ($this->roleid > 0 ? '' : ' and `userid`=' . $this->userid);
		$total=$this->getDb()->count('attachments', $where);
		$limit=$this->getDb()->getlimit($cpage, $psize, $total);
		$infos=$this->getDb()->select('attachments', '*', $where, 'order by `id` desc', $limit);
		$pages=getpage(array('total' => $total,'type' => $pagetype,'cPage' => $cpage,'size' => $psize,'pFunc' => 'ajax_page(\'?\',\'' . ROUTE_A . '\')'), 5);
		include template('attachlist', 'attachment', true);
	}

	public function unusedlist(){
		$pagetype='p';
		$cpage=isset($_GET[$pagetype]) ? $_GET[$pagetype] : 1;
		$psize=12;
		$not_used_arr=$this->att_not_used($cpage, $psize);
		$att_not_used_total=$this->att_not_used_count();
		$pages=getpage(array('total' => $att_not_used_total,'type' => $pagetype,'cPage' => $cpage,'size' => $psize,'pFunc' => 'ajax_page(\'?\',\'' . ROUTE_A . '\')'), 5);
		include template('unusedlist', 'attachment', true);
	}

	public function dirlist(){
		if($_GET['args']){
			extract(getswfinit($_GET['args']));
		}
		$dir=isset($_GET['dir']) && trim($_GET['dir']) ? str_replace(array('..\\','../','./','.\\','..'), '', trim($_GET['dir'])) : '';
		$filepath=UPLOAD_PATH . $dir;
		$list=glob($filepath . '/' . '*');
		if(!empty($list)){
			rsort($list);
		}
		// 判断如果是图片浏览，则过滤非图片文件
		if(isset($_GET['isfile']) && !$_GET['isfile']){
			foreach($list as $ky=>$vl){
				if(!is_dir($vl) && !$this->is_image($vl)){
					unset($list[$ky]);
				}
			}
		}
		$local=str_replace(array(ROOT_PATH,CORE_PATH,CD . CD,'\\'), array('','',CD,'/'), $filepath);
		$url=($dir == '.' || $dir == '') ? UPLOAD_URL : UPLOAD_URL . str_replace('.', '', $dir) . '/';
		include template('dirlist', 'attachment', true);
	}

	public function swfupload_json(){
		return $this->upload_json($_GET['aid'], $_GET['src'], $_GET['filename'], true);
	}

	public function swfupload_json_del(){
		$arr['aid']=intval($_GET['aid']);
		$arr['src']=trim($_GET['src']);
		$arr['filename']=urlencode($_GET['filename']);
		$json_str=json_encode($arr);
		$att_arr_exist=cookie('att_json');
		$att_arr_exist=str_replace(array($json_str,'||||'), array('','||'), $att_arr_exist);
		$att_arr_exist=preg_replace('/^\|\|||\|\|$/i', '', $att_arr_exist);
		cookie('att_json', $att_arr_exist);
	}

	public function swfdelete(){
		$att_del_arr=explode('|', $_GET['data']);
		foreach($att_del_arr as $id){
			$cinfo=$this->getDb()->getOne('attachments', 'id,filepath', 'where `id`=' . intval($id));
			if(!empty($cinfo)){
				$filepath=UPLOAD_PATH . $cinfo['filepath'];
				@unlink($filepath);
				$thumbs=glob(dirname($filepath) . '/*' . basename($filepath));
				if($thumbs){
					foreach($thumbs as $thumb){
						@unlink($thumb);
					}
				}
				$this->getDb()->delete('attachments', 'where `id`=' . intval($id));
			}
		}
	}
	
	// //////////////////////////私有方法///////////////////////////
	
	/**
	 * 功能：从上传文件配置中解析上传文件设置 说明： $args[0]->上传数量限制, $args[1]->允许上传类型, $args[2]->是否从已经上传中选择, $args[3]-允许上传的文件大小（单位:KB）, $args[4]->生成缩略图宽度, $args[5]->生成缩略图高度, $args[6]->是否自动剪裁, $args[7]->是否加水印, $args[8]->是否进行MD5校验
	 */
	private function getswfinit($args){
		$args=explode(',', $args);
		$arr['file_upload_limit']=intval($args[0]) ? intval($args[0]) : '8';
		$args[1]=($args[1] != '') ? $args[1] : implode('|', $this->imgext);
		
		$sytem_gdnotsupport=!function_exists('imagepng') && !function_exists('imagejpeg') && !function_exists('imagegif');
		$setting_attachment=getcache('setting', 'setting', 'array', 'attachment');
		$sytem_watermark_enable=intval($setting_attachment['w_type']);
		$sytem_md5_check=intval($setting_attachment['md5_check']);
		$sytem_arr_allowext=explode('|', str_replace(array("\r","\n",' '), '', $setting_attachment['type']));
		
		// 使所有上传文件类型必须是系统唯一设定的子集
		$arr_allowext=array_intersect($sytem_arr_allowext, explode('|', $args[1]));
		
		foreach($arr_allowext as $k=>$v){
			$v=trim(str_replace('*.', '', $v));
			if(!empty($v)){
				$array[$k]='*.' . trim(str_replace('*.', '', $v));
			}
		}
		
		$arr['file_types_swfupload']=implode(';', $array);
		$arr['file_types']=implode(', ', $arr_allowext);
		$arr['file_types_post']=implode('|', $arr_allowext);
		
		// 必须是系统管理员账号登录并且字段允许从上传中选择才会使用从附件目录读取
		$arr['allow_select_uploaded']=(intval($args[2]) && $this->roleid > 0) ? 1 : 0;
		$arr['allow_upload_maxsize']=intval($args[3]);
		$arr['thumb_width']=intval($args[4]);
		$arr['thumb_height']=intval($args[5]);
		$arr['auto_cut']=intval($args[6]);
		$arr['system_watermark_enable']=!$sytem_gdnotsupport && $sytem_watermark_enable ? 1 : 0;
		$arr['watermark_enable']=$arr['system_watermark_enable'] ? intval($args[7]) : 0;
		$arr['md5_check']=$sytem_md5_check && $args[8] !== '0' ? 1 : 0;
		
		$system_upload_max_filesize=@ini_get('file_uploads') ? intval(ini_get('upload_max_filesize')) * 1024 : -1;
		if($system_upload_max_filesize != -1 && $arr['allow_upload_maxsize'] > $system_upload_max_filesize){
			$arr['allow_upload_maxsize']=$system_upload_max_filesize;
		}
		
		return $arr;
	}

	private function upload_json($aid,$src,$filename){
		$arr['aid']=intval($aid);
		$arr['src']=trim($src);
		$arr['filename']=urlencode($filename);
		$json_str=json_encode($arr);
		$att_arr_exist=cookie('att_json');
		$att_arr_exist_tmp=explode('||', $att_arr_exist);
		if(is_array($att_arr_exist_tmp) && in_array($json_str, $att_arr_exist_tmp)){
			return true;
		}else{
			$json_str=$att_arr_exist ? $att_arr_exist . '||' . $json_str : $json_str;
			cookie('att_json', $json_str);
			return true;
		}
	}
	
	// 获取临时未处理文件列表
	private function att_not_used($cPage=1,$page=12){
		$att=array();
		if($att_json=cookie('att_json')){
			if($att_json){
				$att_cookie_arr=explode('||', $att_json);
				$cnum=0;
				$startDx=($cPage - 1) * $page;
				$endDx=$cPage * $page;
				foreach($att_cookie_arr as $_att_c){
					if($cnum >= $startDx && $cnum < $endDx){
						$att[]=json_decode($_att_c, true);
					}
					$cnum++;
				}
			}
			if(is_array($att) && !empty($att)){
				foreach($att as $n=>$v){
					$ext=fileext($v['src']);
					if(in_array($ext, $this->imgext)){
						$att[$n]['src']=$v['src'];
						$att[$n]['width']='80';
						$att[$n]['id']=$v['aid'];
						$att[$n]['filename']=urldecode($v['filename']);
					}else{
						$att[$n]['src']=$this->file_icon($v['src']);
						$att[$n]['width']='64';
						$att[$n]['id']=$v['aid'];
						$att[$n]['filename']=urldecode($v['filename']);
					}
				}
			}
		}
		return $att;
	}
	
	// 获取未使用文件列表
	private function att_not_used_count(){
		if($att_json=cookie('att_json')){
			if($att_json){
				$att_cookie_arr=explode('||', $att_json);
				return count($att_cookie_arr);
			}
		}else{
			return 0;
		}
	}
	
	// 获取文件类型
	private function file_icon($file,$type='png'){
		$ext_arr=array('doc','docx','ppt','xls','txt','pdf','mdb','jpg','gif','png','bmp','jpeg','rar','zip','swf','flv');
		$ext=fileext($file);
		if($type == 'png'){
			if($ext == 'zip' || $ext == 'rar')
				$ext='rar';
			elseif($ext == 'doc' || $ext == 'docx')
				$ext='doc';
			elseif($ext == 'xls' || $ext == 'xlsx')
				$ext='xls';
			elseif($ext == 'ppt' || $ext == 'pptx')
				$ext='ppt';
			elseif($ext == 'flv' || $ext == 'swf' || $ext == 'rm' || $ext == 'rmvb')
				$ext='flv';
			else
				$ext='do';
		}
		return STATIC_URL . 'common/images/ext/' . (in_array($ext, $ext_arr) ? $ext : 'blank') . '.' . $type;
	}
	
	// 判断是否为图片
	private function is_image($file){
		$ext=fileext($file);
		return in_array($ext, $this->imgext);
	}
}

?>