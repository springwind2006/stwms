<?php
/***
 * 微信平台管理
 * */
class plugin_houserent extends Controller{
	public $id, $url, $path, $db, $config,
			$pageType,$svc,$wxpub,$wxpub_svc;

	public function __construct($config){
		// 获取插件信息
		$this->pageType='page';
		$this->id=$config['id'];
		$this->url=STATIC_URL . 'plugin/' . $config['install_dir'] . '/';
		$this->path=CORE_PATH . 'plugin' . CD . $config['install_dir'] . CD;
		$config['url']=$this->url;
		$config['path']=$this->path;
		include_once $this->path.'service/HouseRentSvc.class.php';
		$this->svc=new HouseRentSvc($config);
		$this->config=$config;
		$this->assign('url',$this->url);
	}
	
	/********插件外部方法，无需权限访问********/
	
	// 公众号后台接口
	public function dispatch(){
		$this->getWxpubSvc()->dispatch();
	}
	
	// 用户登陆授权
	public function login_auth(){
		if(WECHAT_ACCESS){
			//公众号“深拓微助手”用户授权登陆
			session('[start]');
			if(!session('?user_info')){
				$code=$_REQUEST['code'];
				$api_config=include $this->path.'config/wxpublic1_api.cfg.php';
				$tpl_config=include $this->path.'config/wxpublic1_tpl.cfg.php';
				load::cls('WxPublic',0);
				$wxpub=new WxPublic(0,$api_config,$tpl_config);
				if(!empty($code)){
					$data=$wxpub->getAccessTokenByCode($code);
					if($data){
						$info=$this->svc->get_user_by_openid($data['openid']);
						if(!$info){
							$info=$wxpub->getUserInfo($data['openid']);
							$info['openid']=$data['openid'];
							$info['id']=$this->svc->save_user($info);
						}
						session('user_info',$info);
					}
				}else{
					$return_url='/api/dispatch.php?redirect='.sys_crypt(U(':login_auth@www.ytzfgj.com',$_GET));
					$redirect=U($wxpub->authUrl($return_url));
					$this->redirect($wxpub->authUrl($return_url));
				}
			}
			$this->redirect(isset($_GET['return_uri'])?$_GET['return_uri']:':user');
		}else{
			empty($_REQUEST['code']) && exit('no code');
			//小程序登录授权
			$api_config=include $this->path.'config/wxapp_api.cfg.php';
			$tpl_config=include $this->path.'config/wxapp_tpl.cfg.php';
			load::cls('WxApp',0);
			$wxapp=new WxApp(0,$api_config,$tpl_config);
			$data=$wxapp->getSessionByCode($_REQUEST['code']);
			if($data){
				session('[start]');
				$data['id']=$this->svc->save_user($data);
				session('user_info',$data);
				$return['session_id']=session('[id]');
				$this->ajaxReturn($return);
			}
		}
	}
	
	// 退出
	public function logout(){
		session('[start]');
		session('user_info',null);
		session('?user_info') ? 
			$this->error('退出失败!'):$this->success('退出成功!');
	}
	
	// 保存出租人信息
	public function renter(){
		$user_info=$this->checkLogin();
		$user_id=$user_info['id'];
		if(!IS_POST){
			$this->ajaxReturn($this->svc->get_renter($user_id));
		}else{
			$data=safe_replace($_POST);
			$data['user_id']=$user_id;
			$this->svc->save_renter($data);
			$this->success('保存出租人成功');
		}
		
	}
	
	// 保存住客信息
	public function tenant(){
		$type=Param::get_para('type','save');
		
		//开启客户端添加信息
		if(($type=='add'||$type=='upload') || IS_POST){
			if($user_info=$this->checkLogin(false)){
				$user_id=$user_info['id'];
			}else{
				$user_id=Param::get_para('user_id',0,'intval');
			}
		}else{
			$user_info=$this->checkLogin();
			$user_id=$user_info['id'];
		}
		
		//防止客户端非登陆保存，非登陆模式只能添加
		if(IS_POST && empty($user_info)){
			$_POST['id']=0;
		}
		
		if($type=='upload'){
			$res=$this->svc->upload_id_image($user_id);
			$this->ajaxReturn($res);
		}elseif($type=='list'){
			$page=intval(Param::get_para('page',1));
			$field='id,name,id_no';
			$data=$this->svc->get_tenant_list($page,$user_id,$field);
			$return=array('count'=>0,'page'=>$page);
			if(!empty($data)){
				$return['count']=count($data);
				$return['data']=$data;
			}
			$this->ajaxReturn($return);
		}elseif($type=='show'){
			$id=intval($_REQUEST['id']);
			$data=$this->svc->get_tenant($id,$user_id);
			$data['come_date']=date('Y-m-d',$data['come_date']);
			$data['left_date']=date('Y-m-d',$data['left_date']);
			$this->ajaxReturn($data);
		}elseif($type=='add'){
			$auths='chooseImage,uploadImage,openLocation,getLocation,chooseLocation';
			$this->setWechat($auths);
			$this->assign('title','入住∙退房登记');
			$this->assign('action_url',U(':tenant?type=save'));
			$this->display('tenant_add');
		}elseif(IS_POST){
			$data=safe_replace($_POST);
			$data['user_id']=$user_id;
			$this->svc->save_tenant($data);
			$this->success('保存住客成功');
		}
	}
	
