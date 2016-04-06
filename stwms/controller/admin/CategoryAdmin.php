<?php
defined('IN_MYCMS') or exit('No permission resources.');
load::func('dir');
class CategoryAdmin extends AdminController{
	public $urlrule;

	public function __construct(){
		parent::__construct();
		$this->urlrule=load::controller('urlrule');
		$this->xmlDbName='category';
	}

	public function init(){
		if(isset($_GET['get_template'])){
			alert($this->_get_template($_GET));
		}
		if(isset($_GET['check'])){
			alert($this->_check($_GET));
		}
		$tree=load::cls('Tree');
		$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp='&nbsp;&nbsp;&nbsp;';
		$result=getcache('category', 'core', 'array');
		
		// 获取模型
		$models=getcache('model', 'model', 'array');
		
		$array=array();
		$isusable=1;
		foreach($result as $k=>$r){
			if(!empty($r['model'])){
				if(empty($models[$r['model']]['disabled']) && isset($models[$r['model']]['isinstall']) && $models[$r['model']]['isinstall'] == '1'){
					$isusable=1;
				}else{
					$isusable=0;
				}
				$result[$k]['model']=$models[$r['model']]['name'];
			}else{
				$result[$k]['model']='—';
				$isusable=1;
			}
			
			$result[$k]['count']=$r['type'] == 0 && empty($r['arrcid']) ? $this->statistics($r['id'], $r['type']) : '-';
			$result[$k]['type']=$r['type'] == 0 ? '一般' : ($r['type'] == 1 ? '单页' : '外链');
			$result[$k]['url']=$isusable ? '<a href="' . cat_url($r) . '" target="_blank">查看</a>' : '<img title="提示：此栏目模型不可用，请启用或安装！" style="cursor:help;" src="' . STATIC_URL . 'common/images/warning_small.gif"/>';
		}
		unset($models);
		$tree->init($result);
		$categorys=$tree->get_array(0);
		$type=$_GET['type'];
		$auths=load::controller('role')->_check_auth('add,edit,del,listorder');
		include template('init', 'category');
	}

	public function add(){
		if(isset($_POST['dosubmit'])){
			$array=getcache('category', 'core', 'array');
			$pid=empty($_POST['info']['pid']) ? '' : intval($_POST['info']['pid']);
			$_POST['info']['arrpid']=get_parents($pid, $array, 'id', ',', true);
			$_POST['info']['pdir']=get_parents($pid, $array, 'cdir', '/', true);
			$this->simple_url(-1, $_POST['setting'], 'add');
			$_POST['info']['setting']=array2string($_POST['setting']);
			$this->getXmlDb()->trans('start');
			$resId=$this->getXmlDb()->insert('category', $_POST['info']);
			if($resId != -1){ // 更新子目录ID
				$url=$this->setUrl($_POST['info'], $_POST['setting'], $resId);
				$this->getXmlDb()->update('category', array('url' => $url), 'where `id`=' . $resId);
				$this->simple_url($resId, $_POST['setting'], 'add');
				$this->updateArrcid($pid);
				$this->_fresh();
			}
			$this->getXmlDb()->trans('end');
			$this->showmessage($resId != -1 ? '添加成功！' : '添加失败！');
		}else{
			$id=0;
			$tree=load::cls('Tree');
			$result=$this->getXmlDb()->select('category', '*', 'where `type`!=2', 'order by `listorder` desc,`id` asc');
			$tree->init($result);
			$select_categorys=$tree->get_array(0);
			
			// 获取模型
			$models=getcache('model', 'model', 'array');
			foreach($models as $k=>$v){
				if((isset($v['disabled']) && $v['disabled'] == '1') || empty($v['isinstall']) || $v['type'] == 2 || !$v['iscat']){
					unset($models[$k]);
				}
			}
			// 获取URL规则
			$urlrules=getcache('urlrule', 'core', 'array');
			$type=intval(isset($_GET['type']) ? $_GET['type'] : 0);
			chdir(CORE_PATH . 'template' . CD . 'styles' . CD);
			$styleArr=glob('*', GLOB_ONLYDIR);
			include template('add', 'category');
		}
	}

