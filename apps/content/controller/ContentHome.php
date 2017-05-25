<?php
defined('IN_MYCMS') or exit('No permission resources.');
class ContentHome extends HomeController{
	public $models, $modelFields, $categorys, $content_output;
	private $format, $thumb, $images;

	public function __construct(){
		parent::__construct();
		$this->categorys=& getLcache('category', 'core', 'array');
		$this->modelFields=getcache('model', 'model', 'array', 'fields');
		$this->_resetFormat();
	}

	/**
	 * ********************外部访问开放方法*******************
	 */
	/*
	 * 功能：处理首页 页面可用公共变量-> $SEO: array 页面SEO信息数组 包含site_title,title,keyword,description字段 $SITE：array 站点全局设置信息 $categorys: array 所有栏目信息
	 */
	public function index(){
		$SITE=&$this->site;
		$SEO=seo();
		$categorys=&$this->categorys;
		include template('index');
	}

	/*
	 * 功能：处理列表页 页面可用公共变量-> $catid: int 当前栏目ID $typeid：int 当前分类ID(有分类时存在) $page: int 当前页面号 $categorys: array 所有栏目信息 $category: array 当前栏目信息 $arrparentids array 当前栏目所有父辈栏目ID数组 $arrchildids array 当前栏目所有子栏目ID $top_parentid array 当前栏目祖辈栏目ID $url string 当前页面url $template string 当前模板名称 $SEO: array 页面SEO信息数组 包含site_title,title,keyword,description字段 $SITE：array 站点全局设置信息 $_rs: array 可供使用的当前页面字段原始值数组（单页栏目特有） $sub_title：string 分页子标题（有分页符时存在，单页栏目特有） $pagenumber: int 分页总数（有分页符时存在，单页栏目特有） $pageurls: array 分页所有URL（有分页符时存在，单页栏目特有） $pages: string 生成的分页字符串（无分页符时为空，单页栏目特有） $page: int 当前页号（有分页符时存在，单页栏目特有）
	 */
	public function lists(){
		$catid=intval($_GET['catid']);
		$SITE=&$this->site;
		if(!isset($this->categorys[$catid])){
			$this->showmessage('栏目不存在！', 'blank');
		}
		
		// 栏目相关信息
		$categorys=&$this->categorys;
		$category=&$this->categorys[$catid];
		$arrparentids=explode(',', $category['arrpid']);
		$arrchildids=explode(',', $category['arrcid']);
		$top_parentid=$arrparentids[1] ? intval($arrparentids[1]) : $catid;
		
		$url=get_url();
		
		$_template_category=$category['setting']['template_category'];
		$_template_list=$category['setting']['template_list'];
		
		if($this->categorys[$catid]['type'] == 0){
			if(isset($_GET['typeid'])){
				$typeid=intval($_GET['typeid']);
			}
			$template=!empty($_template_category) ? $_template_category : (!empty($_template_list) ? $_template_list : ($this->categorys[$catid]['arrcid'] ? 'category.html' : 'list.html'));
			$SEO=seo($catid);
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
			$_field_title=isset($_field_infos['title']) ? $_field_infos['title'][0] : '';
			$_field_keyword=isset($_field_infos['keyword']) ? $_field_infos['keyword'][0] : '';
			$_field_description=isset($_field_infos['description']) ? $_field_infos['description'][0] : '';
			$_field_content=isset($_field_infos['editor']) ? $_field_infos['editor'][0] : '';
			unset($_field_infos);
			
			$_rs=$this->getDb()->getOne($_model, '*', 'where `catid`=' . $catid);
			$_output_data=$this->getOutput($catid)->_get($_rs, $catid);
			
			$_seo_title=$_output_data[$_field_title]['value'];
			$_seo_description=$category['setting']['seo_description'];
			$_seo_keywords=!empty($_output_data[$_field_keyword]) ? implode(',', $_output_data[$_field_keyword]) : '';
			
			@extract($_output_data);
			$template=!empty($_template_category) ? $_template_category : 'page';
			$_content=$_output_data[$_field_content];
			
			// 分页处理
			if($_paginationtype != 0 && strpos($_content, '[page]') !== false){ // 对分页符号进行处理 ssss[page]ssss[/page]
			                                                                    // 删除开始或结束无效分页符
				$_content=preg_replace("/^[(:?\[\/page\])(:?\[page\])]+|[(:?\[\/page\])(:?\[page\])]+$/i", '', $_content);
				$_contents=array_values(array_filter(explode('[page]', $_content)));
				unset($_content);
				$pagenumber=count($_contents);
				$pageurls=array();
				$page=min((isset($_GET['page']) ? intval($_GET['page']) : 1), $pagenumber);
				for($_i=1; $_i <= $pagenumber; $_i++){
					$pageurls[$_i]=load::controller('admin.urlrule')->_setListURL($category, $_i);
				}
				$pages=getpage($pagenumber, $page, $pageurls);
				// 判断[page]出现的位置是否在第一位
				$_content=$_contents[$page - 1];
				unset($_contents);
				
				if(strpos($_content, '[/page]') !== false){
					list($sub_title, $_content)=explode('[/page]', $_content);
					$SEO=seo($catid, (!empty($sub_title) ? $sub_title : $_seo_title), $_seo_description, $_seo_keywords, empty($sub_title));
				}else{
					$SEO=seo($catid, $_seo_title . ($page != 1 ? '（' . $page . '）' : ''), $_seo_description, $_seo_keywords, empty($_seo_title) || $_seo_title == $category['name']);
				}
				$_content=trim($_content);
				if(stripos($_content, '<p') !== 0){
					$_content='<p>' . $_content;
				}
				if(strtolower(substr($_content, -4, 4)) != '</p>'){
					$_content=$_content . '</p>';
				}
			}else{
				$SEO=seo($catid, $_seo_title, $_seo_description, $_seo_keywords, empty($_seo_title) || $_seo_title == $category['name']);
				$_content=str_replace(array('[page]','[/page]'), '', $_content);
			}
			$$_field_content=&$_content;
			
			unset($_model, $_output_data, $_seo_title, $_seo_description, $_seo_keywords);
		}
		unset($_template_category, $_template_list);
		include template($template,'content');
	}

