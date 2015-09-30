<?php
	header('Content-Type:text/html;charset=utf-8');		//设置系统的输出字符为utf-8
	date_default_timezone_set('PRC');					//设置时区（中国）
	
	//PHP程序所有需要的路径，都是相对路径
	define('HUPHP_PATH', rtrim(HUPHP, '/').'/');		//HuPHP框架的路径
	define('APP_PATH', rtrim(APP, '/').'/');			//用户项目的应用路径
	define('PROJECT_PATH', dirname(HUPHP_PATH).'/');	//项目的根路径，也就是框架所在目录
	define('TMPPATH', str_replace(array('.', '/'), '_', ltrim($_SERVER['SCRIPT_NAME'] ,'/')).'/');	//框架程序缓存目录名字

	//包含系统配置文件
	$config = PROJECT_PATH.'config.inc.php';
	if(file_exists($config)){
		include $config;
	}
	
	//设置DeBug模式
	if(defined('DEBUG') && DEBUG == 1){
		$GLOBALS['debug'] = 1;									//初例化开启Debug
		error_reporting(E_ALL ^ E_NOTICE);						//输出除了注意以外的所有错误报告
		include(HUPHP_PATH.'bases/debug.class.php');			//先包含Debug类
		Debug::start();											//开启脚本的计算时间
		set_error_handler(array('Debug', 'Catcher'));			//设置捕获系统异常的方法
		DEBUG::addmsg('<b>Debug</b>类' ,1);
	}else{
		ini_set('display_errors', 'Off');						//屏蔽所有错误输出
		ini_set('log_error', 'On');								//开启错误日志，将错误写到错误的日志中
		ini_set('error_log', PROJECT_PATH.'runtime/error_log');	//指定错误日志的文件
	}
	
	//包含框架中的函数库文件
	include(HUPHP_PATH.'commons/functions.inc.php');
	
	//包含全局的函数库文件，用户可以在这个文件中定义自己的函数
	$funfile = PROJECT_PATH.'commons/functions.inc.php';
	if(file_exists($funfile)){
		include $funfile;
	}

	//设置包含的目录，类所在的全部目录，PATH_SEPARATOR分割符号 Linux (:) Windows (;)
	$include_path = get_include_path();						//原基目录
	$include_path .= PATH_SEPARATOR.HUPHP_PATH.'bases/';		//框架中的基类所在的目录
	$include_path .= PATH_SEPARATOR.HUPHP_PATH.'classes/';		//框架中的扩展类所在的目录
	$include_path .= PATH_SEPARATOR.HUPHP_PATH.'libs/';			//Smarty模板引擎源码所在目录
	$include_path .= PATH_SEPARATOR.PROJECT_PATH.'classes/';	//项目用到项目根目录的自定义类目录	
	$controlerpath = PROJECT_PATH.'runtime/controls/'.TMPPATH;	//生成控制器所在的路径
	$include_path .= PATH_SEPARATOR.$controlerpath;
	
	//设置使用inculde包含文件可直接包含到的不需要加路径的文件夹（变量$include_path设置的文件夹范围）
	set_include_path($include_path);
	
	//自动加载类
	function __autoload($className){
		if($className == 'Memcache'){		//如果是系统的Memcache则不包含
			return;
		}else if($className == 'Smarty'){	//如果类名是Smarty，则直接包含
			include('Smarty.class.php');
		}else{
			include(strtolower($className).'.class.php');
		}

		DEBUG::addmsg('<b>'.$className.'</b>类' , 1);	//在Debug消息中显示自动包含的类
	}
	
	//判断是否开启了Smarty页面静态化缓存
	if(CSTART == 0){
		Debug::addmsg('<font color="red">没有开启Smarty页面静态化缓存！</font>（但可以使用）');
	}else{
		Debug::addmsg('<font color="green">开启了Smarty页面静态化缓存，实现页面静态化！</font>');
	}
	
	//启用Memcache缓存
	if(!empty($memServers)){						//判断是否设置config了Memchache的变量或数组
		if(extension_loaded('memcache')){			//判断是否安装了Memcache缓存模块
			$mem = new MemcacheModel($memServers);
			//判断Memcache缓存服务器是否有异常
			if(!$mem->mem_connect_error()){
				Debug::addmsg('<font color="red">连接Memcache服务器失败，请检查！</font>');
			}else{
				define('USEMEM', true);				//设置Memcahce开启的常量
				Debug::addmsg('<font color="green">启用Memcache服务器</font>');
			}
		}else{
			Debug::addmsg('<font color="red">PHP没有安装Memcache扩展模块，请先安装！</font>');
		}
	}else{
		Debug::addmsg('<font color="red">没有使用Memcache缓存服务器！</font>（为了程序的速度，建议使用Memcache缓存服务器）');
	}
	
	//如过开启了Memcache，则将Session信息保存在Memcache缓存服务器中
	if(defined('USEMEM')){
		MemSession::start($mem->getMem());
		Debug::addmsg('<font color="green">开启了Session（使用Memcache缓存会话信息）</font>');
	}else{
		session_start();
		Debug::addmsg('<font color="green">开启了Session</font><font color="red">（但没有使用Memcache缓存Session信息，建议开启Memcache后将自动启动）</font>');
	}
	
	Debug::addmsg('会话ID：'.session_id());
	
	Structure::create();		//初始化，部署项目的目录结构
	Prourl::parseUrl();			//解析处理URL
	
	//模板文件中所有要的路径，html\css\javascript\image\link等中用到的路径，从WEB服务器的文档根开始
	$spath = rtrim(substr(dirname(str_replace('\\', '/', dirname(__FILE__))), strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'))), '/\\');
	$GLOBALS['root'] = $spath.'/';							//WEB服务器到项目的根
	$GLOBALS['public'] = $GLOBALS['root'].'public/';		//项目的全局资源目录
	$GLOBALS['res'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\').'/'.ltrim(APP_PATH, './').'views/'.TPLSTYLE.'/resource/';	//当前应用模版的资源
	
	//判断是否开启伪静态模式
	if(REWRITE && strpos($_SERVER['SCRIPT_NAME'], 'index.php')){
		$GLOBALS['app'] = '/';			//当前应用脚本的文件
	}else{
		$GLOBALS['app'] = $_SERVER['SCRIPT_NAME'].'/';			//当前应用脚本的文件
	}
	
	$GLOBALS['url'] = REWRITE ? '/'.$_GET['m'].'/': $GLOBALS['app'].$_GET['m'].'/';		//当前的模块
	
	define('H_ROOT', rtrim($GLOBALS['root'], '/'));
	define('H_PUBLIC', rtrim($GLOBALS['public'], '/'));
	define('H_RES', rtrim($GLOBALS['res'], '/'));
	define('H_APP', rtrim($GLOBALS['app'], '/'));
	define('H_URL', rtrim($GLOBALS['url'], '/'));
	
	
	//访问的当前的控制器所在的路径文件
	$srccontrolerfile = APP_PATH.'controls/'.strtolower($_GET['m']).'.class.php';
	Debug::addmsg('当前访问的控制器类在项目应用下的：<b>'.$srccontrolerfile.'</b>文件！');
	
	//控制器类的创建
	if(file_exists($srccontrolerfile)){
		Structure::commoncontroler(APP_PATH.'controls/', $controlerpath);
		Structure::controler($srccontrolerfile, $controlerpath, $_GET['m']);
		
		$className = ucfirst($_GET['m']).'Action';
		
		$controler = new $className;
		$controler->run();
	}else{
		if(DEBUG){
			Debug::addmsg('<font color="red">对不起，访问的模块不存在，应该在'.APP_PATH.'controls目录创建文件名为'.strtolower($_GET['m']).'.class.php的文件，声明一个类名为'.ucfirst($_GET['m']).'的类！</font>');
		}else{
			//Debug关闭时转换为404模式
			Action::_404();
		}
	}
	
	//输出Debug模式的信息
	if(defined('DEBUG') && DEBUG == 1 && $GLOBALS['debug'] == 1){
		Debug::stop();
		Debug::message();
	}
	
	
	
	
	
	
	
