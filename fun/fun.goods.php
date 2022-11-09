<?php
if (!defined('INI_WEB')){die('访问拒绝');}
/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */
global $cur_lang, $default_lang;
$goods_id = isset($_GET['id'])  ? intval($_GET['id']) : 0;
$qty = empty($_GET['qty'])  ?1 : intval($_GET['qty']);
$atrid    = !empty($_GET['atrid'])  ? intval($_GET['atrid']) : 0;
$cookie_cat_id       =          !empty($_COOKIE["cookie_cat_id"])?intval($_COOKIE["cookie_cat_id"]):0;
$attr_value = '';
$is_yulan = empty($_GET['islan'])?'':$_GET['islan'];
$goods_attr_custom_size_temp = empty($_GET['temp'])?'':$_GET['temp'];
if(!empty($_SESSION["user_rank"]))
{
   $Arr['user_rank'] = $_SESSION["user_rank"];
}
//$Tpl->caching = true;
//$Tpl->cache_lifetime = 12*3600;
if ($is_yulan) {
	$Tpl->caching = false;
}
$goods_url = $_SERVER['REQUEST_URI'];
//特定地址301跳转
if('/best_175648.html' == $_SERVER['REQUEST_URI']) {
	Header( "HTTP/1.1 301 Moved Permanently");
	header("location:/best_179667.html");
	exit();
}
require_once(ROOT_PATH . 'fun/fun.global.php');
goods_redirected(str_replace("/", "", $goods_url)); //分类301跳转
//affiliate_detect(); // 识别　affiliate　链接
if(!empty($goods_attr_custom_size_temp)){
	$abc = empty($_POST['abc'])?'':$_POST['abc'];
	if ($abc == 'yes'){
		unset($_POST['button']);
		unset($_POST['abc']);
		$_POST['msg'] = HtmlEncode($_POST['msg']);
		$_SESSION['custom_size'] = json_encode($_POST);
		echo '<script>try{ymPrompt=top.ymPrompt}catch(e){};ymPrompt.close();</script>';
		exit;
	}
	$_ACT = 'attr_custom_size_temp';
	temp_disp();
	exit;
}
if(!IS_LOCAL)open_cdn_cache();//开启页面CDN缓存


