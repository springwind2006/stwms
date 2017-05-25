<?php
/**
 * 微信公众号开发接口
 * 
 */

class WxPublic{
	private static $cache_path='';
	private $cfg;
	private $tplMsgCfg;

	function __construct($isdebug=0,$api_config=array(),$tpl_config=array(),$cache_path=null){
		self::$cache_path=!empty($cache_path)?$cache_path:CACHE_PATH.'plugin/wxpublic/';
		$api_config['isdebug']=$isdebug;
		$this->cfg=$api_config;
		$this->tplMsgCfg=$tpl_config;
	}

	//获取配置项目
	public function getConfig($key){
		return $this->cfg[$key];
	}

	//验证是否是合法登陆来源
	public function isAuth($state){
		return $state && $state == md5(md5($this->cfg['AppId']).$this->cfg['AppSecret']);
	}

	//根据当前公众号唯一openid获取用户消息
	public function getUserInfo($openid){
		$url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openid.'&lang=zh_CN';
		//$url='https://api.weixin.qq.com/sns/userinfo?access_token='.$this->getAccessToken().'&openid='.$openid.'&lang=zh_CN';
		return json_decode($this->https_request($url),true);
	}

	//根据当前公众平台唯一的openid，获取用户信息
	public function getUserByCode($codeOrToken){
		$token_info=is_array($codeOrToken) ? $codeOrToken : $this->getAccessTokenByCode($codeOrToken);
		$url='https://api.weixin.qq.com/sns/userinfo?access_token='.$token_info['access_token'].'&openid='.$token_info['openid'].'&lang=zh_CN';
		return json_decode($this->https_request($url),true);
	}

	//根据当前公众平台唯一的openid，获取用户信息
	public function getUserByToken($token_info){
		return $this->getUserByCode($token_info);
	}

