<?php
return array(
//常规设置
 'version'=>'stwms v3.0',
  'charset' => 'utf-8', //网站字符集
	'timezone' => 'Etc/GMT-8', //网站时区（只对php 5.1以上版本有效），Etc/GMT-8 实际表示的是 GMT+8
	'lang' => 'zh-cn',  //网站语言包	
	'debug' => 0, //是否显示调试信息
	'lock_ex' => 1, //写入缓存时是否建立文件互斥锁定（如果使用nfs建议关闭）

  'db_conn' => 'sqlite3', //系统启用的数据库连接配置，数据库连接在database.cfg.php文件中配置
  'attachment_stat' => 1, //附件状态使用情况统计
	'admin_log' => 0, //是否记录后台操作日志
	'errorlog' => 0, //1、保存错误日志到 cache/error_log.php | 0、在页面直接显示
	'errorlog_size' => 20, //错误日志预警大小，单位：M
	'maxloginfailedtimes' => 8, //后台最大登陆失败次数
	'minrefreshtime' => 600, //登录失败到达最大次数后，重试间隔时间
  'category_ajax' => 0,
  'gzip' => 0,
  'plugin_sessions' => 'memberid', //设置外部插件用于权限配置的SESSION名称，多个用逗号隔开


//界面风格配置
  'style' => 'blue',//管理系统风格
  'template' => 'daoke',//前台模板名称，位于template/目录
  'static' => 'html/',//前台访问静态文件路径，如果为相对路径则相对于STATIC_URL/styles
  'html_root' => 'dk/',//静态文件生成路径，相对于网站根目录
  
//Session配置
	'session_prefix' => 'sts_',
	'var_session_id'=>'PHPSESSID',

//Cookie配置
	'cookie_domain' => '', //Cookie 作用域
	'cookie_path' => '', //Cookie 作用路径
	'cookie_pre' => 'stc_', //Cookie 前缀，同一域名下安装多套系统时，请修改Cookie前缀
	'cookie_ttl' => 0, //Cookie 生命周期，0 表示随浏览器进程

//安全配置
  'auth_key' => 'b7fa4c8fdeb29b39', //密钥
 );
?>