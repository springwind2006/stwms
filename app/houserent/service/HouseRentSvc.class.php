<?php
class HouseRentSvc{
	const user_catid=21,//小程序用户
		renter_catid=22,//出租人信息
		tenant_catid=23,//住户信息
		alarm_catid=24; //报警信息
	private $config;
	
	public function __construct($config=null){
		// 获取插件信息
		$this->config=$config;
	}
	
	//检查用户是否存在
	public function check_user($id){
		$where['catid']=self::user_catid;
		$where['id']=$id;
		return M('wx_user')->where($where)->find();
	}
	
	//检查用户是否存在
	public function get_user_by_openid($openid){
		$where['catid']=self::user_catid;
		$where['openid']=$openid;
		return M('wx_user')->where($where)->find();
	}
	
	//检查获取用户信息
	public function get_user($id,$is_admin=0){
		$field='id,nickname,sex,headimgurl as avatar,city,province';
		$where['id']=$id;
		$info=M('wx_user')->field($field)->where($where)->find();
		unset($where);
		$where['catid']=self::tenant_catid;
		if(!$is_admin){
			$where['user_id']=$id;
		}
		$info['tenant']=intval(M('ret_tenant')->where($where)->count());
		return $info;
	}
	
	//保存用户信息
	public function save_user($data){
		$data['catid']=self::user_catid;
		$where['catid']=$data['catid'];
		$where['openid']=$data['openid'];
		$data['isimages']=empty($data['headimgurl'])?0:1;
		if($user=M('wx_user')->where($where)->find()){
			if(empty($user['username']) && !empty($data['nickname'])){
				$data['username']=$this->make_username($data['nickname']);
			}
			M('wx_user')->where('`id`='.$user['id'])->save($data);
			return $user['id'];
		}else{
			$data['status']=1;
			$data['addtime']=NOW_TIME;
			return M('wx_user')->add($data);
		}
	}
	
	//获取出租人信息
	public function get_renter($user_id,$strip=true){
		$where['catid']=self::renter_catid;
		$where['user_id']=$user_id;
		if($renter=M('ret_renter')->where($where)->find()){
			if($strip){
				$strips='id,catid,user_id,isimages,status,addtime';
				foreach(explode(',', $strips) as $k){
					unset($renter[$k]);
				}
			}
			return $renter;
		}else{
			return array();
		}
	}
	
	//保存出租人信息
	public function save_renter($data){
		$data['catid']=self::renter_catid;
		$where['catid']=$data['catid'];
		$where['user_id']=$data['user_id'];
		if($renter=M('ret_renter')->where($where)->find()){
			M('ret_renter')->where('`id`='.$renter['id'])->save($data);
		}else{
			$data['isimages']=0;
			$data['status']=1;
			$data['addtime']=NOW_TIME;
			M('ret_renter')->add($data);
		}
	}
	
	//获取住客信息
	public function get_tenant($id,$user_id=0,$strip=true){
		$where['catid']=self::tenant_catid;
		$where['id']=$id;
		if($user_id){
			$where['user_id']=$user_id;
		}
		if($tenant=M('ret_tenant')->where($where)->find()){
			if($strip){
				$strips='catid,user_id,isimages,status,addtime';
				foreach(explode(',', $strips) as $k){
					unset($tenant[$k]);
				}
			}
			return $tenant;
		}else{
			return array();
		}
	}
	
	//获取住客列表
	public function get_tenant_list($page,$user_id=0,$field='id,name,id_no',$size=15){
		$start=(max($page,1)-1)*$size;
		if($user_id){
			$where['user_id']=$user_id;
		}
		$where['catid']=self::tenant_catid;
		return M('ret_tenant')->field($field)->order('id desc')->where($where)->limit($start,$size)->select();
	}
	
	//获取报警信息
	public function get_alarm_list($page,$size=15){
		$start=(max($page,1)-1)*$size;
		return M('ret_alarm')->order('id desc')->limit($start,$size)->select();
	}
	
	//保存住客信息
	public function save_tenant($data,$is_admin=0){
		$id=intval($data['id']);
		$data['isimages']=empty($data['id_img'])?0:1;
		$data['come_date']=strtotime($data['come_date']);
		$data['left_date']=strtotime($data['left_date']);
		if(!$is_admin){
			$where['user_id']=$data['user_id'];
		}
		$where['id']=$id;
		$data['catid']=self::tenant_catid;
		$res_id=0;
		$old_data=array();
		if($id && M('ret_tenant')->where($where)->count('id')){
			$old_data=M('ret_tenant')->where($where)->find();
			if(M('ret_tenant')->where($where)->save($data)){
				$res_id=$id;
			}
		}else{
			$data['status']=1;
			$data['addtime']=NOW_TIME;
			if($result=M('ret_tenant')->add($data)){
				$res_id=$result;
			}
		}
		if($res_id && !$is_admin){
			$data['id']=$res_id;
			$this->keyword_alarm($data,$old_data);
		}
	}
	
