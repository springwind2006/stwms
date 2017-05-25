<?php
load::func('dir');
class Uploadfile{
	var $model;
	var $catid;
	var $attachments;
	var $field;
	var $userid=0;
	var $imageexts=array('gif','jpg','jpeg','png','bmp');
	var $uploadedfiles=array();
	var $downloadedfiles=array();
	var $error;
	var $upload_root;
	var $uploadeds=0;
 // 上传的文件计数
	function __construct($model='',$catid=0,$upload_dir=''){
		$this->catid=intval($catid);
		$this->model=$model;
		$this->upload_root=UPLOAD_PATH;
		$this->upload_func='copy';
		$this->download_func='get_remote_file';
		$this->upload_dir=$upload_dir;
	}
	
	function save($field,$alowexts='',$maxsize=0,$overwrite=0,$attachment_setting=array()){
		if(!isset($_POST[$field]) || strpos($_POST[$field], 'data:image/')!==0){
			$this->error=UPLOAD_ERR_OK;
			return false;
		}
		
		// 获取上传的文件类型
		$spos=strpos($_POST[$field], '/')+1;
		$epos=strpos($_POST[$field], ';');
		$fileext=strtolower(substr($_POST[$field], $spos,$epos-$spos));
		
		// 获取允许上传的文件类型
		if(empty($alowexts) || $alowexts == ''){
			$alowexts='zip|rar|jpeg|jpg|gif|png|bmp';
		}
		
		$fn=isset($_GET['CKEditorFuncNum']) && $_GET['CKEditorFuncNum'] ? $_GET['CKEditorFuncNum'] : '1';
		$this->field=$field;
		$this->savepath=$this->upload_root . $this->upload_dir . date('Y/md/');
		$this->alowexts=$alowexts;
		$this->maxsize=$maxsize;
		$this->overwrite=$overwrite;
		
		//文件类型检查
		if(!preg_match("/^(" . $this->alowexts . ")$/", $fileext)){
			$this->error='10';
			return false;
		}
		// 权限判断
		if(!dir_create($this->savepath)){
			$this->error='8';
			return false;
		}
		if(!is_dir($this->savepath)){
			$this->error='8';
			return false;
		}
		@chmod($this->savepath, 0777);
		if(!is_writeable($this->savepath)){
			$this->error='9';
			return false;
		}
		
		$temp_filename=$this->getname($fileext); // 获取上传文件名
		$savefile=$this->savepath . $temp_filename;
		$filepath=preg_replace(addslashes('|^' . $this->upload_root . '|'),'', $savefile);
		
		if(!$this->overwrite && is_file($savefile))
			false;

		$data=base64_decode(substr($_POST[$field],strpos($_POST[$field], ';base64,')+8));
		$filesize=strlen($data);
		//大小判断
		if($this->maxsize && $filesize > $this->maxsize){
			$this->error='11';
			return false;
		}
		$aids=array();
		//保存文件
		if(file_put_contents($savefile, $data)){
			unset($data);
			
			$this->uploadeds++;
			@chmod($savefile, 0644);
			
			if($this->check_img($savefile)){
				// 调整图片尺寸
				$thumb_enable=($attachment_setting[0] > 0 || $attachment_setting[1] > 0) ? 1 : 0;
				if($thumb_enable || $attachment_setting[3]){
					$image=load::cls('Image', 1, 1);
				}
				if($thumb_enable){
					$image->thumbImg($savefile, $savefile, $attachment_setting[0], $attachment_setting[1], $attachment_setting[2]);
				}
				// 为图片添加水印
				if($attachment_setting[3]){
					$image->watermark($savefile);
				}
			}
			
			// 对上传的文件进行md5值校验，如果已经存在，则删除已经上传的文件
			if($attachment_setting[4] && ($resuilts=$this->check_md5($savefile, $md5ID))){
				$aids[]=$resuilts['id'];
				@unlink($savefile);
			}else{
				$uploadedfile=array('filename' => 'base64','filepath' => $filepath,'filesize' => $filesize,'fileext' => $fileext,'md5' => $md5ID);
				$aids[]=$this->add($uploadedfile);
			}
		}
		return $aids;
	}
	
