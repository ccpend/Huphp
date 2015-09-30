<?php
	class Validate{
		private static $data;
		private static $action;
		private static $msg;
		private static $flag = true;
		private static $db = null;
		
		/**
		 * 获取XML内标记的属性，并处理回调内部方法
		 * @param	resource	$xml_parser		XML的资源
		 * @param	string		$tagName		数据表的名称
		 * @param	array		$args			XML的标记属性
		 */
		private static function start($xml_parser, $tagName, $args){
			if(isset($args['NAME']) && isset($args['MSG'])){
				if(empty($args['ACTION']) || $args['ACTION'] == 'both' || $args['ACTION'] == self::action){
					if(is_array(self::$data)){	//如果$data是数组
						if(array_key_exists($args['NAME'], self::$data)){	//如果要验证的数组里面的键有XML文件里的NAME
							if(empty($args['TYPE'])){
								$method = 'regex';		//如果没有传TYPE则默认为REGEX
							}else{
								$method = strtolower($args['TYPE']);
							}
							
							if(in_array($method, get_class_methods(__CLASS__))){	//查看下您需要使用的$method在整个类里面是否已经有这个方法了
								/**
								 * self::$data[$args['NAME']];	需要验证的值
								 * $args['MSG']					XML文件里提示的消息
								 * $args['VALUE']				XML文件里正则验证的规则
								 * $args['NAME']				XMl文件里节点NAME的属性
								 */
								self::$method(self::$data[$args['NAME']], $args['MSG'], $args['VALUE'], $args['NAME']);
							}else{
								self::$msg[] = '验证的规则'.$args['TYPE'].'不存在，请检查<br />';
								self::$flag = false;
							}
						}else{
							self::$msg[] = '验证的字段'.$args['NAME'].'和表单中的不对应<br />';
							self::$flag = false;
						}
					}
				}
			}
		}
		
		private static function end($xml_parser, $tagName){
			return true;
		}
		
		/**
		 * 解析XML文件
		 * @param	string	$filename	XML的文件名
		 * @param	mixed	$data		表单中输出的数据	
		 * @param 	string	$action		用户执行的动作add或者mod	默认为both
		 * @param	object	$db			数据库连接对象
		 */
		public static function check($data, $action, $db, $vcode){
			$file = substr($db->tabName, strlen(TABPREFIX));
			
			$xmlfile = $db->path.'models/'.$file.'.xml';
			if(file_exists($xmlfile)){
				self::$data = $data;
				self::$action = $action;
				self::$db = $db;
				
				if($vcode == 1){
					if(is_array($data) && array_key_exists('code', $data)){
						self::vcode($data['code'], '验证码输入<font color="red">'.$data['code'].'</font>错误！');
					}else{
						die('表单中code字段不存在,非法操作！');
					}
				}
				
				//创建XML解析器
				$xml_parser = xml_parser_create('utf-8');
				
				//使用大小写折叠开保证能在元素数组中找到这些元素的名称
				xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
				xml_set_element_handler($xml_parser, array(__CLASS__, 'start'), array(__CLASS__, 'end'));
				
				//读取XML文件
				if(!($fp = fopen($xmlfile, 'r'))){
					die('无法读取XML文件'.$xmlfile);
				}
				
				//解析文件
				$has_error = false;		//标志位
				while($data = fread($fp, 4096)){	//设定只读到4096字节
					//循环的读入XML文档，直到文档EOF，停止解析
					if(!xml_parse($xml_parser, $data, feof($fp))){
						$has_error = true;
						break;
					}
				}
				
				if($has_error){
					//输出错误行，列及错误信息
					$error_line = xml_get_current_line_number($xml_parser);
					$error_row = xml_get_current_column_number($xml_parser);
					$error_string = xml_error_string(xml_get_error_code($xml_parser));
					
					$message = sprintf('XML文件'.$xmlfile.'[第%d行，%d列]有误：%s',
						$error_line,
						$error_row,
						$error_string
					);
					self::$msg[] = $message;
					self::$flag = false;		
				}
				//关闭XML解析指针，释放资源
				xml_parser_free($xml_parser);
				return self::$flag;	//直接返回
			}else{
				die('验证XML文件不存在');
			}
		}
		
		/**
		 * 使用正则表达式进行验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	失败提示的消息
		 * @param	string	$rules	正则验证的规则
		 */
		private static function regex($value, $msg, $rules){
			if(!preg_match($rules, $value)){	//如果正则验证失败
				self::$msg[]  = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 唯一性的验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	失败提示的消息
		 * @param	string	$name	需要验证的字段名称
		 */
		private static function unique($value, $msg, $rules, $name){
			if(self::$db->where(array($name=>$value))->total() > 0){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 非空验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	失败提示的消息
		 */
		private static function notnull($value, $msg){
			if(strlen(trim($value)) == 0){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * EMAIL验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证错误提示的消息
		 */
		private static function email($value, $msg){
			$rules = '/\w+([-+.\']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/';	//EMAL的正则验证的表达式
			if(!preg_match($rules, $value)){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		} 
		
		/**
		 * URL验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证错误提示的消息
		 */
		private static function url($value, $msg){
			$rules = '/^http\:\/\/([\w-]+\.)+[\w-]+(\/[\w-.\/?%&=]*)?$/';
			if(!preg_match($rules, $value)){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 数字格式验证	
		 * @param	string	$value	需要验证的值
		 * @param	stribg	$msg	验证错误提示的消息
		 */
		private static function number($value, $msg){
			$rules = '/^\d+$/';				//验证是不是数字格式的正则表达式
			if(!preg_match($rules, $value)){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 货币的验证
		 * @param	string	$value	需要验证的值
		 * @param	stribg	$msg	验证错误提示的消息
		 */
		private static function money($value, $msg){
			$rules = '/^\d+(\.\d+)?$/';
			if(!preg_match($rules, $value)){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 验证码自动验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败提示的信息
		 */
		private static function vcode($value, $msg){
			if(strtolower($value) != $_SESSION['code']){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 使用回调函数进行验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败提示的信息
		 * @param	string	$rules	回调函数的名称，此函数应该卸载commons文件夹下functions.inc.php中	
		 */
		private static function callback($value, $msg, $rules){
			if(!call_user_func_array($rules, array($value))){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 使用回调函数进行验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败提示的信息
		 * @param	string	$rules	对应另一个表单名称	
		 */
		private static function confirm($value, $msg, $rules){
			if($value != self::$data[$rules]){
				self::$msg[] = $msg;
				self::$flag = false;
			}
		}
		
		/**
		 * 验证数值是否在一个范围内
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败提示的信息
		 * @param	string	$rules	一个值或多个值，或者是一个范围
		 */
		private static function in($value, $msg, $rules){
			if(strstr($rules, ',')){
				if(!in_array($value, explode(',', $rules))){	//不在指定范围内
					self::$msg[] = $msg;
					self::$flag = false;
				}
			}else if(strstr($rules, '-')){
				list($min, $max) = explode('-', $rules);
				if(!($value >= $min && $value <= $max)){
					self::$msg[] = $msg;
					self::$flag = false;
				}
			}else{
				if($rules != $value){
					self::$msg[] = $msg;
					self::$flag = false;
				}
			}
		}
		
		/**
		 * 判断数值的长度是否在一定的范围内
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败提示的信息
		 * @param	string	$rules	一个范围
		 */
		public static function length($value, $msg, $rules){
			$fg = strstr($rules, '-') ? '-' : ',';
			
			if(!strstr($rules, $fg)){	//如果没有分割符号
				if(strlen($vlaue) != $rules){
					self::$msg[] = $msg;
					self::$flag = false;
				}
			}else{
				list($min, $max) = explode($fg, $rules);	//有分隔符则分割
				if(empty($max)){
					if(strlen($value) < $rules){
						self::$msg[] = $msg;
						self::$flag = false;
					}
				}else if(!(strlen($value) >= $min && strlen($value) <= $max)){
					self::$msg[] = $msg;
					self::$flag = false;
				}
			}
		}
		
		/**
		 * XML验证失败后返回错误消息
		 */
		public static function getMsg(){
			$msg = self::$msg;
			self::$msg = '';	//清空
			self::$data = null;
			self::$action = '';
			self::$flag = true;
			self::$db = null;
			return $msg;
		}
	}
	
	
	
	
	
	
	
	