	//上传图片
	public function upload_id_image($user_id,$field='uploadfile',$verify=true){
		$alowexts='';
		$maxsize=0;
		$overwrite=0;
		$settings=array(
				800, //thumb_width
				800,//thumb_height
				0,//auto_cut
				0,//watermark_enable
				1 //md5_check
		);
		load::cls('Uploadfile', 0, 0);
		$uploadfile=new Uploadfile('ret_tenant',self::tenant_catid);
		$uploadfile->set_userid($user_id);
		$aids=empty($_FILES[$field]) ? 
					$uploadfile->save($field,$alowexts,$maxsize,$overwrite,$settings) : 
					$uploadfile->upload($field,$alowexts,$maxsize,$overwrite,$settings);
		$return=array('status'=>0,'msg'=>$uploadfile->error);
		if($aids && $aids[0] != -1){
			$filepath=$uploadfile->uploadedfiles[0]['filepath'];
			$return['path']=UPLOAD_URL.$filepath;
			$return['name']=$uploadfile->uploadedfiles[0]['filename'];
			$return['size']=$uploadfile->uploadedfiles[0]['filesize'];
			$return['md5']=$uploadfile->uploadedfiles[0]['md5'];
			$return['status']=1;
			$img_data=base64EncodeImage(UPLOAD_PATH.$filepath);
			$return['infos']=load::controller('home.api',1)->id_identify($img_data);
		}
		
		return $return;
	}
	
	//根据用户昵称生成用户名
	private function make_username($nickname){
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
		return $username;
	}
	
	//关键字预警
	private function keyword_alarm($data,$old){
		$configs=include $this->config['path'].'config/alarm_keyword.cfg.php';
		$results=array();
		$alarm['catid']=self::alarm_catid;
		$alarm['status']=1;
		$alarm['isimages']=0;
		foreach ($configs as $field=>$config){
			if(
				!empty($data[$field]) && 
				(!isset($old[$field]) || $old[$field]!=$data[$field])
			){
				$value=trim($data[$field]);
				$keywords=array_filter(preg_split('/[\s,]+/', $config['keyword']));
				foreach($keywords as $keyword){
					$result='';
					switch ($config['condition']){
						case 'start':
							if(strpos($value, $keyword)===0){
								$result='以“'.$keyword.'”开始';
							}
							break;
						case 'end':
							if(strrpos($value, $keyword)===(strlen($value)-strlen($keyword))){
								$result='以“'.$keyword.'”结尾';
							}
							break;
						case 'contain':
							if(strpos($value, $keyword)!==false){
								$result='包含“'.$keyword.'”';
							}
							break;
						case 'equal':
							if($value==$keyword){
								if($field=='nation'){
									$result='是“维吾尔族/新疆”';
								}else{
									$result='是“'.$keyword.'”';
								}
							}
							break;
					}
					if(!empty($result)){
						$result='“'.$config['name'].'”'.$result;
						$results[]=$result;
						$alarm['tenant_id']=$data['id'];
						$alarm['renter_id']=$data['user_id'];
						$alarm['field']=$field;
						$alarm['name']=$config['name'];
						$alarm['condition']=$config['condition'];
						$alarm['memo']=$result;
						$alarm['addtime']=NOW_TIME;
						M('ret_alarm')->add($alarm);
					}
				}
			}
		}
		if(!empty($results)){
			$api_config=include $this->config['path'].'config/wxpublic1_api.cfg.php';
			$tpl_config=include $this->config['path'].'config/wxpublic1_tpl.cfg.php';
			load::cls('WxPublic',0);
			$alarm=new WxPublic(0,$api_config,$tpl_config);
			$openids=include $this->config['path'].'config/auth_user.cfg.php';
			$remark='详情点击进入管理查看';
			$datas=array(
				'first'=>array('value'=>'您收到报警信息！','color'=>'#ff0000'),
				'keyword1'=>date('Y-m-d H:i:s'),
				'keyword2'=>$data['detail_addr'],
				'keyword3'=>implode('、', $results),
				'remark'=>$remark
			);
			$url=U(':user?type=tenant&id='.$data['id'],true);
			foreach($openids as $openid){
				$alarm->sendTplMsg('report', $openid, $datas,$url);
			}
			//发送报警信息
		}
	}

}






