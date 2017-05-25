<?php
defined('IN_MYCMS') or exit('No permission resources.');
class RoleAdmin extends AdminController{
	private $actList, $menuMap, $roleData;

	public function __construct(){
		parent::__construct();
		$this->actList=array('manage','listorder','add','del','edit','trash','push');
		$this->xmlDbName='user';
	}

	public function init(){
		$data=getcache('role', 'users', 'array');
		$auths=load::controller('role')->_check_auth('user.view,rolemenu.init,authority,category,plugin,add,edit,del,listorder');
		include template('init', 'role');
	}
	
	// 权限设置
	public function authority(){
		if(isset($_GET['dosubmit'])){
			$roleid=intval($_GET['id']);
			if($roleid != 2 && $this->getXmlDb()->update('role', array('authority' => implode('|', $_POST['menuid'])), 'where `id`=' . $roleid)){
				$pluginMenus=load::controller('menu')->_getPluginMenuInfo();
				$pluginObj=load::controller('plugin');
				$roleData=getcache('role', 'users', 'array');
				$pluginIDs=explode('|', $roleData[$roleid]['plugin']);
				$exceptIds=array();
				foreach($pluginMenus as $v){
					if(!in_array($v['id'], $_POST['menuid'])){
						$delIds=$pluginObj->_getInfoByEntry($v['actionid'], 'id');
						$exceptIds=array_merge($exceptIds, $delIds);
					}
				}
				$pluginIDstr=implode('|', array_diff($pluginIDs, $exceptIds));
				$this->getXmlDb()->update('role', array('plugin' => $pluginIDstr), 'where `id`=' . $roleid);
				$this->_fresh();
				alert('保存成功！');
			}else{
				alert('保存失败！');
			}
		}else{
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;&nbsp;';
			$result=getcache('menu', 'menu', 'array');
			$roleData=getcache('role', 'users', 'array');
			$roleid=intval($_GET['id']);
			
			foreach($result as $n=>$m){
				$result[$n]['checked']=($this->is_checked($m['id'], $roleData[$roleid]['authority'])) ? ' checked' : '';
				$result[$n]['level']=$this->get_level($m['id'], $result);
				$result[$n]['parentid_node']=($m['pid']) ? ' class="child-of-node-' . $m['pid'] . '"' : '';
			}
			$tree->init($result);
			$categorys=$tree->get_array(0);
			include template('authority', 'role');
		}
	}
	
	// 栏目权限
	public function category(){
		$roleid=intval($_GET['id']);
		if(isset($_GET['dosubmit'])){
			if($roleid != 2 && $this->getXmlDb()->update('role', array('category' => self::get_priv_code($_POST['priv'], 'encode')), 'where `id`=' . $roleid)){
				$this->_fresh();
				alert('保存成功！');
			}else{
				alert('保存失败！');
			}
		}else{
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;&nbsp;';
			$category=getcache('category', 'core', 'array', 'base');
			// 获取模型
			$privs=$this->get_priv($roleid);
			$roleData=getcache('role', 'users', 'array');
			$menuMap=getcache('menu', 'menu', 'array', 'map');
			
			foreach($category as $cid=>$v){
				if(!in_array($v['type'], array(0,1))){
					unset($category[$cid]);
				}else{
					$category[$cid]=array_merge($v, $this->get_cat_priv($v['type'], $cid, $privs, $menuMap, $roleData[$roleid]['authority']));
				}
			}
			unset($privs, $roleData, $menuMap);
			
			$manages=$this->actList;
			foreach($manages as $key=>$act){
				$manages[$key]="<td align='center'><input type='checkbox' name='priv[\$id][]' \$" . $act . "_check \$" . $act . "_disabled value='" . $act . "' ></td>";
			}
			$str="<tr>
					<td align='center'><input type='checkbox'  value='1' onclick='select_all_auth(\$id, this)' ></td>
				  <td> \$spacer\$name </td>
				  " . implode('', $manages) . "

			  </tr>";
			$tree->init($category);
			$categorys=$tree->get_tree(0, $str);
			include template('category', 'role');
		}
	}
	
