<?php
// ////////////////////////////////////
// //////////公共函数库////////////////
// ////////////////////////////////////

/**
 * 日志记录
 *
 * @param unknown $content
 * @param string $file
 * @param number $is_append
 * @return number 写入日志字节数
 */
function lg($content,$file='log.txt',$is_append=0){
	if(is_array($content)){
		$content=var_export($content, true);
	}
	return $is_append ? file_put_contents(ROOT_PATH . $file, $content, FILE_APPEND) : file_put_contents(ROOT_PATH . $file, $content);
}

/**
 * 系统错误处理函数
 */
function my_error_handler(){
}

/**
 * 获取真实IP地址
 *
 * @return unknown
 */
function getIP(){
	if(getenv('HTTP_CLIENT_IP')){
		$ip=getenv('HTTP_CLIENT_IP');
	}else if(getenv('HTTP_X_FORWARDED_FOR')){
		$ip=getenv('HTTP_X_FORWARDED_FOR');
	}else if(getenv('REMOTE_ADDR')){
		$ip=getenv('REMOTE_ADDR');
	}else{
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

/**
 * 转换字节数为其他单位
 *
 * @param unknown $size
 * @param number $bits
 * @return string
 */
function sizeformat($size,$bits=2){
	$unit=array('B','KB','MB','GB','TB','PB');
	return round($size / pow(1024, ($i=floor(log($size, 1024)))), $bits) . $unit[$i];
}

/**
 * 转换字节数为其他单位
 *
 * @param unknown $tm
 * @return string
 */
function timeformat($tm){
	$unit=array('秒','分钟','小时','天');
	if($tm < 60 && $tm > 0){
		return $tm . $unit[0];
	}else if($tm >= 60 && $tm < 3600){
		return floor($tm / 60) . $unit[1] . timeformat($tm % 60);
	}else if($tm >= 3600 && $tm < (3600 * 24)){
		return floor($tm / 3600) . $unit[2] . timeformat($tm % 3600);
	}else if($tm > 0){
		return floor($tm / (3600 * 24)) . $unit[3] . timeformat($tm % (3600 * 24));
	}
}

/**
 * 获取系当前程序毫秒级时间
 *
 * @return number
 */
function get_mtime($str=''){
	list($usec, $sec)=explode(" ", ($str ? $str : microtime()));
	return ((float)$usec + (float)$sec);
}

/**
 * 获取内存使用大小
 *
 * @param number $isFormat
 * @return string
 */
function get_memory($isFormat=1){
	if(!function_exists('memory_get_usage')){
		return '未知大小';
	}else{
		return $isFormat ? sizeformat(memory_get_usage()) : memory_get_usage();
	}
}

/**
 * 使用特定function对数组中所有元素做处理
 *
 * @param array $array 需要处理的数组
 * @param string $func 对数组处理的函数
 * @param bool $applyKey 是否同时处理数组键名
 */
function array_map_deep($array,$func,$applyKey=false){
	foreach($array as $key=>$value){
		$array[$key]=is_array($value) ? array_map_deep($array[$key], $func, $applyKey) : $func($value);
		if($applyKey){
			$new_key=$func($key);
			if($new_key != $key){
				$array[$new_key]=$array[$key];
				unset($array[$key]);
			}
		}
	}
	return $array;
}


/**
 * 将数组转换为JSON字符串（兼容中文）
 *
 * @param array $array 需要输出的数组
 * @return string JSON字符串
 */
function make_json($array){
	$array=array_map_deep($array, 'urlencode', true);
	$json=json_encode($array);
	return urldecode($json);
}

/**
 * 改进后base64加密或解密
 *
 * @param array||string $data 数据
 * @param string $type 处理类型：ENCODE为加密，DECODE为解密，默认为ENCODE
 * @param array||string $filter 第一个参数为数组时，过滤的键名，默认为NULL
 * @param string $strip 是否对部分字符进行处理，处理后符合URL编码规范，默认为0（不处理）
 * @return unknown 处理后的数据
 */
function base64($data,$type='ENCODE',$filter=NULL,$strip=0){
	$type=strtoupper($type);
	$filterArr=is_array($filter) ? $filter : (is_string($filter) ? explode(',', $filter) : NULL);
	$strip=is_int($filter) ? $filter : $strip;
	if(is_array($data)){
		foreach($data as $ky=>$vl){
			if(empty($filterArr) || in_array($ky, $filterArr)){
				$data[$ky]=base64($vl, $type, $filter, $strip);
			}
		}
	}else{
		$searchs=array('=','/','+');
		$replaces=array('_','-','$');
		if($type != 'DECODE'){
			$data=base64_encode($data);
			if($strip){
				$data=str_replace($searchs, $replaces, $data);
			}
		}else{
			if($strip){
				$data=str_replace($replaces, $searchs, $data);
			}
			$data=base64_decode($data);
		}
	}
	return $data;
}

/**
 * 系统动态加密解密可以设置过期时间的字符串（通常用于授权）
 *
 * @param string $string 需要处理的字符串
 * @param string $operation 处理类型：ENCODE为加密，DECODE为解密，默认为ENCODE
 * @param string $key 自定义秘钥，默认为空
 * @param string $expiry 过期时间，默认为0，不限制
 * @return string 处理后的数据
 */
function sys_auth($string,$operation='ENCODE',$key='',$expiry=0){
	$operation=strtoupper($operation);
	$key_length=4;
	$key=md5($key != '' ? $key : load::cfg('system', 'auth_key'));
	$fixedkey=md5($key);
	$egiskeys=md5(substr($fixedkey, 16, 16));
	$runtokey=$key_length ? ($operation == 'ENCODE' ? substr(md5(microtime(true)), -$key_length) : substr($string, 0, $key_length)) : '';
	$keys=md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
	$string=$operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));
	
	$i=0;
	$result='';
	$string_length=strlen($string);
	for($i=0; $i < $string_length; $i++){
		$result.=chr(ord($string{$i}) ^ ord($keys{$i % 32}));
	}
	if($operation == 'ENCODE'){
		return $runtokey . str_replace('=', '', base64_encode($result));
	}else{
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $egiskeys), 0, 16)){
			return substr($result, 26);
		}else{
			return '';
		}
	}
}

