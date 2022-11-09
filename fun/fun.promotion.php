<?php
/*
+----------------------------------
* 专题促销
+----------------------------------
*/
$active = empty($_GET['active'])?'1':$_GET['active'];
$page = empty($_GET['page'])?'1':intval($_GET['page']);
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
$seo_title = $_LANG_SEO['index']['title'];
$seo_keywords = $_LANG_SEO['index']['keywords'];
$seo_description = $_LANG_SEO['index']['description'];
if($active == 30){
	save_gift(); //保存参加活动者的地址信息
}
if ($active ==75){
	$arr_time = get_active_time_info();
	$Arr['arr_time'] = $arr_time;
}
if($active == 240){
	$act = empty($_GET['a'])?'index':$_GET['a'];	
	$current_time = local_date('d/m/Y H:i:s A');								//获取当前时间
	list($date,$time)= explode(" ",$current_time);
	list($day,$month,$year) = explode("/",$date);
	if($day > 15)
	{
		$start_time = local_mktime(0,0,0,$month,16,$year);
		$end_day = date('t',$start_time);
		$end_time = local_mktime(23,59,59,$month,$end_day,$year);
	}
	else 
	{
		$start_time = local_mktime(0,0,0,$month,1,$year);
		$end_time = local_mktime(23,59,59,$month,15,$year);
	}	
	$Arr['daydeal_time_end'] = local_date('d/m/Y H:i:s',$end_time);
	$Arr['month_str'] = date('F');	
	if ($act == 'all_list')
	{
		require_once(ROOT_PATH . 'lib/class.page.php');
		require_once(ROOT_PATH . 'lib/lib.f.goods.php');		
		$search_review = empty($_GET['search_review']) ? '' : trim($_GET['search_review']);
		$where = empty($search_review) ? "" : " AND (nickname = '" . $search_review ."' OR subject = '" . $search_review ."') ";
		$page       = empty($_GET['page'])?1:intval($_GET['page']);
		$page_size  = 10;
		$sql = "SELECT COUNT(*) FROM " . REVIEW . " AS r, " . GOODS . " AS g " .
				" WHERE r.adddate >= " . $start_time . " AND r.adddate <= " . $end_time . " AND r.is_pass = 1 AND r.goods_id = g.goods_id " .$where;
		$record_count = $db->getOne($sql);
		$from_row = ($page-1)*$page_size;			
		$sql = "SELECT r.* , g.goods_title , g.url_title , g.goods_thumb FROM " . REVIEW . " AS r " . 
				" LEFT JOIN " . GOODS . " AS g  ON r.goods_id = g.goods_id " .
				" WHERE r.is_pass = 1 AND r.adddate >= " . $start_time . " AND r.adddate <= " . $end_time . 
				" AND r.goods_id = g.goods_id $where ORDER BY r.helpful_yes DESC LIMIT $from_row , $page_size";
		$review_list = $db->arrQuery($sql);
		foreach ($review_list as $k=>$v){
			$review_list[$k]['url_title']   = get_details_link($v['goods_id'],$v['url_title']);
			$review_list[$k]['goods_thumb'] = get_image_path($v['goods_id'], $v['goods_thumb'], true);
			$review_list[$k]['adddate']     = local_date('M-d/Y h:m:s',$review_list[$k]['adddate']);
			$review_list[$k]['pros']        = str_replace('\\', '', stripslashes($review_list[$k]['pros']));
			$review_list[$k]['cons']        = str_replace('\\', '', stripslashes($review_list[$k]['cons']));	
			$review_list[$k]['other_thoughts'] = str_replace('\\', '', stripslashes($review_list[$k]['other_thoughts']));					
			$sql = 'select * from '.REVIEW_PIC.' WHERE rid ='.$v['rid'];
			$review_list[$k]['pic'] = $db->arrQuery($sql);
			if(count($review_list[$k]['pic']) ==0 ) $review_list[$k]['pic']="";
			$sql = 'select * from '.REVIEW_VIDEO.' WHERE rid ='.$v['rid'];
			$review_list[$k]['video'] = $db->arrQuery($sql);
			if(count($review_list[$k]['video']) == 0) $review_list[$k]['video']="";
			$sql = 'select * from '.REVIEW_REPLY.' WHERE is_pass=1 and rid ='.$v['rid'];
			$review_list[$k]['reply'] = $db->arrQuery($sql);
			if(count($review_list[$k]['reply'])==0) $review_list[$k]['reply']="";
			$review_list[$k]['subject'] = str_replace('\\', '', stripslashes($review_list[$k]['subject']));
		}		
		$Arr['danqian_url'] = $_SERVER['PATH_INFO'];	//当前URL
		$Arr['review_list']	= $review_list;
		$Arr['search_review']	= $search_review;
		if($search_review)
		{
			$url = '/m-promotion-active-240-a-all_list-search_review-'.$search_review.'.html';
			$page=new page(array('total' => $record_count,'perpage'=>$page_size,'url'=>$url));
		}
		else
		{
			$page=new page(array('total' => $record_count,'perpage'=>$page_size));
		}
		$Arr["pagestr"]  = $page->show(5);
		$Arr['danqian_url'] = $_SERVER['PATH_INFO'];	//当前URL
	}
	else
	{
		//获奖历史记录
		$sql = "SELECT r.win_time , u.email , u.firstname FROM " . REVIEW_HELPFUL_WINTER . " AS r , " . USERS . " AS u WHERE r.user_id = u.user_id ORDER BY win_time DESC";
		$win_list = $db->arrQuery($sql);
		$win_str = '';
		if(!empty($win_list))
		{
			$win_str = '<div class="do"><div class="do_t">Do you want to be our winner?</div><div class="do_w"><ul>';
			foreach ($win_list AS $k => $v)
			{
				list($email_name,$email_type) = explode("@",$v['email']);
				$email_name = substr($email_name,0,5).'****';
				$v['email'] = $email_name.'@'.$email_type;
				$v['firstname'] = empty($v['firstname']) ? '&nbsp;' : $v['firstname'];
				$win_str .= '<li><span>'.$v['win_time'].'</span><span class="name">'.$v['firstname'].'</span><span class="mail">'.$v['email'].'</span></li>';
			}
			$win_str .= '</ul><div class="clear"></div></div></div>';
		}
		$Arr['win_str'] = $win_str;
		
		//Top评论
		$sql = "SELECT r.* , g.goods_title , g.url_title , g.goods_thumb FROM " . REVIEW . " AS r " . 
				" LEFT JOIN " . GOODS . " AS g  ON r.goods_id = g.goods_id " .
				" WHERE r.is_pass = 1 AND r.adddate >= " . $start_time . " AND r.adddate <= " . $end_time . 
				" AND r.helpful_yes > 0 AND r.goods_id = g.goods_id ORDER BY r.helpful_yes DESC LIMIT 10";
		$review_list = $db->arrQuery($sql);
		foreach ($review_list as $k=>$v){
			$review_list[$k]['url_title'] = get_details_link($v['goods_id'],$v['url_title']);
			$review_list[$k]['goods_thumb'] = get_image_path($v['goods_id'], $v['goods_thumb'], true);
			$review_list[$k]['adddate']=local_date('M-d/Y h:m:s',$review_list[$k]['adddate']);
			$review_list[$k]['pros'] = str_replace('\\', '', stripslashes($review_list[$k]['pros']));
			$review_list[$k]['cons'] = str_replace('\\', '', stripslashes($review_list[$k]['cons']));	
			$review_list[$k]['other_thoughts'] = str_replace('\\', '', stripslashes($review_list[$k]['other_thoughts']));			
			$sql = 'select * from '.REVIEW_PIC.' WHERE rid ='.$v['rid'];
			$review_list[$k]['pic'] = $db->arrQuery($sql);
			if(count($review_list[$k]['pic'])==0) $review_list[$k]['pic']="";
			$sql = 'select * from '.REVIEW_VIDEO.' WHERE rid ='.$v['rid'];
			$review_list[$k]['video'] = $db->arrQuery($sql);
			if(count($review_list[$k]['video'])==0) $review_list[$k]['video']="";
			$sql = 'select * from '.REVIEW_REPLY.' WHERE is_pass=1 and rid ='.$v['rid'];
			$review_list[$k]['reply'] = $db->arrQuery($sql);
			if(count($review_list[$k]['reply'])==0) $review_list[$k]['reply']="";
			$review_list[$k]['subject'] = str_replace('\\', '', stripslashes($review_list[$k]['subject']));
		}
		$Arr['review_list']	= $review_list;
		
		//推荐商品
		$cate_id = array(
						array(630),
						array(1747),
						array(1704,1700,1697),
						array(191,448),
						array(582,647,106)
					);
		$tuijian_goods = array();
		foreach($cate_id as $key=>$value)
		{
			$arr = array();
			$sql = "SELECT goods_id , goods_title , goods_grid , shop_price , promote_price, promote_start_date, promote_end_date , url_title FROM " . GOODS . "  WHERE cat_id IN (" . implode(",",$value) . ") ORDER BY week2sale DESC , goods_id DESC LIMIT 10";
			$res = $GLOBALS['db']->arrQuery($sql);
			foreach ($res as $key=>$row)
		    {
		    	$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
		        $promote_price = price_format($promote_price);
		        $arr[$key]['goods_id']         = $row['goods_title'];
				$arr[$key]['goods_name']         = $row['goods_title'];
				$arr[$key]['shop_price']  = $promote_price > 0 ? $promote_price : price_format($row['shop_price']);
				$arr[$key]['goods_grid']  = get_image_path($row['goods_id'], $row['goods_grid']);
				$arr[$key]['url_title']   = get_details_link($row['goods_id'], $row['url_title']); 	
		    }
		    $rand_id = array_rand($arr,1);
			$tuijian_goods[] = $arr[$rand_id];
		}
		$Arr['tuijian_goods'] = $tuijian_goods;
		$Arr['danqian_url'] = $_SERVER['PATH_INFO'];	//当前URL
	}
	$Arr['act'] = $act;
	
}

