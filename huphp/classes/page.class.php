<?php
	//分页类
	class Page{
		private $total;			//数据表中的总数
		private $listRows;		//每页显示行数
		private $limit;			//SQL语句使用limit从名
		private $uri;			//url地址
		private $pageNum;		//页数
		private	$page;			//当前页数
		//分页中显示的信息内容，可以自己设置
		private $config = array('head'=>'条记录', 'prev'=>'上一页', 'next'=>'下一页' ,'first'=>'首页', 'last'=>'尾页');
		private $listNum = 10;	//每页下面分页列表显示的个数
		
		/**
		 * 构造方法，可以设置分页的属性
		 * @param	int		$total		计算分页的总记录数
		 * @param	int		$listRows	每页每页显示的记录数，默认是25个
		 * @param	string	$pa			向目标页面传递的参数
		 * @param	bool	$ord		可选的，默认值为true则默认分页从第一个开始显示，flase为分页从最后一个开始显示
		 */
		public function __construct($total, $listRows = 25, $pa = '', $ord = true){
			$this->total = $total;
			$this->listRows = $listRows;
			$this->uri = $this->getUrl($pa);
			$this->pageNum = ceil($this->total / $this->listRows);		//总页数
			if(!empty($_GET['page'])){
				if(($_GET['page'] < 1) || ($_GET['page'] > $this->pageNum)){		//过滤非法手动过大操作
					$page = 1;
				}else{
					$page = $_GET['page'];
				}
			}else{
				if($ord){
					$page = 1;
				}else{
					$page = $this->pageNum;
				}
			}
			
			if($total > 0){
				if(preg_match('/\D/', $page)){		//判断如果不都是数字
					$this->page = 1;
				}else{
					$this->page = $page;
				}
			}else{
				$this->page = 0;
			}
			
			$this->limit = $this->setLimit();
		}
		
		/**
		 * 用于设置分页的显示信息，可以连贯操作
		 * @param	string	$param		是数组config的下标
		 * @param	string	$value		数组config的值
		 * @return	object				返回本对象自己$this
		 * 说明，应为该方法返回的的值是对象的本身，所以继续可以再后面->set()这样连贯操作，这是一个好技巧
		 */
		public function set($param, $value){
			if(array_key_exists($param, $this->config)){			//查看config数组里是否有这个键
				$this->config[$param] = $value;
			}
			return $this;
		}
		
		/**
		 * 设置limit，组合生成SQL语句
		 */
		private function setLimit(){
			if($this->page > 0){
				return ($this->page - 1) * $this->listRows.', '.$this->listRows;
			}else{
				return 0;
			}
		}
		
		/**
		 * 获得URL
		 */
		private function getUrl($pa){
			if($pa == ''){
				return H_URL.'/'.$_GET['a'].'/';
			}else{
				return H_URL.'/'.$_GET['a'].'/'.trim($pa, '/').'/';				
			}
		}
		
		/**
		 * 外部获取对象属性的接口
		 */
		public function __get($args){
			if($args == 'limit' || $args == 'page'){
				return $this->$args;
			}else{
				return null;
			}
		}
		
		/**
		 * 多少个记录开始
		 * 返回：（(当前页数-1) * 每页显示行数 ）+ 1 
		 */
		private function start(){
			if($this->total == 0){
				return 0;
			}else{
				return ($this->page - 1) * $this->listRows + 1;
			}
		}
		
		/**
		 * 到多少个记录结束
		 * 返回：（当前页数 * 每页显示页数）与 总数 一个取个最小值
		 */
		private function end(){
			return min($this->page * $this->listRows, $this->total);
		}
		
		/**
		 * 显示 第一页 和 上一页 选项
		 */
		private function firstprev(){
			if($this->page > 1){
				$str = '&nbsp;<a href="'.$this->uri.'page/1">'.$this->config['first'].'</a>&nbsp;';
				$str .= '&nbsp;<a href="'.$this->uri.'page/'.($this->page - 1).'">'.$this->config['prev'].'</a>&nbsp;';
				return $str;
			}
		}
		
		/**
		 * 1 2 3 4 5 这个选项
		 */
		private function pageList(){
			$linkPage = '&nbsp;<b>';
			
			$inum = floor($this->listNum / 2);
			
			for($i = $inum; $i >= 1; $i--){
				$page = $this->page - $i;
				if($page >= 1){
					$linkPage .= '<a href="'.$this->uri.'page/'.$page.'">'.$page.'</a>&nbsp;';
				}
			}
			
			if($this->pageNum > 1){
				$linkPage .= '<span>'.$this->page.'</span>&nbsp;';
			}
			
			for($i = 1; $i <= $inum; $i++){
				$page = $this->page + $i;
				if($page <= $this->pageNum){
					$linkPage .= '<a href="'.$this->uri.'page/'.$page.'">'.$page.'</a>&nbsp;';
				}else{
					break;
				}
			}
			
			$linkPage .= '</b>';
				
			return $linkPage;
		}
		
		/**
		 * 下一页	最后一页
		 */
		private function nextlast(){
			if($this->page != $this->pageNum){
				$str = '&nbsp;<a href="'.$this->uri.'page/'.($this->page + 1).'">'.$this->config['next'].'</a>&nbsp;';
				$str .= '&nbsp;<a href="'.$this->uri.'page/'.$this->pageNum.'">'.$this->config['last'].'</a>&nbsp;';
				return $str;
			}
		}
		
		/**
		 * 跳转到哪页
		 */
		private function goPage(){
			if($this->pageNum > 1){
				return '&nbsp;<input type="text" style="width:28px;" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>'.$this->pageNum.')?'.$this->pageNum.':this.value;location=\''.$this->uri.'page/\'+page+\'\'}" value="'.$this->page.'"><input type="button" value="GO" onclick="javascript:var page=(this.previousSibling.value>'.$this->pageNum.')?'.$this->pageNum.':this.previousSibling.value;location=\''.$this->uri.'page/\'+page+\'\'">&nbsp;';
			}
		}
		
		/**
		 * 计算本页多少条
		 */
		private function disnum(){
			if($this->total > 0){
				return $this->end() - $this->start() + 1;
			}else{
				return 0;
			}
		}
		
		/**
		 * 按指定的格式输出分页
		 * @param	int		为0-7的数字，每个数字就是一个参数，可以自定义输出的结构和顺序
		 * @return	string	输出的下面的分页的信息
		 */
		public function fpage(){
			$arr = func_get_args();
			$html[0] = '&nbsp;共<b>'.$this->total.'</b>'.$this->config['head'].'&nbsp;';
			$html[1] = '&nbsp;本页<b>'.$this->disnum().'</b>条&nbsp;';
			$html[2] = '&nbsp;本页从<b>'.$this->start().'-'.$this->end().'</b>条&nbsp';
			$html[3] = '&nbsp;<b>'.$this->page.'/'.$this->pageNum.'</b>页&nbsp;';
			$html[4] = $this->firstprev();
			$html[5] = $this->pageList();
			$html[6] = $this->nextlast();
			$html[7] = $this->goPage();
			
			$fpage = '<div>';
			
			if(count($arr) < 1){
				$arr = array(0, 1, 2, 3, 4, 5, 6, 7);
			}
			
			for($i = 0; $i < count($arr); $i++){
				$fpage .= $html[$arr[$i]];
			}
			
			$fpage .= '</div>';
			
			return $fpage;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
