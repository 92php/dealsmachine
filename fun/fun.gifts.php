<?php
/**
 * 商品分类
*/
if (!defined('INI_WEB')){die('访问拒绝');}
/* 获得请求的分类 ID */
require_once(ROOT_PATH . 'config/config.php');
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once('lib/class.page.php');
require_once('lib/lib.f.goods.php');	
$gifts = gift_list();
$Arr['gifts'] = $gifts;
$Arr['seo_title'] = 'Free gift';	
/**
 * 赠品
 */
function gift_list(){
	global $db, $cur_lang, $default_lang;
	$gifts = read_static_cache('gifts_c_key',2);  //赠品类别
	$sql = "select g.goods_id,goods_img, gifts_id,g.goods_title, g.goods_thumb,g.goods_number,g.url_title, g.goods_grid,g.cat_id, g.shop_price AS org_price, ".
	     	"g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date " .
	            ' FROM '.GOODS.' g  where g.gifts_id >0 and  g.is_on_sale = 1 and g.is_login = 0 and g.is_alone_sale = 1 and g.is_delete = 0 ';
	$res = $db->arrQuery($sql);	
	foreach ($res as $k=>$row){
	    	$res[$k]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img'], true);
	    	$res[$k]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
	    	$res[$k]['goods_grid'] = get_image_path($row['goods_id'], $row['goods_grid'], true);
	    	$res[$k]['shop_price'] = price_format($row['shop_price']);
	    	$res[$k]['url_title'] = get_details_link($row['goods_id']);
	    	if(!empty($gifts[$row['gifts_id']]))$gifts[$row['gifts_id']]['gifts_goods'][] = $res[$k];
	}
	foreach ($gifts as $k=>$v){
		if(empty($gifts[$k]['gifts_goods'])){
			unset($gifts[$k]);
		}
	}
    return $gifts;
}
?>
