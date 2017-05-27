<?php
defined('IN_MYCMS') or exit('No permission resources.');
load::cls('Form', 0);
load::module('home');
class StaticizeAdmin extends AdminController{
	private $categorys, $html;

	public function __construct(){
		parent::__construct();
		$this->categorys=& getLcache('category', 'core', 'array');
		foreach($_GET as $k=>$v){
			$_POST[$k]=$v;
		}
	}

	public function index(){
		!defined('STYLE_URL') && Application::setStyle(false);
		$this->html=load::cls('Html');
		$size=$this->html->index();
		$this->showmessage('首页更新成功! 大小：' . sizeformat($size), 'auto');
	}

	public function category(){
		if(isset($_POST['dosubmit'])){
			!defined('STYLE_URL') && Application::setStyle(false);
			$this->html=load::cls('Html');
			$referer=isset($_POST['referer']) ? urlencode($_POST['referer']) : '';
			$totype=intval($_POST['totype']);
			
			// 生成需要更新的栏目序列缓存
			if(!isset($_POST['set_catid'])){
				if($_POST['catids'][0] != 0){
					$update_url_catids=$_POST['catids'];
				}else{
					foreach($this->categorys as $catid=>$cat){
						if($cat['type'] == 2 || !$cat['setting']['category_ishtml'])
							continue;
						if($_POST['model'] && ($_POST['model'] != $cat['model']))
							continue;
						$update_url_catids[]=$catid;
					}
				}
				setcache('update_html_catid' . '_' . $_SESSION['userid'], $update_url_catids, 'content', 'array');
				$forward=act_url('staticize', 'category', 'set_catid=1&pagesize=' . $_POST['pagesize'] . '&dosubmit=1&model=' . $_POST['model'] . '&totype=' . $totype . '&referer=' . $referer);
				$this->showmessage('开始生成栏目页...', $forward);
			}
			
			// 获取首次创建的需要更新的栏目序列缓存
			$catid_arr=getcache('update_html_catid' . '_' . $_SESSION['userid'], 'content', 'array');
			
			// 获取当前需要更新的栏目序列号（非栏目ID）
			$autoid=isset($_POST['autoid']) ? intval($_POST['autoid']) : 0;
			$autotypeid=isset($_POST['autotypeid']) ? intval($_POST['autotypeid']) : 0;
			
			// 若当前栏目序列号不存在于栏目序列中，则创建静态完成
			if(!isset($catid_arr[$autoid])){
				delcache('update_html_catid' . '_' . $_SESSION['userid'], 'content');
				if(!empty($referer) && $this->categorys[$catid_arr[0]]['type'] != 1){
					$this->showmessage('创建静态化完成！', act_url('content', 'manage', 'catid=' . $catid_arr[0]), 200);
				}else{
					$this->showmessage('创建静态化完成！', act_url('staticize', 'category'), 200);
				}
			}
			
			$catid=$catid_arr[$autoid]; // 当前栏目ID
			$page=isset($_POST['page']) ? intval($_POST['page']) : 1; // 当前页
			$pagesize=isset($_POST['pagesize']) ? intval($_POST['pagesize']) : 0; // 每页信息数
			
			if(isset($_POST['total_number'])){
				$total_number=intval($_POST['total_number']);
			}
			
			$GLOBALS['CUR_TOTAL_PAGES']=1;
			$j=1;
			if(!isset($_POST['set_typeid'])){
				do{
					$this->html->category($catid, $page);
					$page++;
					$j++;
					$total_number=isset($total_number) ? $total_number : $GLOBALS['CUR_TOTAL_PAGES'];
				}while($j <= $total_number && $j < $pagesize);
			}else{
				// 获取所有可供使用的栏目分类ID
				$alltypes=getcache('update_html_typeid_' . $catid . '_' . $_SESSION['userid'], 'content', 'array');
				
				if(!isset($alltypes[$autotypeid])){
					$autoid++;
					$message='栏目【' . $this->categorys[$catid]['name'] . '】所有分类静态化完成！';
					$forward=act_url('staticize', 'category', 'set_catid=1&pagesize=' . $pagesize . '&dosubmit=1&autoid=' . $autoid . '&model=' . $_POST['model'] . '&totype=' . $totype . '&referer=' . $referer);
					delcache('update_html_typeid_' . $catid . '_' . $_SESSION['userid'], 'content');
					$this->showmessage($message, $forward, 200);
				}
				
				$typeid=$alltypes[$autotypeid]['id'];
				$set_typeid=$_POST['set_typeid'];
				do{
					$this->html->category($catid, $page, $typeid);
					$page++;
					$j++;
					$total_number=isset($total_number) ? $total_number : $GLOBALS['CUR_TOTAL_PAGES'];
				}while($j <= $total_number && $j < $pagesize);
			}
			
			if($page <= $total_number){
				$endpage=intval($page + $pagesize);
				$message='生成栏目【' . $this->categorys[$catid]['name'] . '】' . (isset($set_typeid) ? ' 分类【' . $alltypes[$autotypeid]['name'] . '】' : '') . ' 从' . $page . '至' . $endpage . '页......';
				$typeurl=(isset($set_typeid) ? 'set_typeid=1&' : '') . ($autotypeid ? 'autotypeid=' . $autotypeid . '&' : '');
				$forward=act_url('staticize', 'category', $typeurl . 'set_catid=1&pagesize=' . $pagesize . '&dosubmit=1&autoid=' . $autoid . '&page=' . $page . '&total_number=' . $total_number . '&model=' . $_POST['model'] . '&totype=' . $totype . '&referer=' . $referer);
			}else{
				if(!isset($_POST['set_typeid'])){
					if($totype && $this->categorys[$catid]['type'] == 0 && get_formtype_fields($this->categorys[$catid]['model'], 'classid')){
						$classIds=$this->getClassIds($catid); // 获取所有分类ID
						setcache('update_html_typeid_' . $catid . '_' . $_SESSION['userid'], $classIds, 'content', 'array');
						$message='栏目【' . $this->categorys[$catid]['name'] . '】开始更新分类...';
						$forward=act_url('staticize', 'category', 'set_typeid=1&autotypeid=' . $autotypeid . '&pagesize=' . $pagesize . '&set_catid=1&dosubmit=1&autoid=' . $autoid . '&model=' . $_POST['model'] . '&totype=' . $totype . '&referer=' . $referer);
					}else{
						$autoid++;
						$message='栏目【' . $this->categorys[$catid]['name'] . '】静态化完成！';
						$forward=act_url('staticize', 'category', 'set_catid=1&pagesize=' . $pagesize . '&dosubmit=1&autoid=' . $autoid . '&model=' . $_POST['model'] . '&totype=' . $totype . '&referer=' . $referer);
					}
				}else{
					$message='栏目【' . $this->categorys[$catid]['name'] . '】 分类【' . $alltypes[$autotypeid++]['name'] . '】 静态化完成！';
					$forward=act_url('staticize', 'category', 'set_typeid=1&autotypeid=' . $autotypeid . '&pagesize=' . $pagesize . '&set_catid=1&dosubmit=1&autoid=' . $autoid . '&model=' . $_POST['model'] . '&totype=' . $totype . '&referer=' . $referer);
				}
			}
			
			$this->showmessage($message, $forward, 200);
		}else{
			$model=isset($_GET['model']) ? $_GET['model'] : '';
			$models=getcache('model', 'model', 'array');
			foreach($models as $k=>$v){
				if($v['iscat'] == 0 || $v['disabled'] == 1 || $v['isinstall'] == 0){
					unset($models[$k]);
				}
			}
			
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;│ ','&nbsp;&nbsp;├─ ','&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;';
			$categorys=array();
			if(!empty($this->categorys)){
				foreach($this->categorys as $catid=>$r){
					if($r['type'] == 2 && $r['arrcid'] == '')
						continue;
					if($model && $model != $r['model'])
						continue;
					if($r['arrcid'] == ''){
						if(!$r['setting']['category_ishtml'])
							continue;
					}
					$categorys[$catid]=$r;
				}
			}
			$str="<option value='\$id' \$selected>\$spacer \$name</option>";
			
			$tree->init($categorys);
			$string.=$tree->get_tree(0, $str);
			include template('category', 'staticize');
		}
	}

