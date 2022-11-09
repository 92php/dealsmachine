<?php
/**
 * class.upload.php         文件上传类
 * 
 * @author                  mashanling(msl-138@163.com)
 * @date                    2011-12-27 15:24:37
 * @last modify             2011-12-27 15:24:37 by mashanling
 */

class Upload {
    private $upload_dir       = './upload/';    //上传路径
    private $size_limit       = 2048;     //上传大小限制，单位：kb
    private $file;
    private $width            = 0;    //宽，与高同时大于0时则压缩
    private $height           = 0;    //高，与宽同时大于0时则压缩
    private $rename           = 0;    //文件重命名规则。-1：保持文件名不变；0：默认规则，以时间戳+4位随机数命名；
    private $allow_extension  = array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'rar', 'txt', 'zip', 'doc', 'xls');
    
    /**
     * 
     * 设置水印相关变量
     */
    private $set_water    = false;    //是否设置水印
    private $position     = 'br';    //水印位置,可设置值见函数
    private $font_size    = 12;    //水印字体大小
    private $font         = './simsun.ttc';    //水印字体
    private $color        = '#cccccc';    //水印字体颜色
    private $offset_x     = 0;    //水印x偏移量
    private $offset_y     = 0;    //水印y偏移量
    private $trans        = 20;    //水印图片透明度
    
    /**
     * 构造函数
     * 
     * @param array $array 上传键值数据组
     * 
     * @return void 无返回值
     */
    function __construct($array = array()) {
        if (!empty($array)) {
            foreach ($array as $k => $v) {
                $this->$k = $v;
            }
        }
    }
    
    /**
     * 上传文件
     * 
     * @param mixed  $file		  文件
     * @param string $upload_dir 上传路径
     * 
     * @return mixed 成功上传，返回数据，否则返回错误信息
     */
    function execute($file, $upload_dir = '') {
        $this->file = $file;
        $upload_dir = empty($upload_dir) ? $this->upload_dir : $upload_dir;
        
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            return '不能创建上传目录：' . $upload_dir;
        }
        
        if (!is_writable($upload_dir)) {
            return '上传目录不可写：' . $upload_dir;
        }
        
        if (($result = $this->checkUpload()) !== true) {
            return $result;
        }
        
        if (!$this->checkExtension()) {
            return '上传文件类型不允许。可上传类型为：' . join('、', $this->allow_extension) . '。当前上传类型为：' . $this->file_extension;
        }
        
        if (!$this->checkSize()) {
            return '上传文件大小超出最大值，即：' . $this->fileUnit($this->size_limit * 1024) . '。当前上传文件大小：' . $this->fileUnit();
        }
        
        if (!is_uploaded_file($this->file['tmp_name'])) {
            return '非法上传文件';
        }
        
        $filename = $this->resetName();
        $pathname = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $pathname)) {
            return '移动文件失败：' . $pathname;
        }
        
        unset($this->file, $file);
        
        if ($this->set_water && (($_water = $this->water($pathname, $this->background, $this->position)) !== true)) {
            return $_water;
        }
        
        $this->resize($pathname, $this->width, $this->height, $pathname);
        return array(
                'filename'  => $filename, 
                'pathname'  => $pathname, 
                'mime'      => $this->file_mime, 
                'filesize'  => $this->fileUnit(), 
                'extension' => $this->file_extension
        );
    }//end execute
    
    /**
     * 检查文件是否上传成功
     * 
     * @return mixed 上传成功，返回true，否则返回错误信息
     */
    private function checkUpload() {
        $this->filename  = strtolower($this->file['name']);
        $this->file_mime = $this->file['type'];
        $this->filesize  = $this->file['size']; 
        switch ($this->file['error']) {
            case UPLOAD_ERR_OK:
                return true;
                break;
            
            case UPLOAD_ERR_INI_SIZE:
                return '上传文件大小超出了php.ini中upload_max_filesize选项指定的值，即：' . $this->fileUnit(ini_get('upload_max_filesize'));
                break;
            
            case UPLOAD_ERR_FORM_SIZE:
                return '上传文件大小超出了表单中指定的最大值，即：' . $this->fileUnit(isset($_POST['MAX_FILE_SIZE']) ? intval($_POST['MAX_FILE_SIZE']) : 0);
                break;
                    
            case UPLOAD_ERR_PARTIAL:
                return '文件只有部分被上传';
                break;
                
            case UPLOAD_ERR_NO_FILE:
                return '没有文件被上传';
                break;
                
            case UPLOAD_ERR_NO_TMP_DIR:
                return '无法找上传临时文件夹';
                break;
                
            case UPLOAD_ERR_CANT_WRITE:
                return '文件写入失败';
                break;
                
            default:
                return '求知上传错误';
                break;
        }
    }//end checkUpload
    
    
    /**
     * 检查文件后缀名是否在指定后缀内
     * 
     * @return bool 在，返回true，否则返回false
     */
    private function checkExtension() {
        $extension = pathinfo($this->filename, PATHINFO_EXTENSION);
        $extension = strtolower($extension);
        $this->file_extension = $extension;
        return in_array($extension, $this->allow_extension);
    }
    
    /**
     * 检查文件大小是否超出限制
     * 
     * @return bool 未超出，返回true，否则返回false
     */
    private function checkSize() {
        return $this->size_limit == -1 || $this->size_limit * 1024 >= $this->filesize;
    }
    
    
    /**
     * 重命名
     * 
     * @return string 新文件名
     */
    private function resetName() {
        switch ($this->rename) {
            case 0:
                $filename = date('YmdHis') . mt_rand(1000, 9999);
                break;
                
            case -1:
                return $this->filename;
                break;
            
            default:
                $filename = strstr($this->filename, '.' . $this->file_extension, true);
                $filename = function_exists($this->rename) ? $this->rename($filename) : $filename;
                break;
        }
        return $filename . '.' . $this->file_extension;
    }
    
    /**
     * 返回文件大小，带单位
     * 
     * @param int $filesize 大小，单位：字节
     * @param int $decimals 小数点数，默认：2
     * 
     * @return string 带单位的文件大小
     */
    private function fileUnit($filesize = '', $decimals = 2) {
        $filesize = $filesize == '' ? $this->filesize : $filesize;
        if ($filesize >= 1073741824) {
		    $filesize = sprintf('%.2f GB', $filesize / 1073741824);
    	}
    	elseif ($filesize >= 1048576) {
    		$filesize = sprintf('%.2f MB', $filesize / 1048576);
    	}
    	elseif($filesize >= 1024) {
    		$filesize = sprintf('%.2f KB', $filesize / 1024);
    	}
    	else {
    		$filesize = $filesize . ' Bytes';
    	}
    	return $filesize;
    }
    
    /**
     * 缩放图片
     * 
     * @param string $src_file 源图片
     * @param int    $to_w	         缩放至宽度
     * @param int    $to_h     缩放至高度
     * @param int    $to_file  缩放至图片
     * 
     * @return void 无返回值
     */
    private function resize($src_file, $to_w, $to_h, $to_file = '') {
        if (!$to_w || !$to_h) {
            return false;
        }
        $to_file   = $to_file ? $to_file : $src_file;
        $info      = '';
        $src_info  = getimagesize($src_file, $info);
        switch ($src_info[2]) {
            case 1:
                $image_type = 'gif';
                break;
                
            case 2:
                $image_type = 'jpeg';
                break;
                
            case 3:
                $image_type = 'png';
                break;
                
            default:
                return false;
                break;
        }
        $func_image = 'imagecreatefrom' . $image_type;
        $image      = $func_image($src_file);
        $src_w      = imagesx($image);
        $src_h      = imagesy($image);
        $src_wh     = $src_w / $src_h;
        $to_wh      = $to_w / $to_h;
        if ($src_wh >= $to_wh) {
        $to_h = $src_w > $to_w ? $to_w * $src_h / $src_w : $to_h;
        }
        else {
            $to_w = $src_h > $to_h ? $to_h * $src_w / $src_h : $to_w;
        }
        $image_p    = imagecreatetruecolor($to_w, $to_h);
        $func_image = 'image' . $image_type;
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $to_w, $to_h, $src_w, $src_h);
        $image_type == 'jpeg' ? imagejpeg($image_p, $to_file, 100) : $func_image($image_p, $to_file);
        imagedestroy($image_p);
        $src_file != $to_file ? unlink($src_file) : '';
    }//end resize
    
    /**
     * 添加水印
     * 
     * @param string $image      待加水印图片
     * @param string $background 背景图或水印文字
     * @param string $position   水印位置
     * 
     * @return bool 成功添加，返回true，否则返回错误信息
     */
    private function water($image, $background, $position = 'br') {
        
        if (!file_exists($image)) {
            return '待加水印图片不存在';
        }
        
        $image_info = getimagesize($image);    //图片大小
        $image_w    = $image_info[0];    //图片宽
        $image_h    = $image_info[1];    //图片高
        $src_image  = $this->createImage($image_info[2], $image);
        
        if (file_exists($background)) {    //水印为图片
            $water_info  = getimagesize($background);
            $width       = $water_w = $water_info[0];    //水印宽
            $height      = $water_h = $water_info[1];    //水印高
            $water_image = $this->createImage($water_info[2], $background);
        }
        else {    //水印字体
            
            if (!file_exists($this->font)) {
                return '水印字体不存在';
            }
            
            $font_info = imagettfbbox($this->font_size, 0, $this->font, $background);
            $width  = $font_info[2] - $font_info[6];
            $height = $font_info[3] - $font_info[7];
            unset($font_info);
        }
        
        if ($image_w < $width || $image_h < $height) {
            return '图片水印图片或文字区域还小，无法生成水印';
        }
        
        $position_w   = $image_w - $width;
        $position_h   = $image_h - $height;
        $position_x_c = $position_w / 2;
        $position_y_c = $position_h / 2;    
        
        switch ($position) {
            case 'tl':    //顶部居左
                $position_x = 0;
                $position_y = 0;
                break;
                
            case 'tc':    //顶部居中
                $position_x = $position_x_c;
                $position_y = 0;
                break;
                
            case 'tr':    //顶部居右
                $position_x = $position_w;
                $position_y = 0;
                break;
                
            case 'cl':    //中部居左
                $position_x = 0;
                $position_y = $position_y_c;
                break;
                
            case 'cc':    //中部居中
                $position_x = $position_x_c;
                $position_y = $position_y_c;
                break;
                
            case 'cr':    //中部居右
                $position_x = $position_w;
                $position_y = $position_y_c;
                break;
                
            case 'bl':    //底部居左
                $position_x = 0;
                $position_y = $position_h;
                break;
                
            case 'bc':    //底部居中
                $position_x = $position_x_c;
                $position_y = $position_h;
                break;
                
            case 'br':    //底部居右
                $position_x = $position_w;
                $position_y = $position_h;
                break;
            
            default:
                $position_x = rand(0, $position_w);
                $position_y = rand(0, $position_h);
                break;
        }
        $position_x += $this->offset_x;
        $position_y += $this->offset_y;
        imagealphablending($src_image, true);
        
        if (isset($water_image)) {    //水印图片
            imagecopymerge($src_image, $water_image, $position_x, $position_y, 0, 0, $water_w, $water_h, $this->trans);
        }
        else {
            
            if (strlen($this->color) == 7) {
                $red   = hexdec(substr($this->color, 1, 2));
                $green = hexdec(substr($this->color, 3, 2));
                $blue  = hexdec(substr($this->color, 5));
            }
            else {
                $red   = rand(0, 255);
                $green = rand(0, 255);
                $blue  = rand(0, 255);
            }
            imagettftext($src_image, $this->font_size, 0, $position_x, $position_y + $height, imagecolorallocatealpha($src_image, $red, $green, $blue, 80), $this->font, $background);
        }
        unlink($image);
        
        switch ($image_info[2]) {
            case 1:
                imagegif($src_image, $image);
                break;
            
            case 2:
                imagejpeg($src_image, $image, 100);
                break;
                
            case 3:
                imagepng($src_image, $image);
                break;
        }
        
        if (isset($water_image)) {
            unset($water_image);
        }
        
        if (isset($water_image)) {
            imagedestroy($water_image);
        }
        
        unset($image_info);
        imagedestroy($src_image);
        
        return true;
    }//end water

    /**
     * 生成图片
     * 
     * @param int    $type	图片类型
     * @param string $image 图片
     * 
     * @return resource 图片资源
     */
    private function createImage($type, $image) {
        switch ($type) {
            case 1:
                $image = imagecreatefromgif($image);
                break;
                
            case 2:
                $image = imagecreatefromjpeg($image);
                break;
                
            case 3:
                $image = imagecreatefrompng($image);
                break;
            
            default:
                exit('不支持的图片格式');
                break;
        }
        return $image;
    }
}
?>