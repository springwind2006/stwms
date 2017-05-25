<?php

/**
 * 生成上传附件加密验证字符串
 *
 * @param string $args 字符串
 * @param string $hasMd5 是否进行md5加密
 * @return string 处理后的加密字符串
 */
function upload_key($args,$hasMd5=0){
	$pc_auth_key=md5(load::cfg('system', 'auth_key'));
	$authkey=md5((!$hasMd5 ? md5($args) : $args) . $pc_auth_key);
	return $authkey;
}

/**
 * 生成缩略图
 *
 * @param string $imgUrl 需要处理的字符串
 * @param number $maxWidth 限制宽度，默认为0（不限制）
 * @param number $maxHeight 限制高度，默认为0（不限制）
 * @param number $cutType 剪裁类型，为0不剪裁，为1缩小剪裁，为2放大剪裁
 * @param string $defaultImg 图片不存在默认图片
 * @param number $isGetRemot 是否获取远程图片
 * @return string 处理后的图片路径（相对于网站根目录）
 */
function thumb($imgUrl,$maxWidth=0,$maxHeight=0,$cutType=0,$defaultImg='',$isGetRemot=0){
	$smallpic=is_string($defaultImg) && $defaultImg ? $defaultImg : 'nopic.gif';
	$isGetRemot=is_int($defaultImg) ? $defaultImg : $isGetRemot;
	$defaultImg=strpos($smallpic, '/') === false ? STATIC_URL . 'common/images/' . $smallpic : $smallpic;
	if(empty($imgUrl)){
		return $defaultImg;
	}
	$isUpload=strpos($imgUrl, UPLOAD_URL) === 0;
	$oldimgurl=($isUpload ? substr($imgUrl, strlen(UPLOAD_URL)) : (strpos($imgUrl, ROOT_URL) === 0 ? substr($imgUrl, strlen(ROOT_URL)) : $imgUrl));
	$isRemot=strpos($oldimgurl, '://');
	
	// 此参数会导致强制执行，会对图片进行缩放
	$forceExec=!$isRemot ? ($cutType > 0) : $isGetRemot;
	
	$IMG_PATH=$isUpload || $isRemot ? UPLOAD_PATH : ROOT_PATH; // 本地文件的路径
	$IMG_URL=$isUpload || $isRemot ? UPLOAD_URL : ROOT_URL; // 本地文件的地址
	
	if(!extension_loaded('gd') || ($isRemot && !$isGetRemot)){
		// gd库没有加载或外链时不获取远程则不处理
		return $imgUrl;
	}
	$oldimg_path=($isRemot ? '' : $IMG_PATH) . $oldimgurl; // 最初文件路径
	$oldimg_url=($isRemot ? '' : $IMG_URL) . $oldimgurl; // 最初文件地址
	
	if($isRemot){
		$newDirname=str_replace('.', '_', substr(dirname($oldimgurl), $isRemot + 3));
		$newFilename=basename($oldimgurl);
		
		// 如果图片为动态地址，则尝试提取图片扩展名，否则直接图片文件中获取扩展名
		$type='';
		if(preg_match("/\.(jpg|jpeg|gif|png)/i", $newFilename, $m)){
			$type=$m[1];
		}
		if(empty($type) || strpos($newFilename, '?') !== false){
			if(empty($type)){
				$imgInfo=getimagesize($oldimgurl);
				if($imgInfo === false){
					return $defaultImg;
				}
				$type=substr($imgInfo['mime'], strpos($imgInfo['mime'], '/') + 1);
				if(!$maxWidth && !$maxHeight){
					list($maxWidth, $maxHeight)=$imgInfo;
				}
				unset($imgInfo);
			}
			$newFilename=(strpos($newFilename, '?') !== false ? base64($newFilename, 'encode', 1) : $newFilename) . '.' . $type;
		}
		
		if(is_file($IMG_PATH . $newDirname . '/' . $newFilename)){
			$srcInfo=getimagesize($IMG_PATH . $newDirname . '/' . $newFilename);
		}
		
		if(!$maxWidth && !$maxHeight){
			$newimgurl=$newDirname . '/' . $newFilename;
			list($maxWidth, $maxHeight)=(isset($srcInfo) ? $srcInfo : getimagesize($oldimgurl));
		}else if(isset($srcInfo) && $srcInfo[0] <= $maxWidth && $srcInfo[1] <= $maxHeight){
			$newimgurl=$newDirname . '/' . $newFilename;
		}else{
			if(strpos($newFilename, 'thumb') === 0 && strpos($newFilename, '_') == 6 && substr_count($newFilename, '_') >= 3){
				$offset=strpos($newFilename, '_') + 1;
				$offset=strpos($newFilename, '_', $offset) + 1;
				$newFilename=substr($newFilename, strpos($newFilename, '_', $offset) + 1);
			}
			$newimgurl=$newDirname . '/thumb' . $cutType . '_' . $maxWidth . '_' . $maxHeight . '_' . $newFilename;
		}
		unset($newDirname, $newFilename);
	}else{
		if(!is_file($oldimg_path)){
			return $defaultImg;
		}
		list($src_width, $src_height)=getimagesize($oldimg_path);
		if(($src_width == $maxWidth && $src_height == $maxHeight) || ($src_width <= $maxWidth && $src_height <= $maxHeight && !$forceExec)){
			return $oldimg_url;
		}
		$baseimgurl=basename($oldimgurl);
		if(strpos($baseimgurl, 'thumb') === 0 && strpos($baseimgurl, '_') == 6 && substr_count($baseimgurl, '_') >= 3){
			$offset=strpos($baseimgurl, '_') + 1;
			$offset=strpos($baseimgurl, '_', $offset) + 1;
			$baseimgurl=substr($baseimgurl, strpos($baseimgurl, '_', $offset) + 1);
		}
		$newimgurl=dirname($oldimgurl) . '/thumb' . $cutType . '_' . $maxWidth . '_' . $maxHeight . '_' . $baseimgurl;
	}
	if((!$maxWidth && !$maxHeight)){
		return $imgUrl;
	}
	
	if(is_file($IMG_PATH . $newimgurl)){
		return $IMG_URL . $newimgurl;
	}
	
	$imgObj=load::cls('Image', 1, 1);
	$res=$imgObj->thumbImg($oldimg_path, $IMG_PATH . $newimgurl, $maxWidth, $maxHeight, $cutType, $forceExec);
	return $res ? $IMG_URL . $newimgurl : ($res === false ? $oldimg_url : $defaultImg);
}

