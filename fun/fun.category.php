<?php
/**
 * 商品分类
*/
if (!defined('INI_WEB')){die('访问拒绝');}
require_once(ROOT_PATH . 'lib/param.class.php');
require_once(ROOT_PATH . 'config/config.php');
require_once(ROOT_PATH . 'lib/sphinxapi.php');
require_once(ROOT_PATH . 'lib/class.function.php');
require_once('lib/lib.f.goods.php');
require_once(ROOT_PATH . 'fun/fun.global.php');
global $cur_lang, $default_lang;
$cat_id = 0;
$cat_id = empty($_GET['id'])?0:intval($_GET['id']);
$cat_url = $_SERVER['REQUEST_URI'];
cat_redirected(str_replace("/", "", $cat_url)); //分类301跳转
$default_display_type = 'g';                    // 排序、显示方式以及类型
$cookie_time          = time() + 3600 * 2;      //cookie保存时间
/* 初始化分页信息 */
$page        = isset($_GET['page'])   && intval($_GET['page'])  > 0 ? intval($_GET['page'])  : 1;
$price_max   = isset($_GET['price_max']) && floatval($_GET['price_max']) > 0 ? floatval($_GET['price_max']) : 0;
$price_min   = isset($_GET['price_min']) && floatval($_GET['price_min']) > 0 ? floatval($_GET['price_min']) : 0;
$filter_attr = empty($_GET['filter_attr']) ? '' : trim($_GET['filter_attr']);
$price_max   = 0;	//查找价格区间最大价格
$price_min   = 0;	//查找价格区间最小价格
$price_num   = 0;   //查找第几个价格区间
//价格区间 fangxin 2013/08/13
$price_num = empty($_GET['price_num'])?0:intval($_GET['price_num']);		//查找第几个价格区间
$Arr['price_num'] = $price_num;		//查找第几个价格区间
if($price_num && empty($price_max) && empty($price_max)){
    $price_array = get_max_price($cat_id,$price_num);
    if(!empty($price_array)){
        $price_max = $price_array['price_max'];
        $price_min = $price_array['price_min'];
    }
}
if(!IS_LOCAL)open_cdn_cache(); //开启页面CDN缓存
$search_goods_attr = empty($_GET['attr']) ? '' : trim($_GET['attr']);   //属性查找
//商品属性查找参数处理
$search_info = array('search_split'=>array(),'search_goods_attr_key'=>array(),'search_goods_attr'=>array());			//查找商品属性 array();
if($search_goods_attr)
{
	$search_info['search_split'] = explode('~',$search_goods_attr);
	foreach ($search_info['search_split'] as $key=>$value)
	{
		str_replace("+"," ",$value);
		list($attr_brief,$attr_value) = explode("_",$value);
		$search_info['search_goods_attr'][$attr_brief][] = $attr_value;
		$search_info['search_goods_attr_key'][$attr_brief][] = $value;
	}
}
$is_24h_ship      = Param::get('24h_ship');    //显示方式
$is_24h_ship      = !empty($is_24h_ship) ? $is_24h_ship : (isset($_COOKIE['is_24h_ship']) ? $_COOKIE['is_24h_ship'] : 'f');
$freeship         = !empty($_GET['freeship'])?$_GET['freeship']:'f';    //显示方式
$display          = Param::get('display');    //显示方式
$display          = !empty($display) ? $display : (isset($_COOKIE['layout']) ? $_COOKIE['layout'] : $default_display_type);
$display          = in_array($display, array('l', 'g')) ? $display : $default_display_type;
$Arr['24h_ship']  = $is_24h_ship;
if(!empty($_GET['display'])){
	//setcookie('layout', $display, $cookie_time,'/',COOKIESDIAMON);  //保存排列方式 列表、网格
}
$listper  = '20|40|60';
$gridper  = '32|48|72';
$layoutpage["l"]  = explode('|',$listper);
$layoutpage["g"]  = explode('|',$gridper);
$page             = Param::get('page', 'int');    //当前页
$page             = $page > 0 ? $page : 1;
$newpage          = empty($_GET['newpage'])?'0':$_GET['newpage'];
if($newpage) {
	$page         = $newpage;
	$_GET['page'] = $page;
}
$page_size  = Param::get('page_size', 'int');
$page_size  = $page_size > 0 ? $page_size : (isset($_COOKIE['page_num']) ? $_COOKIE['page_num'] : $layoutpage[$display][1]);
$page_size  = $page_size > 0 ? intval($page_size) : 1;
$total      = SPH_MAX_MATCHES;              //限制显示数
$pages      = ceil($total / $page_size);    //最大页数
$page       = $page > $pages ? $pages : $page;
$offset     = ($page - 1) * $page_size;     //偏移量
$_CFG['order'] = array (
    'new'        => '@id DESC',                     //对应前台 New Arrival
    'hot'        => 'is_hot desc,week2sale DESC',   //对应前台 Hot
    'reviews'    => 'reviews DESC',                 //对应前 Reviews
    'lowprice'   => 'shop_price ASC',               //对应前 Lowest Price
    'highprice'  => 'shop_price desc',              //对应前 high Price first
    'conversion'  => 'conversion_rate2 DESC,week2sale DESC,@id DESC'  //转化率 by mashanling on 2012-09-13 16:46:22
);
$display = (isset($_GET['display']) && in_array(trim(strtolower($_GET['display'])),array("l","g")))?trim($_GET['display']):(isset($_COOKIE['layout'])? $_COOKIE['layout']:$default_display_type);
$sortby      = !empty($_GET['sortby'])?$_GET['sortby']:(!empty($_COOKIE['porder'])?$_COOKIE['porder']:(($cat_id==1779)?'hot':'conversion'));//排序KEY值
$sortby      = !empty($_CFG['order'][$sortby])?$sortby:'conversion'; //检查是否存在排序；
$Arr['odr']  = $sortby;
$order       = $_CFG['order'][$sortby];
$size        = (!empty($_GET['page_size']) && intval($_GET['page_size']) > 0) ? intval($_GET['page_size']) :((isset($_COOKIE['page_num']) && intval($_COOKIE['page_num'])!=0) ? (isset($_GET['display'])?$layoutpage[$display][1]:intval($_COOKIE['page_num'])): $layoutpage[$display][1]);
/* 页面的缓存ID */
$my_cache_id = sprintf('%X', crc32($cat_id . '-' . $display . '-' . $sortby  .'-' . $page . '-' . $size .'-' .$cur_lang .'-'. $price_max . '-' .$price_min . '-' .$search_goods_attr));
$typeArray =  read_static_cache('category_c_key',2);
if($cur_lang != $default_lang) {
	$typeArray =  read_static_cache($cur_lang . '_category_c_key',2);
}
$cat_arr = $typeArray;
//如果这一类需要登陆
$cl = new SphinxClient();    //实例化sphinx
$cl->SetConnectTimeout(2);
$cl->SetServer(SPH_HOST,SPH_PORT);    //链接sphinx
if (!empty($typeArray[$cat_id]['is_login'])&&!$typeArray[$cat_id]['is_show_seo']){
	if(empty($blang) && empty($_COOKIE['WEBF-dan_num'])){
	   redirect_url();
	   exit;
	}else{
		if(stripos($typeArray[$cat_id]['clang'],$blang)!==false && empty($_COOKIE['WEBF-dan_num'])){
			redirect_url();
		    exit;
		}
	}
}