$active_arr = explode('_',$active);

//活动专题，英文URL fangxin 2013-10-29
if(!empty($active_arr[0])) {
	if(is_string($active_arr[0])) {
		$sql = "SELECT id, redirection_name FROM eload_activity WHERE redirection_name = '". $active_arr[0] ."'";
		$res = $db->arrQuery($sql);
		foreach($res as $key=>$value) {
			$active_arr[$key] = $value['id'];
		}
	}
}

foreach ($active_arr as $aid){
	$aid=intval($aid);
	if(is_integer($aid)){
		$arr_goods = get_active_goods($aid,1000);
		$arr_name = 'arr_active_'.$aid;
		$Arr[$arr_name]=$arr_goods;
	}
}

//黑色星期五专题相关SEO
$sql = 'SELECT * FROM eload_activity WHERE id='.intval($active_arr[0]);
$activity_rs = $GLOBALS['db']->arrQuery($sql);
if(!empty($activity_rs)){
	if(intval($active_arr[0]) == 301) {
		$Arr['seo_title'] = 'Black Friday 2013 - Black Friday Deals And Sales | dealsmachine.com';
	}
	$seo_title = $activity_rs[0]['title'];
	$seo_keywords = $activity_rs[0]['keywords'];
	$seo_description = $activity_rs[0]['description'];
}
//meta
$Arr['seo_title'] = $seo_title;
$Arr['seo_keywords'] = $seo_keywords;
$Arr['seo_description'] = $seo_description;


