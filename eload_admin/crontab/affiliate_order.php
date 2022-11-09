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
$_REQUEST['end_date']   = empty($_REQUEST['end_date'])?'':local_strtotime($_REQUEST['end_date'].' 23:59:59');

$_REQUEST['start_date2'] = empty($_REQUEST['start_date2'])?'':local_strtotime($_REQUEST['start_date2']);
$_REQUEST['end_date2']   = empty($_REQUEST['end_date2'])?'':local_strtotime($_REQUEST['end_date2'].' 23:59:59');

$_REQUEST['sort_by']    = empty($_REQUEST['sort_by'])?'':$_REQUEST['sort_by'];
$_REQUEST['sort_order'] = empty($_REQUEST['sort_order'])?'':$_REQUEST['sort_order'];
$_REQUEST['goods_sn']   = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
$_REQUEST['linkid']     = empty($_REQUEST['linkid']) ? '' : intval($_REQUEST['linkid']);
$_REQUEST['com_is_fa']  = empty($_REQUEST['com_is_fa']) ? '' : intval($_REQUEST['com_is_fa']);




//$_REQUEST['start_date'] = empty($_REQUEST['start_date'])?local_strtotime('-1 months'):local_strtotime($_REQUEST['start_date']);
//$_REQUEST['end_date'] = empty($_REQUEST['end_date'])?local_strtotime('+1 day'):local_strtotime($_REQUEST['end_date']);
//
//
//$_REQUEST['start_date2'] = empty($_REQUEST['start_date2'])?local_strtotime('-1 months'):local_strtotime($_REQUEST['start_date2']);
//$_REQUEST['end_date2'] = empty($_REQUEST['end_date2'])?local_strtotime('+1 day'):local_strtotime($_REQUEST['end_date2']);

$_REQUEST['act'] = empty($_REQUEST['act'])?'':$_REQUEST['act'];