$my_cache_id = $goods_id . '-'.$atrid.'-'.$cur_lang.'-'.$cookie_cat_id ;
$my_cache_id = sprintf('%X', crc32($my_cache_id));
if (!$Tpl->is_cached('goods.htm', $my_cache_id))
{

	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	require_once(ROOT_PATH . 'lib/class.page.php');
	$sn = isset($_GET['sn'])  ? $_GET['sn'] : '';
	if(!empty($sn))
	   $goods_id =  $db->getOne("select goods_id from eload_goods where goods_sn = '$sn'");

    $Arr['image_width'] =   $_CFG['image_width'];
    $Arr['image_height'] =  $_CFG['image_height'];
    $Arr['id'] =            $goods_id;
    $Arr['type'] =          0;
    $Arr['qty'] =        $qty;
	if ($atrid >0){
	   $sql = "select attr_value from ".GATTR." where goods_attr_id = '$atrid' ";
	   $attr_value = $db->getOne($sql);
	   $attr_value = $attr_value?$attr_value.' ':'';
	}

   //处理每日特销开始
	$nowtime = local_strtotime(local_date('Y-m-d'));
	//$sql = "select shop_price_backup,goods_id,daydeal_time from ".GOODS."  WHERE  goods_id = '$goods_id' AND is_daydeal = 2 limit 1 ";
	//$gAds = $db->selectinfo($sql);
	//$daydeal_time = $gAds['daydeal_time'];
	//$shop_price   = $gAds['shop_price_backup'];
	//促销时间不对，进入促销价格处理流程
	//if(!empty($daydeal_time) && $daydeal_time != $nowtime && $shop_price > 0){
	//		require_once(ROOT_PATH.'lib/syn_public_fun.php');
			//多级价格还原
	//		$sql = "UPDATE " . VPRICE .
	//			   " SET price_type = '1' WHERE  goods_id = '$goods_id'";
	//		$db->query($sql);
	//		$sql = "update ".GOODS."  set  shop_price = '$shop_price', is_daydeal = '1'  where goods_id = '$goods_id'";
	//		$db->query($sql);
			//echo '进入价格处理流程'.$sql;
	//}
   //unset($gAds);
   // echo $daydeal_time.' '.$nowtime;
   // exit;
   //每日特销结束

    $nav_title = '';
    //获得商品的信息 fangxin 2013/07/05
    $goods = get_goods_info($goods_id);
	//canonical fangxin 2013/08/15
	$goods_cononical_url  = get_goods_cononical($goods['goods_sn']);
	$Arr['canonical_uri'] = $goods_cononical_url;
    //产品链接带参数的指向原地址 fangxin 2013/10/29
    if(!empty($_SERVER["REDIRECT_QUERY_STRING"]) && empty($Arr['canonical_uri'])){
		$cononical = WEBSITE;
		$cononical = substr($cononical,0,(strlen($cononical)-1));
        $Arr['canonical_uri'] = $cononical . $_SERVER["REDIRECT_URL"];
    }
    if ($goods === false)
    {
        $goods2cat = read_static_cache('goods2cat', FRONT_STATIC_CACHE_PATH);//删除的产品映射到分类
    	$qinquan = read_static_cache('qinquan.log',2); //	侵权产品
    	if(!empty($goods2cat["$goods_id"])||!empty($qinquan["$goods_id"])){
    		$c_id =!empty($goods2cat["$goods_id"])?$goods2cat[$goods_id]:$qinquan["$goods_id"];
    		$cat_url= $db->getone("select url_title from ".CATALOG." where cat_id =$c_id");
    		if(!empty($cat_url)){
    			Header( "HTTP/1.1 301 Moved Permanently");
    			header("location:/$cat_url");
    			exit();
    		}
    	}
        /* 如果没有找到任何记录输出404*/
		redirect_url();
	   	exit;
    }
    else
    {
    	if($goods['is_delete'] == '1'){
    		  redirect_url();
			  exit;
    	}
	    $_SERVER['REQUEST_URI'] = empty($_SERVER['REQUEST_URI'])?(empty($_SERVER['HTTP_X_REWRITE_URL'])?'':$_SERVER['HTTP_X_REWRITE_URL']):$_SERVER['REQUEST_URI'];
		if ($goods['is_on_sale'] == '0'){
			$goods['goods_number'] = 0;
		}
		if(empty($_SESSION["WebUserInfo"]["sa_user"])){
			if ($goods['is_login'] && stripos($_SERVER['REQUEST_URI'],'.html')!==false){
				//如果这一产品需要登陆
				if(empty($blang) && empty($_COOKIE['WEBF-dan_num'])){
				   redirect_url();
				   exit;
				}else{
					if(stripos($goods['clang'],$blang)!==false && empty($_COOKIE['WEBF-dan_num'])){
					   redirect_url();
					   exit;
					}
				}
			}
		}
		if($goods['gifts_id']){
			$gifts = read_static_cache('gifts_c_key',2);
			$goods['gifts_name'] = empty($gifts[$goods['gifts_id']])?'':$gifts[$goods['gifts_id']]['gifts_name'];
		}
		$goods['promote_price'] = $goods['promote_price']?$goods['promote_price']:0;
		if($goods['presale_date_from']){
			if($goods['presale_date_from'] > gmtime()){
				$goods['presale_date_from'] = local_date($GLOBALS['_CFG']['date_format'], $goods['presale_date_from']);
			}else {
				$goods['presale_date_from'] ='';
			}
		}
		if($goods['presale_date_to']){
			if($goods['presale_date_to'] > gmtime()){
				$goods['presale_date_to'] = local_date($GLOBALS['_CFG']['date_format'], $goods['presale_date_to']);
			}else {
				$goods['presale_date_to'] = '';
			}
		}
		$goods['comment_count'] = $GLOBALS['db']->getOne('select count(comment_id) from '.COMMENT." where  status = 1 and id_value = '".$goods['goods_id']."'");
		$goods['goods_title'] = $attr_value.$goods['goods_title'];
		$goods['goods_title'] = varResume($goods['goods_title']);
		$goods['is_direct_sale']=0;
		$typeArray =  read_static_cache('category_c_key',2);		//此处只取原语言分类进行判断
        $shop_price          =          $goods['shop_price'];
		$price_save          =          ($goods["market_price"] - $shop_price);
        $goods['price_save'] =          $price_save;
		$price               =          ($goods["market_price"]==0)?1:$goods["market_price"];
        $goods['save_lv']    =           price_format($price_save/$price)*100;
		$cat_id		         =          $goods['cat_id'];
		if (empty($typeArray[$cat_id])) {
			redirect_url();
			exit;
        }else{
		}
		$Arr['bottom_keywords']=key_to_search_link($goods['keywords']);
		if(($cookie_cat_id != $cat_id) && ($cookie_cat_id!=0)){
			$sql = "select goods_id from ".GOODSCAT." where cat_id = '".$cookie_cat_id."' ";
			$mysql_goods_id = $db->getOne($sql);
			if ($mysql_goods_id == $goods_id) $cat_id = $cookie_cat_id;
		}
		//get new arrival
		$Arr['new_arrival'] = getnewarrival($cat_id,$goods_id);
		$Arr['review']=get_review($goods_id,1,3,'(is_top = 1)');  //有帮助的评论
		$filer_arr = array();
		$recent_review_num = 0;
		foreach ($Arr['review']['review_list'] as $key=>$value)
		{
			$filer_arr[] = $value['rid'];
			$recent_review_num++;

		}
		$filer =empty($filer_arr) ? '' : 'rid not in ('.implode(",",$filer_arr).')';
		$Arr['recent_review']=get_review($goods_id,1,5-$recent_review_num,$filer);  //有帮助的评论
		//print_r(get_review($goods_id,1,5-$recent_review_num,$filer));
		//$Arr['review']['review_list'] = array_merge($Arr['review']['review_list'],$Arr['recent_review']['review_list']);
		$Arr['inquiry']=get_inquiry($goods_id);  //评论
		$ppid = empty($typeArray[$cat_id]['parent_id'])?$cat_id:$typeArray[$cat_id]['parent_id'];
		$ppid = empty($typeArray[$ppid]['parent_id'])?$ppid:$typeArray[$ppid]['parent_id'];
		$ppid = empty($typeArray[$ppid]['parent_id'])?$ppid:$typeArray[$ppid]['parent_id'];
		$ppid = empty($typeArray[$ppid]['parent_id'])?$ppid:$typeArray[$ppid]['parent_id'];
		$parent_children = " g.cat_id in ('".$cat_id."') ";
		$children            =           get_children($cat_id);		              //得到同类商品
		if(!empty($typeArray[$cat_id]['parent_id'])){
			if (!empty($typeArray[$typeArray[$cat_id]['parent_id']]['cat_id'])){
				$parent_cat_id = $typeArray[$typeArray[$cat_id]['parent_id']]['cat_id'];
				$parent_children            = get_children($parent_cat_id);		          //得到同类父ID商品
				$same_parent_rand_cat_goods = '';//get_same_cat_goods($parent_children,'RAND(),'); //取得同父级的随机产品10个
				$Arr['same_parent_rand_cat_goods']   = $same_parent_rand_cat_goods;
				$Arr["top_parent"] = $typeArray[$typeArray[$cat_id]['parent_id']]['cat_name'];
				$cat_url_key = 'b';
				if ($typeArray[$typeArray[$cat_id]['parent_id']]['parent_id']!=0)$cat_url_key = 'c';

				$Arr["top_parent_url"] ='/Wholesale-'. $typeArray[$typeArray[$cat_id]['parent_id']]['url_title'].'-'.$cat_url_key.'-'.$typeArray[$typeArray[$cat_id]['parent_id']]['cat_id'].'.html';
				$catArr = read_static_cache('category_c',2);
			}
		}
		if(empty($catArr))$catArr='';
		$Arr['left_catArr']   = getDynamicTree(0,1);
        $goods['goods_style_name'] = add_style($goods['goods_title'], $goods['goods_name_style']);
        $goods['goods_desc']       = varResume($goods['goods_desc']);
		$goods_model = $goods['goods_name'];
		if (strpos($goods['goods_name'],',') !== false){
			$goods['goods_name'] = explode(',',$goods['goods_name']);
			$goods['goods_name'] =  $goods['goods_name'][0];
		}
		$Arr['pictures']     =         get_goods_gallery($goods_id);                    // 商品相册
		$goods['pro'] = get_properties($goods['goods_id'],$goods['url_title'],$typeArray[$goods['cat_id']]['cat_name']) ;
		$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title'],$atrid);
    	if( $goods['is_delete'] == '1' || $goods['is_on_sale'] == '0' )
		{
			//封面图片显示out of stock
			//$goods['goods_thumb'] = IMGCACHE_URL . 'images/out_of_stock_new.gif';
		    $goods['goods_grid'] = IMGCACHE_URL . 'images/normal/out_of_stock_new.gif';
		    $goods['goods_img'] = IMGCACHE_URL . 'images/normal/out_of_stock_new.gif';
		    $goods['original_img'] = IMGCACHE_URL . 'images/normal/out_of_stock_new.gif
';
		    //相册清空
		    $Arr['pictures'] = '';

		    $Arr['is_out_of_stock'] = true;
			$Arr['out_hot_sale'] = get_hot_product_by_cat_id($cat_id,'',5,$goods_id);

		    //商品描述图片替换
		    $goods['goods_desc'] = preg_replace('/<img\s*.+?src="([^"]+)"/i','<img src="' . IMGCACHE_URL . 'images/normal/out_of_stock_new.gif"',$goods['goods_desc']);
		}
        $Arr['goods'] =               $goods;
        $Arr['goods_id'] =            $goods['goods_id'];
        $Arr['promote_end_time'] =    $goods['gmt_end_time'];
        $Arr['fittings']     =         get_goods_fittings(array($goods_id));            // 配件
		if('CS0091101' == $goods['goods_sn'] || 'CS0064901' == $goods['goods_sn'] || 'CB0043002' == $goods['goods_sn'] || 'CS0094502' == $goods['goods_sn']) 		{
			$Arr['fittings_checked'] = true;
		}
        $Arr['saving']     =         0;
		$Arr['peijian_width']=  count($Arr['fittings']) * 118;
		$keywords     = htmlspecialchars($goods['keywords']);
		$keywords     = str_replace('，',',',$keywords);
		$keywords_arr = explode(',',$keywords);
		foreach ($keywords_arr as $k => $val){$keywords_arr[$k] = trim($val);}
		if (is_array($keywords_arr))
		$Arr['related_goods']    =  '';
        //meta设置
		$Arr['seo_title'] = str_replace(array('"','##goods_title'), array("'",$goods['goods_title']), $_LANG_SEO['goods']['title']);
		$Arr['seo_keywords'] = $_LANG_SEO['goods']['keywords'];
		$Arr['seo_description'] = str_replace(array('"','##goods_price','##goods_title'), array("'",$goods['shop_price'],$goods['goods_title']), $_LANG_SEO['goods']['description']);
		$Arr['logo_alt']   =   $goods['goods_title'].' Wholesale '.$_CFG['shop_name'];
		if($cat_id!="" && !empty($typeArray[$cat_id]["parent_id"])) $nav_title = getNavTitle($typeArray,$typeArray[$cat_id]["cat_id"]);
		$Arr['top_cat_id']=$typeArray[$cat_id]["parent_id"];
		$thisurl = creat_nav_url($typeArray[$cat_id]["url_title"],$cat_id);
        $cat_name = $typeArray[$cat_id]["cat_name"];
		if(!empty($typeArray[$cat_id]['cat_id'])) {
			$cat_name = get_cat_name_lang($typeArray[$cat_id]['cat_id']);
		}
		$cat_url  =  $thisurl;
		$nav_title = $nav_title.' &raquo;  '.$goods['goods_title'];
		$children =  "";
		if (!empty($typeArray[$cat_id]["parent_id"])){
			$parent_id = $typeArray[$cat_id]["parent_id"];
			$cat_parent_name = $typeArray[$parent_id]["cat_name"];
			$cat_parent_url = creat_nav_url($typeArray[$parent_id]["url_title"],$parent_id);
			$Arr['cat_parent_name']  =  $cat_parent_name;
			$Arr['cat_parent_url']  =  $cat_parent_url;
			$children = get_children($parent_id);
		}
		$Arr['cat_name']  =  $cat_name;
		$Arr['cat_url']  =  $cat_url;
		$Arr['nav_title']  =  $nav_title;
		$Arr['shop_name']  =  $_CFG['shop_name'];
        $catlist = array();
        $properties = get_goods_properties($goods_id);  // 获得商品的规格和属性
		$page = !empty($_GET['page'])?intval($_GET['page']):1;

		//判断商品是否是新商品编码
		$Arr['have_same_goods'] = 0;
		if( $goods['is_new_sn'] == 1 )
		{
			//获得同一商品不同规格的商品编号
			$same_goods_sn = get_same_goods_sn($goods['goods_sn']);

			//获得同一商品不同规格的商品列表
			if($same_goods_sn){
				$same_goods_id_list = get_same_goods_list($same_goods_sn);
				//判断是否存在同一商品不同规格的商品
				if(count($same_goods_id_list)>=1)
				{
					$Arr['have_same_goods'] = 1;
					//同一商品不同规格商品的所有规格属性数据
					$Arr['same_goods_list'] = get_same_goods_list_spec($goods_id,$same_goods_id_list);
				}
			}
		}
        $Arr['properties']    =        $properties['pro'];                              // 商品属性
        $Arr['specification'] =        $properties['spe'];                              // 商品规格
		$Arr['attrkey']       =        join('|',$properties['key']);
        $Arr['attribute_linked'] =     get_same_attribute_goods($properties);           // 相同属性的关联商品
        $volume_price_list   = get_volume_price_list($goods['goods_id'], '1');
		$volume_price_list  = get_price_compare($volume_price_list,$goods['market_price']);
        //VIP 价格
        if( !empty($Arr['user_rank']) )
        {
            foreach($volume_price_list as $vk=>$vv){
               if( $vk== 0  && $vv['format_price'] !='' ){
                  $min = $vv['format_price'];
               }elseif( $vv['format_price'] !='' ){
                  $min = min($min,$vv['format_price']);
               }
            }
            $Arr['vip_price']=$min;
        }
		 $goods['is_direct_sale']=0;
		 foreach ($volume_price_list as $price){
		 	if(intval($price['number'])==5){
		 		$goods['is_direct_sale']=1;
				$goods['bulk_price']=$price['format_price'];
		 	}
		 }
		//谷歌再营销
		$currency = get_currency();
		$pcat = getGoogleNavTitle($typeArray,$typeArray[$cat_id]["parent_id"]) . get_cat_name($typeArray[$cat_id]["cat_id"],$typeArray[$cat_id]["cat_name"]) . ' > ' . $goods['goods_title'];
		$exchange = read_static_cache('exchange',2);
		$google_tag_params = array(
			'prodid' => "'".$goods['goods_sn'] . $currency['lang'] ."'",
			'pagetype' => "'product'",
			'totalvalue' => round($goods['shop_price']*$exchange['Rate'][''.$currency['currency'].''],2),
			'currency' => "'". $currency['currency'] ."'",
			'pcat' => "'". $pcat ."'"
		);
		$Arr['google_tag_params'] = $google_tag_params;
		//判断是否为super star产品
		$super_star  = read_static_cache('super_star',1);    //后台推荐明显产品
		if($super_star && in_array($goods['goods_sn'],$super_star)){
			if (!IS_LOCAL) {
				unset($db);
				$db = get_slave_db();
			}
			$goods_hitnum = $db->getOne("select sum(hitnum) from ".GOODS_HITS." where goods_id = '".$goods_id."'"); //产品点击数
			$order_num = $db->getOne("select count(og.goods_id) from ".ODRGOODS." as og left join ".ORDERINFO." as oi on og.order_id = oi.order_id where oi.order_status>0 and oi.order_status < 9 and og.goods_id = '".$goods_id."'");
			$Arr['sale_num'] = $order_num + intval(($goods_hitnum/50))+500;
		}
		$Arr['goods'] =  $goods;
		$Arr['cat_id'] = $goods['cat_id'];
		$Arr['current_url'] =  "/m-users-a-sign.htm?ref=".urlencode($_SERVER["REQUEST_URI"]);
        $Arr['volume_price_list'] = $volume_price_list;    // 商品优惠价格区间
		$Arr['domain_url']   = $_CFG['creat_html_domain'];
		$Arr['his_list']    = insert_history();
        $Arr['hot_list']    = get_hot_goods($cat_id);
        $Arr['relate_list'] = get_relate_goods($cat_id);
		if($cur_lang != $default_lang) {
			$Arr['current_desc_img'] = $cur_lang . '_';
		} else {
			$Arr['current_desc_img'] = '';
		}
 	}
}

