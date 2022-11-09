<?
/*
+----------------------------------
* 专题
+----------------------------------
*/
$active = empty($_GET['active'])?'1':intval($_GET['active']);
$page = empty($_GET['page'])?'1':intval($_GET['page']);
$special_id = empty($_GET['special'])?'':$_GET['special'];
$Tpl->caching = false;        //使用缓存
if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id)) {
    require_once(ROOT_PATH . 'fun/fun.global.php');
    require_once(ROOT_PATH . 'fun/fun.public.php');
    if($special_id){
        $special = get_special_info($special_id);           //获取专题详情
		$special['remark'] = htmlspecialchars_decode($special['remark']);
        if($special['temp'] !=''){
            if($special['temp'] == 1){//只包含一个分类
                $Arr['data'] = get_special_goods_by_special_id($special['special_id']);
            }elseif($special['temp'] == 2){//返回带分类的
                $Arr['data'] = get_special_goods_by_category($special_id);
            }
            $Arr['special'] = $special;
            $Arr['_temp'] = $special['temp'];
            $seo_title = $special['title'].' - '.$_CFG['shop_name'];
            $seo_keywords = $special['keyword'];
            $seo_description = $special['description'];
            $Arr['shop_name'] = $_CFG['shop_name'];
            $Arr['nav_title'] = $special['title'];
			$Arr['ArticleCatArr'] = get_foothelp_article();
            $_MDL = 'special';
            $_ACT='special';
            temp_disp();exit;
        }
    }else{//普通专题 需要美工添加模板
		$c_Arr = require(SMARTY_TMPL.'promotion_title_confing.php');
        if(empty($c_Arr[$active])) {
            redirect_url();//保持链接地址不变 by mashanling on 2012-07-13 10:19:38
            exit;
        }
        $is_login_str = 'category_login_html';
        if (!empty($_COOKIE['WEBF-dan_num'])) $is_login_str = 'category_html';
        $Arr['left_catArr']  = read_static_cache($is_login_str,2);
        $catArr = read_static_cache('category_c_key',2);
        $seo_title = $c_Arr[$active]['title'].' - '.$_CFG['shop_name'];
        $seo_keywords = $c_Arr[$active]['keywords'];
        $seo_description = $c_Arr[$active]['desc'];
        $Arr['shop_name'] = $_CFG['shop_name'];
        $Arr['home_flag'] = '_s';
        $Arr['active'] = $active;
        $Arr['page'] = $page;
    }
    //meta设置
    $Arr['seo_title'] = $seo_title;
    $Arr['seo_keywords'] = $seo_keywords;
    $Arr['seo_description'] = $seo_description;     
}//end if isCache

//==================================================华丽分割线==================================================
function get_childss($val) {
    global $db;
    $sql = "select cat_id from ".CATALOG." where parent_id = '".$val."'";
    $sub_cat_id_Arr = $db->arrQuery($sql);
    $sub_Arr = array();
    foreach($sub_cat_id_Arr as $sk => $sv) {
        $sub_Arr[] = $sub_cat_id_Arr[$sk]['cat_id'];

        $sql = "select cat_id from ".CATALOG." where parent_id = '".$sub_cat_id_Arr[$sk]['cat_id']."'";
        $sub_IDS_Arr = $db->arrQuery($sql);
        foreach($sub_IDS_Arr as $kkk => $ssv) {
            $sub_Arr[] = $sub_IDS_Arr[$kkk]['cat_id'];
        }

    }
    return ' cat_id '.db_create_in(array_unique(array_merge(array($val), $sub_Arr)));

}


/**
 * 获得推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot
 * @return  array
 */
function get_special_goods($cat_id, $limit = '5',$ishot = true) {
//echo $cat_id;
    $catsql = " and cat_id = '$cat_id' ";
    if ($ishot) $catsql .=  " AND is_hot = 1 ";

    $sql = 'SELECT goods_id,cat_id, goods_title,goods_name_style,is_free_shipping,shop_price,promote_price,promote_start_date, promote_end_date,goods_thumb,market_price,goods_grid,sort_order,url_title ' .
        ' FROM ' . GOODS . ' AS g ' .
        ' WHERE is_on_sale = 1 AND is_alone_sale = 1  AND is_delete = 0 '.$catsql .
        ' ORDER BY sort_order, last_update DESC LIMIT '.$limit;
    //  echo $sql ;
    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($goods_res as $row) {


        $arr[$row['goods_id']]['goods_title']      = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
        $arr[$row['goods_id']]['short_name']       = $row['goods_title'];
        $arr[$row['goods_id']]['goods_grid']       = get_image_path($row['goods_id'], $row['goods_grid'], true);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['saveperce']        = ($row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['shop_price'])/$row['market_price'])*100;
        $arr[$row['goods_id']]['market_price']       = price_format($row['market_price']);
        $arr[$row['goods_id']]['save_money']       = intval($row['market_price'] - $row['shop_price']);
        $arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
    }
    return  $arr;
}



