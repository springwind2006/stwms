<?php

/**
 * 获取文件大小，并根据大小转换为相应kb mb
 * @param unknown $fpath
 * @return string
 */
function format_size($fpath){
	$fbytes=filesize($fpath);
	if($fbytes < 1024){
		return '1KB';
	}
	if($fbytes < 1024 * 1024){
		$fbytes=round($fbytes / 1024);
		return $fbytes . 'KB';
	}
	$fbytes=round($fbytes / (1024 * 1024), 2);
	return $fbytes . 'MB';
}

/**
 * 从指定目录根据知道路径读取文件列表
 *
 * @param string $dir
 * @param string $ftype
 * @return multitype:string
 */
function file_list($dir='.',$ftype='*'){
	$farr=array();
	if($handle=opendir($dir)){
		while(false !== ($file=readdir($handle))){
			if($file != '.' && $file != '..'){
				$farr[]=$file;
			}
		}
		closedir($handle);
	}
	if($ftype != '*'){
		$reArr=array();
		foreach($farr as $fv){
			if(preg_match("/{$ftype}\s*$/i", $fv) && is_file($fv)){
				$reArr[]=$fv;
			}
		}
		return $reArr;
	}
	return $farr;
}
?>