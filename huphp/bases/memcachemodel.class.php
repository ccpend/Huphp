<?php 
	class MemcacheModel{
		private $mc = null;
		/**
		 * 构造方法，用于添加服务器创造对象
		 */
		
		public function __construct($servers){
			$mc = new Memcache;
			//如果有多台Memcache服务器
			if(is_array($servers[0])){	//是二维数组
				foreach($servers as $server){
					call_user_func_array(array($mc, 'addserver'), $server);
				}
			}else{
				//如果只有一台Memcache服务器
				call_user_func_array(array($mc, 'addserver'), $servers);
			}
			
			$this->mc = $mc;
		}
		
		/**
		 * 获取Memcache对象
		 * @return	object	memcache对象
		 */
		public function getMem(){
			return $this->mc;
		}
		
		/**
		 * 检查Mem是否连成功
		 * @return	bool	成功返回真，不成功返回假
		 */
		public function mem_connect_error(){
			$stats = $this->mc->getStats();
			if(empty($stats)){
				return false;
			}else{
				return true;
			}
		}
		
		private function addKey($tabName, $key){
			$keys = $this->mc->get($tabName);
			if(empty($keys)){
				$keys = array();
			}
			
			//如果key不存与$keys在就将其打入
			if(!in_array($key, $keys)){
				$keys[] = $key;			//将新的key打入数组keys中
				$this->mc->set($tabName, $keys, MEMCACHE_COMPRESSED, 0);
				return true;		//不存在返回true	
			}else{
				return false;		//存在返回flase
			}
		}
		
		/**
		 * 向Memcache中添加数据
		 * @param	string	$tabName	需要缓存的数据的表名
		 * @param	string	$sql		SQL语句作为MEMCAHCE缓存的key
		 * @param	mixed	$tabName	需要缓存的数据
		 */
		public function addCache($tabName, $sql, $data){
			$key = md5($sql);					//先把SQL语句转换为md5 的$key
			//如果不存在
			if($this->addKey($tabName, $key)){	//返回真，则往下执行
				$this->mc->set($key, $data, MEMCACHE_COMPRESSED, 0);	//以MD5的SQL 为键存入MEM
			}			
		}
		
		/**
		 * 从memcache中获取数据
		 * @param	string	$sql	SQL语句作为MEMCAHCE缓存的key
		 * @return	mixed			返回缓存中的数据
		 */
		public function getCache($sql){
			$key = md5($sql);
			return $this->mc->get($key);
		}
	
		/**
		 * 删除同一个相关的所有缓存
		 * @param	string	$tabName	数据的表名
		 */
		public function delCache($tabName){
			$keys = $this->mc->get($tabName);
			
			//删除同一表的缓存
			if(!empty($keys)){
				foreach($keys as $key){
					$this->mc->delete($key, 0);	//0表示立即删除
				}
			}
			
			$this->mc->delete($tabName, 0);
		}
		
		/**
		 * 删除单独的一个语句的语句缓存
		 * @param	string	$sql	//执行的SQL语句
		 */
		public function delone($sql){
			$key = md5($sql);
			$this->mc->delete($key, 0);
		}	
	}
	
	
	
	
	
	