/**
 * 获得推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot
 * @return  array
 */
function get_ext_hot_goods($cat_id, $limit = '5',$ishot = true) {
//echo $cat_id;

    $catsql = " cat_id = '$cat_id' ";

    $sql = 'SELECT goods_id FROM ' . GOODSCAT . " AS g WHERE $catsql";
    $extension_goods_array = $GLOBALS['db']->getCol($sql);
    $catsql = ' and  '.db_create_in($extension_goods_array, 'goods_id');


    if ($ishot) $catsql .=  " AND is_hot = 1 ";

    $sql = 'SELECT goods_id,cat_id, goods_title,goods_name_style,is_free_shipping,shop_price,promote_price,promote_start_date, promote_end_date,goods_thumb,market_price,goods_grid,sort_order,url_title ' .
        ' FROM ' . GOODS . ' AS g ' .
        ' WHERE is_on_sale = 1 AND is_alone_sale = 1  AND is_delete = 0 '.$catsql .
        ' ORDER BY sort_order, last_update DESC LIMIT '.$limit;
    //echo $sql ;
    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($goods_res as $row) {


        $arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
        $arr[$row['goods_id']]['short_name']       = $row['goods_title'];
        $arr[$row['goods_id']]['goods_grid']      = get_image_path($row['goods_id'], $row['goods_grid'], true);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['saveperce']        = ($row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['shop_price'])/$row['market_price'])*100;
        $arr[$row['goods_id']]['market_price']       = price_format($row['market_price']);
        $arr[$row['goods_id']]['save_money']       = intval($row['market_price'] - $row['shop_price']);
        $arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
    }
    return  $arr;
}


/**
 * 获得推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot
 * @return  array
 */
function get_BigCat_hot_goods($children, $limit = '5',$ishot = true) {
//echo $cat_id;

    $catsql = "  AND $children ";

    //$sql = 'SELECT goods_id FROM ' . GOODSCAT . " AS g WHERE $catsql";
    //$extension_goods_array = $GLOBALS['db']->getCol($sql);
    //$catsql = ' and  '.db_create_in($extension_goods_array, 'goods_id');


    if ($ishot) $catsql .=  " AND is_hot = 1 ";

    $sql = 'SELECT goods_id,cat_id, goods_title,goods_name_style,is_free_shipping,shop_price,promote_price,promote_start_date, promote_end_date,goods_thumb,market_price,goods_grid,sort_order,url_title ' .
        ' FROM ' . GOODS . ' AS g ' .
        ' WHERE is_on_sale = 1 AND is_alone_sale = 1  AND is_delete = 0 '.$catsql .
        ' ORDER BY sort_order, last_update DESC LIMIT '.$limit;
    //echo $sql ;
    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($goods_res as $row) {


        $arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
        $arr[$row['goods_id']]['short_name']       = $row['goods_title'];
        $arr[$row['goods_id']]['goods_grid']      = get_image_path($row['goods_id'], $row['goods_grid'], true);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['saveperce']        = ($row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['shop_price'])/$row['market_price'])*100;
        $arr[$row['goods_id']]['market_price']       = price_format($row['market_price']);
        $arr[$row['goods_id']]['save_money']       = intval($row['market_price'] - $row['shop_price']);
        $arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
    }
    return  $arr;
}


/**
 * 94专题产品
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2013-05-13 17:11:38
 *
 * @return void 无返回值
 */
