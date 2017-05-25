<?php
defined('IN_MYCMS') or exit('No permission resources.');
class ApiHome extends HomeController{

	/**
	 * 功能：生成客户端验证码 参数说明： 提交方式：GET； bg:背景颜色，值为6位16进制字符，如:ff0000(红色)； ft:字体颜色，与bg参数相同； w:图片宽度，为数值，默认为50，单位像素； h:图片高度，为数值，默认为20，单位像素； num:验证码位数：默认为5
	 */
	public function code(){
		session_start();
		// 生成验证码图片
		header("Content-type: image/PNG");
		
		// 背景颜色
		$bgColorArr=array(100,0,0);
		if(isset($_GET['bg'])){
			$bgStr=str_replace('#', '', strtolower($_GET['bg']));
			if(preg_match("/^([0-9a-f]{3})|([0-9a-f]{6})$/i", trim($bgStr))){
				if(strlen($bgStr) == 3){
					$bgColorArr[0]=hexdec(substr($bgStr, 0, 1));
					$bgColorArr[1]=hexdec(substr($bgStr, 1, 1));
					$bgColorArr[2]=hexdec(substr($bgStr, 2, 1));
				}else{
					$bgColorArr[0]=hexdec(substr($bgStr, 0, 2));
					$bgColorArr[1]=hexdec(substr($bgStr, 2, 2));
					$bgColorArr[2]=hexdec(substr($bgStr, 4, 2));
				}
			}
		}
		// 文字颜色
		$ftColorArr=array(255,100,0);
		if(isset($_GET['ft'])){
			$ftStr=str_replace('#', '', strtolower($_GET['ft']));
			if(preg_match("/^([0-9a-f]{3})|([0-9a-f]{6})$/i", trim($ftStr))){
				if(strlen($ftStr) == 3){
					$ftColorArr[0]=hexdec(substr($ftStr, 0, 1));
					$ftColorArr[1]=hexdec(substr($ftStr, 1, 1));
					$ftColorArr[2]=hexdec(substr($ftStr, 2, 1));
				}else{
					$ftColorArr[0]=hexdec(substr($ftStr, 0, 2));
					$ftColorArr[1]=hexdec(substr($ftStr, 2, 2));
					$ftColorArr[2]=hexdec(substr($ftStr, 4, 2));
				}
			}
		}
		
		$width=isset($_GET['w']) && intval($_GET['w']) ? intval($_GET['w']) : 50;
		$height=isset($_GET['h']) && intval($_GET['h']) ? intval($_GET['h']) : 20;
		$num=isset($_GET['num']) && intval($_GET['num']) ? intval($_GET['num']) : 5;
		
		$im=imagecreate($width, $height);
		$bgColor=imagecolorallocate($im, $bgColorArr[0], $bgColorArr[1], $bgColorArr[2]);
		$ftColor=imagecolorallocate($im, $ftColorArr[0], $ftColorArr[1], $ftColorArr[2]);
		
		imagefill($im, 68, 30, $bgColor);
		$authnum=random($num, '23456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz');
		// 将四位整数验证码绘入图片
		imagestring($im, 5, 3, 3, $authnum, $ftColor);
		// 加入干扰象素
		for($i=0; $i < 50; $i++){
			$randcolor=imagecolorallocate($im, rand(200, 240), rand(200, 240), rand(200, 240));
			imagesetpixel($im, rand() % $width, rand() % $height, $randcolor);
		}
		imagepng($im);
		imagedestroy($im);
		$_SESSION['checkcode']=$authnum;
	}

	/**
	 * 功能：直接处理图片，不缓存至文件 参数示例： 本地图片-》index.php?c=api&a=thumb&f=/ufs/2013/1013/20131013125942100.jpg&w=600&h=500&cut=1 网络图片-》index.php?c=api&a=thumb&f=http://p8.qhimg.com/t01e87328014671ce55.jpg&w=600&h=500 参数说明： 提交方式：GET； f:图片地址，为相对于服务器的地址； w:图片最大宽度，默认为0，即按原宽度显示； h:图片最大高度，默认为0，即按原高度显示； cut:是否剪裁图片，默认为0，即不剪裁，剪裁图片后输出图片完全按照给定的高度和宽度显示图片
	 */
	public function thumb(){
		$filename=isset($_GET['f']) ? $_GET['f'] : '';
		if(empty($filename)){
			return false;
		}
		$width=isset($_GET['w']) ? intval($_GET['w']) : 0;
		$height=isset($_GET['h']) ? intval($_GET['h']) : 0;
		$autocut=isset($_GET['cut']) ? intval($_GET['cut']) : 0;
		$filepath=strpos($filename, '://') ? $filename : ROOT_PATH . ltrim($filename, '/');
		
		if(strpos($filepath, '://') || is_file($filepath)){
			if(empty($width) && empty($height)){
				list($width, $height)=getimagesize($filepath);
			}
			$imgObj=load::cls('Image', 1, 1);
			$res=$imgObj->thumbImg($filepath, '', $width, $height, $autocut);
			if(!$res){
				$imageinfo=getimagesize($filepath);
				header('Content-type: ' . $imageinfo['mime']);
				echo file_get_contents($filepath);
			}
		}
	}

