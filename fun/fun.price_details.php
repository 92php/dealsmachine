<?php
/**
 * 商品分类
*/

if (!defined('INI_WEB')){die('访问拒绝');}

/* 获得请求的分类 ID */
$cat_id = 0;
$cat_id = empty($_GET['id'])?0:intval($_GET['id']);  
if ($cat_id != 0){$cat_id = intval($cat_id);
}else{
   header("Location: /$cur_lang_url"."m-page_not_found.htm");
   exit;
}
$price_max = isset($_GET['price_max']) && floatval($_GET['price_max']) > 0 ? floatval($_GET['price_max']) : 0;
$price_min = isset($_GET['price_min']) && floatval($_GET['price_min']) > 0 ? floatval($_GET['price_min']) : 0;
$page = empty($_GET['page'])?1:intval($_GET['page']);

/* 页面的缓存ID */
$my_cache_id = sprintf('%X', crc32($cat_id . '-' . $page . '-' .$cur_lang .'-'. $price_max . '-' .$price_min ));

if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id))
{

	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/class.page.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	
	
	$children = get_children($cat_id);
	
	
	//导航
	if($cur_lang != $default_lang){
		$typeArray =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$typeArray =  read_static_cache('category_c_key',2);
	}	
	
	if(empty($typeArray[$cat_id])){
	   header("Location: /$cur_lang_url"."m-page_not_found.htm");
	   exit;
	 }
	
	$nav_title = getNavTitle($typeArray,$typeArray[$cat_id]["parent_id"]);
	$thisurl = creat_nav_url($typeArray[$cat_id]["url_title"],$cat_id);
	$cat_name = $typeArray[$cat_id]["cat_name"];
	$cat_url  =  $thisurl;
	$nav_title = $nav_title.' &raquo; <a href="'.$thisurl.'" title = "'.$typeArray[$cat_id]["cat_name"].'">'.$cat_name.'</a>';
	$Arr['nav_title']  =  $nav_title;
	
	
	
	
	$size = 100;
	$ext = '';
	$order = ' g.shop_price ';
	
	//同一商品不同规格只取一个商品显示
	$ext .= " GROUP BY IF( g.is_new_sn = 1, substr(g.goods_sn,1,7) , g.goods_id) ";
	
	$count = get_cagtegory_goods_price_count($children, $price_min, $price_max, $ext);
	
	$max_page = ($count> 0) ? ceil($count / $size) : 1;
	if ($page > $max_page)
	{
		$page = $max_page;
	}
	
	$firstnum = ($page - 1) * $size + 1;
	if ($count == 0) $firstnum = 0;
	$lastnum = $page * $size;
	if ($lastnum > $count) $lastnum = $count;
	
	$goodslist = category_get_goods($children, $price_min, $price_max, $ext, $size, $page,  $order);
	//价格导航
	
	$same_cat = array();
	$Price_Arr= array();
	foreach($typeArray as $s => $v){
		if($v['parent_id'] == $typeArray[$cat_id]["parent_id"]){
			$same_cat[$s]['cat_name'] = $v['cat_name'];
			$same_cat[$s]['url'] = '/'.title_to_url($v['cat_name']).'-'.$cat_id.'-'.$price_min.'-'.$price_max.'-Wholesale.html';
		}
	}
	
	$Arr['same_cat'] = $same_cat;
	
	
	$Price_Arr = get_price_nav_arr($children,$cat_id,$typeArray);
	$Arr['Price_Arr'] = $Price_Arr;
	
	
	
	
	
	$price_str = '';
	if ($price_min!=0) $price_str .= ' $'.$price_min;
	if ($price_max!=0) $price_str .= ' $'.$price_max;
	
	$is_sub_cat = 0; //等于0 为子类
	
	
	//seo标题
	$cat_name = $typeArray[$cat_id]["cat_name"];
	if($price_str!=''){
		$seo_title   = "China $price_str $cat_name Wholesale - ".$_CFG['shop_name'];  //大类
	}else{
		if($typeArray[$cat_id]["parent_id"] == 0){
			$seo_title   = 'China '.$cat_name.' Wholesale - '.$_CFG['shop_name'];  //大类
			$is_sub_cat = 1;
		}else{
			$bseo_title  = $typeArray[$typeArray[$cat_id]["parent_id"]]["cat_name"];
			$seo_title   = $bseo_title.' Wholesale China '.$cat_name.' Suppliers  - '.$_CFG['shop_name'];   //小类
		}
	}
	
	$Arr['is_sub_cat'] = $is_sub_cat;
	
	//seo关键字
	$seo_keyword = $typeArray[$cat_id]["keywords"];
	
	if($price_str!=''){
		$seo_keyword   = 'China '.$price_str.' '.$cat_name.' Wholesale ';  
	}else{
	
		$geshu  = 0;
		if ($seo_keyword == '') {
			
			$flag = false;
			foreach ($typeArray as $key => $val){
				if($typeArray[$key]["parent_id"] == $cat_id ){
					$flag = true;
				}
			}
			
			if($flag) {
				$cur_cat_id = $cat_id;
				$loop = 10;
			}else{
				$cur_cat_id = $typeArray[$cat_id]["parent_id"];
				$loop = 5;
			}
			
			foreach ($typeArray as $key => $val){
				if($typeArray[$key]["parent_id"] == $cur_cat_id && $geshu <$loop){
					$seo_keyword .= $typeArray[$key]['cat_name'].' Wholesale,';
					$geshu ++;
					if ($geshu >=$loop) break;
				}
			}
		}
	
	}
	$seo_desc    = $typeArray[$cat_id]["cat_desc"];
	
	if ($seo_desc == '') {
		if($price_str!=''){
			$seo_desc   = $_CFG['shop_name'].", who provide all kinds of high quality and low price $price_str $cat_name products Wholesale";  //价格描述
		}else{
			if($flag) {
				$seo_desc ="We are China $cat_name  wholesaler, who provide all kinds of high quality and low price  $cat_name  products.China ".$_CFG['shop_name']; //大类描述
			}else{
				$seo_desc ="China $cat_name Wholesale, Best quality and factory price $cat_name products Wholesale ".$_CFG['shop_name'];  //小类描述
			}
		}
	}
	
	$cat_url = creat_nav_url($typeArray[$cat_id]["url_title"],$cat_id);
	if($is_sub_cat == '0' && $price_str=='') {
		$parent_id = $typeArray[$cat_id]["parent_id"];
		$cat_parent_name = $typeArray[$parent_id]["cat_name"];
		$cat_parent_url  = creat_nav_url($typeArray[$parent_id]["url_title"],$parent_id);
		$Arr['cat_parent_name']  =  $cat_parent_name;
		$Arr['cat_parent_url']  =  $cat_parent_url;
	}
	
	$Arr['cat_name']   =  $cat_name;
	$Arr['cat_url']   =  $cat_url;
	$Arr['seo_title'] =  $seo_title;
	$Arr['seo_keywords']   =  $seo_keyword;
	$Arr['seo_description']   =  $seo_desc;
	$Arr['goods_list'] =  $goodslist;
	$Arr['total']      =  $count;
	$Arr['firstnum']   =  $firstnum;
	$Arr['lastnum']    =  $lastnum;
	$Arr['price_min']    =  $price_min;
	$Arr['price_max']    =  $price_max;
	$Arr['shop_name']  =  $_CFG['shop_name'];
	
	
	$page=new page(array('total'=>$count,'perpage'=>$size)); 
	$Arr["pagestr"]  = $page->show(5);
}


