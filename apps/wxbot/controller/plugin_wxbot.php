<?php
class plugin_wxbot extends Controller{
	public $id, $url, $path, $db, $pageType,$config;

	public function __construct($config){
		// 获取插件信息
		$this->config=$config;
		$this->pageType='page';
		$this->id=$config['id'];
		$this->url=STATIC_URL . 'plugin/' . $config['install_dir'] . '/';
		$this->path=CORE_PATH . 'plugin' . CD . $config['install_dir'] . CD;
	}
	
	public function convert(){
		$data=array('status'=>0,'info'=>'没有上传文件！');
		$file=isset($_GET['file'])?$_GET['file']:(isset($_POST['file'])?$_POST['file']:'upfile');
		if(isset($_FILES[$file]['tmp_name'])){
			$static_path=STATIC_PATH.'plugin' . CD . $this->config['install_dir'] . CD .'datas'. CD;
			!is_dir($static_path) && mkdir($static_path,0777,true);
			$filename=date('YmdHis').rand(100, 999);
			$fileext=fileext($_FILES[$file]['name']);
			$oldfile=$static_path.$filename.'.'.$fileext;
			$newfile=$static_path.$filename.'.mp3';
			$fileurl=rtrim(SITE_URL,'/').$this->url.'datas/'.$filename.'.mp3';
			$data['info']='上传失败！';
			$data['name']=$_FILES[$file]['name'];
			if(move_uploaded_file($_FILES[$file]['tmp_name'], $oldfile)){
				$command='ffmpeg -i '.$oldfile.' '.$newfile;
				exec($command);
				if(is_file($newfile)){
					unlink($oldfile);
					$data['status']=1;
					$data['info']='转换成功！';
					$data['url']=$fileurl;
				}else{
					$data['info']='转换失败！';
				}
			}
		}
		$this->ajaxReturn($data);
	}
	
	// 插件管理方法，需要管理权限访问
	public function m_init(){
		$setting=$this->wxbot_config();
		@extract($setting);
		include template('init', 'admin');
	}

	public function m_do(){
		switch($_GET['type']){
			case 'start':
				$this->wxbot_config('isAuthLogin',true);
				echo  $this->wxbot_config('isAuthLogin')?'1':'0';
			break;
			case 'checkqrurl':
				$authLoginURL=$this->wxbot_config('authLoginURL');
				echo $authLoginURL?$authLoginURL:'0';
			break;
			case 'checklogin':
				$isLogin=$this->wxbot_config('isLogin');
				echo $isLogin?'1':'0';
			break;
			case 'setting':
				$settings=array(
					'dealContactMsg',
					'dealGroupMsg',
					'dealSignIn',
					'saveGroupImg',
					'saveGroupAudio',
					'savePubArticle',
				);
				$name=$_GET['name'];
				$value=$_GET['value']?true:false;
				in_array($name, $settings) && $this->wxbot_config($name,$value);
				echo $this->wxbot_config($name)?'1':'0';
			break;
		}
	}
	
	private function wxbot_config($key=null,$value=null){
		static $redis=null;
		if(is_null($redis)){
			$redis = new Redis();
			$redis->connect('127.0.0.1', 6379);
		}
	
		$setting=$redis->get('wxbot_setting');
		$setting=!is_null($setting) ? json_decode($setting,true) : array();
		
		if(is_null($key) && is_null($value)){
			return $setting;
		}elseif(!is_null($key) && is_null($value)){
			return $setting[$key];
		}elseif(!is_null($key) && !is_null($value)){
			$setting[$key]=$value;
			return $redis->set('wxbot_setting',json_encode($setting));
		}
	}
	
}
?>