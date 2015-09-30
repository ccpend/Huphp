<?php
	/**
	 *	输出各种类型的数据、适用于调试模式和开发阶段 
	 *	@param	mixed	参数可以是一个或者多个任意变量或值
	 */
	function p(){
		$args = func_get_args();	//获取多个参数
		if(count($args) < 1){
			Debug::addmsg('<font color="red">函数p()一定要提供一个以上的参数</font>');
			return;
		}
		
		echo '<div style="width:100%" text-align:"left"><pre>';
		//多个参数循环输出
		foreach($args as $arg){
			if(is_array($arg)){
				print_r($arg);
				echo '<br />';
			}else if(is_string($arg)){
				echo $arg;
				echo '<br />';
			}else{
				var_dump($arg);
				echo '<br />';
			}
		}
		echo '</pre></div>';
	}
	
	
	function D($className = null, $app = ''){
		$db = null;
		//如果没有传表名或类名，则直接创建DB对象，但是不能对表进行操作
		if(is_null($className)){
			$class = 'D'.DRIVER;
			$db = new $class;
		}else{
			$className = strtolower($className);
			$model = Structure::model($className, $app);
			$model = new $model;
			
			//如果数据表不存在，则获取表结构
			$model->setTable($className);
			
			$db = $model;
		}
		if($app == ''){
			$db->path = APP_PATH;
		}else{
			$db->path = PROJECT_PATH.strtolower($app).'/';
		}
		
		return $db;
	}
	
	
	/**
	 * 文件尺寸的转换
	 * @param	int		$bytes	字节大小
	 * @return	string	转换后带单位大小的
	 */
	function tosize($bytes){							//自定义一个转换文件大小的函数
		if($bytes >= pow(2, 40)){						//如果字节大于2的40次方，则条件成立
			$return = round($bytes / pow(1024, 4), 2);	//将字节转换成同比TB的大小，保留两位小数
			$suffix = 'TB';								//单位为TB
		}
		if($bytes >= pow(2, 30)){						//如果字节大于2的30次方，则条件成立
			$return = round($bytes / pow(1024, 3), 2);	//将字节转换成同比GB的大小，保留两位小数
			$suffix = 'GB';								//单位为GB
		}
		if($bytes >= pow(2, 20)){						//如果字节大于2的20次方，则条件成立
			$return = round($bytes / pow(1024, 2), 2);	//将字节转换成同比MB的大小，保留两位小数
			$suffix = 'MB';								//单位为MB
		}
		if($bytes >= pow(2, 10)){						//如果字节大于2的10次方，则条件成立
			$return = round($bytes / pow(1024, 1), 2);	//将字节转换成同比KB的大小，保留两位小数
			$suffix = 'KB';								//单位为KB
		}else{
			$return = $bytes;							//直接返回byte（字节）
			$suffix = 'byte';							//单位为字节
		}
		return $return.' '.$suffix;	
	}

	/**
	 * 调用此函数后，即即时关闭调试模式（只有此页面生效）
	 */
	function closeDebug(){
		$GLOBALS['debug'] = 0;
	}
	
	
	
	
	
	