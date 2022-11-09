<?
/*
+----------------------------------
* 相册
+----------------------------------
*/
if (!defined('INI_WEB')){die('访问拒绝');}
$goods_id = isset($_GET['id'])  ? intval($_GET['id']) : 0;
if(!$goods_id){
   header("Location: /$cur_lang_url"."m-page_not_found.htm");
   exit;
}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
$sql   = "select goods_title,url_title,original_img,goods_thumb from ".GOODS." where goods_id = '".$goods_id."'";
$goods = $db->selectinfo($sql);
$goods['url_title']    = get_details_link($goods_id,$goods['url_title']);
$goods['original_img'] = get_image_path($goods_id,$goods['original_img']);
$goods['goods_thumb']  = get_image_path($goods_id,$goods['goods_thumb']);
$Arr['pictures']       = get_goods_gallery($goods_id); 	
$Arr['seo_title']     = $_CFG['shop_name'].': Photo Gallery - '.$goods['goods_title'];
$Arr['goods']          = $goods;	
$big_img               = "";
foreach($Arr['pictures'] as $val){
	if ($big_img) $big_img .= ',';
	$big_img               .= '"/'.$val['img_original'].'"';
}
$Arr['big_img']        = $big_img;
$Arr['shop_name']      = $_CFG['shop_name'];
?>  