<?
/*----------------------------------* 团购+---------------------------------*/

/*
$Tpl->caching = false;
$my_cache_id = $cur_lang ;
$my_cache_id = sprintf('%X', crc32($my_cache_id));
if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id))
{
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	require_once(ROOT_PATH . 'lib/class.page.php');
	require_once(ROOT_PATH . 'lib/inc.fun.php');
		
	$now_time = gmtime();
	$act      = empty($_GET['act'])?'':$_GET['act'];
	$user_id  = empty($_GET['user_id'])?0:intval($_GET['user_id']);
	//if(!IS_LOCAL)open_cdn_cache(); //开启页面CDN缓存
	if(!empty($user_id)){
		$_SESSION['groupdeals_recomm_user_id'] = $user_id;
		header("Location: /$cur_lang_url"."GroupDeals.html\n");
		exit;
	}
	if ($act == 'getlink'){
		if(empty($_SESSION['user_id'])){
			header("Location: /$cur_lang_url"."m-users-a-sign.htm?ref=".urlencode('/daily-deals/?act=getlink')."\n");
			exit;
		}
		$Arr['links']      = 'http://'.$_SERVER['HTTP_HOST'].'/'. $cur_lang_url .'GroupDealsLink'.$_SESSION['user_id'];
		$seo_title = 'Get link  - '.$_CFG['shop_title'];
		$seo_keywords = 'Get link  , '.$_CFG['shop_keywords'];
		$seo_description = 'Get link  , '.$_CFG['shop_desc'];
	}else{
		//301跳转 fangxin 2013-10-28
		if($_SERVER['REQUEST_URI'] == '/GroupDeals.html') {
			redirect_url(WEBSITE . 'DailyDeals.html', 301);
		}
		$sql = "select goods_title,cat_id,is_free_shipping,goods_id,is_groupbuy,shop_price,groupbuy_price,groupbuy_max_number,groupbuy_final_price,groupbuy_people_first_number,
		groupbuy_people_final_number,groupbuy_start_date,url_title,groupbuy_end_date,goods_img,groupbuy_ad_desc,groupbuy_bought,groupbuy_chengjiao_price  from eload_goods where is_groupbuy = 1 and
		groupbuy_end_date > ".$now_time." and groupbuy_start_date < ".$now_time." and is_delete =0  order by sort_order asc ";
		$goodsArr = $db->arrQuery($sql);
		$gidArr   = array();
		$timeArr  = array();
		$group_cat_name = '';
		foreach($goodsArr as &$row){
			//购买人数
			$buyers = get_groupbuyer($row['goods_id']);
			$row['now_price'] = get_groupbuy_price($row);
			$row['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
			$row['still_first_num'] = $row['groupbuy_people_first_number'] - $buyers;
			$row['still_final_num'] = $row['groupbuy_people_final_number'] - $buyers;
			$row['yousave'] = round($row['shop_price'] - $row['now_price'],4);
			$row['discount'] = round($row['yousave']/$row['shop_price'],4)*100;
			$row['buyers']   = $buyers;
			$row['url_title'] = get_details_link($row['goods_id'],$row['url_title']);			
			$row['left_time'] = $row['groupbuy_end_date'] - $now_time;
			$cat_name         = get_groupbuy_cat_name($row['cat_id']);
			$row['cat_name']  = $cat_name;
			$cat_name = check_cat_name_repeat($cat_name, $group_cat_name);
			if(!empty($cat_name)) {					
				$group_cat_name   .= $cat_name . ', ';
			}
			$gidArr[] = $row['goods_id'];
			$timeArr[] = $row['left_time'];
		}
		if(!empty($group_cat_name)) {
			$group_cat_name = substr($group_cat_name, 0, (strlen($group_cat_name)-2));
		}
		$Arr['group_cat_name'] = $group_cat_name;
		// 多语言 fangxin 2013/07/05
		if($cur_lang != $default_lang) {
			if(is_array($goodsArr)) {
				foreach($goodsArr as $key=>$value) {
					$goods_id = $value['goods_id'];
					$sql = 'SELECT g.*' .
							' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
							" WHERE g.goods_id = '". $goods_id ."'";	
					if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
						$goodsArr[$key]['goods_title']  = $row_lang['goods_title'];
					}
				}
			}			
		}				
		$Arr['goods']      = $goodsArr;
		$Arr['goods_ids']  = implode(',',$gidArr);
		$Arr['left_times'] = implode(',',$timeArr);
		$sql = "select goods_title,goods_id,is_groupbuy,shop_price,groupbuy_price,groupbuy_final_price,groupbuy_people_first_number,
	groupbuy_people_final_number,groupbuy_start_date,url_title,goods_thumb,groupbuy_end_date,goods_img,groupbuy_bought,groupbuy_chengjiao_price from eload_goods where is_groupbuy = 1 and
	groupbuy_end_date < ".$now_time." and is_delete = 0  order by groupbuy_end_date desc limit 5 ";
		$lastArr = $db->arrQuery($sql);
		foreach($lastArr as &$row1){
			//购买人数
			$buyers           = get_groupbuyer($row1['goods_id']);
			$row1['buyers']   = $buyers;
			$row1['groupbuy_bought'] = $buyers;
			$row1['goods_img'] = get_image_path($row1['goods_id'], $row1['goods_thumb']);
			$row1['now_price'] = ($buyers > $row1['groupbuy_people_final_number'])?$row1['groupbuy_price']:$row1['groupbuy_final_price'];
			$row1['url_title'] = get_details_link($row1['goods_id'],$row1['url_title']);
			$row1['groupbuy_bought'] = empty($row1['groupbuy_bought'])?0:$row1['groupbuy_bought'];
			$row1['groupbuy_chengjiao_price'] = $row1['groupbuy_price'];
		}
		$Arr['lastArr'] = $lastArr;
		//next deal
		$sql = "select goods_title,goods_id,is_groupbuy,shop_price,groupbuy_price,groupbuy_final_price,groupbuy_people_first_number,
	groupbuy_people_final_number,groupbuy_start_date,url_title,goods_thumb,groupbuy_end_date,goods_img,groupbuy_bought,groupbuy_chengjiao_price from eload_goods where is_groupbuy = 1 and
	groupbuy_start_date  > ".$now_time." and is_delete = 0   order by groupbuy_start_date desc limit 10 ";
		$nextArr = $db->arrQuery($sql);
		foreach($nextArr as $k=>$row2){
			//购买人数
			$buyers                             = get_groupbuyer($row2['goods_id']);
			$nextArr[$k]['url_title']           = get_details_link($row2['goods_id'],$nextArr[$k]['url_title']);
			$nextArr[$k]['groupbuy_start_date'] = local_date('M d',$nextArr[$k]['groupbuy_start_date']);
			$nextArr[$k]['goods_thumb']         = get_image_path($nextArr[$k]['goods_id'],$nextArr[$k]['goods_thumb']);
		}
		$Arr['nextArr']    = $nextArr;
		$seo_title = $_LANG_SEO['daily_deals']['title'];
		$seo_keywords = $_LANG_SEO['daily_deals']['keywords'];
		$seo_description = $_LANG_SEO['daily_deals']['description'];
	}
	$Arr['SpecialOffer_s'] = '_s';
	//meta设置
	$Arr['seo_title'] = $seo_title;
	$Arr['seo_keywords'] = $seo_keywords;
	$Arr['seo_description'] = $seo_description;		
}

function check_cat_name_repeat($cat_name, $group_cat_name) {
	if(!empty($group_cat_name)) {
		if(stristr($group_cat_name, $cat_name)) {			
		} else {
			return $cat_name;
		}
	} else {
		return $cat_name;
	}
}
*/


