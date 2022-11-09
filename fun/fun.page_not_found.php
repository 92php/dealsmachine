<?
/*
+----------------------------------
* 错误页面
+----------------------------------
*/

if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id))
{
	header("HTTP/1.0 404 Not Found");
	header('Status: 404 Not Found');
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');

	$Arr['seo_title'] = 'Page Not Found - '.$_CFG['shop_title'];
	$Arr['seo_keywords'] = 'Page Not Found , '.$_CFG['shop_keywords'];
	$Arr['seo_description'] = 'Page Not Found , '.$_CFG['shop_desc'];

	$is_login_str = 'category_login_html';
	if (!empty($_COOKIE['WEBF-dan_num'])) $is_login_str = 'category_html';
	//$Arr['left_catArr']  = read_static_cache($is_login_str,2);
	$Arr['left_catArr']   = getDynamicTree(0);

	get_cache_best_goods();
}
?>