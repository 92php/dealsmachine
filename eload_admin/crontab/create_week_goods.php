<?php
/**
 * create_week_goods.php          每周一,周四定时任务
 *
 * @author                  fangxin 
 * @date                    2014-01-20 AM
 */
set_time_limit(0);
define('INI_WEB', true);
require('../../lib/global.php');              //引入全局文件
require(LIB_PATH . 'time.fun.php');
require(LIB_PATH . 'class.function.php');
if('create' == $_GET['type']) {
	create_week_file();	
	echo '操作成功!';
}
//print_r(get_recommend_goods_sn(95,3));

//写入缓存文件
function create_week_file() {	
	global $goods_filter;	
	$goods_filter = '1';	
	$data = array(
		'recommended_deals' => get_recommend_deals(),
		'hot_deals' => get_hot_deals(),
		'cat_recommend_products_top'     => get_cat_recommend_products_top(),
		'search_recommend_products_left' => get_search_recommend_product()
	);
	setData($data);	
}

function setData($data) {	
	write_static_cache('week_goods', $data);
}	

//首页一周热卖: hot deals商品
function get_hot_deals() {
	$cat_goods_sn_1 = get_hot_goods_sn(59,1); //手机
	$cat_goods_sn_2 = get_hot_goods_sn(95,1); //平板			
	$cat_goods_sn_3 = get_hot_goods_sn(86,1); //电脑周边			
	$cat_goods_sn_4 = get_hot_goods_sn(1782,1); //消费电子
	$cat_goods_sn_5 = get_hot_goods_sn(1832,1); //移动电源
	$cat_goods_sn_6 = get_hot_goods_sn(93,1); //手机配件
	$cat_goods_sn_7 = get_hot_goods_sn(89,1); //苹果配件
	$cat_goods_sn_8 = get_hot_goods_sn(96,1); //光电
	$cat_goods_sn_9 = get_hot_goods_sn(109,1); //耳机
	$cat_goods_sn_10 = get_hot_goods_sn(91,1); //汽车电子
	$goods_list_sn = '';
	if(!empty($cat_goods_sn_1)) {
		$goods_list_sn .=  $cat_goods_sn_1 . ',';
	}
	if(!empty($cat_goods_sn_2)) {
		$goods_list_sn .= $cat_goods_sn_2 . ',';
	}
	if(!empty($cat_goods_sn_3)) {
		$goods_list_sn .= $cat_goods_sn_3 . ',';
	}
	if(!empty($cat_goods_sn_4)) {
		$goods_list_sn .= $cat_goods_sn_4 . ',';
	}
	if(!empty($cat_goods_sn_5)) {
		$goods_list_sn .= $cat_goods_sn_5 . ',';
	}
	if(!empty($cat_goods_sn_6)) {
		$goods_list_sn .= $cat_goods_sn_6 . ',';
	}
	if(!empty($cat_goods_sn_7)) {
		$goods_list_sn .= $cat_goods_sn_7 . ',';
	}
	if(!empty($cat_goods_sn_8)) {
		$goods_list_sn .= $cat_goods_sn_8 . ',';
	}
	if(!empty($cat_goods_sn_9)) {
		$goods_list_sn .= $cat_goods_sn_9 . ',';
	}
	if(!empty($cat_goods_sn_10)) {
		$goods_list_sn .= $cat_goods_sn_10 . ',';
	}	
	$goods_list_sn = substr($goods_list_sn,0,(strlen($goods_list_sn)-1));	
	$goods_list = get_goods($goods_list_sn, 10, 50);	
	return $goods_list; 
}
		