	// 插件权限
	public function plugin(){
		if(isset($_GET['dosubmit'])){
			$roleid=intval($_GET['id']);
			if($roleid != 2 && $this->getXmlDb()->update('role', array('plugin' => implode('|', $_POST['menuid'])), 'where `id`=' . $roleid)){
				$this->_fresh();
				alert('保存成功！');
			}else{
				alert('保存失败！');
			}
		}else{
			$tree=load::cls('Tree');
			$tree->icon=array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp='&nbsp;&nbsp;&nbsp;';
			
			$roleData=getcache('role', 'users', 'array');
			$roleid=intval($_GET['id']);
			
			// 生成插件权限数组
			$result=array();
			$plugin=getcache('plugin', 'core', 'array');
			$pluginMenu=getcache('plugin', 'core', 'array', 'menu');
			$menuObj=load::controller('menu');
			foreach($plugin as $v){
				$menuId=$menuObj->_getMenuInfoByActionID($v['entry'], 'id');
				if($this->is_checked($menuId, $roleData[$roleid]['authority'])){
					foreach($pluginMenu as $pv){
						if($pv['pluginid'] == $v['id']){
							if($pv['id'] == $v['entry']){
								$pv['pid']=0;
								$pv['name']=$v['alias'];
								$pv['display']=1;
							}else{
								$pv['pid']=$v['entry'];
								$pv['display']=0;
							}
							$result[$pv['id']]=$pv;
						}
					}
				}
			}
			unset($menuObj, $plugin, $pluginMenu);
			
			foreach($result as $n=>$m){
				$result[$n]['checked']=($this->is_checked($m['id'], $roleData[$roleid]['plugin'])) ? ' checked' : '';
				$result[$n]['level']=$this->get_level($m['id'], $result);
				$result[$n]['parentid_node']=($m['pid']) ? ' class="child-of-node-' . $m['pid'] . '"' : '';
			}
			$tree->init($result);
			$categorys=$tree->get_array(0);
			
			include template('authority', 'role');
		}
	}
	
	// 添加
	public function add(){
		if(isset($_GET['dosubmit'])){
			$res=$this->getXmlDb()->insert('role', $_POST['info']);
			if($res != -1){
				$this->_fresh();
			}
			alert($res != -1 ? '添加角色信息成功！' : '添加角色信息失败！');
		}else{
			include template('add', 'role');
		}
	}
	
	// 编辑
	public function edit(){
		if(isset($_GET['dosubmit'])){
			$roleid=intval($_POST['id']);
			$res=($roleid != 2 ? $this->getXmlDb()->update('role', $_POST['info'], 'where `id`=' . $roleid) : 0);
			if($res){
				$this->_fresh();
			}
			alert($res ? '修改角色信息成功！' : '修改角色信息失败！');
		}else{
			$roleid=intval($_GET['id']);
			$r=$this->getXmlDb()->getOne('role', '*', 'where `id`=' . $roleid);
			if($r)
				extract($r);
			include template('edit', 'role');
		}
	}
	
	// 删除
	public function del(){
		$roleid=intval($_GET['id']);
		$res=($roleid != 2 ? $this->getXmlDb()->delete('role', 'where `id`=' . $roleid) : 0);
		if($res){
			$this->_fresh();
		}
		$this->showmessage($res ? '删除角色信息成功！' : '删除角色信息失败！');
	}
	
	// 排序
	public function listorder(){
		if(isset($_POST['dosubmit'])){
			$isSuccess=1;
			$this->getXmlDb()->trans('start');
			foreach($_POST['listorders'] as $id=>$listorder){
				$res=$this->getXmlDb()->update('role', array('listorder' => $listorder), 'where `id`=' . $id);
				if(!$res){
					$isSuccess=0;
				}
			}
			$this->getXmlDb()->trans('end');
			if($isSuccess){
				$this->_fresh();
			}
			$this->showmessage($isSuccess ? '排序操作成功！' : '排序操作失败！');
		}else{
			$this->showmessage('排序操作失败！');
		}
	}

	/* * *************************外部接口方法**************************** */
	public function _fresh(){
		$roles=$this->getXmlDb()->select('role', '*', 'where `id`>=0', 'order by `listorder` asc');
		$reData=array();
		foreach($roles as $v){
			$reData[$v['id']]=$v;
		}
		unset($roles);
		setcache('role', $reData, 'users', 'array');
	}

