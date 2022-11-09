<?php
/*
+----------------------------------
* 首页
+----------------------------------
*/
$Tpl->caching = false;        //使用缓存
global $cur_lang, $default_lang;
if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id))
{
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	$Arr['seo_title'] = $_LANG_SEO['index']['title'];
	$Arr['seo_keywords'] = $_LANG_SEO['index']['keywords'];
	$Arr['seo_description'] = $_LANG_SEO['index']['description'];		
	$Arr['shop_name']  = $_CFG['shop_name'];
	$Arr['home_flag']  = '_s';
	$is_login_str = 'category_login_html';
	if($cur_lang !='en') 
		$Arr['left_catArr'] = getDynamicTree(0);
	$Arr['page'] = 'index';	 
	$Arr['left_polices']  =  get_top_article_list();//置顶文章
	$Arr['filepath'] =  ARTICLE_DIR;
	$home_cat = get_is_home_cat(8);
	foreach ($home_cat as $k=>$v){
		$home_cat[$k]['cat_name'] = get_cat_name($v['cat_id'],$home_cat[$k]['cat_name']) ;
		$home_cat[$k]['cat_name'] = get_cat_name($v['cat_id'],$home_cat[$k]['cat_name']) ;
	}
	$Arr['home_cat'] = $home_cat;
    $Arr['daily_recommended'] = get_recommend_goods('daily',1,70, true);//每日推荐	
	$Arr['recommended_deals'] = get_recommend_goods_new('recommended_deals');//推荐	
	$Arr['hot_deals'] = get_recommend_goods_new('hot_deals'); //热卖
    if ($Arr['daily_recommended'] = array_shift($Arr['daily_recommended'])) {
        $Arr['daily_recommended_review'] = $db->getOne($sql = 'SELECT pros FROM ' . REVIEW . " WHERE goods_id={$Arr['daily_recommended']['goods_id']} AND is_pass=1 ORDER BY is_top DESC, rid DESC");//评论
    }
	if($cur_lang != $default_lang){
		$catKeyArr =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$catKeyArr =  read_static_cache('category_c_key',2);
	}	
	$dalei_Arr = array();
	$leftlei_Arr = array();
	if (! empty ( $catKeyArr )) {
		foreach ( $catKeyArr as $key => $val ) {
			if (empty ( $val ['parent_id'] ) && $val ['is_show'] && $val ['is_home']) {
				$val ['url_title'] = creat_nav_url ( $val ['url_title'], $val ["cat_id"], true );
				$val ['_childs'] = getCatGoods ( $val ["cat_id"], $catKeyArr );
				$dalei_Arr [] = $val;
			}
			if (empty ( $val ['parent_id'] ) && $val ['is_show'] && $val ['is_home_under']) {
				$val ['url_title'] = creat_nav_url ( $val ['url_title'], $val ["cat_id"], true );
				$leftlei_Arr [] = $val;
			}

		}
	}
	$Arr['dalei_Arr'] = $dalei_Arr;
	$Arr['leftlei_Arr'] = $leftlei_Arr;
	unset($catKeyArr);

	/* 查询订单 */
	$Arr['order_list']  =  get_index_order();
	$sql = "select cat_name,cat_id from ".ARTICLECAT." where parent_id = 13 ORDER BY sort_order,cat_id";
	$ArticleCatArr = $db -> arrQuery($sql);
	foreach ($ArticleCatArr as $k => $v){
		//多语语 fangxin 2013/07/17
		if($cur_lang != $default_lang) {
			$sql = "SELECT * FROM eload_article_cat_muti_lang WHERE cat_id = ". $v['cat_id'] ." AND lang ='". $cur_lang ."'";
			$lang_res = $db->selectInfo($sql);
			if($lang_res) {
				$ArticleCatArr[$k]["cat_name"] = $lang_res['cat_name'];		
			}
		}
		//end		
		$ArticleCatArr[$k]["_childlist"] = get_article_list($v['cat_id']);
	}	
	$Arr['ArticleCatArr'] = $ArticleCatArr;
	$Arr['is_index'] = 1;
    if (!empty($_GET['gt'])){
	   echo $daydealArr['LeftTime'];
	   exit;
    }
    //谷歌再营销
	$currency = get_currency();
	$google_tag_params = array(
		'prodid' => "''",
		'pagetype' => "'home'",
		'totalvalue' => "''",
		'currency' => "'". $currency['currency'] ."'",
		'pcat' => "''"    
	);	
	$Arr['google_tag_params'] = $google_tag_params;
}