if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id))
{
	require_once(ROOT_PATH . 'fun/fun.public.php');
	//require_once('lib/class.page.php');
	require_once(ROOT_PATH . 'lib/class.page_new.php');
	require_once('lib/lib.f.goods.php');
	$Arr['left_new_arrivals'] = get_news_arrivals($cat_id);
	$children = get_children($cat_id);
	$cat = get_cat_info($cat_id);   // 获得分类的相关信息
	if (!$cat){
		redirect_url();
		exit;
	}
	$Arr['left_catArr_top'] = getDynamicTreeTop(0, 1); //fangxin 2013/08/21
	$Arr['filepath']        = ARTICLE_DIR;
	$Arr['goods_list_flag'] = 'yes';
	$ppid = empty($typeArray[$cat_id]['parent_id'])?$cat_id:$typeArray[$cat_id]['parent_id'];
	$ppid = empty($typeArray[$ppid]['parent_id'])?$ppid:$typeArray[$ppid]['parent_id'];
	$ppid = empty($typeArray[$ppid]['parent_id'])?$ppid:$typeArray[$ppid]['parent_id'];
	$ppid = empty($typeArray[$ppid]['parent_id'])?$ppid:$typeArray[$ppid]['parent_id'];
    if ($cat_id == $ppid&&$page==1&&(empty($_GET['display'])&&empty($_GET['24h_ship'])&&empty($_GET['freeship'])&&empty($_GET['sortby']))) {
		//自动推荐产品 fangxin 2014/1/20
		$promote_goods = get_recommend_goods_new('cat_recommend_products_top',$cat_id);
		$promote_goods = array_values($promote_goods);
		$Arr['super_star']  = $promote_goods;
        $Arr['category_ad'] = true;
    }
	$left_sub_catArr =  getDynamicTree($ppid,0,false,$cat_id);
	$Arr['left_sub_catArr']   = $left_sub_catArr;
	$Arr['category'] =        $cat_id;
	$Arr['filter_attr'] =     $filter_attr;
	$cl->SetFilter('is_on_sale',array(1));
	if(empty($_COOKIE['WEBF-dan_num'])){
		$cl->SetFilter('is_login', array(0));
		$is_login_str = false;
	}else{
		$is_login_str = true;
	}
    $cat_ids = Func::get_category_children_ids($cat_arr, $cat_id);
    $cat_ids = explode(',', $cat_ids . $cat_id);
	$cl->SetFilter('cat_id_all', $cat_ids);
	if ($is_24h_ship == 't')
    {
    	$cl->SetFilter('is_24h_ship',array(1));
    }
	if ($freeship == 't')
    {
    	$cl->SetFilter('is_free_shipping',array(1));
    }
    $price_max > 0 && $cl->SetFilterFloatRange('shop_price', $price_min, $price_max); //价格区间 fangxin 2013/08/13
	empty($_COOKIE['WEBF-dan_num']) && $cl->SetFilter('is_login', array(0));
    !empty($Arr['super_star']) && $cl->SetFilter('@id', array_keys($Arr['super_star']), true);//干掉super产品

	//商品属性查找
	$attr_value_str = '';
	if($search_info['search_goods_attr'])
	{
		foreach ($search_info['search_goods_attr'] as $key=>$search_brief)
		{
			$search_brief = preg_replace('/\s+/', '_', $search_brief);    //替换连续空格为下划线
			$search_brief = str_replace('(', '{', $search_brief);    //替换(为{
			$search_brief = str_replace(')', '}', $search_brief);    //替换)为}
			if( empty($attr_value_str) )
			{
				$attr_value_str = '(,' .implode(',|,' , $search_brief) . ',)';
			}
			else
			{
				$attr_value_str .= '&(,' .implode(',|,' , $search_brief) . ',)';
			}
		}
		$attr_value_str = '@goods_attr ' .$attr_value_str;
	}

	//多语言搜索 fangxin 2013/07/17
	$sph_index_main = SPH_INDEX_MAIN;
	if($cur_lang != $default_lang) {
		$sph_index_main .= '_' . $cur_lang;
	}

	//查询当前分类下产品最低价格
    $cl->SetSortMode(SPH_SORT_EXTENDED, 'shop_price ASC');
	$cl->AddQuery('', $sph_index_main);

	//默认排序
	$sort = 'goods_number DESC,' . $order;
    $cl->SetSortMode(SPH_SORT_EXTENDED, $sort);    //排序
    $cl->SetGroupBy('group_goods_id', SPH_GROUPBY_ATTR, $sort);    //group_goods_id分组
    $cl->SetLimits(intval($offset), $page_size,SPH_MAX_MATCHES);    //limit
    $cl->SetMatchMode(SPH_MATCH_EXTENDED);
	$cl->AddQuery($attr_value_str, $sph_index_main);    //添加关键字查询
	$result    = $cl->RunQueries();    //执行查询

    //属性查找显示，属性查找URL处理
	$select_attr = array();
	if($typeArray[$cat_id]['template_id'])
	{
		$Arr['template_attr_info'] = get_search_attr_info($typeArray,$cat_id,$price_min,$price_max,$price_num,$search_info);
		if(!empty($Arr['template_attr_info']['select_value']))
		{
			$select_attr = $Arr['template_attr_info']['select_value'];		//选择的查找属性
		}
	}
	$Arr['select_attr'] = $select_attr;
    if ($result === false) {    //查询出错
        $error      = $cl->GetLastError();
        $Arr['sql'] = var_export($error, true);
    }else {
		if(is_array($result[0]['matches'])) {
			if(!empty($result[0]['matches'])) {
				$matches_0 = current($result[0]['matches']);
				$low_price = price_format($matches_0['attrs']['shop_price']);
			}
		}
    	$goodslist = array();
    	$count =  0;
    	$firstnum = 0;
		$lastnum = 0;
		$max_page = 0;
		if (!empty($result[1]['matches'])) {    //匹配结果
			$count =  $result[1]['total_found'];
			if($count>SPH_MAX_MATCHES)$count=SPH_MAX_MATCHES;
			$max_page = ($count> 0) ? ceil($count / $size) : 1;
			if ($page > $max_page)
			{
				$page = $max_page;
			}
			$firstnum = ($page - 1) * $size + 1;
			if ($count == 0) $firstnum = 0;
			$lastnum = $page * $size;
			if ($lastnum > $count) $lastnum = $count;
			$goods_list_arr = Func::sphinx_get_goods($result[1]['matches'], $cat_arr, $cat_id, $order);
            $goodslist		= $goods_list_arr['goods_list'];

		}
    }
	if(empty($goodslist)){
		get_cache_best_goods(8);
		$Arr['collect_info_lang'] = $_LANG['COLLECT_INFO'];
		$Arr['collect_info_lang_json'] = json_encode($_LANG['COLLECT_INFO']);
		$cat_arr = array();
		foreach($typeArray as $v) {
			if (!$v['parent_id'] && $v['is_show']) {
				$cat_arr[$v['cat_id']] = $v['cat_name'];
			}
		}
		$cat_arr[0] = 'Others';
		$Arr['cat_arr'] = $cat_arr;
	}
	if($cat_id!="") $nav_title = getNavTitle($typeArray,$typeArray[$cat_id]["parent_id"]);
	$Arr['top_cat_id']         = getTopCategoryID($cat_id, $typeArray);
	$Arr['parent_id']          = $typeArray[$cat_id]["parent_id"];
	$nav_title                 = $nav_title.' : <h1>'.get_cat_name($typeArray[$cat_id]["cat_id"],$typeArray[$cat_id]["cat_name"]) . '</h1>';
	$cat_id_arr = array();
	if(!empty($typeArray[$cat_id]['node'])){
			$node_arr = explode(',', $typeArray[$cat_id]['node']);
			$cat_id_arr['cat1_id'] = $Arr['top_cat_id'];
			if(!empty($node_arr[0]))$cat_id_arr['cat1_id'] = $node_arr[0];
			if(!empty($node_arr[1]))$cat_id_arr['cat2_id'] = $node_arr[1];
			if(!empty($node_arr[2]))$cat_id_arr['cat3_id'] = $node_arr[2];
	}
	$Arr['cat_id_arr']      = $cat_id_arr;
	if($cat_id) $shop_title = getTitle($typeArray,$typeArray[$cat_id]["parent_id"]);
	$price_str              = '';
	if ($price_min!=0) $price_str .= ' $'.$price_min;
	if ($price_max!=0) $price_str .= ' $'.$price_max;
	$is_sub_cat = 0; //等于0 为子类
	if($display=='l' && $count && $cur_lang == $default_lang)$Arr['NewArrivalArr'] = get_right_new_arrival($count,$children);
	//seo标题
	$cat_name = $typeArray[$cat_id]["cat_name"];
	$cat_title = $typeArray[$cat_id]["cat_title"];
	$Arr['is_sub_cat'] = $is_sub_cat;
	//META的设置
	$Arr['seo_title'] = str_replace(array('"','##cat_name'), array("'",$cat_name), $_LANG_SEO['goods_list']['title']);
	$Arr['seo_keywords'] = $_LANG_SEO['goods_list']['keywords'];
	$Arr['seo_description'] = str_replace(array('"','##cat_name'), array("'",$cat_name), $_LANG_SEO['goods_list']['description']);
	$cat_url     = creat_nav_url($typeArray[$cat_id]["url_title"],$cat_id);
	$parent_id   = $typeArray[$cat_id]["parent_id"];
	if(@$is_sub_cat == '0' && @$price_str=='') {
		@$cat_parent_name = $typeArray[$parent_id]["cat_name"];
		@$cat_parent_url  = creat_nav_url($typeArray[$parent_id]["url_title"],$parent_id);
		$Arr['cat_parent_name']  =  $cat_parent_name;
		$Arr['cat_parent_url']   =  $cat_parent_url;
	}
	$is_exit_sub = false;
	foreach($typeArray as $key => $val){
		if ($typeArray[$key]['parent_id'] == $cat_id){
			$is_exit_sub = true;
		}
	}
	if ($parent_id && $is_exit_sub){
		$Arr["same_cat_name"] = $typeArray[$parent_id]['cat_name'];
		//$Arr["same_cat_arr"] =  getTree($catArr, $parent_id,1,$is_login_str,false);
	}
    $parent_id_top = get_category_top_parent_id($cat_id);    //最顶级分类id
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($goodslist)) {
			foreach($goodslist as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '". $key ."'";
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$goodslist[$key]['goods_title']  = sub_str($row_lang['goods_title'], 60);
					$goodslist[$key]['goods_name']   = $row_lang['goods_name'];
					$goodslist[$key]['goods_full_title']  = $row_lang['goods_title'];
				}
			}
		}
		//推荐介绍
		$sql = 'SELECT c.*' .
				' FROM ' . CATALOG_LANG .' AS c' .
				" WHERE c.lang = '". $cur_lang ."' AND c.cat_id = '". $cat_id ."'";
		if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
			$typeArray[$cat_id]["cat_cont"]  =  $row_lang['cat_cont'];
		}
	}
    $Arr['parent_id_top']  =  $parent_id_top;
	$Arr['goods_list']     =  $goodslist;
	$Arr['out_of_stock']   = !empty($goods_list_arr['out_of_stock'])?$goods_list_arr['out_of_stock']:array();		//默认显示out of stock,当前页有一个商品为in stock时，整页都不显示out of stock  by xyl 2013-04-08
	$Arr['category']   =  $cat_id;
	$Arr['cat_id']     =  $cat_id;
	$Arr['total']      =  $count;
	$Arr['firstnum']   =  $firstnum;
	$Arr['lastnum']    =  $lastnum;
	$Arr['nav_title']  =  $nav_title;
	//底部SEO描述 fangxin 2013/08/19
	$equal_category    =  getEqualCategory($cat_id, $typeArray[$cat_id]["parent_id"]);
	$equal_category_str = '';
	if(is_array($equal_category)) {
		foreach($equal_category as $value) {
			$equal_category_str .= "<a href=\"". creat_nav_url($value['url_title'], $value['cat_id']) ."\">". $value['cat_name'] ."</a> ";
		}
	}
	if(!empty($typeArray[$cat_id]['cat_cont'])) {
		$Arr['cat_cont'] = 	$typeArray[$cat_id]['cat_cont'];
	} else {
		$Arr['cat_cont'] = $_LANG['seo_des_1'] . " " . $cat_name . " " . $_LANG['seo_des_2'] . " " . $cat_name . " " . $_LANG['seo_des_3'] . " " . $cat_name . " " . $_LANG['seo_des_4'] . " " . $equal_category_str . " " . $_LANG['seo_des_5'];
	}
	$Arr['shop_name']  =  $_CFG['shop_name'];
	$Arr['cat_name']   =  $cat_name;
	$Arr['cat_url']    =  $cat_url;
	$Arr['logo_alt']   =  'China '.$cat_name.' Wholesale '.$_CFG['shop_name'];
	$Arr['Price_Arr']	     = get_price_nav_arr_att(false,$cat_id,$cat_arr,$search_goods_attr,$price_num);	//价格区间列表
	$Arr['hot_products']     = newprocuts(10,60,$children,true); //销售前100随机15
	$same_cat = array();
	if (($price_max > 0) && ($price_min > 0)){
		foreach($typeArray as $s => $v){
			if($v['parent_id'] == $typeArray[$cat_id]["parent_id"]){
				$same_cat[$s]['cat_name'] = $v['cat_name'];
				$same_cat[$s]['url'] = '/'.title_to_url($v['cat_name']).'-'.$v['cat_id'].'-'.$price_min.'-'.$price_max.'-Wholesale.html';
			}
		}
	}
	$Arr['same_cat']   = $same_cat;
	$Arr['his_list']   = insert_history();
	$Arr['display']    =  $display;
	$Arr['page_size']  =  $size;
	$Arr['display']    =  $display;
	$Arr['sortby']     =  $sortby;
    $Arr['freeship']   = $freeship;
	$Arr['layoutpage'] =  $layoutpage[$display];
	$Arr['total']      =  $count;
	$Arr['firstnum']   =  $firstnum;
	$Arr['lastnum']    =  $lastnum;
	$Arr['page']       =  $page;
	$Arr['most_searched_tags']       = explode(',',$_CFG['most_searched_tags']);
	$Arr['is_category']   = true;//分类页标识符 by mashanling on 2012-09-21 08:59:23
	$page                 = new page(array('total'=>$count,'perpage'=>$size));
	$Arr['pagestr']       = $page->show(5);
	$Arr['left_polices']  =  get_top_article_list();//置顶文章
    //产品链接带参数的指向原地址 fangxin 2013/10/29
    if(!empty($_SERVER["REDIRECT_QUERY_STRING"]) && !isset($Arr['canonical_uri'])){
		$cononical = WEBSITE;
		$cononical = substr($cononical,0,(strlen($cononical)-1));
        $Arr['canonical_uri'] = $cononical . $_SERVER["REDIRECT_URL"];
    }
    //取第一页的url，用于排序，选择排序后，都重新跳到第一页
    $Arr['index_page'] = str_replace("-page-".$Arr['page'].".html",".html",$_SERVER['PATH_INFO']);
    //谷歌再营销
	$currency = get_currency();
	$pcat = getGoogleNavTitle($typeArray,$typeArray[$cat_id]["parent_id"]) . get_cat_name($typeArray[$cat_id]["cat_id"],$typeArray[$cat_id]["cat_name"]);
	$google_tag_params = array(
		'prodid' => "''",
		'pagetype' => "'category'",
		'totalvalue' => "''",
		'currency' => "'".$currency['currency']."'",
		'pcat' => "'".$pcat."'"
	);
	$Arr['google_tag_params'] = $google_tag_params;
}



