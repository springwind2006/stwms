<?php
defined('IN_MYCMS') or exit('No permission resources.');
class TestbHome extends HomeController{
	public function d(){
		include template('b','test');
	}
}

?>