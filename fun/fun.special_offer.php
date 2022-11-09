<?
/*
+----------------------------------
* Clearance
+----------------------------------
*/
$Tpl->caching = false;        //不使用缓存
if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id))
{
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/class.page.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	if(!IS_LOCAL)open_cdn_cache(); //开启页面CDN缓存
	$is_login_str = 'category_login_html';
	if (!empty($_COOKIE['WEBF-dan_num'])) $is_login_str = 'category_html';
	$Arr['left_catArr']  = read_static_cache($is_login_str,2);
	$act = empty($_GET['a'])?'':$_GET['a'];
	$page = isset($_GET['page'])   && intval($_GET['page'])  > 0 ? intval($_GET['page'])  : 1;
	$is_login_str = ' and g.is_login = 0 ';
    $order = ' sort_order,goods_id desc ';
	$WHRSTR = " g.is_on_sale = 1 AND g.is_delete = 0 and g.is_alone_sale = 1 and g.goods_number > 0  and g.gifts_id=0 and g.goods_thumb <>'' ";
	//discount center
	$now=gmtime();
	if($act == 'discount_center'){
    	$activity=" and gifts_id=0 and promote_price<>0.99 and g.promote_start_date< $now and g.promote_end_date >$now";
    	$ext='';
		$goods_discount = category_get_goods($WHRSTR,100,1,'rand()',$ext,$activity,40);
		$Arr['goods_discount'] = $goods_discount;
		$Arr['nav'] = $_LANG["Discount_Center"];//'Discount center';
	}elseif ($act == 'clearance_sale'){
	    $activity=" and gifts_id=0 and g.goods_number<100 ";
	    $ext ='';
	    $goods_clearance = category_get_goods($WHRSTR,100,1,'sort_order',$ext,$activity,15);
		$Arr['goods_clearance'] = $goods_clearance;
		$Arr['nav'] = $_LANG["Clearance_Sale"];//'Clearance sale';
	}else {
    	//a dollar store
	    $activity="  and gifts_id=0 and promote_price=0.99 and g.promote_start_date< $now and g.promote_end_date >$now";
	    $ext = '';
	    $goods_one_dollar = category_get_goods($WHRSTR,100,1,'rand()',$ext,$activity);
		$Arr['goods_one_dollar'] = $goods_one_dollar;
		$Arr['nav'] = $_LANG["One_dollar_store"];//'One dollar store';
	}
	$Arr['seo_title'] =$Arr['nav'].'  '.$_CFG['shop_name'];
	$Arr['SpecialOffer_s'] = '_s';
    //clearance
	$Arr['seo_title'] = $_LANG_SEO['one_dollar_store']['title'];
	$Arr['seo_keywords'] = $_LANG_SEO['one_dollar_store']['keywords'];
	$Arr['seo_description'] = $_LANG_SEO['one_dollar_store']['description'];    	
    /* 查询订单 */
	$Arr['order_list']  =  get_index_order();
}

//==================================================华丽分割线==================================================
/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_get_goods($where,$size,$page,$order,$ext,$activity,$limit=100)
{
	global $display, $Arr, $cur_lang, $default_lang;

	if ($order) $order = $order;
    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id,promote_start_date,promote_end_date, g.goods_title, g.goods_name_style,g.goods_name,g.goods_sn,g.cat_id, g.market_price,g.goods_weight,g.is_free_shipping, g.url_title,g.goods_grid, g.goods_number, g.shop_price AS org_price, g.shop_price , g.promote_price, g.goods_type, g.promote_price ,g.promote_start_date, g.promote_end_date, g.goods_brief, g.is_login ' .
           'FROM ' . GOODS . ' AS g ' .
           "WHERE  $where $activity $ext ORDER BY  $order limit $limit";//limit ".(($page - 1) * $size).",$size ";
	if(!empty($_GET['is_test']))echo $sql;
    $res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($res as $row)
    {
    	if($row['promote_price']>0){
    		$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
    	}else {
    		$promote_price =0;
    	}

        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        $arr[$row['goods_id']]['goods_number']     = $row['goods_number'];
        $arr[$row['goods_id']]['goods_title']      = $row['goods_title'];
        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']       = price_format($promote_price?$promote_price:$row['shop_price']);
        $arr[$row['goods_id']]['goods_grid']       = get_image_path($row['goods_id'], $row['goods_grid']);
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
        if(!empty($row['market_price']) && $promote_price>0 && $row['market_price']>0)
        {//echo $row['market_price'];
        	$arr[$row['goods_id']]['discount']         = number_format(($row['market_price']-$promote_price)/$row['market_price']*100,0);
        }
        if(empty($arr[$row['goods_id']]['discount']))$arr[$row['goods_id']]['discount'] =0;
    }
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '". $key ."'";	
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['goods_title']  = $row_lang['goods_title'];
				}
			}
		}			
	}	
    return $arr;
}

function get_order(){
	global $db;

    $sql = "SELECT o.country,o.province,u.goods_id,u.goods_name,g.url_title,o.order_id " .
			" FROM " . ORDERINFO . " AS o, " .
			ODRGOODS. " AS u,  ".
			GOODS. " AS g where u.order_id=o.order_id and g.goods_id=u.goods_id and g.is_login = 0 and g.goods_number>0 and o.order_status  > 0 and  o.order_status  < 9 and g.gifts_id=0 ".
			" Group by o.country,o.province  ORDER BY o.order_id desc ".
			" limit 20";
	$order_list = $db->arrQuery($sql);
	$region_arr = area_list();
	foreach($order_list as $key => $row){
		$order_list[$key]['url_title']  = get_details_link($row['goods_id'],$row['url_title']);
		$order_list[$key]['goods_name'] = sub_str($row['goods_name'], 50);
		$order_list[$key]['country']   =  empty($region_arr[$row['country']])?'':$region_arr[$row['country']]['region_name'];
	}
	return $order_list;
}
?>