	/*
	 * * 功能：从源或从菜单数据库获取 参数：_get_menu($roleid 角色ID,$fromSource=1 适合从源获取) 说明：获取菜单，默认从数据源获取
	 */
	public function _get_menu($roleid,$fromSource=0){
		if(!$fromSource){
			$uMenuPath=CORE_PATH . 'data' . CD . 'menu' . CD . 'rolemenu_' . $roleid . '.php';
			if(!is_file($uMenuPath)){
				$roleArr=$this->_get_menu($roleid, 1);
				if(!$roleArr){
					return array();
				}
				$menuDb=load::db('xml', 'rolemenu_' . $roleid, 'menu', 1);
				$menuDb->create('menu', 'id|pid|name|c|a|data|display|type|listorder|icon');
				foreach($roleArr as $v){
					$menuDb->insert('menu', $v);
				}
				$menuDb->save($uMenuPath);
			}
			return getcache('rolemenu', 'menu', 'array', $roleid);
		}
		
		$roles=getcache('role', 'users', 'array');
		
		if(!$roles[$roleid]){
			return false;
		}
		
		$allMenus=array();
		$result=getcache('menu', 'menu', 'array', 'display');
		$contentPid=0;
		foreach($result as $k=>$r){
			if($this->is_checked($r['id'], $roles[$roleid]['authority'])){
				if($r['c'] == 'content' && $r['a'] == 'init'){
					$contentPid=$r['id'];
				}
				$r['type']=1;
				$allMenus[$r['id']]=$r;
			}
		}
		$startCid=max(array_keys($result));
		unset($result);
		
		$category=getcache('category', 'core', 'array', 'base');
		$roleprivs=$this->get_priv($roleid);
		
		foreach($roleprivs as $cid=>$cv){
			$mid=$category[$cid]['id'] + $startCid;
			$mpid=$category[$cid]['pid'] ? $category[$cid]['pid'] + $startCid : $contentPid;
			$cmenu=array('id' => $mid,'pid' => $mpid,'name' => $category[$cid]['name'],'c' => 'content','a' => 'manage','data' => 'catid=' . $category[$cid]['id'],'display' => 1,'listorder' => 0,'type' => 1,'icon' => '');
			$allMenus[$mid]=$cmenu;
		}
		ksort($allMenus);
		unset($category);
		return $allMenus;
	}

	/*
	 * * 功能：检测用户对每个控制器类和方法的操作权限 参数：_check_auth($c 控制器类名,$a 控制器方法,$actid操作id, 角色ID,$roleid 角色ID)
	 */
	public function _check_auth($c,$a='',$actid=NULL,$roleid=NULL){
		if(is_array($c) || strpos($c, ',')){
			$roleid=($a === '' ? NULL : $a);
			if(is_string($c)){
				$c=explode(',', $c);
			}
			$reArr=array();
			foreach($c as $caid){
				$cArrs=explode('.', $caid);
				if(!isset($cArrs[1])){
					$cArrs[1]=$cArrs[0];
					$cArrs[0]=ROUTE_C;
				}else{
					$caid=$cArrs[0] . '_' . $cArrs[1];
				}
				$reArr[$caid]=$this->_check_auth($cArrs[0], $cArrs[1], (!isset($cArrs[2]) ? -1 : intval($cArrs[2])), $roleid);
			}
			return $reArr;
		}else{
			if(empty($a)){
				$a='';
			}
			if(is_null($roleid)){
				$roleid=$_SESSION['roleid'];
			}
			if($roleid != 1 && $roleid != 2){
				$ca=$c . $a;
				if(is_null($this->menuMap)){
					$this->menuMap=getcache('menu', 'menu', 'array', 'map');
				}
				if(isset($this->menuMap[$ca])){
					if(is_null($this->roleData)){
						$this->roleData=getcache('role', 'users', 'array');
					}
					
					if($this->is_checked($this->menuMap[$ca], $this->roleData[$roleid]['authority'])){
						if($c == 'content' && in_array($a, $this->actList) && $actid != -1){ // 对栏目操作进行检查
							$catid=is_null($actid) ? intval(Param::get_para('catid')) : $actid;
							if(!empty($catid)){
								$allowcategorys=self::get_priv_code($this->roleData[$roleid]['category'], 'decode');
								if(isset($allowcategorys[$catid]) && in_array($a, $allowcategorys[$catid])){
									return true;
								}
							}
							return false;
						}else if($c == 'plugin' && $a == 'admin' && $actid != -1){
							$actionid=is_null($actid) ? intval(Param::get_para('actionid')) : $actid;
							return $actionid ? $this->is_checked($actionid, $this->roleData[$roleid]['plugin']) : $this->_check_plugin_auth(Param::get_para('plugin_c'), Param::get_para('plugin_a'), $roleid);
						}
						return true; // 对非栏目操作的权限
					}
				}
				return false;
			}
			return true; // 系统管理员或创始人直接允许所有权限
		}
	}

