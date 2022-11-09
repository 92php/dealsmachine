<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/lib_order.php');
require_once(ROOT_PATH . 'lib/class.page.php');
require_once('lang/order.php');
$Arr['lang'] =  $_LANG;

$_REQUEST['start_date'] = empty($_REQUEST['start_date'])?'':local_strtotime($_REQUEST['start_date']);
$_REQUEST['end_date'] = empty($_REQUEST['end_date'])?'':local_strtotime($_REQUEST['end_date'].' 23:59:59');

$_REQUEST['start_date2'] = empty($_REQUEST['start_date2'])?'':local_strtotime($_REQUEST['start_date2']);
$_REQUEST['end_date2'] = empty($_REQUEST['end_date2'])?'':local_strtotime($_REQUEST['end_date2'].' 23:59:59');

$_REQUEST['sort_by']    = empty($_REQUEST['sort_by'])?'':$_REQUEST['sort_by'];
$_REQUEST['sort_order'] = empty($_REQUEST['sort_order'])?'':$_REQUEST['sort_order'];
$_REQUEST['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);

//echo $_ACT;
//exit();
$_ACT = empty($_GET['act'])?'':$_GET['act'];

if (empty($_ACT)){
	/* 权限检查 */

	admin_priv('affiliate_order');
	$cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
	$fangshi = empty($_REQUEST['fangshi']) ? 1 : intval($_REQUEST['fangshi']);
	$Arr['cat_list'] = cat_list($cat_id);
	$Arr['fangshi'] = $fangshi;
	
	
	$goods_order_data = get_sales_order();
	
	$Arr['goods_order_data'] = $goods_order_data['sales_order_data'];
	$Arr['tj'] = $goods_order_data['tj'];
	
	$sort_flag           = sort_flag($goods_order_data['filter']);	
	$Arr[$sort_flag['tag']] = $sort_flag['img'];	
	$goods_order_data['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	
	$Arr['filter'] = $goods_order_data['filter'];
	
	$page=new page(array('total'=>$goods_order_data['record_count'],'perpage'=>$goods_order_data['page_size']));
	
	$Arr["pagestr"]  = $page->show();
	
	
	
	$Arr['start_date'] =       local_date('Y-m-d', $_REQUEST['start_date']);
	$Arr['end_date'] =         local_date('Y-m-d', $_REQUEST['end_date']);
	$Arr['start_date2'] =       local_date('Y-m-d', $_REQUEST['start_date2']);
	$Arr['end_date2'] =         local_date('Y-m-d', $_REQUEST['end_date2']);
	$Arr['goods_sn'] =        $_REQUEST['goods_sn'];
	
	$Arr["search_url"] = get_url_parameters($_GET,array('sort_by','sort_order'));
	
}else if ($_ACT == 'underlayer')  //下家的affiliate统计
	
{
	$user_id = empty($_GET['user_id'])?'0':intval($_GET['user_id']);
	include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
	$Arr['act'] = $_ACT;

	$Arr['user_info'] = user_info($user_id);
	$date_f = empty($_GET['date_f'])?'':$_GET['date_f'];
	$date_t = empty($_GET['date_t'])?'':$_GET['date_t'];
	//$nav_title = ' &raquo;  '.$_LANG['profile'].'&raquo; referral';
	$sql = "select id from ".WJ_LINK ." where user_id =$user_id";
	$tmp = $db->arrQuery($sql);
	$linkid = arr2str($tmp,'id');	
	//echo $linkid;
	if(!empty($linkid)){
		$all_stat=get_all_stat($linkid);
		//print_r($all_stat);
		$Arr['all_stat'] = $all_stat;

		
		
		
		
		$sql = "select count(distinct(user_id)) from ".USERS." where wj_linkid in($linkid) ";
		$record_count = $db->getOne($sql);	
		//echo $sql;
		//$sql = "select * from ";
		
		$size = 20;
		$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
		$page_count = ceil($record_count/$size);
		if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
		if ($_GET['page'] < 1 ) $_GET['page'] = 1;
		$start = ($_GET['page'] - 1) * $size;
		
	
		$sql = "select user_id,reg_time from ".USERS." where wj_linkid in($linkid) limit $start,$size";
		//echo $sql;
		$users = $db->arrQuery($sql);
		
		$users = add_underlayer_stat($users); //增加用户统计数据
		//print_r($users);
		$userids = arr2str($users,'user_id');
		$Arr['users'] = $users;
		
		$page=new page(array('total' => $record_count,'perpage'=>$size)); 
		//print_r($page);
		$Arr["pagestr"]  = $page->show();	
	}
	
	
	//$where="`user_id` in ($userids)";
	
	
		
}
else if ($_ACT == 'save_add_underlayer')  //增加下家
	{

		$email = empty($_GET['email'])?'':$_GET['email'];
		$user_id = empty($_GET['user_id'])?'0':$_GET['user_id'];
		
		if(empty($email)||!is_email($email)){
			echo '请输入有效的用户email';
			exit();
		}
		$user = $db->selectInfo("select * from ".USERS." where email='$email'");
		
		if(empty($user)){
			//echo $email;
			echo "找不到用户：".$email." 请检查.";
			exit();			
		}
		if(empty($user_id)){
			echo '用户信息不完整.';
			exit();			
		}
		$link= $db->selectInfo("select  id from ".WJ_LINK." where user_id =$user_id limit 1");
		//echo "update ".USERS." set wj_linkid=".$user_id." where user_id=$user[user_id]";
		if($db->query("update ".USERS." set wj_linkid=".$link['id']." where user_id=$user[user_id]")){
			echo 'ok';
		}else{
			echo '增加失败';
		}
		exit();
		
		
	}

/*------------------------------------------------------ */
//--排行统计需要的函数
/*------------------------------------------------------ */
/**
 * 取得销售排行数据信息
 * @param   bool  $is_pagination  是否分页
 * @return  array   销售排行数据
 */
function get_sales_order($is_pagination = true)
{
    $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
    $filter['end_date']   = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
	
    $filter['start_date2'] = empty($_REQUEST['start_date2']) ? '' : $_REQUEST['start_date2'];
    $filter['end_date2']   = empty($_REQUEST['end_date2']) ? '' : $_REQUEST['end_date2'];
	
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'choujin' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    $filter['order_status'] = !isset($_REQUEST['order_status']) ? '-1' : intval($_REQUEST['order_status']);
	$filter['add_user']   = empty($_REQUEST['add_user'])?'':$_REQUEST['add_user'];
    $where = " WHERE  u.user_type = 2 and wj.user_id = u.user_id and  o.wj_linkid = wj.id and o.order_id = g.order_id ";

   $where .= " AND  o.order_status >0 and o.order_status < 9 ";

    if ($filter['start_date'])
    {		
        $where .= " AND o.add_time >= '" . $filter['start_date'] . "'";
    }
    if ($filter['end_date'])
    {
        $where .= " AND o.add_time <= '" . $filter['end_date'] . "'";
    }
	
	
	
    if ($filter['start_date2'])
    {		
        $where .= " AND o.pay_time >= '" . $filter['start_date2'] . "'";
    }
    if ($filter['end_date2'])
    {
        $where .= " AND o.pay_time <= '" . $filter['end_date2'] . "'";
    }
		
    if ($filter['add_user'])
    {
        $where .= " AND (u.email like '%" . $filter['add_user'] . "%' or u.firstname like '%" . $filter['add_user'] . "%'  or u.lastname like '%" . $filter['add_user'] . "%')  ";
    }
	

    $sql = "SELECT COUNT(*) FROM ( select  u.email from  " .
           ORDERINFO . ' AS o,'.
 		   USERS." AS u ,".
		   ODRGOODS." AS g ,".
          WJ_LINK . ' AS wj '.
           $where . ' group by u.email) aa ' ;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);


    

    /* 分页大小 */
    $filter = page_and_size($filter);

  $sql = "select u.email,u.user_id,sum(g.goods_number*g.goods_price) as price_total, sum(g.goods_number*g.goods_price)*u.com_rate as choujin ".
   // $sql = "SELECT o.order_sn,o.order_amount, o.order_id,o.user_id,o.add_time,wj.id,wj.user_id,u.email,u.com_rate,o.order_status,wj.link_url,wj.img,wj.link_text,o.pay_time " .
           "FROM ".WJ_LINK." AS wj, " .
		   USERS." AS u ,".
		   ODRGOODS." AS g ,".
           ORDERINFO." AS o  " .$where .
           "  ".
           '  group by u.email  ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'] ;
    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
	//echo $sql;
	
    $data = $GLOBALS['db']->getAll($sql);
    foreach ($data as $key => $item)
    {
       $data[$key]['taxis']   = $key + 1;
	   $data[$key]['choujin'] = price_format($item['choujin']);
	   $data[$key]['email']		= email_disp_process($item['email']);
    }
	
	$sql = "select SUM(g.goods_number*g.goods_price) * u.com_rate as com_money ,SUM(g.goods_number*g.goods_price) as turnover  ".
           "FROM ".WJ_LINK." AS wj, " .
		   USERS." AS u ,".
		   ODRGOODS." AS g ,".
           ORDERINFO." AS o  " .$where ;
		 //  echo 
	$tj = $GLOBALS['db']->selectinfo($sql);

   $tj['turnover'] = price_format($tj['turnover']);
   $tj['com_money']    = price_format($tj['com_money']);
	
    $arr = array('sales_order_data' => $data, 'filter' => $filter,'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count'],'tj'=>$tj);
    return $arr;
}