	public function edit(){
		$deIds=Param::get_para('id');
		if(empty($deIds)){
			$this->showmessage('请先选择栏目！');
		}
		if(!is_array($deIds)){
			$id=intval($deIds);
			$deIds=array($deIds);
		}
		$categorys=getcache('category', 'core', 'array');
		
		if(isset($_POST['dosubmit'])){
			$isMod=0;
			$this->getXmlDb()->trans('start');
			foreach($deIds as $id){
				$pid=empty($_POST['info'][$id]['pid']) ? '' : intval($_POST['info'][$id]['pid']);
				$_POST['info'][$id]['arrpid']=get_parents($pid, $categorys, 'id', ',', true);
				$_POST['info'][$id]['pdir']=get_parents($pid, $categorys, 'cdir', '/', true);
				$this->simple_url($id, $_POST['setting'][$id], 'edit', $categorys[$id]);
				$_POST['info'][$id]['url']=$this->setUrl($_POST['info'][$id], $_POST['setting'][$id], $id);
				
				$_POST['info'][$id]['setting']=array2string($_POST['setting'][$id]);
				$res=$this->getXmlDb()->update('category', $_POST['info'][$id], 'where `id`=' . $id);
				if($res){ // 更新子目录ID
					$this->updateArrcid($pid);
					$this->updateArrcid($id);
					$this->updateChildTemplate($id, $_POST['setting'][$id]);
					$isMod++;
				}
			}
			if($isMod){
				$this->getXmlDb()->trans('end');
				$this->_fresh();
			}
			if(count($deIds) == 1){
				$this->showmessage($isMod ? '修改成功！' : '修改失败！');
			}else{
				$this->showmessage($isMod ? '修改成功！' : '修改失败！', act_url('category', 'init'));
			}
		}else{
			$allcategorys=array();
			$urlrules=getcache('urlrule', 'core', 'array'); // 获取URL规则
			$tree=load::cls('Tree');
			$models=getcache('model', 'model', 'array'); // 获取所有模型
			$cat_seo=getcache('category', 'core', 'array', 'seo');
			foreach($models as $k=>$v){
				if((isset($v['disabled']) && $v['disabled'] == '1') || empty($v['isinstall']) || $v['type'] == 2 || !$v['iscat']){
					unset($models[$k]);
				}
			}
			
			foreach($deIds as $id){
				$categorys[$id]['seo']=$cat_seo[$id];
				$allcategorys[$id]=&$categorys[$id];
				$allcategorys[$id]['hasDatas']=$this->statistics($id, $categorys[$id]['type']);
				
				// 设置栏目选择数组
				$result=$this->getXmlDb()->select('category', '*', 'where `id`!=' . $id . ' and `pid`!=' . $id . ' and `type`!=2', 'order by `listorder` desc,`id` asc');
				$tree->init($result);
				unset($result);
				$allcategorys[$id]['select_categorys']=$tree->get_array(0);
				
				// 获取模板数组
				$template_styles=array();
				chdir(CORE_PATH . 'template' . CD . 'styles' . CD);
				$template_styles['style']=glob('*', GLOB_ONLYDIR);
				if(!empty($categorys[$id]['setting']['template_style'])){
					chdir(CORE_PATH . 'template' . CD . 'styles' . CD . $categorys[$id]['setting']['template_style']);
					$template_styles['category']=glob('category_*.html');
					$template_styles['list']=glob('list_*.html');
					$template_styles['show']=glob('{show,page}_*.html', GLOB_BRACE);
				}
				$allcategorys[$id]['template_styles']=$template_styles;
				unset($template_styles);
			}
			
			include template('edit', 'category');
		}
	}