	public function _check_plugin_auth($c,$a='',$roleid=NULL){
		if(is_array($c) || strpos($c, ',')){
			$roleid=($a === '' ? NULL : $a);
			if(is_string($c)){
				$c=explode(',', $c);
			}
			$reArr=array();
			$plugin_c=NULL;
			foreach($c as $caid){
				$cArrs=explode('_', $caid);
				if(!isset($cArrs[1])){
					$cArrs[1]=$cArrs[0];
					// 此处如果没有提供插件名称，会先从plugin_c参数获取，然后从尝试从正在运行的插件获取
					if(is_null($plugin_c)){
						$plugin_c=Param::get_para('plugin_c');
						if(!$plugin_c && isset($GLOBALS[PLUGIN_ID])){
							$plugin_path=CORE_PATH . 'plugin' . CD . $GLOBALS[PLUGIN_ID] . CD . 'config.php';
							if(is_file($plugin_path)){
								$cfg=include ($plugin_path);
								$plugin_c=$cfg['name'];
							}
						}
					}
					$cArrs[0]=$plugin_c;
				}
				$reArr[$caid]=$this->_check_plugin_auth($cArrs[0], $cArrs[1], $roleid);
			}
			return $reArr;
		}else if($c){
			if(is_null($roleid)){
				$roleid=$_SESSION['roleid'];
			}
			if($roleid == 1 || $roleid == 2){
				return true;
			}
			if(empty($a)){
				$plugin_maps=& getLcache('plugin', 'core', 'array', 'map');
				if(isset($plugin_maps[$c])){
					$plugin_menus=getcache('plugin', 'core', 'array', 'menu');
					$a=$plugin_menus[$plugin_maps[$c]['entry']]['a'];
				}
			}
			$ca=$c . $a;
			$camaps=getcache('plugin', 'core', 'array', 'camap');
			return $camaps[$ca] ? $this->is_checked($camaps[$ca], $this->roleData[$roleid]['plugin']) : false;
		}
		return false;
	}

	/* * *************************内部私有方法**************************** */
	// 检查指定菜单是否有权限
	private function is_checked($menuid,$data){
		if(empty($data)){
			return false;
		}
		$data=explode('|', $data);
		return in_array($menuid, $data);
	}
	
	// 是否为设置状态
	private function get_level($id,$array=array(),$i=0){
		foreach($array as $n=>$value){
			if($value['id'] == $id){
				if($value['pid'] == '0')
					return $i;
				$i++;
				return $this->get_level($value['pid'], $array, $i);
			}
		}
	}
	
	// 获取权限
	private function get_priv($roleid){
		$info=$this->getXmlDb()->getOne('role', 'id,category', 'where `id`=' . $roleid);
		return self::get_priv_code($info['category'], 'decode');
	}
	
	// 获取栏目权限
	private function get_cat_priv($type,$cid=-1,&$privs=array(),&$menuMap=array(),$authority=''){
		$reArr=array();
		$cat_isset=isset($privs[$cid]);
		foreach($this->actList as $act){
			$reArr[$act . '_disabled']=($type == 1 && $act != 'manage' && $act != 'edit') || !$this->is_checked($menuMap['content' . $act], $authority) ? 'disabled="disabled"' : '';
			$reArr[$act . '_check']=$cat_isset && !$reArr[$act . '_disabled'] && in_array($act, $privs[$cid]) && ($type != 1 || ($act == 'manage' || $act == 'edit')) ? 'checked="checked"' : '';
		}
		return $reArr;
	}

	/* * *************************静态方法************************* */
	public static function get_priv_code($data,$type='encode'){
		if($type == 'encode'){ // 编码
			if(is_array($data)){
				$reArr=$actArr=array();
				foreach($data as $cid=>$cacts){
					foreach($cacts as $act){
						if(!isset($actArr[$act])){
							$actArr[$act]=array();
						}
						$actArr[$act][]=$cid;
					}
				}
				foreach($actArr as $act=>$ids){
					$reArr[]=$act . ':' . implode(',', $ids);
				}
				return implode('|', $reArr);
			}
		}else{ // 解码
			if(is_string($data)){
				$reArr=array();
				if(!empty($data)){
					$dataArr=explode('|', $data);
					foreach($dataArr as $dv){
						$dvArr=explode(':', $dv);
						if(!empty($dvArr[1])){
							$ids=explode(',', $dvArr[1]);
							foreach($ids as $cid){
								$reArr[intval($cid)][]=$dvArr[0];
							}
						}
					}
				}
				return $reArr;
			}
		}
		return false;
	}
}

?>