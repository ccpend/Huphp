<?php
	//验证码类
	class Vcode{
		private $width;				//验证码的宽度
		private $height;			//验证码的高度
		private $codeNum;			//验证码验证字符的数量
		private $disturbColorNum;	//干扰元素的数量
		private $checkCode;			//验证码字符
		private $image;				//图像资源
		
		/**
		 * 构造方法，并设置一些初始属性
		 * @param	int		$width		验证码的宽度，默认为80像素
		 * @param	int		$height		验证码的高度，默认为20像素
		 * @param	int		$codeNum	验证的字符的数量，默认为4个
		 */
		public function __construct($width = 80, $height = 20, $codeNum = 4){
			$this->width = $width;				//成员属性宽度初始化
			$this->height = $height;			//成员属性高度初始化
			$this->codeNum = $codeNum;			//成员属性验证码字符数量初始化
			
			$number = floor($height * $width / 15);	//做一个变量
			if($number > 240 - $codeNum){
				$this->disturbColorNum = 240 - $codeNum;
			}else{
				$this->disturbColorNum = $number;
			}
			$this->checkCode = $this->createCheckCode();	//为成员属性checkcode初始化
		}
		
		/**
		 * 输出验证码，也向服务器的SESSION中保存验证码的字符
		 * 用echo 输出对象即可取得
		 * @return	string	验证码
		 */
		public function __tostring(){
			$_SESSION['code'] = strtolower($this->checkCode);	//把验证码加到SESSON中去
			$this->outImg();									//输出验证码
			return '';								
		}
		
		private function outImg(){		//通过该方法向浏览器中输出图像，一步步来
			$this->getCreateImage();	//创建画布并初始化
			$this->setDisturbColor();	//创建干扰元素
			$this->outputText();		//输出字符
			$this->outputImage();		//输出图片
		}
		
		/**
		 * 创建图像资源并且创造背景
		 */
		private function getCreateImage(){
			$this->image = imagecreatetruecolor($this->width, $this->height);	//创建黑色画布
			$backColor = imagecolorallocate($this->image, rand(225,255), rand(225,255), rand(225,255));
			@imagefill($this->image,0, 0, $backColor);				//填充上背景颜色
			$border = imagecolorallocate($this->image, 225, 225, 225);
			imagerectangle($this->image, 0, 0, $this->width - 1, $this->height - 1, $border);	//绘制边框
		}
		
		/**
		 * 创建随机生成的验证的字符
		 */
		private function createCheckCode(){
			//随机生成用户指定的字符串，去掉容易混淆的字符oOLlz和数字012
			$code = '3456789abcdefghijkmnpqrstuvwxyABCDEFGHIJKMNPQRSTUVWXY';
			$ascii = '';
			for($i = 0; $i < $this->codeNum; $i++){
				$char = $code[rand(0, strlen($code) - 1)];
				$ascii .= $char;
			}
			
			return $ascii;
		}
		
		/**
		 * 创建干扰元素
		 */
		private function setDisturbColor(){
			//创建干扰点
			for($i = 0; $i < $this->disturbColorNum; $i++){
				$color = imagecolorallocate($this->image, rand(200,255), rand(200,255), rand(200,255)); //干扰点的颜色
				imagesetpixel($this->image, rand(1, $this->width - 2), rand(1, $this->height - 2), $color);
			}
			
			//创建干扰圆弧
			for($i = 0; $i < 10; $i++){
				$color = imagecolorallocate($this->image, rand(200,255), rand(200,255), rand(200,255)); //干扰线的颜色
				imagearc($this->image, rand(-10, $this->width), rand(-10, $this->height), rand(30, 300), rand(20, 200), 55, 44, $color);
			}
		}
		
		/**
		 * 文字的颜色。随机摆放，向图像中输出
		 */
		private function outputText(){
			for($i = 0; $i <= $this->codeNum; $i++){
				$fontcolor = imagecolorallocate($this->image, rand(0, 128), rand(0, 128), rand(0, 128));
				$fontSize = rand($this->width / $this->codeNum / 1.8, $this->width / $this->codeNum / 2.2);
				$fontFamily = array('Action_Force.ttf', 'ASSEENONTV.ttf', 'Algerian.ttf');					//字体数组
				$x = floor($this->width / $this->codeNum  * $i + ($this->width / ($this->codeNum * 3)));			//x
				$y = rand(($this->height + imagefontheight($fontSize)) / 2.3, ($this->height + imagefontheight($fontSize)) / 2.1);	//y
				$angle =rand(-20, 20);	//随机旋转度数
				imagettftext($this->image, $fontSize, $angle, $x, $y, $fontcolor, HUPHP_PATH.'commons/fontsize/'.$fontFamily[rand(0, count($fontFamily)-1)] , $this->checkCode[$i]);
			}
		}
		
		/**
		 * 最后输出图片
		 */
		private function outputImage(){
			//自动检测GD库支持的图片的创建类型
			if(imagetypes() & IMG_GIF){
				header('Content-Type:image/gif');
				imagegif($this->image);
			}else if(imagetypes() & IMG_JPG){
				header('Content-Type:image/jpeg');
				imagegif($this->image);
			}else if(imagetypes() & IMG_PNG){
				header('Content-Type:image/png');
				imagegif($this->image);
			}else if(imagetypes() & IMG_WBMP){
				header('Content-Type:image/gif');
				imagegif($this->image);
			}else{
				die('PHP不支持图像创建');
			}
		}
		
		/**
		 * 销毁图像资源
		 */
		public function __destruct(){
			imagedestroy($this->image);
		}
	}
	
	
	
	
	