	/**
	 * 功能：下载文件 参数示例： 1.index.php?c=api&a=download&f=L3Vmcy8yMDEzLzEwMTMvMjAxMzEwMTMxMjU5NDIxMDAuanBn 2.index.php?c=api&a=download&fid=12 参数说明： 提交方式：GET； f:图片地址，已经用base64加密，为相对于服务器的地址； fid:附件在附件库中id
	 */
	function download($fileInfo=array()){
		if(empty($fileInfo)){
			if(!empty($_GET['f'])){
				$filepath=base64($_GET['f'], 'decode', 1);
				if($filepath && (strpos($filepath, '://') || strpos($filepath, UPLOAD_URL) === 0)){
					$fileInfo['path']=strpos($filepath, '://') ? $filepath : ROOT_PATH . ltrim($filepath, '\\/');
					$fileInfo['name']=basename($filepath);
					$fileInfo['ext']=strtolower(($pos=strrpos($fileInfo['name'], '.')) !== false ? substr($fileInfo['name'], $pos + 1) : '');
				}
			}else if(!empty($_GET['fid'])){
				$id=intval($_GET['fid']);
				$info=$this->getDb()->getOne('attachments', '*', 'where `id`=' . $id);
				if(!empty($info)){
					$fileInfo['path']=UPLOAD_PATH . $info['filepath'];
					$fileInfo['name']=$info['filename'];
					$fileInfo['ext']=strtolower($info['fileext']);
				}
				unset($info);
			}
		}
		
		if(!empty($fileInfo)){ // 文件名乱码问题
			$fileInfo['name']=str_replace(array('"','\''), '', $fileInfo['name']);
			if(preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT'])){
				$attachmentHeader='Content-Disposition: attachment; filename=' . str_replace("+", "%20", urlencode($fileInfo['name'])) . '; charset=utf-8';
			}else if(preg_match("/Firefox/", $_SERVER['HTTP_USER_AGENT'])){
				$attachmentHeader='Content-Disposition: attachment; filename*="utf-8\'\'' . $fileInfo['name'] . '"';
			}else{
				$attachmentHeader='Content-Disposition: attachment; filename="' . $fileInfo['name'] . '"; charset=utf-8';
			}
			header('Content-Type: application/force-download');
			header('Content-Type: ' . load::cfg('mimetype', $fileInfo['ext'], 'application/octet-stream'));
			header($attachmentHeader);
			header('Pragma: cache');
			header('Cache-Control: public, must-revalidate, max-age=0');
			header('Content-Length: ' . filesize($fileInfo['path']));
			readfile($fileInfo['path']);
		}
	}

	/*
	 * 功能：获取访问次数 参数：(栏目和ID标识,类别) 说明：此函数外部调用时需GET提交catid和id参数，内部调用第一个参数为{catid}-{id}格式， 第二个参数是否同时更新（默认为1）
	 */
	function hits($hid='',$type=1){
		$isInCall=!empty($hid) && !is_array($hid);
		if($isInCall){
			if(strpos($hid, '-') === false){
				$catid=intval($hid);
				$hitsid=-1;
			}else{
				$hids=explode('-', $hid);
				$catid=intval($hids[0]);
				$hitsid=intval($hids[1]);
			}
		}else{
			$catid=intval(Param::get_para('catid'));
			$hitsid=intval(isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : -1));
		}
		if(!$catid || $hitsid == -1){
			if($isInCall){
				return false;
			}else{
				exit('{}');
			}
		}
		
		$models=getcache('category', 'core', 'array', 'base');
		if(!isset($models[$catid]) || $models[$catid]['type'] == 2){
			if($isInCall){
				return false;
			}else{
				exit('{}');
			}
		}
		$model=empty($models[$catid]['model']) ? 'page' : $models[$catid]['model'];
		unset($models);
		$status=1;
		
