<?php
class plugin_message extends Controller{
	public $id, $url, $path, $db, $pageType;

	public function __construct($config){
		// 获取插件信息
		$this->pageType='page';
		$this->id=$config['id'];
		$this->url=STATIC_URL . 'plugin/' . $config['install_dir'] . '/';
		$this->path=CORE_PATH . 'plugin' . CD . $config['install_dir'] . CD;
	}
	
	// 插件外部方法，无需权限访问
	public function add(){
		session_start();
		if(strtolower($_SESSION['checkcode']) != strtolower($_POST['cfgnum'])){
			echo '2';
		}else{
			$_POST=safe_replace($_POST);
			$_POST['ip']=$_POST['ip'] ? $_POST['ip'] : getIP();
			$_POST['addtime']=time();
			$_POST['isdeal']=0;
			M('message_db')->add($_POST);
			echo '1';
		}
	}
	
	// 插件管理方法，需要管理权限访问
	public function m_init(){
		$pageType=$this->pageType;
		$where='where `id`>0';
		$cpage=isset($_GET[$pageType]) ? $_GET[$pageType] : 1;
		$total=M('message_db')->where($where)->count();
		$psize=(isset($_SESSION['custom']['lsize']) ? $_SESSION['custom']['lsize'] : 16);
		$limit=Db::getlimit($cpage, $psize, $total,false);
		$datas=M('message_db')->where($where)->order('`id` desc')->limit($limit);
		$pages=getpage(array(
				'total' => $total,
				'cPage' => $cpage,
				'size' => $psize,
				'type' => $pageType
		), 5);
		include template('init', 'admin');
	}

	public function m_show(){
		$id=intval($_GET['id']);
		$data=M('message_db')->where('`id`=' . $id)->field();
		@extract($data);
		include template('show', 'admin');
	}

	public function m_del(){
		$id=intval($_GET['id']);
		$res=M('message_db')->where('`id`=' . $id)->delete();
		showmessage($res ? '操作成功！' : '操作失败！', plugin_url('message', 'init', $this->pageType . '=' . $_GET[$this->pageType]));
	}

	public function m_deal(){
		$isdeal=intval($_GET['isdeal']);
		$id=intval($_GET['id']);
		$res=M('message_db')->where('`id`=' . $id)->save(array('isdeal' => $isdeal));
		showmessage($res ? '操作成功！' : '操作失败！', plugin_url('message', 'init', $this->pageType . '=' . $_GET[$this->pageType]));
	}
	
	// 插件标签方法，在标签中调用，提供UI界面
	public function _tag_add(){
		include template('add', 'ui');
	}
	
}
?>