	// 保存更新用户信息
	public function user(){
		$user_info=$this->checkLogin();
		$user_id=$user_info['id'];
		$type=Param::get_para('type','index');
		$openids=include $this->path.'config/auth_user.cfg.php';
		$is_authed=in_array($user_info['openid'], $openids);
		$this->assign('is_authed',$is_authed);
		if($type=='index'){
			$this->assign('user_info',$this->svc->get_user($user_id,$is_authed));
			$this->setWechat('hideOptionMenu,closeWindow',1);
			$this->assign('title','个人中心');
			$this->display('user_index');
		}elseif($type=='tenant'){
			$id=Param::get_para('id',0,'intval');
			if(!$id){//获取所有住户信息
				$page=Param::get_para('page',1,'intval');
				$uid=$is_authed?0:$user_id;
				$pagesize=20;
				$tenants=$this->svc->get_tenant_list($page,$uid,'*',$pagesize);
				$this->assign('lists',$tenants);
				if(IS_AJAX){
					$data['page']=$page;
					$data['html']=!empty($tenants)?$this->fetch('user_tenant_list'):'';
					$this->ajaxReturn($data);
				}else{
					$this->assign('title','租客信息管理');
					$this->setWechat('hideOptionMenu,closeWindow',1);
					$this->display('user_tenant');
				}
			}else{
				if(!IS_POST){
					$uid=$is_authed?0:$user_id;
					$tenant=$this->svc->get_tenant($id,$uid);
					$this->assign('vo',$tenant);
					$auths='chooseImage,uploadImage,openLocation,getLocation,chooseLocation';
					$this->setWechat($auths,1);
					$this->assign('title','租客信息查看');
					$this->assign('action_url',U(':user?type=tenant&id='.$id));
					$this->display('tenant_add');
				}else{
					$data=safe_replace($_POST);
					if($is_authed){
						unset($data['user_id']);
					}
					$this->svc->save_tenant($data,$is_authed);
					$jumpUrl=U(':user?type=tenant');
					$data['noreset']=1;
					$this->success("保存成功",$jumpUrl,$data);
				}
			}
		}elseif($type=='alarm'){
			$page=Param::get_para('page',1,'intval');
			$uid=$is_authed?0:$user_id;
			$pagesize=20;
			$alarms=$this->svc->get_alarm_list($page,$pagesize);
			$this->assign('lists',$alarms);
			if(IS_AJAX){
				$data['page']=$page;
				$data['html']=!empty($alarms)?$this->fetch('user_alarm_list'):'';
				$this->ajaxReturn($data);
			}else{
				$this->assign('title','报警信息查看');
				$this->setWechat('hideOptionMenu,closeWindow',1);
				$this->display('user_alarm');
			}
		}elseif($type=='info'){
			$this->ajaxReturn($this->svc->get_user($user_id));
		}elseif(IS_POST){
			$data=array_change_key_case(safe_replace($_POST));
			$data['sex']=$data['gender'];
			$data['headimgurl']=$data['avatarurl'];
			$data['catid']=$user_info['catid'];
			$data['openid']=$user_info['openid'];
			$this->svc->save_user($data);
			$this->success('更新成功');
		}
	}
	