	public function del(){
		$deIds=Param::get_para('id');
		if(empty($deIds)){
			$this->showmessage('删除操作失败！请先选择栏目');
		}
		if(!is_array($deIds)){
			$deIds=array($deIds);
		}
		
		$categorys=getcache('category', 'core', 'array');
		$delNum=0;
		$allNum=count($deIds);
		foreach($deIds as $id){
			$id=intval($id);
			if($this->statistics($id, $categorys[$id]['type'])){
				continue;
			}
			$childIDs=get_childs($id, $categorys, false); // 获取所有ID
			array_unshift($childIDs, $id);
			$this->getXmlDb()->trans('start');
			$isDel=false;
			foreach($childIDs as $cid){
				$res=$this->getXmlDb()->delete('category', 'where `id`=' . $cid);
				if($categorys[$cid]['type'] == 1){
					$this->delPage($cid); // 单页栏目删除数据
				}
				if($res){
					$this->del_simple_url($categorys[$cid]['setting']['simple_url']);
					$delNum++;
					$isDel=true;
				}
			}
			if($isDel){
				$this->updateArrcid($categorys[$id]['pid']);
			}
		}
		if($delNum){
			$this->getXmlDb()->trans('end');
			$this->_fresh();
		}
		$this->showmessage(!$delNum ? '删除操作失败！请清空要删除的栏目数据' : ($delNum >= $allNum ? '删除成功！' : '成功删除' . $delNum . '个栏目！'));
	}

	public function listorder(){
		if(isset($_POST['dosubmit'])){
			$categorys=getcache('category', 'core', 'array', 'base');
			$this->getXmlDb()->trans('start');
			foreach($_POST['listorders'] as $id=>$listorder){
				$this->getXmlDb()->update('category', array('listorder' => $listorder), 'where `id`=' . $id);
				$this->updateArrcid($categorys[$id]['pid']);
			}
			$this->getXmlDb()->trans('end');
			$this->_fresh();
			$this->showmessage('排序操作成功！', 'auto');
		}else{
			$this->showmessage('排序操作失败！', 'auto');
		}
	}

	/* * ****内部接口方法**** */
	public function _check($cfg){
		$pid=empty($cfg['pid']) ? '' : intval($cfg['pid']);
		$str='';
		if(isset($cfg['type']) && $cfg['type']){
			// 地址简化目录或文件检查
			$curl=strtolower(trim($cfg['curl'], '/\\ ')); // sss.php
			$curl=strpos($curl, '.php') === (strlen($curl) - 4) ? $curl : (str_replace('.', '_', $curl) . '/');
			if(empty($cfg['id'])){ // 添加验证
				$res=$this->getXmlDb()->getOne('category', 'id', 'where `pid`=\'\' and `cdir`=\'' . rtrim($curl, '/') . '\'');
				$str=!empty($res) || ($curl != '/' && is_file(ROOT_PATH . $curl)) ? '0' : '1';
			}else{ // 修改验证
				$res=$this->getXmlDb()->getOne('category', 'id', 'where `pid`=\'\' and `cdir`=\'' . rtrim($curl, '/') . '\' and `id`!=' . intval($cfg['id']));
				$str=!empty($res) || ($curl != '/' && is_file(ROOT_PATH . $curl)) ? '0' : '1';
			}
		}else{
			// 静态化目录检查
			$cdir=$cfg['cdir'];
			$html_root=trim(load::cfg('system', 'html_root'), '/ \/');
			if(!empty($html_root)){
				$rootDirs=array();
			}else{
				$rootDirs=glob(ROOT_PATH . '*', GLOB_ONLYDIR);
				foreach($rootDirs as $k=>$v){
					$rootDirs[$k]=str_replace(ROOT_PATH, '', $v);
				}
			}
			if(empty($cfg['id'])){ // 添加验证
				$res=$this->getXmlDb()->getOne('category', 'id', 'where `pid`=\'' . $pid . '\' and `cdir`=\'' . $cdir . '\'');
				$str=!empty($res) || in_array($cdir, $rootDirs) ? '0' : '1';
			}else{ // 修改验证
				$res=$this->getXmlDb()->getOne('category', 'id', 'where `pid`=\'' . $pid . '\' and `cdir`=\'' . $cdir . '\' and `id`!=' . intval($cfg['id']));
				$str=!empty($res) || in_array($cdir, $rootDirs) ? '0' : '1';
			}
		}
		return $str . $cfg['mcid'];
	}

