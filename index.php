<?php
if(stripos($_SERVER['HTTP_HOST'], 'stwms.top') != false){
	header('Content-type: text/html; charset=utf-8');
	exit('网站建设中...');
}

define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
include 'stwms/base.php';
load::app();
?>