		if($this->getDb()->hasField($model, 'status')){
			$res=$this->getDb()->getOne($model, 'id,status', 'where `id`=' . $hitsid . ' and `catid`=' . $catid);
			$status=is_numeric($res['status']) ? intval($res['status']) : $status;
			unset($res);
		}
		
		if($status != 1){ // 排除非正常状态值下的数据统计
			if($isInCall){
				return false;
			}else{
				exit('{}');
			}
		}
		
		$r=$this->getDb()->getOne('hits', '*', 'where `hitsid`=' . $hitsid . ' and `catid`=' . $catid);
		$inArr=array('hitsid' => $hitsid,'catid' => $catid,'views' => 0,'yesterdayviews' => 0,'dayviews' => 0,'weekviews' => 0,'monthviews' => 0,'viewtime' => NOW_TIME);
		
		if(empty($r)){
			$inArr['views']=$inArr['dayviews']=$inArr['weekviews']=$inArr['monthviews']=$inArr['views'] + 1;
			if($type){
				$this->getDb()->update($model, array('hits' => $inArr['views']), 'where `id`=' . $hitsid . ' and `catid`=' . $catid);
				$this->getDb()->insert('hits', $inArr);
			}
		}else{
			$inArr['views']=intval($r['views']) + 1;
			$inArr['yesterdayviews']=(date('Ymd', $r['viewtime']) == date('Ymd', strtotime('-1 day'))) ? $r['dayviews'] : $r['yesterdayviews'];
			$inArr['dayviews']=(date('Ymd', $r['viewtime']) == date('Ymd', NOW_TIME)) ? ($r['dayviews'] + 1) : 1;
			$inArr['weekviews']=(date('YW', $r['viewtime']) == date('YW', NOW_TIME)) ? ($r['weekviews'] + 1) : 1;
			$inArr['monthviews']=(date('Ym', $r['viewtime']) == date('Ym', NOW_TIME)) ? ($r['monthviews'] + 1) : 1;
			if($type){
				$this->getDb()->update($model, array('hits' => $inArr['views']), 'where `id`=' . $hitsid . ' and `catid`=' . $catid);
				$this->getDb()->update('hits', $inArr, 'where `hitsid`=' . $hitsid . ' and `catid`=' . $catid);
			}
		}
		if($isInCall){
			return $inArr;
		}else{
			exit(make_json($inArr));
		}
	}
	
	// 根据IP地址获取所在地
	public function ipwhere(){
		$ip=Param::get_para('ip', getIP());
		$isMore=Param::get_para('more', 0);
		load::cls('Client', 0);
		$data=Client::get_ip_from($ip);
		$jsoncallback=Param::get_para('jsoncallback');
		if($isMore){
			$json_str=make_json($data);
			echo $jsoncallback ? $jsoncallback . '(' . $json_str . ')' : $json_str;
		}else{
			$json_str=$data['data']['country'] . $data['data']['region'] . $data['data']['city'];
			echo $jsoncallback ? $jsoncallback . '("' . $json_str . '")' : $json_str;
		}
	}
	
	//身份证识别
	public function id_identify($img_data=null){
		if(is_null($img_data)){
			$filename=Param::get_para('filename');
			$img_data=base64EncodeImage($filename);
		}
		if(!empty($img_data)){
			$url = 'https://dm-51.data.aliyun.com/rest/160601/ocr/ocr_idcard.json';
			$method = 'POST';
			$appcode = '90f01e941f64440ebbede48dd2a809e6';
			$headers[]='Authorization:APPCODE ' . $appcode;
			$headers[]='Content-Type:application/json; charset=UTF-8';
			$bodys = '{"inputs": [{"image": {"dataType": 50,"dataValue": "'.$img_data.'"},"configure": {"dataType": 50,"dataValue": "{\"side\":\"face\"}"}}]}';
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_FAILONERROR, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			if (1 == strpos("$".$url, "https://")){
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			}
			curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
			$data=curl_exec($curl);
			$data=json_decode($data,true);
			if(is_array($data) && isset($data['outputs'][0]['outputValue']['dataValue'])){
				$data=json_decode($data['outputs'][0]['outputValue']['dataValue'],true);
				if(is_array($data) && $data['success'] && !empty($data['address'])){
					return $data;
				}
			}
		}
		return false;
	}
	
}

?>