	/**
	 * 功能：附件上传方法 参数：( $field 上传字段, $alowexts 允许上传类型, $maxsize 最大上传大小, $overwrite 是否覆盖原有文件, $attachment_setting 附件设置, ) 说明： $attachment_setting参数 (缩略图宽度,缩略图高度，剪裁类型，是否水印，是否md5值校验)
	 */
	function upload($field,$alowexts='',$maxsize=0,$overwrite=0,$attachment_setting=array()){
		if(!isset($_FILES[$field])){
			$this->error=UPLOAD_ERR_OK;
			return false;
		}
		
		// 获取允许上传的文件类型
		if(empty($alowexts) || $alowexts == ''){
			$alowexts='zip|rar|jpeg|jpg|gif|png|bmp';
		}
		$fn=isset($_GET['CKEditorFuncNum']) && $_GET['CKEditorFuncNum'] ? $_GET['CKEditorFuncNum'] : '1';
		$this->field=$field;
		$this->savepath=$this->upload_root . $this->upload_dir . date('Y/md/');
		$this->alowexts=$alowexts;
		$this->maxsize=$maxsize;
		$this->overwrite=$overwrite;
		
		// 获取所有待上传的文件列表
		$uploadfiles=array();
		$description=isset($GLOBALS[$field . '_description']) ? $GLOBALS[$field . '_description'] : array();
		if(is_array($_FILES[$field]['error'])){
			$this->uploads=count($_FILES[$field]['error']);
			foreach($_FILES[$field]['error'] as $key=>$error){
				if($error === UPLOAD_ERR_NO_FILE)
					continue;
				if($error !== UPLOAD_ERR_OK){
					$this->error=$error;
					return false;
				}
				$uploadfiles[$key]=array('tmp_name' => $_FILES[$field]['tmp_name'][$key],'name' => $_FILES[$field]['name'][$key],'type' => $_FILES[$field]['type'][$key],'size' => $_FILES[$field]['size'][$key],'error' => $_FILES[$field]['error'][$key],'description' => $description[$key],'fn' => $fn);
			}
		}else{
			$this->uploads=1;
			if(!$description)
				$description='';
			$uploadfiles[0]=array('tmp_name' => $_FILES[$field]['tmp_name'],'name' => $_FILES[$field]['name'],'type' => $_FILES[$field]['type'],'size' => $_FILES[$field]['size'],'error' => $_FILES[$field]['error'],'description' => $description,'fn' => $fn);
		}
		
		// 权限判断
		if(!dir_create($this->savepath)){
			$this->error='8';
			return false;
		}
		if(!is_dir($this->savepath)){
			$this->error='8';
			return false;
		}
		@chmod($this->savepath, 0777);
		if(!is_writeable($this->savepath)){
			$this->error='9';
			return false;
		}
		
		// 开始上传文件
		$aids=array(); // 保存已经上传的文件列表，便于保存至数据库
		foreach($uploadfiles as $k=>$file){
			$fileext=fileext($file['name']);
			if($file['error'] != 0){
				$this->error=$file['error'];
				return false;
			}
			if(!preg_match("/^(" . $this->alowexts . ")$/", $fileext)){
				$this->error='10';
				return false;
			}
			if($this->maxsize && $file['size'] > $this->maxsize){
				$this->error='11';
				return false;
			}
			if(!$this->isuploadedfile($file['tmp_name'])){
				$this->error='12';
				return false;
			}
			
			$temp_filename=$this->getname($fileext); // 获取上传文件名
			$savefile=$this->savepath . $temp_filename;
			$savefile=preg_replace("/(php|phtml|php3|php4|jsp|exe|dll|asp|cer|asa|shtml|shtm|aspx|asax|cgi|fcgi|pl)(\.|$)/i", "_\\1\\2", $savefile);
			$filepath=preg_replace(addslashes("|^" . $this->upload_root . "|"), "", $savefile);
			if(!$this->overwrite && is_file($savefile))
				continue;
			$upload_func=$this->upload_func;
			
			// 文件开始上传
			if(@$upload_func($file['tmp_name'], $savefile)){
				$this->uploadeds++;
				@chmod($savefile, 0644);
				@unlink($file['tmp_name']);
				
				if($this->check_img($savefile)){
					// 调整图片尺寸
					$thumb_enable=($attachment_setting[0] > 0 || $attachment_setting[1] > 0) ? 1 : 0;
					if($thumb_enable || $attachment_setting[3]){
						$image=load::cls('Image', 1, 1);
					}
					if($thumb_enable){
						$image->thumbImg($savefile, $savefile, $attachment_setting[0], $attachment_setting[1], $attachment_setting[2]);
					}
					// 为图片添加水印
					if($attachment_setting[3]){
						$image->watermark($savefile);
					}
				}
				
				// 对上传的文件进行md5值校验，如果已经存在，则删除已经上传的文件
				if($attachment_setting[4] && ($resuilts=$this->check_md5($savefile, $md5ID))){
					$aids[]=$resuilts['id'];
					@unlink($savefile);
				}else{
					$uploadedfile=array('filename' => $file['name'],'filepath' => $filepath,'filesize' => $file['size'],'fileext' => $fileext,'md5' => $md5ID);
					$aids[]=$this->add($uploadedfile);
				}
			}
		}
		return $aids;
	}