function get_hot_goods_sn($cat_id=59, $limit=1) {
	global $goods_filter;
	//查从服务器
	$db_slave = get_slave_db();	
	$typeArray =  read_static_cache('category_c_key',2);
	require_once(ROOT_PATH . 'lib/class.function.php');
	$cat_ids = Func::get_category_children_ids($typeArray, $cat_id);
	$cat_ids = substr($cat_ids,0,(strlen($cat_ids)-1));
	if(count($cat_ids) > 5) {
		foreach($cat_ids as $key=>$value) {
			if($key > 6) {
				unset($cat_ids[$key]);
			}
		}
	}
	$goods_list_sn = '';
	if(!empty($cat_ids)) {		
		$sql = "SELECT g.goods_id,g.goods_sn,g.cat_id,gh.hitnum,gh.daytime FROM eload_goods g 
		LEFT JOIN eload_goods_hits gh ON g.goods_id = gh.goods_id 
		WHERE g.is_on_sale = 1 AND g.cat_id IN(".$cat_ids.")
		AND UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -7 DAY)) < gh.daytime 
		AND g.goods_id NOT IN(". $goods_filter .")
		ORDER BY gh.hitnum DESC LIMIT 1";
		$res = $db_slave->arrQuery($sql);
		if($res) {
			$goods_list_sn = "'" . $res[0]['goods_sn'] . "'";
		}				
	}
	/*
	//商品浏览量查询使用从数据库
	$db_slave = get_slave_db();		
	if(!empty($cat_id)) {
		$sql = 'SELECT gh.*,g.goods_id,g.is_on_sale FROM eload_goods_hits gh LEFT JOIN eload_goods g ON gh.goods_id=g.goods_id WHERE top_cat_id = '. $cat_id .' AND g.is_on_sale=1 ORDER BY hitnum DESC LIMIT '. $limit .'';
		$res = $db_slave->selectInfo($sql);	
		if($res) {
			$sql_g = 'SELECT goods_id,goods_sn FROM eload_goods WHERE goods_id = '. $res['goods_id'] .'';
			$res_g = $GLOBALS['db']->selectInfo($sql_g);
			$goods_list_sn = "'" . $res_g['goods_sn'] . "'";
		}	
	}
	*/
	return $goods_list_sn;
} 	

//首页一周推荐：recommended deals
//上架30天，按转化率排序，连续两周不要重复SKU，同款不同色，不要重复，不要赠品。
function get_recommend_deals() {
	$cat_59_goods_sn = get_recommend_goods_sn(59,3); //手机
	$cat_95_goods_sn = get_recommend_goods_sn(95,3); //平板
	$cat_1782_goods_sn = get_recommend_goods_sn(1832,2); //移动电源
	$cat_93_goods_sn = get_recommend_goods_sn(93,1); //手机配件
	$cat_89_goods_sn = get_recommend_goods_sn(89,1); //苹果配件 
	$goods_list_sn = $cat_59_goods_sn;
	if(!empty($cat_95_goods_sn)) {
		$goods_list_sn .= ',' . $cat_95_goods_sn;
	}
	if(!empty($cat_1782_goods_sn)) {
		$goods_list_sn .= ',' . $cat_1782_goods_sn;
	}
	if(!empty($cat_93_goods_sn)) {
		$goods_list_sn .= ',' . $cat_93_goods_sn;
	}
	if(!empty($cat_89_goods_sn)) {
		$goods_list_sn .= ',' . $cat_89_goods_sn;
	}		
	$goods_list = get_goods($goods_list_sn, 10, 50);	
	shuffle($goods_list);
	return $goods_list; 	
}

