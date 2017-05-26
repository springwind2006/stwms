<?php
defined('IN_MYCMS') or exit('No permission resources.');
class ApiAdmin extends AdminController{

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
	
	// 获取客户端信息
	public function custom(){
		if(isset($_GET['lsize']) && isset($_GET['tsize'])){
			$_SESSION['custom']=array('lsize' => intval($_GET['lsize']),'tsize' => intval($_GET['tsize']));
		}
	}
	
	// 代码运行测试
	public function ctest(){
		$code=Param::get_para('code');
		$isphp=Param::get_para('isphp', 0);
		ob_start();
		($isphp ? eval($code) : eval('?>' . $code));
		$res=trim(ob_get_contents());
		ob_end_clean();
		$errortype=array('(?:Error)','(?:Warning)','(?:Parse\s+error)','(?:Notice)','(?:Core\s+Error)','(?:Core\s+Warning)','(?:Compile\s+Error)','(?:Compile\s+Warning)','(?:User\s+Error)','(?:User\s+Warning)','(?:User\s+Notice)','(?:Runtime\s+Notice)');
		if(preg_match("/<b>" . implode('|', $errortype) . "<\/b>:\s/i", $res)){
			die('0');
		}
		die('1');
	}
}

?>