	/*
	 * 功能：处理详细页面 页面可用公共变量-> $id: int 当前页面ID $catid: int 当前页面栏目ID $model: string 当前页面模型 $categorys: array 所有栏目数组 $category: array 当前栏目数组 $arrparentids: array 当前所有父栏目数组 $arrchildids: array 当前所有子栏目数组 $top_parentid: int 当前祖辈栏目ID $_rs: array 可供使用的当前页面字段原始值数组 $template: string 当前页面模板名称 $previous_page: array 前一页信息数组 $next_page: array 后一页信息数组 $SEO: array 页面SEO信息数组 包含site_title,title,keyword,description字段 $SITE：array 站点全局设置信息 $sub_title：string 分页子标题（有分页符时存在） $pagenumber: int 分页总数（有分页符时存在） $pageurls: array 分页所有URL（有分页符时存在） $pages: string 生成的分页字符串（无分页符时为空） $page: int 当前页号（有分页符时存在）
	 */
	public function show(){
		$SITE=&$this->site;
		$id=intval($_GET['id']);
		$catid=intval($_GET['catid']);
		
		if(!isset($this->categorys[$catid])){
			$this->showmessage('栏目不存在！', 'blank');
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
		$_rs=$this->getDb()->getOne($_model, '*', 'where `catid`=' . $catid . ' and `id`=' . $id . (isset($_GET['authsec']) && $_GET['authsec'] == load::cfg('admin', 'ini') ? '' : ' and `status`=1'));
		
		if(empty($_rs)){
			$message_tpl=template('message');
			if(is_file($message_tpl)){
				include $message_tpl;
			}
			die(0);
		}
		
		$_output_data=$this->getOutput($catid)->_get($_rs, $catid);
		!isset($_output_data['_url'])&&isset($_output_data['url'])&&($_output_data['_url']=$_output_data['url']);
		load::controller('admin.urlrule')->_setShowURL($_output_data);
		
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
		$categorys=&$this->categorys;
		$category=&$categorys[$catid];
		$arrparentids=explode(',', $category['arrpid']);
		$arrchildids=explode(',', $category['arrcid']);
		$top_parentid=$arrparentids[1] ? intval($arrparentids[1]) : $catid;
		
		$template=!empty($category['setting']['template_show']) ? $category['setting']['template_show'] : 'show.html';
		$_content=$_output_data[$_field_content];
		$model=$_model;
		unset($_output_data);
		// 上一页和下一页设置
		$this->getDb()->setFilter(array($this,'_toDataID'));
		$_ALL_IDS=$this->getDb()->select($model, 'id', 'where `catid` = \'' . $catid . '\' and `status`=1', 'order by listorder desc,id desc');
		$this->getDb()->unsetFilter();
		$_ALL_IDS_REV=array_flip($_ALL_IDS); // 以id为索引，序号为值的数组
		$_PREVIOUS_ID=($_ALL_IDS_REV[$id] - 1 >= 0) ? $_ALL_IDS[$_ALL_IDS_REV[$id] - 1] : -1;
		$_NEXT_ID=($_ALL_IDS_REV[$id] + 1 < count($_ALL_IDS)) ? $_ALL_IDS[$_ALL_IDS_REV[$id] + 1] : -1;
		unset($_ALL_IDS, $_ALL_IDS_REV);
		
		$previous_page=$_PREVIOUS_ID == -1 ? false : $this->getDb()->getOne($model, '*', 'where `id`=' . $_PREVIOUS_ID);
		$next_page=$_NEXT_ID == -1 ? false : $this->getDb()->getOne($model, '*', 'where `id`=' . $_NEXT_ID);
		unset($_PREVIOUS_ID, $_NEXT_ID);
		
		if(!empty($previous_page)){
			$previous_page=$this->getOutput($previous_page['catid'])->_get($previous_page, $previous_page['catid']);
			load::controller('admin.urlrule')->_setShowURL($previous_page); // 设置前一页url
		}
		if(!empty($next_page)){
			$next_page=$this->getOutput($next_page['catid'])->_get($next_page, $next_page['catid']);
			load::controller('admin.urlrule')->_setShowURL($next_page); // 设置下一页url
		}
		
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
			$page=min((isset($_GET['page']) ? intval($_GET['page']) : 1), $pagenumber);
			for($_i=1; $_i <= $pagenumber; $_i++){
				$pageurls[$_i]=load::controller('admin.urlrule')->_setShowURL($_rs, $_i, 'page', 0);
			}
			
			$pages=getpage($pagenumber, $page, $pageurls);
			// 判断[page]出现的位置是否在第一位
			$_content=$_contents[$page - 1];
			unset($_contents);
			
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
		}else{
			$SEO=seo($catid, $_seo_title, $_seo_description, $_seo_keywords);
			$_content=str_replace(array('[page]','[/page]'), '', $_content);
		}
		
		$$_field_content=&$_content;
		// 释放变量
		unset($_content, $_model, $_seo_title, $_seo_description, $_seo_keywords, $_paginationtype, $_maxcharperpage, $_i);
		
		include template($template,'content');
	}