/**
 * 系统加密解密的字符串
 *
 * @param string $string 需要处理的字符串
 * @param string $type 处理类型：1为加密，0为解密，默认为1
 * @param string $key 自定义秘钥，默认为空
 * @return string 处理后的数据
 */
function sys_crypt($string,$type=1,$key=''){
	$keys=md5($key !== '' ? $key : load::cfg('system', 'auth_key'));
	$string=$type ? (string)$string : base64($string, 'decode', 1);
	$string_length=strlen($string);
	$result='';
	for($i=0; $i < $string_length; $i++){
		$result.=chr(ord($string{$i}) ^ ord($keys{$i % 32}));
	}
	return $type ? base64($result, 'encode', 1) : $result;
}

/**
 * 处理界面模板（包括管理后台、前台和插件）
 *
 * @param string $template 模板文件名，不包括扩展名（.html）
 * @param string||bool $dir 为string时为模板在目录，相对于模板目录；为bool时是否强制调用（true：后台模板，false:前台模板）
 * @param string||bool $style 为string时为使用的风格；为bool时是否强制调用（true：后台模板，false:前台模板）
 * @param string||number $useType 使用的模板类型，为-1系统自动判断，为0前台模板，为1后台模板
 * @return string 返回模板路径
 */
function template($template='index',$dir='',$style='',$module='',$useType=-1){
	$isAdmin=!defined('STYLE_URL'); // 是否属于后台管理
	$isPlugin=isset($GLOBALS[PLUGIN_ID]); // 是否属于插件
	$useType=intval(is_bool($dir) ? $dir : (is_bool($style) ? $style : (is_bool($module) ? $module : $useType)));
	$tplExists=false;
	is_bool($dir)&&($dir='');
	is_bool($style)&&($style='');
	!is_string($module)&&($module='');
	($useType != -1)&&($isAdmin=$useType);
	
	if(strpos($template, '.') === false){
		$phpfile=$template . '.php';
		$template.='.html';
	}else{
		$phpfile=substr($template, 0, strrpos($template, '.')) . '.php';
	}
	
	if($useType != -1 || !$isPlugin){
		$dir=str_replace('/', CD, $dir);
		$style==='' && ($style=load::cfg('system', $isAdmin ? 'style' : 'template'));
		$tplRoot='template' . CD . ($isAdmin ? 'admin' : ($module!=='' ? $module : (defined('STYLE_MODULE') ? STYLE_MODULE : ROUTE_M)));
		
		$templatefile=str_replace(CD . CD, CD, CORE_PATH . $tplRoot . CD . $style . CD . $dir . CD . $template);
		$compiledtplfile=str_replace(CD . CD, CD, CACHE_PATH . $tplRoot . CD . $style . CD . $dir . CD . $phpfile);
		$tplExists=is_file($templatefile);
		if(!$tplExists){
			$templatefile=str_replace(CD . CD, CD, CORE_PATH . $tplRoot . CD . 'default' . CD . $dir . CD . $template);
			$compiledtplfile=str_replace(CD . CD, CD, CACHE_PATH . $tplRoot . CD . 'default' . CD . $dir . CD . $phpfile);
			$tplExists=is_file($templatefile);
		}
	}else{
		$dir=str_replace('/', CD, $dir);
		$templatefile=str_replace(CD . CD, CD, CORE_PATH . 'plugin' . CD . $GLOBALS[PLUGIN_ID] . CD . 'template' . CD . $dir . CD . $template);
		$tplExists=is_file($templatefile);
		$compiledtplfile=str_replace(CD . CD, CD, CACHE_PATH . 'template' . CD . 'plugin' . CD . $GLOBALS[PLUGIN_ID] . CD . $dir . CD . $phpfile);
	}
	
	if($tplExists){
		if(!is_file($compiledtplfile) || (@filemtime($templatefile) > @filemtime($compiledtplfile))){
			$template_cache=load::cls('TemplateCache');
			$template_cache->template_compile($templatefile, $compiledtplfile);
		}
	}
	return $compiledtplfile;
}