$db->autoReplace('eload_goods_hits_temp', array('goods_id' => $goods_id, 'daytime' => $nowtime, 'hitnum' => 1), array('hitnum' => 1));
$Arr['now_time'] = gmtime();           // 当前系统时间
$Arr['facebook_meta'] = '<meta property="og:title" content="' . $goods['goods_title'] . '" />'.
								'<meta property="og:type" content="product" />'.
								'<meta property="og:url" content="' . $goods['url_title'] . '" />'.
								'<meta property="og:image" content="' . $goods['goods_thumb'] . '" />'.
								'<meta property="og:site_name" content="dealsmachine" />'.
								'<meta property="fb:admins" content="100003136903167" />'.
								'<meta property="fb:page_id" content="125940057521729"/>'.
								'<meta property="og:description" content="I like this. Do you think I should buy it?"/>';

//历史记录
if (!empty($_COOKIE['WEB-history']))
{
	$history = explode(',', $_COOKIE['WEB-history']);
	array_unshift($history, $goods_id);
	$history = array_unique($history);
	while (count($history) > 5)
	{
		array_pop($history);
	}
	setcookie('WEB-history', implode(',', $history), gmtime() + 3600 * 24 * 30,'/',COOKIESDIAMON);
}
else
{
	setcookie('WEB-history', $goods_id, gmtime() + 3600 * 24 * 30,'/',COOKIESDIAMON);
}
require_once(ROOT_PATH . 'lib/class.function.php');
$cat_arr =  read_static_cache('category_c_key',2);
if($cur_lang != $default_lang) {
	$cat_arr =  read_static_cache($cur_lang . '_category_c_key',2);
}
$cat_id_59 = Func::get_category_children_ids($cat_arr, 59);
$cat_id_95 = Func::get_category_children_ids($cat_arr, 95);
$cat_ids = explode(',', $cat_id_59 . $cat_id_95 . '59,95');
if(in_array($cat_id, $cat_ids)) {
	$Arr['warranty'] = 1;
}


