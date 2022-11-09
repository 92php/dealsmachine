<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/cls_image.php');
require_once(ROOT_PATH . 'lib/lib_order.php');
require_once(ROOT_PATH . 'lib/class.page.php');
require_once('lang/order.php');
$Arr['lang'] =  $_LANG;
$act = empty($_REQUEST['act'])?'affiliate_links_list':$_REQUEST['act'];
$_REQUEST['start_date']  = empty($_REQUEST['start_date'])?'':local_strtotime($_REQUEST['start_date']);
$_REQUEST['end_date']    = empty($_REQUEST['end_date'])?'':local_strtotime($_REQUEST['end_date'].' 23:59:59');
$_REQUEST['start_date2'] = empty($_REQUEST['start_date2'])?'':local_strtotime($_REQUEST['start_date2']);
$_REQUEST['end_date2']   = empty($_REQUEST['end_date2'])?'':local_strtotime($_REQUEST['end_date2'].' 23:59:59');
$_REQUEST['sort_by']     = empty($_REQUEST['sort_by'])?'':$_REQUEST['sort_by'];
$_REQUEST['sort_order']  = empty($_REQUEST['sort_order'])?'':$_REQUEST['sort_order'];
$_REQUEST['user_id']     = empty($_REQUEST['user_id']) ? '' : trim($_REQUEST['user_id']);
$_REQUEST['user_id_s']   = empty($_REQUEST['user_id_s']) ? '' : trim($_REQUEST['user_id_s']);
$_REQUEST['fb_user_id']  = empty($_REQUEST['fb_user_id'])?'':$_REQUEST['fb_user_id'];
$table  = 'eload_wj_recommend_link';	
if($act == 'save_link'){
	admin_priv('recommend_list');
	$image = new cls_image();
    $php_maxsize="2M";	
    $link = array();
    $link_name = empty($_POST['link_name'])?'':$_POST['link_name'];
    $id = empty($_POST['id'])?0:$_POST['id'];
    $url = empty($_POST['url'])?'':$_POST['url'];
    $sort_order = empty($_POST['sort_order'])?'':$_POST['sort_order'];
    $des = empty($_POST['des'])?'':$_POST['des'];
    if(empty($link_name)){
    	sys_msg('请输入链接内容', 1, array(), false);
    } 
    if(empty($url)){
    	sys_msg('请输入链接URL', 1, array(), false);
    }
	if(isset($_FILES['img'])&&$_FILES['img']['tmp_name'] != '') { 
  		//$original_img   = $image->upload_image($_FILES['img']); // 原始图片	
 		$link['img'] = $image->upload_image($_FILES['img']);
	}
	$link['des']= $des;
	$link['link_name']= $link_name;
	$link['url']= $url;
	$link['sort_order']= $sort_order;
	if($id){
		$db->autoExecute($table,$link,'UPDATE','id = '.$id);
	}else {
		$db->autoExecute($table,$link);
	}
	header('Location:affiliate_links_list.php?act=recommend_list');
	exit();
}
elseif ($act == 'recommend_list') { //推荐的链接
	$_ACT = 'affiliate_recommend_list';
	admin_priv('recommend_list');
	$link_arr = $db->arrQuery("select * from $table order by  sort_order ,id desc");	
	$Arr['link_arr'] = $link_arr;	
}
elseif ($act == 'del_link') {//删除推荐的链接
	admin_priv('recommend_list');
	$link_ids = empty($_POST['checkboxes'])?0:$_POST['checkboxes'];
	$link_arr = $db->arrQuery("delete  from eload_wj_recommend_link  where id ".db_create_in($link_ids));
	header('Location:affiliate_links_list.php?act=recommend_list');
	exit();
}
elseif ($act == 'add') {//删除推荐的链接
	admin_priv('recommend_list');
	$_ACT = 'affiliate_recommend_list';
	$id = empty($_GET['id'])?0:$_GET['id'];
	$link = array();
	if($id){
		$link = $db->selectInfo("select * from $table  where id ='$id'");
	}
	$Arr['link'] =  $link;
}
else
{
    /* 权限检查 */
    admin_priv('affiliate_links_list');
    $cat_id = empty($_GET['cat_id']) ? 0 : intval($_GET['cat_id']);
    $fangshi = empty($_GET['fangshi']) ? 1 : intval($_GET['fangshi']);
    $Arr['cat_list'] = cat_list($cat_id);
    $Arr['fangshi'] = $fangshi;		
    $linkArr = get_affiliate_links();	
    $Arr['linkArr'] = $linkArr['linkArr'];
    $Arr['tj'] = $linkArr['tj'];	
    $sort_flag           = sort_flag($linkArr['filter']);	
	$Arr[$sort_flag['tag']] = $sort_flag['img'];	
	$goods_order_data['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
    $Arr['filter'] = $goods_order_data['filter'];
	$page=new page(array('total'=>$linkArr['record_count'],'perpage'=>$linkArr['page_size']));	
	$Arr["pagestr"]  = $page->show();
	$Arr["userArr"]  = $db->arrQuery("select user_id,email from ".USERS.' where user_type=2');
    $Arr['start_date']  = local_date('Y-m-d', isset($_REQUEST['start_date'])?$_REQUEST['start_date']:'');
    $Arr['end_date']    = local_date('Y-m-d', isset($_REQUEST['end_date'])?$_REQUEST['end_date']:'');
    $Arr['start_date2'] = local_date('Y-m-d', isset($_REQUEST['start_date2'])?$_REQUEST['start_date2']:'');
    $Arr['end_date2']   = local_date('Y-m-d', isset($_REQUEST['end_date2'])?$_REQUEST['end_date2']:'');	
	$Arr["search_url"]  = get_url_parameters($_GET,array('sort_by','sort_order'));
	$Arr["search_url"]  = get_url_parameters($_GET,array('sort_by','sort_order'));
	$search_url2 = get_url_parameters($_GET,array('act'));	
    $Arr['action_link'] =      array('text' => '销售排行报表下载', 'href' => $search_url2 );
    $_ACT ='affiliate_links_list';
}

/*------------------------------------------------------ */
//--排行统计需要的函数
/*------------------------------------------------------ */
/**
 * 取得销售排行数据信息
 * @param   bool  $is_pagination  是否分页
 * @return  array   销售排行数据
 */
function get_affiliate_links($is_pagination = true)
{
	global $db;	
    //$slave_db = get_slave_db();
	$filter['start_date'] = empty($_REQUEST['start_date'])?'':$_REQUEST['start_date'];
	$filter['end_date'] = empty($_REQUEST['end_date'])?'':$_REQUEST['end_date'];
	$filter['start_date2'] = empty($_REQUEST['start_date2'])?'':$_REQUEST['start_date2'];
	$filter['end_date2'] = empty($_REQUEST['end_date2'])?'':$_REQUEST['end_date2'];
	$filter['user_id'] = empty($_REQUEST['user_id']) ? '' : trim($_REQUEST['user_id']);
	$filter['email']   = empty($_REQUEST['email'])?'':$_REQUEST['email'];
	$filter['id']   = empty($_REQUEST['link_id'])?0:intval($_REQUEST['link_id']);
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    $filter['order_status'] = !isset($_REQUEST['order_status']) ? '-1' : intval($_REQUEST['order_status']);
	$filter['fb_user_id'] = empty($_REQUEST['fb_user_id'])?'':$_REQUEST['fb_user_id'];
	$filter['fb_email']	= empty($_REQUEST['fb_email'])?'':$_REQUEST['fb_email'];
	$filter['goods_sn']	= empty($_REQUEST['goods_sn'])?'':$_REQUEST['goods_sn'];
	$filter['fb_share'] = empty($_REQUEST['fb_share'])?'':$_REQUEST['fb_share'];	
	$filter['linkid']   = empty($_REQUEST['linkid'])?'':$_REQUEST['linkid'];		
	if($filter['fb_user_id'] || $filter['fb_email'] || $filter['goods_sn']|| $filter['fb_share']){
		$where = " WHERE u.user_id = s.user_id and l.id = s.link_id ";
	}else{
		$where = " WHERE u.user_id = l.user_id ";
	}
	$ip_where = ' ';
    if ($filter['start_date'])
    {		
        $where .= " AND l.adddate >= '" . $filter['start_date'] . "'";
    }

    if ($filter['linkid'])
    {		
        $where .= " AND l.id = '" . $filter['linkid'] . "'";
    }
    if ($filter['end_date'])
    {
        $where .= " AND l.adddate <= '" . $filter['end_date'] . "'";
    }
    if ($filter['start_date2'])
    {		
        $ip_where .= " AND adddate >= '" . $filter['start_date2'] . "'";
    }
    if ($filter['end_date2'])
    {
        $ip_where .= " AND adddate <= '" . $filter['end_date2'] . "'";
    }			
    if ($filter['user_id'])
    {
        $where .= " AND l.user_id='".$filter['user_id']."'";
    }
    if ($filter['email'])
    {
        $where .= " AND u.email='".$filter['email']."'";
    }
    if ($filter['id'])
    {
        $where .= " AND l.id='".$filter['id']."'";
    }
	if($filter['fb_user_id']){
		$where .= " and s.fb_uid ='".$filter['fb_user_id']."'";
	}
	if($filter['fb_email']){
	
		$where .= " and s.email='".$filter['fb_email']."'";
	}
	if($filter['goods_sn']){
		$where .= " and s.goods_sn= '".$filter['goods_sn']."'";
	}
	if($filter['fb_user_id'] || $filter['fb_email'] || $filter['goods_sn'] || $filter['fb_share']){
			$sql = "SELECT COUNT(*) FROM " .
           WJ_LINK . ' AS l,'.USERS." AS u ,".WJ_SHARE." AS s ".$where;
	
	}
    else{
		$sql = "SELECT COUNT(*) FROM " .
           WJ_LINK . ' AS l,'.USERS." AS u ".$where;
	}
    //$filter['record_count'] = $slave_db->getOne($sql);
	$filter['record_count'] = $db->getOne($sql);    

    /* 分页大小 */
    $filter = page_and_size($filter);
	if($filter['fb_user_id'] || $filter['fb_email'] || $filter['goods_sn'] || $filter['fb_share']){
		$sql = "SELECT l.*,u.email ,(select count(distinct(ips)) from ".WJ_IP." where from_linkid = l.id $ip_where ) as DIP,(select count(ips) from ".WJ_IP." where from_linkid = l.id $ip_where ) as visit_count  " .
           "  ";   
		if (trim($ip_where)) {//注册数及订单数根据时间查询，而不是总数 by mashanling on 2013-02-20 14:52:22
			$sql .= ',(SELECT COUNT(*) FROM ' . USERS . ' WHERE wj_linkid=l.id ' . str_replace('adddate', 'reg_time', $ip_where) . ') AS reg_count';
			$sql .= ',(SELECT COUNT(*) FROM ' . ORDERINFO . ' WHERE wj_linkid=l.id ' . str_replace('adddate', 'add_time', $ip_where) . ') AS order_count';
		}
		$sql .= " ,s.email as fb_email,s.fb_uid as fb_user_id ,s.goods_sn FROM ".WJ_LINK." AS l, " .
			   USERS." AS u ,".WJ_SHARE." as s ".$where .
			   "  ".
			   ' ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'] ;
	
	}
    else{
		 $sql = "SELECT l.*,u.email ,(select count(distinct(ips)) from ".WJ_IP." where from_linkid = l.id $ip_where ) as DIP,(select count(ips) from ".WJ_IP." where from_linkid = l.id $ip_where ) as visit_count " .
           "  ";
		if (trim($ip_where)) {//注册数及订单数根据时间查询，而不是总数 by mashanling on 2013-02-20 14:52:22
			$sql .= ',(SELECT COUNT(*) FROM ' . USERS . ' WHERE wj_linkid=l.id ' . str_replace('adddate', 'reg_time', $ip_where) . ') AS reg_count';
			$sql .= ',(SELECT COUNT(*) FROM ' . ORDERINFO . ' WHERE wj_linkid=l.id ' . str_replace('adddate', 'add_time', $ip_where) . ') AS order_count';
		}
		$sql .= " FROM ".WJ_LINK." AS l, " .
			   USERS." AS u ".$where .
			   "  ".
			   ' ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'] ;
	}
   
    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
	
    //$linkArr = $slave_db->getAll($sql);
	$linkArr = $db->getAll($sql);
    $link_ids = arr2str($linkArr,'id');
	$sql = "select wj_linkid,sum(order_amount) as order_amount from eload_order_info where order_status>0 and order_status<10";
	if(!empty($link_ids)) {
		$sql .= " and wj_linkid in($link_ids)";
	}
	$sql .= " group by wj_linkid";
    $order_sum_arr = $db->arrQuery($sql);
    $order_sum_arr = fetch_id($order_sum_arr,'wj_linkid');
	
	//fangxin 2013-10-25
	//$pay_order_wheres = str_replace('adddate', 'reg_time', $ip_where);
    //$sql_count = 'select order_status,sum(order_amount) as paya,count(order_id) as payc from '.ORDERINFO;
    	
	
    foreach ($linkArr as $key => $item)
    {        
        //$linkArr[$key]['link_url_jump'] = preg_match ('/\?/', $item['link_url']) ? $item['link_url'] ."&lkid=" . $item['id'] : $item['link_url'] ."?lkid=" . $item['id'];
		/*
		//fangxin 2013-10-25
    	$countArr = $GLOBALS['db']->arrQuery($sql_count.' where wj_linkid = '.$item['id'].$pay_order_wheres.' group by order_status');
        $linkArr[$key]['nopay_order_count'] = 0;
        $linkArr[$key]['pay_order_count'] = 0;
        $linkArr[$key]['nopay_order_acount'] = 0;
        $linkArr[$key]['pay_order_acount'] = 0;
        foreach ($countArr as $kc => $vc) {
            if($vc['order_status'] == 0){
                $linkArr[$key]['nopay_order_count'] = $vc['payc'];
                $linkArr[$key]['nopay_order_acount'] = $vc['paya'];
            }
            if($vc['order_status'] > 1 && $vc['order_status'] < 9){
                $linkArr[$key]['pay_order_count'] += $vc['payc'];
                $linkArr[$key]['pay_order_acount'] += $vc['paya'];
            }
        }
		*/		
		
        $s   = false === strpos($item['link_url'], '#') ? '#' : '';
        $linkArr[$key]['link_url_jump'] = $item['link_url'] . $s . 'lkid=' . $item['id'];        
        $linkArr[$key]['sno']       = $key + 1;
        $linkArr[$key]['order_amount']       = empty($order_sum_arr[$item['id']][0]['order_amount'])?0:$order_sum_arr[$item['id']][0]['order_amount'];
        $linkArr[$key]['adddate']    =  local_date($GLOBALS['_CFG']['time_format'], $item['adddate']);       
    }
	if($filter['fb_user_id'] || $filter['fb_email'] || $filter['goods_sn'] || $filter['fb_share']){
		$sql = "select l.id,SUM(l.reg_count) as reg_count  ,SUM(l.order_count) as order_count, sum((select count(distinct(ips)) from ".WJ_IP." where from_linkid = l.id $ip_where )) as DIP ,SUM((select count(ips) from ".WJ_IP." where from_linkid = l.id $ip_where )) as visit_count ,s.email as fb_email,s.fb_uid as fb_user_id ,s.goods_sn ".
           "FROM ".WJ_LINK." AS l, " .
		   USERS." AS u ,".WJ_SHARE." as s " .$where;
	}else{
		$sql = "select l.id,SUM(l.reg_count) as reg_count  ,SUM(l.order_count) as order_count, sum((select count(distinct(ips)) from ".WJ_IP." where from_linkid = l.id $ip_where )) as DIP ,SUM((select count(ips) from ".WJ_IP." where from_linkid = l.id $ip_where )) as visit_count ".
           "FROM ".WJ_LINK." AS l, " .
		   USERS." AS u " .$where;	
	}
	//$tj = $slave_db->selectinfo($sql);
	$tj = $db->selectinfo($sql);
	//$tj	= array('id'=>1,'reg_count'=>115267,'order_count'=>55640,'DIP'=>3611969,'visit_count'=>4889936);
	
	if($filter['fb_user_id'] || $filter['fb_email'] || $filter['goods_sn'] || $filter['fb_share']){
       $sql = "select l.id ".
           "FROM ".WJ_LINK." AS l, " .
           USERS." AS u " .$where;
    }else{
        $sql = "select l.id ".
           "FROM ".WJ_LINK." AS l, " .
           USERS." AS u ,".WJ_SHARE." as s " .$where;
    }
    //$tjArr = $GLOBALS['db']->arrQuery($sql_count.' where wj_linkid in ('.$sql.')'.$pay_order_wheres.' group by order_status');

    $tj['nopay_order_count'] = 0;
    $tj['pay_order_count'] = 0;
    $tj['nopay_order_acount'] = 0;
    $tj['pay_order_acount'] = 0;
	if(is_array($tjArr)) {
		foreach ($tjArr as $kc => $vc) {
			if($vc['order_status'] == 0){
				$tj['nopay_order_count'] = $vc['payc'];
				$tj['nopay_order_acount'] = $vc['paya'];
			}
			if($vc['order_status'] > 1 && $vc['order_status'] < 9){
				$tj['pay_order_count'] += $vc['payc'];
				$tj['pay_order_acount'] += $vc['paya'];
			}
		}	
	}
	
	if(!empty($linkArr))
	{
		foreach($linkArr as $k=>$v){
			$linkArr[$k]['email']			= email_disp_process($v['email']);
			if(!empty($v['fb_email']))
			{
				$linkArr[$k]['fb_email']	= email_disp_process($v['fb_email']);
			}
		}
	}
	
    $arr = array('linkArr' => $linkArr, 'filter' => $filter,'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count'],'tj'=>$tj);
    return $arr;
}

$_ACT = empty($_ACT)?$act:$_ACT;
temp_disp();
?>