// //////////////////////////////////////////////////////////////
/**
 * ***********************字符串处理函数**********************
 */

/**
 * 取得文件扩展名（小写）
 *
 * @param string $filename 文件名
 * @return string 扩展名
 */
function fileext($filename){
	return strtolower(trim(substr(strrchr($filename, '.'), 1)));
}

/**
 * 从提供的字符串中，产生随机字符串
 *
 * @param number $length 输出长度
 * @param string $chars 范围字符串，默认为：0123456789
 * @return string 生成的字符串
 */
function random($length,$chars='0123456789'){
	$hash='';
	$max=strlen($chars) - 1;
	for($i=0; $i < $length; $i++){
		$hash.=$chars[mt_rand(0, $max)];
	}
	return $hash;
}

/**
 * 将字符串转换为数组
 *
 * @param string $data
 * @return array
 */
function string2array($data){
	if(is_array($data)){
		return $data;
	}
	if($data == ''){
		return array();
	}
	@eval("\$array = $data;");
	return $array;
}

/**
 * 将数组转换为字符串
 *
 * @param array $data
 * @return string
 */
function array2string($data,$isformdata=1){
	if($data == ''){
		return '';
	}
	if(!is_array($data)){
		return $data;
	}
	if($isformdata){
		$data=slashes($data, 0);
	}
	return var_export($data, TRUE);
}

/**
 * 将数组转换数据为字符串表示的形式，支持其中变量设置
 *
 * @param array $data
 * @return string
 */
function array2html($data){
	if(is_array($data)){
		$str='array(';
		foreach($data as $key=>$val){
			if(is_string($key)){
				$key='\'' . $key . '\'';
			}
			if(is_array($val)){
				$str.=$key . '=>' . array2html($val) . ',';
			}else{
				if(strpos($val, '$') === 0){
					$str.=$key . '=>' . $val . ',';
				}else{
					if(is_string($val)){
						if(strpos($val, '$') !== false){
							$val='"' . addslashes($val) . '"';
						}else{
							$val='\'' . addslashes($val) . '\'';
						}
					}
					$str.=$key . '=>' . $val . ',';
				}
			}
		}
		return $str . ')';
	}
	return false;
}

/**
 * 返回经addslashes处理过的字符串或数组
 * 
 * @param unknown $string
 * @param number $isadd
 * @return string|unknown
 */
function slashes($string,$isadd=1){
	if(!is_array($string)){
		return $isadd ? addslashes($string) : stripslashes($string);
	}
	foreach($string as $key=>$val){
		$string[$key]=slashes($val, $isadd);
	}
	return $string;
}

/**
 * 转义 javascript 代码标记
 *
 * @param string $str 原始html字符串
 * @return string
 */
function trim_script($str){
	if(is_array($str)){
		foreach($str as $key=>$val){
			$str[$key]=trim_script($val);
		}
	}else{
		$str=preg_replace('/\<([\/]?)script([^\>]*?)\>/si', '&lt;\\1script\\2&gt;', $str);
		$str=preg_replace('/\<([\/]?)iframe([^\>]*?)\>/si', '&lt;\\1iframe\\2&gt;', $str);
		$str=preg_replace('/\<([\/]?)frame([^\>]*?)\>/si', '&lt;\\1frame\\2&gt;', $str);
		$str=preg_replace('/]]\>/si', ']] >', $str);
	}
	return $str;
}

/**
 * 安全过滤函数
 *
 * @param array||string $string
 * @return string
 */
function safe_replace($string){
	if(!is_array($string)){
		$string=str_replace('%20', '', $string);
		$string=str_replace('%27', '', $string);
		$string=str_replace('%2527', '', $string);
		$string=str_replace('*', '', $string);
		$string=str_replace('"', '&quot;', $string);
		$string=str_replace("'", '', $string);
		$string=str_replace('"', '', $string);
		$string=str_replace('`', '', $string);
		$string=str_replace(';', '', $string);
		$string=str_replace('<', '&lt;', $string);
		$string=str_replace('>', '&gt;', $string);
		$string=str_replace("{", '', $string);
		$string=str_replace('}', '', $string);
		$string=str_replace('\\', '', $string);
	}else{
		foreach($string as $key=>$val){
			$string[$key]=safe_replace($val);
		}
	}
	return $string;
}


