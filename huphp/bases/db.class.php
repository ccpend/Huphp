<?php
	abstract class DB{
		protected $msg = array();	//提示消息的数组
		protected $tabName = '';	//表名，自动获取
		protected $fieldList = array();	//字段结构，自动获取
		protected $auto;
		public $path;
		protected $sql = array('field'=>'', 'where'=>'', 'order'=>'', 'limit'=>'', 'group'=>'', 'having'=>'');
		//SQL的初始化（这些都是需要传递参数的SQL语句）
		
		/**
		 * 用来获取表名
		 */
		public function __get($pro){
			if($pro == 'tabName'){
				return $this->tabName;
			}
		}
		
		/**
		 * 用于重置成员属性（这些都是需要传递参数的SQL语句）,每次在程序里面调用，都像受保护的属性protected $sql里面打值
		 */
		protected function setNull(){
			$this->sql = array('field'=>'', 'where'=>'', 'order'=>'', 'limit'=>'', 'group'=>'', 'having'=>'');
		}
		
		
		/**
		 * 连贯操作调用field() where() order() limit() group() having()方法，作用是把你在程序里传的方法和参数打到全局的属性里，为了在下面个各种操作中调用，这些关键词都不能单独使用（这些都是需要传递参数的SQL语句）
		 */
		public function __call($methodName, $args){
			//$args 传进来以后变数组（自动加一维）
			$methodName = strtolower($methodName);
			if(array_key_exists($methodName, $this->sql)){			//如果$this->sql数组里面设置了传过来的连贯操作的方法
				if(empty($args[0]) || (is_string($args[0]) && trim($args[0]) === '')){
					$this->sql[$methodName] = '';
				}else{
					$this->sql[$methodName] = $args; 
				}
				
				if($methodName == 'limit'){
					if($args[0] == '0'){
						$this->sql[$methodName] = $args;
					}
				}
			}else{
				Debug::addmsg('<font color="red">调用类'.get_class($this).'中的方法'.$methodName.'()不存在</font>');
			}
			return $this;
		}
		
		/**
		 * 按指定条件获得结果集的总数
		 */
		public function total(){
			$where = '';
			$data = array();
			
			$args = func_get_args();
			if(count($args) > 0){
				$where = $this->comWhere($args);
				$data = $where['data'];
				$where = $where['where'];
			}else if($this->sql['where'] != ''){
				$where = $this->comWhere($this->sql['where']);
				$data = $where['data'];
				$where = $where['where'];
			}
			
			$sql = 'SELECT COUNT(*) AS count FROM '.$this->tabName.$where;
			return $this->query($sql, __METHOD__, $data);
		}
		
		/**
		 * 获取查询多条结果，返回二维数组
		 */
		public function select(){
			$fields = $this->sql['field'] != '' ? $this->sql['field'][0] : implode(',', $this->fieldList);
			
			$where = '';
			$data = array();
			$args = func_get_args();	//接收传来的参数
			if(count($args) > 0){
				$where = $this->comWhere($args);
				$data = $where['data'];
				$where = $where['where'];
			}else if($this->sql['where'] != ''){
				$where = $this->comWhere($this->sql['where']);
				$data = $where['data'];
				$where = $where['where'];
			}
			
			$order = $this->sql['order'] != '' ? ' ORDER BY '.$this->sql['order'][0] : ' ORDER BY '.$this->fieldList['pri'].' ASC';
			$limit = $this->sql['limit'] != '' ? $this->comLimit($this->sql['limit']) : '';
			$group = $this->sql['group'] != '' ? ' GROUP BY '.$this->sql['group'][0] : '';
			$having = $this->sql['having'] != '' ? ' HAVING '.$this->sql['having'][0] : '';
			
			$sql = 'SELECT '.$fields.' FROM '.$this->tabName.$where.$group.$having.$order.$limit;
			return $this->query($sql, __METHOD__, $data);
		}
		
		/**
		 * 获取一条记录，返回一位数组
		 */
		public function find($pri = ''){
			$field = $this->sql['field'] != '' ? $this->sql['field'][0] : implode(',', $this->fieldList);
			
			//如果传的主键的值为空
			if($pri == ''){
				$where = $this->comWhere($this->sql['where']);
				$data = $where['data'];
				$where = $this->sql['where'] != '' ? $where['where'] : '';
			}else{
				$where = ' WHERE '.$this->fieldList['pri'].'=?';
				$data[] = $pri;
			}
			
			$order = $this->sql['order'] != '' ? ' ORDER BY '.$this->sql['order'][0] : '';
			$sql = 'SELECT '.$field.' FROM '.$this->tabName.$where.$order.' LIMIT 1';
			
			return $this->query($sql, __METHOD__, $data);
		}
		
		/**
		 * 过滤用户输入的信息，最主要是转换HTML为实体
		 * filter = 1  开启 , 0不变
		 */
		private function check($array, $filter){
			$arr = array();
			foreach($array as $key=>$value){
				$key = strtolower($key);
				if(in_array($key, $this->fieldList) && $value !== ''){
					if(is_array($filter) && !empty($filter)){	//这里可以再$filter参数里这样写 array('name','cname') name cname 要与值里面的键一一对应，这样可以做到部分过滤 
						if(in_array($key, $filter)){	//看$filter 设置的 判断是否在$array 的键里面存在
							$arr[$key] = $value;
						}else{
							//$arr[$key] = stripslashes(htmlspecialchars($value));	修正原版本的鸡肋 但我不知道对不对
							$arr[$key] = htmlspecialchars($value, ENT_QUOTES);	//第二个参数 设置编译双引号和单引号
						}
					}else if(!$filter){		//如果过滤开关为0
						$arr[$key] = $value;
					}else{
						//$arr[$key] = stripslashes(htmlspecialchars($value));	修正原版本的鸡肋 但我不知道对不对
						$arr[$key] = htmlspecialchars($value, ENT_QUOTES);
					}
				}
			}
			
			return $arr;
		}
		
		/**
		 * 向数据库中插入一条记录
		 */
		public function insert($array = null, $filter = 1, $validate = 0, $vcode = 0){
			if(is_null($array)){
				$array = $_POST;
			}
			
			if($validate){
				$vali = Validate::check($array, 'add', $this, $vcode);
			}else{
				$vali = true;
			}
			
			if($vali){
				$array = $this->check($array, $filter);
				$sql = 'INSERT INTO '.$this->tabName.'('.implode(',', array_keys($array)).') VALUES ('.implode(',', array_fill(0, count($array), '?')).')';
				return $this->query($sql, __METHOD__, array_values($array));
			}else{
				$this->msg = Validate::getMsg();
				return false;
			}
		}
		
		/**
		 * 更新数据表指定条的记录
		 */
		public function update($array = null, $filter = 1, $validate = 0, $vcode = 0){
			if(is_null($array)){				//如果第一个参数为null
				$array = $_POST;				//则直接抓取$_POST里面的
			}
			
			if($validate){
				$vali = Validate::check($array, 'mod', $this, $vcode);
			}else{
				$vali = true;
			}
			
			if($vali){
				$data = array();
				if(is_array($array)){			//第一个参数是数组
					if(array_key_exists($this->fieldList['pri'], $array)){		//查看传来第一个参数要更新的数组里面有没有当前表的主键
						$pri_value = $array[$this->fieldList['pri']];			//如果有主键，将其的值其赋给$pri_value
						unset($array[$this->fieldList['pri']]);					//干掉第一个参数里面的主键的键值
					}
					$array = $this->check($array, $filter);						//进过滤器
					$s = '';
					foreach($array as $k=>$v){									//循环被上面被干过了的$array
						$s .= $k.'=?,';											//有几个下标，就生成几个xxx=?,xxx=?,
						$data[] = $v;	//值										//将值全部打入$data
					}
					$s = rtrim($s, ',');										//干掉生成问号字符串最右边的逗号
					$setfield = $s;												//将其(xxx=?,xxx=?)存入$setfield这是uodate基本语法
				}else{															//如果不是数组而是字符串
					$setfield = $array;											//直接将字符串(例子 “age=age+1”) 则保存在$setfield
					$pri_value = '';											
				}
				
				$order = $this->sql['order'] != '' ? ' ORDER BY '.$this->sql['order'][0] : '';
				$limit = $this->sql['limit'] != '' ? $this->comLimit($this->sql['limit']) : '';
				
				if($this->sql['where'] != ''){					//如果用->where()
					$where = $this->comWhere($this->sql['where']);
					$sql = 'UPDATE '.$this->tabName.' SET '.$setfield.$where['where'];		//打入comWhere返回的where组合好的字符串
					
					if(!empty($where['data'])){			//如果comWhere传来有data 说明where里面设置的有值（非直接字符串的）
						foreach($where['data'] as $v){
							$data[] = $v;				//打入$data 追加在后面
						}
					}
					$sql .= $order.$limit;				//追加limit和order
				}else{			//如果没用->where()
					$sql = 'UPDATE '.$this->tabName.' SET '.$setfield.' WHERE '.$this->fieldList['pri'].'=?';
					$data[] = $pri_value;	//将$array中的修改值得主键的值打到$data最后 对应上面的问号
				}
				
				return $this->query($sql, __METHOD__, $data);
			}else{
				$this->msg = Validate::getMsg();
				return false;
			}
		}
		
		/**
		 * 删除满足条件的记录
		 */
		public function delete(){
			$where = '';
			$data = array();
			
			$args = func_get_args();
			if(count($args) > 0){
				$where = $this->comWhere($args);
				$data = $where['data'];
				$where = $where['where'];
			}else if($this->sql['where'] != ''){
				$where = $this->comWhere($this->sql['where']);
				$data = $where['data'];
				$where = $where['where'];
			}
			
			$order = $this->sql['order'] != '' ? ' ORDER BY '.$this->sql['order'][0] : '';
			$limit = $this->sql['limit'] != '' ? $this->comLimit($this->sql['limit']) : '';
			
			if($where == '' && $limit == ''){
				$where = ' WHERE '.$this->fieldList['pri'].'=""';
			}
			
			$sql = 'DELETE FROM '.$this->tabName.$where.$order.$limit;
			
			return $this->query($sql, __METHOD__, $data);
		}
		
		/**
		 * 执行查询语句时LIMIT的限制(以后可以优化)
		 */
		private function comLimit($args){
			if(count($args) > 2){
				return ' LIMIT '.$args[0].','.$args[1];
			}else if(count($args) == 1){
				return ' LIMIT '.$args['0'];
			}else{
				return '';
			}
		}
		
		/**
		 * 用来组合SQL语句中中的where条件，传进来的是数组(需要慢慢理解)
		 */
		private function comWhere($args){
			$where = ' WHERE ';
			$data = array();
			
			//如果参数为空
			if(empty($args)){
				return array('where'=>'', 'data'=>$data);	//直接返回空
			}	
			
			foreach($args as $option){
				if(empty($option)){
					$where = '';	//条件为空，则返回空字符串，如'', 0, false则返回: ''
					continue;
				}else if(is_string($option)){			//如果是字符串类型的（带引号的）
					if(is_numeric($option[0])){			//（1）如果是字符串数字 例where('1,2') 会自动和主键匹配
						$option = explode(',', $option);	//拆分为数组
						$where .= $this->fieldList['pri'].' IN('.implode(',', array_fill(0, count($option), '?')).')';
						$data = $option;
						continue;
					}else{	//！！！！！！！！！（2）例 where('id = 1') where('id ='.$_POST['id'])
						//直接使用字符串参数而不是数字直接执行里面的语句，警告！！！这样传不能防止SQL注入
						$where .= $option;
						continue;
					}
					
				}else if(is_numeric($option)){		//（3）如果是直接数字例 where(1) 只限传单个数字 会自动和主键匹配,一般这种情况只会在程序里程序员手工填写时出现，用户输入不会出现
					$where .= $this->fieldList['pri'].'=?';	
					$data[0] = $option;
					continue;
				}else if(is_array($option)){			//如果参数传的是数组
					
					if(isset($option[0])){				//（4）如果是1维数组 array(1,2,3,4) 会自动和主键匹配
						$where .= $this->fieldList['pri'].' IN('.implode(',', array_fill(0, count($option), '?')).')';
						$data = $option;			//直接把数组传过去
						continue;
					}
					
					foreach($option as $k=>$v){
						if(is_array($v)){	
							//（5）如果是二维数组	array('uid'=>array(1,2,3,4));
							$where .= $k.' IN('.implode(',', array_fill(0, count($v), '?')).')';
							
							foreach($v as $val){
								$data[] = $val;
							}		
												
						}else if(strpos($k, ' ')){	//如果出现空格 大于小于判断时必须要传 空格+ < 或 >，不然就会自动加上=，那就不对了
							//（6）array('add_time >'=>'2012-12-21')，条件$k带 < >符号的
							$where .= $k.'?';
							$data[] = $v;
							
						}else if(isset($v[0]) && $v[0] == '%' && substr($v, -1) == '%'){
							//（7）array('name'=>'%中%')，LIKE操作
							$where .= $k.' LIKE ?';
							$data[] = $v;
						}else{
							//（8）array('cid'=>1)
							$where .= $k.'=?';
							$data[] = $v;
						}
						
						$where .= ' AND ';		//在当前数组里循环，自动给SQl语句的末尾加上AND
					}
					
					$where = rtrim($where, 'AND ');			//干掉SQL语句末尾的AND
					$where .= ' OR ';						//先加上关键词OR 以备用
					continue;
				}
			}
			$where = rtrim($where, 'OR ');					//干掉SQL语句末尾的OR
			
			//$where：组合好的预处理SQL语句，带问号   $data：query(用来替换问号的值)			
			return array('where'=>$where, 'data'=>$data);
		}
		
		/**
		 * 在数据库操作时候去除单引号与双引号,将被调用在Dmysqli类 和 Dpdo类里面
		 */
		protected function escape_string_array($array){
			/*
			//去除注释，开启(这个方法我觉得也是原版本里面的一个鸡肋)
			if(empty($array)){
				return $array;
			}
			$value = array();
			foreach($array as $val){
				$value[] = str_replace(array('"', '\''), '', $val);
			}
			return $value;
			*/
			return $array;
		}
		
		/**
		 * 输出完成的SQl语句，用于开发调试
		 */
		protected function sql($sql, $params_arr){
			if((strpos($sql, '?') == false) || count($params_arr) == 0){
				return $sql;
			}
			
			//进行?的替换，变量替换
			if(strpos($sql, '%') == false){
				//不存在'%' 替换问号为s%，进行字符串格式化
				$sql = str_replace('?', "'%s'", $sql);
				array_unshift($params_arr, $sql);	//把带问号的SQL语句打进这个数组一起
				return call_user_func_array('sprintf', $params_arr);	//调用sprintf函数，正好和此系统函数的参数对应，返回组合好的SQL语句
			}
		}
		
		/**
		 * 关联查询，参数位数组，可以有多个，每个数组为一个关联的表
		 */
		public function r_select(){
			$args = func_get_args();	//获取传入的参数自动转换为数组
			
			if(count($args) == 0 || !is_array($args[0])){	//如果传进来的内容为空  或者（第一个数组）不是数组
				return false;
			}
			
			$one = $this->select();			//将当前对象执行select方法保存到变量$one中
			$pri = $this->fieldList['pri'];	//获取当前模型的主键
			$pris = array();
			
			foreach($one as $row){
				$pris[] = $row[$pri];		//第一个模型主键的值循环打入$pris[]
			}
			
			foreach ($args as $tab){
				/**
				 * $tabName	关联的表名
				 * $field	需要关联查出的字段
				 * $fk		关联的关键
				 */
				list($tabName, $field, $fk) = $tab;		//用系统函数list()去抓取循环中的$tab中的3个值，list()函数抓取的数组一定不能是关联数组
				
				if(!empty($field)){					//如果字段$field不为空
					if(!in_array($fk, explode(',', $field))){		//将$field字符串组合成数组，如果设置的外键在没$field有
						$field = $field.','.$fk;	//将外键追加到$field的最后
					}else{
						$field = $field;			//如果外键在$field中出现
					}
				}else{
					$field = '';					//如果$field为空
				}
				//以子数组形式方式1:n（1对多）
				if(!empty($tab[3])){		//如果第四个参数不为空
					$sub = $tab[3];			//子数组
					if(is_array($sub)){		//如果第四个参数是数组
						$obj = D($tabName);	//实例化一下需要关联的第二个模型
						$new = array();
						
						foreach($one as $row){	//遍历一下第一个模型的select 结果
							$where = $fk.'='.$row[$pri];	//外键 = 当前第一个模型主键的值
							if(!empty($sub[3])){			//子数组的第四个参数
								$where .= ' AND '.$sub[3];
							}
							
							if(!empty($sub[1])){		//子数组的第二个参数，设置排序存在
								if(!empty($sub[2])){	//如果子数组有设置了第三个参数limit
									$row[$sub[0]] = $obj->field($field)->order($sub[1])->limit($sub[2])->where($where)->select();
								}else{					//没设置第三个参数
									$row[$sub[0]] = $obj->field($field)->order($sub[1])->where($where)->select();
								}
							}else{		//子数组的第二个参数，设置排序不存在
								if(!empty($sub[2])){	//如果子数组有设置了第三个参数limit
									$row[$sub[0]] = $obj->field($field)->where($where)->limit($sub[2])->select();
								}else{//没设置第三个参数
									$row[$sub[0]] = $obj->field($field)->where($where)->select();
								}
							}

							$new[] = $row;	//加入以后，反打回去
						}
						$one = $new;	//将$new 保存到$one 循环回去可继续用	
					}else{				//如果第四个参数不是数组
						$new = array();
						$npris = array();
						foreach($one as $row){	//遍历一下第一个模型的select 结果
							$npris[] = $row[$sub];//把select结果的键为（当子数组的第四个参数不是数组）第四个参数传的键名的对应select结果的值打入$npris
						}
						
						//以平级的组数方式1:1(左关联)
						$where = array($fk=>$npris);// array('pid'=>array(1,2,3,4)) array(主数组的第三个参数外键=>第四个参数传的键名的对应select结果的值打入$npris（当子数组的第四个参数不是数组）)
						if(!empty($where[$fk])){	//数组where 键名为设置的外键，键值内容不为空
							$data = D($tabName)->field($field)->where($where)->select();	//按条件获取结果集
							$i = 0;
							foreach($one as $row){			//遍历一下第一个模型的select 结果
								foreach($data as $read){	//遍历按条件查出的结果集
									if($read[$fk] == $row[$sub]){	//总结果集的条件查出的结果集[$fk] = 第一个模型的select 结果[子数组第四个参数] 
										foreach($read as $k3=>$v3){
											if(array_key_exists($k3, $row)){
												$row[$tabName.'_'.$k3] = $v3;
											}else{
												$row[$k3] = $v3;
											}
										}
										
										$new[$i] = $row;
										break;
									}
								}
								
								if(empty($new[$i])){
									$new[$i] = $one[$i];
								}
								
								$i++;
							}
							$one = $new;		//将$new 保存到$one 循环回去可继续用
						}
					}
				}else{	//如果没有第四个参数 (多表查询)
						//以平级数组的方式 1:1
					$new = array();
					$where = array($fk=>$pris);		

					if(!empty($where[$fk])){
						$data = D($tabName)->field($field)->where($where)->select();
						
						foreach($data as $row){
							foreach($one as $read){
								if($read[$pri] == $row[$fk]){
									foreach($row as $k3=>$v3){
										if(array_key_exists($k3, $read)){
											$read[$tabName.'_'.$k3] = $v3;
										}else{
											$read[$k3] = $v3;
										}
									}
									
									$new[] = $read;
								}
							}
						}
						$one = $new;	//将$new 保存到$one 循环回去可继续用
					}
				}	
			}
			return $new;
		}
		
		/**
		 * 关联删除
		 */
		public function r_delete(){
			$args = func_get_args();
			if(count($args) == 0 || !is_array($args[0])){
				return false;
			}
				
			$one = $this->select();
			$pri = $this->fieldList['pri'];
			$pris = array();		//要删除的
			
			foreach($one as $row){
				$pris[] = $row[$pri];	//存入原模型主键值
			}
			
			$affected_rows = 0;
			
			foreach($args as $tab){
				$where = array($tab[1]=>$pris);		//array('pid'=>array(1,2,3)) array('user'=>array(1,2,3))
				
				if(!empty($tab[2])){				//第三个参数不为空
					$where = array_merge($where, $tab[2]);	//组合$where array_merge($where, array('id >'=>5))
				}
				
				if(!empty($where[$tab[1]])){		//如果$where里面键为第二个参数的值不为空
					$affected_rows += D($tab[0])->where($where)->delete();
				}
			}
			
			$affected_rows += $this->where($pris)->delete();	//最后把主表的要删除的干掉
			
			return $affected_rows;								//返回影响行数
		}
		
		/**
		 * 设置提示信息
		 * @param	mixed	$mess	提示消息字符串或者数组
		 */
		public function setMsg($mess){
			if(is_array($mess)){
				foreach($mess as $one){
					$this->msg[] = $one;
				}
			}else{
					$this->msg[] = $mess;	
			}
		}
		
		/**
		 * 获取消息信息
		 * @return	string	获取提示消息的字符串
		 */
		public function getMsg(){
			$str = '';
			
			foreach($this->msg as $msg){
				$str .= $msg.'<br>';
			}	
			
			return $str;
		}
		
		abstract function query($sql, $method, $data=array());
		abstract function setTable($tabName);
		abstract function beginTransaction();
		abstract function commit();
		abstract function rollBack();
		abstract function dbSize();
		abstract function dbVersion();
	}