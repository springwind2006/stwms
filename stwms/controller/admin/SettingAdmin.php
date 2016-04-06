<?php
defined('IN_MYCMS') or exit('No permission resources.');
class SettingAdmin extends AdminController{

	public function __construct(){
		parent::__construct();
		$this->xmlDbName='setting';
	}

	public function web(){
		if(isset($_GET['type']) && $_GET['type'] == 'upload'){
			// 水印图片上传
			$ext=fileext($_FILES['Filedata']['name']);
			$filename='mark.' . $ext;
			$filepath=UPLOAD_PATH . 'watermark' . CD . $filename;
			$attachment_setting=getcache('setting', 'setting', 'array', 'attachment');
			$types='|' . $attachment_setting['type'] . '|';
			if(stripos($types, '|' . $ext . '|') === false){
				alert('0');
			}
			if(move_uploaded_file($_FILES['Filedata']['tmp_name'], $filepath)){
				$maxWidth=intval($attachment_setting['upload_maxwidth']);
				$maxHeight=intval($attachment_setting['upload_maxheight']);
				load::cls('Image')->thumbImg($filepath, $filepath, $maxWidth, $maxHeight);
				alert($filename);
			}
			alert('0');
		}else if(isset($_GET['type']) && $_GET['type'] == 'preview'){
			// 水印预览图片
			$old_image=UPLOAD_PATH . 'watermark' . CD . 'preview.jpg';
			$new_image=UPLOAD_PATH . 'watermark' . CD . 'preview_v.jpg';
			if(is_file($new_image)){
				@unlink($new_image);
			}
			if(copy($old_image, $new_image)){
				load::cls('Image')->watermark($new_image, $new_image, $_GET['setting']);
				header('Content-type: image/jpeg');
				readfile($new_image);
				@unlink($new_image);
			}else{
				header('Content-type: image/jpeg');
				readfile($old_image);
			}
		}else if(isset($_GET['dosubmit'])){
			$_POST['web']=array2string($_POST['web']);
			$_POST['seo']=array2string($_POST['seo']);
			$_POST['attachment']=array2string($_POST['attachment']);
			$this->settings('web', $_POST['web']);
			$this->settings('seo', $_POST['seo']);
			$this->settings('attachment', $_POST['attachment']);
			$this->_fresh();
			alert('保存成功!');
		}else{
			$web=getcache('setting', 'setting', 'array', 'web');
			$seo=getcache('setting', 'setting', 'array', 'seo');
			$attachment=getcache('setting', 'setting', 'array', 'attachment');
			$gdnotsupport=!function_exists('imagepng') && !function_exists('imagejpeg') && !function_exists('imagegif');
			include template('web', 'setting');
		}
	}

	public function base(){
		if(isset($_GET['test_mail'])){
			load::func('mail');
			$toemail=$_POST['mail']['to'];
			if(empty($toemail) || !strpos($toemail, '@')){
				alert('测试邮箱地址错误！');
			}
			$from=$_POST['mail']['from'];
			$subject='发送测试主题';
			$message='邮件发送测试内容';
			$res=send_mail($toemail, $subject, $message, $from, $_POST['mail']);
			alert($res ? '1' : '发送失败！请检查配置！');
		}
		if(isset($_GET['dosubmit'])){
			$_POST['mail']=array2string($_POST['mail']);
			$_POST['connect']=array2string($_POST['connect']);
			$this->settings('mail', $_POST['mail']);
			$this->settings('connect', $_POST['connect']);
			$allowKeys=array('template','static','category_ajax','gzip','attachment_stat','html_root');
			$this->set_config($_POST['system'], 'system', $allowKeys);
			$this->_fresh();
			alert('保存成功！');
		}else{
			$mail=getcache('setting', 'setting', 'array', 'mail');
			$connect=getcache('setting', 'setting', 'array', 'connect');
			$system=load::cfg('system');
			chdir(CORE_PATH . 'template' . CD . DEFAULT_MODULE . CD);
			$styles=glob('*', GLOB_ONLYDIR);
			include template('base', 'setting');
		}
	}