	//获取共享菜单
	public function getJsApiConfig($auth_list='',$current_url=''){
		$config=array('isdebug'=>$this->cfg['isdebug'],'appid'=>$this->cfg['AppId'],'timestamp'=>time(),'noncestr'=>rand(100,999));
		$current_url=$current_url ? $current_url : (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$shal_str='jsapi_ticket='.$this->getJsApiTicket().'&noncestr='.$config['noncestr'].'&timestamp='.$config['timestamp'].'&url='.$current_url;
		$config['signature']=sha1($shal_str);
		if(!empty($auth_list)&&(is_array($auth_list)||is_string($auth_list))){
			if(is_array($auth_list)){
				foreach($auth_list as $k=> $v){
					$auth_list[$k]='\''.$v.'\'';
				}
				$auth_list=implode(',', $auth_list);
			}else{
				$auth_list='\''.str_replace(',', '\',\'', $auth_list).'\'';
			}
		}
		$config['apilist']=$auth_list;
		return $config;
	}

	///////////////////二维码处理相关函数////////////////////
	//获取二维码信息
	public function getQRCode($qid,$type=1,$expire=1800){
		$dataArr['action_name']=!$type ? 'QR_SCENE' : ($type == 1 ? 'QR_LIMIT_SCENE' : 'QR_LIMIT_STR_SCENE');
		if(!$type){
			$dataArr['expire_seconds']=$expire;
		}
		$dataArr['action_info']=array('scene'=>array((!$type || $type == 1 ? 'scene_id' : 'scene_str')=>($type == 1 ? intval($qid < 1 ? 1 : min($qid,100000)) : $qid)));

		$sendDatas=json_encode($dataArr);
		unset($dataArr);
		$options=array(
			'http'=>array(
				'method'=>'POST',
				'header'=>"Content-type: application/x-www-form-urlencoded\r\nContent-length: ".strlen($sendDatas)."\r\n",
				'content'=>$sendDatas
			)
		);
		$url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$this->getAccessToken();
		return json_decode(file_get_contents($url,false,stream_context_create($options)),true);
	}

	////////////////////消息发送相关函数/////////////////////
	//发送模板消息
	public function sendTplMsg($type,$openid,$datas,$url='',$topColor='#000000'){
		$sendDatas=array(
			'touser'=>$openid,
			'template_id'=>$this->tplMsgCfg[$type],
			'url'=>$url,
			'topcolor'=>$topColor
		);
		foreach($datas as $key=> $val){
			if(is_array($val)){
				$datas[$key]['value']=urlencode($val['value']);
			}else{
				$datas[$key]=array(
					'value'=>urlencode($val),
					'color'=>'#000000'
				);
			}
		}
		$sendDatas['data']=$datas;
		$sendDatas=urldecode(json_encode($sendDatas));

		$req_url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$this->getAccessToken();
		$content_length=strlen($sendDatas);
		$options=array(
			'http'=>array(
				'method'=>'POST',
				'header'=>"Content-type: application/x-www-form-urlencoded\r\nContent-length: $content_length\r\n",
				'content'=>$sendDatas
			)
		);
		$result=file_get_contents($req_url,false,stream_context_create($options));
		return json_decode($result,true);
	}

	//发送文本消息
	public function sendText($msg,$openid,$FromUserName=''){
		return '<xml>
		<ToUserName><![CDATA['.$openid.']]></ToUserName>
		<FromUserName><![CDATA['.($FromUserName ? $FromUserName : $this->cfg['WeixinId']).']]></FromUserName>
		<CreateTime>'.time().'</CreateTime>
		<MsgType><![CDATA[text]]></MsgType>
		<Content><![CDATA['.$msg.']]></Content>
		</xml>';
	}

	//发送图片消息
	public function sendImage($msgId,$openid,$FromUserName=''){
		return '<xml>
		<ToUserName><![CDATA['.$openid.']]></ToUserName>
		<FromUserName><![CDATA['.($FromUserName ? $FromUserName : $this->cfg['WeixinId']).']]></FromUserName>
		<CreateTime>'.time().'</CreateTime>
		<MsgType><![CDATA[image]]></MsgType>
		<Image>
		<MediaId><![CDATA['.$msgId.']]></MediaId>
		</Image>
		</xml>';
	}

	//发送多图文消息
	public function sendNews($msgs,$openid,$FromUserName=''){
		$content='<xml>';
		$content.=
				'<ToUserName><![CDATA['.$openid.']]></ToUserName>
				<FromUserName><![CDATA['.($FromUserName ? $FromUserName : $this->cfg['WeixinId']).']]></FromUserName>
				<CreateTime>'.time().'</CreateTime>
				<MsgType><![CDATA[news]]></MsgType>
				<ArticleCount>'.count($msgs).'</ArticleCount>';
		$content.='<Articles>';
		foreach($msgs as $msg){
			$content.=
					'<item>
					<Title><![CDATA['.$msg['title'].']]></Title>
					<Description><![CDATA['.$msg['description'].']]></Description>
					<PicUrl><![CDATA['.$msg['picurl'].']]></PicUrl>
					<Url><![CDATA['.$msg['url'].']]></Url>
					</item>';
		}
		$content.='</Articles>';
		$content.='</xml>';
		return $content;
	}
	
	//主动发送设备消息
	public function sendDeviceText($content,$deviceid,$openid){
		$url='https://api.weixin.qq.com/device/transmsg?access_token='.$this->getAccessToken();
		$data['device_type']=$this->cfg['WeixinId'];
		$data['device_id']=$deviceid;
		$data['open_id']=$openid;
		$data['content']=base64_encode($content);
		$result=$this->https_request($url,json_encode($data));
		return json_decode($result,true);
	}
	
	//回复设备信息，在接受到设备的事件或消息后回复设备
	public function replyDeviceText($msg,$data){
		return '<xml>
		    <ToUserName><![CDATA['.$data['FromUserName'].']]></ToUserName>
		    <FromUserName><![CDATA['.$data['ToUserName'].']]></FromUserName>
		    <CreateTime>'.time().'</CreateTime>
		    <MsgType><![CDATA['.$data['MsgType'].']]></MsgType>'.
			    (isset($data['Event'])?$data['Event']:'').
			    '<DeviceType><![CDATA['.$data['DeviceType'].']]></DeviceType>
		    <DeviceID><![CDATA['.$data['DeviceID'].']]></DeviceID>
		    <SessionID>'.$data['SessionID'].'</SessionID>
		    <Content><![CDATA['.$msg.']]></Content>
		</xml>';
	}
	
	//强制绑定设备
	public function complBindDevice($deviceid,$openid){
		$url='https://api.weixin.qq.com/device/compel_bind?access_token='.$this->getAccessToken();
		$data['device_id']=$deviceid;
		$data['openid']=$openid;
		$result=json_decode($this->https_request($url,json_encode($data)),true);
		return is_array($result) && isset($result['base_resp']['errcode']) ? 
					($result['base_resp']['errcode']!=-1?true:$result['base_resp']['errmsg']) : 'https error!';
	}
	
	//强制解除绑定设备
	public function complUnbindDevice($deviceid,$openid){
		$url='https://api.weixin.qq.com/device/compel_unbind?access_token='.$this->getAccessToken();
		$data['device_id']=$deviceid;
		$data['openid']=$openid;
		$result=json_decode($this->https_request($url,json_encode($data)),true);
		return is_array($result) && isset($result['base_resp']['errcode']) ?
					($result['base_resp']['errcode']!=-1?true:$result['base_resp']['errmsg']) : 'https error!';
	}

	/**上传多媒体
	 * @param string $filepath 上传文件路径
	 * @param string $type 媒体文件类型，分别有图片（image）、语音（voice）、视频（video）和缩略图（thumb）
	 * @return array 返回信息：{"type":"TYPE","media_id":"MEDIA_ID","created_at":123456789}
	 */
	public function uploadMedia($filepath,$type='image'){
		$sendDatas['media']= !class_exists('CURLFile',false) ? '@'.$filepath : (new CURLFile($filepath));
		$req_url='http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->getAccessToken().'&type='.$type;
		$result=$this->https_request($req_url,$sendDatas);
		return json_decode($result,true);
	}
	
	/**下载多媒体
	 * @param string $mediaId 媒体ID，由上传后返回的media_id获得
	 * @param string $savepath 保存路径，如果为空，直接返回结果数据，为false返回下载地址
	 * @return number|mixed 返回信息，如果出错返回null
	 */
	public function downloadMedia($mediaId,$savepath=''){
		$req_url='http://file.api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$mediaId;
		return $savepath===false ? $req_url : get_remote_file($req_url,$savepath);
	}
	
	//创建微信菜单
	public function createMenu($data){
		$result=json_decode(file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->getAccessToken()),true);
		if($result['errcode']){
			return $result;
		}
		$url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccessToken();
		$header='content-type: application/x-www-form-urlencoded; charset=UTF-8';
		return json_decode($this->https_request($url,$data,$header),true);
	}
	
