<?php
/**
 * reget_img.php     重新获取图片，中转服务器上有，但网站上没有
 * 
 * @author           mashanling(msl-138@163.com)
 * @date             2011-10-13
 * @last modify      2011-11-30 by mashanling
 * 
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once('../../lib/is_loging.php');
require_once('../../lib/time.fun.php');
require_once('../../lib/cls_image.php');
require_once('../../lib/syn_public_fun.php');
require_once('../../lib/param.class.php');

$sql = 'SELECT * FROM ' . GGALLERY . ' WHERE goods_id=' . Param::get('goods_id');
$arr = $db->arrQuery($sql);
$image = new cls_image();
$temp = array('img_url', 'thumb_url', 'img_original');

$opts = array( 
		'http'=>array( 
		'method'=>'GET', 
		'timeout'=>60, 
		) 
	); 
$context = stream_context_create($opts);

foreach ($arr as $value) {
    
    foreach ($temp as $v) {
            $img_syn_data = fopen_url('http://usimg.davismicro.com.cn/' . $value[$v], false, $context);
            $filename     = ROOT_PATH . $value[$v];
            file_put_contents($filename, $img_syn_data);
            
            if (!in_array(check_file_type($filename), array('jpg'))) {
                //unlink($filename);
                exit(__FILE__ . __LINE__ . "图片{$filename}同步失败");
            }
        }
        
        //加水印
        if (empty($_GET['not_add_water'])) {
            $result = $image->add_watermark(ROOT_PATH . $value['img_original'], '', $GLOBALS['_CFG']['watermark'], '', $GLOBALS['_CFG']['watermark_alpha']);
            
            if (!$result) {
                exit(__FILE__ . __LINE__ . '原图' . ROOT_PATH . $value['img_original'] . '加水印失败.' . $image->error_msg);
            }
        }
        
}
/*EXIT;
$img_syn_data = fopen_url($import_url . $temp_str[$value], false, $context);
            $filename     = ROOT_PATH . $temp_str[$value];
            file_put_contents($filename, $img_syn_data);*/
?>