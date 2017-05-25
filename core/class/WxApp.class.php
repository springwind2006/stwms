<?php
/**
 * 微信小程序开发接口
 * 
 */

class WxApp{
	private static $cache_path='';
	private $cfg;
	private $tplMsgCfg;

	function __construct($isdebug=0,$api_config=array(),$tpl_config=array(),$cache_path=null){
		self::$cache_path=!empty($cache_path)?$cache_path:CACHE_PATH.'plugin/wxapp/';
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
		return $state && $state == $this->getAuth();
	}
	
	//获取授权码
	public function getAuth(){
		return md5(md5($this->cfg['AppId']).$this->cfg['AppSecret']);
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
				echo $_GET['echostr'];
			}
			exit(0);
		}
	}

	//获取来自微信端的数据
	public function getData(){
		$content=$GLOBALS['HTTP_RAW_POST_DATA'];
		if(!$content){
			$content = file_get_contents('php://input');
		}
		!empty($content) || die('sorry,this is the interface for weixin');
		$data=new SimpleXMLElement($content);
		$datas=array();
		foreach($data as $key=> $value){
			$datas[$key]=strval($value);
		}
		return $datas;
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
	
	//通过code获取Session,返回:{"openid":"OPENID","session_key":"SESSIONKEY"}
	public function getSessionByCode($code){
		$url='https://api.weixin.qq.com/sns/jscode2session?appid='.$this->cfg['AppId'].'&secret='.$this->cfg['AppSecret'].'&js_code='.$code.'&grant_type=authorization_code';
		$arrs=json_decode($this->https_request($url),true);
		return is_array($arrs) && $arrs['openid'] ? $arrs : false;
	}

}

?>