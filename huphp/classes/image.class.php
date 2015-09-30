<?php
	//图像处理类
	class Image{
		protected $path;	//图片所在的路径
		
		/**
		 * 
		 * 创建对象时传递图像的一个路径，默认是框架的文件上传目录
		 * @param	string	$path	可以指定处理图片的路径
		 */
		public function __construct($path = ''){
			if($path == ''){
				$path = PROJECT_PATH.'public/uploads';
			}
			$this->path = $path;
		}
		
		/**
		 * 对指定的图像进行缩放	
		 * @param	string	$name	需要处理图片的名称
		 * @param	int		$width	缩放后的宽度
		 * @param	int		$height	缩放后的高度
		 * @param	string	$qz		新的图片的前缀
		 * @return	mixed			缩放后返回的图片名称，失败返回false
		 */
		public function thumb($name, $width, $height, $qz = 'th_'){
			$imgInfo = $this->getInfo($name);									//获取图片的信息(调用内部封装函数)
			$srcImg = $this->getImg($name, $imgInfo);							//获取图片资源
			$size = $this->getNewSize($name, $width, $height, $imgInfo);		//获得新图片的尺寸
			$newImg = $this->kidOfImage($srcImg, $size, $imgInfo);				//获取新图片的资源
			return $this->createNewImage($newImg, $qz.$name, $imgInfo);			
		}
		
		/**
		 * 给图片添加水水印
		 * @param	string	$groundName		背景图片，需要添加水印的背景图片，暂时只支持jpg,gif.png格式
		 * @param	string	$waterName		图片水印，作为水印的图片，暂时只支持jpg,gif.png格式
		 * @param	int		$waterPos		水印的位置，有10中模式，默认0为随机模式
		 * @param	string	$qz				加完水印后新图片的前缀
		 * @param	mixed	生成水印后的图面的名称，失败返回假
		 */
		public function waterMark($groundName, $waterName, $waterPos = 0, $qz = 'wa_'){
			$curpath =	rtrim($this->path, '/').'/';
			$dir = dirname($waterName);
			if($dir == '.'){
				$wpath = $curpath;
			}else{
				$wpath = $dir.'/';
				$waterName = basename($waterName);
			}
			
			//如果都存在
			if(file_exists($curpath.$groundName) && file_exists($wpath.$waterName)){
				$groundInfo = $this->getInfo($groundName);		//获取背景图片信息
				$waterInfo = $this->getInfo($waterName, $dir);		//获取水印图片信息
				
				if(!$pos = $this->position($groundInfo, $waterInfo, $waterPos)){
					Debug::addmsg('<font color="red">图片的大小不应该比水印小</font>');
					return false;
				}
				
				$groundImg = $this->getImg($groundName, $groundInfo);	//获取背景图片资源
				$waterImg = $this->getImg($waterName, $waterInfo, $dir);
				
				$groundImg = $this->copyImage($groundImg, $waterImg, $pos, $waterInfo);	//拷贝图像
				
				return $this->createNewImage($groundImg, $qz.$groundName, $groundInfo);
			}else{
				Debug::addmsg('<font color="red">图片或水印图片不存在</font>');
				return false;
			}
		}
		
		/**
		 * 水印的位置
		 * @param	要打水印背景图片信息	$groundInfo
		 * @param	水印图片的信息		$waterInfo
		 * @param	位置					$waterPos
		 */
		private function position($groundInfo, $waterInfo, $waterPos){
			//需要添加水印的图片如果长度与宽度比水印还小，则不能添加水印
			if(($groundInfo['width'] < $waterInfo['width']) || ($groundInfo['height'] < $waterInfo['height'])){
				return false;
			}
			
			switch($waterPos){
				case 1:	//1为顶端居左
					$posX = 0;
					$posY = 0;
					break;
				case 2:	//2为顶端居中
					$posX = ($groundInfo['width'] - $waterInfo['width']) / 2;
					$posY = 0;
					break;
				case 3:	//3为顶端居右
					$posX = $groundInfo['width'] - $waterInfo['width'];
					$posY = 0;
					break;
				case 4:	//4中部居左
					$posX = 0;
					$posY = ($groundInfo['height'] - $waterInfo['height']) / 2;
					break;
				case 5:	//5中部居中
					$posX = ($groundInfo['width'] - $waterInfo['width']) / 2;
					$posY = ($groundInfo['height'] - $waterInfo['height']) / 2;
					break;
				case 6:	//6为中端居右
					$posX = $groundInfo['width'] - $waterInfo['width'];
					$posY = ($groundInfo['height'] - $waterInfo['height']) / 2;
					break;
				case 7:	//7为低端居左
					$posX = 0;
					$posY = $groundInfo['height'] - $waterInfo['height'];
					break;
				case 8:	//8为低端居中
					$posX = ($groundInfo['width'] - $waterInfo['width']) / 2;
					$posY = $groundInfo['height'] - $waterInfo['height'];
					break;
				case 9:	//9为低端居右
					$posX = $groundInfo['width'] - $waterInfo['width'];
					$posY = $groundInfo['height'] - $waterInfo['height'];
					break;
				case 0:
				default:	//随机
					$posX = rand(0, $groundInfo['width'] - $waterInfo['width']);
					$posY = rand(0, $groundInfo['height'] - $waterInfo['height']);
					break;																																				
			}
			
			return array('posX'=>$posX, 'posY'=>$posY);
		}
		
		/**
		 * 获取图片的信息
		 * @param	string	$name	需要提取信息的图片的名称
		 * @param	string	$path	图片所在的路径
		 */
		private function getInfo($name, $path = '.'){
			$spath = $path == '.' ? rtrim($this->path, '/').'/' : $path.'/';	//路径
		
			$data = getimagesize($spath.$name);	//获得图像的信息的数组
			$imgInfo['width'] = $data[0];
			$imgInfo['height'] = $data[1];
			$imgInfo['type'] = $data[2];
			
			return $imgInfo;
		}
		
		/**
		 * 创建图像资源
		 * @param	string	$name		需要提取信息的图片的名称
		 * @param	array	$imgInfo	原图片相关的信息
		 * @param	string	$path		图片所在的路径
		 */
		private function getImg($name, $imgInfo, $path = '.'){
			$spath = $path == '.' ? rtrim($this->path, '/').'/' : $path.'/';	//路径
			$srcPic = $spath.$name;
			
			switch($imgInfo['type']){
				case 1:	//gif
					$img = imagecreatefromgif($srcPic);
					break;
				case 2:	//jpg
					$img = imagecreatefromjpeg($srcPic);
					break;
				case 3:	//png
					$img = imagecreatefrompng($srcPic);
					break;
				default:
					return false;
					break;		
			}
			return $img;
		}
		
		/**
		 * 等比列的缩放图片，如果原图比缩放之后的还小则图片大小保持不变
		 * @param	int		$width		要设置的宽度
		 * @param	int		$height		要设置的高度
		 * @param 	array	$imgInfo	原图片相关的信息
		 */
		private function getNewSize($name, $width, $height, $imgInfo){
			$size['width'] = $imgInfo['width'];				//将原图片的宽度赋给$size['width']
			$size['height'] = $imgInfo['height'];			//将原图片的高度赋给$size['width']
			
			if($width < $imgInfo['width']){
				$size['width'] = $width;					//改变$size['width'] 缩放的宽度如果比原图小才改变
			}
			
			if($width < $imgInfo['height']){
				$size['height'] = $height;					//改变$size['height'] 缩放的高度如果比原图小才改变
			}
			
			/**
			 * 妹的 这个算法叫我想我肯定是想不出来的
			 */
			//如果	原图的宽度*设宽度 > 原图的高度*设高度
			if($imgInfo['width'] * $size['width'] > $imgInfo['height'] * $size['height']){
				//如果宽度大于高度则  宽度直接到那个数值   高度等比计算压缩	最后高度 = 原高度*设宽度/原宽度
				$size['height'] = round($imgInfo['height'] * $size['width'] / $imgInfo['width']);
			}else{
				//否则高度大于宽度则   高度直接到那个数值  宽度等比计算压缩	最后宽度 = 原宽度*设高度/原高度
				$size['width'] = round($imgInfo['width'] * $size['height'] / $imgInfo['height']);
			}
			
			return $size;
		}
		
		/**
		 * 创建图片
		 */
		private function createNewImage($newImg, $newName, $imgInfo){
			$this->path = rtrim($this->path, '/').'/';
			switch($imgInfo['type']){
				case 1:	//gif
					$result = imagegif($newImg, $this->path.$newName);
					break;
				case 2:	//jpg
					$result = imagejpeg($newImg, $this->path.$newName);
					break;
				case 3:	//png
					$result = imagepng($newImg, $this->path.$newName);
					break;										
			}
			imagedestroy($newImg);
			return $newName;
		}
		
		/**
		 * 复制图片
		 */
		private function copyImage($groundImg, $waterImg, $pos, $waterInfo){
			imagecopy($groundImg, $waterImg, $pos['posX'], $pos['posY'], 0, 0, $waterInfo['width'], $waterInfo['height']);
			imagedestroy($waterImg);
			return $groundImg;
		}
		
		/**
		 * 创建新图片资源
		 */
		private function kidOfImage($srcImg, $size, $imgInfo){
			//返回一个图像标识符，代表了一幅大小为 x_size 和 y_size 的黑色图像。
			$newImg = imagecreatetruecolor($size['width'], $size['height']);
			$otsc = imagecolortransparent($srcImg);		//将某个颜色定义为透明色
			if($otsc > 0 && $otsc <imagecolorstotal($srcImg)){		//取得一幅图像调色板中的数目
				$transparentcolor = imagecolorsforindex($srcImg, $otsc);	//取得某索引的颜色
				$newtransparentcolor = imagecolorallocate(
					$newImg,
					$transparentcolor['red'],
					$transparentcolor['green'],
					$transparentcolor['blue']										
				);
				
				imagefill($newImg, 0, 0, $newtransparentcolor);
				imagecolortransparent($newImg, $newtransparentcolor);	
			}
			imagecopyresized($newImg, $srcImg, 0, 0, 0, 0, $size['width'], $size['height'], $imgInfo['width'], $imgInfo['height']);
			imagedestroy($srcImg);
			return $newImg;
		}
	}
	
	
	
	
	
	
	