/**
 * 生成SEO
 *
 * @param number $catid 栏目ID
 * @param string $title 标题
 * @param string $description 描述
 * @param string $keyword 关键词
 * @param bool $ispage 是否单页模式处理
 * @return array SEO字符串
 */
function seo($catid=0,$title='',$description='',$keyword='',$ispage=false){
	if(!empty($title))
		$title=strip_tags($title);
	if(!empty($description))
		$description=strip_tags($description);
	if(!empty($keyword))
		$keyword=str_replace(' ', ',', strip_tags($keyword));
	
	$site_seo=getcache('setting', 'setting', 'array', 'seo');
	$site_info=getcache('setting', 'setting', 'array', 'web');
	$cat=array();
	$catid=intval($catid);
	if(!empty($catid)){
		$categorys=getcache('category', 'core', 'array', 'seo');
		$cat=$categorys[$catid];
	}
	$seo['site_title']=!empty($site_seo['title']) ? $site_seo['title'] : $site_info['name'];
	$seo['keyword']=!empty($keyword) ? $keyword : (!empty($cat['keywords']) ? $cat['keywords'] : $site_seo['keywords']);
	$seo['description']=isset($description) && !empty($description) ? $description : (!empty($cat['description']) ? $cat['description'] : (isset($site_seo['description']) && !empty($site_seo['description']) ? $site_seo['description'] : ''));
	if($ispage){
		$seo['title']=(isset($title) && !empty($title) ? $title . ' - ' : (!empty($cat['title']) ? $cat['title'] . ' - ' : (isset($cat['name']) && !empty($cat['name']) ? $cat['name'] . ' - ' : ''))) . $seo['site_title'];
	}else{
		$seo['title']=(isset($title) && !empty($title) ? $title . ' - ' : '') . (!empty($cat['title']) ? $cat['title'] . ' - ' : (isset($cat['name']) && !empty($cat['name']) ? $cat['name'] . ' - ' : '')) . $seo['site_title'];
	}
	foreach($seo as $k=>$v){
		$seo[$k]=str_replace(array("\n","\r"), '', $v);
	}
	return $seo;
}

