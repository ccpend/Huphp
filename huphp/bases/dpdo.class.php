<?php
	class Dpdo extends DB{
		public static $pdo = null;
		
		/**
		 * 获取数据库连接对象PDO
		 */
		public static function connect(){
			if(is_null(self::$pdo)){
				try{
					if(defined('DSN')){
						$dsn = DSN;
					}else{
						$dsn = 'mysql:host='.HOST.';dbname='.DBNAME;
					}
					
					$pdo = new PDO($dsn, USER, PASS, array(PDO::ATTR_PERSISTENT=>true));
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$stmt = $pdo->prepare('set names '.DBCHAR);	//设置数据库的默认连接字符集
					$stmt->execute();
					self::$pdo = $pdo;
					return $pdo;
					
				}catch(PDOException $e){
					die('连接失败:'.$e->getMessage().'请查看config.inc.php文件中是否有误！');
				}
			}else{
				return self::$pdo;
			}
		}
		
		/**
		 * 执行SQl语句的方法 
		 * @param	string	$sql		用户查询的SQl语句
		 * @param	string	$method		SQl语句的类型(select, find, total, insert, update, other)（接受从DB类返回的方法名称）
		 * @param	array	$data		为prepare()预处理方法绑定的值
		 * @return	mixed				根据不同的SQL语句返回不同的值
		 */
		public function query($sql, $method, $data = array()){
			$startTime = microtime(true);
			$this->setNull();	//SQL初始化
			$value = $this->escape_string_array($data);	//过滤传过来的值 调用去除单引号和双引号的函数（但是感觉没用）
			$marr = explode('::', $method);
			$method = strtolower(array_pop($marr));
			
			if(strtolower($method) == trim('total')){
				$sql = preg_replace('/select.*?from/i', 'SELECT COUNT(*) AS count FROM', $sql);
			}
			
			/*判断是否开启了Memcache并执行*/
			$addcache = false;
			$memkey = $this->sql($sql, $value);		//组合好以后的SQl语句，用于调试开发的时候看
			if(defined('USEMEM')){					//如果成功开启了MEM
				global $mem;
				if($method == 'select' || $method == 'find' || $method == 'total'){
					$data = $mem->getCache($memkey);//试着用SQL语句为键去取
					if($data){						//如果存在结果直接返回结束
						return $data;	//直接从Memcache中取出，方法不往下执行
					}else{
						$addcache = true;	//如果不存在 变量$addcache设为TRUE
					}
				}
			}
			
			try{
				$return = null;
				$pdo = self::connect();
				$stmt = $pdo->prepare($sql);	//准备好一个SQl语句
				$result = $stmt->execute($value);	//执行一个准备好的语句

				//如果使用了Mem 且不是查找语句
				if(isset($mem) && !$addcache){		//如果成功开启MEM
					if($stmt->rowCount() > 0){		//SQL执行的影响函数>0
						$mem->delCache($this->tabName);	//清除此表的全部缓存
						Debug::addmsg('清除表<b>'.$this->tabName.'</b>在Memcache中的缓存');
					}
				}
				
				switch($method){
					case 'select':			//查所有满足的条件
						$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
						
						if($addcache){
							$mem->addCache($this->tabName, $memkey, $data);
						}
						$return =  $data;
						break;
						
					case 'find':				//只查询一条记录的
						$data = $stmt->fetch(PDO::FETCH_ASSOC);	
						
						if($addcache){
							$mem->addCache($this->tabName, $memkey, $data);
						}
						$return =  $data;
						break;
						
					case 'total':				//返回总记录数
						$row = $stmt->fetch(PDO::FETCH_NUM);
						
						if($addcache){
							$mem->addCache($this->tabName, $memkey, $row[0]);
						}
						$return = $row[0];
						break;
						
					case 'insert':
						if($this->auto == 'yes'){
							$return = $pdo->lastInsertId();
						}else{
							$return = $result;
						}
						break;
						
					case 'delete':
					case 'update':
						$return = $stmt->rowCount();
						break;
					default:
						$return = $result;			
				}
				
				$stopTime = microtime(true);
				$ys = round(($stopTime - $startTime), 4);
				Debug::addmsg('[用时<font color="red">'.$ys.'</font>秒] - '.$memkey, 2);
				return $return;
			}catch(PDOException $e){
				Debug::addmsg('<font color="red">SQL error：'.$e->getMessage().'</font>');
				Debug::addmsg('请查看：<font color="#005500">'.$memkey.'</font>');
			}
		}
		
		
		/**
		 * 自动换取表结构
		 */
		public function setTable($tabName){
			$cachefile = PROJECT_PATH.'runtime/data/'.$tabName.'.php';
			$this->tabName = TABPREFIX.$tabName;
			if(!file_exists($cachefile)){
				try{
					$pdo = self::connect();
					$stmt = $pdo->prepare('desc '.$this->tabName);
					$stmt->execute();
					$auto = 'yno';
					$fields = array();
					while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						if($row['Key'] == 'PRI'){
							$fields['pri'] = strtolower($row['Field']);
						}else{
							$fields[] = strtolower($row['Field']);
						}
						if($row['Extra'] == 'auto_increment'){
							$auto = 'yes';
						}
					}
						//如果没有主键，则将第一列作为主件
						if(!array_key_exists('pri', $fields)){
							$fields['pri'] = array_shift($fields);
						}
						
						//如果关闭了Debug
						if(!DEBUG){
							file_put_contents($cachefile, '<?php '.json_encode($fields).$auto);
						}
						$this->fieldList = $fields;
						$this->auto = $auto;					
				}catch(PDOException $e){
					Debug::addmsg('<font color="red">异常：'.$e->getMessage().'</font>');
				}
				
			}else{
				$json = ltrim(file_get_contents($cachefile), '<?ph ');
				$this->auto = substr($json, -3);
				$json = substr($json, 0, -3);
				$this->fieldList = (array)json_decode($json, true);
			}
			Debug::addmsg('表<b>'.$this->tabName.'</b>结构：'.implode(',', $this->fieldList), 2);
		}
		
		/**
		 * 事务开始
		 */
		public function beginTransaction(){
			$pdo = self::connect();
			$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);	//关闭自动提交
			$pdo->beginTransaction();
			
		}
		
		/**
		 * 事务提交
		 */
		public function commit(){
			$pdo = self::connect();
			$pdo->commit();
			$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
		}
		
		/**
		 * 事务回滚
		 */
		public function rollback(){
			$pdo = self::connect();
			$pdo->rollBack();
			$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
		}
		
		/**
		 * 获取数据库的大小
		 * @return	string	返回转变好格式的数据库的大小
		 */
		public function dbSize(){
			$sql = 'SHOW TABLE STATUS FROM '.DBNAME;
			if(defined('TABPREFIX')){
				$sql .= ' LIKE "'.TABPREFIX.'%"';
			}
			$pdo = self::connect();
			$stmt = $pdo->prepare($sql);
			$stmt->execute();
			$size = 0;
			
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$size += $row['Data_length'] + $row['Index_length'];
			}
			
			return(tosize($size));
		}
		
		/**
		 * 获取数据库的版本
		 * @return	string	返回数据库的版本信息
		 */
		public function dbVersion(){
			$pdo = self::connect();
			return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
		}
	}
	
	
	
	
	