	/**
	 * ********************内部接口方法[标签方法]*******************
	 */
	public function _resetFormat($isformat=0,$isthumb=0,$isimages=0){
		$this->format=$isformat;
		$this->thumb=$isthumb;
		$this->images=$isimages;
	}

	public function _setOutput($data){
		if(!empty($data)){
			// 处理格式化
			if($this->format){
				
				$data=$this->getOutput($data['catid'])->_get($data, $data['catid']);
			}
			
			// 处理图片
			$isthumb=$this->thumb && empty($data['thumb']);
			$isimages=$this->images && !isset($data['allimages']);
			if(($isthumb || $isimages) && isset($data['catid']) && isset($this->categorys[$data['catid']])){
				$images=$this->get_images($data, $this->categorys[$data['catid']]['model'], 0);
				if($isthumb){
					$data['thumb']=$images[0];
				}
				if($isimages){
					$data['allimages']=$images;
				}
			}
			!isset($data['_url'])&&isset($data['url'])&&($data['_url']=$data['url']);
			// 处理URL
			load::controller('admin.urlrule')->_setShowURL($data);
		}
		return $data;
	}

	public function _toDataID($data){
		return intval($data['id']);
	}

	/**
	 * 功能：统计总数 paras参数说明： 必须属性->action、catid； 可选属性->where、typeid,posid（根据action调用）
	 */
	public function _count($paras){
		if($paras['action'] == 'lists'){
			$catid=intval($paras['catid']);
			if($this->categorys[$catid]['type'] || empty($this->categorys[$catid]['model'])){
				return false;
			}
			
			$where='where `status`=1';
			if($this->categorys[$catid]['arrcid']){
				$where.=' and `catid` in (' . $this->categorys[$catid]['arrcid'] . ',' . $catid . ')';
			}else{
				$where.=' and `catid`=' . $catid;
			}
			
			if(!empty($paras['thumb'])){
				$where.=' and `isimages`=1';
			}
			if(!empty($paras['where'])){
				$where.=' and ' . $paras['where'];
			}
			
			// typeid属性处理
			if(!empty($paras['typeid']) && ($classFields=get_formtype_fields($this->categorys[$catid]['model'], 'classid,typeid'))){
				$type_map=getcache('classify', 'classify', 'array', 'map');
				$typeid=intval($paras['typeid']);
				if(isset($type_map[$typeid])){
					$classids=getcache('classify', 'classify', 'array', $type_map[$typeid]);
					$arrcids=$classids[$typeid]['arrcid'];
					$arrcids[]=$typeid;
					$arrcids=array_unique($arrcids);
					
					if(isset($classFields['typeid'])){ // 单分类模式查询
						$where.=' and `' . $classFields['typeid'][0] . '` ' . (count($arrcids) == 1 ? '=' . $typeid : 'in (' . implode(',', $arrcids) . ')');
					}else{ // 多分类模式查询，效率较低，待优化！
						foreach($arrcids as $k=>$v){
							$arrcids[$k]='`' . $classFields['classid'][0] . '` like \'%' . $v . ',%\'';
						}
						$where.=' and (' . implode(' or ', $arrcids) . ')';
					}
					unset($classids, $arrcids, $type_map);
				}
			}
			return $this->getDb()->count($this->categorys[$catid]['model'], $where);
		}else if($paras['action'] == 'position'){
			$posid=intval($paras['posid']);
			$infos=getcache('position', 'core', 'array');
			return empty($infos[$posid]) || empty($infos[$posid]['content']) ? 0 : (substr_count($infos[$posid]['content'], ',') + 1);
		}
	}

