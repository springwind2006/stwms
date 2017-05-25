<?php
defined('IN_MYCMS') or exit('No permission resources.');
load::func('dir');
class Html{
	private $db, $html_root, $categorys, $urlrule, $content;

	public function __construct(){
		$this->categorys=& getLcache('category', 'core', 'array');
		$this->html_root=load::cfg('system', 'html_root');
		$this->urlrule=load::controller('admin.urlrule');
	}

	/*
	 * 功能：生成内容页 /red/sss 参数：($_rs 原始数据,$_action 方法)
	 */
	public function show($_rs=array(),$_action='add'){
		if(!empty($_rs['islink']) && !empty($_rs['url'])){
			return 0;
		}
		$_output_file=substr($this->urlrule->_setShowURL($_rs), strlen(ROOT_URL));
		
		$id=intval($_rs['id']);
		$catid=intval($_rs['catid']);
		if(!isset($this->categorys[$catid])){
			return false;
		}
		$_model=$this->categorys[$catid]['model'];
		
		$_field_infos=get_formtype_fields($_model);
		if(isset($_field_infos['_type'])){
			$_paginationtype=intval($_field_infos['_type'][0]);
			$_maxcharperpage=intval($_field_infos['_chars'][0]);
		}else{
			$_paginationtype=0;
		}
		// 获取一些关键字段名
		$_field_title=isset($_field_infos['title']) ? $_field_infos['title'][0] : '';
		$_field_keyword=isset($_field_infos['keyword']) ? $_field_infos['keyword'][0] : '';
		$_field_description=isset($_field_infos['description']) ? $_field_infos['description'][0] : '';
		$_field_content=isset($_field_infos['editor']) ? $_field_infos['editor'][0] : '';
		unset($_field_infos);
		
		$_output_data=$this->getOutput($catid)->_get($_rs, $catid);
		
		// SEO设置
		$_seo_keywords=!empty($_output_data[$_field_keyword]) ? implode(',', $_output_data[$_field_keyword]) : '';
		$_seo_title=strip_tags($_output_data[$_field_title]['value']);
		$_seo_description=strip_tags($_output_data[$_field_description]);
		
		extract($_output_data);
		/**
		 * *可能会遭遇重置的变量后面设定**
		 */
		
		// 设置栏目相关信息
		if(!is_int($catid)){
			$catid=intval($catid);
		}
		$categorys=$this->categorys;
		$category=$categorys[$catid];
		$arrparentids=explode(',', $category['arrpid']);
		$arrchildids=explode(',', $category['arrcid']);
		$top_parentid=$arrparentids[1] ? intval($arrparentids[1]) : $catid;
		
		$template=!empty($category['setting']['template_show']) ? $category['setting']['template_show'] : 'show.html';
		$_content=$_output_data[$_field_content];
		$model=$_model;
		unset($_output_data);
		// 设置上一页和下一页
		$previous_page=$this->getDb()->getOne($model, '*', 'where `catid` = \'' . $catid . '\' and `id`<' . $id . ' and `status`=1', 'order by id desc');
		$next_page=$this->getDb()->getOne($model, '*', 'where `catid`= \'' . $catid . '\' AND `id`>' . $id . ' AND `status`=1');
		if(!empty($previous_page)){
			$previous_page=$this->getOutput($previous_page['catid'])->_get($previous_page, $previous_page['catid']);
			$this->urlrule->_setShowURL($previous_page); // 设置前一页url
		}
		if(!empty($next_page)){
			$next_page=$this->getOutput($next_page['catid'])->_get($next_page, $next_page['catid']);
			$this->urlrule->_setShowURL($next_page); // 设置下一页url
		}
		
		// 分页处理
		$pages='';
		if($_paginationtype == 2){ // 自动分页
			if($_maxcharperpage < 10){
				$_maxcharperpage=500;
			}
			$_contentpage=load::cls('ContentPage');
			$_content=$_contentpage->get_data($_content, $_maxcharperpage);
			$_contentpage=NULL;
		}
		
		if($_paginationtype != 0 && strpos($_content, '[page]') !== false){ // 对分页符号进行处理 ssss[page]ssss[/page]
		                                                                    // 删除开始或结束无效分页符
			$_content=preg_replace("/^[(:?\[\/page\])(:?\[page\])]+|[(:?\[\/page\])(:?\[page\])]+$/i", '', $_content);
			$_contents=array_values(array_filter(explode('[page]', $_content)));
			unset($_content);
			$pagenumber=count($_contents);
			$pageurls=array();
			for($_i=1; $_i <= $pagenumber; $_i++){
				$pageurls[$_i]=$this->urlrule->_setShowURL($_rs, $_i, 'page', 0);
			}
			$SITE=getcache('setting', 'setting', 'array', 'web');
			// 生成分页
			foreach($pageurls as $page=>$_curl){
				$pages=getpage($pagenumber, $page, $pageurls);
				// 判断[page]出现的位置是否在第一位
				$_content=$_contents[$page - 1];
				
				if(strpos($_content, '[/page]') !== false){
					list($sub_title, $_content)=explode('[/page]', $_content);
					$SEO=seo($catid, (!empty($sub_title) ? $sub_title : $_seo_title), $_seo_description, $_seo_keywords);
				}else{
					$SEO=seo($catid, $_seo_title . ($page != 1 ? '（' . $page . '）' : ''), $_seo_description, $_seo_keywords);
				}
				
				$_content=trim($_content);
				if(stripos($_content, '<p') !== 0){
					$_content='<p>' . $_content;
				}
				if(strtolower(substr($_content, -4, 4)) != '</p>'){
					$_content=$_content . '</p>';
				}
				
				$$_field_content=&$_content;
				
				$_pagefile=ROOT_PATH . substr($_curl, strlen(ROOT_URL));
				
				ob_start();
				include template($template);
				$this->createhtml($_pagefile);
			}
			return true;
		}else{
			$_content=str_replace(array('[page]','[/page]'), '', $_content);
			$$_field_content=&$_content;
		}
		// 分页处理结束
		$SEO=seo($catid, $_seo_title, $_seo_description, $_seo_keywords);
		$SITE=getcache('setting', 'setting', 'array', 'web');
		unset($_seo_title, $_seo_description, $_seo_keywords);
		ob_start();
		include template($template);
		return $this->createhtml(ROOT_PATH . $_output_file);
	}