/**
 * 产生分页HTML代码
 *
 * @param array||int $info 类型为array时为列表分页，类型为int时为详细页分页的页面总数，array列如：array( 'type'=>'cpg',@分页参数 'total'=> $totalrows,@记录总数 'cPage'=> $currentpage,@当前页数 'size'=> $pagesize,@每页大小 'url'=> $curl @分页url ,'pFunc'=>'showPage(\'?\')' @js回调函数)
 * @param int $setpages 设置显示的页数数量（列表页）或当前页数（详细页分页）
 * @param string $urlrule URL规则（列表页）或所有页面URL数组（详细页分页）
 * @param array $array 参数替换数组 （详细页无此参数）
 * @return string 返回分页的HTML代码（详细页无此参数）
 */
function getpage($info,$setpages=10,$urlrule='',$array=array()){
	load::func('page');
	return is_int($info) ? get_content_pages($info, $setpages, $urlrule) : get_list_pages($info, $setpages, $urlrule, $array);
}

// /////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////
/**
 * ***********************缓存系列函数***************************
 */

/**
 * 获取模板数据缓存
 *
 * @param string $name 缓存名称
 * @param int $times 保存的时间
 * @return unknown
 *
 */
function tpl_cache($name,$times=0){
	$filepath='tpl_data';
	$info=getcacheinfo($name, $filepath);
	if(NOW_TIME - $info['filemtime'] >= $times){
		return false;
	}else{
		return getcache($name, $filepath, 'array');
	}
}

/**
 * 设置缓存
 *
 * @param string $name 缓存名称
 * @param string $data 缓存数据
 * @param string $cDir 缓存目录
 * @param string $dType 缓存类型
 * @return unknown
 *
 */
function setcache($name,$data,$cDir,$dType='file'){
	$filepath=CACHE_PATH . str_replace('/', CD, $cDir) . CD;
	if(!is_dir($filepath)){
		@mkdir($filepath, 0777, true);
	}
	if($dType == 'array'){
		$data="<?php\n\rreturn " . var_export($data, true) . ";\n\r?>";
	}elseif($dType == 'serialize'){
		$data=serialize($data);
	}
	return file_put_contents($filepath . $name . '.cache.php', $data, LOCK_EX);
}

/**
 * 此函数与getcache用法相同，但返回的是引用，此引用的资源在整个系统运行中会一直存在，慎用！
 *
 * @param string $name 缓存名称
 * @param string $cDir 缓存目录
 * @param string $dType 缓存类型
 * @param string $addtions 自动缓存参数
 * @param number $autoCache 是否自动缓存
 * @return unknown
 *
 */
function &getLcache($name,$cDir,$dType='file',$addtions=NULL,$autoCache=1){
	static $cacheArr=array();
	$cacheKey=$name . (is_null($addtions) ? '' : '_' . $addtions);
	if(!isset($cacheArr[$cacheKey])){
		$cacheArr[$cacheKey]=getcache($name, $cDir, $dType, $addtions, $autoCache);
	}
	return $cacheArr[$cacheKey];
}

/**
 * 获取缓存，如果缓存不存在，尝试调用缓存名称 对应的控制器的_fresh方法，重新生成缓存并返回
 *
 * @param string $name 缓存名称
 * @param string $cDir 缓存目录
 * @param string $dType 缓存类型
 * @param string $addtions 缓存附加信息
 * @param number $autoCache 缓存类型
 * @return unknown
 *
 */
function getcache($name,$cDir,$dType='file',$addtions=NULL,$autoCache=1){
	$cDir=str_replace('/', CD, $cDir);
	$cacheNa=$name . (is_null($addtions) ? '' : '_' . $addtions);
	$filename=CACHE_PATH . $cDir . CD . $cacheNa . '.cache.php';
	if(!is_file($filename) && $autoCache){
		$cObj=load::controller($name);
		
		// 如果外部缓存控制器不存在，尝试使用管理模块同名控制器
		if((!is_object($cObj) || !method_exists($cObj, '_fresh')) && ROUTE_M != 'admin'){
			unset($cObj);
			$cObj=load::controller('admin.' . $name);
		}
		if(is_object($cObj) && method_exists($cObj, '_fresh')){
			if(is_null($addtions)){
				$cObj->_fresh();
			}else{
				$cObj->_fresh($addtions);
			}
			return getcache($name, $cDir, $dType, $addtions, 0);
		}
	}
	// 当数据类型为数组或序列化对象时，文件不存在则返回
	if(!is_file($filename) && ($dType == 'array' || $dType == 'serialize')){
		return false;
	}
	if($dType == 'array'){
		return include ($filename);
	}elseif($dType == 'serialize'){
		return unserialize(file_get_contents($filename));
	}else{
		return $filename;
	}
}

