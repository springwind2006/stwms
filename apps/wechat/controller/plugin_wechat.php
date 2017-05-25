<?php
/***
 * 微信平台管理
 * 蓝牙地址：98:5D:AD:20:EF:62
 * */
class plugin_wechat extends Controller{
	public $id, $url, $path, $db, 
			$pageType,$wechatProc,$wechat;

	public function __construct($config){
		// 获取插件信息
		$this->pageType='page';
		$this->id=$config['id'];
		$this->url=STATIC_URL . 'plugin/' . $config['install_dir'] . '/';
		$this->path=CORE_PATH . 'plugin' . CD . $config['install_dir'] . CD;
		
		include_once $this->path.'class/WeChatPublic.class.php';
		$api_config=include $this->path.'config/weixin_api_config.php';
		$tpl_config=include $this->path.'config/weixin_tpl_config.php';
		$cache_path=$this->path.'cache/';
		$this->wechat=new WeChatPublic(0,$api_config,$tpl_config,$cache_path);
		
		$config['url']=$this->url;
		$config['path']=$this->path;
		include_once $this->path.'class/WeChatProc.class.php';
		$this->wechatProc=new WeChatProc($this->wechat,$config);
		$this->assign('url',$this->url);
		$this->blue_auths=array(
			'configWXDeviceWiFi','openWXDeviceLib','closeWXDeviceLib',
			'getWXDeviceInfos','sendDataToWXDevice','startScanWXDevice',
			'stopScanWXDevice','connectWXDevice','disconnectWXDevice',
			'getWXDeviceTicket','onWXDeviceBindStateChange','onWXDeviceStateChange',
			'onReceiveDataFromWXDevice','onScanWXDeviceResult','onWXDeviceBluetoothStateChange'
		);
		$this->recoder_auths=array(
			'configWXDeviceWiFi','openWXDeviceLib','closeWXDeviceLib',
		);
	}
	
	// 插件外部方法，无需权限访问
	public function dispatch(){
		$this->wechatProc->dispatch();
	}
	
	// 首页
	public function index(){
		$this->setWechat($this->blue_auths);
		$this->assign('title','球形机器人控制');
		$this->display();
	}
	
	//电视控制
	public function tv(){
		$this->setWechat($this->blue_auths);
		$this->assign('title','电视控制');
		$this->display();
	}
	//基于socket的远程控制
	public function socket(){
		$auths=array(
				'startRecord',
				'stopRecord',
				'onVoiceRecordEnd',
				'playVoice',
				'pauseVoice',
				'stopVoice',
				'onVoicePlayEnd',
				'uploadVoice',
				'translateVoice',
				'downloadVoice'
		);
		$this->setWechat($auths);
		$this->assign('title','基于socket的远程控制');
		$this->display();
	}
	
	public function test(){
		switch ($_GET['type']){
			case 'update':
				$token=$this->wechat->getAccessToken();
				$url='https://api.weixin.qq.com/device/authorize_device?access_token='.$token;
				$device=array(
					'id'=>'', //设备的deviceid
					'mac'=>'',//设备的mac地址, 格式采用16进制串的方式（长度为12字节），不需要0X前缀，如： 1234567890AB
					'connect_protocol'=>'3', //支持以下四种连接协议： android classic bluetooth->1；ios classic bluetooth->2；ble->3；wifi->4 （一个设备可以支持多种连接类型，用符号"|"做分割，客户端优先选择靠前的连接方式（优先级按|关系的排序依次降低））
					'auth_key'=>'', //auth及通信的加密key，第三方需要将key烧制在设备上（128bit），格式采用16进制串的方式（长度为32字节），不需要0X前缀，如： 1234567890ABCDEF1234567890ABCDEF
					'close_strategy'=>'1', //断开策略，目前支持： 1：退出公众号页面时即断开连接 2：退出公众号之后保持连接不断开
					'conn_strategy'=>'1', //连接策略，32位整型，按bit位置位，目前仅第1bit和第3bit位有效（bit置0为无效，1为有效；第2bit已被废弃），且bit位可以按或置位（如1|4=5），各bit置位含义说明如下： 1：（第1bit置位）在公众号对话页面，不停的尝试连接设备,4：（第3bit置位)处于非公众号页面（如主界面等），微信自动连接。当用户切换微信到前台时，可能尝试去连接设备，连上后一定时间会断开
					'crypt_method'=>'0', //auth加密方法，目前支持两种取值： 0：不加密 1：AES加密（CBC模式，PKCS7填充方式）
					'auth_ver'=>'0', //auth version，设备和微信进行auth时，会根据该版本号来确认auth buf和auth key的格式（各version对应的auth buf及key的具体格式可以参看“客户端蓝牙外设协议”），该字段目前支持取值： 0：不加密的version 1：version 1
					'manu_mac_pos'=>'-1', //表示mac地址在厂商广播manufature data里含有mac地址的偏移，取值如下： -1：在尾部、 -2：表示不包含mac地址 其他：非法偏移
					'ser_mac_pos'=>'-2',//表示mac地址在厂商serial number里含有mac地址的偏移，取值如下： -1：表示在尾部 -2：表示不包含mac地址 其他：非法偏移
					'ble_simple_protocol'=>'0', //精简协议类型，取值如下：计步设备精简协议：1 （若该字段填1，connect_protocol 必须包括3。非精简协议设备切勿填写该字段）
				);
				
				$devices[]=array_merge($device,array('id'=>'gh_09897c3dfb39_5b78345ee23c73e7','mac'=>'f0c77ffd0b6d'));
				$devices[]=array_merge($device,array('id'=>'gh_09897c3dfb39_4a01f8f68d00430b','mac'=>'f0c77ffd0c96'));
				$datas=array(
					'device_num'=>'1', //设备id的个数
					'device_list'=>$devices,
					'op_type'=>'1' //请求操作的类型，限定取值为：1：设备更新（更新已授权设备的各属性值）
				);
				var_dump($this->wechat->https_request($url,json_encode($datas)));
				break;
			case 'auth':
				var_dump($this->wechat->deviceAuthorize(27959));
				break;
			case 'token':
				echo $this->wechat->getAccessToken();
				break;
			case 'test':
				var_dump($_SERVER);
				//echo (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				break;
		}
	}
	
	// 插件管理方法，需要管理权限访问
	public function m_init(){
		$this->display();
	}

	//设置
	public function m_setting(){
		
	}
	
	//微信设置
	private function setWechat($auths){
		if(WECHAT_ACCESS){
			$weixin_js_api=$this->wechat->getJsApiConfig(implode(',', $auths));
			$this->assign('weixin_js_api',$weixin_js_api);
		}
	}
}
?>