function get_recommend_goods_sn($cat_id=59, $limit=2) {
	global $goods_filter;
	$typeArray =  read_static_cache('category_c_key',2);
	require_once(ROOT_PATH . 'lib/class.function.php');
	$cat_ids = Func::get_category_children_ids($typeArray, $cat_id);	
	$cat_ids = substr($cat_ids,0,(strlen($cat_ids)-1));
	$goods_list_sn = '';		
	if(!empty($cat_ids)) {
		$sql = "SELECT g.goods_id,g.cat_id,LEFT(g.goods_sn,7) AS goods_sn_c,g.goods_sn,g.goods_number,g.is_on_sale,g.add_time,gcr.conversion_rate 
		FROM eload_goods g left join eload_category gc ON g.cat_id = gc.cat_id 
		LEFT JOIN eload_goods_conversion_rate gcr ON g.goods_id = gcr.goods_id 
		WHERE UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -30 DAY)) < g.add_time
		AND g.is_on_sale = 1 AND g.goods_number > 0
		AND g.cat_id IN(".$cat_ids.") 
		AND g.goods_id NOT IN(".$goods_filter.")
		GROUP BY goods_sn_c
		ORDER BY gcr.conversion_rate DESC LIMIT ". $limit ."";
		$res = $GLOBALS['db']->arrQuery($sql);
		if(is_array($res)) {
			foreach($res as $key=>$value) {
				$goods_list_sn .= "'". $value['goods_sn'] . "',";
			}
			$goods_list_sn = substr($goods_list_sn,0,(strlen($goods_list_sn)-1));
		}
	}
	return $goods_list_sn;
} 

//一级分类列表页顶部推荐商品
function get_cat_recommend_products_top() {
	global $goods_filter;
	$typeAllArray =  read_static_cache('category_c_key',2);
	$typeArray =  read_static_cache('category_c_key',2);
	foreach($typeArray as $key=>$value) {
		if($value['parent_id'] > 0) {
			unset($typeArray[$key]);
		}
	}
	foreach($typeArray as $key=>$value) {
		$cat_id = $value['cat_id'];
		$cat_ids = Func::get_category_children_ids($typeAllArray, $cat_id);	
		$cat_ids = substr($cat_ids,0,(strlen($cat_ids)-1));	
		//上架一个月，两周内销售数量排名前5的选2个。
		$sql = "SELECT g.goods_id,g.cat_id,LEFT(g.goods_sn,7) AS goods_sn_c,g.goods_sn,g.add_time,g.week2sale
		FROM eload_goods g left join eload_category gc ON g.cat_id = gc.cat_id 
		WHERE UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -30 DAY)) < g.add_time
		AND g.is_on_sale = 1  
		AND g.cat_id IN(".$cat_ids.")
		AND g.cat_id NOT IN(435,1822)
		AND g.goods_id NOT IN(". $goods_filter .")
		GROUP BY goods_sn_c
		ORDER BY g.week2sale DESC LIMIT 2";
		$res_1 = $GLOBALS['db']->arrQuery($sql);
		$goods_id = '';
		if($res_1) {
			foreach($res_1 as $key=>$value) {
				$goods_id .= $value['goods_id'] . ',';
			}
			$goods_id = substr($goods_id,0,(strlen($goods_id)-1));
		}
		//上架时间不限，两周内销售数量排名前5的选2个。	
		$sql = "SELECT g.goods_id,g.cat_id,LEFT(g.goods_sn,7) AS goods_sn_c,g.goods_sn,g.add_time,g.week2sale
		FROM eload_goods g left join eload_category gc ON g.cat_id = gc.cat_id 
		WHERE g.is_on_sale = 1  
		AND g.cat_id IN(".$cat_ids.")
		AND goods_id NOT IN(". $goods_filter .")
		";
		if(!empty($goods_id)) {
			$sql .= "AND g.goods_id NOT IN(". $goods_id .") AND g.cat_id NOT IN(435)";
		}
		$sql .= " GROUP BY goods_sn_c";
		$sql .= " ORDER BY g.week2sale DESC LIMIT 2";
		$res_2 = $GLOBALS['db']->arrQuery($sql);
		$res = array_merge($res_1,$res_2);
		$goods_list_sn = '';
		foreach($res as $key=>$value) {			
			$goods_list_sn .= "'" . $value['goods_sn'] . "',";		
		}
		$goods_list_sn = substr($goods_list_sn,0,(strlen($goods_list_sn)-1));
		$goods_list[$cat_id] = get_goods($goods_list_sn, 5, 50);	
		
	}
	return $goods_list;
}