	/**
	 * 功能：list标签处理函数 处理属性说明： catid：栏目ID,必须填写； typeid：标签所属分类ID,可以不用设置（默认为0，即获取顶级分类）、为数值 或$typeid变量（此种方式会随着提交的typeid参数值变化而改变父级分类）。 where：查询条件，默认为空，即查询catid栏目下的所有数据，格式如：“id>10”或“id>10 and name like '%li%'”。 num：数量的限制，数字，默认为空获取所有内容； start：开始条数，数字，默认为空，从第一条开始； order：排序，默认空按照id倒序输出，格式如：“listorder desc”或“listorder desc,id asc”； format：是否进行数据输出格式化，默认为1，反之这直接调用数据库中的原始数据； thumb：只获取具有缩略图的数据，如果返回的字段不包括“thumb”或“thumb”为空，则从数据所有数据中搜索； images：是否强制获取所有图片，将返回“allimages”字段，此字段包含了所有图片的数组。
	 */
	public function _tag_lists($paras){
		$catid=intval($paras['catid']);
		if($this->categorys[$catid]['type'] || empty($this->categorys[$catid]['model'])){
			return false;
		}
		
		$this->_resetFormat((isset($paras['format']) ? $paras['format'] : 1), (!empty($paras['thumb']) ? 1 : 0), (!empty($paras['images']) ? 1 : 0));
		
		$where='where `status`=1';
		if($this->categorys[$catid]['arrcid']){
			$where.=' and `catid` in (' . $this->categorys[$catid]['arrcid'] . ',' . $catid . ')';
		}else{
			$where.=' and `catid`=' . $catid;
		}
		if($this->thumb){
			$where.=' and `isimages`=1';
		}
		if(isset($paras['where'])){
			$where.=' and ' . $paras['where'];
		}
		
		// typeid属性处理
		if(!empty($paras['typeid']) && ($classFields=get_formtype_fields($this->categorys[$catid]['model'], 'classid,typeid'))){
			$type_map=getcache('classify', 'classify', 'array', 'map');
			$typeid=intval($paras['typeid']);
			if(isset($type_map[$typeid])){
				$classids=getcache('classify', 'classify', 'array', $type_map[$typeid]);
				$arrcids=$classids[$typeid]['arrcid'];
				$arrcids[]=$typeid;
				$arrcids=array_unique($arrcids);
				
				if(isset($classFields['typeid'])){ // 单分类模式查询
					$where.=' and `' . $classFields['typeid'][0] . '` ' . (count($arrcids) == 1 ? '=' . $typeid : 'in (' . implode(',', $arrcids) . ')');
				}else{ // 多分类模式查询，效率较低，待优化！
					foreach($arrcids as $k=>$v){
						$arrcids[$k]='`' . $classFields['classid'][0] . '` like \'%' . $v . ',%\'';
					}
					$where.=' and (' . implode(' or ', $arrcids) . ')';
				}
				unset($classids, $arrcids, $type_map);
			}
		}
		
		$field=$this->setBaseField($paras);
		if(is_array($field)){
			$field=array_intersect($field, $this->modelFields[$this->categorys[$catid]['model']]);
		}
		$limit=!empty($paras['limit']) ? 'limit ' . $paras['limit'] : '';
		$order=!empty($paras['order']) ? 'order by ' . $paras['order'] : 'order by `id` desc';
		$this->getDb()->setFilter(array($this,'_setOutput'));
		$infos=$this->getDb()->select($this->categorys[$catid]['model'], $field, $where, $order, $limit);
		$this->getDb()->unsetFilter();
		$this->_resetFormat();
		return $infos;
	}