//==================================================华丽分割线==================================================
/**
 * 获得分类的信息
 *
 * @param   integer $cat_id
 *
 * @return  void
 */
function get_cat_info($cat_id)
{
	global $cur_lang, $default_lang;
	if($cur_lang != $default_lang){
		$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$catArr =  read_static_cache('category_c_key',2);
	}
	if(empty($catArr[$cat_id])){
		$catArr =  read_static_cache('category_c_key',2);
		if(empty($catArr[$cat_id])){
			return false;
		} else {
			return $catArr[$cat_id];
		}
		return false;
	}else{
		if ($catArr[$cat_id]['is_show'] == '1'||$catArr[$cat_id]['is_show_seo']== '1'){
			return $catArr[$cat_id];
		}else{
			return false;
		}
	}
}

/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_get_goods($children, $min, $max, $ext, $size, $page,  $order,$typeArray,$cat_id)
{
	global $display,$Arr;
    $where = "  g.is_on_sale = 1   AND  ".
            "g.is_delete = 0 AND ($children or t.cat_id=$cat_id or " . get_extension_goods($children) . ")  and g.is_alone_sale = 1 ";
    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

	if ($order) $order = ','.$order;
    //除hot之外，其他的全部要去掉置顶标志
    $is_top_sql  = ' 0 as is_hot,0 as is_new,0 as is_promote ,0 as is_best , ';
	$on_sql      =  '';
	$sortby      = !empty($_GET['sortby'])?$_GET['sortby']:(!empty($_COOKIE['porder'])?$_COOKIE['porder']:'new');//排序KEY值
	$sortby      = !empty($GLOBALS['_CFG']['order'][$sortby])?$sortby:'new'; //检查是否存在排序；
	if ($sortby == 'hot') {
	    $is_top_sql  = ' if( g.goods_number=0,0,if(t.is_hot = 1 ,1,0)) as is_hot,if( g.goods_number =0, 0,if(t.is_new = 1 ,1,0)) as is_new,if( g.goods_number =0, 0, if (g.promote_end_date < unix_timestamp(now()) ,0,g.is_promote) ) as is_promote ,if( g.goods_number =0, 0,if(t.is_best = 1 ,1,0)) as is_best , ';
        $order = ",is_hot desc,t.add_date desc,week2sale desc,click_count desc,goods_id desc";
	}
	$on_sql =  'left join '.GOODSTUIJIAN.' as t '. " on (g.goods_id=t.goods_id  and t.cat_id = '".$cat_id."') ";
	/* 获得商品列表 */
	$sql = 'SELECT is_24h_ship,g.presale_date_from,g.presale_date_to,g.goods_id, g.goods_title, g.goods_name_style,g.goods_name,g.goods_sn,g.cat_id, g.market_price,g.goods_weight,g.is_free_shipping, g.url_title,g.goods_grid, g.goods_number, g.shop_price AS org_price, g.shop_price , g.promote_price, g.goods_type, g.promote_price ,  '.$is_top_sql.' g.promote_start_date, g.promote_end_date,g.is_free_shipping , g.goods_brief, g.goods_thumb , g.goods_img '.
	' FROM ' . GOODS . ' AS g '.' left join '.GOODS_STATE.' s on g.goods_id=s.goods_id '. $on_sql . " WHERE $where  $ext ORDER BY if( goods_number =0, 0, 1 ) DESC $order limit ".(($page - 1) * $size).",$size ";
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
        $arr[$row['goods_id']]['goods_id']              = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_title']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
        }
		if (strpos($row['goods_name'],',') !== false){
			$row['goods_name'] = explode(',',$row['goods_name']);
			$row['goods_name'] = $row['goods_name'][0];
		}
		$cat_name = empty($typeArray[$row['cat_id']]['cat_name'])?'':$typeArray[$row['cat_id']]['cat_name'];
		$big_cat  = empty($typeArray[$row['cat_id']]['parent_id'])?true:false;
		$arr[$row['goods_id']]['is_24h_ship'] = $row['is_24h_ship'];
		$arr[$row['goods_id']]['cat_name']    = $cat_name;
		$arr[$row['goods_id']]['cat_url']     = creat_nav_url(empty($typeArray[$row['cat_id']])?'':$typeArray[$row['cat_id']]['url_title'],$row['cat_id'],$big_cat);
		if($row['presale_date_from']){
				if($row['presale_date_from'] > gmtime()){
					$arr[$row['goods_id']]['presale_date_from'] =$row['presale_date_from'];
				}else {
					$arr[$row['goods_id']]['presale_date_from'] = '';
				}
	     }
	     if($row['presale_date_to']){
				if($row['presale_date_to'] > gmtime()){
					$arr[$row['goods_id']]['presale_date_to'] =$row['presale_date_to'];
				}else {
					$arr[$row['goods_id']]['presale_date_to'] = '';
				}
		}
        $arr[$row['goods_id']]['name']             = $row['goods_title'];
        $arr[$row['goods_id']]['goods_name']       = $row['goods_name']; //型号
        $arr[$row['goods_id']]['goods_sn']         = $row['goods_sn'];
        $arr[$row['goods_id']]['goods_full_title'] = $row['goods_title'];
        $arr[$row['goods_id']]['review']		   = get_review_rate($row['goods_id']);
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['is_hot']           = $row['is_hot'];
        $arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
        $arr[$row['goods_id']]['is_best']          = $row['is_best'];
        $arr[$row['goods_id']]['is_new']           = $row['is_new'];
        $arr[$row['goods_id']]['is_promote']       = ($promote_price > 0) ? $row['is_promote']:0;
        $arr[$row['goods_id']]['goods_number']     = $row['goods_number'];
        $arr[$row['goods_id']]['goods_brief']      = sub_str($row['goods_brief'],110);
        $arr[$row['goods_id']]['goods_weight']     = formated_weight($row['goods_weight']);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        $arr[$row['goods_id']]['type']             = $row['goods_type'];
        $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['promote_zhekou']   = ($promote_price > 0 && $row['market_price']>0) ? round(($row['market_price'] - $promote_price)/$row['market_price'],2) * 100 : '';
        $arr[$row['goods_id']]['shop_price']       = ($promote_price > 0) ? price_format($promote_price) : price_format($row['shop_price']);
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['goods_grid']       = get_image_path($row['goods_id'], $row['goods_grid']);
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['saveprice']        = price_format($row['market_price'] - $row['shop_price']);
		$arr[$row['goods_id']]['saveperce']        = ($row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['shop_price'])/$row['market_price'])*100;
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
    }
    return $arr;
}