//搜索页推荐商品
function get_search_recommend_product() {
	$cat_59_goods_sn = get_search_recommend_product_sn(59,2);
	$cat_95_goods_sn = get_search_recommend_product_sn(95,2);
	$cat_1782_goods_sn = get_search_recommend_product_sn(1782,1);
	$goods_list_sn = $cat_59_goods_sn;
	if(!empty($cat_95_goods_sn)) {
		$goods_list_sn .= ',' . $cat_95_goods_sn;
	}
	if(!empty($cat_1782_goods_sn)) {
		$goods_list_sn .= ',' . $cat_1782_goods_sn;
	}
	$goods_list = get_goods($goods_list_sn, 5, 50);	
	return $goods_list; 		
}

function get_search_recommend_product_sn($cat_id,$limit) {
	global $goods_filter;
	$typeArray =  read_static_cache('category_c_key',2);
	require_once(ROOT_PATH . 'lib/class.function.php');
	$cat_ids = Func::get_category_children_ids($typeArray, $cat_id);	
	$cat_ids = substr($cat_ids,0,(strlen($cat_ids)-1));
	$sql = "SELECT g.goods_id,g.cat_id,g.goods_sn,g.add_time,gcr.conversion_rate 
	FROM eload_goods g left join eload_category gc ON g.cat_id = gc.cat_id 
	LEFT JOIN eload_goods_conversion_rate gcr ON g.goods_id = gcr.goods_id 
	WHERE UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -2 DAY)) < g.add_time
	AND g.is_on_sale = 1  
	AND g.cat_id IN(".$cat_ids.")
	AND g.goods_id NOT IN(". $goods_filter .")
	ORDER BY gcr.conversion_rate DESC LIMIT ". $limit ."";
	$res = $GLOBALS['db']->arrQuery($sql);
	$goods_list_sn = '';
	if(is_array($res)) {
		foreach($res as $key=>$value) {
			$goods_list_sn .= "'". $value['goods_sn'] . "',";
		}
		$goods_list_sn = substr($goods_list_sn,0,(strlen($goods_list_sn)-1));
	}
	return $goods_list_sn;	
}

//查询商品信息
function get_goods($goods_list_sn, $limit=1, $len=20) {
	global $cur_lang, $default_lang;
	$goods_arr = explode(",", $goods_list_sn);
	foreach($goods_arr as $key=>$value) {
		calculate_promote_price($value);
	}	
	$where = '';
	if(!empty($goods_list_sn)) {
		$where  = " AND goods_sn IN(".$goods_list_sn.")";
	}
	$sql = 'SELECT g.goods_id,g.goods_sn,cat_id, goods_title,goods_name_style,is_free_shipping,shop_price,g.market_price,goods_thumb,goods_grid,sort_order,promote_price,promote_start_date,promote_end_date,url_title,is_superstar ' .
	   ' FROM ' . GOODS . ' AS g left join ' .GOODS_STATE.' s on g.goods_id=s.goods_id'.
	   ' WHERE is_on_sale = 1 AND is_alone_sale = 1  and is_login =0  AND is_delete = 0  '.$where.' '.
	   " LIMIT $limit";  
	$goods_res = $GLOBALS['db']->arrQuery($sql);
	$arr = array();
	foreach ($goods_res as $row) 
	{
		if ($row['promote_price'] > 0)
		{
			$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
		}
		else
		{
			$promote_price = 0;
		}
		$arr[$row['goods_id']]['goods_title']      = sub_str($row['goods_title'],$len);
		$arr[$row['goods_id']]['goods_full_title'] = $row['goods_title'];
		$arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
		$arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],$len);
		$arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb']?$row['goods_thumb']:$row['goods_grid'], true);
		$goods_grid = get_image_path($row['goods_id'], $row['goods_grid']?$row['goods_grid']:$row['goods_img'], true);
		if('/temp/skin1/images/no_pic.jpg' == $goods_grid) {
			$goods_grid = get_image_path($row['goods_id'], $row['goods_thumb']?$row['goods_thumb']:$row['goods_grid'], true);
		}
		$arr[$row['goods_id']]['goods_grid']       = $goods_grid;
		$arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
		$arr[$row['goods_id']]['shop_price']       = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
		$arr[$row['goods_id']]['market_price']     = $row['shop_price'];
		$arr[$row['goods_id']]['is_superstar']     = $row['is_superstar'];
		//$arr[$row['goods_id']]['promote_zhekou']   = $row['promote_price'] > 0 && $row['market_price'] > 0 ? round(($row['market_price'] - $row['promote_price']) / $row['market_price'], 2) * 100 : '';		
		$arr[$row['goods_id']]['promote_zhekou']   = 0; 
		$arr[$row['goods_id']]['goods_sn']         = $row['goods_sn'];
	}
	// 多语言
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$key'";	
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['goods_title'] = $row_lang['goods_title'];
					$arr[$key]['short_name']  = sub_str($row_lang['goods_title'], $len);
				}
			}
		}			
	}
	return $arr;	
}

