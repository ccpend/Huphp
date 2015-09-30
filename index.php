<?php 
	/*
	 * 单一入口文件
	 */
	define('CSTART', 1);			//缓存开关，1开启，0关闭
	define('TPLSTYLE', 'default');	//默认模板存放目录
	define('HUPHP', './huphp');		//框架源文件的目录
	define('APP', './home');			//设置当前应用目录
	
	require(HUPHP.'/huphp.php');	//加载框架的入口文件