	/*
	 * 功能：生成栏目列表 参数：($catid 栏目id,$page 当前页数)
	 */
	public function category($catid,$page=1,$typeid=''){
		$catid=intval($catid);
		if(!isset($this->categorys[$catid])){
			return false;
		}
		if(!$this->categorys[$catid]['setting']['category_ishtml']){
			return false;
		}
		
		$page=intval($page);
		
		// 栏目相关信息
		$categorys=$this->categorys;
		$category=$this->categorys[$catid];
		$arrparentids=explode(',', $category['arrpid']);
		$arrchildids=explode(',', $category['arrcid']);
		$top_parentid=$arrparentids[1] ? intval($arrparentids[1]) : $catid;
		
		$url=$this->urlrule->_setListURL($category, $page, $typeid);
		$_output_file=ROOT_PATH . substr($url, strlen(ROOT_URL));
		
		$_category_ruleid=intval($category['setting']['category_ruleid']);
		$_urlrules=getcache('urlrule', 'core', 'array');
		$_urlruleArr=explode('|', $_urlrules[$_category_ruleid]['urlrule']);
		
		foreach($_urlruleArr as $k=>$v){
			$_urlruleArr[$k]=str_replace('//', '/', ROOT_URL . $this->html_root . ltrim($v, '/\\ '));
		}
		
		$_template_category=$category['setting']['template_category'];
		$_template_list=$category['setting']['template_list'];
		
		if($category['type'] == 0){
			$template=(!$category['arrcid'] || $typeid) ? (!empty($_template_list) ? $_template_list : 'list.html') : (!empty($_template_category) ? $_template_category : (!empty($_template_list) ? $_template_list : 'category.html'));
			
			$SEO=seo($catid);
			// URL规则
			$GLOBALS['URL_RULE']=implode('~', $_urlruleArr);
			$GLOBALS['URL_ARRAY']=array('pdir' => $category['pdir'],'cdir' => $category['cdir'],'cid' => $category['id'],'pid' => $category['pid'],'typeid' => $typeid);
		}else{
			// 单页栏目
			$_model=trim($category['model']) == '' ? 'page' : $category['model'];
			
			// 获取分页信息
			$_field_infos=get_formtype_fields($_model);
			if(isset($_field_infos['_type'])){
				$_paginationtype=intval($_field_infos['_type'][0]);
				$_maxcharperpage=intval($_field_infos['_chars'][0]);
			}else{
				$_paginationtype=0;
			}
			// 获取一些关键字段名
			$_field_title=isset($_field_infos['title']) ? $_field_infos['title'][0] : '';
			$_field_keyword=isset($_field_infos['keyword']) ? $_field_infos['keyword'][0] : '';
			$_field_description=isset($_field_infos['description']) ? $_field_infos['description'][0] : '';
			$_field_content=isset($_field_infos['editor']) ? $_field_infos['editor'][0] : '';
			unset($_field_infos);
			
			$_rs=$this->page($catid);
			$_output_data=$this->getOutput($catid)->_get($_rs, $catid);
			
			$_seo_title=$_output_data[$_field_title]['value'];
			$_seo_keywords=!empty($_output_data[$_field_keyword]) ? implode(',', $_output_data[$_field_keyword]) : '';
			
			@extract($_output_data);
			$template=!empty($_template_category) ? $_template_category : 'page.html';
			$_content=$_output_data[$_field_content];
			
			// 分页处理
			if($_paginationtype != 0 && strpos($_content, '[page]') !== false){ // 对分页符号进行处理 ssss[page]ssss[/page]
			                                                                    // 删除开始或结束无效分页符
				$_content=preg_replace("/^[(:?\[\/page\])(:?\[page\])]+|[(:?\[\/page\])(:?\[page\])]+$/i", '', $_content);
				$_contents=array_values(array_filter(explode('[page]', $_content)));
				unset($_content);
				$pagenumber=count($_contents);
				$pageurls=array();
				
				for($_i=1; $_i <= $pagenumber; $_i++){
					$pageurls[$_i]=load::controller('admin.urlrule')->_setListURL($category, $_i);
				}
				
				$SITE=getcache('setting', 'setting', 'array', 'web');
				foreach($pageurls as $page=>$url){
					$pages=getpage($pagenumber, $page, $pageurls);
					// 判断[page]出现的位置是否在第一位
					$_content=$_contents[$page - 1];
					
					if(strpos($_content, '[/page]') !== false){
						list($sub_title, $_content)=explode('[/page]', $_content);
						$SEO=seo($catid, (!empty($sub_title) ? $sub_title : $_seo_title), '', $_seo_keywords, empty($sub_title));
					}else{
						$SEO=seo($catid, $_seo_title . ($page != 1 ? '（' . $page . '）' : ''), '', $_seo_keywords, empty($_seo_title) || $_seo_title == $category['name']);
					}
					$_content=trim($_content);
					if(stripos($_content, '<p') !== 0){
						$_content='<p>' . $_content;
					}
					if(strtolower(substr($_content, -4, 4)) != '</p>'){
						$_content=$_content . '</p>';
					}
					$$_field_content=&$_content;
					$_pagefile=ROOT_PATH . substr($url, strlen(ROOT_URL));
					
					ob_start();
					include template($template);
					$this->createhtml($_pagefile);
				}
				return true;
			}else{
				$SEO=seo($catid, $_seo_title, '', $_seo_keywords, empty($_seo_title) || $_seo_title == $category['name']);
				$_content=str_replace(array('[page]','[/page]'), '', $_content);
			}
			$$_field_content=&$_content;
			
			unset($_model, $_output_data, $_seo_keywords);
		}
		$SITE=getcache('setting', 'setting', 'array', 'web');
		unset($_category_ruleid, $_urlrules, $_urlruleArr, $_template_category, $_template_list);
		ob_start();
		include template($template);
		return $this->createhtml($_output_file);
	}
	
