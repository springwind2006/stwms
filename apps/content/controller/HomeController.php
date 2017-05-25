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
		$this->site=&getLcache('setting', 'setting', 'array', 'web');
		$this->checkStatus();
	}

	/**
	 * 默认方法
	 * @param unknown $c
	 * @param unknown $a
	 */
	public function _default($a,$c=''){
		if(($file=(isset($GLOBALS[PLUGIN_ID]) ? template($a, $c) : template($a, $c, false))) && is_file($file)){
			$SEO=seo();
			$SITE=&$this->site;
			include ($file);
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
			$this->db=load::db($dbconn, $dbname, $dbpath, $isNew);
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
			$dbName=($name == '' ? (is_null($this->xmlDbName) ? ROUTE_C : $this->xmlDbName) : $name);
			$dbPath=($path == '' ? (is_null($this->xmlDbPath) ? 'core' : $this->xmlDbPath) : $path);
			$this->xmlDb=load::db('xml', $dbName, $dbPath, $isNew);
		}
		return $this->xmlDb;
	}
}