$_ACT = 'affiliate_count';
temp_disp();//





//给$users 数组里的用户加referral 统计信息
function add_underlayer_stat($users)
{
	global $db;
    /* 取得链接列表 */
    $userids = arr2str($users,'user_id');
    if(empty($userids)) return '';
    $date_f = empty($_GET['date_f'])?'':$_GET['date_f'];
    $date_t = empty($_GET['date_t'])?'':$_GET['date_t'];
    //$where 
    //echo $userids;
    // 注册数统计信息
    $sql = "select l.user_id,count(id) as reg_count from ".WJ_LINK." l,".USERS." u where l.user_id in($userids) and l.id=u.wj_linkid ";
	if(!empty($date_f)) $sql.=" and reg_time >=UNIX_TIMESTAMP('$date_f')";
	if(!empty($date_t)) $sql.=" and reg_time <=UNIX_TIMESTAMP('$date_t')";
	$sql.=" group by l.user_id";
    $reg_count = $db->arrQuery($sql);
    $reg_count = fetch_id($reg_count,'user_id');
   // print_r($reg_count);
    //
    //echo $sql;
    $sql = "select count(distinct(ips)) as ip_count,user_id from ".WJ_IP." i,".WJ_LINK." l where l.id=i.from_linkid and user_id in($userids)";
	if(!empty($date_f)) $sql.=" and i.adddate >=UNIX_TIMESTAMP('$date_f')";
	if(!empty($date_t)) $sql.=" and i.adddate <=UNIX_TIMESTAMP('$date_t')";    
    $sql.=" group by user_id";
//$sql = "SELECT wj.*  ,(select count(distinct(ips)) from ".WJ_IP." where from_linkid = wj.id) as ip_count   FROM ".WJ_LINK." as wj  WHERE wj.user_id in($userids) group by l.user_id  ";    
    $ip_count = $db->arrQuery($sql);
    $ip_count = fetch_id($ip_count,'user_id');
       
    $sql = "select l.user_id,count(order_id) as order_count,sum(goods_amount) as order_sum from ".ORDERINFO." o,".WJ_LINK." l where l.user_id in($userids) and l.id=o.wj_linkid and order_status >0 and order_status<9";
	if(!empty($date_f)) $sql.=" and pay_time >=UNIX_TIMESTAMP('$date_f')";
	if(!empty($date_t)) $sql.=" and pay_time <=UNIX_TIMESTAMP('$date_t')";
	$sql.=" group by l.user_id";
	//echo $sql;
    $order_stat = $db->arrQuery($sql);
   
	$order_stat = fetch_id($order_stat,'user_id');
	// print_r($order_stat);
    //print_r($sql);   
    //$reg_count = fetch_id($temp,'id');
    
    foreach ($users as $k=>$v){
		$users[$k]['email']    = $db->getOne("select email from eload_users where user_id = '".$v['user_id']."'");
    	$users[$k]['reg_count']=empty($reg_count[$v['user_id']])?0:$reg_count[$v['user_id']][0]['reg_count'];
    	
    	$users[$k]['order_count']=empty($order_stat[$v['user_id']][0]['order_count'])?0:$order_stat[$v['user_id']][0]['order_count'];
    	$users[$k]['order_sum']=empty($order_stat[$v['user_id']][0]['order_sum'])?0:$order_stat[$v['user_id']][0]['order_sum'];
    	$users[$k]['reg_time']=local_date('Y-m-d', $users[$k]['reg_time']);
    	$users[$k]['ip_count']=empty($ip_count[$v['user_id']][0]['ip_count'])?0:$ip_count[$v['user_id']][0]['ip_count'];
		$users[$k]['email'] = email_disp_process($users[$k]['email']);
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
			//$sql.=" group by l.user_id";
			//echo $sql;
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
			//echo $sql;
		    //$sql.=" group by user_id";
		//$sql = "SELECT wj.*  ,(select count(distinct(ips)) from ".WJ_IP." where from_linkid = wj.id) as ip_count   FROM ".WJ_LINK." as wj  WHERE wj.user_id in($userids) group by l.user_id  ";    
		    $ip_count = $db->selectInfo($sql);
		    //print_r($ip_count);	    
		    $order_stat['ip_count']  =$ip_count['ip_count'];
		    
		    
		    return $order_stat;
}








?>