	//长地址转短地址
	public function shortUrl($long_url){
		$url='https://api.weixin.qq.com/cgi-bin/shorturl?access_token='.$this->getAccessToken();
		$sendDatas=array(
				'action'=>'long2short',
				'long_url'=>$long_url,
		);
		$result=json_decode($this->https_request($url,$sendDatas),true);
		return is_array($result)&&!$result['errcode']&&$result['short_url'] ? $result['short_url'] : '';
	}
	
	//网页授权登陆URL
	public function authUrl($redirect_uri,$state='',$scope='snsapi_base'){
		if(strpos($redirect_uri, '://')===false){
			$redirect_uri=SITE_PROTOCOL.$this->cfg['redirect_domain'].'/'.ltrim($redirect_uri,'/');
		}
		$paras=array(
			'appid'=>$this->cfg['AppId'],
			'scope'=>$scope,
			'state'=>$state,
			'redirect_uri'=>urlencode($redirect_uri),
		);
		$urls='https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect';
		foreach($paras as $k=>$v){
			$urls=str_replace('{$'.$k.'}', $v, $urls);
		}
		return $urls;
	}
	
	//设备授权
	public function deviceAuthorize($product_id=1){
		$url='https://api.weixin.qq.com/device/getqrcode?access_token='.$this->getAccessToken().'&product_id='.$product_id;
		return json_decode($this->https_request($url),true);
	}
	

