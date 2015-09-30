<?php
	class Prourl{
		/**
		 * URL路由，转为PATHINFO模式
		 */
		public static function parseUrl(){
			if(isset($_SERVER['PATH_INFO'])){
				//获取PATHINFO
				$pathinfo = explode('/', trim($_SERVER['PATH_INFO'], '/'));
				
				//获取Control
				$_GET['m'] = (!empty($pathinfo[0]) ? $pathinfo[0] : 'index');
				
				array_shift($pathinfo);			//将数组的第一个元素移除
				
				$_GET['a'] = (!empty($pathinfo[0]) ? $pathinfo[0] : 'index');
				array_shift($pathinfo);			//再将数组的第一个元素移除
				
				//将$pathinfo变量里的数据键值对应的解析的全局变量$_GET中
				for($i = 0; $i<count($pathinfo); $i+=2){
					$_GET[$pathinfo[$i]] = $pathinfo[$i+1];
				}
			}else{
				$_GET['m'] = (!empty($_GET['m']) ? $_GET['m'] : 'index');//没有pathinfo全局变量时，默认m是index
				$_GET['a'] = (!empty($_GET['a']) ? $_GET['a'] : 'index');//没有pathinfo全局变量时，默认a是index
				
				//如果以www.huphp.com/index.php?id=3&cid=6格式传入即没用pathinfo模式
				if($_SERVER['QUERY_STRING']){
					$m = $_GET['m'];
					unset($_GET['m']);
					$a = $_GET['a'];
					unset($_GET['a']);
					$query = http_build_query($_GET);	//将$_GET剩下的URL参数转换成cid=6&pid=3格式
					
					//组成新的URL
					//判断是否开启伪静态模式
					if(REWRITE && strpos($_SERVER['SCRIPT_NAME'], 'index.php')){
						$url = '/'.$m.'/'.$a.'/'.str_replace(array('=','&'), '/', $query);
					}else{
						$url = $_SERVER['SCRIPT_NAME'].'/'.$m.'/'.$a.'/'.str_replace(array('=','&'), '/', $query);
					}
					header('Location:'.$url);			//跳转到pathinfo格式的地址
				}				
			}
		}
	}