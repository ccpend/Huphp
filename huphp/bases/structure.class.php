<?php 
	class Structure{
		public static $mess = array();		//提示消息
		
		/**
		 * 创建文件的方法
		 * @param	string	$fileName	需要创建的文件名
		 * @param	string	$str		需要向文件中写入的字符串
		 */
		public static function touch($fileName, $str){
			if(!file_exists($fileName)){
				if(file_put_contents($fileName, $str)){
					self::$mess[] = '创建文件'.$fileName.'成功。';
				}
			}
		}
		
		/**
		 * 创建目录的方法
		 * @param	string $dirs	需要创建目录的数组
		 */
		public static function mkdir($dirs){
			foreach($dirs as $dir){
				if(!file_exists($dir)){
					if(mkdir($dir, 755)){
						self::$mess[] = '创建目录'.$dir.'成功。';
					}
				}
			}
		}
		
		/**
		 * 创建运行时的缓存文件
		 */
		public static function runtime(){
			$dirs = array(
				PROJECT_PATH.'runtime/cache/',
				PROJECT_PATH.'runtime/cache/'.TPLSTYLE,
				PROJECT_PATH.'runtime/comps/',
				PROJECT_PATH.'runtime/comps/'.TPLSTYLE,
				PROJECT_PATH.'runtime/comps/'.TPLSTYLE.'/'.TMPPATH,
				PROJECT_PATH.'runtime/data/',
				PROJECT_PATH.'runtime/controls/',
				PROJECT_PATH.'runtime/controls/'.TMPPATH,
				PROJECT_PATH.'runtime/models/',
				PROJECT_PATH.'runtime/models/'.TMPPATH,																																
			);
			
			self::mkdir($dirs);
		}
		
		/**
		 * 创建项目的目录结构
		 */
		public static function create(){			//不存在runtime文件夹时创建
			self::mkdir(array(PROJECT_PATH.'runtime/'));
			
			//文件锁，一旦生成，就不在创建
			$structFile = PROJECT_PATH.'runtime/'.str_replace('/', '_', $_SERVER['SCRIPT_NAME']);	//runtime文件夹中主入口文件名（文件锁）
			
			/**
			 * 创建.htaccess文件
			 */
			if(!file_exists($structFile)){
				$fileName = PROJECT_PATH.'.htaccess';
				$str = <<<st
RewriteEngine on
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
</IfModule>
st;
				self::touch($fileName, $str);								
				
				$fileName = PROJECT_PATH.'config.inc.php';
				$str = <<<st
<?php
	@define('DEBUG', 1);				//开启调试模式 （1：开启）（0：关闭）
	@define('DRIVER', 'mysqli');		//数据库驱动选择，系统支持mysqli(默认)和pdo两种驱动
	//@define('DSN' 'mysql:host=localhost;dbname=huphp');	//如果PDO可以这种方式连接其他数据库，不使用则默认连接MySQL
	@define('HOST', 'localhost');	//数据库主机
	@define('USER', 'root');			//数据库用户名
	@define('PASS', '');				//数据库密码
	@define('DBNAME', 'huphp');		//连接数据库名
	@define('DBCHAR', 'utf8');	//设置数据库的默认连接字符集	
	@define('TABPREFIX', 'hu_');		//数据表前缀
	@define('CSTART', '0');			//缓存开关，1开启，0关闭
	@define('CTIME', '60*60*24*7');	//缓存时间
	@define('TPLPREFIX', 'html');	//模板文件的后缀名
	@define('TPLSTYLE', 'default');	//默认模板存放的目录
	@define('REWRITE', '0');		//是否开启index项目伪静态模式	,默认为关闭
	
	//\$memServers = array('localhost', '11211');		//使用Memcache缓存服务器
	/*
	//如果有多台Memcache服务器可以使用二维数组
	\$memServers = array(
		array('www.huphp.com', '11211'),
		array('www.php.net', '11211')
	);
	*/
st;
				self::touch($fileName, $str);
				
				if(!defined('DEBUG')){
					include $fileName;
				}
				
				$dirs = array(
					PROJECT_PATH.'classes/',						//项目通用类文件夹
					PROJECT_PATH.'commons/',						//项目通用函数functions.inc.php所在的文件夹				
					PROJECT_PATH.'public/',							//项目根目录下的的公用目录	
					PROJECT_PATH.'public/uploads/',					//项目公上传文件目录
					PROJECT_PATH.'public/css/',						//项目公css文件目录
					PROJECT_PATH.'public/js/',						//项目公js文件目录	
					PROJECT_PATH.'public/images/',					//项目公图片文件目录
					APP_PATH,										//当前的应用目录 如/home /admin
					APP_PATH.'models/',								//当前应用的模型目录
					APP_PATH.'controls/',							//当前应用的控制器目录	
					APP_PATH.'views/',								//当前应用的视图目录
					APP_PATH.'views/'.TPLSTYLE,						//当前应用的使用的模板目录
					APP_PATH.'views/'.TPLSTYLE.'/public/',			//当前应用的使用的公共模板目录	
					APP_PATH.'views/'.TPLSTYLE.'/resource/',		//当前应用的使用的公共资源目录
					APP_PATH.'views/'.TPLSTYLE.'/resource/css/',	//当前应用的使用的css目录	
					APP_PATH.'views/'.TPLSTYLE.'/resource/js/',		//当前应用的使用的js目录		
					APP_PATH.'views/'.TPLSTYLE.'/resource/images/',	//当前应用的使用的图片目录																																																																		
				);
				
				self::mkdir($dirs);
				
				$fileName = PROJECT_PATH.'commons/functions.inc.php';
				$str = <<<st
<?php
	//整个项目全局使用的函数可以声明在这个文件中。
st;
				self::touch($fileName, $str);
				
				//创建统一的消息模版
				$success = APP_PATH.'views/'.TPLSTYLE.'/public/success.'.TPLPREFIX;
				if(!file_exists($success)){
					copy(HUPHP_PATH.'commons/success', $success);
				}
				
				$page404 = APP_PATH.'views/'.TPLSTYLE.'/public/404.'.TPLPREFIX;				
				//创建404页面模板
				if(!file_exists($page404)){
					copy(HUPHP_PATH.'commons/404', $page404);
				}				
				
				//生成应用的默认的控制器commons.class.php		
				$fileName = APP_PATH.'controls/common.class.php';
				$str = <<<st
<?php
	class Common extends Action{
		function init(){
					
		}
	}				
st;
				self::touch($fileName, $str);
				
				//生成应用的默认的控制器index.class.php
				$fileName = APP_PATH.'controls/index.class.php';
				$str = <<<st
<?php
	class Index{
		function index(){
			echo '<span>欢迎使用HuPHP中文开源开发框架，第一次访问会生成项目应用结构</span><br>';
			echo '<pre>';
			echo file_get_contents('{$structFile}');
			echo '</pre>';
		}
	}				
st;
				self::touch($fileName, $str);
				
				//创建项目的文件锁
				self::touch($structFile, implode("\n", self::$mess));
			}
			
			self::runtime();		//创建运行时生成的文件
		}
		
		/**
		 * Common控制器的生成 
		 * @param	string	$srccontrolerpath	//原Common控制器的路径 
		 * @param	string	$controlerpath		//目标Common控制器的路径
		 */
		public static function commoncontroler($srccontrolerpath, $controlerpath){
			$srccommon = $srccontrolerpath.'common.class.php';
			$common = $controlerpath.'common.class.php';
			//如果新Common控制器不存在，原Common控制器有修改就生成
			if(!file_exists($common) || filemtime($srccommon) > filemtime($common)){
				copy($srccommon, $common);
			}
		}
		
		/**
		 * 控制器的生成 
		 */
		public static function controler($srccontrolerfile, $controlerpath, $m){
			$controlerfile = $controlerpath.strtolower($m).'action.class.php';	//设置生成控制器的文件名
			//如果生成的新控制器不存在，或者原控制器有改动，就生成
			if(!file_exists($controlerfile) || filemtime($srccontrolerfile) > filemtime($controlerfile)){
				//将	原控制器的内容读取出来
				$classContent = file_get_contents($srccontrolerfile);
				//看看有没有继承父类
				$super = '/extends\s+(.+?)\s*{/i';
				//如果已有父类
				if(preg_match($super, $classContent, $arr)){
					$classContent = preg_replace('/class\s+(.+?)\s+extends\s+(.+?)\s*{/i', 'class \1Action extends \2 {', $classContent, 1);
					//替换一些词，生成runtime文件夹里面的控制器类
					file_put_contents($controlerfile, $classContent);
				//没有父类时
				}else{
					//自动继承父类Common
					$classContent = preg_replace('/class\s+(.+?)\s*{/i', 'class \1Action extends Common {', $classContent, 1);
					file_put_contents($controlerfile, $classContent);
				}
			}
		}
		
		public static function model($className, $app){
			$driver = 'D'.DRIVER;	//数据库扩展技术父类名
			$path = PROJECT_PATH.'runtime/models/'.TMPPATH;	//model在runtime文件夹里生成的文件夹
			//不跨域
			if($app == ''){
				$src = APP_PATH.'models/'.strtolower($className).'.class.php';	//本项目文件夹下的model文件
				$psrc = APP_PATH.'models/___.class.php';		//假设本项目文件夹下的model父类（带替换）
				$className = ucfirst($className).'Model';		//Model类名	
				$parentClass = '___model';	//假设Model父类的文件名（带替换）
				$to = $path.strtolower($className).'.class.php';//在runtime文件夹里生成的类路径名称
				$pto = $path.$parentClass.'.class.php';//在runtime文件夹里生成的父类路径名称
				
			//有传APP则跨域（跨项目应用）
			}else{
				$src = PROJECT_PATH.$app.'/models/'.strtolower($className).'.class.php';//对过域下面的model文件
				$psrc = PROJECT_PATH.$app.'/models/___.class.php';//对过域下面的假设model父类文件（带替换）
				$className = ucfirst($app).ucfirst($className).'Model';//对过域下面的model类名
				$parentClass = ucfirst($app).'___model';//对过域下面的假设Model父类的文件名（带替换）
				$to = $path.strtolower($className).'.class.php';//在runtime文件夹里生成的类路径名称
				$pto = $path.$parentClass.'.class.php';	//在runtime文件夹里生成的父类路径名称	
			}
			
			//如果项目model文件夹下里已有原model存在
			if(file_exists($src)){
				$classContent = file_get_contents($src);
				$super = '/extends\s+(.+?)\s*{/i';
				//查看是否有父类，如果已有父类
				if(preg_match($super, $classContent, $arr)){
					/**
					 * $psrc	如果已有父类，父类的在model文件夹下的路径文件名
					 * $pto		如果已有父类，父类的在runtime文件夹下的路径文件名
					 */
					$psrc = str_replace('___', strtolower($arr[1]), $psrc);
					$pto = str_replace('___', strtolower($arr[1]), $pto);

					//父类的在原model文件夹下在存在
					if(file_exists($psrc)){
						if(!file_exists($pto) || filemtime($psrc) > filemtime($pto)){
							$pclassContent = file_get_contents($psrc);
							
							$preg = '/class\s+(.+?)\s*{/i';
							$pclassContent = preg_replace($preg, 'class '.$arr['1'].'Model extends '.$driver.' {', $pclassContent, 1);
							//生成runtime文件夹下的model的父类的文件
							file_put_contents($pto, $pclassContent);
						}
					}else{
						Debug::addmsg('<font color="red">文件'.$psrc.'不存在</font>');
					}
					
					//继承完数据库扩展类以后，将$driver重新赋值为父类的类名，以便子类使用
					$driver = $arr['1'].'Model';
					
					include_once($pto);
					Debug::addmsg('<b>'.$driver.'</b>类' ,1);
				}
				
				//model生成处理(没有父类或者不是父类！)
				if(!file_exists($to) || filemtime($src) > filemtime($to)){
					$preg = '/class\s+(.+?)\s*{/i';
					
					//$driver如有有父类则接受赋值，不然直接继承数据库扩展类
					$classContent = preg_replace($preg, 'class '.$className.' extends '. $driver.' {', $classContent, 1);
					//生成runtime文件夹下的model
					file_put_contents($to, $classContent);
				}
				
			}else{
				if(!file_exists($to)){
					$classContent = <<<st
<?php
	class {$className} extends {$driver} {
				
	}				
st;
					//生成runtime文件夹下的直接集成数据库扩展技术类的MODEL
					file_put_contents($to, $classContent);
				}	
			}
			
			include_once($to);
			Debug::addmsg('<b>'.$className.'</b>类' ,1);
			
			return $className;
		}
	}
	
	
	
	
	
	
	
	