/*
 * 自动计算促销价，并更新数据库
 * 低于5美元，利润率1.4
 * 高于5美元，利润率1.2
 * 汇率 5.83
 */
function calculate_promote_price($goods_sn) {
	$new_time  =  local_date('Y-m-d', gmtime() - 0 * 3600);
	$promote_start_date = local_strtotime($new_time." 0:0:0");	//开始时间
	$promote_end_date = $promote_start_date + 24*60*60*30;	//结束时间,促销时间一个月	
	if(!empty($goods_sn)) {
		$sql = 'SELECT goods_id,chuhuo_price,shop_price,is_free_shipping,goods_weight FROM eload_goods WHERE goods_sn = '. $goods_sn .'';
		$res = $GLOBALS['db']->selectinfo($sql);
		if($res) {
			if($res['shop_price'] > 5) {
				$promote_lv = 1.2;
			} else {
				$promote_lv = 1.4;
			}
			//促销价＝出货价*利润率/汇率+运费
			$hl = 5.83;
			$free_shipping = 0;
			if($res['is_free_shipping']) {
				$free_shipping = ($res['goods_weight']*95)/$hl; 
			}
			$promote_price = price_format(format_price(($res['chuhuo_price']*$promote_lv)/$hl+$free_shipping));	
			$sql = "UPDATE eload_goods SET promote_price=". $promote_price .", promote_start_date=". $promote_start_date .", promote_end_date=". $promote_end_date .", promote_lv=". $promote_lv .", is_promote=1 WHERE goods_sn=". $goods_sn ."";
			$GLOBALS['db']->query($sql);
		}
	}
}

/**
 * 根据规则修改价格
 *
 * @author        fangxin
 * @date          2013-10-08 AM
 * @last modify   2013-10-08 AM
 * @param mixed   $shop_price    商品价格
 * @return int 
 *
 * 规则如下：
 * 新价格=原售价取整 + x
 *	1）原售价在10.00-29.99美元时
 *	   a）如果原售价取整后余数是0-0.49, x=0.49
 *	   b）如果原售价取整后余数是0.5-0.99 x=0.99
 *	2）原售价在30.00美元以上时 x=0.99 
 *  3) 原售价小于10.00美元不变
 *
 */
function format_price($price) {
	if(!empty($price)) {
		$price_suffix_array = explode(".", $price);
		$price_suffix = $price_suffix_array[1];
		if(strlen($price_suffix) == 1) {
			$price_suffix = $price_suffix * 10;
		}
		if($price < 10.00) {
			$price = $price_suffix_array[0] . '.' . $price_suffix;
			return $price;
		}elseif($price >= 10.00 and $price <= 30.00) {
			if($price_suffix < 50 and $price_suffix >= 0) {
				$price_suffix = 49;
			}elseif($price_suffix < 99 and $price_suffix >= 50) {
				$price_suffix = 99;
			}
		}elseif($price > 30.00) {
			$price_suffix = 99;
		}
		$price = $price_suffix_array[0] . '.' . $price_suffix;
	}
	return $price;
}

