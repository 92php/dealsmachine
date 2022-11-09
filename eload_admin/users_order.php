<?php

/**
 * 会员排行统计程序
*/

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');


/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'order_num';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}


/* 权限判断 */
admin_priv('users_order');

/* 时间参数 */
if ( !empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date']))
{
    $start_date = strtotime($_REQUEST['start_date']);
    $end_date   = strtotime($_REQUEST['end_date']);
}
else
{
    $today  = strtotime(date('Y-m-d'));
    $start_date = $today - 86400 * 7;
    $end_date   = $today;
}

/* 根据用户条件生成报表显示的数量*/
$show_num   = (!empty($_REQUEST['show_num'])) ? intval($_REQUEST['show_num']) : 15;

/*------------------------------------------------------ */
//--按订单数量排行
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'order_num')
{
    /* 取得会员排行数据 */
    $user_orderinfo = get_user_orderinfo($show_num, 'order_num', $start_date, $end_date);
	$Arr['user_orderinfo'] = $user_orderinfo;
	$start_date = date('Y-m-d', $start_date);
	$end_date = date('Y-m-d', $end_date);
	$Arr['start_date']     =   $start_date;
	$Arr['end_date']       = $end_date;
	$Arr['action_link'] =  "users_order.php?act=turnover&start_date=$start_date&end_date=$end_date&orderby=turnover";
	$Arr['action_link2'] =  "users_order.php?act=order_num&start_date=$start_date&end_date=$end_date&orderby=order_num";
	$Arr['form_act']       = 'order_num';
	$Arr['show_num']       = $show_num;
}
/*------------------------------------------------------ */
//--按购物金额排行
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'turnover')
{
    /* 取得会员排行数据 */
    $user_orderinfo = get_user_orderinfo($show_num, 'turnover', $start_date, $end_date);
	$start_date = date('Y-m-d', $start_date);
	$end_date = date('Y-m-d', $end_date);
	$Arr['start_date']     =   $start_date ;
	$Arr['end_date']       = $end_date ;

    /* 赋值到模板 */
	$Arr['action_link'] =  "users_order.php?act=turnover&start_date=$start_date&end_date=$end_date&orderby=turnover";
	$Arr['action_link2'] =  "users_order.php?act=order_num&start_date=$start_date&end_date=$end_date&orderby=order_num";
	$Arr['user_orderinfo'] = $user_orderinfo;
	$Arr['form_act']       = 'order_num';
	$Arr['show_num']       = $show_num;
}
if ($_REQUEST['act'] == 'download')
{
    $start_date = $_REQUEST['start_date'];
    $end_date   = $_REQUEST['end_date'];

    $user_orderinfo = get_user_orderinfo($show_num, 'turnover', $start_date, $end_date);
    $filename = $start_date . '_' . $end_date . 'users_order';

    header("Content-type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename.xls");

    $data = "$_LANG[visit_buy]\t\n";
    $data .= "$_LANG[order_by]\t$_LANG[member_name]\t$_LANG[order_amount]\t$_LANG[buy_sum]\t\n";

    foreach ($user_orderinfo AS $k => $row)
    {
        $order_by = $k + 1;
        $data .= "$order_by\t$row[user_name]\t$row[order_num]\t$row[turnover]\n";
    }
    echo ecs_iconv(EC_CHARSET, 'GB2312', $data);
    exit;
}
/*------------------------------------------------------ */
//--会员排行需要的函数
/*------------------------------------------------------ */
/*
 * 取得会员订单量/购物额排名统计数据
 *
 * @param   int             $show_num        每页显示的数量
 * @param   timestamp       $start_date      开始时间
 * @param   timestamp       $end_date        结束时间
 * @return  array                            会员购物排行数据
 */
 function get_user_orderinfo($show_num, $order_by, $start_date, $end_date)
 {
    global $db, $ecs;

    $where = "WHERE u.user_id = o.user_id ".
             "AND u.user_id > 0 " ;
    $limit = " LIMIT " .$show_num;

    if ($start_date)
    {
        $where .= "AND o.add_time >= '$start_date' ";
    }

    if ($end_date)
    {
        $where .= "AND o.add_time <= '$end_date' ";
    }

    /* 计算订单各种费用之和的语句 */
    $total_fee = " SUM(order_amount) AS turnover ";

    if ($order_by == 'order_num')
    {
        /* 按订单数量来排序 */
        $sql = "SELECT u.user_id, u.firstname,u.lastname,u.email, COUNT(*) AS order_num, " .$total_fee.
               "FROM ".USERS." AS u, ".ORDERINFO." AS o " .$where .
               "GROUP BY u.user_id ORDER BY order_num DESC, turnover DESC" . $limit;
    }
    else
    {
        /* 按购物金额来排序 */
        $sql = "SELECT u.user_id, u.firstname,u.lastname,u.email, COUNT(*) AS order_num, " .$total_fee.
               "FROM ".USERS." AS u, ".ORDERINFO." AS o " .$where .
               "GROUP BY u.user_id ORDER BY turnover DESC, order_num DESC" . $limit;
    }

    $user_orderinfo = array();
    $res = $db->query($sql);

    while ($items = $db->fetchRow($res))
    {
        $items['turnover'] = price_format($items['turnover']);
        $items['email']		= email_disp_process($items['email']);
        $user_orderinfo[] = $items;
    }

    return $user_orderinfo;
}

$_ACT = 'order';
$_ACT = $_ACT == 'msg'?'msg':'users_'.$_ACT;
temp_disp();

?>