/**
 * 输出字符串或json 参数
 *
 * @param string $msg 输出的字符串或数组（为数组时输出json）
 * @param number $isExit 输出后是否退出程序执行
 */
function alert($msg,$isExit=1){
	header("Content-Type:text/html;charset=utf-8");
	header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
	if(is_array($msg)){
		echo make_json($msg);
	}else{
		echo $msg;
	}
	if($isExit){
		exit(0);
	}
}

/**
 * 主要用于截取从0开始的任意长度的字符串(完整无乱码)
 *
 * @param string $sourcestr 待截取的字符串
 * @param number $cutlength 截取长度
 * @param bool $addfoot 是否添加"..."在末尾
 * @param bool &$isAdd 是否处理特殊字符，引用方式传入
 * @return string
 */
function cut_str($sourcestr,$cutlength=80,$addfoot=true,&$isAdd=false){
	$isAdd=false;
	if(function_exists('mb_substr')){
		return mb_substr($sourcestr, 0, $cutlength, 'utf-8') . ($addfoot && ($isAdd=strlen_utf8($sourcestr) > $cutlength) ? '...' : '');
	}elseif(function_exists('iconv_substr')){
		return iconv_substr($sourcestr, 0, $cutlength, 'utf-8') . ($addfoot && ($isAdd=strlen_utf8($sourcestr) > $cutlength) ? '...' : '');
	}
	$returnstr='';
	$i=0;
	$n=0.0;
	$str_length=strlen($sourcestr); // 字符串的字节数
	while(($n < $cutlength) and ($i < $str_length)){
		$temp_str=substr($sourcestr, $i, 1);
		$ascnum=ord($temp_str); // 得到字符串中第$i位字符的ASCII码
		if($ascnum >= 252){ // 如果ASCII位高与252
			$returnstr=$returnstr . substr($sourcestr, $i, 6); // 根据UTF-8编码规范，将6个连续的字符计为单个字符
			$i=$i + 6; // 实际Byte计为6
			$n++; // 字串长度计1
		}elseif($ascnum >= 248){ // 如果ASCII位高与248
			$returnstr=$returnstr . substr($sourcestr, $i, 5); // 根据UTF-8编码规范，将5个连续的字符计为单个字符
			$i=$i + 5; // 实际Byte计为5
			$n++; // 字串长度计1
		}elseif($ascnum >= 240){ // 如果ASCII位高与240
			$returnstr=$returnstr . substr($sourcestr, $i, 4); // 根据UTF-8编码规范，将4个连续的字符计为单个字符
			$i=$i + 4; // 实际Byte计为4
			$n++; // 字串长度计1
		}elseif($ascnum >= 224){ // 如果ASCII位高与224
			$returnstr=$returnstr . substr($sourcestr, $i, 3); // 根据UTF-8编码规范，将3个连续的字符计为单个字符
			$i=$i + 3; // 实际Byte计为3
			$n++; // 字串长度计1
		}elseif($ascnum >= 192){ // 如果ASCII位高与192
			$returnstr=$returnstr . substr($sourcestr, $i, 2); // 根据UTF-8编码规范，将2个连续的字符计为单个字符
			$i=$i + 2; // 实际Byte计为2
			$n++; // 字串长度计1
		}elseif($ascnum >= 65 and $ascnum <= 90 and $ascnum != 73){ // 如果是大写字母 I除外
			$returnstr=$returnstr . substr($sourcestr, $i, 1);
			$i=$i + 1; // 实际的Byte数仍计1个
			$n++; // 但考虑整体美观，大写字母计成一个高位字符
		}elseif(!(array_search($ascnum, array(37,38,64,109,119)) === FALSE)){ // %,&,@,m,w 字符按１个字符宽
			$returnstr=$returnstr . substr($sourcestr, $i, 1);
			$i=$i + 1; // 实际的Byte数仍计1个
			$n++; // 但考虑整体美观，这些字条计成一个高位字符
		}else{ // 其他情况下，包括小写字母和半角标点符号
			$returnstr=$returnstr . substr($sourcestr, $i, 1);
			$i=$i + 1; // 实际的Byte数计1个
			$n=$n + 0.5; // 其余的小写字母和半角标点等与半个高位字符宽...
		}
	}
	
	if(($isAdd=$i < $str_length) && $addfoot){
		$returnstr=$returnstr . '...';
	} // 超过长度时在尾处加上省略号
	return $returnstr;
}

