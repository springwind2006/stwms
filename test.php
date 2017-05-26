<?php
function fatal_handler() {
	$error = var_export(error_get_last(),true);
	exit('<pre>'.$error.'</pre>');
}
register_shutdown_function('fatal_handler');

function __autoload($class){
	$ret=array_filter(preg_split("/(?=[A-Z])/", $class));
	var_dump($ret);
}
try{
	echo HomeCCntroller::name;
}catch (Exception $e){
	
}
?>
