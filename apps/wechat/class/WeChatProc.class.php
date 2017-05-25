<?php
class WeChatProc{
	private $wechat,$config;
	
	public function __construct(WeChatPublic $wechat,$config=null){
		// 获取插件信息
		$this->wechat=$wechat;
		$this->config=$config;
	}
	
	///////////////////////////////////////////////////////////////////
	///////////////////////////微信回复路由//////////////////////////////
	public function dispatch(){
		$this->wechat->checkSignature();
		$data=$this->wechat->getData();
		$method=parse_name(
							$data['MsgType']!='event' ? 
								($data['MsgType']!='device_event' ? 
									'reply_'.$data['MsgType']:
									'on_device_'.$data['Event']) : 
							'on_'.$data['Event'],
						1);
		$openid=$data['FromUserName'];
		if(method_exists($this, $method)){
			header('Content-Type:text/html; charset=utf-8');
			header('Cache-control: private');
			$this->$method($openid,$data);
		}
	}
	
	//回复文本信息
	public function replyText($openid,&$data){
		$catid=20;
		$info['openid']=$openid;
		$info['catid']=$catid;
		$info['status']=1;
		$info['addtime']=time();
		$info['isimages']=0;
		$info['type']='text';
		$info['content']=$data['Content'];
		$info['reply']=$this->robotReply($openid, $data['Content']);
		M('wx_message')->add($info);
		if($info['reply']){
			echo $this->wechat->sendText($info['reply'],$openid);
		}
	}
	//回复图片信息
	public function replyImage($openid,&$data){
	}
	//回复语音信息
	public function replyVoice($openid,&$data){
	}
	//回复视频信息
	public function replyVideo($openid,&$data){
	}
	//回复小视频消息
	public function replyShortvideo($openid,&$data){
		
	}
	//回复位置信息
	public function replyLocation($openid,&$data){
	}
	//回复连接信息
	public function replyLink($openid,&$data){
	}
	//回复设备消息
	public function replyDeviceText($openid,&$data){
		$msg='rev:'.$data['Content'];
		echo $this->wechat->replyDeviceText($msg, $data);
	}
	
	//关注微信时候调用
	public function onSubscribe($openid,&$data){
		$catid=19;//微信用户栏目ID
		$info=$this->wechat->getUserInfo($openid);
		$info['isimages']=empty($info['headimgurl'])?0:1;
		if($user=M('wx_user')->where('`openid`=\''.$openid.'\'')->find()){
			M('wx_user')->where('`id`='.$user['id'])->save($info);
		}else{
			$username=preg_replace("/[^\x{4e00}-\x{9fa5}A-Za-z0-9_]+/u", '', $info['nickname']);
			if($username === ''){
				$username='user';
			}
			$maxId=intval(M('wx_user')->max('id')) + 1;
			// 生成不存在的用户名：用户名格式规范：昵称_ID
			if(M('wx_user')->where('`username`=\'' . $username . '\'')->count('id')){
				while(M('wx_user')->where('`username`=\'' . $username . '_' . $maxId . '\'')->count('id')){
					$maxId++;
				}
				$username.='_' . $maxId;
			}
			$info['catid']=$catid;
			$info['username']=$username;
			$info['status']=1;
			$info['addtime']=time();
			M('wx_user')->add($info);
		}
		echo $this->wechat->sendText('欢迎使用，<a href="http://mapp.h928.com/index.php?plugin_c=wechat&plugin_a=socket">点击进入控制操作</a>', $openid);
	}
	//取消关注事件调用
	public function onUnsubscribe($openid,&$data){
		M('wx_user')->where('`openid`=\''.$openid.'\'')->setField('subscribe',0);
	}
	//扫码事件调用
	public function onScan($openid,&$data){
	}
	//位置事件调用
	public function onLocation($openid,&$data){
	}
	//点击事件调用
	public function onClick($openid,&$data){
	}
	//查看事件调用
	public function onView($openid,&$data){
	}
	//设备绑定事件
	public function onDeviceBind($openid,&$data){
		$ret=$this->wechat->complBindDevice($data['DeviceID'], $openid);
		$ret===true && M('wx_user')->where('`openid`=\''.$openid.'\'')->setField('device_id',$data['DeviceID']);
		echo $this->wechat->replyDeviceText($ret===true?'bind success!':($ret?$ret:'bind failed!'), $data);
	}
	//设备解除绑定事件
	public function onDeviceUnbind($openid,&$data){
		$ret=$this->wechat->complUnbindDevice($data['DeviceID'], $openid);
		$ret===true && M('wx_user')->where('`openid`=\''.$openid.'\'')->setField('device_id','');
		echo $this->wechat->replyDeviceText($ret===true?'unbind success!':($ret?$ret:'unbind failed!'), $data);
	}
	
	
	/////////////内部私有方法//////////////
	private function robotReply($openid,$msg){
		$key=!empty($this->config['tuling_key'])?$this->config['tuling_key']:'6b0b8e0f9463c8310a8062f6e4e3da53';
		$url='http://www.tuling123.com/openapi/api?key='.$key.'&info='.urlencode($msg).'&userid='.$openid;
		$result=json_decode($this->wechat->https_request($url),true);
		return is_array($result)&&$result['text'] ? $result['text'] : '';
	}
	
}