/**
 * 删除缓存，并返回删除结果
 *
 * @param string $name 缓存名称
 * @param string $cDir 缓存目录
 * @param string $addtions 缓存附加信息
 * @return bool
 *
 */
function delcache($name,$cDir,$addtions){
	$cDir=str_replace('/', CD, $cDir);
	$filename=CACHE_PATH . $cDir . CD . $name . (is_null($addtions) ? '' : '_' . $addtions) . '.cache.php';
	if(!is_file($filename)){
		return false;
	}
	@unlink($filename);
	@rmdir(dirname($filename));
	return true;
}

/**
 * 获取缓存信息
 *
 * @param string $name 缓存名称
 * @param string $cDir 缓存目录
 * @param string $addtions 缓存附加信息
 * @return bool
 *
 */
function getcacheinfo($name,$cDir,$addtions=''){
	if($addtions){
		$name=$name . '_' . $addtions;
	}
	$filename=CACHE_PATH . str_replace('/', CD, $cDir) . CD . $name . '.cache.php';
	if(is_file($filename)){
		$res=array();
		$res['filename']=$name . '.cache.php';
		$res['filepath']=dirname($filename) . CD;
		$res['filectime']=filectime($filename);
		$res['filemtime']=filemtime($filename);
		$res['filesize']=filesize($filename);
		return $res;
	}else{
		return false;
	}
}

/**
 * 显示消息
 * 
 * @param unknown $message 消息
 * @param string $url_forward 返回URL
 * @param number $ms 停留秒数
 * @param string $dialog 对话框
 * @param string $returnjs 执行JS
 */
function showmessage($message,$url_forward='goback',$ms=1250,$dialog='',$returnjs=''){
	include template('message', true);
	exit(0);
}

/**
 * URL重定向
 *
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url,$time=0,$msg=''){
	// 多行URL地址支持
	$url=str_replace(array("\n","\r"), '', $url);
	if(empty($msg)){
		$msg="系统将在{$time}秒之后自动跳转到{$url}！";
	}
	if(!headers_sent()){
		// redirect
		if(0 === $time){
			header('Location: ' . $url);
		}else{
			header("refresh:{$time};url={$url}");
			echo ($msg);
		}
		exit();
	}else{
		$str="<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
		if($time != 0){
			$str.=$msg;
		}
		exit($str);
	}
}

/**
 * 模型快速操作
 * 
 * @param string $tb 需要操作的表
 * @return DbExtend 数据库拓展模型
 */
function M($tb=null){
	static $db;
	if(is_null($db)){
		load::cls('DbExtend', 0);
		$db=new DbExtend(load::db());
	}
	$db->clear();
	return $db->table($tb);
}

/**
 * URL组装 支持不同URL模式
 * 
 * @param string $url URL表达式，格式：'[模块/控制器/操作#锚点@域名]?参数1=值1&参数2=值2...'
 * @param string|array $vars 传入的参数，支持数组和字符串
 * @param boolean $domain 是否显示域名
 * @return string
 */
function U($url='',$vars='',$domain=false){
	// 解析URL
	$info=parse_url($url);
	$url=!empty($info['path']) ? $info['path'] : ROUTE_A;
	if(isset($info['fragment'])){ // 解析锚点
		$anchor=$info['fragment'];
		if(false !== strpos($anchor, '?')){ // 解析参数
			list($anchor, $info['query'])=explode('?', $anchor, 2);
		}
		if(false !== strpos($anchor, '@')){ // 解析域名
			list($anchor, $host)=explode('@', $anchor, 2);
		}
	}elseif(false !== strpos($url, '@')){ // 解析域名
		list($url, $host)=explode('@', $info['path'], 2);
	}
	// 解析子域名
	if(isset($host)){
		$domain=$host . (strpos($host, '.') ? '' : strstr($_SERVER['HTTP_HOST'], '.'));
	}elseif($domain === true){
		$domain=$_SERVER['HTTP_HOST'];
	}
	
	// 解析参数
	if(is_string($vars)){ // aaa=1&bbb=2 转换成数组
		parse_str($vars, $vars);
	}elseif(!is_array($vars)){
		$vars=array();
	}
	if(isset($info['query'])){ // 解析地址里面参数 合并到vars
		parse_str($info['query'], $params);
		$vars=array_merge($params, $vars);
	}
	
	// URL组装
	$depr='/';
	
	if($url){
		if(0 === strpos($url, '/')){ // 定义路由
			$route=true;
			$url=substr($url, 1);
		}else{
			// 解析模块、控制器和操作
			$url=trim($url, $depr);
			$path=explode($depr, $url);
			$var=array();
			$varModule='m';
			$varController='c';
			$varAction='a';
			$var[$varAction]=!empty($path) ? array_pop($path) : ROUTE_A;
			$var[$varController]=!empty($path) ? array_pop($path) : ROUTE_C;
			
			$module='';
			if(!empty($path)){
				$var[$varModule]=implode($depr, $path);
			}
			if(isset($var[$varModule])){
				$module=$var[$varModule];
				unset($var[$varModule]);
			}
		}
	}
	
	$url=ROOT_FULL_URL . '?' . ((defined('BIND_MODULE') && BIND_MODULE == $module)||$module==='' ? '' : 'm=' . $module . '&') . http_build_query(array_reverse($var));
	if(!empty($vars)){
		unset($vars[$varModule]);
		$vars=http_build_query($vars);
		$url.='&' . $vars;
	}
	if(isset($anchor)){
		$url.='#' . $anchor;
	}
	if($domain){
		$url=SITE_PROTOCOL . $domain . $url;
	}
	return $url;
}


