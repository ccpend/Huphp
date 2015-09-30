<?php
	class MyTpl extends Smarty{
		/**
		 * 构造方法，用于初始化和重载Smarty对象中的成员和属性
		 */
		function __construct(){
			$this->template_dir = APP_PATH.'views/'.TPLSTYLE;	//模板目录
			$this->compile_dir = PROJECT_PATH.'runtime/comps/'.TPLSTYLE.'/'.TMPPATH;	//Smarty合成编译后的文件的位置
			$this->caching = CSTART;	//设置缓存是否开启
			$this->cache_dir = PROJECT_PATH.'runtime/cache/'.TPLSTYLE;			//设置Smarty缓存的目录
			$this->cache_lifetime = CTIME;						//设置缓存的时间
			$this->left_delimiter = '<{';						//Smarty模板左分隔符
			$this->right_delimiter = '}>';						//Smarty模板的右分隔
			parent::__construct();								//调用父类被覆盖的构造方法
		}
		
		/**
		 * 重载Smarty中的display方法
		 * @param	string	$resource_name	模板的位置
		 * @param	mixed	$cache_id		缓存的ID
		 */
		public function display($resource_name = null, $cache_id = null, $compile_id = null){
			//将部分全局变量直接分配到Smarty模板中
			$this->assign('root', H_ROOT);
			$this->assign('app', H_APP);
			$this->assign('url', H_URL);
			$this->assign('public', H_PUBLIC);
			$this->assign('res', H_RES);
			
			if(is_null($resource_name)){
				$resource_name = $_GET['m'].'/'.$_GET['a'].'.'.TPLPREFIX;
			}else{
				$resource_name = trim($resource_name, '/');
			
				if(strstr($resource_name, '/')){
					$resource_name = $resource_name.'.'.TPLPREFIX;
				}else{
					$resource_name = $_GET['m'].'/'.$resource_name.'.'.TPLPREFIX;
				}
			}
			Debug::addmsg('使用模板<b>'.$resource_name.'</b>');
			
			parent::display($resource_name, $cache_id, $compile_id);
		}

		/**
		 * 重载Smarty中的is_cached方法
		 * @param	string	$tpl_file	模板的位置
		 * @param	mixed	$cache_id	缓存的ID
		 */
		public function is_cached($tpl_file = null, $cache_id = null, $compile_id = null){
			if(is_null($tpl_file)){
				$tpl_file = $_GET['m'].'/'.$_GET['a'].'.'.TPLPREFIX;
			}else if(strstr($tpl_file, '/')){
				$tpl_file = $tpl_file.TPLPREFIX;
			}else{
				$tpl_file = $_GET['m'].'/'.$tpl_file.'.'.TPLPREFIX;
			}
			
			return parent::is_cached($tpl_file, $cache_id, $compile_id);
		}

		/**
		 * 重载Smarty中的clear_cache方法
		 * @param	string	$tpl_file	模板的位置
		 * @param	mixed	$cache_id	缓存的ID
		 */		
		public function clear_cache($tpl_file = null, $cache_id = null, $compile_id = null, $exp_time = null){
			if(is_null($tpl_file)){
				$tpl_file = $_GET['m'].'/'.$_GET['a'].'.'.TPLPREFIX;
			}else if(strstr($tpl_file, '/')){
				$tpl_file = $tpl_file.TPLPREFIX;
			}else{
				$tpl_file = $_GET['m'].'/'.$tpl_file.'.'.TPLPREFIX;
			}

			return parent::clear_cache($tpl_file, $cache_id, $compile_id, $exp_time);
		}
	}
	
	
	
	