//==================================================华丽分割线==================================================
/**
 * 获取欧洲杯活动时间
 */
function get_active_time_info(){		
		//date_default_timezone_set('UTC');
		$C1_t1 = 4*3600;  //第一场 ，开始
		$C1_t2 = 16*3600; //第一场 ，结束
		$C2_t1 = 16*3600; //第二场 ，开始
		$C2_t2 = 4*3600;  //第二场 ，结束		 	
		$h = date('H')-date('Z')/3600;
		$h = $h % 24;	
		$m = date('i');
		$s = date('s');	
		$now_second = $h*3600+$m*60+$s;		
		if($now_second >= $C1_t1 && $now_second<$C1_t2){ //第一场
			$next_active_time = 0;
			$active_left =$C1_t2-$now_second;
		}else {  // 第二场开始
			$next_active_time = 0;
			$active_left =$C2_t2 - $now_second;			
		}
		if($active_left<0)$active_left += 12*3600;
		if($active_left<0)$active_left += 12*3600;
		return array('next_active_time'=>$next_active_time,'active_left'=>$active_left);
}

/**
 *获取活动下的商品
 */
function get_active_goods($activeid='1', $limit = '5')
{
	global $db;
	 if ( $activeid=='182'){
		check_is_sign();
    }	 	
	if(!is_integer($activeid)) return;	
	$sql = 'SELECT * FROM eload_activity WHERE id='.$activeid;
	$activity_info = $GLOBALS['db']->selectinfo($sql);
	if(empty($activity_info))return;
	if ($activeid=='9') //New Arrival
    {
        $cat_id = get_children(59);//手机
   	    $sql = 'SELECT s.is_24h_ship,g.goods_number,g.goods_id,cat_id, goods_sn,goods_title,original_img,goods_img,goods_name_style,is_free_shipping,shop_price,promote_price,promote_start_date, promote_end_date,goods_thumb,market_price,goods_grid,sort_order,url_title ' .
       ' FROM ' . GOODS . ' AS g left join ' .GOODS_STATE.' s on g.goods_id=s.goods_id '.
       " WHERE is_on_sale = 1 AND is_alone_sale = 1  and $cat_id AND is_delete = 0 ".
       ' ORDER BY g.goods_id DESC LIMIT '.$limit;
   } else {
		if(!empty($activity_info['act_goods_list'])){
        	$goods_list_sn = "'".str_replace(',',"','",$activity_info['act_goods_list'])."'";       
   		}else{
   			$goods_arr =  $db->arrQuery("select goods_sn from ".GOODS." where activity_list like '%,$activeid,%' order by sort_order");
   			$goods_list_sn = arr2str($goods_arr, 'goods_sn');
   		}   		
   		if(empty($goods_list_sn))return array();
        $sql = 'SELECT s.is_24h_ship,g.goods_number,s.sold,g.goods_id,goods_sn,cat_id, goods_title,original_img ,goods_img,goods_name_style,is_free_shipping,shop_price,promote_price,promote_start_date, promote_end_date,goods_thumb,market_price,goods_grid,sort_order,url_title ' .       
        ' FROM ' . GOODS . ' AS g left join ' .GOODS_STATE.' s on g.goods_id=s.goods_id '.
       " WHERE is_on_sale = 1 AND is_alone_sale = 1 AND goods_sn in($goods_list_sn) AND is_delete = 0 ";
      if(!empty($goods_list_sn))$sql .=" ORDER BY  FIND_IN_SET(g.goods_sn, '".str_replace("'",'',  $goods_list_sn)."')";
      $sql .=" LIMIT $limit";
   }
    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($goods_res as $row){
        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        $arr[$row['goods_id']]['goods_sn']         = $row['goods_sn'];
        $arr[$row['goods_id']]['goods_number']         = $row['goods_number'];
        if(!empty($row['sold']))$arr[$row['goods_id']]['sold']         = $row['sold'];
        $arr[$row['goods_id']]['is_24h_ship']         = $row['is_24h_ship'];
        $arr[$row['goods_id']]['goods_title']      = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
        $arr[$row['goods_id']]['short_name']       = $row['goods_title'];
        $arr[$row['goods_id']]['goods_grid']       = get_image_path($row['goods_id'], $row['goods_grid'], true);
        $arr[$row['goods_id']]['original_img']       = get_image_path($row['goods_id'], $row['original_img'], true);
        $arr[$row['goods_id']]['goods_img']       = get_image_path($row['goods_id'], $row['goods_img'], true);
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
        	 $arr[$row['goods_id']]['shop_price']       = price_format($promote_price);
        }
        if ( $activeid=='75' && $promote_price==0)
        {
       	 	if(empty($_GET['test']))unset($arr[$row['goods_id']]);
        }
        if ( $activeid=='11' )
        {
            $volume_price_list   = get_volume_price_list($row['goods_id'], '1');
            $arr[$row['goods_id']]['volume_price']=$volume_price_list[1]["format_price"];
        }
		$review = $db->selectinfo('select avg(rate_overall) as rating,count(*) as count from '.REVIEW." r where goods_id= ".$row['goods_id']." and is_pass=1");             
		$arr[$row['goods_id']]['review']['count']         = empty($review['count'])?0:$review['count'];
        $arr[$row['goods_id']]['review']['rating']             =   empty($review['rating'])?0:round($review['rating'],1);     		
        if ($arr[$row['goods_id']]['review']['count']  > 0){
        	$arr[$row['goods_id']]['review']['shows'] = "<img src='".showRate($review['rating'])."'> <a  href='m-review-a-view_review-goods_id-".$row['goods_id'].".htm'>".$review['count']." reviews</a>";
        }        
		if(!empty($_GET['test'])) {
			if($arr[$row['goods_id']]['review']['count'])exit();
		}
    }
    return $arr;
}

