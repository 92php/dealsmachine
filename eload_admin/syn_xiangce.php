<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/cls_image.php');
$image = new cls_image();

$syn_gallery_image = empty($_POST['syn_gallery_image'])?'':$_POST['syn_gallery_image'];
$goods_sn   = !empty($_POST['goods_sn']) ? $_POST['goods_sn'] : '';
$goods_id = '0';
if ($goods_sn!=''){
	$sql = "select goods_id from ".GOODS." WHERE  goods_sn = '$goods_sn' ";
	$goods_id = $db->getOne($sql);
}


if ($goods_id){
	$import_url = 'http://www.davismicro.com.cn/';
	if($syn_gallery_image!=''){
		$opts = array( 
		'http'=>array( 
		'method'=>'GET', 
		'timeout'=>60, 
		) 
		); 
		
		$delfalg = false;
		
		$sql = "SELECT img_url, thumb_url, img_original " .
				" FROM " . GGALLERY ." where  goods_id = '$goods_id' ";
		$arr = $db->arrQuery($sql);
		foreach($arr as $key => $row){
			if (!file_exists(ROOT_PATH.$row['img_url']) || !file_exists(ROOT_PATH.$row['thumb_url']) || !file_exists(ROOT_PATH.$row['img_original'])){
				$delfalg = true;
				//echo '进入判断图片不存在程序，';
				break;
			}else{
				//echo '进入判断图片存在程序，';
			}
		}
		if ($delfalg){
			//echo '进入删除程序，';
			 $sql = "delete from ".GGALLERY." where goods_id = '$goods_id'";
			 $db->query($sql);
		
			$context = stream_context_create($opts); 
			uksort($syn_gallery_image,"cmp");
			foreach($syn_gallery_image as $kk => $vv ){
				
				   $temp_str = array();
				   $temp_arr = explode('@@@',$vv);
				   foreach($temp_arr  as $kk2 => $vv2 ){
					  $temp_sub =  explode('@@',$vv2);
					  $temp_str[$temp_sub[0]] = $temp_sub[1];
					  $temp_str['goods_id'] = $goods_id;
				   }				   
				
					$img_syn_data = file_get_contents($import_url.$temp_str['img_url']);
					file_put_contents(ROOT_PATH . $temp_str['img_url'] , $img_syn_data, false, $context);
				
					$img_syn_data = file_get_contents($import_url.$temp_str['thumb_url']);
					file_put_contents(ROOT_PATH . $temp_str['thumb_url'] , $img_syn_data, false, $context);
				
					$img_syn_data = file_get_contents($import_url.$temp_str['img_original']);
					file_put_contents(ROOT_PATH . $temp_str['img_original'] , $img_syn_data, false, $context);
					//加水印
					$result = $image->add_watermark(ROOT_PATH . $temp_str['img_original'], '', $GLOBALS['_CFG']['watermark'], '', $GLOBALS['_CFG']['watermark_alpha']);
					$db->autoExecute(GGALLERY,$temp_str);
				
			}
			echo $goods_sn.'修复完成<br>';
		}else{
			echo $goods_sn.'相册已经存在<br>';
		}
	}
}else{
	echo '<font color="#ff0000">'.$goods_sn.'不存在</font><br>';
}




function cmp($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
}


?>