	public function test(){
		switch ($_GET['type']){
			case 'auth':
				echo $this->wxapp->getAuth();
				break;
			case 'token':
				echo $this->wxapp->getAccessToken();
				break;
			case 'test':
				var_dump(WECHAT_ACCESS);
				//echo (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				break;
			case 'msg':
				load::cls('WxPublic',0);
				$api_config=include $this->path.'config/wxpublic1_api.cfg.php';
				$tpl_config=include $this->path.'config/wxpublic1_tpl.cfg.php';
				$alarm=new WxPublic(0,$api_config,$tpl_config);
				$openids=array(
						'owN5V0Xw_ic0z7D3Hw_gLR0yepOM'  //本人
				);
				$remark='详情进入管理后台查看';
				$datas=array(
						'first'=>array('value'=>'您收到报警信息！','color'=>'#ff0000'),
						'keyword1'=>date('Y-m-d H:i:s'),
						'keyword2'=>'北京海淀',
						'keyword3'=>'测试',
						'remark'=>$remark
				);
				$return_url='/api/dispatch.php?plugin=houserent/login_auth';
				$url=$alarm->authUrl($return_url);
				foreach($openids as $openid){
					var_dump($alarm->sendTplMsg('report', $openid, $datas,$url));
				}
				break;
		}
	}
	
	/********插件管理方法，需要后台管理权限访问********/
	
	//首页
	public function m_init(){
		$filename=$this->path.'config/alarm_keyword.cfg.php';
		$configs=include $filename;
		$default=array(
				'nation'=>array(
					'name'=>'民族',
					'condition'=>'equal',
					'keyword'=>'1',
				)
			);
		unset($configs['nation']);
		if(IS_POST){
			$datas=is_array($_POST['alarm'])?$_POST['alarm']:array();
			foreach($datas as $k => $v){
				if(trim($v['keyword'])===''){
					unset($datas[$k]);
				}
			}
			$datas=array_merge($datas,$default);
			$res=file_put_contents($filename,"<?php \r\n return ".var_export($datas,true).';');
			$jumpUrl=plugin_url('houserent','init','t='.time());
			$res ? $this->success('保存成功！',$jumpUrl):$this->success('保存失败！');
		}else{
			$conditions=array(
					'contain'=>'包含',
					'start'=>'开始',
					'end'=>'结尾',
					'equal'=>'等于'
			);
			$alarm_fields=array('name','id_addr','id_no','mobile','addr_name','detail_addr');
			$all_fields=getcache('field', 'model', 'array', 'ret_tenant');
			foreach($alarm_fields as $k=>$v){
				if(!isset($configs[$v])){
					$alarm_fields[$k]=$all_fields[$v];
				}else{
					unset($alarm_fields[$k]);
				}
			}
			unset($all_fields);
			$this->assign('conditions',$conditions);
			$this->assign('configs',$configs);
			$this->assign('alarm_fields',$alarm_fields);
			$this->display();
		}
	}
	
	/********私有方法********/
	
	//登陆检查
	private function checkLogin($jumpUrl=null){
		session('[start]');
		if(APP_LOCAL_DEBUG){
			session('user_info',M('wx_user')->where('id=15')->find());
		}
		if(
			session('?user_info')
		){
			$user_id=intval(session('user_info.id'));
			$user_info=$this->svc->check_user($user_id);
			return $user_info?$user_info:session('user_info');
		}elseif(is_bool($jumpUrl)){
			return false;
		}else{
			if(WECHAT_ACCESS){
				$return_url=!empty($jumpUrl)?$jumpUrl:REQUEST_FULL_URL;
				$this->redirect(':login_auth',array('return_uri'=>$return_url));
			}else{
				$this->error('not login!',$jumpUrl);
			}
		}
	}
	
	//获取微信公众平台服务
	private function getWxpubSvc($no=''){
		if(is_null($this->wxpub_svc)){
			$api_file=$this->path.'config/wxpublic'.$no.'_api.cfg.php';
			$tpl_file=$this->path.'config/wxpublic'.$no.'_tpl.cfg.php';
			if(is_file($api_file) && is_file($tpl_file)){
				load::cls('WxPublic',0);
				include_once $this->path.'service/WxPublicSvc.class.php';
				$api_config=include $api_file;
				$tpl_config=include $tpl_file;
				$this->wxpub=new WxPublic(0,$api_config,$tpl_config);
				$this->wxpub_svc=new WxPublicSvc($this->wxpub,$this->config);
			}
		}
		return $this->wxpub_svc;
	}
	
	//微信设置
	private function setWechat($auths,$no=''){
		if(WECHAT_ACCESS){
			$this->getWxpubSvc($no);
			$weixin_js_api=$this->wxpub->getJsApiConfig(is_array($auths)?implode(',', $auths):$auths);
			$this->assign('weixin_js_api',$weixin_js_api);
		}
	}
	
}
?>