<?php
defined('IN_MYCMS') or exit('No permission resources.');
!IS_RUNTIME && load::func('content');
class HomeController extends Controller{
	public $site;
	protected $xmlDbName=null;
	protected $xmlDbPath;
	private $xmlDb=null;
	private $db=null;
	
	public function __construct(){
		$this->xmlDbName=ROUTE_C;
		$this->xmlDbPath='core';
		$this->site=getcache('setting', 'setting', 'array', 'web');
		if(!$this->site['status']){
			$this->showmessage($this->site['notice'],'auto');
		}
	}
	
	public function _default($c,$a){
		if(($file=template($c,$a, false)) && is_file($file)){
			$SEO=seo();
			$SITE=&$this->site;
			include($file);
		}
	}
	
	public function showmessage($message,$url_forward='goback',$ms=1250,$dialog='',$returnjs=''){
		$file=template('message', false);
		if(is_file($filename)){
			$SITE=&$this->site;
			include($file);
		}else{
			echo $message;
		}
	}
	
	/**
	 * 获取系统数据库
	 *
	 * @param string $dbconn
	 * @param string $dbname
	 * @param string $dbpath
	 * @param number $isNew
	 * @return Ambigous <boolean, Ambigous>
	 */
	protected function getDb($dbconn=NULL,$dbname='',$dbpath='dbase',$isNew=0){
		if(is_null($this->db)){
			$this->db=load::db($dbconn,$dbname,$dbpath,$isNew);
		}
		return $this->db;
	}
	
	/**
	 * 获取系统内核数据库
	 *
	 * @param string $name
	 * @param string $path
	 * @param number $isNew
	 * @return Ambigous <boolean, Ambigous>
	 */
	protected function getXmlDb($name='',$path='',$isNew=0){
		if(is_null($this->xmlDb)){
			$dbName=($name=='' ? (is_null($this->xmlDbName) ? ROUTE_C : $this->xmlDbName) : $name);
			$dbPath=($path=='' ? (is_null($this->xmlDbPath) ? 'core' : $this->xmlDbPath) : $path);
			$this->xmlDb=load::db('xml', $dbName, $dbPath, $isNew);
		}
		return $this->xmlDb;
	}	
}