//==================================================华丽分割线==================================================
//private function
function getnewarrival($cat_id,$goods_id){
	$is_login_str = ' and g.is_login = 0 ';
    if (!empty($_COOKIE['WEBF-dan_num'])){
		$is_login_str = '';
	}
    $sql = 'SELECT g.goods_id, g.goods_title,g.goods_number, g.is_free_shipping, g.goods_thumb, g.goods_grid,g.cat_id, g.shop_price AS org_price, ' .
            'g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date,g.url_title ' .
            ' FROM ' . GOODS . ' AS g   ' .
            "WHERE  g.is_on_sale = 1 $is_login_str and g.is_alone_sale = 1 and g.is_delete = 0 AND g.cat_id = '$cat_id' And  g.goods_id <> '$goods_id'".
            " order by if( goods_number =0, 0, 1 ) DESC,goods_id desc LIMIT 5";
    $res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach($res as $row)
    {
        $arr[$row['goods_id']]['cat_id']     = $row['cat_id'];
        $arr[$row['goods_id']]['cat_id']     = $row['cat_id'];
        $arr[$row['goods_id']]['goods_title']   = $row['goods_title'];
        $arr[$row['goods_id']]['is_free_shipping']   = $row['is_free_shipping'];
        $arr[$row['goods_id']]['goods_number']   = $row['goods_number'];
        $arr[$row['goods_id']]['short_name']   = $row['goods_title'];
        $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']    = get_image_path($row['goods_id'], $row['goods_grid']);
        $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
        $arr[$row['goods_id']]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);
		$arr[$row['goods_id']]['review'] =get_review_rate($row['goods_id']);
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


/**
 * 获得指定同类商品
 *
 * @param   str
 * @return  array
 */
function get_same_cat_goods($cat_id,$is_rand = '')
{
	$a=array();
	return $a;
    $sql = 'SELECT g.goods_id, g.goods_title, g.goods_thumb,g. url_title, g.goods_grid,g.cat_id, g.shop_price AS org_price, ' .
                'g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            ' FROM ' . GOODS . ' AS g   ' .
            "WHERE  g.is_on_sale = 1 and g.is_login = 0 and g.is_alone_sale = 1 and g.is_delete = 0 AND g.cat_id = '$cat_id' ".
            " order by $is_rand if( goods_number =0, 0, 1 ) DESC, g.week2sale * g.shop_price desc,sort_order,click_count desc,goods_id desc LIMIT 20";
    $res = $GLOBALS['db']->query($sql);
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$row['goods_id']]['cat_id']     = $row['cat_id'];
        $arr[$row['goods_id']]['cat_id']     = $row['cat_id'];
        $arr[$row['goods_id']]['goods_title']   = $row['goods_title'];
        $arr[$row['goods_id']]['short_name']   = sub_str($row['goods_title'],30);
        $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_grid']    = get_image_path($row['goods_id'], $row['goods_grid']);
        $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
       // $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
       $arr[$row['goods_id']]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);

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