/**
 * 统计utf-8字符长度
 *
 * @param string $str 原始html字符串
 * @return string
 */
function strlen_utf8($str){
	if(function_exists('mb_strlen')){
		return mb_strlen($str, 'utf-8');
	}
	$i=0;
	$count=0;
	$len=strlen($str);
	while($i < $len){
		$chr=ord($str[$i]);
		$count++;
		$i++;
		if($i >= $len)
			break;
		if($chr & 0x80){
			$chr<<=1;
			while($chr & 0x80){
				$i++;
				$chr<<=1;
			}
		}
	}
	return $count;
}

// //////////////////////////////////////////////////////////////
// //////////////////////////////////////////////////////////////
/**
 * ***********************栏目相关函数************************
 */

/**
 * 获取栏目信息
 *
 * @param string $para 栏目ID或栏目信息数组
 * @param string $ky 获取键名称
 * @param string $typeid 类型ID
 * @return array|string
 *
 */
function catInfo($para,$ky=NULL,$typeid=NULL){
	if(is_array($para)){
		return $ky == 'url' ? cat_url($para, $typeid) : (!is_null($ky) ? $para[$ky] : $para);
	}else{
		$categorys=& getLcache('category', 'core', 'array');
		return $ky == 'url' ? cat_url($categorys[$para], $typeid) : (is_null($ky) ? $categorys[$para] : $categorys[$para][$ky]);
	}
}

/**
 * 获取栏目信息
 *
 * @param string $cat 栏目ID或栏目信息数组
 * @param string $typeid 类型ID
 * @return array|string
 *
 */
function cat_url($cat,$typeid=NULL){
	static $html_root;
	if(is_null($html_root)){
		$html_root=ltrim(load::cfg('system', 'html_root'), '/');
	}
	if(is_array($cat)){
		if(isset($cat['type'])){ // 栏目数组缓存
			if($cat['type'] != 2){
				if(is_null($typeid)){
					return ROOT_URL . ($html_root && $cat['setting']['category_ishtml'] ? $html_root : '') . $cat['url'];
				}else{
					return $cat['setting']['category_ishtml'] ? load::controller('admin.urlrule')->_setListURL($cat, 0, $typeid) : url_par('typeid=' . $typeid, ROOT_URL . $cat['url']);
				}
			}else{
				return $cat['url'];
			}
		}else{ // 由栏目id组成的数组
			foreach($cat as $ky=>$catid){
				$cat[$ky]=cat_url($catid, $typeid);
			}
		}
	}else{
		return catInfo($cat, 'url', $typeid);
	}
}

/**
 * 获取子栏目
 *
 * @param number $pid 父栏目ID，默认为NULL（获取顶级目录）
 * @param number $ismenu 是否为显示菜单，为1时返回显示栏目，为-1时返回所有，为0时返回隐藏栏目，默认返回显示栏目
 * @param number $self 是否包括本身
 * @return array|string
 *
 */
function subcat($pid=NULL,$ismenu=1,$self=0){
	$categorys=& getLcache('category', 'core', 'array');
	$subcat=array();
	if($pid === NULL){
		foreach($categorys as $id=>$cat){
			if($cat['pid'] == '' && ($ismenu === -1 || $cat['ismenu'] == $ismenu)){
				$subcat[$id]=$cat;
				$subcat[$id]['url']=cat_url($cat);
			}
		}
	}else{
		if(isset($categorys[$pid])){
			if($self && ($ismenu === -1 || $categorys[$pid]['ismenu'] == $ismenu)){
				$subcat[$pid]=$categorys[$pid];
				$subcat[$pid]['url']=cat_url($categorys[$pid]);
			}
			if(!empty($categorys[$pid]['arrcid'])){
				$arrcidArr=explode(',', $categorys[$pid]['arrcid']);
				foreach($arrcidArr as $cid){
					$cid=intval($cid);
					if(isset($categorys[$cid]) && ($ismenu === -1 || $categorys[$cid]['ismenu'] == $ismenu)){
						$subcat[$cid]=$categorys[$cid];
						$subcat[$cid]['url']=cat_url($categorys[$cid]);
					}
				}
			}
		}
	}
	return $subcat;
}