	public function core(){
		if(isset($_GET['dbtype'])){
			alert(load::cfg('database', $_GET['dbtype']));
		}
		
		if(isset($_GET['test_connect'])){
			alert(is_object(load::db($_GET['database'])) ? '1' : '0');
		}
		
		if(isset($_GET['dosubmit'])){
			$adminAllowKeys=array('url','ini');
			$systemAllowKeys=array('style','admin_log','plugin_sessions','db_conn','errorlog','errorlog_size','maxloginfailedtimes','minrefreshtime');
			
			$_POST['admin']['url']=preg_replace('/[\/\/\s]/','',$_POST['admin']['url']);
			
			if($plugin_sessions=array_diff(preg_split("/[^\w]+/", trim($_POST['system']['plugin_sessions'])), array('userid','roleid','iscreator'))){
				$_POST['system']['plugin_sessions']=implode(',', $plugin_sessions);
			}else{
				unset($_POST['system']['plugin_sessions']);
			}
			
			$this->set_config($_POST['admin'], 'admin', $adminAllowKeys);
			$this->set_config($_POST['system'], 'system', $systemAllowKeys);
			
			if(isset($_POST['database']['pconnect'])){
				$_POST['database']['pconnect']=(boolean)$_POST['database']['pconnect'];
			}
			$databases=load::cfg('database');
			$database=$databases[$_POST['system']['db_conn']];
			foreach($database as $ky=>$vl){
				if(array_key_exists($ky, $_POST['database'])){
					$database[$ky]=$_POST['database'][$ky];
				}
			}
			$databases[$_POST['system']['db_conn']]=$database;
			
			$data="<?php\nreturn " . var_export($databases, true) . ";\n?>";
			
			if(load::cfg('system', 'lock_ex')){
				file_put_contents(CORE_PATH . 'config' . CD . 'database.cfg.php', $data, LOCK_EX);
			}else{
				file_put_contents(CORE_PATH . 'config' . CD . 'database.cfg.php', $data);
			}
			alert('保存成功!');
		}else{
			$admin=load::cfg('admin');
			$system=load::cfg('system');
			$databases=load::cfg('database');
			$database=$databases[$system['db_conn']];
			
			$isServerDb=stripos($database['type'], 'mysql') !== false || stripos($database['type'], 'mssql') !== false;
			
			load::db(0);
			$sysdbs=Db::check();
			$supportdbs=array_keys($databases);
			foreach($supportdbs as $k=>$v){
				if($v == 'xml' || !in_array($databases[$v]['type'], $sysdbs)){
					unset($supportdbs[$k]);
				}
			}
			
			chdir(CORE_PATH . 'template' . CD . 'admin' . CD);
			$styles=glob('*', GLOB_ONLYDIR);
			include template('core', 'setting');
		}
	}

	/* * ****内部接口方法**** */
	public function _fresh($key=''){
		if(empty($key)){
			$infos=$this->getXmlDb()->select('setting', '*');
		}else{
			$infos=$this->getXmlDb()->select('setting', '*', 'where `key`=\'' . $key . '\'');
		}
		foreach($infos as $info){
			setcache('setting_' . $info['key'], string2array($info['value']), 'setting', 'array');
		}
	}

	/* * ****私有方法**** */
	/*
	 * array('js_path','css_path','img_path','attachment_stat','admin_log','gzip',
	 * 'errorlog','phpsso','phpsso_appid','phpsso_api_url','phpsso_auth_key',
	 * 'phpsso_version','connect_enable', 'upload_url','sina_akey', 'sina_skey',
	 * 'snda_enable', 'snda_status', 'snda_akey', 'snda_skey', 'qq_akey', 'qq_skey',
	 * 'qq_appid','qq_appkey','qq_callback','admin_url')
	 */
	private function set_config($config,$filename='system',&$allowKeys=array()){
		$configfile=CORE_PATH . 'config' . CD . $filename . '.cfg.php';
		if(!is_writable($configfile)){
			return 0;
		}
		$pattern=$replacement=array();
		foreach($config as $k=>$v){
			if(in_array($k, $allowKeys)){
				$v=safe_replace(trim($v));
				$pattern[$k]="/'" . $k . "'\s*=>\s*([']?)[^']*([']?)(\s*),/is";
				$replacement[$k]="'" . $k . "' => \${1}" . $v . "\${2}\${3},";
			}
		}
		$str=file_get_contents($configfile);
		$str=preg_replace($pattern, $replacement, $str);
		return load::cfg('system', 'lock_ex') ? file_put_contents($configfile, $str, LOCK_EX) : file_put_contents($configfile, $str);
	}

	private function settings($key,$value=NULL){
		$dbfile=CORE_PATH . 'data' . CD . 'core' . CD . 'setting.php';
		if(is_null($value)){
			$cRes=$this->getXmlDb()->getOne('setting', '*', 'where `key`=\'' . $key . '\'');
			return string2array($cRes['value']);
		}else{
			if(!$this->getXmlDb()->hasFdVl('setting', 'key', $key)){
				return $this->getXmlDb()->insert('setting', array('key' => $key,'value' => $value));
			}else{
				return $this->getXmlDb()->update('setting', array('key' => $key,'value' => $value), 'where `key`=\'' . $key . '\'');
			}
		}
	}
}

?>