	/**
	 * 功能：附件下载 参数：( $field 预留字段, $value 传入下载内容, $attachment_setting 附件设置, $ext 下载扩展名, $absurl 绝对路径, $basehref ) 说明： $attachment_setting参数 (缩略图宽度,缩略图高度，剪裁类型，是否水印，是否md5值校验)
	 */
	function download($field,$value,$attachment_setting=array(),$ext='gif|jpg|jpeg|bmp|png',$absurl='',$basehref=''){
		$this->field=$field;
		$dir=date('Y/md/');
		$uploadpath=UPLOAD_URL . $dir;
		$uploaddir=$this->upload_root . $dir;
		$string=slashes($value, 0);
		
		if(empty($attachment_setting)){
			$system_setting=getcache('setting', 'setting', 'array', 'attachment');
			$attachment_setting=array(intval($system_setting['upload_maxwidth']),intval($system_setting['upload_maxheight']),0,0,intval($system_setting['md5_check']));
			unset($system_setting);
		}
		
		dir_create($uploaddir);
		$remotefileurls=array();
		
		$allMatches=array();
		// 对特定文件和图片类型匹配
		if(preg_match_all("/(href|src)=([\"|']?)([^ \"'>]+\.($ext))\\2/i", $string, $matches)){
			$allMatches=array_merge($allMatches, $matches[3]);
			unset($matches);
		}
		
		if(preg_match_all('/<img[^>]+src=[\'"]([^>\'"]+)[\'"][^>]*>/i', $string, $matches)){
			$allMatches=array_merge($allMatches, $matches[1]);
			unset($matches);
		}
		
		unset($string);
		$allMatches=array_unique($allMatches);
		
		foreach($allMatches as $matche){
			if(strpos($matche, '://') === false)
				continue;
			$remotefileurls[$matche]=$this->fillurl($matche, $absurl, $basehref);
		}
		unset($allMatches);
		
		$remotefileurls=array_unique($remotefileurls);
		$oldpath=$newpath=array();
		foreach($remotefileurls as $k=>$file){
			if(strpos($file, '://') === false || strpos($file, UPLOAD_URL) !== false)
				continue;
			$filename=fileext($file);
			$filename=$this->getname($filename);
			
			$savefile=$uploaddir . $filename;
			$download_func=$this->download_func;
			if($download_func($file, $savefile)){
				$oldpath[]=$k;
				@chmod($savefile, 0777);
				$fileext=fileext($filename);
				
				$thumb_enable=($attachment_setting[0] > 0 || $attachment_setting[1] > 0) ? 1 : 0;
				if($thumb_enable && $this->check_img($savefile)){
					$image=load::cls('Image', 1, 1);
					$image->thumbImg($savefile, $savefile, $attachment_setting[0], $attachment_setting[1], $attachment_setting[2]);
				}
				
				$filepath=$dir . $filename;
				if($attachment_setting[4] && ($resuilts=$this->check_md5($savefile, $md5ID, 'download'))){
					$aids[]=$resuilts['id'];
					$GLOBALS['downloadfiles'][]=$newpath[]=UPLOAD_URL . $resuilts['filepath'];
					@unlink($savefile);
				}else{
					$GLOBALS['downloadfiles'][]=$newpath[]=$uploadpath . $filename;
					$downloadedfile=array('filename' => $filename,'filepath' => $filepath,'filesize' => filesize($savefile),'fileext' => $fileext,'md5' => $md5ID);
					$aid=$this->add($downloadedfile);
					$this->downloadedfiles[$aid]=$filepath;
				}
			}
		}
		return str_replace($oldpath, $newpath, $value);
	}

