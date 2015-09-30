<?php
	class Action extends MyTpl{
		/**
		 * 该方法用来运行框架中的控制器，在入口文件huphp.php文件中调用
		 */
		public function run(){
			
			//如果子类中有Common类的init()方法，则自动调用（可做权限控制）
			if(method_exists($this, 'init')){
				$this->init();
			}
			
			//根据动作($_GET['a']去找相应的方法)
			$method = $_GET['a'];
			if(method_exists($this, $method)){
				$this->$method();
			}else{
				if(DEBUG){
					Debug::addmsg('<font color="red">没有'.$_GET['a'].'这个操作！</font>');
				}else{
					self::_404();
				}	
			}
		}
		
		/**
		 * 在控制器中用于位置重定向
		 * @param	string	$path	用于设置重定向的位置
		 * @param	string	$args	重定向到新的位置之后传递的参数
		 * $this->redirect('index');					/当前模块的/index方法
		 * $this->redirect('user/index');				/user模块的/index方法
		 * $this->redirect('user/index', 'page/5');		/user模块的/index方法/page/5
		 */
		public function redirect($path, $args=''){
			$path = trim($path, '/');
			if($args != ''){
				$args = '/'.trim($args, '/');
			}
			if(strstr($path, '/')){
				$url = $path.$args;
			}else{
				$url = $_GET['m'].'/'.$path.$args;
			}
			
			$uri = H_APP.'/'.$url;
			//使用JS跳转页面钱可以有输出
			echo '<script>';
			echo 'location = "'.$uri.'"';
			echo '</script>';
		}
		
		/**
		 * 操作成功提示的消息框
		 * @param 	string	$mess		设置成功的提示消息
		 * @param	int		$timeout	设置几秒后跳转
		 * @param	string	$location	设置跳转的位置
		 */
		public function success($mess = '操作成功', $timeout = 3, $location = ''){	
			$this->pub($mess, $timeout, $location);
			$this->assign('mark', true);			//如果成功 $mark = true
			$this->display('public/success');
			exit;
		}
		
		/**
		 * 操作失败提示的消息框
		 * @param 	string	$mess		设置成功的提示小西
		 * @param	int		$timeout	设置几秒后跳转
		 * @param	string	$location	设置跳转的位置
		 */
		public function error($mess = '操作失败', $timeout = 3, $location = ''){
			$this->pub($mess, $timeout, $location);
			$this->assign('mark', false);			//如果失败 $mark = flase
			$this->display('public/success');
			exit;
		}

		public function pub($mess, $timeout, $location){
			$this->caching = 0;	//设置Smarty缓存关闭
			
			if($timeout == ''){
				$mess = 3;
			}
			
			if($location == ''){
				$location = 'window.history.back();';
			}else{
				$path = trim($location, '/');
				
				if(strstr($path, '/')){
					$url = $path;
				}else{
					$url = $_GET['m'].'/'.$path;
				}
				
				$location = H_APP.'/'.$url;
				$location = 'window.location=\''.$location.'\'';
			}
			
			$this->assign('mess', $mess);
			$this->assign('timeout', $timeout);
			$this->assign('location', $location);
			closeDebug();
		}
		
		/**
		 * 404页面优化
		 */
		static public function _404(){
			//清除缓冲区的所有输出
			ob_end_clean();
			
			header("HTTP/1.0 404 Not Found");
			header("status: 404 Not Found");
			
			$my = new self();
			$my->display('public/404');
			exit();
		}
	}
	
	
	
	
	
	