	public function _get_template($cfg){
		$template_styles=array();
		if(!empty($cfg['style']) && is_dir(CORE_PATH . 'template' . CD . 'styles' . CD . $cfg['style'] . CD)){
			chdir(CORE_PATH . 'template' . CD . 'styles' . CD . $cfg['style'] . CD);
			if($cfg['type'] == 1){ // 单页可用模板
				$template_styles['category']=glob('{show,page}_*.html', GLOB_BRACE);
			}else{
				$template_styles['category']=glob('category_*.html');
				$template_styles['list']=glob('list_*.html');
				$template_styles['show']=glob('{show,page}_*.html', GLOB_BRACE);
			}
			$template_styles['mcid']=$cfg['mcid'];
		}
		return $template_styles;
	}

	public function _fresh($type=''){
		$categorys=$this->getXmlDb()->select('category', '*', 'where `id`>=0', 'order by `listorder` desc', '', '', 'id');
		$urlArr=array();
		$seoArr=array();
		$setURLKeys=array('category_ruleid','category_ishtml','show_ruleid','show_ishtml','simple_url');
		
		foreach($categorys as $k=>$v){
			$categorys[$k]['setting']=string2array($categorys[$k]['setting']);
			$seoArr[$k]=array('name' => $categorys[$k]['name'],'title' => $categorys[$k]['setting']['seo_title'],'keywords' => $categorys[$k]['setting']['seo_keywords'],'description' => $categorys[$k]['setting']['seo_desc']);
			$urlArr[$k]=array('type' => $categorys[$k]['type'],'url' => $categorys[$k]['url'],'pdir' => $categorys[$k]['pdir'],'cdir' => $categorys[$k]['cdir'],'pid' => $categorys[$k]['pid']);
			foreach($setURLKeys as $saveKy){
				if(isset($categorys[$k]['setting'][$saveKy])){
					$urlArr[$k]['setting'][$saveKy]=$categorys[$k]['setting'][$saveKy];
				}
			}
		}
		setcache('category', $categorys, 'core', 'array');
		foreach($categorys as $k=>$v){
			unset($categorys[$k]['setting']);
		}
		setcache('category_base', $categorys, 'core', 'array');
		setcache('category_url', $urlArr, 'core', 'array');
		setcache('category_seo', $seoArr, 'core', 'array');
	}

	public function _update_urls(){
		$categorys=getcache('category', 'core', 'array');
		$this->getXmlDb()->trans('start');
		foreach($categorys as $category){
			$url=$this->setUrl($category);
			$this->getXmlDb()->update('category', array('url' => $url), 'where `id`=' . $category['id']);
		}
		$this->getXmlDb()->trans('end');
		$this->_fresh();
	}

	/* * ****私有方法**** */
	private function updateArrcid($id){
		if(empty($id)){
			return false;
		}
		$arr=$this->getXmlDb()->select('category', 'id,pid,listorder', 'where `id`>=0', 'order by `listorder` desc');
		$ids=get_childs($id, $arr);
		unset($arr);
		$this->getXmlDb()->update('category', array('arrcid' => implode(',', $ids)), 'where `id`=' . $id);
		return $ids;
	}

	private function updateChildTemplate($cid,$setting){
		if($setting['apply_subcat'] && $setting['template_style']){
			$array=getcache('category', 'core', 'array');
			$childs=get_childs($cid, $array);
			$updates=array('template_style','template_category','template_list','template_show','apply_subcat');
			foreach($childs as $id){
				foreach($updates as $ky){
					$array[$id]['setting'][$ky]=$setting[$ky];
				}
				$array[$id]['setting']=array2string($array[$id]['setting']);
				$this->getXmlDb()->update('category', $array[$id], 'where `id`=' . $id);
			}
		}
	}

	/**
	 * 功能：删除相应的数据
	 */
	private function delPage($catid){
		$info=$this->getDb()->getOne('page', '*', 'where `catid`=' . $catid);
		if(!empty($info)){
			$this->getDb()->delete('page', 'where `catid`=' . $catid);
			$this->getDb()->delete('hits', 'where `catid`=' . $catid . ' and `hitsid`=' . $info['id']);
		}
	}

