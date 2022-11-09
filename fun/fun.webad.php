<?php

/**
 * 广告联盟
*/
if (!defined('INI_WEB')){die('Access denied');}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/modules/ipb.php');
require_once(ROOT_PATH . 'lib/class.page.php');
require_once(ROOT_PATH . 'lib/class.affiliate_stat.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
require_once(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');
global $cur_lang, $cur_lang_url;
$callback = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : '';
$user = new ipb($db);
$Arr["shop_title"] = "Affiliates program";
$user_id = empty($_SESSION['user_id'])?'':$_SESSION['user_id'];


if ($_ACT == 'add_link_auto')  //保存链接
{
	$callback = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : '';
	if ($user_id == '')
	{
		$msg = "Please <a href='/m-users-a-sign.htm'> Sign in</a>";
		echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
		exit;		
	}
	//$linkid = empty($_GET["id"])?'':intval($_GET["id"]);
	$pid = empty($_GET["pid"])?'':$_GET["pid"];
	if(!$pid)empty($_GET["pid"])?'':$_GET["pid"];
	if(!$pid){
		$msg = "product info error";
		echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
		exit;
	}
	//if(!$linkid)empty($_POST["linkid"])?'':$_POST["linkid"];
	//echo $_GET["linkid"];
	$sql= "select * from ".WJ_LINK ." where pid=$pid and user_id=$user_id";
	
	$link_info = $db->selectInfo($sql);
	if(!$link_info){
		$sql= "select goods_id,goods_name,goods_title,goods_thumb from ".GOODS ." where goods_id=$pid";
		$goods = $db->selectInfo($sql);	
		if(!$goods)	{
			$msg = "product info error";
			echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
			exit;
		}
		$link_name = addslashes($goods['goods_name']);
		$link_text = addslashes($goods['goods_title']);
		//$link_desc = $goods['goods_name'];
		//$link_url  = "http://".$_SERVER['SERVER_NAME']."/".get_details_link($goods['goods_id']);
		$link_url  = get_details_link($goods['goods_id']);
		
		$img       = get_image_path(false, $goods['goods_thumb']);
		
		
		$link["link_name"]=$link_name;
		$link["link_text"]=$link_text;
		$link["pid"]=$pid;
		//$link["link_desc"]=$link_desc;
		$link["link_url"]=$link_url;
			
		$link["adddate"]=gmtime();
		$link["last_modify"]=gmtime();
		$link["img"]=$img;
		$link["user_id"]=$user_id;
		$db->autoExecute(WJ_LINK, $link);
		$sql= "select * from ".WJ_LINK ." where pid=$pid and user_id=$user_id";
		$link_info = $db->selectInfo($sql);
		//print_r($sql);
	}

	if($link_info){
		$str="This product's Affiliates link:<br/>";
		//$str.="<b>http://".$_SERVER['SERVER_NAME']."/m-webad-a-r-lid-".$link_info['id'].".htm</b>";
		/*if (preg_match ('/\?/', $link_info['link_url'])) {
			$str.="<b>". $link_info['link_url'] ."&lkid=" . $link_info['id'] . "</b>";	
		}
		else
		{
			$str.="<b>". $link_info['link_url'] ."?lkid=" . $link_info['id'] . "</b>";	
		}*/
        $s   = false === strpos($link_info['link_url'], '#') ? '#' : '';
        $str = '<b>' . $link_info['link_url'] . $s . 'lkid=' . $link_info['id'] . '</b>';
		echo  $callback . '('.json_encode(array('ms'=>$str )).')';
		exit;		
	}
	exit();
}


if ($_ACT == 'r')  //网站推广入口
{
	$linkid=empty($_GET["lid"])?0:intval($_GET["lid"]);     //来访的链接id
	
	//die();
	if(!$linkid){   //链接id是否合法
		header("Location:/");
		exit();
	}
	
	/*$l_arr["from_linkid"]=$linkid;   
	$l_arr["HTTP_REFERER"]= empty($_SERVER['HTTP_REFERER'])?'':$_SERVER['HTTP_REFERER'];   
	$l_arr["ips"]=real_ip();
	$l_arr["adddate"]=gmtime();
	$statusArr=$db->autoExecute(WJ_IP,$l_arr);  //记录来访IP*/
	
// set the expiration date to one hour ago
	//setcookie ("linkid", "$linkid", time() - 3600*24*30);    //保存链接ID到Cookie
	/*setcookie ("linkid", "$linkid", time() + 3600*24*30, "", COOKIESDIAMON);
	
	$sql="update ".WJ_LINK." set visit_count=visit_count+1 where id=$linkid";
	$db->query($sql);    //点击计数器加1*/
	
	
	$sql="select link_url from ".WJ_LINK." where id=$linkid";
	$link_url=$db->getOne($sql);     //取出链接ID对应的URL
	
	
	if($link_url){
		$url = $link_url.'?';
	}else {
		$url="/?m=index";
	}
	
	
	$thispar = get_url_parameters($_GET,array('r','m','lid','webad','a','isour'));
	$url = $url.$thispar;
	
	header("Location: $url\n");   //转到链接ID对应的URL
	exit();
}


/* 载入语言文件 */
include(ROOT_PATH . 'languages/' .$cur_lang. '/user.php');
$Arr['lang']   =  $_LANG;
$Arr['shop_name'] = $_CFG['shop_name'];





$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);

if ($user_id == '')
{
	header("Location: /$cur_lang_url"."m-users-a-sign.htm\n");
	exit;
}



if ($_ACT == 'introduce')  //网站推广介绍
{
	$Arr['act'] = $_ACT;	
	$user_type = $db->getOne("select user_type from ".USERS." where user_id = '$user_id'");
	if($user_type == '1'){
		header("Location: /$cur_lang_url"."m-webad-a-is_checking.htm");
		exit();
	}elseif ($user_type == '2') {
		header("Location: /$cur_lang_url"."m-webad-a-recommend_link.htm");
		exit();
	}
	//echo $_ACT;
	//echo $Arr['act'];
	//$_MDL = 'webad_'.$_ACT;
	$_MDL = "users_inc";
}

//echo $_ACT;
 
if ($_ACT == 'add_link')  //保存链接
{
	//echo 11111;
	$linkid = empty($_GET["linkid"])?'':intval($_GET["linkid"]);
	$pid = empty($_GET["pid"])?'':$_GET["pid"];
	if(!$pid)empty($_POST["pid"])?'':$_POST["pid"];
	if(!$linkid)empty($_POST["linkid"])?'':$_POST["linkid"];
	//echo $_GET["linkid"];
	$link_name = empty($_POST["link_name"])?'':$_POST["link_name"];
	$link_text = empty($_POST["link_text"])?'':$_POST["link_text"];
	$img = empty($_POST["img"])?'':$_POST["img"];
	$link_desc = empty($_POST["link_desc"])?'':$_POST["link_desc"];
	$link_url = empty($_POST["link_url"])?'':$_POST["link_url"];
	$img = empty($_POST["img"])?'':$_POST["img"];
	
	if($linkid !='' && $link_name !=''){
		$link["link_name"]=$link_name;
		$link["link_text"]=$link_text;
		$link["img"]=$img;
		$link["link_desc"]=$link_desc;
		$link["link_url"]=$link_url;
		$link["last_modify"]=gmtime();
		//print_r($link);
		//exit();
		$db->autoExecute(WJ_LINK, $link,"UPDATE","id='$linkid'");
		echo ("<script type='text/javascript'>alert('link have been saved(1)');opener.location.reload();window.close();</script>");
		exit();
	}else {
		if($link_name){
			$link["link_name"]=$link_name;
			$link["link_text"]=$link_text;
			$link["link_desc"]=$link_desc;
			$link["link_url"]=$link_url;
			
			$link["adddate"]=gmtime();
			$link["last_modify"]=gmtime();
			$link["img"]=$img;
			$link["user_id"]=$user_id;
			$db->autoExecute(WJ_LINK, $link);
			if($pid){    //如果是从产品页过来就跳转到链接列表
				header("location:m-webad-a-links_list.html");
			}else {  //否则关闭当前页面
				echo ("<script type='text/javascript'>alert('link have been saved(1)');opener.location.href='m-webad-a-links_list.htm';window.close();</script>");
			}
			exit();
		}
	}
	if($pid){
			$sql = "select goods_id,goods_name,goods_title,goods_thumb from ".GOODS." where goods_id='$pid'";
			$goods = $db->selectInfo($sql);
			$link = array();
			$link["link_name"] = stripslashes($goods["goods_name"]);
			$link["img"] = "http://".$_SERVER['SERVER_NAME']."/".$goods["goods_thumb"];
			$link["link_name"] = stripslashes ($goods["goods_name"]);
			$link["link_text"] = stripslashes($goods["goods_title"]);
			$link["link_url"] = get_details_link($goods['goods_id']);
			$link["last_modify"]=gmtime();

	}else {
		$link = $db->selectInfo("select * from ".WJ_LINK." where id='$linkid'");
	}
	//echo ("select * from ".WJ_LINK." where id='$linkid'");
	
	//print_r($link);
	$Arr["link"] = $link;
	$Arr["pid"] = $pid;
	$Arr['act'] = $_ACT;
	$_MDL = $_MDL.'_links_list';
}
if ($_ACT == 'del_link')  //删除链接
{
	$linkid = empty($_POST["linkid"])?'':$_POST["linkid"];
	if(!$linkid){
		header("location:".$_SERVER['HTTP_REFERER']);
		exit();
	}
	$ids=is_array($linkid)?implode(',',$linkid):$linkid;
	$db->delete(WJ_LINK,"id in($ids) and user_id='$user_id'");
	header("location:".$_SERVER['HTTP_REFERER']);
	exit();
}
if ($_ACT == 'links_list')  //链接列表
{
	
	$nav="Affiliate links";
	include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
	$Arr['act'] = $_ACT;
	$_MDL = $_MDL.'_links_list';
	
	$unique_id = $db->getOne("SELECT min(id) FROM " .WJ_LINK. " WHERE state=1 and `user_id` ='$user_id'") ;
	$Arr['unique_id'] = $unique_id;
	$record_count = $db->getOne("SELECT COUNT(*) FROM " .WJ_LINK. " WHERE state=1 and `user_id` ='$user_id'");
	

    $size = 10;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	
	$Arr_links = get_user_links($user_id,$size,$start,$unique_id);
	//print_r($Arr_links);
	//$_GET['x'] = '2';
	//$com_rate = $db->getOne("select com_rate from ".USERS.'');
	
	$prev_time = local_strtotime('-1 month');		//前一个月的当前时间戳
	$day_num = local_date('t',$prev_time);		//前一个月天数
	$date_str = local_date('Y-m',$prev_time);		//前一个月字符串
	$start_time = local_strtotime($date_str."-1 0:0:0");		//前一个月月初开始时间戳
	$end_time = local_strtotime($date_str."-" . $day_num ." 23:59:59");		//前一个月月末结束时间戳
	//echo date('Y-m-d',$start_time);
	//$now_time = gmtime()							//当月的当前时间戳
	$now_time = local_strtotime('now');		//当月的当前时间戳
	$now_day_num = local_date('t',$now_time);		//当月天数
	$now_date_str = local_date('Y-m',$now_time);		//当月字符串
	$now_start_time = local_strtotime($now_date_str."-1 0:0:0");		//当月月初开始时间戳
	$now_end_time = local_strtotime($now_date_str."-" . $now_day_num ." 23:59:59");		//当月月末结束时间戳
	
	//$Arr['all_last_complete_commission'] = round($db->getOne("SELECT SUM(order_amount) FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id and  month(FROM_UNIXTIME(pay_time,'%Y-%m-%d'))=month(curdate())-1 
//AND order_status > 0 and order_status < 9 and u.user_id='$user_id' ")*$com_rate,2);
	/*$Arr['all_last_complete_commission'] = round($db->getOne("SELECT SUM(order_amount)*u.com_rate FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id and o.pay_time >= '$start_time' and  o.pay_time <= '$end_time' 
 AND order_status > 0 and order_status < 9 and u.user_id='$user_id' "),2);
*/	
	$Arr['all_last_complete_order'] = number_format($db->getOne("SELECT SUM(order_amount) FROM ".ORDERINFO." o,".WJ_LINK." l WHERE  o.wj_linkid = l.id and o.pay_time >= '$start_time' and  o.pay_time <= '$end_time' 
 AND order_status > 0 and order_status < 9 and l.user_id='$user_id' "),2);	//上月

	$Arr['this_complete_order'] = number_format($db->getOne("SELECT SUM(order_amount) FROM ".ORDERINFO." o,".WJ_LINK." l WHERE  o.wj_linkid = l.id and o.pay_time >= '$now_start_time' AND order_status > 0 and order_status < 9 and l.user_id='$user_id' "),2);	//当月

	$Arr['non_paid_order'] = number_format($db->getOne("SELECT SUM(order_amount) FROM ".ORDERINFO." o,".WJ_LINK." l WHERE  o.wj_linkid = l.id and (order_status = 0 or order_status >8) and l.user_id='$user_id' "),2);	//当月	
  /* 
	$Arr['all_Commsission_remaining'] = round($db->getOne("SELECT SUM(order_amount)*u.com_rate FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id and o.com_is_fa = 1
AND order_status > 0 and order_status < 9 and u.user_id='$user_id' "),2);
*/
//echo "SELECT SUM(order_amount) FROM ".ORDERINFO." WHERE month(FROM_UNIXTIME(pay_time,'%Y-%m-%d'))=month(curdate())-1  AND order_status > 0 and order_status < 9 and user_id='".$user_id."' ";
	
    //$Arr['all_current_complete_commission'] = round($db->getOne("SELECT SUM(order_amount) FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id and  month(FROM_UNIXTIME(pay_time,'%Y-%m-%d'))=month(curdate())
//AND order_status > 0 and order_status < 9 and u.user_id='$user_id' ")*$com_rate,2);
   /* $Arr['all_current_complete_commission'] = round($db->getOne("SELECT SUM(order_amount)*u.com_rate FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id and o.pay_time >= '$now_start_time' and  o.pay_time <= '$now_end_time' 
 AND order_status > 0 and order_status < 9 and u.user_id='$user_id' "),2);
*/    
 /*   $Arr['all_Pending_commission']=round($db->getOne("SELECT SUM(order_amount)*u.com_rate FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id 
AND order_status =0 and u.user_id='$user_id' "),2);
	//echo "SELECT SUM(order_amount) FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id 
//AND order_status =0 and o.user_id='$user_id' ";*/
	$Arr['Arr_links']=$Arr_links;
	$page=new page(array('total' => $record_count,'perpage'=>$size)); 
		//print_r($page);
		$Arr["pagestr"]  = $page->show(5);	
}


if ($_ACT == 'order_list')  //订单列表
{	
	
	$nav="Order Record";
	$linkid = empty($_GET['linkid'])?0:intval($_GET['linkid']);
	$referer = empty($_GET['referer'])?'':HtmlEncode($_GET['referer']);
	$start_time = empty($_GET['start_time'])?'':local_strtotime($_GET['start_time']);		//开始时间
	$end_time = empty($_GET['end_time'])?'':local_strtotime($_GET['end_time']);		//结束时间
	$status = empty($_GET['status'])?'':intval($_GET['status']);  //订单状态
	$Arr['act'] = $_ACT;
	$_MDL = $_MDL.'_links_list';
	
	
	
	
	$where ="i.wj_linkid=l.id and l.user_id='$user_id'";
	if($linkid)$where.=" and wj_linkid ='$linkid'";
	if($start_time)$where.=" and i.add_time >'$start_time'";
	if($end_time)$where.=" and i.add_time <'$end_time'";
	
	if(!empty($referer))$where.=" and wj_referer like '%$referer%'";
	if(!empty($status))$where.=" and order_status > 0 and order_status < 9";

	include_once(ROOT_PATH . 'lib/lib.f.transaction.php');

	$sql ="SELECT count(*) FROM eload_order_info i,".WJ_LINK." l WHERE $where";			
	$record_count = $db->getOne($sql);
    $size = 10;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	
	$orders = get_orders_list($where,$size,$start);

	$Arr['order_list']=$orders;
	$Arr['record_count']=$record_count;
	
	$page=new page(array('total' => $record_count,'perpage'=>$size)); 
	$Arr["pagestr"]  = $page->show(5);	
}
//var_dump($_ACT == 'ip_list');
if ($_ACT == 'ip_list')  //ip 列表
{
	$nav="Traffic Sources > IP list";
	$linkid = empty($_GET['linkid'])?0:intval($_GET['linkid']);
	$referer = empty($_GET['referer'])?'':$_GET['referer'];
	$start_time = empty($_GET['start_time'])?'':local_strtotime($_GET['start_time']);		//开始时间
	$end_time = empty($_GET['end_time'])?'':local_strtotime($_GET['end_time']);		//结束时间
	$Arr['act'] = $_ACT;
	$_MDL = $_MDL.'_links_list';
	$where ="l.id=i.from_linkid and user_id='$user_id'";
	if($linkid)$where.=" and from_linkid ='$linkid'";
	if($start_time)$where.=" and i.adddate >'$start_time'";
	if($end_time)$where.=" and i.adddate <'$end_time'";
	
	if(!empty($referer))$where.=" and HTTP_REFERER like '%$referer%'";
	
	$sql ="SELECT count(*) FROM ".WJ_IP." i,".WJ_LINK." l WHERE $where";	
	$record_count = $db->getOne($sql);
    $size = 20;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	
	$ip_list = get_ip_list($where,$size,$start);
	$Arr['ip_list']=$ip_list;
	$Arr['record_count']=$record_count;
	$page=new page(array('total' => $record_count,'perpage'=>$size)); 
	$Arr["pagestr"]  = $page->show(5);	
}

if ($_ACT == 'desc')  //ip 列表
{
	
	$nav="Get Comission ";
	$Arr['act'] = $_ACT;
	$_MDL = $_MDL.'_links_list';
}

if ($_ACT == 'source_stat')  //来源 统计 列表
{

	$nav="Traffic Sources";
	$referer = empty($_GET['referer'])?'':$_GET['referer'];
	$start_time = empty($_GET['start_time'])?'':local_strtotime($_GET['start_time']);		//开始时间
	//echo $_GET['start_time'];
	$end_time = empty($_GET['end_time'])?'':local_strtotime($_GET['end_time']);		//结束时间
	$Arr['act'] = $_ACT;
	$_MDL = $_MDL.'_links_list';
	$where ="l.id=i.from_linkid and user_id='$user_id'";
	//if($start_time)$where.=" and i.adddate >'$start_time'";
	//if($end_time)$where.=" and i.adddate <'$end_time'";
	
	if(!empty($referer))$where.=" and HTTP_REFERER like '%$referer%'";
	
	$sql ="SELECT count(distinct(HTTP_REFERER)) FROM ".WJ_IP." i,".WJ_LINK." l WHERE $where order by l.id desc";	
	$record_count = $db->getOne($sql);
	//echo $sql;
    $size = 20;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	
	$source_list = get_source_list($user_id,$start_time,$end_time,$referer,$where,$size,$start);
	$Arr['source_list'] = $source_list;
	$Arr['record_count'] = $record_count;

	$sql ="SELECT count(*) as amount FROM ".ORDERINFO." i,".WJ_LINK." l WHERE i.wj_linkid=l.id and l.user_id='$user_id' and order_status > 0 and order_status < 9 ";
	//$sql ="SELECT count(*) as amount,wj_referer FROM ".ORDERINFO." i,".WJ_LINK." l WHERE i.wj_linkid=l.id and i.user_id='$user_id' and order_status > 0 and order_status < 9 ";
	if(!empty($referers))$sql.=" and wj_referer like '%$referers%'";
	if($start_time)$sql.=" and i.add_time >'$start_time'";
	if($end_time)$sql.=" and i.add_time <'$end_time'";	
	$Arr['all_order_count']=$db->getOne($sql);  //总订单数
	
	$sql ="SELECT sum(order_amount) as amount FROM ".ORDERINFO." i,".WJ_LINK." l WHERE i.wj_linkid=l.id and l.user_id='$user_id' and order_status > 0 and order_status < 9 ";
	//$sql ="SELECT count(*) as amount,wj_referer FROM ".ORDERINFO." i,".WJ_LINK." l WHERE i.wj_linkid=l.id and i.user_id='$user_id' and order_status > 0 and order_status < 9 ";
	if($referer)$sql.=" and  wj_referer like '%$referer%'";
	if($start_time)$sql.=" and i.add_time >'$start_time'";
	if($end_time)$sql.=" and i.add_time <'$end_time'";	
	//echo $sql;
	$Arr['all_order_sum']=$db->getOne($sql);	//总订单金额
	if(empty($Arr['all_order_sum']))$Arr['all_order_sum']=0;
	  
	
    $sql = "select count(distinct(ips)) as amount,HTTP_REFERER from ".WJ_IP." i,".WJ_LINK." l where $where ";
	//die($sql);
	//and wj_referer like '%$referer%'";
    if(!empty($referers))$sql.=" and HTTP_REFERER like '%$referers%'";
	if($start_time)$sql.=" and i.adddate >'$start_time'";
	if($end_time)$sql.=" and i.adddate <'$end_time'";
    $res = $db->arrQuery($sql);	
 
	$Arr['all_ip_count']=$db->getOne($sql);	//总ＩＰ数
	//echo $sql;
	$page=new page(array('total' => $record_count,'perpage'=>$size)); 
	$Arr["pagestr"]  = $page->show(5);
}


if ($_ACT == 'underlayer')  //下家的affiliate统计
{
	$nav="Referral user";
	include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
	$Arr['act'] = $_ACT;
	$_MDL = $_MDL.'_links_list';
	$date_f = empty($_GET['date_f'])?'':$_GET['date_f'];
	$date_t = empty($_GET['date_t'])?'':$_GET['date_t'];
	$sql = "select id from ".WJ_LINK ." where user_id =$user_id";
	$tmp = $db->arrQuery($sql);
	$linkid = arr2str($tmp,'id');	
	if(!empty($linkid)){
		$all_stat=get_all_stat($linkid);
		$Arr['all_stat'] = $all_stat;		
		$sql = "select count(distinct(user_id)) from ".USERS." where wj_linkid in($linkid) ";
		$record_count = $db->getOne($sql);			
		$size = 20;
		$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
		$page_count = ceil($record_count/$size);
		if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
		if ($_GET['page'] < 1 ) $_GET['page'] = 1;
		$start = ($_GET['page'] - 1) * $size;		
	
		$sql = "select user_id,reg_time from ".USERS." where wj_linkid in($linkid) limit $start,$size";
		$users = $db->arrQuery($sql);
		
		$users = add_underlayer_stat($users); //增加用户统计数据
		$userids = arr2str($users,'user_id');
		$Arr['users'] = $users;
	}
		$page=new page(array('total' => $record_count,'perpage'=>$size)); 
		$Arr["pagestr"]  = $page->show(5);	
}

if ($_ACT == 'apply')  //网站推广申请
{
	$_MDL = 'users_inc';
	$Arr['act'] = $_ACT;	
	$user_type = $db->getOne("select user_type from ".USERS." where user_id = '$user_id'");
	if($user_type == '1'){
		header("Location: /$cur_lang_url"."m-webad-a-is_checking.htm");
		exit();
	}elseif ($user_type == '2') {
		header("Location: /$cur_lang_url"."m-webad-a-links_list.htm");
		exit();
	}
}
if ($_ACT == 'is_checking')  //网站推广申请审核中
{
	$Arr['act'] = $_ACT;	
}
if ($_ACT == 'apply_form')  //网站推广资料补充
{
	$Arr['act'] = $_ACT;
	include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    $Arr['profile'] = get_profile($user_id);
    $nav_title = ' &raquo;  '.$_LANG['profile'];
    $Arr['seo_title'] = ' My Account -  '.$_LANG['profile'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
	$_MDL = 'users_inc';
}




if ($_ACT == 'edit_profile')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    $msn = trim($_GET['other']['msn']);
    $firstname = trim($_GET['firstname']);
    $lastname = trim($_GET['lastname']);
    $paypal_account = trim($_GET['paypal_account']);
    $bbs_profile = trim($_GET['bbs_profile']);
    $bbs_id = trim($_GET['bbs_id']);        
    $introduction = trim($_GET['introduction']);
    $mobile_phone = trim($_GET['other']['phone']);
    
    
    if ($lastname =='' || $firstname==''){
         show_message($_LANG['passport_js']['lastname_and_firstname']);
	}
	//var_dump(print_r($_GET));
    if (empty($paypal_account) || !is_email($paypal_account))
    {
        show_message($_LANG['paypal_account_is']);
    }
    if (empty($introduction))
    {
        show_message($_LANG['please_enter_the']);
    }


    $old_user_type =$db->getOne("select user_type  from ".USERS." where user_id='$user_id'");

    $profile  = array(
		'user_id'  => $user_id,
		'firstname' => htmlspecialchars($firstname),
		'lastname' =>  htmlspecialchars($lastname),
		'sex'      => isset($_GET['sex'])   ? intval($_GET['sex']) : 0,
		'paypal_account' =>  htmlspecialchars($paypal_account),
		'introduction' =>  htmlspecialchars($introduction),
		'bbs_profile' =>  htmlspecialchars($bbs_profile),
		'bbs_id' =>  htmlspecialchars($bbs_id),
		'affiliates_apply_time' => gmtime(),
		'affiliates_pass_time' => gmtime(),
		'user_type' =>'2',
		'other'=>isset($_GET['other']) ? $_GET['other'] : array(),
        );	
    if (edit_profile($profile))
    {
		$_SESSION['firstname']  = $profile['firstname'];
		$_SESSION['lastname']  = $profile['lastname'];
		$user_info = $db->selectInfo("select email,firstname,user_type  from ".USERS." where user_id='$user_id'");
		if($old_user_type !=2){
			$note = "Affiliate program passed";
			add_point($user_id,10,2,$note);
		
			$email = $user_info['email'];
			//require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
			//$email        = $_SESSION['email'];
			$sql = "insert into eload_wj_link(link_name,link_text,link_url,img,user_id) select link_name,link_text,link_url,img,$user_id from eload_wj_link,eload_users where eload_wj_link.user_id=eload_users.user_id and email='link@davismicro.com'";
			$db->query($sql);
			$mail_subject = $mail_conf[$cur_lang][30];
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $cur_lang .'/30.html');							
			if(empty($mail_subject)) {
				$mail_subject = $mail_conf['en'][30];
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/30.html');				
			}
			$mail_subject = str_replace('$site_name',$_SERVER['HTTP_HOST'],$mail_subject);			
			$mail_body    = str_replace('{$site_name}',$_SERVER['HTTP_HOST'],$mail_body);
		}
		header("Location: /$cur_lang_url"."m-webad-a-recommend_link.htm\n");
		exit;
        show_message($ms_title, $ms, DOMAIN_USER.'m-users-a-order_list.htm', 'success');
        
    }
    else
    {
		$msg = $_LANG['edit_profile_failed'];
        show_message($msg, '', '', 'warning');
    }
    $nav_title = ' &raquo;  '.$_LANG['profile'];
    $Arr['seo_title'] = ' My Account -  '.$_LANG['profile'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
	$_MDL = 'users_inc';

}


//用户中心欢迎页
if ($_ACT == 'index')
{
	$nav_title = ' &raquo;  My information';
	$Arr['seo_title'] = $_CFG['shop_name'];
    $Arr['seo_title'] = ' My Account -  My information  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
		
}
$Arr['act'] = $_ACT;
if ($_ACT == 'recommend_link')  //网站推广资料补充
{
	$_MDL = $_MDL.'_links_list';
	$Arr['act'] = $_ACT;
	$nav = 'Affiliate Homepage';
	$re_link = $db->arrQuery("select * from eload_wj_recommend_link order by sort_order,id desc");
	

	foreach ($re_link as $k=>$v){
		 if(!empty($re_link[$k]['img']))
		 	$re_link[$k]['img'] = "http://".$_SERVER['SERVER_NAME'].'/'.$re_link[$k]['img'];
	}
	//print_r($re_link);
	$Arr['re_link'] = $re_link;
	
	$stat = new affiliate_stat($user_id);
	
    
	$Arr['ip_stat'] = $stat->ip_stat();
	$Arr['order_stat'] = $stat->order_stat();
	//print_r($Arr['order_stat']);
	//exit();
	
}
if ($_ACT == 'commission')  //
{
	$_MDL = $_MDL.'_links_list';
	$sql = "SELECT com_fa_date,count(*) as order_count,SUM(order_amount) as order_amount,SUM(order_amount)*u.com_rate as commission FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id and u.user_id=".$user_id."  and o.wj_linkid = l.id  AND order_status > 0 and order_status < 9 and com_is_fa = 2 and com_fa_date>0 group by com_fa_date order by com_fa_date desc";
	$com_arr = $db->arrQuery($sql);

	foreach ($com_arr as $k=>$v){
		//echo $com_arr[$k]['com_fa_date'] ;
		$com_arr[$k]['com_fa_date'] = date('Y-m-d',$com_arr[$k]['com_fa_date']);
		$com_arr[$k]['commission'] = number_format($com_arr[$k]['commission'],2);
	}
	$Arr['pending_comission'] = $db->selectInfo("SELECT SUM(order_amount) as order_amount,SUM(order_amount)*u.com_rate as commission FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id and u.user_id=".$user_id."  and o.wj_linkid = l.id  AND order_status > 0 and order_status < 9 and com_is_fa = 1");
	if(!empty($Arr['pending_comission']))$Arr['pending_comission']['commission']=number_format($Arr['pending_comission']['commission'],2);
	$Arr['com_arr']  = $com_arr;
}

if ($_ACT == 'contact')  //网站推广资料补充
{
	$_MDL = $_MDL.'_links_list';
}

if ($_ACT == 'how2use')  //网站推广资料补充
{
	$_MDL = $_MDL.'_links_list';
}

//给$users 数组里的用户加referral 统计信息
function add_underlayer_stat($users)
{
	global $db;
    /* 取得链接列表 */
    $userids = arr2str($users,'user_id');
    if(empty($userids)) return '';
    $date_f = empty($_GET['date_f'])?'':$_GET['date_f'];
    $date_t = empty($_GET['date_t'])?'':$_GET['date_t'];

    // 注册数统计信息
    $sql = "select l.user_id,count(id) as reg_count from ".WJ_LINK." l,".USERS." u where l.user_id in($userids) and l.id=u.wj_linkid ";
	if(!empty($date_f)) $sql.=" and reg_time >=UNIX_TIMESTAMP('$date_f')";
	if(!empty($date_t)) $sql.=" and reg_time <=UNIX_TIMESTAMP('$date_t')";
	$sql.=" group by l.user_id";
    $reg_count = $db->arrQuery($sql);
    $reg_count = fetch_id($reg_count,'user_id');

    $sql = "select count(distinct(ips)) as ip_count,user_id from ".WJ_IP." i,".WJ_LINK." l where l.id=i.from_linkid and user_id in($userids)";
	
    if(!empty($date_f)) $sql.=" and i.adddate >=UNIX_TIMESTAMP('$date_f')";
	if(!empty($date_t)) $sql.=" and i.adddate <=UNIX_TIMESTAMP('$date_t')";    
    $sql.=" group by user_id";

    $ip_count = $db->arrQuery($sql);
    $ip_count = fetch_id($ip_count,'user_id');
       
    $sql = "select l.user_id,count(order_id) as order_count,sum(goods_amount) as order_sum from ".ORDERINFO." o,".WJ_LINK." l where l.user_id in($userids) and l.id=o.wj_linkid and order_status >0 and order_status<9";
	if(!empty($date_f)) $sql.=" and pay_time >=UNIX_TIMESTAMP('$date_f')";
	if(!empty($date_t)) $sql.=" and pay_time <=UNIX_TIMESTAMP('$date_t')";
	$sql.=" group by l.user_id";
    $order_stat = $db->arrQuery($sql);
   
	$order_stat = fetch_id($order_stat,'user_id');    
    foreach ($users as $k=>$v){
    	$users[$k]['reg_count']=empty($reg_count[$v['user_id']])?0:$reg_count[$v['user_id']][0]['reg_count'];
    	
    	$users[$k]['order_count']=empty($order_stat[$v['user_id']][0]['order_count'])?0:$order_stat[$v['user_id']][0]['order_count'];
    	$users[$k]['order_sum']=empty($order_stat[$v['user_id']][0]['order_sum'])?0:$order_stat[$v['user_id']][0]['order_sum'];
    	$users[$k]['reg_time']=local_date('Y-m-d', $users[$k]['reg_time']);
    	$users[$k]['ip_count']=empty($ip_count[$v['user_id']][0]['ip_count'])?0:$ip_count[$v['user_id']][0]['ip_count'];
    }
    return $users; 
}

//获取一个affiliate用户的referral  情况
//$linkid　是也统计的affiliate用户账户的 link 的id 值组成的字符串
function get_all_stat($linkid){
			global  $db;
			$date_f = empty($_GET['date_f'])?'':$_GET['date_f'];
			$date_t = empty($_GET['date_t'])?'':$_GET['date_t'];
			if(empty($linkid)) return ;
			$sql = "select user_id from ".USERS." where wj_linkid in($linkid)";
			$u = $db->arrQuery($sql);
			$userids = arr2str($u,'user_id');
			if(empty($userids)){
				$order_stat['ip_count']=0 ;
				$order_stat['order_sum']=0 ;
				$order_stat['order_count']=0 ;
				$order_stat['reg_count']=0 ;
				return $order_stat;
			}
		    $sql = "select count(order_id) as order_count,sum(goods_amount) as order_sum from ".ORDERINFO." o,".WJ_LINK." l where l.user_id in($userids) and l.id=o.wj_linkid and order_status >0 and order_status<9";
			if(!empty($date_f)) $sql.=" and pay_time >=UNIX_TIMESTAMP('$date_f')";
			if(!empty($date_t)) $sql.=" and pay_time <=UNIX_TIMESTAMP('$date_t')";

		    $order_stat = $db->selectInfo($sql);
		    $order_stat['order_sum'] =  number_format($order_stat['order_sum'],2);
		    
		    $sql = "select count(id) as reg_count from ".WJ_LINK." l,".USERS." u where l.user_id in($userids) and l.id=u.wj_linkid ";
			if(!empty($date_f)) $sql.=" and reg_time >=UNIX_TIMESTAMP('$date_f')";
			if(!empty($date_t)) $sql.=" and reg_time <=UNIX_TIMESTAMP('$date_t')";	
		    $reg_count = $db->selectInfo($sql);		    
		    $order_stat['reg_count']  =$reg_count['reg_count'];
		    
		    $sql = "select count(distinct(ips)) as ip_count from ".WJ_IP." i,".WJ_LINK." l where l.id=i.from_linkid and user_id in($userids)";
			if(!empty($date_f)) $sql.=" and i.adddate >=UNIX_TIMESTAMP('$date_f')";
			if(!empty($date_t)) $sql.=" and i.adddate <=UNIX_TIMESTAMP('$date_t')";  
		    $ip_count = $db->selectInfo($sql);    
		    $order_stat['ip_count']  =$ip_count['ip_count'];
		    
		    
		    return $order_stat;
}

function get_orders_list($where, $num = 10, $start = 0)
{
    /* 取得订单列表 */
    $user_id = $_SESSION['user_id'];
    $arr    = array();
    
	$sql = "SELECT *,if(order_status>0 and order_status<9,1,order_status) as order_status_n FROM eload_order_info i,".WJ_LINK." l WHERE $where order by order_id desc limit $start,$num";
	//echo $sql;
	$com_rate = $GLOBALS['db']->getOne('select com_rate from '.USERS . " where user_id ='$user_id'");   
    $res = $GLOBALS['db']->arrQuery($sql);    
    foreach ($res as $k=>$v)
    {
		$sql = "select g.goods_id,g.goods_sn,goods_title,og.goods_name,og.goods_number,goods_price,url_title from eload_order_goods og,eload_goods g where og.goods_id=g.goods_id and order_id='$v[order_id]' group by g.goods_sn";
        $goods_list= $GLOBALS['db']->arrQuery($sql);	
        foreach ($goods_list as $ikey=>$iv){
        	$goods_list[$ikey]['subtotal']=$iv['goods_price']*$iv['goods_number'];
        	$goods_list[$ikey]['url_title']=  get_details_link($goods_list[$ikey]['goods_id'],$goods_list[$ikey]['url_title']);
        }
        $res[$k]['add_time']=local_date($GLOBALS['_CFG']['AM_time_format'], $res[$k]['add_time']);
        $res[$k]['commission'] = number_format($com_rate*$v['order_amount'],2);
        if($v['order_status']>=9 || $v['order_status']==0)
        	 $res[$k]['commission_state'] = '-';
        else
        	$res[$k]['commission_state']= $v['com_is_fa']==1?'Pending':'Paid';
        
        $res[$k]['goods_list']=$goods_list;
        $res[$k]['order_status_str']=$GLOBALS['_LANG']['os'][$res[$k]['order_status_n']];;
    }    
    return $res;
}

/*------------------------------------------------------ */
/**
 * 取得来访IP数据信息

 */
function get_ip_list($where, $num = 10, $start = 0)
{
	$sql ="SELECT i.id,i.ips,from_linkid,i.adddate,HTTP_REFERER FROM ".WJ_IP." i,".WJ_LINK." l WHERE $where";
	$sql.=" ORDER BY i.id desc limit  $start,$num";
    //echo $sql;
    
	$res = $GLOBALS['db']->arrQuery($sql);
    foreach ($res as $k=>$v)
    {
        $res[$k]['adddate']=local_date($GLOBALS['_CFG']['AM_time_format'], $v['adddate']);	
        $res[$k]['ips']=substr($res[$k]['ips'],0,-3).'***';	
    }
    return $res;
}

/**
 * 来源统计
 */
function get_source_list($user_id,$start_time,$end_time,$referer,$where,$num = 10, $start = 0)
{
	
	global $db;
    /* 取得链接列表 */

	//if($start_time)$where.=" and i.adddate >'$start_time'";
	//if($end_time)$where.=" and i.adddate <'$end_time'";
	//$orderby ="(select count(distinct(ips)) from ".WJ_IP." ii,".WJ_LINK." ll where ll.id=ii.from_linkid and user_id='$user_id' and ii.HTTP_REFERER=i.HTTP_REFERER)";
	if($start_time)$where.=" and i.adddate >'$start_time'";
	if($end_time)$where.=" and i.adddate <'$end_time'";
	
	if(!empty($referer))$where.=" and HTTP_REFERER like '%$referer%'";
	
	
	
	$sql ="SELECT HTTP_REFERER,count(distinct(ips)) as c FROM ".WJ_IP." i,".WJ_LINK." l WHERE $where";
	$sql.=" group by HTTP_REFERER";
	$sql.=" order by c desc ";
	$sql.=" limit  $start,$num";
	//echo $sql;
    //exit();
	$res = $db->arrQuery($sql);
    
    $referers = arr2str($res,'HTTP_REFERER');
    
    
    $sql = "select count(distinct(ips)) as amount,HTTP_REFERER from ".WJ_IP." i,".WJ_LINK." l where $where ";
	//die($sql);
    if($referers)$sql.=" and HTTP_REFERER in($referers)";
	if($start_time)$sql.=" and i.adddate >'$start_time'";
	if($end_time)$sql.=" and i.adddate <'$end_time'";
	$sql.=" group by HTTP_REFERER";
	//die($sql);
	$ip_count_arr=$db->arrQuery($sql);
	//print_r($ip_count_arr);
	//exit();
	$ip_count_arr=fetch_id($ip_count_arr,'HTTP_REFERER'); // ip 数

	
	//统计订单数
	$sql ="SELECT count(*) as amount,wj_referer FROM ".ORDERINFO." i,".WJ_LINK." l WHERE i.wj_linkid=l.id and l.user_id='$user_id' and order_status > 0 and order_status < 9 ";
	if($referers)$sql.=" and wj_referer in($referers)";
	if($start_time)$sql.=" and i.add_time >'$start_time'";
	if($end_time)$sql.=" and i.add_time <'$end_time'";
	$sql.=" group by wj_referer";
	//echo $sql;
	$order_finish_count = $db->arrQuery($sql);
	$order_finish_count=fetch_id($order_finish_count,'wj_referer');
	
	
	//统计订单总金额
	$sql ="SELECT sum(order_amount) as amount,wj_referer FROM ".ORDERINFO." i,".WJ_LINK." l WHERE i.wj_linkid=l.id and l.user_id='$user_id' and order_status > 0 and order_status < 9 ";
	if($referers)$sql.=" and wj_referer in($referers)";
	if($start_time)$sql.=" and i.add_time >'$start_time'";
	if($end_time)$sql.=" and i.add_time <'$end_time'";
	$sql.=" group by wj_referer";
	$order_finish_sum = $db->arrQuery($sql);
	$order_finish_sum=fetch_id($order_finish_sum,'wj_referer');
	
	//print_r($order_finish_count);
	
//    echo $sql;
    foreach ($res as $k=>$v)
    {
		$res[$k]['HTTP_REFERER_encode']=empty($res[$k]['HTTP_REFERER'])?'':urlencode($res[$k]['HTTP_REFERER']);
		//echo HtmlEncode($res[$k]['HTTP_REFERER']);
		//echo $res[$k]['HTTP_REFERER'];
		//exit();
		//echo $order_finish_count[$v['wj_referer']].":".$order_finish_count[$v['wj_referer']][0]['amount'];
		$res[$k]['ip_count']=empty($ip_count_arr[$v['HTTP_REFERER']][0]['amount'])?0:$ip_count_arr[$v['HTTP_REFERER']][0]['amount'];
		$res[$k]['order_count']=empty($order_finish_count[$v['HTTP_REFERER']][0]['amount'])?0:$order_finish_count[$v['HTTP_REFERER']][0]['amount'];
		$res[$k]['order_sum']=empty($order_finish_sum[$v['HTTP_REFERER']][0]['amount'])?0:$order_finish_sum[$v['HTTP_REFERER']][0]['amount'];
    }
    
    return $res;
}
//echo $_ACT;
if(empty($Arr['act']))$_ACT['act']  = $_ACT;

//echo $Arr['act'];
if(!empty($nav))$Arr['nav']=$nav;

?>