if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' ||  $_REQUEST['act'] == 'download'))
{
    admin_priv('affiliate_order');

    /* 下载报表 */
    if ($_REQUEST['act'] == 'download')
    {
        $goods_order_data = get_sales_order(false);
		$tj = $goods_order_data['tj'];
        $goods_order_data = $goods_order_data['sales_order_data'];

        $filename = local_date('Y-m-d', $_REQUEST['start_date']). '_' . local_date('Y-m-d', $_REQUEST['end_date']).'sale_order';

        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$filename.xls");
		
    }
	
    $goods_order_data = get_sales_order();
    $Arr['goods_order_data'] = $goods_order_data['sales_order_data'];
    $Arr['filter'] =       $goods_order_data['filter'];
    $Arr['record_count'] = $goods_order_data['record_count'];
    $Arr['page_count'] =   $goods_order_data['page_count'];
	
    $Arr['start_date'] =       local_date('Y-m-d', $_REQUEST['start_date']);
    $Arr['end_date'] =         local_date('Y-m-d', $_REQUEST['end_date']);
	
    $sort_flag  = sort_flag($goods_order_data['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];

}
else
if($_REQUEST['act'] == 'batch'){
	
	
	
	admin_priv('affiliate_order');
    $order_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
	
	
	
	$GoodsSnArrTemp = array();
	$GoodsSnArr = $db->arrQuery("select order_sn from ".ORDERINFO." WHERE order_id " . db_create_in($order_id));
	foreach($GoodsSnArr as $val){
		$GoodsSnArrTemp[] = $val['order_sn'];
	}
	$order_sn = implode('，',$GoodsSnArrTemp);
	
	
    $BatchStr = '';
	
    $_TYPE = !empty($_POST['btnSubmit'])?trim($_POST['btnSubmit']):'';
	
	
	
	
	if ($_TYPE == '已发放') {
		$sql = "update ".ORDERINFO." set com_is_fa = 2 where order_id " . db_create_in($order_id);	
		echo $sql;
		$db->query($sql);
		$BatchStr = $order_sn.'改成了佣金已发放';
	}
	
	if ($_TYPE == '未发放') {
		$sql = "update ".ORDERINFO." set com_is_fa = 1 where order_id " . db_create_in($order_id);	
		$db->query($sql);
		$BatchStr = $order_sn.'改成了佣金未发放';
	}
	
	$link[0]["name"] = "返回上一页";
	$link[0]["url"] = $_SERVER["HTTP_REFERER"];
    sys_msg("批量操作成功", 0, $link);
	
}
else
{
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
	$search_url2 = get_url_parameters($_GET,array('act'));
	
    $Arr['action_link'] =      array('text' => '销售排行报表下载', 'href' => $search_url2 );
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
	
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'order_id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    $filter['order_status'] = !isset($_REQUEST['order_status']) ? '-1' : intval($_REQUEST['order_status']);
    $filter['linkid']    = empty($_REQUEST['linkid']) ? '' : intval($_REQUEST['linkid']);
    $filter['com_is_fa'] = empty($_REQUEST['com_is_fa']) ? '' : intval($_REQUEST['com_is_fa']);
	
	$filter['add_user']   = empty($_REQUEST['add_user'])?'':$_REQUEST['add_user'];
    $where = " WHERE o.wj_linkid = wj.id  and u.user_id = wj.user_id ";

   if ($filter['order_status'] >'-1'){
	   if ($filter['order_status'] == '0') {
		   $where .= " AND  o.order_status = '0'";
	   }else{
           $where .= " AND  o.order_status >0 and o.order_status < 9 ";
	   }
   }
   
   if ($filter['linkid']){
		   $where .= " AND  o.wj_linkid = '".$filter['linkid']."'";
   }
   if ($filter['com_is_fa']){
		   $where .= " AND  o.com_is_fa = '".$filter['com_is_fa']."'";
   }

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
	

    $sql = "SELECT COUNT(o.order_sn) FROM " .
           ORDERINFO . ' AS o,'.
 		   USERS." AS u ,".
          WJ_LINK . ' AS wj '.
           $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

echo $sql;
    

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "SELECT o.order_sn,o.order_amount, o.com_is_fa,o.order_id,o.user_id,o.add_time,wj.id,wj.user_id,u.email,u.com_rate,o.order_status,wj.link_url,wj.img,wj.link_text,o.pay_time " .
           "  ".
           "FROM ".WJ_LINK." AS wj, " .
		   USERS." AS u ,".
           ORDERINFO." AS o  " .$where .
           "  ".
           ' ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'] ;
    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
	//echo $sql;
	
    $sales_order_data = $GLOBALS['db']->getAll($sql);
    foreach ($sales_order_data as $key => $item)
    {
        $sales_order_data[$key]['id'] = $item['id'];
        $sales_order_data[$key]['email'] = $item['email'];
        $sales_order_data[$key]['link_url'] = $item['link_url'];
        $sales_order_data[$key]['link_url_jump'] = preg_match ('/\?/', $item['link_url']) ? $item['link_url'] ."&lkid=" . $item['id'] : $item['link_url'] ."?lkid=" . $item['id'];
        $sales_order_data[$key]['img'] = $item['img'];
        $sales_order_data[$key]['link_text'] = $item['link_text'];
        $sales_order_data[$key]['order_sn'] = $item['order_sn'];
		$sales_order_data[$key]['order_status_str'] = $GLOBALS['_LANG']['os'][$item['order_status']];
        $sales_order_data[$key]['order_amount'] = price_format($item['order_amount']);
        $sales_order_data[$key]['pay_amount'] = price_format($item['order_amount'] * $item['com_rate']);
        $sales_order_data[$key]['taxis']       = $key + 1;
        $sales_order_data[$key]['add_time']    =  local_date($GLOBALS['_CFG']['time_format'], $item['add_time']);
        $sales_order_data[$key]['pay_time']    =  local_date($GLOBALS['_CFG']['time_format'], $item['pay_time']);
    }
	
	$sql = "select SUM(o.order_amount * u.com_rate) as com_money ,SUM(o.order_amount) as turnover  ".
           "FROM ".WJ_LINK." AS wj, " .
		   USERS." AS u ,".
           ORDERINFO." AS o  " .$where ;
		 //  echo 
	$tj = $GLOBALS['db']->selectinfo($sql);
	
	//$sql = "select oi.order_sn, oi.order_amount ".
      //     "FROM ".ODRGOODS." AS og, ".GOODS." AS g, ".
      //     ORDERINFO." AS oi  " .$where .' group by oi.order_sn ';
	//$tjArr = $GLOBALS['db']->arrQuery($sql);
	//$tj['turnover'] = 0;
	//foreach($tjArr as $val){
	//	$tj['turnover'] = $tj['turnover'] + $val['order_amount'];
	//}
   $tj['turnover'] = price_format($tj['turnover']);
   $tj['com_money']    = price_format($tj['com_money']);
	
    $arr = array('sales_order_data' => $sales_order_data, 'filter' => $filter,'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count'],'tj'=>$tj);
    return $arr;
}
$_ACT = 'affiliate_order';
temp_disp();//

?>