function here_94($position_id = 377, $special_id = 51) {
    global $db, $Arr;

    $data = array();
    $sql  = 'SELECT g.goods_id,g.goods_title,g.url_title,g.shop_price,g.goods_img,g.market_price,g.promote_price,g.promote_start_date,g.promote_end_date,g.point_rate,g.goods_number FROM ' . SPECIAL_GOODS . ' AS s JOIN ' . GOODS . ' AS g ON g.goods_id=s.goods_id WHERE s.position_id=' . $position_id . ' AND s.special_id=' . $special_id;
    $db->query($sql);

    while($row = $db->fetchArray()) {
        $price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);	//促销价格
        $row['goods_img'] = get_image_path(false, $row['goods_img']);
        $row['shop_price'] = $price > 0 ? $price : $row['shop_price'];
        $row['zekou'] = $row['market_price'] > $row['shop_price'] ? round(($row['market_price'] - $row['shop_price']) / $row['market_price'], 2) * 100 : '0';
        $row['link_url'] = get_details_link($row['goods_id'], $row['url_title']);
        $row['points'] = intval($row['shop_price'] * $row['point_rate']);
        $data[] = $row;
    }

    $Arr['data'] = $data;
}

/*
 *author lchen
 * 获取专题详情
 */
function get_special_info($active){
    global $db;
    $data = array();
    $data = $db->selectInfo("select * from ".SPECIAL." where  special_id = '".$active."'");
    return $data;
}

function get_special_goods_by_category($active){
    global $db, $Arr;
    $data = array();
    $goods_info = array();
    $position = $db->arrQuery("select position_id,name,url from ".SPECIAL_POSITION." where  special_id = '".$active."' order by position_id asc");

    foreach ($position as $key=>$val){
        $info =array();
        $sql  = 'SELECT g.goods_id,g.goods_title,g.url_title,g.shop_price,g.goods_img,g.market_price,g.promote_price,g.promote_start_date,g.promote_end_date,g.point_rate,g.goods_number,goods_grid FROM ' . SPECIAL_GOODS . ' AS s JOIN ' . GOODS . ' AS g ON g.goods_id=s.goods_id WHERE s.position_id="' . $val['position_id'] . '" AND s.special_id= "'.$active.'" order by s.sort_order';
        $goods_info=$db->arrQuery($sql);
        foreach($goods_info as $keys=>$row){
            $price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);	//促销价格
            $row['goods_img'] = get_image_path(false, $row['goods_img']);
            $row['goods_grid'] = get_image_path(false, $row['goods_grid']);
            $row['shop_price'] = $price > 0 ? $price : $row['shop_price'];
			$row['promote_price'] = $price;
            $row['zekou'] = $row['market_price'] > $row['shop_price'] ? round(($row['market_price'] - $row['shop_price']) / $row['market_price'], 2) * 100 : '0';
            $row['link_url'] = get_details_link($row['goods_id'], $row['url_title']);
            $row['points'] = intval($row['shop_price'] * $row['point_rate']);
            $info[$keys] = $row;
        }
        $data[$key]['position_id'] = $val['position_id'];
        $data[$key]['name'] = $val['name'];
        $data[$key]['url'] = $val['url'];
        $data[$key]['data'] = $info;
        unset($info);
    }
    return $data;

}

/**
 * 94专题产品
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2013-05-13 17:11:38
 *
 * @return void 无返回值
 */
function get_special_goods_by_special_id($special_id) {
    global $db, $Arr;
    $position_id = $db->getOne("select position_id from ".SPECIAL_POSITION." where special_id = '".$special_id."'");
    $data = array();
    $sql  = 'SELECT g.goods_id,g.goods_title,g.url_title,g.shop_price,g.goods_img,g.market_price,g.promote_price,g.promote_start_date,g.promote_end_date,g.point_rate,g.goods_number,goods_grid FROM ' . SPECIAL_GOODS . ' AS s JOIN ' . GOODS . ' AS g ON g.goods_id=s.goods_id WHERE s.position_id="' . $position_id . '" AND s.special_id=' . $special_id." order by s.sort_order";
    $db->query($sql);

    while($row = $db->fetchArray()) {
        $price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);	//促销价格
        $row['goods_img'] = get_image_path(false, $row['goods_img']);
        $row['goods_grid'] = get_image_path(false, $row['goods_grid']);
        $row['shop_price'] = $price > 0 ? $price : $row['shop_price'];
		$row['promote_price'] = $price;
        $row['zekou'] = $row['market_price'] > $row['shop_price'] ? round(($row['market_price'] - $row['shop_price']) / $row['market_price'], 2) * 100 : '0';
        $row['link_url'] = get_details_link($row['goods_id'], $row['url_title']);
        $row['points'] = intval($row['shop_price'] * $row['point_rate']);
        $data[] = $row;
    }

    return $data;
}