/**
 * 获取系统配置信息
 * 
 * @param string $key 获取配置的名称，可以使用“配置名.键名1.键名2”的格式
 * @param string $default 默认值
 * @return mixed 配置信息
 */
function C($key='',$default=''){
	if(is_string($key)){
		$keys=explode('.',$key);
		count($keys) < 2 && array_unshift($keys, 'system');
		$len=count($keys);
		if($len < 3){
			return load::cfg($keys[0],$keys[1],$default);
		}else{
			$res=load::cfg($keys[0],$keys[1],$default);
			if(is_array($res)){
				for($i=2;$i< $len;$i++){
					if(!is_array($res) || !isset($res[$keys[$i]])){
						$res=$default;
						break;
					}
					$res=$res[$keys[$i]];
				}
			}
			return $res;
		}
	}elseif(is_array($key)){
		return load::cfg('system',$key);
	}
	return null;
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name='',$value='') {
	static $prefix=NULL;
	is_null($prefix)&&($prefix=C('session_prefix'));
	
	if(is_array($name)) { // session初始化 在session_start 之前调用
		isset($name['prefix']) && ($prefix=$name['prefix']);
		
		if(C('var_session_id') && isset($_REQUEST[C('var_session_id')])){
			session_id($_REQUEST[C('var_session_id')]);
		}elseif(isset($name['id'])) {
			session_id($name['id']);
		}
		
		ini_set('session.auto_start', 0);
		
		isset($name['name']) && session_name($name['name']);
		isset($name['path']) && session_save_path($name['path']);
		isset($name['domain']) && ini_set('session.cookie_domain', $name['domain']);
		if(isset($name['expire'])){
			ini_set('session.gc_maxlifetime', $name['expire']);
			ini_set('session.cookie_lifetime', $name['expire']);
		}
		isset($name['use_trans_sid']) && ini_set('session.use_trans_sid', $name['use_trans_sid']?1:0);
		isset($name['use_cookies']) && ini_set('session.use_cookies', $name['use_cookies']?1:0);
		isset($name['cache_limiter']) && session_cache_limiter($name['cache_limiter']);
		isset($name['cache_expire']) && session_cache_expire($name['cache_expire']);
		
		if($name['type']) { // 读取session驱动
			$class = ucwords(strtolower($name['type']));
			$path = CORE_PATH.'lib/driver/session/'.$class.'.class.php';
			if(is_file($path)){
				include_once $path;
				$hander=new $class();
				session_set_save_handler(
						array(&$hander,'open'),
						array(&$hander,'close'),
						array(&$hander,'read'),
						array(&$hander,'write'),
						array(&$hander,'destroy'),
						array(&$hander,'gc')
				);
			}
		}
		// 启动session
		session_start();
	}elseif('' === $value){
		if(''===$name){
			// 获取全部的session
			return $prefix ? $_SESSION[$prefix] : $_SESSION;
		}elseif(0===strpos($name,'[')) { // session 操作
			if('[pause]'==$name){ // 暂停session
				session_write_close();
			}elseif('[start]'==$name){ // 启动session
				session_start();
			}elseif('[destroy]'==$name){ // 销毁session
				$_SESSION =  array();
				session_unset();
				session_destroy();
			}elseif('[regenerate]'==$name){ // 重新生成id
				session_regenerate_id();
			}
		}elseif(0===strpos($name,'?')){ // 检查session
			$name   =  substr($name,1);
			if(strpos($name,'.')){ // 支持数组
				list($name1,$name2) =   explode('.',$name,2);
				return $prefix?isset($_SESSION[$prefix][$name1][$name2]):isset($_SESSION[$name1][$name2]);
			}else{
				return $prefix?isset($_SESSION[$prefix][$name]):isset($_SESSION[$name]);
			}
		}elseif(is_null($name)){ // 清空session
			if($prefix) {
				unset($_SESSION[$prefix]);
			}else{
				$_SESSION = array();
			}
		}elseif($prefix){ // 获取session
			if(strpos($name,'.')){
				list($name1,$name2) =   explode('.',$name,2);
				return isset($_SESSION[$prefix][$name1][$name2])?$_SESSION[$prefix][$name1][$name2]:null;
			}else{
				return isset($_SESSION[$prefix][$name])?$_SESSION[$prefix][$name]:null;
			}
		}else{
			if(strpos($name,'.')){
				list($name1,$name2) =   explode('.',$name,2);
				return isset($_SESSION[$name1][$name2])?$_SESSION[$name1][$name2]:null;
			}else{
				return isset($_SESSION[$name])?$_SESSION[$name]:null;
			}
		}
	}elseif(is_null($value)){ // 删除session
		if(strpos($name,'.')){
			list($name1,$name2) =   explode('.',$name,2);
			if($prefix){
				unset($_SESSION[$prefix][$name1][$name2]);
			}else{
				unset($_SESSION[$name1][$name2]);
			}
		}else{
			if($prefix){
				unset($_SESSION[$prefix][$name]);
			}else{
				unset($_SESSION[$name]);
			}
		}
	}else{ // 设置session
		if(strpos($name,'.')){
			list($name1,$name2) =   explode('.',$name);
			if($prefix){
				$_SESSION[$prefix][$name1][$name2]   =  $value;
			}else{
				$_SESSION[$name1][$name2]  =  $value;
			}
		}else{
			if($prefix){
				$_SESSION[$prefix][$name]   =  $value;
			}else{
				$_SESSION[$name]  =  $value;
			}
		}
	}
	return null;
}