/**
 * 获得相同关键字
 *
 * @param   str
 * @return  array
 */
function get_related_goods($keywords_arr)
{
	if (!is_array($keywords_arr)) {
		return array();
	}else{
		$sql = '';
		foreach($keywords_arr as $v){
			if ($v != ''){
				$v = addslashes($v);
				if ($sql==''){
					$sql = " INSTR(CONCAT(',',g.keywords,','),',$v,')>0 ";
				}else{
					$sql .=" OR INSTR(CONCAT(',',g.keywords,','),',$v,')>0 ";
				}
			}
		}
		if ($sql !='') $sql =" AND ($sql) ";
		$sql = 'SELECT g.goods_id, g.goods_title, g.goods_thumb, g.goods_img,  g.cat_id, g.shop_price AS org_price,g.url_title, ' .
				'g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
				' FROM ' . GOODS . ' AS g   ' .
				"WHERE  g.is_on_sale = 1 and g.is_delete = 0  and g.is_login = 0 and g.is_alone_sale = 1 $sql ORDER BY goods_id DESC ".
				"LIMIT 12";
		$res = $GLOBALS['db']->query($sql);
		$arr = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$arr[$row['goods_id']]['goods_id']     = $row['goods_id'];
			$arr[$row['goods_id']]['goods_title']   = $row['goods_title'];
			$arr[$row['goods_id']]['short_name']   = sub_str($row['goods_title'],30);
			$arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
			$arr[$row['goods_id']]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
			$arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
		   // $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
		   $arr[$row['goods_id']]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);

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
}