/**
 * 根据指定id获取所有父栏目字段名组成的数组
 *
 * @param number $id 当前层级ID
 * @param array &$arr 引用的数组
 * @param string $type 获取的字段名，默认为"id"
 * @param string $spilter 分隔符，默认为","
 * @param bool $is_self 是否包括本身，默认为false
 * @return string
 *
 */
function get_parents($id,&$arr,$type='id',$spilter=',',$is_self=false){
	if(empty($id)){
		return '';
	}
	static $pids='';
	foreach($arr as $v){
		if($id == $v['id']){
			$pids.=$spilter . $v[$type];
			if($v['pid'] != ''){
				return get_parents($v['pid'], $arr, $type, $spilter, $is_self);
			}else{
				$cpid=$pids;
				$pids='';
				if(!$is_self){
					$cpid=substr($cpid, strlen($spilter));
					$spos=strpos($cpid, $spilter);
					if($spos !== false){
						$cpid=substr($cpid, $spos + strlen($spilter));
						$reArr=array_reverse(explode($spilter, $cpid));
						return implode($spilter, $reArr);
					}else{
						return '';
					}
				}else{
					$cpid=substr($cpid, strlen($spilter));
					$reArr=array_reverse(explode($spilter, $cpid));
					return implode($spilter, $reArr);
				}
			}
		}
	}
}

/**
 * 根据指定id获取所有子栏目id
 *
 * @param number $id 当前层级ID
 * @param array &$arr 引用的数组
 * @param bool $isOnlyChild 是否包括本身，默认为false
 * @return array
 *
 */
function get_childs($id,&$arr,$isOnlyChild=true){
	if($isOnlyChild){
		$cids=array();
		foreach($arr as $v){
			if($id == $v['pid']){
				$cids[]=$v['id'];
			}
		}
		return $cids;
	}else{
		$cArr=array();
		foreach($arr as $v){
			if($id == $v['pid']){
				$cArr[]=$v['id'];
				$cArr=array_merge($cArr, get_childs($v['id'], $arr, $isOnlyChild));
			}
		}
		return $cArr;
	}
}

/**
 * 根据根据表单类型返回特定的字段名称
 *
 * @param string||array &$paras 模型名称可以为字符串或数组
 * @param array &$filerArr 获取的表单类型可以为字段名称，默认为NULL（获取editor,title,keyword,description表单类型字段）
 * @return array
 *
 */
function get_formtype_fields(&$paras,$filerArr=NULL){
	static $cacheArr=array(); // 缓存加速
	$filerArr=is_null($filerArr) ? array('editor','title','keyword','description') : (is_array($filerArr) ? $filerArr : explode(',', $filerArr));
	
	if(is_array($paras)){
		$fields=&$paras;
		$model=$paras['model'] ? $paras['model'] : 'page';
		$key=md5($model . implode(',', $filerArr));
		if(isset($cacheArr[$key])){
			return $cacheArr[$key];
		}
	}else{
		$model=empty($paras) ? 'page' : $paras;
		$key=md5($model . implode(',', $filerArr));
		if(isset($cacheArr[$key])){
			return $cacheArr[$key];
		}
		$fields=getcache('field', 'model', 'array', $model);
	}
	$s_fields=array();
	
	foreach($fields as $fd_info){
		if(in_array($fd_info['formtype'], $filerArr)){
			if($fd_info['formtype'] == 'editor'){
				$s_fields['_type'][]=$fd_info['setting']['page_type'];
				$s_fields['_chars'][]=$fd_info['setting']['page_chars'];
			}
			$s_fields[$fd_info['formtype']][]=$fd_info['field'];
		}
	}
	if(isset($key)){
		$cacheArr[$key]=$s_fields;
	}
	return $s_fields;
}

// /////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////
/**
 * **********************URL处理系列函数*************************
 */

/**
 * 根据原始URL及新增参数重新URL
 *
 * @param array|string $par 新增的参数
 * @param string $url 原始URL
 * @param string $key 需要排除的URL参数
 * @return string 重新设置的URL
 */