	/**
	 * 功能：附件删除方法 参数：($where 删除sql语句)
	 */
	function delete($where){
		$result=$this->getDb()->select('attachments', 'id,filepath', $where);
		foreach($result as $r){
			$image=$this->upload_root . $r['filepath'];
			@unlink($image);
			$thumbs=glob(dirname($image) . '/*' . basename($image));
			if($thumbs){
				foreach($thumbs as $thumb)
					@unlink($thumb);
			}
		}
		return $this->getDb()->delete('attachments', $where);
	}

	/**
	 * 功能：附件添加如数据库 参数：($uploadedfile 附件信息)
	 */
	function add($uploadedfile){
		$uploadedfile['model']=$this->model;
		$uploadedfile['catid']=$this->catid;
		$uploadedfile['userid']=$this->userid;
		$uploadedfile['uploadtime']=NOW_TIME;
		$uploadedfile['uploadip']=getIP();
		$uploadedfile['status']=load::cfg('system', 'attachment_stat') ? 0 : 1;
		$uploadedfile['filename']=strlen($uploadedfile['filename']) > 49 ? $this->getname($uploadedfile['fileext']) : $uploadedfile['filename'];
		$uploadedfile['isimage']=in_array($uploadedfile['fileext'], $this->imageexts) ? 1 : 0;
		
		$this->getDb()->insert('attachments', $uploadedfile);
		$insertId=$this->getDb()->lastInsert('attachments', 'id');
		$uploadedfile['isupload']=1;
		$this->uploadedfiles[]=$uploadedfile;
		return $insertId;
	}

	/**
	 * 功能：根据提供的md5值检测是否上传的文件已经存在 参数：($filename 文件名称)
	 */
	function check_md5($filename,&$md5str,$type='upload'){
		$md5str=md5_file($filename);
		$info=$this->getDb()->getOne('attachments', '*', 'where `md5`=\'' . $md5str . '\'');
		if(!empty($info)){
			if($type == 'upload'){
				$info['isupload']=0;
				$this->uploadedfiles[]=$info;
			}
		}
		return $info;
	}

	function check_img($image){
		return extension_loaded('gd') && preg_match("/\.(jpg|jpeg|gif|png)/i", $image, $m) && (strpos($image, '://') || is_file($image)) && function_exists('imagecreatefrom' . ($m[1] == 'jpg' ? 'jpeg' : $m[1]));
	}

	function set_userid($userid){
		$this->userid=$userid;
	}

	/**
	 * 功能：获取附件名称 参数：($fileext 附件扩展名)
	 */
	function getname($fileext){
		return (get_mtime() * 10000) . rand(100, 999) . '.' . $fileext;
	}

	/**
	 * 功能：判断文件是否是通过 HTTP POST 上传的 参数：(string $file 文件地址) 返回：bool 所给出的文件是通过 HTTP POST 上传的则返回 TRUE
	 */
	function isuploadedfile($file){
		return is_uploaded_file($file) || is_uploaded_file(str_replace('\\\\', '\\', $file));
	}