	private function setUrl($info,$setting=NULL,$id=NULL){
		if($info['type'] == 2){
			return $info['url'];
		}else{
			if(!is_null($setting)){
				$info['setting']=$setting;
			}
			if(!is_null($id)){
				$info['id']=$id;
			}
			// 【注】：此处返回的URL不包括html_root和ROOT_URL路径
			return load::controller('urlrule')->_setListURL($info, 1, '', 'page', 0);
		}
	}

	private function simple_url($catid,&$new_cat,$type='edit',&$old_cat=array()){
		$simple_url=trim($new_cat['simple_url'], '/\\ ');
		if(strpos($simple_url, '//') !== false || strpos($simple_url, '\\\\') !== false){
			$simple_url=preg_replace("/[\/\\\]+/i", '/', $simple_url);
		}
		
		if(empty($simple_url)){
			$new_cat['simple_url']='';
			$simple_url='';
		}else{
			$simple_url=strripos($simple_url, '.php') === (strlen($simple_url) - 4) ? $simple_url : ($simple_url . '/');
			$new_cat['simple_url']=$simple_url;
		}
		
		if($catid == -1){
			return $simple_url;
		}
		
		if(!empty($simple_url)){
			$filename=ROOT_PATH . $simple_url . (substr($simple_url, -1, 1) == '/' ? 'index.php' : '');
		}
		
		if($type == 'edit'){
			if($old_cat['setting']['category_ishtml'] == 0 && !empty($old_cat['setting']['simple_url'])){
				if($new_cat['category_ishtml'] == 0 && $new_cat['simple_url'] == $old_cat['setting']['simple_url'] && is_file($filename)){
					return true;
				}
				$this->del_simple_url($old_cat['setting']['simple_url']); // 删除以前的文件
			}
		}
		
		if(!empty($simple_url) && $new_cat['category_ishtml'] == 0){
			$filepath=dirname($filename);
			if(!is_dir($filepath)){
				@mkdir($filepath, 0777, true);
			}
			$filepath=str_replace('/', CD, $filepath);
			
			// 计算相对路径
			$arr_core=explode(CD, rtrim(CORE_PATH, CD));
			$arr_curr=explode(CD, $filepath);
			foreach($arr_curr as $ky=>$vl){
				if($arr_curr[$ky] != $arr_core[$ky]){
					break;
				}else{
					unset($arr_core[$ky], $arr_curr[$ky]);
				}
			}
			$inc_path=str_repeat('../', count($arr_curr)) . implode('/', $arr_core);
			
			$sptor=str_repeat('../', substr_count($simple_url, '/'));
			$cnt='<?php' . "\r\n" . '$_GET[\'catid\']=\'' . $catid . '\';$_GET[\'c\']=\'content\';' . "\r\n" . '$_GET[\'a\']=isset($_GET[\'id\'])&&!empty($_GET[\'id\'])?\'show\':\'lists\';' . "\r\n" . 'define(\'ROOT_URL\',\'' . ROOT_URL . '\');' . "\r\n" . 'define(\'ROOT_PATH\',dirname(__FILE__).DIRECTORY_SEPARATOR.\'' . $sptor . '\');' . "\r\n" . 'include \'' . $inc_path . '/base.php\';' . "\r\n" . 'load::app();' . "\r\n" . '?>';
			file_put_contents($filename, $cnt);
		}
	}

	private function del_simple_url($simple_url){
		$filename=$simple_url . (substr($simple_url, -1, 1) == '/' ? 'index.php' : '');
		if(is_file($filename)){
			@unlink($filename); // 删除文件
			if(strpos($filename, '/') !== false){
				$del_dir=ROOT_PATH . substr($filename, 0, strpos($filename, '/') + 1);
				dir_delete($del_dir, 1); // 删除空的文件夹
			}
		}
	}

	private function statistics($catid,$type=0){
		return $type != 0 ? 0 : $this->getDb()->count('hits', 'where `catid`=' . $catid);
	}
}

?>