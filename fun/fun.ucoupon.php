<?php
/**
 * 会员中心
*/
if (!defined('INI_WEB')){die('访问拒绝');}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/modules/ipb.php');
require_once(ROOT_PATH . 'lib/class.page.php');
global $cur_lang_url;
$user = new ipb($db);
/* 载入语言文件 */
include(ROOT_PATH . 'languages/' .$cur_lang. '/user.php');
$Arr['lang']   =  $_LANG;
$Arr['shop_name'] = $_CFG['shop_name'];
$user_id = empty($_SESSION['user_id'])?'':$_SESSION['user_id'];
$Arr['ArticleCatArr'] = get_foothelp_article(); //文章
if($_ACT == 'appliy'){
	$nav_title = ' &raquo;  Apply for cash coupon';
	$Arr['seo_title'] = $_CFG['shop_name'];
    $Arr['seo_title'] = ' Apply for cash coupon  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
	$Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
}
else if($_ACT == 'submit'){
 	//判断有没有登陆
	if(empty($user_id)){
		show_message($_LANG['sorry_you_should_first_register'], array(' Sign in',' Register ',$_LANG['Return_to_the_previous_page']), array('/m-users-a-sign.htm?ref='.urlencode('/'.$cur_lang_url.'m-ucoupon-a-submit.htm'),'/'.$cur_lang_url.'m-users-a-join.htm?ref='.urlencode('/'.$cur_lang_url.'m-ucoupon-a-submit.htm'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''), 'info');
	}
	
	//VIP会员不能申请
	$sql = "select user_rank from eload_users where user_id = '".$user_id."'";
	if($db->getOne($sql)){
		show_message($_LANG['sorry_the_cash_coupon'], array('My Account','Go to shopping'), array('/'.$cur_lang_url.'m-users.htm', './'), 'info');		
	}
	
	//查询普通会员是否已经下过单的
	/*$sql = "select count(*) from eload_order_info where user_id = '".$user_id."'  ";
	if($db->getOne($sql)){
		show_message('Sorry, cash coupon activity is only used for new register members.', array('My Account','Go to shopping'), array('/m-users.htm', './'), 'info');		
	}*/
	
	//判断用户是否是在一个月内注册的
	$prev_month = local_strtotime('-3 month');
	$sql = "SELECT count(*) FROM " . USERS ." WHERE user_id = '".$user_id."' AND reg_time <= " .$prev_month;
	if($db->getOne($sql)){
		show_message($_LANG['sorry_cash_coupon_activity'], array('My Account','Go to shopping'), array('/'.$cur_lang_url.'m-users.htm', './'), 'info');		
	}
	
    //查询普通会员是否已经申请过
    $sql  = 'SELECT user_id,coupon FROM ' . USERS_INFO . ' WHERE user_id=' . $_SESSION['user_id'];
    $info = $db->selectInfo($sql);
    
    if (empty($info)) {//未有用户信息
        $db->insert(USERS_INFO, 'user_id,coupon', "{$_SESSION['user_id']},1");
    }
    else {
        //已经申请过代金券了
        $info['coupon'] == 1 && show_message($_LANG['sorry_you_have_applied_for_it'], array('My Account', $_LANG['Return_to_Home']), array('/'.$cur_lang_url.'m-users.htm', './'), 'info');
        
        $db->update(USERS_INFO, 'coupon=1', 'user_id=' . $_SESSION['user_id']);
    }
	
	
    //$pcodeArr['users']       = $_SESSION['email'];
    $pcodeArr['users']       = $_SESSION['user_id'];
    $pcodeArr['create_time'] = gmtime() ;
    $pcodeArr['exp_time']    = gmtime()+24*3600*30*12 ;
    $pcodeArr['youhuilv']    = 5;
    $pcodeArr['goods']       = '';
    $pcodeArr['fangshi']     = 2;
    $pcodeArr['times']       = 1;
	$pcodeArr['is_applay']   = 1;
	
	for($i=1;$i<=10;$i++){
		
		$error_no = 0;
		do
		{
			$pcodeArr['code']   = randomkeys(8);
			$db->autoExecute(PCODE, $pcodeArr);
			$error_no = $db->Errno;			
			if ($error_no > 0 && $error_no != 1062)
			{
				die($GLOBALS['db']->errorMsg());
			}
		}
		while ($error_no == 1062); //如果是订单号重复则重新提交数据
	}
	
	
	show_message($_LANG['you_have_successfully_applied'] , array('My cash coupon',$_LANG['Return_to_Home']), array('/'.$cur_lang_url.'m-ucoupon-a-couponlist.htm', './'),'success');

	
}

//coupon list
else if($_ACT  == 'couponlist'){
	if(empty($user_id)){
		//未登录提交数据。非正常途径提交数据！
		header("Location: /". $cur_lang_url ."m-users-a-sign.htm\n");
		exit;
	}
	/*
    //查询普通会员是否已经申请过
    $sql  = 'SELECT user_id FROM ' . USERS_INFO . ' WHERE coupon=1 AND user_id=' . $_SESSION['user_id'];
    $info = $db->selectInfo($sql);
    if (empty($info)) {//未申请
        $Arr['not_apply_yet'] = true;
    }
    else {
        $sql = "select * from eload_promotion_code where users like '%".$_SESSION['email']."%' AND is_applay = 1";
		$couponArr = $db->arrQuery($sql);
        foreach ($couponArr as $key=>$row){
            if($row['exp_time']<gmtime()){
                $couponArr[$key]['time_out']=1;
            }else{
                $couponArr[$key]['time_out']=0;
            }
        }
        $Arr['couponArr'] = $couponArr ;
    }
    */

    //查询普通会员是否已经申请过
    $sql  = 'SELECT user_id FROM ' . USERS_INFO . ' WHERE coupon=1 AND user_id=' . $_SESSION['user_id'];
    $info = $db->selectInfo($sql);
    $cat_arr  = read_static_cache('category_c_key', 2);    //所有分类
    $now  = gmtime();
    $prev_month = local_strtotime('-1 month');
    $sql = "SELECT reg_time FROM " . USERS ." WHERE user_id = '".$_SESSION['user_id']."' AND reg_time <= " .$prev_month;
    $reg_time = $db->getOne($sql);
    if (empty($info) && empty($reg_time)) {//未申请
        $Arr['not_apply_yet'] = true;
    }
    $type = isset($_GET['type'])?$_GET['type']:1;  //查看类型 1为未使用未过期 2为已经使用 3为未使用已过期

    switch ($type) {
        case 1:
            $where = " AND cishu = 0 AND exp_time > '".$now."'";
            break;
        case 2:
            $where = " AND cishu = 1 ";
            break;
        case 3:
            $where = " AND cishu = 0 AND exp_time < '".$now."'";
            break;

    }
    $sql = "select * from eload_promotion_code where (users = '".$_SESSION['email']."' or users = {$_SESSION['user_id']})" .$where." order by id desc";
    $couponArr = $db->arrQuery($sql);
    foreach($couponArr as $key=>$row) {
        $couponArr[$key]['exp_time'] = date('Y/m/d',$row['exp_time']);
        $couponArr[$key]['need'] = '';
        if(!empty($row['cat_id'])) {
            $cat_info = explode(',',$row['cat_id']);
            foreach($cat_info as $val) {
                $couponArr[$key]['need'] = isset($couponArr[$key]['need'])?($couponArr[$key]['need'].' '."<a href='".$cat_arr[$val]['url_title']."'  style='color:#0062AD;text-decoration:underline'>".$cat_arr[$val]['cat_name']."</a>"):"<a href='".$cat_arr[$val]['url_title']."'>".$cat_arr[$val]['cat_name']."</a>";
            }
            $couponArr[$key]['need'] .= ' Only';
        }
        if($row['fangshi'] == 1 && empty($row['is_applay'])) {
            $couponArr[$key]['fangshi'] = 'Discount Coupon';
        }else {
            $couponArr[$key]['fangshi'] = 'Cash Coupon';
        }
        if($row['fangshi'] == 1) {
            $couponArr[$key]['youhuilv'] = $row['youhuilv']."% off";
        }else {
            if(strpos($row['youhuilv'],'-')) {
                $youhuilv = explode('-',$row['youhuilv']);
                $couponArr[$key]['youhuilv'] = $youhuilv[1].' USD';
                $couponArr[$key]['need'] .= !empty($couponArr[$key]['need'])?"<br> Subtotal over ".$youhuilv[0]." USD": "Subtotal over ".$youhuilv[0]." USD";
                if($youhuilv[1]==100&&$youhuilv[0]==100&&empty($row['source'])){
                    $couponArr[$key]['source'] = 'Most helpful Review';
                }
            }elseif($row['youhuilv'] ==5){
                 $couponArr[$key]['youhuilv'] ='5 USD';
                 $couponArr[$key]['need'] .= !empty($couponArr[$key]['need'])?"<br> Subtotal over 50 USD": "Subtotal over 50 USD";
                 if(empty($row['source'])){
                    $couponArr[$key]['source'] = 'New Registration';
                }
            }
            else{
                $couponArr[$key]['youhuilv'] = $row['youhuilv'].' USD';
            }
        }
        if($row['goods']) {
            $goods_sn = explode(',',$row['goods']);
            $goods_info = $db->arrQuery("select goods_id,url_title,goods_sn from ".GOODS." where goods_sn in ('".implode("','",$goods_sn)."')");
            $need = 'SKU: ';
            foreach($goods_info as $item){
                $url_title = get_details_link($item['goods_id'], $item['url_title']);
                $need .= "<a href='".$url_title."' style='color:#0062AD;text-decoration:underline'>".$item['goods_sn']."</a>,";
            }
            $need = trim($need,',');
            $couponArr[$key]['need'] .= !empty($couponArr[$key]['need'])?"<br>".$need.' Only':$need.' Only';
            unset($goods_info);unset($goods_sn);
        }
        if($type == 2) {
            $order_time = $db->getOne("SELECT add_time FROM ".ORDERINFO." where user_id = '".$user_id."' and promotion_code = '".$row['code']."'");
            if(!empty($order_time)){
                $couponArr[$key]['use_time'] = date('Y/m/d',$order_time);
            }
        }
    }    	
	$Arr['couponArr'] = $couponArr;
	$nav_title = ' &raquo;  My cash coupon';
    $Arr['seo_title'] = ' My cash coupon  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
	$Arr['action'] = $_ACT;
	$Arr['type'] = $type;
    $_ACT = 'users_inc';		
}




$_MDL = $_ACT;




function randomkeys($length)
{
	$key = '';
	$pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
	for($i=0;$i<$length;$i++)
	{
	   $key .= $pattern{mt_rand(0,35)};    //生成php随机数
	}
	return $key;
}

?>