	// 更新首页
	public function index(){
		$output_file=ROOT_PATH . 'index.html';
		$SEO=seo();
		$SITE=getcache('setting', 'setting', 'array', 'web');
		$categorys=$this->categorys;
		ob_start();
		include template('index');
		return $this->createhtml($output_file);
	}
	
	// 获取单页数据
	public function page($catid){
		$model=empty($this->categorys[$catid]['model']) ? 'page' : $this->categorys[$catid]['model'];
		return $this->getDb()->getOne($model, '*', 'where `catid`=' . $catid);
	}
	
	// 写入HTML
	private function createhtml($output_file){
		$output_file=trim($output_file);
		if(strpos($output_file, '.') === false){
			$output_file=(rtrim($output_file, '/\\') . '/index.html');
		}else{
			$dot_pos=strrpos($output_file, '.');
			$ext=strtolower(substr($output_file, $dot_pos + 1));
			if(!in_array($ext, array('html','htm'))){
				$lastChar=substr($output_file, -1, 1);
				if(!in_array($lastChar, array('/','\\'))){
					return 0;
				}else{
					$output_file=$output_file . '/index.html';
				}
			}
		}
		
		$data=ob_get_contents();
		ob_clean();
		$dir=dirname($output_file);
		if(!is_dir($dir)){
			mkdir($dir, 0777, 1);
		}
		$strlen=file_put_contents($output_file, $data);
		@chmod($output_file, 0777);
		if(!is_writable($output_file)){
			$output_file=str_replace(ROOT_PATH, '', $output_file);
			showmessage('文件：' . $output_file . '<br>不可写入!');
		}
		return $strlen;
	}

	private function getDb(){ // 获取系统数据库
		if(is_null($this->db)){
			$this->db=load::db();
		}
		return $this->db;
	}

	private function getOutput($catid=-1){ // 获取输出处理
		if(is_null($this->content_output) && $catid != -1){
			include_once getcache('formtype', 'formtype', 'file', 'output');
			$this->content_output=new formtype_output($this->categorys[$catid]['model'], $catid, $this->categorys);
		}
		return $this->content_output;
	}
}