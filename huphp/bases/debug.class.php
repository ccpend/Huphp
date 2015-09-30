<?php
	class Debug{
		private static $includefile = array();
		private static $info = array();
		private static $sqls = array();
		private static $startTime;		//获取脚本执行开始时的时间（以微秒形式保存）
		private static $stopTime;		//获取脚本执行结束时的时间（以微秒形式保存）
		private static $msg = array(
			E_WARNING=>'运行时警告',
			E_NOTICE=>'运行时提醒',
			E_STRICT=>'编码标准化警告',
			E_USER_ERROR=>'自定义错误',
			E_USER_WARNING=>'自定义警告',
			E_USER_NOTICE=>'自定义提醒',
			'Unknow'=>'未知结果'
		);
		
		/**
		 * 在脚本开始处调用获取脚本开始时间的微秒值
		 */
		public static function start(){
			self::$startTime = microtime(true);		//将获取的时间赋给$startTime
		}
		
		/**
		 * 在脚本结束处调用获取脚本开始时间的微秒值
		 */
		public static function stop(){
			self::$stopTime = microtime(true);		//将获取的时间赋给$stioTime
		}
		
		/**
		 * 返回同一脚本内2次时间的差值
		 */
		public static function spent(){
			return round((self::$stopTime - self::$startTime), 4);		//计算后以四舍五入保留四位返回
		}
		
		/**
		 * error handler function
		 */
		public static function Catcher($errno, $errstr, $errfile, $errline){
			if(!isset(self::$msg[$errno])){
				$errno = 'Unknow';
			}
			
			if($errno == E_NOTICE || $errno == E_USER_NOTICE){
				$color = '#000088';
			}else{
				$color = 'red';
			}
			
			$mess  = '<font color='.$color.'>';
			$mess .= '<b>'.self::$msg[$errno].'</b>[在文件'.$errfile.'中，第'.$errline.'行]:';
			$mess .= $errstr;
			$mess .= '</font>';

			self::addmsg($mess);
		}
		
		/**
		 * 添加调试信息
		 * @param	string	$msg	调试消息的字符串
		 * @param	int		$type	调试消息的类型
		 */
		public static function addmsg($msg, $type = 0){
			if(defined('DEBUG') && DEBUG == 1){
				switch($type){
					case 0:
						self::$info[] = $msg;
						break;
					case 1:
						self::$includefile[] = $msg;
						break;
					case 2:
						self::$sqls[] = $msg; 						
						break;						
				}
			}
		}
		
		/**
		 * 输出调试的消息
		 */
		public static function message(){
			echo '<div style="clear:both;text-align:left;font-size:14px;color:#444444;width:95%;margin:10px;padding:10px;background:#FBFAE3;border:1px solid #FF8040;z-index:100">';
			echo '<i style="float:left; font-size:18px; font-family:\'微软雅黑\'; font-weight:bold; color:#FF5511;">HuPHP中文开源开发框架</i><span onclick="this.parentNode.style.display=\'none\'" style="cursor:pointer;float:right;width:45px;background:red;border:1px solid #444444;color:white">关闭X</span>';
			echo '<div style="float:left;width:100%;"><span style="float:left;width:200px;"><b>运行信息</b>( <font color="red">'.self::spent().' </font>秒):</span></div>';
			echo '<ul style="margin:0px;padding:0 10px 0 10px;list-style:none;clear:both;">';
			
			if(count(self::$includefile) > 0){
				echo '<li>[自动包含]</li>';
				foreach(self::$includefile as $file){
					echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$file.'</li>';
				}
			}
			
			if(count(self::$info) > 0){
				echo '<li>[系统信息]</li>';
				foreach(self::$info as $info){
					echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$info.'</li>';
				}
			}

			if(count(self::$sqls) > 0){
				echo '<li>[SQL语句]</li>';
				foreach(self::$sqls as $sql){
					echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$sql.'</li>';
				}
			}	
			echo '</ul>';
			echo '</div>';		
		}
	}
	
	
	
	
	