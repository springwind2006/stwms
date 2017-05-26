<?php
defined('IN_MYCMS') or exit('No permission resources.');
class UrlruleAdmin extends AdminController{
	public $categorys, $urlruleCache, $htmlRoot, $showURLCacheArr=array(), $categoryURLCacheArr=array();

	public function __construct(){
		parent::__construct();
		$this->xmlDbName='urlrule';
	}

	public function init(){
		$data=$this->getUrlruleCache();
		$auths=load::controller('role')->_check_auth('add,edit,del,disabled');
		include template('init', 'urlrule');
	}

	public function add(){
		if(isset($_GET['dosubmit'])){
			$res=$this->getXmlDb()->insert('urlrule', $_POST['info']);
			$this->_fresh();
			alert('添加操作成功!');
		}else{
			include template('add', 'urlrule');
		}
	}

	public function edit(){
		$id=intval($_GET['id']);
		if(isset($_GET['dosubmit'])){
			$res=$this->getXmlDb()->update('urlrule', $_POST['info'], 'where `id`=' . $id);
			if($res > 0){
				$this->_fresh();
				if($this->isUsing($id) == 1){
					load::controller('category')->_update_urls();
				}
			}
			alert($res > 0 ? '修改操作成功!' : '修改操作失败!');
		}else{
			$data=$this->getXmlDb()->getOne('urlrule', '*', 'where `id`=' . $id);
			@extract($data);
			include template('edit', 'urlrule');
		}
	}

	public function del(){
		$id=intval($_GET['id']);
		$isUsing=$this->isUsing($id);
		if(!$isUsing){
			$res=$this->getXmlDb()->delete('urlrule', 'where `id`=' . intval($_GET['id']));
			if($res > 0){
				$this->_fresh();
			}
			$this->showmessage($res > 0 ? '删除操作成功!' : '删除操作失败!', 'auto');
		}else{
			$this->showmessage('删除操作失败!此规则正在使用中...', 'auto');
		}
	}

	public function disabled(){
		$res=$this->getXmlDb()->update('urlrule', array('disabled' => intval($_GET['disabled'])), 'where `id`=' . intval($_GET['id']));
		if($res > 0){
			$this->_fresh();
		}
		$this->showmessage($res > 0 ? '操作成功!' : '操作失败!');
	}

	/**
	 * 功能：设置选择结果中的带有catid字段的url 参数：(&$data array 数据,$page=0 int 页数, $pagetype='page' string 分页类别,$isset=1 bool 是否设置URL到数据)
	 */
	public function _setShowURL(&$data,$page=0,$pagetype='page',$isset=1){
		if(!empty($data['islink']) && !empty($data['url'])){
			return $data['url'];
		}
		$url='';
		if(isset($data['catid']) && isset($data['id'])){
			$categorys=& getLcache('category', 'core', 'array');
			$category=&$categorys[$data['catid']];
			$catid=$category['id'];
			$time=empty($data['addtime']) ? NOW_TIME : $data['addtime'];
			if($category['setting']['show_ishtml']){
				$show_ruleid=intval($category['setting']['show_ruleid']);
				$urlDx=(empty($page) || $page == 1 ? 's' : 'l') . $show_ruleid . $catid;
				if(!isset($this->showURLCacheArr[$urlDx])){
					$urlRules=$this->getUrlruleCache();
					
					$urlruleArr=explode('|', $urlRules[$show_ruleid]['urlrule']);
					$urlruleTemplate=(empty($page) || $page == 1 ? $urlruleArr[0] : $urlruleArr[1]);
					$urlruleTemplate=str_replace(array('{$pdir}','{$cdir}','{$pid}','{$cid}'), array($category['pdir'],$category['cdir'],$category['pid'],$catid), $urlruleTemplate);
					$urlruleTemplate=ROOT_URL . $this->getHtmlRoot() . ltrim($urlruleTemplate, '/\\ ');
					
					$this->showURLCacheArr[$urlDx]=$urlruleTemplate;
					$url=str_replace(array('{$year}','{$month}','{$day}','{$id}','{$page}'), array(date('Y', $time),date('m', $time),date('d', $time),$data['id'],$page), $urlruleTemplate);
				}else{
					$url=str_replace(array('{$year}','{$month}','{$day}','{$id}','{$page}'), array(date('Y', $time),date('m', $time),date('d', $time),$data['id'],$page), $this->showURLCacheArr[$urlDx]);
				}
				if(strpos($url, '//') !== false || strpos($url, '\\\\') !== false){
					$url=preg_replace("/[\/\\\]+/i", '/', $url);
				}
			}else{
				if(empty($category['setting']['simple_url'])){
					$url=ROOT_URL . SYS_ENTRY . '?c=content&a=show&catid=' . $data['catid'] . '&id=' . $data['id'] . (empty($page) || $page == 1 ? '' : '&' . $pagetype . '=' . $page);
				}else{
					$url=ROOT_URL . $category['setting']['simple_url'] . '?id=' . $data['id'] . (empty($page) || $page == 1 ? '' : '&' . $pagetype . '=' . $page);
				}
			}
			if($isset){
				$data['url']=$url;
			}
		}
		return $url;
	}