function getCatGoods($cat_id,$catKeyArr){
	$is_login_str = ' and g.is_login = 0 ';
    if (!empty($_COOKIE['WEBF-dan_num'])){
		$is_login_str = '';
	}
	$children =' g.cat_id '. db_create_in(getChildArr($catKeyArr,$cat_id));
	//	$children = ' cat_id '.db_create_in(array_unique(array_merge(array($val), $sub_Arr)));
    $sql = 'SELECT g.goods_id, g.goods_title,g.goods_number, g.is_free_shipping, g.goods_thumb, g.goods_grid,g.cat_id, g.shop_price AS org_price,g.promote_price,g.promote_start_date,g.promote_end_date, ' .
           'g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date,g.url_title ' .
           ' FROM ' . GOODS . ' AS g   ' .
		   ' left join '.GOODSTUIJIAN.' AS t on t.goods_id = g.goods_id '.
           "WHERE  g.is_on_sale = 1 and t.is_hot = 1 And t.cat_id = $cat_id  $is_login_str and g.is_alone_sale = 1 and g.is_delete = 0 AND  $children And g.goods_number > 0 ".
           " order by sort_order,click_count desc,goods_id desc LIMIT 4";
    $res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach($res as $row)
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }
		$isbig = '';
		$urltitle = $catKeyArr[$row["cat_id"]]["url_title"];
		$cat_name = $catKeyArr[$row["cat_id"]]["cat_name"];
		if ($catKeyArr[$row["cat_id"]]["parent_id"] == 0)  $isbig = true;
		$arr[$row['goods_id']]['leiurl'] = creat_nav_url($urltitle,$row["cat_id"],$isbig);
		$arr[$row['goods_id']]['leiname'] = $cat_name;
        $arr[$row['goods_id']]['cat_id']             = $row['cat_id'];
        $arr[$row['goods_id']]['goods_title']        = $row['goods_title'];
        $arr[$row['goods_id']]['is_free_shipping']   = $row['is_free_shipping'];
        $arr[$row['goods_id']]['short_name']         = $row['goods_title'];
        $arr[$row['goods_id']]['goods_thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']          = get_image_path($row['goods_id'], $row['goods_grid']);
        $arr[$row['goods_id']]['shop_price']         = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
        $arr[$row['goods_id']]['url_title']          = get_details_link($row['goods_id'],$row['url_title']);

        if ($row['promote_price'] > 0)
        {
            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
        }
        else
        {
            $arr[$row['goods_id']]['promote_price'] = 0;
        }
    }
    return $arr;
}


function getChildArr($catKeyArr,$cat_id){
	$cat_idArr = '';
	foreach($catKeyArr as $kk => $vv){
		if($vv['parent_id'] == $cat_id){
			$cat_idArr .= $vv['cat_id'].','.getChildArr($catKeyArr,$vv['cat_id']);
		}
	}
	return $cat_idArr;
}


function get_new_products(){
	$sql = "select goods_id,cat_id, goods_title,goods_name_style,is_free_shipping,shop_price,goods_thumb,goods_grid,sort_order,url_title from ".GOODS." WHERE is_on_sale = 1 AND goods_thumb <>'' and is_alone_sale = 1 and is_login =0 AND goods_number > 0 AND is_delete = 0 and is_new=1 and is_home =1 group by cat_id order by sort_order asc limit 11";
	$res = $GLOBALS['db']->arrQuery($sql);
	$arr = array();
	if (!empty($res)){
		foreach ($res as $row){
			$arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
			$arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
            $arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
            $arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],50);
			$arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
			$arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
			$arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
			$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
		}
	}
	return  $arr;
}

function get_is_home_cat($limit=6){
	global $cur_lang, $default_lang;
	if($cur_lang != $default_lang) {
		$sql = "SELECT * FROM ".CATALOG." c INNER JOIN ".CATALOG_LANG." l ON c.cat_id=l.cat_id WHERE is_home=1 AND parent_id = 0 AND lang = '". $cur_lang ."' AND is_show = 1 AND cat_pic<>'' ORDER BY sort_order";
	} else {
		$sql = "SELECT * FROM ".CATALOG." WHERE is_home=1 AND parent_id=0 AND cat_pic<>'' ORDER BY sort_order";
	}
	if($limit)$sql .= " limit $limit";
	$ArrHomeBigCat  = $GLOBALS['db']->arrQuery($sql);
	foreach($ArrHomeBigCat as $k=> $BigCat){
		$ArrHomeBigCat[$k]['sml_cat']=getTree('',$BigCat['cat_id'],1,false,0,0,3);
		$ArrHomeBigCat[$k]['sml_cat'] = str_replace(array('<li>', '</li>','<ul>','</ul>'), array('<dd>', '</dd>','<dl>','</dl>'), $ArrHomeBigCat[$k]['sml_cat']);
		$p = empty($BigCat['parent_id'])?true:false;
		$ArrHomeBigCat[$k]['url_title']=creat_nav_url($BigCat['url_title'],$BigCat['cat_id'],$p);
	}
	return $ArrHomeBigCat;
}