/********deals页面********/

!defined('INI_WEB') && exit('Access Denied');
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
//require_once(ROOT_PATH . 'lib/class.page.php');
require_once(ROOT_PATH . 'lib/class.page_new.php');
require_once(ROOT_PATH . 'lib/inc.fun.php');
$act = empty($_GET['act'])?'':$_GET['act'];
if($act == 'add_up'){
	add_ups();
}
$Arr['seo_title'] = 'Best Deals Online - Daily Deals and Discount';
$Arr['seo_keywords'] = 'DealsMachine.com has the Daily Deals you need for teblets, cell phoes, earphones and more. free shipping worldwide.';
$Arr['seo_description'] = 'Best Deals, Daily Deals';

if(empty($_GET['page'])) $_GET['page']=1;
$size = 3;
$deals=get_deals($_GET['page'],$size);  //deals 
$record_count = $deals['deals_count'];
$page = empty($_GET['page'])?1:intval($_GET['page']);
$page_count = ceil($record_count/$size);
if ($page > $page_count ) $_GET['page'] = $page_count;
if ($page < 1 ) $page = 1;
$start = ($page - 1) * $size;        
$Arr['deals'] = $deals;
$Arr["page_count"]  = $page_count;
$page_obj=new page(array('total' => $record_count,'perpage'=>$size));
$Arr["pagestr"]  = $page_obj->show();
$Arr['cool_hot_products'] = get_recommend_goods('is_cool',10,40,true,'hot');
$cool_hot_products_remove =array();
foreach($Arr['cool_hot_products'] as $c=>$res){
    $cool_hot_products_remove[] = $c;
}
$Arr['fun_hot_products'] = get_recommend_goods('is_fun',10,40,true,'hot',$cool_hot_products_remove);     
$Arr['deals_title'] = 'Gadget Deals - Electronic, Home, Outdoor & Accessories Deals | GearBest.com';
$Arr['deals_url']   =  DOMAIN.'/daily-deals/';

