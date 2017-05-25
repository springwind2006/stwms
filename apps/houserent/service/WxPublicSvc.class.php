<?php
class WxPublicSvc{
	private $wxpub,$config,$domain,$autoReply,$userCatid,$msgCatid;
	
	public function __construct(WxPublic $wxpub,$config=null){
		// 获取插件信息
		$this->wxpub=$wxpub;
		$this->config=$config;
		$this->domain='http://www.ytzfgj.com';
		$this->autoReply=(bool)$config['auto_reply'];
		$this->userCatid=isset($config['user_catid'])?$config['user_catid']:19;//用户栏目ID
		$this->msgCatid=isset($config['msg_catid'])?$config['msg_catid']:20;//消息栏目ID
	}
	
	///////////////////////////////////////////////////////////////////
	///////////////////////////微信回复路由//////////////////////////////
	public function dispatch(){
		$this->wxpub->checkSignature();
		$data=$this->wxpub->getData();
		if(is_array($data) && isset($data['FromUserName'])){
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
			return true;
		}
		return false;
	}
	
	//回复文本信息
	public function replyText($openid,&$data){
		$catid=$this->msgCatid;
		$info['openid']=$openid;
		$info['catid']=$catid;
		$info['status']=1;
		$info['addtime']=time();
		$info['isimages']=0;
		$info['type']='text';
		$info['content']=$data['Content'];
		$info['wxappid']=$data['ToUserName'];
		$info['reply']=$this->robotReply($openid, $data['Content']);
		M('wx_message')->add($info);
		if($info['reply']){
			echo $this->wxpub->sendText($info['reply'],$openid);
		}else{
			echo 'success';
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
		echo $this->wxpub->replyDeviceText($msg, $data);
	}
	
	//关注微信时候调用
	public function onSubscribe($openid,&$data){
		$wxappid=$data['ToUserName'];
		$user_id=$this->_register($openid, $wxappid);
		$news=array(array(
			'title'=>'北京月坛派出所出租房管理',
			'description'=>'点击进入住∙退房登记。',
			'picurl'=>$this->domain.'/apps/houserent/images/info_add.jpg',
			'url'=>$this->domain.'/index.php?plugin_c=houserent&plugin_a=tenant&type=add&user_id='.$user_id
		));
		echo $this->wxpub->sendNews($news, $openid,$wxappid);
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
		$ret=$this->wxpub->complBindDevice($data['DeviceID'], $openid);
		$ret===true && M('wx_user')->where('`openid`=\''.$openid.'\'')->setField('device_id',$data['DeviceID']);
		echo $this->wxpub->replyDeviceText($ret===true?'bind success!':($ret?$ret:'bind failed!'), $data);
	}
	//设备解除绑定事件
	public function onDeviceUnbind($openid,&$data){
		$ret=$this->wxpub->complUnbindDevice($data['DeviceID'], $openid);
		$ret===true && M('wx_user')->where('`openid`=\''.$openid.'\'')->setField('device_id','');
		echo $this->wxpub->replyDeviceText($ret===true?'unbind success!':($ret?$ret:'unbind failed!'), $data);
	}
	
	//注册用户
	public function _register($openid,$wxappid){
		$info=$this->wxpub->getUserInfo($openid);
		$info['openid']=$openid;
		$info['isimages']=empty($info['headimgurl'])?0:1;
		if($user_id=M('wx_user')->where('`openid`=\''.$openid.'\'')->getField('id')){
			M('wx_user')->where('`id`='.$user_id)->save($info);
			return $user_id;
		}else{
			$nickname=isset($info['nickname'])?$info['nickname']:'';
			$username=preg_replace("/[^\x{4e00}-\x{9fa5}A-Za-z0-9_]+/u", '', $nickname);
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
			$info['wxappid']=$wxappid;
			$info['catid']=$this->userCatid;
			$info['username']=$username;
			$info['status']=1;
			$info['addtime']=time();
			return M('wx_user')->add($info);
		}
	}
	
	/////////////内部私有方法//////////////
	private function robotReply($openid,$msg){
		if($this->autoReply){
			$key=!empty($this->config['tuling_key'])?$this->config['tuling_key']:'6b0b8e0f9463c8310a8062f6e4e3da53';
			$url='http://www.tuling123.com/openapi/api?key='.$key.'&info='.urlencode($msg).'&userid='.$openid;
			$result=json_decode($this->wxpub->https_request($url),true);
			return is_array($result)&&$result['text'] ? $result['text'] : '';
		}else{
			return '';
		}
	}
	
}