/**
 *获取指定分类下的新品
 */
function get_news_arrivals($cat_id='0',$limit = 10)
{
	global $db, $cur_lang, $default_lang;
	if(empty($cat_id))return ;
	$cat_ids = get_children($cat_id);
	$sql     = 'SELECT g.goods_number,g.goods_id,goods_sn,cat_id, goods_title,original_img ,goods_thumb,goods_img,goods_name_style,is_free_shipping,shop_price,promote_price,promote_start_date, promote_end_date,goods_thumb,market_price,goods_grid,sort_order,url_title ' .
    ' FROM ' . GOODS . ' AS g '.
    " WHERE is_on_sale = 1 AND is_alone_sale = 1 and $cat_ids".
    ' ORDER BY goods_id desc LIMIT '.$limit;
	if(!empty($_GET['is_test'])) echo  $sql;
    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr       = array();
    foreach ($goods_res as $row){
        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        $arr[$row['goods_id']]['goods_sn']         = $row['goods_sn'];
        $arr[$row['goods_id']]['goods_number']     = $row['goods_number'];
        $arr[$row['goods_id']]['goods_title']      = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
        $arr[$row['goods_id']]['short_name']       = strlen($row['goods_title'])<68?$row['goods_title']:substr($row['goods_title'],0,67).'...';
        $arr[$row['goods_id']]['goods_grid']       = get_image_path($row['goods_id'], $row['goods_grid'], true);
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['original_img']     = get_image_path($row['goods_id'], $row['original_img'], true);
        $arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img'], true);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['saveperce']        = ($row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['shop_price'])/$row['market_price'])*100;
        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        $arr[$row['goods_id']]['save_money']       = intval($row['market_price'] - $row['shop_price']);
        $arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }
        $arr[$row['goods_id']]['promote_price']       = $promote_price;
        $arr[$row['goods_id']]['zhekou'] = ($promote_price == 0||$row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $promote_price)/$row['market_price'])*100;
        if($promote_price>0){
        	 $arr[$row['goods_id']]['shop_price']     = price_format($promote_price);
        }
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
					$arr[$key]['short_name']  = strlen($row_lang['goods_title'])<68?$row_lang['goods_title']:substr($row_lang['goods_title'],0,67).'...';
				}
			}
		}
	}

    return $arr;
}

