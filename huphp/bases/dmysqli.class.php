<?php
	class Dmysqli extends DB{
		public static $mysqli = null;
		
		/**
		 * 用于获取数据库连接mysqli对象，如过mysqli对象已经存在.则不再调用connect()去连接
		 */
		public static function connect(){
			if(is_null(self::$mysqli)){
				$mysqli = new mysqli(HOST, USER, PASS, DBNAME);
				if(mysqli_connect_errno()){
					Debug::addmsg('<font color="red">连接失败:'.mysqli_connect_error().'请查看config.inc.php文件中是否有误！</font>');
					return false;
				}else{
					$mysqli->set_charset(DBCHAR);	//设置数据库的默认连接字符集
					self::$mysqli = $mysqli;
					return $mysqli;
				}
			}else{
				return self::$mysqli;
			}
		}
		
		/**
		 * 执行SQL语句的方法
		 * @param	string	$sql	用户执行的查询语句
		 * @param	string	$method	SQL语句的类型(select, find, total, insert, update, other)
		 * @param	array	$data 	为parpare方法中的?参数绑定值
		 * @return	mixed			根据不同的SQL返回值
		 */
		public function query($sql, $method, $data=array()){
			$startTime = microtime(true);
			$this->setNull();	//初始化SQL
			
			$value = $this->escape_string_array($data);	//进入过滤函数
			$marr = explode('::', $method);
			$method = strtolower(array_pop($marr));	//把数组的第一个元素干掉，并转成小写
			if(strtolower($method) == trim('total')){
				$sql = preg_replace('/select.*from/i', 'SELECT COUNT(*) AS count FROM', $sql);
			}
			$addcache = false;	//判断是否像Mem中加数据
			$memkey = $this->sql($sql, $value);	//组合SQL语句
			if(defined('USEMEM')){
				global $mem;
				if($method == 'select' || $method == 'find' || $method == 'total'){
					$data = $mem->getCache($memkey);
					if($data){
						return $data;		//直接从MEM中返回 不往下执行
					}else{
						$addcache = true;
					}
				}
			}
			
			$mysqli = self::connect();
			if($mysqli){
				$stmt = $mysqli->prepare($sql);	//准备好一个SQL语句
			}else{
				return;	//直接返回
			}
			
			//绑定参数
			if(count($value) > 0){
				$s = str_repeat('s', count($value));
				array_unshift($value, $s);
				call_user_func_array(array($stmt, 'bind_param'), self::refValues($value));
			}
			if($stmt){
				$result = $stmt->execute();		//执行一个准备好的语句
			}
			
			//如果SQL有误，则输出并直接退出
			if(!$result){
				Debug::addmsg('<font color="red">SQL ERROR：['.$mysqli->errno.'] '.$stmt->error.'</font>');
				Debug::addmsg('请查看：<font color="#005500">'.$memkey.'</font>');	//debug
				return;
			}
			
			//如果使用了Mem，且不是查询语句
			if(isset($mem) && !$addcache){
				if($stmt->affected_rows > 0){	//有影响行数
					$mem->delCache($this->tabName);
					Debug::addmsg('清除表<b>'.$this->tabName.'</b>在Memcache中的缓存');
				}
			}
			$resultv = null;
			switch($method){
				case 'select':	//查所有满条件的记录
					$stmt->store_result();
					$data = $this->getAll($stmt);
					if($addcache){	//如果MEM已经开启并且数据的缓存的MEM中不存在
						$mem->addCache($this->tabName, $memkey, $data);
					}
					$resultv = $data;
					break;
				case 'find':	//只需要一条记录
					$stmt->store_result();
					if($stmt->num_rows > 0){
						$data = $this->getOne($stmt);
						if($addcache){	//如果MEM已经开启并且数据的缓存的MEM中不存在
							$mem->addCache($this->tabName, $memkey, $data);
						}
						$resultv = $data;
					}else{
						$resultv = false;
					}
					break;
				case 'total':
					$stmt->store_result();
					$row = $this->getOne($stmt);
					
					if($addcache){
						$mem->addCache($this->tabName, $memkey, $row['count']);
					}
					$resultv = $row['count'];
					break;
				case 'insert':	//插入数据，返回最后插入的ID
					if($this->auto == 'yes'){
						$resultv = $mysqli->insert_id;
					}else{
						$resultv = $result;
					}
					break;
				case 'delete':
				case 'update':
					$resultv = $stmt->affected_rows;
					break;
				default:
					$resultv = $result;	
			}
			$stopTime = microtime(true);
			$ys = round(($stopTime - $startTime), 4);
			Debug::addmsg('[用时<font color="red">'.$ys.'</font>秒] - '.$memkey, 2);
			return $resultv;
		}
		
		/**
		 * 获取多条所有记录
		 */
		private function getAll($stmt){
			$result = array();
			$field = $stmt->result_metadata()->fetch_fields();	//遍历所有字段信息
			$out = array();
			//获取所有信息结果集中的字段名
			$fields = array();
			foreach($field as $val){
				$fields[] = &$out[$val->name];
			}
			//将所有字段绑定到bind_result方法上
			call_user_func_array(array($stmt, 'bind_result'), $fields);
			while($stmt->fetch()){
				$t = array();	//一条记录关联数组
				foreach($out as $key=>$val){
					$t[$key] = $val;
				}
				$result[] = $t;
			}
			return $result;
		}
		
		/**
		 * 获取一条记录
		 */
		private function getOne($stmt){
			$result = array();
			$field = $stmt->result_metadata()->fetch_fields();	//遍历字段所有信息
			$out = array();
			//获取所有信息结果集中的子算名
			$fields = array();
			foreach($field as $val){
				$fields[] = &$out[$val->name];
			}
			//将所有字段绑定到bind_result方法上
			call_user_func_array(array($stmt, 'bind_result'), $fields);
			$stmt->fetch();
			
			foreach($out as $key=>$val){
				$result[$key] = $val;
			}
			
			return $result;	//一维关联数组	
		}
		
		/**
		 * 自动获取表结构
		 * @param	string	$tabName	表名
		 */
		public function setTable($tabName){
			//设置缓存表结构的文件的位置
			$cachefile = PROJECT_PATH.'runtime/data/'.$tabName.'.php';
			$this->tabName = TABPREFIX.$tabName;						//合成表名
			
			//如果缓存表结构存在
			if(file_exists($cachefile)){
				$json = ltrim(file_get_contents($cachefile), '<?ph ');
				$this->auto = substr($json, -3);
				$json = substr($json, 0, -3);
				$this->fieldList = (array)json_decode($json, true);
			}else{
				$mysqli = self::connect();
				if($mysqli){
					$result = $mysqli->query('desc '.$this->tabName);
				}else{
					return;
				}
				
				$fields = array();
				$auto = 'yno';
				
				while($row = $result->fetch_assoc()){
					if($row['Key'] == 'PRI'){
						$fields['pri'] = strtolower($row['Field']);
					}else{
						$fields[] = strtolower($row['Field']);
					}
					if($row['Extra'] == 'auto_increment'){
						$auto = 'yes';
					}
				}
				//如果表没有主键，则用第一列当主键
				if(!array_key_exists('pri', $fields)){
					$fields['pri'] = array_shift($fields);
				}
				
				//如果关闭Debug，则将缓存表结构
				if(!DEBUG){
					file_put_contents($cachefile, '<?php '.json_encode($fields).$auto);
				}
				$this->fieldList = $fields;
				$this->auto = $auto;
			}
			Debug::addmsg('表<b>'.$this->tabName.'</b>结构：'.implode(',', $this->fieldList), 2);
		}
		
		/**
		 * 事务开始 关闭自动提交
		 */
		public function beginTransaction(){
			$mysqli = self::connect();
			$mysqli->autocommit(false);
		}
		
		/**
		 * 事务提交  提交后开启自动提交
		 */
		public function commit(){
			$mysqli = self::connect();
			$mysqli->commit();
			$mysqli->autocommit(true);
		}
		
		/**
		 * 事务回滚 回滚后开启自动回滚
		 */
		public function rollBack(){
			$mysqli = self::connect();
			$mysqli->rollback();
			$mysqli->autocommit(true);
		}
		
		/**
		 * 获取数据库的使用的大小
		 * @return	string	返回数据库的大小
		 */
		public function dbSize(){
			$sql = 'SHOW TABLE STATUS FROM '.DBNAME;
			if(defined('TABPREFIX')){
				$sql .= ' LIKE "'.TABPREFIX.'%"';	//获取表中的带表前缀的表
			}

			$mysqli = self::connect();
			$result = $mysqli->query($sql);
			$size = 0;
			
			while($row = $result->fetch_assoc()){
				$size += $row['Data_length'] + $row['Index_length'];	//如果有多个表，让他叠加
			}
			return tosize($size);
		}
		
		/**
		 * 获取数据库的版本
		 * @return	string	返回数据库服务器的版本
		 */
		public function dbVersion(){
			$mysqli = self::connect();
			return $mysqli->server_info;
		}
		
		/**
		 * 为了在绑定参数bind_param()时兼容PHP5.3.0以上的版本
		 */
		static public function refValues($arr){
			if(version_compare(PHP_VERSION,'5.3.0') >= 0){
			$refers = array();
				foreach($arr as $key=>$value){
					$refers[$key]=&$arr[$key];
				} 
				return $refers;
			}
			return $arr;
		}
	}	
	
	
	
	
	