/**
 * Cookie 设置、获取、删除
 * @param string $name cookie名称
 * @param mixed $value cookie值
 * @param mixed $option cookie参数
 * @return mixed
 */
function cookie($name='', $value='', $option=null) {
	// 默认设置
	$config = array(
			'prefix'    =>  C('cookie_pre'), // cookie 名称前缀
			'expire'    =>  C('cookie_ttl',0), // cookie 保存时间
			'path'      =>  C('cookie_path'), // cookie 保存路径
			'domain'    =>  C('cookie_domain'), // cookie 有效域名
			'secure'    =>  C('cookie_secure',false), //  cookie 启用安全传输
			'httponly'  =>  C('cookie_httponly',false), // httponly设置
	);
	// 参数设置(会覆盖黙认设置)
	if (!is_null($option)) {
		if (is_numeric($option)){
			$option = array('expire' => $option);
		}elseif (is_string($option)){
			parse_str($option, $option);
		}
		$config = array_merge($config, array_change_key_case($option));
	}
	if(!empty($config['httponly'])){
		ini_set('session.cookie_httponly', 1);
	}
	// 清除指定前缀的所有cookie
	if (is_null($name)) {
		if (empty($_COOKIE)){
			return null;
		}
		// 要删除的cookie前缀，不指定则删除config设置的指定前缀
		$prefix = empty($value) ? $config['prefix'] : $value;
		if (!empty($prefix)) {// 如果前缀为空字符串将不作处理直接返回
			foreach ($_COOKIE as $key => $val) {
				if (0 === stripos($key, $prefix)) {
					setcookie($key, '', time() - 3600, $config['path'], $config['domain'],$config['secure'],$config['httponly']);
					unset($_COOKIE[$key]);
				}
			}
		}
		return null;
	}elseif('' === $name){
		// 获取全部的cookie
		return $_COOKIE;
	}
	$name = $config['prefix'] . str_replace('.', '_', $name);
	if ('' === $value) {
		if(isset($_COOKIE[$name])){
			$value = sys_crypt($_COOKIE[$name],0);
			if(0===strpos($value,'stwms:')){
				$ret  =   substr($value,6);
				$ret=json_decode(MAGIC_QUOTES_GPC?stripslashes($ret):$ret,true);
				return is_array($ret) ? array_map_deep($ret, 'urldecode',true) : $value;
			}else{
				return $value;
			}
		}else{
			return null;
		}
	} else {
		if (is_null($value)) {
			setcookie($name, '', time() - 3600, $config['path'], $config['domain'],$config['secure'],$config['httponly']);
			unset($_COOKIE[$name]); // 删除指定cookie
		} else {
			// 设置cookie
			if(is_array($value)){
				$value  = 'stwms:'.json_encode(array_map_deep($value,'urlencode',true));
			}
			$value=sys_crypt($value,1);
			
			$expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
			setcookie($name, $value, $expire, $config['path'], $config['domain'],$config['secure'],$config['httponly']);
			$_COOKIE[$name] = $value;
		}
	}
	return null;
}

/**
 * memcache操作函数
 * 
 * @param string $key
 * @param mixed $val
 * @param number $expire 过期时间，默认为0，不过期
 * @param bool $is_compress 是否启用压缩，默认为false
 * @return unknown|Ambigous <NULL, unknown>|NULL
 */
