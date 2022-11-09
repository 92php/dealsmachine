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



$_ACT = empty($_GET['act'])?'':$_GET['act'];

if (empty($_ACT)){
	/* 权限检查 */
	admin_priv('affiliate_referer');
	$cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
	$fangshi = empty($_REQUEST['fangshi']) ? 1 : intval($_REQUEST['fangshi']);
	$Arr['cat_list'] = cat_list($cat_id);
	$Arr['fangshi'] = $fangshi;
	
	
	$get_ip_list = get_ip_list();
	
	if(!empty($get_ip_list['sales_order_data']))$Arr['get_ip_list'] = $get_ip_list['sales_order_data'];
	//$Arr['tj'] = $get_ip_list['tj'];
	
	$sort_flag           = sort_flag($get_ip_list['filter']);	
	$Arr[$sort_flag['tag']] = $sort_flag['img'];	
	$get_ip_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	
	$Arr['filter'] = $get_ip_list['filter'];
	$Arr['ip_list']  = $get_ip_list['data'];
	$page=new page(array('total'=>$get_ip_list['record_count'],'perpage'=>$get_ip_list['page_size']));
	
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
		$userids = arr2str($users,'user_id');
		$Arr['users'] = $users;
		
		$page=new page(array('total' => $record_count,'perpage'=>$size)); 
		//print_r($page);
		$Arr["pagestr"]  = $page->show();	
	}
	
	
	//$where="`user_id` in ($userids)";
	
	
		
}

/*------------------------------------------------------ */
//--来访IP的函数
/*------------------------------------------------------ */
/**
 * 取得来访IP数据信息
 * @param   bool  $is_pagination  是否分页
 * @return  array   来访IP数据
 */
function get_ip_list($is_pagination = true)
{
    $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
    $filter['end_date']   = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
	
    $filter['start_date2'] = empty($_REQUEST['start_date2']) ? '' : $_REQUEST['start_date2'];
    $filter['end_date2']   = empty($_REQUEST['end_date2']) ? '' : $_REQUEST['end_date2'];
	
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'i.adddate' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    //$filter['order_status'] = !isset($_REQUEST['order_status']) ? '-1' : intval($_REQUEST['order_status']);
	$filter['referer']   = empty($_REQUEST['referer'])?'':trim($_REQUEST['referer']);
	$filter['email']   = empty($_REQUEST['email'])?'':trim($_REQUEST['email']);
	$filter['linkid']   = empty($_REQUEST['linkid'])?'':trim($_REQUEST['linkid']);
    $where = " WHERE i.from_linkid = l.id and u.user_id = l.user_id ";

   //$where .= " AND  o.order_status >0 and o.order_status < 9 ";

    if ($filter['start_date'])
    {		
        $where .= " AND i.adddate >= '" . $filter['start_date'] . "'";
    }
    if (!empty($filter['linkid']))
    {		
        $where .= " AND l.id =" . $filter['linkid'] ;
    }
    if ($filter['end_date'])
    {
        $where .= " AND i.adddate <= '" . $filter['end_date'] . "'";
    }
		
    if ($filter['email'])
    {
        $where .= " AND u.email = '$filter[email]'";
    }elseif(!empty($filter['user_id']))
    {
        $where .= " AND i.user_id = $filter[user_id]";
    }
    
    if ($filter['referer'])
    {
        $where .= " AND HTTP_REFERER like  '%" . $filter['referer'] . "%'";
    }  
	

    $sql = "SELECT COUNT(*) FROM ".WJ_IP." as i ,".WJ_LINK." as l,".USERS." as u ".
           $where  ;
    // die($sql);
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);


    

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "select u.email,l.id,u.user_id,ips,i.adddate,i.HTTP_REFERER,link_text,link_url ".
   // $sql = "SELECT o.order_sn,o.order_amount, o.order_id,o.user_id,o.add_time,wj.id,wj.user_id,u.email,u.com_rate,o.order_status,wj.link_url,wj.img,wj.link_text,o.pay_time " .
     " FROM ".WJ_IP." as i ,".WJ_LINK." as l,".USERS." as u " .$where ." ORDER BY " . $filter['sort_by'] . ' ' . $filter['sort_order'];
    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
	//echo $sql;
	
    $data = $GLOBALS['db']->getAll($sql);
    foreach ($data as $key => $item)
    {
       $data[$key]['adddate']   = local_date($GLOBALS['_CFG']['time_format'], $item['adddate']);
	   $data[$key]['email']		= email_disp_process($item['email']);
    }

	if(empty($tj))$tj=array();
    $arr = array('data' => $data, 'filter' => $filter,'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count'],'tj'=>$tj);
    return $arr;
}



$_ACT = 'affiliate_ips';
temp_disp();//

?>