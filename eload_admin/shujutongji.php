<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/lib_order.php');
require_once(ROOT_PATH . 'lib/class.page.php');

$_REQUEST['start_date'] = empty($_REQUEST['start_date'])?'':local_strtotime($_REQUEST['start_date']);
$_REQUEST['end_date'] = empty($_REQUEST['end_date'])?'':local_strtotime($_REQUEST['end_date']);

$_REQUEST['time_jainge']    = empty($_REQUEST['time_jainge'])?'60':intval($_REQUEST['time_jainge']);

$_REQUEST['sort_by']    = empty($_REQUEST['sort_by'])?'':$_REQUEST['sort_by'];
$_REQUEST['sort_order'] = empty($_REQUEST['sort_order'])?'':$_REQUEST['sort_order'];
$_REQUEST['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);

$_REQUEST['order_num'] = empty($_REQUEST['order_num']) ? '' : intval($_REQUEST['order_num']);
$_REQUEST['intro_type'] = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);


//$_REQUEST['start_date'] = empty($_REQUEST['start_date'])?local_strtotime('-1 months'):local_strtotime($_REQUEST['start_date']);
//$_REQUEST['end_date'] = empty($_REQUEST['end_date'])?local_strtotime('+1 day'):local_strtotime($_REQUEST['end_date']);
//
//
//$_REQUEST['start_date2'] = empty($_REQUEST['start_date2'])?local_strtotime('-1 months'):local_strtotime($_REQUEST['start_date2']);
//$_REQUEST['end_date2'] = empty($_REQUEST['end_date2'])?local_strtotime('+1 day'):local_strtotime($_REQUEST['end_date2']);


/* 权限检查 */
admin_priv('shujutongji');



if(!empty($_GET)){
	
	
	$jian_ge = $_REQUEST['end_date'] - $_REQUEST['start_date'];
	if ($jian_ge <= 0){
		 sys_msg("时间选择错误！", 1, array(), false);
	}
	
	$ever_times = ceil($jian_ge/(60*$_REQUEST['time_jainge']));
	
	
	
	$page = empty($_GET['page'])?1:intval($_GET['page']);
    $filter = page_and_size(array());
	
	$ssss = $filter['page_size']  * ($page - 1) + 1 ;
	$eeee = $filter['page_size']  * $page;
	if ($eeee>$ever_times)
	    $eeee = $ever_times;
	
	$res = array();
	for ($i=$ssss;$i<=$eeee;$i++){
		$row = array();
		$stime = $_REQUEST['start_date']+$_REQUEST['time_jainge']*60*($i-1);
		$etime = $_REQUEST['start_date']+$_REQUEST['time_jainge']*60*$i;
		
		$row['time_start'] = local_date('Y-m-d H:i:s', $stime) .' - '. local_date('Y-m-d H:i:s', $etime);
		
		$sql = "select count(*) from eload_users where reg_time >= '".$stime."' and reg_time < '".$etime."'";
		$row['reg_num'] = $db->getOne($sql);
		
		$sql = "select count(*) from eload_order_info where add_time >= '".$stime."' and add_time < '".$etime."'  ";
		$row['order_num'] = $db->getOne($sql);
		
		$sql = "select count(*) from eload_order_info where add_time >= '".$stime."' and add_time < '".$etime."' and order_status > 0 ";
		$row['order_pay_num'] = $db->getOne($sql);
		
		$sql = "select count(DISTINCT session_id) from eload_cart where addtime >= '".$stime."' and addtime < '".$etime."' ";
		$row['cart_user_num'] = $db->getOne($sql);
		
		$sql = "select count(goods_id) from eload_cart where addtime >= '".$stime."' and addtime < '".$etime."'";
	
		$row['cart_goods_num'] = $db->getOne($sql);
		
		
		$sql = "select count(user_id) from eload_user_login_log where login_time >= '".$stime."' and login_time < '".$etime."'";
		$row['login_num'] = $db->getOne($sql);
		
		$res[] = $row;
		
	}
	
	
	
	$Arr['tongji_data'] = $res;
	$page=new page(array('total'=>$ever_times,'perpage'=>$filter['page_size']));
	$Arr["pagestr"]  = $page->show();
}





