<?php
	class MemSession{
		private static $handler = null;
		private static $lifetime = null;
		
		/**
		 * 初始化和开始SESSION
		 * @param	Memcache	$memcache 	Memcache对象
		 */
		public static function start(Memcache $memcache){
			//将session.start_handler设置为true,而不是默认的file
			ini_set('session.save_handler', 'user');
			
			//不使用URL传递session_id()的方式
			//int_set('session.use_trans_sid', 0);
			
			//设置垃圾回收最大生存时间
			//ini_set('session.gc_maxlifetime', 3600);
			
			//设置使用cookie保存session_id()的方式
			//ini_set('session.use_cookies', 1);
			//ini_set('session.cookie_path', '/');
			
			//多主机共享保存session id的cookie
			//ini_set('session.cookie_domain', 'huphp.com');
			
			self::$handler = $memcache;
			self::$lifetime = ini_get('session.gc_maxlifetime');
			
			session_set_save_handler(
				array(__CLASS__, 'open'),
				array(__CLASS__, 'close'),
				array(__CLASS__, 'read'),
				array(__CLASS__, 'write'),
				array(__CLASS__, 'destroy'),
				array(__CLASS__, 'gc')
			);
			
			session_start();
			return true;
		}
		
		public static function open($path, $name){
			return true;
		}
		
		public static function close(){
			return true;
		}		
		
		/**
		 * 从SESSION读取信息
		 * @param	string	$PHPSESSID	session id
		 * @return	mixed	返回的数据
		 */
		public static function read($PHPSESSID){
			$out = self::$handler->get(self::session_key($PHPSESSID));
			
			if($out === false || $out == null){
				return '';
			}
			
			return $out;
		}
		
		public static function write($PHPSESSID, $data){
			$method = $data ? 'set' : 'replace';
			
			return self::$handler->$method(self::session_key($PHPSESSID), $data, MEMCACHE_COMPRESSED, self::$lifetime);
		}
		
		public static function destroy($PHPSESSID){
			return self::$handler->delete(self::session_key($PHPSESSID));
		}
		
		public static function gc($lifetime){
			//无需回收,memcache有自己的回收机制
			return true;
		}
		
		private static function session_key($PHPSESSID){
			$session_key = TABPREFIX.$PHPSESSID;
			return $session_key;
		}
	}
	
	
	
	
	
	