	/**
	 * 功能：补全网址 参数：(string $surl 源地址, string $absurl 相对地址, string $basehref 网址) 返回：string 网址
	 */
	function fillurl($surl,$absurl,$basehref=''){
		if($basehref != ''){
			$preurl=strtolower(substr($surl, 0, 6));
			if($preurl == 'http://' || $preurl == 'ftp://' || $preurl == 'mms://' || $preurl == 'rtsp://' || $preurl == 'thunde' || $preurl == 'emule://' || $preurl == 'ed2k://')
				return $surl;
			else
				return $basehref . '/' . $surl;
		}
		$i=0;
		$dstr='';
		$pstr='';
		$okurl='';
		$pathStep=0;
		$surl=trim($surl);
		if($surl == '')
			return '';
		$urls=@parse_url(SITE_URL);
		$HomeUrl=$urls['host'];
		$BaseUrlPath=$HomeUrl . $urls['path'];
		$BaseUrlPath=preg_replace("/\/([^\/]*)\.(.*)$/", '/', $BaseUrlPath);
		$BaseUrlPath=preg_replace("/\/$/", '', $BaseUrlPath);
		$pos=strpos($surl, '#');
		if($pos > 0)
			$surl=substr($surl, 0, $pos);
		if($surl[0] == '/'){
			$okurl='http://' . $HomeUrl . '/' . $surl;
		}elseif($surl[0] == '.'){
			if(strlen($surl) <= 2)
				return '';
			elseif($surl[0] == '/'){
				$okurl='http://' . $BaseUrlPath . '/' . substr($surl, 2, strlen($surl) - 2);
			}else{
				$urls=explode('/', $surl);
				foreach($urls as $u){
					if($u == "..")
						$pathStep++;
					else if($i < count($urls) - 1)
						$dstr.=$urls[$i] . '/';
					else
						$dstr.=$urls[$i];
					$i++;
				}
				$urls=explode('/', $BaseUrlPath);
				if(count($urls) <= $pathStep)
					return '';
				else{
					$pstr='http://';
					for($i=0; $i < count($urls) - $pathStep; $i++){
						$pstr.=$urls[$i] . '/';
					}
					$okurl=$pstr . $dstr;
				}
			}
		}else{
			$preurl=strtolower(substr($surl, 0, 6));
			if(strlen($surl) < 7)
				$okurl='http://' . $BaseUrlPath . '/' . $surl;
			elseif($preurl == "http:/" || $preurl == 'ftp://' || $preurl == 'mms://' || $preurl == "rtsp://" || $preurl == 'thunde' || $preurl == 'emule:' || $preurl == 'ed2k:/')
				$okurl=$surl;
			else
				$okurl='http://' . $BaseUrlPath . '/' . $surl;
		}
		$preurl=strtolower(substr($okurl, 0, 6));
		if($preurl == 'ftp://' || $preurl == 'mms://' || $preurl == 'rtsp://' || $preurl == 'thunde' || $preurl == 'emule:' || $preurl == 'ed2k:/'){
			return $okurl;
		}else{
			$okurl=preg_replace('/^(http:\/\/)/i', '', $okurl);
			$okurl=preg_replace('/\/{1,}/i', '/', $okurl);
			return 'http://' . $okurl;
		}
	}

	/**
	 * 功能：返回错误信息
	 */
	function error(){
		$UPLOAD_ERROR=array(
				0 => '文件上传成功',
				1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
				2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
				3 => '文件只有部分被上传',
				4 => '没有文件被上传',
				5 => '',
				6 => '找不到临时文件夹。',
				7 => '文件写入临时文件夹失败',
				8 => '附件目录创建不成功',
				9 => '附件目录没有写入权限',
				10 => '不允许上传该类型文件',
				11 => '文件超过了管理员限定的大小',
				12 => '非法上传文件',
				13 => '24小时内上传附件个数超出了系统限制');
		return $UPLOAD_ERROR[$this->error];
	}

	/**
	 * 功能：ck编辑器返回 参数：($fn , $fileurl 路径, $message 显示信息)
	 */
	function mkhtml($fn,$fileurl,$message){
		$str='<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(' . $fn . ', \'' . $fileurl . '\', \'' . $message . '\');</script>';
		exit($str);
	}

	/**
	 * 功能：获取数据库配置
	 */
	private function getDb(){ // 获取系统数据库
		if(is_null($this->db)){
			$this->db=load::db();
		}
		return $this->db;
	}
}
?>