function cat_redirected($cat_url){
	$arr_cat =  read_static_cache('redirect301cat',1);
	if(!empty($arr_cat[$cat_url])){
		Header("HTTP/1.1 301 Moved Permanently");
	    header("location:/".$arr_cat[$cat_url]);
	    exit();
	}
}

/**
 * 获取super star产品
 * @author          mashanling <msl-138@163.com>
 * @date            2013-02-17 13:58:23
 * @param int $cat_id 分类id
 * @param int $limit  获取个数。默认4
 */
function get_category_super_star_goods($cat_id, $limit = 4) {
    global $db, $cur_lang, $default_lang;
    $data = array();
    $sql  = 'SELECT g.goods_id,g.goods_sn,goods_title,goods_grid,market_price,url_title,shop_price,goods_grid,promote_price,promote_start_date,promote_end_date FROM ' . GOODS . ' AS g JOIN ' . GOODSTUIJIAN . " AS t ON g.goods_id=t.goods_id AND t.cat_id={$cat_id} WHERE is_on_sale=1 AND is_alone_sale=1 AND is_delete=0 AND goods_number>0 AND t.is_super_star=1 ORDER BY g.sort_order,g.goods_id DESC LIMIT {$limit}";
    $db->query($sql);
    $super_star = read_static_cache('super_star',1);
    while ($row = $db->fetchArray()) {
        $goods_id      = $row['goods_id'];
        $promote_price = $row['promote_price'] > 0 ? bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']) : 0;
        $data[$goods_id]['promote_price'] = $row['promote_price'];
        $data[$goods_id]['shop_price']    = price_format($promote_price > 0 ? $promote_price : $row['shop_price']);
        $data[$goods_id]['goods_title']   = sub_str($row['goods_title'],60,'...');
		$data[$goods_id]['goods_full_title']   = $row['goods_title'];
        $data[$goods_id]['goods_img']     = get_image_path(false, $row['goods_grid']);
        $data[$goods_id]['market_price']  = $row['market_price'];
        $data[$goods_id]['save_price']    = price_format($row['market_price'] - $data[$goods_id]['shop_price']);
        $data[$goods_id]['save_zekou']    = $data[$goods_id]['save_price'] > 0 ? price_format($data[$goods_id]['save_price'] / $row['market_price']) * 100 : 0;
        $data[$goods_id]['goods_url']     = get_details_link($row['goods_id'], $row['url_title']);
        if($super_star && in_array($row['goods_sn'],$super_star)){
            $data[$goods_id]['is_super_star'] = 1;
        }
    }

	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($data)) {
			foreach($data as $key => $value) {
				$sql = 'SELECT g.*' .
					   ' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
					   " WHERE g.goods_id = '". $key ."'";
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$data[$key]['goods_title']  = $row_lang['goods_title'];
				}
			}
		}
	}

    return $data;
}

//获取当前分类的一级分类ID
function getTopCategoryID($cat_id, $typeArray) {
	global $top_category_id;
	if($typeArray[$cat_id]['parent_id'] == 0) {
		return $cat_id;
	} else {
		$top_category_id = $typeArray[$cat_id]['parent_id'];
		getTopCategoryID($typeArray[$cat_id]['parent_id'], $typeArray);
	}
	return $top_category_id;
}

//同级分类
function getEqualCategory($cat_id, $parent_id) {
	global $db;
	$sql = "SELECT cat_id, cat_name, parent_id, url_title FROM ". CATALOG ." WHERE parent_id = ". $parent_id ." AND cat_id NOT IN(". $cat_id .")";
	$res = $db->getAll($sql);
	if($res) {
		return $res;
	}
}