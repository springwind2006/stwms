<?php
class WxAppSvc{
	private $wxapp,$config;
	
	public function __construct(WxApp $wxapp,$config=null){
		// 获取插件信息
		$this->wxapp=$wxapp;
		$this->config=$config;
	}
	
	///////////////////////////////////////////////////////////////////
	///////////////////////////微信回复路由//////////////////////////////
	public function dispatch(){
		$this->wxapp->checkSignature();
		$data=$this->wxapp->getData();
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
			echo $this->wxapp->sendText($info['reply'],$openid);
		}
	}
	//回复图片信息
	public function replyImage($openid,&$data){
	}

	

	//进入会话事件
	public function onUserEnterTempsession($openid,&$data){
		
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
		$key=!empty($this->config['tuling_key'])?$this->config['tuling_key']:'6b0b8e0f9463c8310a8062f6e4e3da53';
		$url='http://www.tuling123.com/openapi/api?key='.$key.'&info='.urlencode($msg).'&userid='.$openid;
		$result=json_decode($this->wxapp->https_request($url),true);
		return is_array($result)&&$result['text'] ? $result['text'] : '';
	}
	
}