	/**
	 * 功能：设置或获取栏目页URL 参数：(&$category array 栏目配置,$page=0 int 页数,$typeid='' int 分类ID, $pagetype='page' string 分页类别,$isfull=1 bool 是否返回全路径)
	 */
	//
	public function _setListURL(&$category,$page=0,$typeid='',$pagetype='page',$isfull=1){
		$url='';
		if(isset($category['id']) && $category['type'] != 2){
			$catid=$category['id'];
			if($category['setting']['category_ishtml']){
				$category_ruleid=intval($category['setting']['category_ruleid']);
				$urlDx=(empty($page) || $page == 1 ? 'p' : 'l') . $category_ruleid . $catid;
				if(!isset($this->categoryURLCacheArr[$urlDx])){
					$urlRules=$this->getUrlruleCache();
					
					$urlruleArr=explode('|', $urlRules[$category_ruleid]['urlrule']);
					$urlruleTemplate=(empty($page) || $page == 1 ? $urlruleArr[0] : $urlruleArr[1]);
					
					$urlruleTemplate=str_replace(array('{$pdir}','{$cdir}','{$pid}','{$cid}'), array($category['pdir'],$category['cdir'],$category['pid'],$catid), $urlruleTemplate);
					$urlruleTemplate=($isfull ? ROOT_URL . $this->getHtmlRoot() : '') . ltrim($urlruleTemplate, '/\\ ');
					$this->categoryURLCacheArr[$urlDx]=$urlruleTemplate;
					$url=str_replace(array('{$page}','{$typeid}'), array($page,$typeid), $urlruleTemplate);
				}else{
					$url=str_replace(array('{$page}','{$typeid}'), array($page,$typeid), $this->categoryURLCacheArr[$urlDx]);
				}
				if(strpos($url, '//') !== false || strpos($url, '\\\\') !== false){
					$url=preg_replace("/[\/\\\]+/i", '/', $url);
				}
			}else{
				if(empty($category['setting']['simple_url'])){
					$url=($isfull ? ROOT_URL : '') . SYS_ENTRY . '?c=content&a=lists&catid=' . $catid . (empty($typeid) ? '' : '&typeid=' . $typeid) . (empty($page) || $page == 1 ? '' : '&' . $pagetype . '=' . $page);
				}else{
					$addtion=(empty($typeid) && (empty($page) || $page == 1) ? '' : '?') . (empty($typeid) ? '' : 'typeid=' . $typeid) . (empty($page) || $page == 1 ? '' : (empty($typeid) ? '' : '&') . $pagetype . '=' . $page);
					$url=($isfull ? ROOT_URL : '') . $category['setting']['simple_url'] . $addtion;
				}
			}
			return $url;
		}
		return $category['url'];
	}

	/**
	 * *********仅供内部方法**********
	 */
	// 更新模型缓存
	public function _fresh(){
		$data=$this->getXmlDb()->select('urlrule', '*', 'where `disabled`=0');
		$reData=array();
		foreach($data as $vl){
			$reData[$vl['id']]=$vl;
		}
		setcache('urlrule', $reData, 'core', 'array');
	}

	/**
	 * *********私有方法**********
	 */
	private function getUrlruleCache(){
		if(is_null($this->urlruleCache)){
			$this->urlruleCache=getcache('urlrule', 'core', 'array');
		}
		return $this->urlruleCache;
	}

	private function getHtmlRoot(){
		if(is_null($this->htmlRoot)){
			$this->htmlRoot=trim(load::cfg('system', 'html_root'));
		}
		return $this->htmlRoot;
	}

	private function isUsing($id){
		$categorys=& getLcache('category', 'core', 'array');
		foreach($categorys as $cat){
			if($cat['setting']['category_ishtml'] && $cat['setting']['category_ruleid'] == $id){
				return 1;
			}
			if($cat['setting']['show_ishtml'] && $cat['setting']['show_ruleid'] == $id){
				return 2;
			}
		}
		return 0;
	}
}

?>