	/**
	 * 功能：one标签处理函数 处理属性说明： catid：栏目ID,必须填写； typeid：标签所属分类ID,可以不用设置（默认为0，即获取顶级分类）、为数值 或$typeid变量（此种方式会随着提交的typeid参数值变化而改变父级分类）。 where：查询条件，默认为空，即查询catid栏目下的所有数据，格式如：“id>10”或“id>10 and name like '%li%'”。 num：数量的限制，数字，默认为空获取所有内容； start：开始条数，数字，默认为空，从第一条开始； order：排序，默认空按照id倒序输出，格式如：“listorder desc”或“listorder desc,id asc”； format：是否进行数据输出格式化，默认为1，反之这直接调用数据库中的原始数据； thumb：只获取具有缩略图的数据，如果返回的字段不包括“thumb”或“thumb”为空，则从数据所有数据中搜索； images：是否强制获取所有图片，将返回“allimages”字段，此字段包含了所有图片的数组。
	 */
	public function _tag_one($paras){
		$catid=intval($paras['catid']);
		$model=empty($this->categorys[$catid]['model']) ? 'page' : $this->categorys[$catid]['model'];
		
		$this->_resetFormat((isset($paras['format']) ? $paras['format'] : 1), (!empty($paras['thumb']) ? 1 : 0), (!empty($paras['images']) ? 1 : 0));
		
		$where='where ' . ($model == 'page' ? '`id`>0' : '`status`=1');
		if($this->categorys[$catid]['arrcid']){
			$where.=' and `catid` in (' . $this->categorys[$catid]['arrcid'] . ',' . $catid . ')';
		}else{
			$where.=' and `catid`=' . $catid;
		}
		if($model != 'page' && $this->thumb){
			$where.=' and `isimages`=1';
		}
		if($model != 'page' && isset($paras['where'])){
			$where.=' and ' . $paras['where'];
		}
		
		// typeid属性处理
		if($model != 'page' && !empty($paras['typeid']) && ($classFields=get_formtype_fields($model, 'classid'))){
			$type_map=getcache('classify', 'classify', 'array', 'map');
			$typeid=intval($paras['typeid']);
			if(isset($type_map[$typeid])){
				$classids=getcache('classify', 'classify', 'array', $type_map[$typeid]);
				$arrcids=$classids[$typeid]['arrcid'];
				$arrcids[]=$typeid;
				$arrcids=array_unique($arrcids);
				foreach($arrcids as $k=>$v){
					$arrcids[$k]='`' . $classFields['classid'][0] . '` like \'%' . $v . ',%\'';
				}
				$where.=' and (' . implode(' or ', $arrcids) . ')';
				unset($classids, $arrcids, $type_map);
			}
		}
		
		$field=$this->setBaseField($paras);
		if(is_array($field)){
			$field=array_intersect($field, $this->modelFields[$model]);
		}
		$limit=$model != 'page' && !empty($paras['limit']) ? 'limit ' . $paras['limit'] : '';
		$order=$model != 'page' && !empty($paras['order']) ? 'order by ' . $paras['order'] : 'order by `id` desc';
		$this->getDb()->setFilter(array($this,'_setOutput'));
		$infos=$this->getDb()->getOne($model, $field, $where, $order, $limit);
		$this->getDb()->unsetFilter();
		$this->_resetFormat();
		return $infos;
	}