function category_get_goods($children, $min, $max, $ext, $size, $page,  $order)
{
	global $display;
	$where = "g.is_on_sale = 1  AND ".
			"g.is_delete = 0 AND ($children)  and g.is_alone_sale = 1 ";

	if ($min > 0)
	{
		$where .= " AND g.shop_price >= $min ";
	}

	if ($max > 0)
	{
		$where .= " AND g.shop_price <= $max ";
	}

	/* 获得商品列表 */
	$sql = 'SELECT g.goods_id, g.goods_title, g.goods_name_style,g.cat_id, g.market_price,g.goods_weight,g.is_free_shipping,g.goods_grid, g.is_new, g.is_best,g.url_title, g.is_hot, g.shop_price AS org_price, ' .
				" g.shop_price , g.promote_price, g.goods_type, g.promote_price ," .
				'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
			'FROM ' . GOODS . ' AS g ' .			
			"WHERE  $where  $ext ORDER BY $order limit ".(($page - 1) * $size).",$size ";
	$res = $GLOBALS['db']->arrQuery($sql);
	$arr = array();
	 foreach ($res as $row)
	{
		if ($row['promote_price'] > 0)
		{
			$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
		}
		else
		{
			$promote_price = 0;
		}

		/* 处理商品水印图片 */
		$watermark_img = '';

		if ($promote_price != 0)
		{
			$watermark_img = "watermark_promote_small";
		}
		elseif ($row['is_new'] != 0)
		{
			$watermark_img = "watermark_new_small";
		}
		elseif ($row['is_best'] != 0)
		{
			$watermark_img = "watermark_best_small";
		}
		elseif ($row['is_hot'] != 0)
		{
			$watermark_img = 'watermark_hot_small';
		}

		if ($watermark_img != '')
		{
			$arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
		}

		$arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
		if($display == 'grid')
		{
			$arr[$row['goods_id']]['goods_title']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];
		}
		else
		{
			$arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
		}
		$arr[$row['goods_id']]['name']             = $row['goods_title'];
		$arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
		$arr[$row['goods_id']]['goods_brief']  = sub_str($row['goods_brief'],350);
		$arr[$row['goods_id']]['goods_weight']     = formated_weight($row['goods_weight']);
		$arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
		$arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
		$arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
		$arr[$row['goods_id']]['type']             = $row['goods_type'];
		$arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
		$arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img']);
		$arr[$row['goods_id']]['goods_grid']       = get_image_path($row['goods_id'], $row['goods_grid']);
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['saveprice']        = price_format($row['market_price'] - $row['shop_price']);
		$arr[$row['goods_id']]['saveperce']        = ($row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['shop_price'])/$row['market_price'])*100;
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
		
		//$arr[$row['goods_id']]['url']            = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_title']);
	}
	return $arr;
}

/**
 * 获得分类下的商品总数
 *
 * @access  public
 * @param   string     $cat_id
 * @return  integer
 */
function get_cagtegory_goods_price_count($children, $min = 0, $max = 0, $ext='')
{
    $where  = "g.is_on_sale = 1  AND g.is_delete = 0 AND ($children  OR " . get_extension_goods($children) . " )  and g.is_alone_sale = 1  ";


    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }
    
    /* 返回商品总数 */
    $sql = 'SELECT g.goods_id FROM ' . GOODS . " AS g WHERE $where $ext";
    $goods_id_array = $GLOBALS['db']->getCol($sql);
	$count = $GLOBALS['db']->numRows();
	return $count;
    //return $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . GOODS . " AS g WHERE $where $ext");
}








?>