function url_par($par,$url='',$key='page'){
	if($url == ''){
		$url=get_url(1);
	}
	$pos=strpos($url, '?');
	if($pos === false){
		$url.='?' . (is_array($par) ? http_build_query($par) : $par);
	}else{
		$querystring=substr(strstr($url, '?'), 1);
		$pars=explode('&', $querystring);
		$querystring='';
		foreach($pars as $kv){
			if(strpos($kv, '=') === false){
				$k=$kv;
				$v=false;
			}else{
				$k=substr($kv, 0, strpos($kv, '='));
				$v=substr($kv, strpos($kv, '=') + 1);
			}
			if($k != $key){
				$querystring.=$k . ($v === false ? '' : '=' . $v) . '&';
			}
		}
		
		$querystring=($querystring ? $querystring : '') . (is_array($par) ? http_build_query($par) : $par);
		$url=substr($url, 0, $pos) . (empty($querystring) ? '' : '?' . $querystring);
	}
	return $url;
}

/**
 * 获取当前页面URL地址
 *
 * @param int $type 需要获取的类型，取值及返回值意义 0->绝对地址，1->相对地址，2->不带参数绝对地址，3->不带参数相对地址
 * @return string 获取的URL，类型由$type决定
 */
function get_url($type=0){
	$sys_protocal=isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self=$_SERVER['PHP_SELF'] ? safe_replace($_SERVER['PHP_SELF']) : safe_replace($_SERVER['SCRIPT_NAME']);
	$path_info=isset($_SERVER['PATH_INFO']) ? safe_replace($_SERVER['PATH_INFO']) : '';
	$relate_url=isset($_SERVER['REQUEST_URI']) ? safe_replace($_SERVER['REQUEST_URI']) : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . safe_replace($_SERVER['QUERY_STRING']) : $path_info);
	$relate_url_nopara=strpos($relate_url, '?') === false ? $relate_url : substr($relate_url, 0, strpos($relate_url, '?'));
	switch($type){
		case 0:
			return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
			break;
		case 1:
			return $relate_url;
			break;
		case 2:
			return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url_nopara;
			break;
		case 3:
			return $relate_url_nopara;
			break;
	}
}

/**
 * 根据类名和方法获取处理URL
 *
 * @param string $c 控制类名称
 * @param string $a 控制方法名称
 * @param string|array $para 参数数组或序列参数
 * @param int $isOuter 是否为外部，定义为外部后将不使用ADMIN_INI为前置参数，用于获取前台URL
 * @return string 获取的URL
 */
function act_url($c,$a,$para='',$isOuter=0){
	if(empty($c)){
		$c=ROUTE_C;
	}
	if(empty($a)){
		$a=ROUTE_A;
	}
	$isOuter=is_int($para) ? $para : $isOuter;
	$paras=is_int($para) || empty($para) ? '' : (is_array($para) ? http_build_query($para) : $para);
	return ROOT_URL . SYS_ENTRY . '?' . ($isOuter || !defined('ADMIN_INI') ? '' : ADMIN_INI . '&') . 'c=' . $c . '&a=' . $a . (empty($paras) ? '' : '&' . $paras);
}

/**
 * 根据插件名和方法获取处理URL
 *
 * @param string $c 插件名称
 * @param string $a 插件方法名称
 * @param string|array $para 参数数组或序列参数
 * @param int $isOuter 是否为外部，定义为外部后将不使用ADMIN_INI为前置参数，用于获取前台URL
 * @return string 获取的URL
 */
function plugin_url($c,$a='index',$para='',$isOuter=0){
	$isOuter=is_int($para) ? $para : ($isOuter || !defined('ADMIN_INI'));
	$para=is_int($para) ? '' : $para;
	$paras=is_int($para) || empty($para) ? '' : (is_array($para) ? http_build_query($para) : $para);
	if($isOuter){
		$urls=& getLcache('plugin', 'core', 'array', 'url');
		if($urls[$c] !== ''){
			return ROOT_URL . $urls[$c] . $a . (empty($paras) ? '/' : '/?' . $paras);
		}
	}
	return ROOT_URL . SYS_ENTRY . '?' . ($isOuter ? '' : ADMIN_INI . '&') . 'plugin_c=' . $c . '&plugin_a=' . $a . (empty($paras) ? '' : '&' . $paras);
}