/**
 * 保存参加活动者的地址信息
 */
function save_gift(){
	$act = empty($_GET['act'])?'':$_GET['act'];
	if($act != 'save')return ;
	global $db;
	$gift_code  = $_POST['gift_code']?$_POST['gift_code']:'';
	$email		= $_POST['email']?$_POST['email']:'';
	$firstname	= $_POST['firstname']?$_POST['firstname']:'';
	$lastname	= $_POST['lastname']?$_POST['lastname']:'';
	$phone		= $_POST['phone']?$_POST['phone']:'';
	$city		= $_POST['city']?$_POST['city']:'';
	$states		= $_POST['states']?$_POST['states']:'';
	$country_code= $_POST['country_code']?$_POST['country_code']:'';
	$address	= $_POST['address']?$_POST['address']:'';
	$zip		= $_POST['zip']?$_POST['zip']:'';
	if(empty($gift_code)||empty($email)||empty($firstname)||empty($lastname)||empty($phone)||empty($city)||empty($states)||empty($country_code)||empty($address)||empty($zip)){
		show_message($_LANG['please_enter']);
	}
	if(!is_email($email)){
		show_message($_LANG['please_enter_correct']);
	}
	if($db->getOne("select count(*) from eload_promotion_address where email='$email'")){
		show_message($_LANG['everyone_can_get_one']);
	}
	$db->autoExecute('eload_promotion_address',$_POST);
	header("Location:/m-promotion-active-38-t-1.html");
	exit();
}