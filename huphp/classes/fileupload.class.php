<?php
	//文件上传类
	class FileUpload extends Image{
		private $allowtype = array('jpg', 'gif', 'png');	//设置文件上传的类型，可以用set()设置，使用小字母
		private $maxsize = 1000000;							//设置文件的大小，单位是字节，可以用set()设置
		private $israndname = true;							//设置是否是用随重命名,true为设置,false为不设置，可以用set()设置
		private $thumb = array();							//设置用缩放图片，可以用set()设置
		private $watermark = array();						//设置图片加水印,可以用set()设置
		private $originName; 								//原文件名
		private $tmpFileName;								//临时文件名
		private $fileType;									//文件类型，文件名后缀
		private $fileSize;									//文件大小
		private $newFileName;								//新文件名
		private $errorNum = 0;								//错误号
		private	$errorMess = '';							//错误报告消息
		
		/**
		 * 用于设置成员属性($path, $allowtype, $maxsize, $israndname, $thumb, $watermark )
		 * 可以使用连贯操作一次性设置多个值
		 * @param	string	$key	成员属性名（不区分大小写）
		 * @param	mixed	$val	为成员属性设置值
		 * @return	object	$this	返回对象本身，以便于控制器内的连贯操作
		 */
		public function set($key, $val){
			$key = strtolower($key);
			if(array_key_exists($key, get_class_vars(get_class($this)))){
				$this->setOption($key, $val);
			}
			return $this;
		}
		
		/**
		 * 调用该方法上传]
		 * @param	string	$fileField	上传文件的表单的名称 name属性
		 * @return	bool				如果成功
		 */
		public function upload($fileField){
			$return = true;
			if(!$this->checkFilePath()){		//检查文件路径，如果错误返回假取反
				$this->errorMess = $this->getError();
				return false;
			}
			
			$name = $_FILES[$fileField]['name'];
			$tmp_name = $_FILES[$fileField]['tmp_name'];
			$size = $_FILES[$fileField]['size'];
			$error = $_FILES[$fileField]['error'];			
			
			//如果上传多个文件则$name会是一个数组
			if(is_array($name)){
				$errors = array();
				for($i = 0; $i < count($name); $i++){
					if($this->setFiles($name[$i], $tmp_name[$i], $size[$i], $error[$i])){	//循环设置文件信息
						//如果文件大小超过规定或者类型非法
						if(!$this->checkFileSize() || !$this->checkFileType()){
							$errors[] = $this->getError();
							$return = false;
						}
					}else{
						$errors[] = $this->getError();
						$return = false;
					}
					
					//如果有问题，则初始化属性
					if(!$return){
						$this->setFiles();
					}
				}
				
				//如果额没有任何错误
				if($return){
					$fileNames = array();
					for($i = 0; $i < count($name); $i++){
						if($this->setFiles($name[$i], $tmp_name[$i], $size[$i], $error[$i])){	//循环设置文件信息
							$this->setNewFileName();
							if(!$this->copyFile()){		//如果上传失败
								$errors [] = $this->getError();
								$return = false;
							}
							$fileNames[] = $this->newFileName;
							
							//设置缩放	$this->thumb不为空
							if(!empty($this->thumb)){
								//如果thumb['prefix']文件前缀为空
								if(empty($this->thumb['prefix'])){
									$this->thumb['prefix'] = '';
								}
								
								$this->newFileName = $this->thumb($this->newFileName, $this->thumb['width'], $this->thumb['height'], $this->thumb['prefix']);
							}
							
							//设置水印	$this->watermark不为空
							if(!empty($this->watermark)){
								//如果watermark['prefix']文件前缀为空
								if(empty($this->watermark['prefix'])){
									$this->watermark = '';
								}
								$this->newFileName = $this->waterMark($this->newFileName, $this->watermark['water'], $this->watermark['position'], $this->watermark['prefix']);
							}	
						}
					}
					$this->newFileName = $fileNames;
				}
				$this->errorMess = $errors;
				return $return;
				
			}else{
			//如果上传的是单个文件
				if($this->setFiles($name, $tmp_name, $size, $error)){	//设置文件信息
					if($this->checkFileSize() && $this->checkFileType()){
						$this->setNewFileName();	//设置新文件名字
						if($this->copyFile()){		//上传文件，返回0 说明成功
							//设置缩放	$this->thumb不为空
							if(!empty($this->thumb)){
								//如果thumb['prefix']文件前缀为空
								if(empty($this->thumb['prefix'])){
									$this->thumb['prefix'] = '';
								}
								
								$this->newFileName = $this->thumb($this->newFileName, $this->thumb['width'], $this->thumb['height'], $this->thumb['prefix']);
							}
							
							//设置水印	$this->watermark不为空
							if(!empty($this->watermark)){
								//如果watermark['prefix']文件前缀为空
								if(empty($this->watermark['prefix'])){
									$this->watermark['prefix'] = '';
								}
								$this->newFileName = $this->waterMark($this->newFileName, $this->watermark['water'], $this->watermark['position'], $this->watermark['prefix']/*, $this->watermark['path']*/);
							}

							return true;
						}else{
							$return = false;
						}
					}else{
						$return = false;
					}
				}else{
					$return = false;
				}
				if(!$return){
					$this->errorMess = $this->getError();
				}
				
				return $return;
			}
		}
		
		/**
		 * 获取上传后的文件名称
		 * @return	string	上传后新的名称
		 */
		public function getFileName(){
			return $this->newFileName;
		}
		
		/**
		 * 上传失败后，在外部调用该方法返失败的错误信息
		 * @return	string	上传失败的错误信息
		 */
		public function getErrorMsg(){
			return $this->errorMess;
		}
		
		/**
		 * 设置上传的错误信息
		 */
		private function getError(){
			$str = '上传文件<font color="red">'.$this->originName.'</font>时出错';
			switch ($this->errorNum){
				case 4:
					$str .= '没有文件被上传';
					break;
				case 3:
					$str .= '文件只有部分被上传';
					break;
				case 2:
					$str .= '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项中指定的值';
					break;
				case 1:
					$str .= '上传文件的大小超过了 php.ini 中 upload_max_filesize 选项限定的值';
					break;
				case -1:
					$str .= '未允许类型';
					break;
				case -2:
					$str .= '文件过大，上传的文件不能超过'.$this->maxsize.'个字节';
					break;															
				case -3:
					$str .= '上传失败';
					break;	
				case -4:
					$str .= '建立存放上传文件的目录失败，请重新指定上传目录';
					break;		
				case -5:
					$str .= '必须指定上传文件的路径';
					break;
				default:
					$str .= '未知错误';																	
			}
			
			return $str.'<br />';
		}
		
		/**
		 * 设置和$_FILES有关的内容
		 */
		private function setFiles($name = '', $tmp_name = '', $size = 0, $error = 0){
			$this->setOption('errorNum', $error);
			if($error){
				return false;
			}
			$this->setOption('originName', $name);
			$this->setOption('tmpFileName', $tmp_name);	
			$aryStr = explode('.', $name);
			$this->setOption('fileType', strtolower($aryStr[count($aryStr)-1]));
			$this->setOption('fileSize', $size);
			return true;		
		}

		/**
		 * 为单个成员属性设置值
		 */
		private function setOption($key, $val){
			$this->$key = $val;
		}			
		
		/**
		 * 设置上传后的文件名称
		 */
		private function setNewFileName(){
			if($this->israndname){
				$this->setOption('newFileName', $this->proRandName());
			}else{
				$this->setOption('newFileName', $this->originName);
			}
		}
		
		/**
		 * 检查上传文件是否合法类型
		 */
		private function checkFileType(){
			if(in_array(strtolower($this->fileType), $this->allowtype)){
				return true;
			}else{
				$this->setOption('errorNum', -1);
				return false;
			}
		}
		
		/**
		 * 检查上传文件是不是允许的大小
		 */
		private function checkFileSize(){
			if($this->fileSize > $this->maxsize){
				$this->setOption('errorNum', -2);
				return false;
			}else{
				return true;
			}
		}
				
		/**
		 * 检查是否存放上传文件目录
		 */
		private function checkFilePath(){
			//如果$this->path 为空
			if(empty($this->path)){
				$this->setOption('errorNum', -5);
				return false;
			}
			
			if(!file_exists($this->path) || !is_writable($this->path)){
				//如果创建文件失败
				if(!@mkdir($this->path, 0755)){
					$this->setOption('errorNum', -4);
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * 设置随机文件名
		 */
		private function proRandName(){
			$fileName = date('YmdHis').'_'.rand(100, 999);	//获取随机文件名
			return $fileName.'.'.$this->fileType;
		}
		
		/**
		 * 复制上传文件到指定的位置
		 */
		private function copyFile(){
			if(!$this->errorNum){	//如果上传的步骤没有任何错误
				$path = rtrim($this->path, '/').'/';
				$path .= $this->newFileName;
				if(@move_uploaded_file($this->tmpFileName, $path)){		//如果移动成功返回真
					return true;
				}else{
					$this->setOption('errorNum', -3);
					return false;
				}
			}else{
				return false;
			}
		}
	}
	
	
	
	
	
	
	
	
	