function memcache($key,$val='',$expire=0,$is_compress=false){
	static $mem=null;

	//初始化缓存对象
	if(is_null($mem)&&class_exists('Memcache',false)){
		$mem=new Memcache;
		$config=explode(':',C('MEMCACHE_HOST','127.0.0.1:11211'));
		//此处为memcache的连接主机与端口
		if(!$mem->connect($config[0],$config[1])){
			$mem=null;
			$mem=false;
		}
	}

	if(is_object($mem)){
		if(strpos($key,'.')){
			$keyArr=explode('.',$key);
			$key=$keyArr[0];
			$skey=$keyArr[1];
		}
		
		$key=C('MEMCACHE_PRE',(defined('STYLE_MODULE') ? STYLE_MODULE : ROUTE_M)).$key;
		if($val===''){ //获取值
			return is_array($result=$mem->get($key))&&isset($skey)&&$skey!=='' ? $result[$skey] : $result;
		}elseif(is_null($val)){ //删除值
			if(is_array($result=$mem->get($key))&&isset($skey)&&$skey!==''){
				unset($result[$skey]);
				return memcache($key,$result,$expire,$is_compress);
			}else{
				return $mem->delete($key);
			}
		}else{ //设置值
			if(isset($skey)){
				if(!is_array($result=$mem->get($key))){
					$result=array();
				}
				if($skey!==''){
					$result[$skey]=$val;
				}else{
					$result[]=$val;
				}
				return memcache($key,$result,$expire,$is_compress);
			}else{
				return $mem->set($key,$val,$is_compress,$expire);
			}
		}
	}
	return null;
}

/**
 * 导入静态文件路径
 * 如：'js/show.js@daoke:home'，则返回statics/home/daoke/js/show.js
 * 
 * @param unknown $file 文件模式路径
 * @param string $type 文件类型
 * @param bool $check 是否检查存在，如果不存在返回空
 * @param string $default='default' 如果不存在，默认检查的风格名称
 */
function import($file,$type='',$check=false,$default='default'){
	if(($pos=strpos($file,'/'))!==0){
		$module='';
		$style='';
		if(strpos($file,'@')!=false){
			$styles=explode('@', $file,2);
			$file=$styles[0];
			$styles=explode(':',$styles[1],2);
			if(count($styles)==1){
				$style=$styles[0];
				$module=(defined('STYLE_MODULE') ? STYLE_MODULE : ROUTE_M);
			}else{
				$style=$styles[0];
				$module=$styles[1];
			}
		}else{
			$module=(defined('STYLE_MODULE') ? STYLE_MODULE : ROUTE_M);
			$style=C(!defined('STYLE_URL') ? 'style' : 'template');
		}
		
		if($file===''){return '';}
		if(!$pos&&($ext=($type!=='' ? $type :fileext($file)))){ //单个文件导入，根据文件名（如果为js,css,images文件）自动识别上级目录 
			if($ext=='js'||$ext=='css'){
				$file=$ext.'/'.$file;
			}elseif($ext=='jpg'||$ext=='png'||$ext=='gif'||$ext=='jpeg'||$ext=='bmp'){
				$file='images/'.$file;
			}
		}
		if($check){
			if(!is_file(STATIC_PATH.$module.CD.($style==='' ? '' : $style.CD).$file)){
				return is_file(STATIC_PATH.$module.CD.$default.CD.$file) ? 
						STATIC_URL.$module.'/default/'.$file:'';
			}
		}
		return STATIC_URL.$module.'/'.($style==='' ? '' : $style.'/').$file;
	}else{
		return STATIC_URL.ltrim($file,'/');
	}
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id   数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function xml_encode($data, $root='root', $item='item', $attr='', $id='id', $encoding='utf-8') {
	if(is_array($attr)){
		$_attr = array();
		foreach ($attr as $key => $value) {
			$_attr[] = "{$key}=\"{$value}\"";
		}
		$attr = implode(' ', $_attr);
	}
	$attr   = trim($attr);
	$attr   = empty($attr) ? '' : " {$attr}";
	$xml    = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
	$xml   .= "<{$root}{$attr}>";
	$xml   .= data_to_xml($data, $item, $id);
	$xml   .= "</{$root}>";
	return $xml;
}

/**
 * 数据XML编码
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 * @return string
 */
function data_to_xml($data, $item='item', $id='id') {
	$xml = $attr = '';
	foreach ($data as $key => $val) {
		if(is_numeric($key)){
			$id && $attr = " {$id}=\"{$key}\"";
			$key  = $item;
		}
		$xml    .=  "<{$key}{$attr}>";
		$xml    .=  (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
		$xml    .=  "</{$key}>";
	}
	return $xml;
}
?>