/**
 * 获得购买过该商品的人还买过的商品
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_also_bought($goods_id)
{
    $sql = 'SELECT COUNT(b.goods_id ) AS num, g.goods_id, g.cat_id, g.goods_title, g.is_free_shipping, g.goods_thumb, g.goods_img, g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date,g.url_title ' .
            'FROM ' . ODRGOODS . ' AS a ' .
            'LEFT JOIN ' . ODRGOODS . ' AS b ON b.order_id = a.order_id ' .
            'LEFT JOIN ' . GOODS . ' AS g ON g.goods_id = b.goods_id ' .
            "WHERE a.goods_id = '$goods_id' AND b.goods_id <> '$goods_id' AND g.is_on_sale = 1  and g.is_login = 0  AND g.goods_number > 0  and g.is_alone_sale = 1  AND g.is_delete = 0 " .
            'GROUP BY b.goods_id ' .
            'ORDER BY num DESC ' .
            'LIMIT ' . $GLOBALS['_CFG']['bought_goods'];
    $res = $GLOBALS['db']->query($sql);

    $key = 0;
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$key]['goods_id']    = $row['goods_id'];
        $arr[$key]['goods_title']  = $row['goods_title'];
        $arr[$key]['is_free_shipping']  = $row['is_free_shipping'];
        $arr[$key]['short_name']  = sub_str($row['goods_title'], 70);
        $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$key]['goods_img']   = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$key]['shop_price']  = price_format($row['shop_price']);
	    $arr[$key]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);

        if ($row['promote_price'] > 0)
        {
            $arr[$key]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$key]['formated_promote_price'] = price_format($arr[$key]['promote_price']);
        }
        else
        {
            $arr[$key]['promote_price'] = 0;
        }

        $key++;
    }
    return $arr;
}

/**
 * 获得指定商品的销售排名
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  integer
 */
function get_goods_rank($goods_id)
{
    /* 统计时间段 */
    $period = intval($GLOBALS['_CFG']['top10_time']);
    if ($period == 1) // 一年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
    }
    elseif ($period == 2) // 半年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
    }
    elseif ($period == 3) // 三个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
    }
    elseif ($period == 4) // 一个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
    }
    else
    {
        $ext = '';
    }

    /* 查询该商品销量 */
    $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
        'FROM ' . ORDERINFO . ' AS o, ' .
            ODRGOODS . ' AS g ' .
        "WHERE o.order_id = g.order_id " .
        "AND o.order_status > 0 and o.order_status < 9 " .
        " AND g.goods_id = '$goods_id'" . $ext;
    $sales_count = $GLOBALS['db']->getOne($sql);

    if ($sales_count > 0)
    {
        /* 只有在商品销售量大于0时才去计算该商品的排行 */
        $sql = 'SELECT DISTINCT SUM(goods_number) AS num ' .
                'FROM ' . ORDERINFO . ' AS o, ' .
                    ODRGOODS . ' AS g ' .
                "WHERE o.order_id = g.order_id " .
                "AND o.order_status = 0 " .
                " GROUP BY g.goods_id HAVING num > $sales_count";
        $res = $GLOBALS['db']->query($sql);

        $rank = $GLOBALS['db']->nr($res) + 1;

        if ($rank > 10)
        {
            $rank = 0;
        }
    }
    else
    {
        $rank = 0;
    }

    return $rank;
}

/**
 * 获得商品选定的属性的附加总价格
 *
 * @param   integer     $goods_id
 * @param   array       $attr
 *
 * @return  void
 */
function get_attr_amount($goods_id, $attr)
{
    $sql = "SELECT SUM(attr_price) FROM " . GATTR .
        " WHERE goods_id='$goods_id' AND " . db_create_in($attr, 'goods_attr_id');
    return $GLOBALS['db']->getOne($sql);
}

//得到每级价格与市场价格的比较；
function get_price_compare($v_price_Arr,$c_price){
	$c_price = floatval($c_price);
	if (is_array($v_price_Arr)){
		foreach($v_price_Arr as $k => $v){
			$v_price_Arr[$k]['save_price']   = price_format($c_price - $v_price_Arr[$k]['price']);
			$v_price_Arr[$k]['save_percent'] = $c_price == 0?0:(price_format($v_price_Arr[$k]['save_price']/$c_price))*100;
		}
	}
	return $v_price_Arr;
}

//前台无限级分类
function getTree_goods($data, $pId)
{
	$html = '';
	$lianjie = '-c-';
	$i = 1;
	foreach($data as $k => $v)
	{
		if($v['parent_id'] == $pId)
		{
			if ($v['is_show'] == 1){
				$html .= "<li><a href=\"/Wholesale-".$v['url_title'].$lianjie.$v['cat_id'].".html\">".$v['cat_name']."</a>";
				$html = $html."</li>";
				if ($i>14) break;
				$i++;
			}
		}
	}
	return $html ? '<ul>'.$html.'</ul>' : $html ;
}