$Arr['time_jainge'] = array(0=>'10',1=>'20',2=>'30',3=>'40',4=>'50',5=>'60');




$Arr['start_date'] = local_date('Y-m-d H:i:s', $_REQUEST['start_date']);
$Arr['end_date']   = local_date('Y-m-d H:i:s', $_REQUEST['end_date']);

$Arr['goods_sn']   = $_REQUEST['goods_sn'];
$Arr['order_num']  = $_REQUEST['order_num'];


$Arr["search_url"] = '&start_date='.$Arr['start_date'].'&end_date='.$Arr['end_date'];
$search_url2 = '?act=download&sort_by='.$_REQUEST['sort_by'].'&sort_order='.$_REQUEST['sort_order'].$Arr["search_url"];
	

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
	
$isping = '';
if($_SESSION["WebUserInfo"]["sa_user"] == 'haoren')
     $isping = ' and oi.is_ping = 0';
	
    $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
    $filter['end_date']   = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
	
    $filter['start_date2'] = empty($_REQUEST['start_date2']) ? '' : $_REQUEST['start_date2'];
    $filter['end_date2']   = empty($_REQUEST['end_date2']) ? '' : $_REQUEST['end_date2'];
	
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'goods_num' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    $filter['cat_id']     = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
	$filter['add_user']   = empty($_REQUEST['add_user'])?'':$_REQUEST['add_user'];
    $filter['intro_type'] = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);
	
	
    $where = " WHERE og.order_id = oi.order_id and g.goods_id = og.goods_id and oi.order_status > 0 and oi.order_status < 9  $isping ";



        /* 推荐类型 */
        switch ($filter['intro_type'])
        {
            case 'is_free_shipping':
                $where .= " AND g.is_free_shipping=1";
                 break;
            case 'is_best':
                $where .= " AND g.is_best=1";
                 break;
           case 'on_sale':
                $where .= " AND g.is_on_sale=1";
                break;
            case 'not_on_sale':
                $where .= " AND g.is_on_sale=0";
                break;
            case 'is_hot':
                $where .= ' AND g.is_hot=1';
            case 'is_bighot':
                $where .= ' AND g.is_bighot=1';
                break;
            case 'is_new':
                $where .= ' AND g.is_new=1';
                break;
            case 'is_promote':
                $where .= " AND g.is_promote = 1 ";
                break;
            case 'all_type';
                $where .= " AND (g.is_best=1 OR g.is_hot=1 OR g.is_new=1 OR g.is_promote = 1)";
        }

   $subwhere = ' ';

    if ($filter['start_date'])
    {		
        $where .= " AND oi.add_time >= '" . $filter['start_date'] . "'";
        $subwhere .= " AND daytime >= '" . $filter['start_date'] . "'";
    }
    if ($filter['end_date'])
    {
        $where .= " AND oi.add_time <= '" . $filter['end_date'] . "'";
        $subwhere .= " AND daytime <= '" . $filter['end_date'] . "'";
    }
	
	
	if ($_REQUEST['goods_sn'])
	{
        $where .= " AND g.goods_sn = '" .$_REQUEST['goods_sn'] . "'";
	}
	
	if ($_REQUEST['order_num'] > 0)
	{
		$tempArr = array();
		$sql = " select user_id from eload_order_info where order_status >0 AND order_status <9  group by user_id HAVING count(order_id) >= '".$_REQUEST['order_num']."' ";
		$gArr = $GLOBALS['db']->arrQuery($sql);
		if (!empty($gArr)){
			foreach($gArr as $val){
				$tempArr[] = $val['user_id'];
			}			
			$where .= " and user_id ".db_create_in($tempArr);
		}else{
			$where .= " and user_id  in ('0')";
		}
		
		//print_r($tempArr);
		
		
		 //$where .= " and user_id id ( ) ";
///		 in('123','124')
	}
	//add_time >= '" . $filter['start_date'] . "' and add_time <= '" . $filter['end_date'] . "' exists
    if ($filter['start_date2'])
    {		
        $where .= " AND g.add_time >= '" . $filter['start_date2'] . "'";
    }
    if ($filter['end_date2'])
    {
        $where .= " AND g.add_time <= '" . $filter['end_date2'] . "'";
    }
	
	
    if ($filter['cat_id'])
    {
		$children = get_children($filter['cat_id']);
        $where .= " AND $children ";
    }
	
    if ($filter['add_user'])
    {
        $where .= " AND g.add_user = '" . $filter['add_user'] . "'";
    }
	

    $sql = "SELECT COUNT(distinct(og.goods_id)) FROM " .
           ORDERINFO . ' AS oi,'.
           GOODS . ' AS g,'.
           ODRGOODS . ' AS og '.
           $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);


    

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "SELECT og.goods_id, g.goods_sn, g.goods_title, g.goods_thumb,(select sum(hitnum) from ".GOODS_HITS." where goods_id = og.goods_id $subwhere ) as click_count , count(oi.order_id) as order_num,  g.cat_id,g.add_time,g.add_user, oi.order_status, " .
           "SUM(og.goods_number) AS goods_num, SUM(og.goods_number * og.goods_price) AS turnover ".
           "FROM ".ODRGOODS." AS og, ".GOODS." AS g, " .
           ORDERINFO." AS oi  " .$where .
           " GROUP BY og.goods_sn ".
           ' ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'] ;
    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
	//echo $sql;
	
    $sales_order_data = $GLOBALS['db']->getAll($sql);
    $catArr = read_static_cache('category_c_key',2);    
    foreach ($sales_order_data as $key => $item)
    {
        $sales_order_data[$key]['wvera_price'] = price_format($item['goods_num'] ? $item['turnover'] / $item['goods_num'] : 0);
        $sales_order_data[$key]['short_name']  = sub_str($item['goods_title'], 30, true);
        $sales_order_data[$key]['goods_name']  = $item['goods_title'];
        $sales_order_data[$key]['add_time']    =  local_date('Y-m-d', $item['add_time']);
        $sales_order_data[$key]['turnover']    = price_format($item['turnover']);
        $sales_order_data[$key]['cat_id']      = empty($catArr[$item['cat_id']]['cat_name'])?'':$catArr[$item['cat_id']]['cat_name'];
        $sales_order_data[$key]['taxis']       = $key + 1;
		//echo $sales_order_data[$key]['click_count'].'<br>';
		
        $sales_order_data[$key]['con_lv'] = !empty($sales_order_data[$key]['click_count'])?round($item['goods_num']/$sales_order_data[$key]['click_count'],4)*100 :0;
        $sales_order_data[$key]['shiji_con_lv'] = !empty($sales_order_data[$key]['click_count'])?round($item['order_num']/$sales_order_data[$key]['click_count'],4)*100 :0;
		
        $sales_order_data[$key]['click_count'] = $item['click_count'];
    }
	unset($catArr);
	
	$sql = "select SUM(og.goods_number) as goods_num,SUM(og.goods_number*og.goods_price) as goods_price  ".
           "FROM ".ODRGOODS." AS og, ".GOODS." AS g, ".
           ORDERINFO." AS oi  " .$where ;
	$tj = $GLOBALS['db']->selectinfo($sql);
	//print_r($tj);
/*	$sql = "select  oi.order_amount  ".
           "FROM ".ODRGOODS." AS og, ".GOODS." AS g, ".
           ORDERINFO." AS oi  " .$where .'  group by oi.order_id ';
		  // echo $sql;
	$tjArr = $GLOBALS['db']->arrQuery($sql);
	$tj['turnover'] = 0;
	foreach($tjArr as $val){
		$tj['turnover'] = $tj['turnover'] + $val['order_amount'];
	}
	$tj['turnover'] = round($tj['turnover'],2);
*/	
    $arr = array('sales_order_data' => $sales_order_data, 'filter' => $filter,'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count'],'tj'=>$tj);
    return $arr;
}
$_ACT = 'shujutongji';
temp_disp();//

?>