//获取deals 列表
function get_deals($page=1,$page_size=3) {
    global $db;
    $orderby = empty($_GET['orderby'])?'new':$_GET['orderby'];
    $inquiry = array();
    $where = '1';
    $now = gmtime();
    switch ($orderby){
    	case 'hot':
    		$order_by = 'ups desc';
    		$where .=" and expried_time >$now " ;
    		break;
    	case 'expiring':
    		$order_by = 'expried_time asc';
    		
    		$where .=" and expried_time >$now" ;
    		break;
    	default:
    		$order_by = 'add_time desc';
    }
    $from_row = ($page-1)*$page_size;
    $sql = "select count(*) as deals_count from ".DEALS. " where $where ";
    $deals_stat= $db->selectInfo($sql);
    $deals['deals_count'] = $deals_stat['deals_count'];
    $sql = 'select  * from '.DEALS ." where $where  order by $order_by  ";
    $deals_list = $db->arrQuery($sql);
    if(!empty($_SESSION['user_id'])){//点击过赞的deals
    	$click_deals = $db->arrquery("select deals_id from ".DEALS_UPS." where user_id='{$_SESSION['user_id']}'");
    	$click_deals = fetch_id($click_deals,'deals_id');
    }
    foreach ($deals_list as $k=>&$v) {
    	$v['expried_time'] = local_date('M d,Y h:m:s',$v['expried_time']);//过期时间
		!empty($click_deals[$v['deals_id']])&&$v['clicked'] =1;//点击过赞的deals
    	$v['pass_time'] = time_tran($v['add_time']);//添加时间
        $sql = "select * from ".DEALS_ITEM." where deals_id='{$v['deals_id']}'";
        $v['items'] = $db->arrquery($sql);  //deals item      
    }
    $deals['deals_list'] = $deals_list;
    return $deals;
}

/**
 * 增加赞
 */
function add_ups(){
	global $db,$_LANG;
	check_is_sign();//检查登录
	$user_id  =$_SESSION['user_id'];
	$deals_id = empty($_GET['deals_id'])?0:$_GET['deals_id'];
	$now = gmtime();
	if($deals_id ==0){
		return 'error(01)';
	}
	$sql  = "select count(1) as c from ".DEALS_UPS." where user_id='$user_id' and deals_id='$deals_id'";
	if($db->getone($sql)){
		echo $_LANG['have_voted'];
	}else{
		$sql ='insert ignore '.DEALS_UPS."(user_id,deals_id,add_time)values($user_id,$deals_id,$now)";
		$db->query($sql);
		$sql ='update '.DEALS." set ups=ups+1 where deals_id='$deals_id'";
		$db->query($sql);
		echo 'ok';
	}
	exit();
}

/**
 * 转换数据为X days
 */
function time_tran($the_time){
   $now_time = gmtime();
   $show_time = $the_time;
   $dur = $now_time - $show_time;
   if($dur < 60){
    return $dur.' seconds';
   }else{
    if($dur < 3600){
     return floor($dur/60).' minutes';
    }else{
     if($dur < 86400){
      return floor($dur/3600).' hours';
     }else{
      //if($dur < 259200){//3天内
       return floor($dur/86400).' days';
      //}else{
       //return $the_time;
      //}
     }
    }
   }
}