function hot_product($parent_children,$cat_id,$goods_id) {
	if (!empty($parent_children)){
		$sss = ' and  ('.$parent_children.') ';
	}
	$sql = 'SELECT gg.goods_id,gg.cat_id, gg.goods_title,gg.goods_name_style,gg.is_free_shipping,gg.shop_price,gg.goods_thumb,gg.sort_order,gg.url_title, ' .
		   ' gg.promote_price, gg.promote_start_date, gg.promote_end_date  ' .
	       ' FROM ' . GOODS . ' AS gg, (
			SELECT g.goods_id FROM ' . GOODS . ' as g WHERE g.is_on_sale = 1  and g.is_login = 0 AND g.is_alone_sale = 1  AND g.is_delete = 0  '.$sss. ' and g.goods_number > 0 order by  if( goods_number =0, 0, 1 ) DESC,(select SUM(o.goods_number * o.goods_price) AS turnover  from `eload_order_goods` as o,eload_order_info AS oi WHERE o.order_id = oi.order_id  and oi.order_status > 0 and oi.order_status < 9 AND oi.add_time >= '.gmstr2time('-1 month').' AND oi.add_time <= '.gmtime().' AND o.goods_id = g.goods_id group by goods_id ) desc limit 20) as o ' .
	      ' WHERE gg.goods_id = o.goods_id and gg.is_on_sale = 1  and gg.is_login = 0 AND gg.is_alone_sale = 1  AND gg.is_delete = 0  and gg.goods_number > 0'.
	      '  limit 6 ';
	$arr = array();
	foreach ($goods_res as $row){
		$arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],40);
		$arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
		$arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
	}
	return  $arr;
}
function affiliate_detect(){
	global $db;
	$linkid=empty($_GET["lid"])?0:intval($_GET["lid"]);     //来访的链接id
	if($linkid){   //链接id是否合法
		$l_arr["from_linkid"]=$linkid;
		$l_arr["HTTP_REFERER"]= empty($_SERVER['HTTP_REFERER'])?'':$_SERVER['HTTP_REFERER'];
		$l_arr["ips"]=real_ip();
		$l_arr["adddate"]=gmtime();
		$statusArr=$db->autoExecute(WJ_IP,$l_arr);  //记录来访IP
		setcookie ("linkid", "$linkid", time() + 3600*24*30, "", COOKIESDIAMON);
		$sql="update ".WJ_LINK." set visit_count=visit_count+1 where id=$linkid";
		$db->query($sql);    //点击计数器加1
	}
}

//获得相同商品不同规格的商品编码
function get_same_goods_sn($goods_sn)
{
	if(empty($goods_sn))
	{
		return false;
	}
	$xiangtong_goods_sn = substr($goods_sn,0,7);
	return $xiangtong_goods_sn;
}

//获得相同商品不同规格的商品列表
function get_same_goods_list($same_goods_sn)
{
	global $GLOBALS;
	$sql = "SELECT goods_id FROM " . GOODS . " WHERE is_new_sn = 1 AND goods_sn LIKE '" . $same_goods_sn . "%' AND goods_type =" . $GLOBALS['public_goods_type_id'] . " ORDER BY goods_id DESC";
	return $GLOBALS['db']->getCol($sql);
}