	public function _tag_relation($paras){
	}

	/**
	 * 功能：hits标签处理函数，用于获取点击排行 处理属性说明： catid：栏目ID,默认为空，即调用所有栏目的数据，多个栏目之间用逗号隔开，格式如：“12,25”； day：调用多少天内的排行，默认为空即调用所有的排行。 limit：数量的限制，默认为空获取所有推荐内容，格式如：“5”或“2,5”，分别表示获取5条或从第三条开始获取五条； order：排序类型,默认为“views desc”（本月排行- monthviews desc 、本周排行 - weekviews desc、今日排行 - dayviews desc） format：是否进行数据输出格式化，默认为1，反之这直接调用数据库中的原始数据。
	 */
	public function _tag_hits($paras){
		$where='where `id`>0';
		
		// 处理栏目
		if(isset($paras['catid'])){
			$paras['catid']=array_filter(preg_split("/[^\d]+/", $paras['catid']));
			if(!empty($paras['catid'])){
				$catids=array();
				foreach($paras['catid'] as $cid){
					$catids[]=$cid;
					if($this->categorys[$cid]['arrcid']){
						$catids=array_merge($catids, explode(',', $this->categorys[$cid]['arrcid']));
					}
				}
				$catids=array_unique($catids);
				$where.=' and `catid` in (' . implode(',', $catids) . ')';
				unset($catids);
			}
		}
		
		// 处理多少天内数据
		if(!empty($paras['day'])){
			$days=intval($paras['day']);
			if($days){
				$where.=' and `viewtime`>=' . (time() - $days * 24 * 3600);
			}
		}
		
		// 处理其它参数
		$paras['limit']=!empty($paras['limit']) ? ' limit ' . $paras['limit'] : '';
		$paras['order']=' order by ' . (!empty($paras['order']) ? $paras['order'] : 'views desc');
		$field=$this->setBaseField($paras);
		$isArrayFd=is_array($field);
		$this->_resetFormat((isset($paras['format']) ? $paras['format'] : 1), 0, 0);
		// 获取数据
		$data=$this->getDb()->select('hits', '*', $where, $paras['order'], $paras['limit']);
		$this->getDb()->setFilter(array($this,'_setOutput'));
		foreach($data as $id=>$vl){
			$cModel=(!empty($this->categorys[$vl['catid']]['model']) ? $this->categorys[$vl['catid']]['model'] : 'page');
			if($isArrayFd){
				$cField=array_intersect($field, $this->modelFields[$cModel]);
			}
			$info=$this->getDb()->getOne($cModel, $cField, 'where `id`=' . $vl['hitsid'] . ' and `catid`=' . $vl['catid']);
			if(!empty($info)){
				unset($data[$id]['id'], $data[$id]['hitsid']);
				$data[$id]=array_merge($data[$id], $info);
			}else{
				unset($data[$id]);
			}
		}
		$this->getDb()->unsetFilter();
		$this->_resetFormat();
		return $data;
	}

	/**
	 * 功能：classify标签处理函数 处理属性说明： classid：标签所属类别ID,必须填写 typeid：标签所属分类ID,可以不用设置（默认为0，即获取顶级分类）、为数值 或$typeid变量（此种方式会随着提交的typeid参数值变化而改变父级分类）。
	 */
	public function _tag_classify($paras){
		$catid=intval($paras['catid']);
		if(isset($paras['catid']) && isset($this->categorys[$catid]) && isset($paras['classid'])){
			if(is_null($this->urlrule)){
				$this->urlrule=load::controller('admin.urlrule');
			}
			$typeids=getcache('classify', 'classify', 'array', $paras['classid']);
			$paras['typeid']=isset($paras['typeid']) ? $paras['typeid'] : 0;
			if(!empty($typeids)){
				foreach($typeids as $tid=>$tvl){
					if($paras['typeid'] == $tvl['pid']){
						$typeids[$tid]['url']=$this->urlrule->_setListURL($this->categorys[$catid], 1, $tid);
					}else{
						unset($typeids[$tid]);
					}
				}
				return $typeids;
			}
		}
	}