	/////////////////////基础支持函数////////////////////
	///////////////////////////////////////////////////
	//初次接入进行验证，在微信与网站接口函数处调用
	public function checkSignature(){
		if(isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']) && isset($_GET['echostr'])){
			$signature=$_GET['signature'];
			$timestamp=$_GET['timestamp'];
			$nonce=$_GET['nonce'];
			$tmpArr=array($this->getConfig('Token'),$timestamp,$nonce);
			sort($tmpArr,SORT_STRING);
			$tmpStr=implode($tmpArr);
			$tmpStr=sha1($tmpStr);
			if($tmpStr == $signature){
				exit($_GET['echostr']);
			}
		}
	}

	//获取来自微信端的数据
	public function getData(){
		$content = !empty($GLOBALS['HTTP_RAW_POST_DATA']) ? 
							$GLOBALS['HTTP_RAW_POST_DATA'] : 
							file_get_contents('php://input');
		if(!empty($content)){
			$data=new SimpleXMLElement($content);
			$datas=array();
			foreach($data as $key=> $value){
				$datas[$key]=strval($value);
			}
			return $datas;
		}
		return false;
	}

	//通过code获取Token信息，此token包括openid，此token暂未采用缓存机制
	public function getAccessTokenByCode($code){
		$url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->cfg['AppId'].'&secret='.$this->cfg['AppSecret'].'&code='.$code.'&grant_type=authorization_code';
		$data=$this->https_request($url);
		return json_decode($data,true);
	}

	//信息提交，使用http
	public function https_request($url,$data=null,$header=null){
		$curl=curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
		if(!empty($header)){
			if(!is_array($header)){
				$headers[]=$header;
			}else{
				$headers=$header;
			}
			curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
		}
		curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		if(!empty($data)){
			curl_setopt($curl,CURLOPT_POST,1);
			curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
		}
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$output=curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	//获取JsapiTicket，在JS API中使用
	public function getJsApiTicket(){
		$surl='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={ACCESS_TOKEN}&type=jsapi';
		if(!is_dir(self::$cache_path)){
			@mkdir(self::$cache_path,0777,true);
		}
		$filename=self::$cache_path.md5($surl);
		$arr='';
		if(!file_exists($filename) ||
				(
				($arr=explode("\t",file_get_contents($filename))) &&
				(time() - filemtime($filename)) > intval($arr[1])
				)
		){
			$surl=str_replace('{ACCESS_TOKEN}',$this->getAccessToken(),$surl);
			$arrs=json_decode($this->https_request($surl),true);
			file_put_contents($filename,$arrs['ticket']."\t".$arrs['expires_in']);
			return $arrs['ticket'];
		}
		if(!$arr){
			$arr=explode("\t",file_get_contents($filename));
		}
		return $arr[0];
	}

	//通用方式获取AccessToken
	public function getAccessToken(){
		$surl='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={APPID}&secret={APPSECRET}';
		if(!is_dir(self::$cache_path)){
			@mkdir(self::$cache_path,0777,true);
		}
		$filename=self::$cache_path.md5(serialize($this->cfg));
		$arr='';
		if(!file_exists($filename) ||
				(
				($arr=explode("\t",file_get_contents($filename))) &&
				(time() - filemtime($filename)) >= intval($arr[1])
				)
		){
			$surl=str_replace('{APPID}',$this->cfg['AppId'],str_replace('{APPSECRET}',$this->cfg['AppSecret'],$surl));
			$arrs=json_decode($this->https_request($surl),true);
			if(is_array($arrs) && $arrs['access_token']){
				file_put_contents($filename,$arrs['access_token']."\t".$arrs['expires_in']."\t".time());
				return $arrs['access_token'];
			}
		}
		if(!$arr){
			$arr=explode("\t",file_get_contents($filename));
		}
		return $arr[0];
	}

}

?>