//获得当前商品的相同商品不同规格的商品的规格列表
function get_same_goods_list_spec($goods_id,$same_goods_id_list)
{
	global $GLOBALS, $cur_lang, $default_lang, $_LANG;;
	$same_goods_id_string = implode(",",$same_goods_id_list);

	//同一商品不同规格商品的所有规格属性数据
	if($cur_lang != $default_lang) {		//多语言
		$sql = "SELECT g.* , a.attr_name,a.isnes, a.attr_type, ga.attr_value_lang " .
		   " FROM " . GATTR . " AS g " .
		   ' LEFT JOIN ' . ATTR . ' AS a ON a.attr_id = g.attr_id ' .
		   " LEFT JOIN " . GOODSATTRLANG . " AS ga ON ga.attr_id = g.attr_id AND ga.attr_value = g.attr_value AND ga.lang = '" . $cur_lang . "' " .
		   " WHERE g.goods_id IN (" . $same_goods_id_string . ") AND a.disp = 1 AND g.attr_id IN (" . implode(",",$GLOBALS['public_goods_type_spec_id']) .") " .
		   " ORDER BY g.goods_id,a.sort_order,g.attr_id,g.attr_price";
	}
	else
	{
		$sql = "SELECT g.* , a.attr_name,a.isnes, a.attr_type " .
			   " FROM " . GATTR . " AS g " .
			   'LEFT JOIN ' . ATTR . ' AS a ON a.attr_id = g.attr_id ' .
			   " WHERE g.goods_id IN (" . $same_goods_id_string . ") AND a.disp = 1 AND g.attr_id IN (" . implode(",",$GLOBALS['public_goods_type_spec_id']) .") " .
			   " ORDER BY g.goods_id,a.sort_order,g.attr_id,g.attr_price";
	}
	$query = $GLOBALS['db']->query($sql);
	$same_goods_atrr_all = array();
	while ($rows = $GLOBALS['db']->fetchRow($query))
	{
		if($cur_lang != $default_lang) {		//多语言
			$rows['attr_value'] = empty($rows['attr_value_lang']) ? $rows['attr_value'] : $rows['attr_value_lang'];
		}
		$same_goods_atrr_all[$rows['goods_id']][$rows['attr_id']] = $rows;
		$same_goods_atrr_all[$rows['goods_id']][$rows['attr_id']]['spe_format_price'] =  price_format(abs($rows['attr_price']));
	}

	//获得当前商品详情页显示的可以选择的规格列表
	$Arr_same_goods_list = array();
	foreach ($same_goods_atrr_all as $key => $value)
	{
		if($key == $goods_id)
	 	{
	 		if( !empty($value[$GLOBALS['public_goods_type_spec_id']['size']]) )
	 		{
	 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['size']]['name'] = $_LANG['size'];
	 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['size']]['list'][$key] = $value[$GLOBALS['public_goods_type_spec_id']['size']];
	 		}
	 		if( !empty($value[$GLOBALS['public_goods_type_spec_id']['color']]) )
	 		{
	 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['color']]['name'] = $_LANG['color'];
	 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['color']]['list'][$key] = $value[$GLOBALS['public_goods_type_spec_id']['color']];
	 		}
	 	}
	 	else
	 	{
	 		//当前商品只有颜色规格，没有尺寸规格的（记录所有有颜色规格的同类商品）
	 		if( !empty($same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['color']]) && empty($same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['size']]) )
	 		{
	 			if( !empty($value[$GLOBALS['public_goods_type_spec_id']['color']]['attr_value']) )
		 		{
		 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['color']]['list'][$key] = $value[$GLOBALS['public_goods_type_spec_id']['color']];
		 		}
	 		}
	 		//当前商品只有尺寸规格，没有颜色规格的（记录所有有尺寸规格的同类商品）
	 		elseif ( empty($same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['color']]) && !empty($same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['size']]) )
	 		{
	 			if( !empty($value[$GLOBALS['public_goods_type_spec_id']['size']]['attr_value']) )
		 		{
		 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['size']]['list'][$key] = $value[$GLOBALS['public_goods_type_spec_id']['size']];
		 		}
	 		}
	 		//当前商品有尺寸规格，又有颜色规格的
	 		elseif ( !empty($same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['color']]) && !empty($same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['size']]) )
	 		{
	 			//与当前商品相同颜色，不同尺寸的商品的 颜色属性放到商品尺寸规格中
	 			if( ( ($value[$GLOBALS['public_goods_type_spec_id']['color']]['attr_value'] == $same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['color']]['attr_value']) && ($value[$GLOBALS['public_goods_type_spec_id']['size']]['attr_value'] != $same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['size']]['attr_value']) ) )
		 		{
		 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['size']]['list'][$key] = $value[$GLOBALS['public_goods_type_spec_id']['size']];
		 		}
		 		//与当前商品相同尺寸，不同颜色的商品的 颜色属性放到商品颜色规格中
		 		elseif( ($value[$GLOBALS['public_goods_type_spec_id']['size']]['attr_value'] == $same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['size']]['attr_value']) && ($value[$GLOBALS['public_goods_type_spec_id']['color']]['attr_value'] != $same_goods_atrr_all[$goods_id][$GLOBALS['public_goods_type_spec_id']['color']]['attr_value']) )
		 		{
		 			$Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['color']]['list'][$key] = $value[$GLOBALS['public_goods_type_spec_id']['color']];
		 		}
	 		}
	 	}
	}
	$Arr_same_goods_list_return = array();
	if( !empty($Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['color']]) )
	{
		$Arr_same_goods_list_return[$GLOBALS['public_goods_type_spec_id']['color']] = $Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['color']];
	}
	if( !empty($Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['size']]) )
	{
		$Arr_same_goods_list_return[$GLOBALS['public_goods_type_spec_id']['size']] = $Arr_same_goods_list[$GLOBALS['public_goods_type_spec_id']['size']];
	}
	return $Arr_same_goods_list_return;
}
/*
 * 产品跳转
 */
function goods_redirected($goods_url){
	$arr_goods =  read_static_cache('redirect301goods',1);
	preg_match('/best_(\d+).html/', $goods_url, $matches, PREG_OFFSET_CAPTURE);
	$goods_id = $matches[1][0];
	$goods_url = 'best_'. $goods_id .'.html';
	if(!empty($arr_goods[$goods_url])){
		Header("HTTP/1.1 301 Moved Permanently");
	    header("location:/".$arr_goods[$goods_url]);
	    exit();
	}
}

// 多语言分类名称
function get_cat_name_lang($cat_id) {
	global $db, $cur_lang, $defualt_lang;
	if(!empty($cur_lang) && !empty($cat_id)) {
		$sql = "SELECT cat_id, cat_name, lang FROM eload_category_muti_lang WHERE cat_id = ". $cat_id ." AND lang = '". $cur_lang ."'";
		$res = $db->selectInfo($sql);
		return $res['cat_name'];
	}
}

//cononical链接
function get_goods_cononical($goods_sn) {
	global $db, $cur_lang, $default_lang;
	if(!empty($goods_sn)) {
		$cononical = '';
		//同一款产品全部指向第一个产品
		$goods_sn_o = $goods_sn;
		$goods_sn = substr($goods_sn, 0, 7);
		$sql = "SELECT goods_id, goods_sn FROM ". GOODS ." WHERE goods_sn like '". $goods_sn ."%' ORDER BY goods_sn ASC";
		$res = $db->getAll($sql);
		if(count($res) > 1 && $goods_sn_o <> $res[0]['goods_sn']) {
			$goods_id  = $res[0]['goods_id'];
			$cononical = WEBSITE;
			if($cur_lang != $default_lang) {
				$cononical .= $cur_lang . '/';
			}
			$cononical .= "product-" . $goods_id . '.html';
			return $cononical;
		}
	} else {
		return false;
	}
}

if (!empty($_GET['no_menu']))$_MDL = 'goods_no_menu';
?>