	public function show(){
		if(isset($_POST['dosubmit'])){
			!defined('STYLE_URL') && Application::setStyle(false);
			$this->html=load::cls('Html');
			
			$model=isset($_POST['model']) ? $_POST['model'] : '';
			$type=isset($_POST['type']) ? $_POST['type'] : '';
			if(isset($_POST['total'])){
				$total=intval($_POST['total']);
			}
			$pagesize=isset($_POST['pagesize']) ? intval($_POST['pagesize']) : 0;
			$pages=isset($_POST['pages']) ? intval($_POST['pages']) : 1;
			$page=isset($_POST['page']) ? intval($_POST['page']) : 1;
			$fromdate=isset($_POST['fromdate']) ? $_POST['fromdate'] : '';
			$todate=isset($_POST['todate']) ? $_POST['todate'] : '';
			
			if($model){
				// 设置模型数据表名
				$table_name=$this->getDb()->mTb($model);
				
				if($type == 'lastinput'){
					$offset=0;
				}else{
					$page=max(intval($_POST['page']), 1);
					$offset=$pagesize * ($page - 1);
				}
				$where=' where `status`=1 ';
				$order='asc';
				
				if(isset($_POST['catids'])){
					$catids=$_POST['catids'];
				}
				if(isset($_POST['first'])){
					$first=$_POST['first'];
				}
				
				if(!isset($first) && is_array($catids) && $catids[0] > 0){
					setcache('html_show_' . $_SESSION['userid'], $catids, 'content', 'array');
					$catids=implode(',', $catids);
					$where.=' and `catid` in(' . $catids . ') ';
					$first=1;
				}elseif(count($catids) == 1 && $catids[0] == 0){
					$catids=array();
					foreach($this->categorys as $cid=>$cat){
						if($cat['arrcid'] || $cat['type'] != 0){
							continue;
						}
						$setting=$cat['setting'];
						if(!$setting['show_ishtml']){
							continue;
						}
						$catids[]=$cid;
					}
					setcache('html_show_' . $_SESSION['userid'], $catids, 'content', 'array');
					$catids=implode(',', $catids);
					$where.=' and `catid` in(' . $catids . ') ';
					$first=1;
				}elseif(isset($first) && $first){
					$catids=getcache('html_show_' . $_SESSION['userid'], 'content', 'array');
					$catids=implode(',', $catids);
					$where.=' and `catid` in(' . $catids . ') ';
				}else{
					$first=0;
				}
				if(count($catids) == 1 && $catids[0] == 0){
					$this->showmessage('更新完成！', act_url('staticize', 'show'));
				}
				
				if($type == 'lastinput' && isset($_POST['number']) && $_POST['number']){
					$offset=0;
					$pagesize=intval($_POST['number']);
					$order='desc';
				}elseif($type == 'date'){
					if(isset($_POST['fromdate'])){
						$fromtime=strtotime($_POST['fromdate'] . ' 00:00:00');
						$where.=' and `addtime`>=' . $fromtime . ' ';
					}
					if(isset($_POST['todate'])){
						$totime=strtotime($_POST['todate'] . ' 23:59:59');
						$where.=' and `addtime`<=' . $totime . ' ';
					}
				}elseif($type == 'id'){
					$fromid=intval($_POST['fromid']);
					$toid=intval($_POST['toid']);
					if($fromid)
						$where.=' and `id`>=' . $fromid . ' ';
					if($toid)
						$where.=' and `id`<=' . $toid . ' ';
				}
				$start=0;
				if(!isset($total) && $type != 'lastinput'){
					$total=$this->getDb()->count($model, $where);
					$pages=ceil($total / $pagesize);
					$start=1;
				}
				
				$data=$this->getDb()->select($model, '*', $where, 'order by `id` ' . $order, 'limit ' . $offset . ',' . $pagesize);
				
				foreach($data as $r){
					$this->html->show($r, 'edit');
				}
				
				if($pages > $page){
					$page++;
					$http_url=get_url();
					$creatednum=$offset + count($data);
					$percent=round($creatednum / $total, 2) * 100;
					
					$message='共需更新 <font color="red">' . $total . '</font> 条信息 - 已完成 <font color="red">' . $creatednum . '</font> 条（<font color="red">' . $percent . '%</font>）';
					$forward=$start ? act_url('staticize', 'show', 'type=' . $type . '&dosubmit=1&first=' . $first . '&fromid=' . $fromid . '&toid=' . $toid . '&fromdate=' . $fromdate . '&todate=' . $todate . '&pagesize=' . $pagesize . '&page=' . $page . '&pages=' . $pages . '&total=' . $total . '&model=' . $model) : preg_replace("/&page=([0-9]+)&pages=([0-9]+)&total=([0-9]+)/", '&page=' . $page . '&pages=' . $pages . '&total=' . $total, $http_url);
				}else{
					delcache('html_show_' . $_SESSION['userid'], 'content');
					$message='更新完成！ ...';
					$forward=act_url('staticize', 'show');
				}
				$this->showmessage($message, $forward, 200);
			}else{
				// 当没有选择模型时，需要按照栏目来更新
				if(isset($_POST['catids'])){
					$catids=$_POST['catids'];
				}
				if(!isset($_POST['set_catid'])){
					if(isset($catids) && is_array($catids) && $catids[0] != 0){
						$update_url_catids=$catids;
					}else{
						foreach($this->categorys as $cid=>$cat){
							if($cat['arrcid'] || $cat['type'] != 0){
								continue;
							}
							$setting=string2array($cat['setting']);
							if(!$setting['show_ishtml']){
								continue;
							}
							$update_url_catids[]=$cid;
						}
					}
					setcache('update_html_catid' . '-' . $_SESSION['userid'], $update_url_catids, 'content', 'array');
					$message='开始更新......';
					$forward=act_url('staticize', 'show', 'set_catid=1&pagesize=' . $pagesize . '&dosubmit=1');
					$this->showmessage($message, $forward, 200);
				}
				if(count($catids) == 1 && $catids[0] == 0){
					$this->showmessage('更新完成！', act_url('staticize', 'show'), 200);
				}
				$catid_arr=getcache('update_html_catid' . '-' . $_SESSION['userid'], 'content', 'array');
				$autoid=isset($_POST['autoid']) ? intval($_POST['autoid']) : 0;
				if(!isset($catid_arr[$autoid])){
					$this->showmessage('更新完成！', act_url('staticize', 'show'), 200);
				}
				$catid=$catid_arr[$autoid];
				
				$model=$this->categorys[$catid]['model'];
				// 设置模型数据表名
				$table_name=$this->getDb()->mTb($model);
				
				$page=max(intval($page), 1);
				$offset=$pagesize * ($page - 1);
				$where=' where `status`=1 and `catid`=\'' . $catid . '\'';
				$order='asc';
				$start=0;
				
				if(!isset($total)){
					$total=$this->getDb()->count($model, $where);
					$pages=ceil($total / $pagesize);
					$start=1;
				}
				
				$data=$this->getDb()->select($model, '*', $where, 'order by `id` ' . $order, 'limit ' . $offset . ',' . $pagesize);
				foreach($data as $r){
					$this->html->show($r, 'edit');
				}
				
				if($pages > $page){
					$page++;
					$http_url=get_url();
					$creatednum=$offset + count($data);
					$percent=round($creatednum / $total, 2) * 100;
					$message='【' . $this->categorys[$catid]['name'] . '】 ' . '共需更新 <font color="red">' . $total . '</font> 条信息 - 已完成 <font color="red">' . $creatednum . '</font> 条（<font color="red">' . $percent . '%</font>）';
					$forward=$start ? act_url('staticize', 'show', 'type=' . $type . '&dosubmit=1&first=' . $first . '&fromid=' . $fromid . '&toid=' . $toid . '&fromdate=' . $fromdate . '&todate=' . $todate . '&pagesize=' . $pagesize . '&page=' . $page . '&pages=' . $pages . '&total=' . $total . '&autoid=' . $autoid . '&set_catid=1') : preg_replace("/&page=([0-9]+)&pages=([0-9]+)&total=([0-9]+)/", '&page=' . $page . '&pages=' . $pages . '&total=' . $total, $http_url);
				}else{
					$autoid++;
					$message='开始更新' . $this->categorys[$catid]['name'] . " ...";
					$forward=act_url('staticize', 'show', 'set_catid=1&pagesize=' . $pagesize . '&dosubmit=1&autoid=' . $autoid);
				}
				$this->showmessage($message, $forward, 200);
			}
		}else{
			$model=isset($_GET['model']) ? $_GET['model'] : '';
			$models=getcache('model', 'model', 'array');
			foreach($models as $k=>$v){
				if($v['iscat'] == 0 || $v['disabled'] == 1 || $v['isinstall'] == 0){
					unset($models[$k]);
				}
			}
			
			$isShowFromdate=!empty($model) && in_array($this->getDb()->mTb($model), $this->getDb()->tables()) && $this->getDb()->hasField($model, 'addtime') ? 1 : 0;
			
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;│ ','&nbsp;&nbsp;├─ ','&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;';
			$categorys=array();
			if(!empty($this->categorys)){
				foreach($this->categorys as $catid=>$r){
					if($r['type'] == 2 && $r['arrcid'] == '')
						continue;
					if($model && $model != $r['model'])
						continue;
					if($r['arrcid'] == ''){
						if(!$r['setting']['show_ishtml'])
							continue;
					}
					$r['disabled']=$r['arrcid'] != '' ? 'disabled' : '';
					$categorys[$catid]=$r;
				}
			}
			$str="<option value='\$id' \$selected \$disabled>\$spacer \$name</option>";
			
			$tree->init($categorys);
			$string.=$tree->get_tree(0, $str);
			include template('show', 'staticize');
		}
	}

	/**
	 * ******私有方法*******
	 */
	private function getClassIds($catid){
		$classifies=getcache('classify', 'classify', 'array');
		$catid=strval($catid);
		$classIds=array();
		foreach($classifies as $key=>$cls){
			$catids=explode(',', $cls['catids']);
			if(in_array($catid, $catids)){
				$cArr=getcache('classify', 'classify', 'array', $key);
				foreach($cArr as $cid=>$cvl){
					$classIds[]=$cvl;
				}
			}
		}
		return $classIds;
	}
}

?>