	/**
	 * 功能：position标签处理函数 处理属性说明： posid：推荐位ID,必须填写； limit：数量的限制，默认为空获取所有推荐内容，格式为：“5”或“2,5”，分别表示获取5条或从第三条开始获取五条； format：是否进行数据输出格式化，默认为1，反之这直接调用数据库中的原始数据。 thumb：只获取具有缩略图的数据，如果返回的字段不包括“thumb”或“thumb”为空，则从数据所有数据中搜索；
	 */
	public function _tag_position($paras){
		$posid=intval($paras['posid']);
		$infos=array();
		if($posid > 0){
			$positions=getcache('position', 'core', 'array');
			$position=$positions[$posid];
			unset($positions);
			if(!empty($position) && $position['content']){
				$posids=explode(',', $position['content']);
				$field=$this->setBaseField($paras);
				$isArrayFd=is_array($field);
				$limit=isset($paras['limit']) ? trim($paras['limit']) : '';
				$this->_resetFormat((isset($paras['format']) ? $paras['format'] : 1), (!empty($paras['thumb']) ? 1 : 0), 0);
				$startDx=empty($limit) || strpos($limit, ',') === false ? 0 : max(intval(substr($limit, 0, strpos($limit, ','))), 0);
				$dxLen=empty($limit) ? 0 : max(intval(strpos($limit, ',') === false ? $limit : substr($limit, strpos($limit, ',') + 1)), 0);
				$cnum=0;
				$where='where `status`=1';
				if($this->thumb){
					$where.=' and `isimages`=1';
				}
				$this->getDb()->setFilter(array($this,'_setOutput'));
				foreach($posids as $cids){
					if($cnum >= $startDx && (!$dxLen || $cnum < $startDx + $dxLen)){
						$mods=explode('.', $cids);
						if($isArrayFd){
							$cField=array_intersect($field, $this->modelFields[$mods[0]]);
						}
						$cdata=$this->getDb()->getOne($mods[0], $cField, $where . ' and `id`=' . $mods[1]);
						
						if(empty($cdata)){
							continue;
						}
						$infos[]=$cdata;
					}
					$cnum++;
				}
				$this->getDb()->unsetFilter();
				$this->_resetFormat();
			}
		}
		return $infos;
	}

	/**
	 * ********************内部私有方法*******************
	 */
	private function getOutput($catid=-1){
		if(is_null($this->content_output) && $catid != -1){
			include_once getcache('formtype', 'formtype', 'file', 'output');
			$this->content_output=new formtype_output($this->categorys[$catid]['model'], $catid, $this->categorys);
		}
		return $this->content_output;
	}

	private function get_images(&$datas,&$fields,$isCheck=1,$forms=NULL){
		$forms=is_null($forms) ? array('image','images','editor') : $forms;
		$formtypes=get_formtype_fields($fields, $forms);
		$imgs=array();
		if(!empty($formtypes)){
			foreach($forms as $cForm){
				if(isset($formtypes[$cForm])){
					if($cForm == 'editor'){
						foreach($formtypes[$cForm] as $cfd){
							if(stripos($datas[$cfd], '<img') !== false){
								if(preg_match_all("/(href|src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $datas[$cfd], $matches)){
									foreach($matches[3] as $matche){
										$smileyImgPos=stripos($matche, 'editor/plugins/smiley/images/');
										if($smileyImgPos !== false && strripos($matche, '/') == ($smileyImgPos + 28)){
											continue; // 此处排除编辑器中表情图片
										}
										if($isCheck){
											return 1;
										}else{
											$imgs[]=$matche;
										}
									}
								}
								unset($matches);
							}
						}
					}else{
						foreach($formtypes[$cForm] as $cfd){
							if(!empty($datas[$cfd])){
								if($isCheck){
									return 1;
								}else{
									if(is_array($datas[$cfd])){
										foreach($datas[$cfd] as $cdt){
											$imgs[]=$cdt['url'];
										}
									}else{
										$imgs[]=$datas[$cfd];
									}
								}
							}
						}
					}
				}
			}
		}
		return $isCheck ? 0 : $imgs;
	}

	private function setBaseField(&$paras,$atts=array('catid','id')){
		$field=isset($paras['field']) ? $paras['field'] : '*';
		if($field != '*'){
			return array_unique(array_merge($atts, explode(',', str_replace(' ', '', $field))));
		}
		return $field;
	}
}

?>