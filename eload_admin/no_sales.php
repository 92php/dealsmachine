<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/lib_order.php');
require_once(ROOT_PATH . 'lib/class.page.php');

$_REQUEST['start_date'] = empty($_REQUEST['start_date'])?local_strtotime('-6 months'):local_strtotime($_REQUEST['start_date']);
$_REQUEST['end_date'] = empty($_REQUEST['end_date'])?gmtime():local_strtotime($_REQUEST['end_date']);
$_REQUEST['pro_adddate'] = empty($_REQUEST['pro_adddate'])?0:intval($_REQUEST['pro_adddate']);


$_REQUEST['shuxing1']    = empty($_REQUEST['shuxing1'])?'':$_REQUEST['shuxing1'];
$_REQUEST['shuxing2']    = empty($_REQUEST['shuxing2'])?'':$_REQUEST['shuxing2'];

//echo date('Y-m-d',$_REQUEST['end_date']);

$_REQUEST['sort_by']    = empty($_REQUEST['sort_by'])?'':$_REQUEST['sort_by'];
$_REQUEST['sort_order'] = empty($_REQUEST['sort_order'])?'':$_REQUEST['sort_order'];
$_REQUEST['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);


/* 权限检查 */
admin_priv('no_sales');
$cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
$fangshi = empty($_REQUEST['fangshi']) ? 1 : intval($_REQUEST['fangshi']);
$Arr['cat_list'] = cat_list($cat_id);
$Arr['fangshi'] = $fangshi;
$Arr['shuxing1'] = $_REQUEST['shuxing1'];
$Arr['shuxing2'] = $_REQUEST['shuxing2'];


$goods_order_data = get_sales_order();

$Arr['goods_order_data'] = $goods_order_data['sales_order_data'];

$sort_flag           = sort_flag($goods_order_data['filter']);

$Arr[$sort_flag['tag']] = $sort_flag['img'];

$goods_order_data['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];

$Arr['filter'] = $goods_order_data['filter'];
$sql = 'select add_user from '.GOODS.' where add_user <> "" group by add_user ORDER BY binary add_user asc ';
$Arr['users'] = $db->arrQuery($sql);

$page=new page(array('total'=>$goods_order_data['record_count'],'perpage'=>$goods_order_data['page_size']));

$Arr["pagestr"]  = $page->show();

$Arr['goods_sn'] =        $_REQUEST['goods_sn'];
$Arr['start_date']  =       local_date('Y-m-d', $_REQUEST['start_date']);
$Arr['end_date']    =       local_date('Y-m-d', $_REQUEST['end_date']);
$Arr['pro_adddate'] =       $_REQUEST['pro_adddate'];
$Arr["search_url"] = '&start_date='.$Arr['start_date'].'&pro_adddate='.$Arr['pro_adddate'].'&end_date='.$Arr['end_date'].'&cat_id='.$cat_id.'&shuxing1='.$_REQUEST['shuxing1'].'&shuxing2='.$_REQUEST['shuxing2'];
//$search_url2 = '?act=download&sort_by='.$_REQUEST['sort_by'].'&sort_order='.$_REQUEST['sort_order'].$Arr["search_url"];
	
   // $Arr['action_link'] =      array('text' => '销售排行报表下载', 'href' => $search_url2 );

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
	
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    $filter['cat_id']     = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
	$filter['add_user']   = empty($_REQUEST['add_user'])?'':$_REQUEST['add_user'];
	$filter['shuxing1']    = empty($_REQUEST['shuxing1'])?'':$_REQUEST['shuxing1'];
	$filter['shuxing2']    = empty($_REQUEST['shuxing2'])?'':$_REQUEST['shuxing2'];
	$filter['pro_adddate']    = empty($_REQUEST['pro_adddate'])?0:intval($_REQUEST['pro_adddate']);

    $ziwhere = '';
	
    if ($filter['start_date'])
    {		
        $ziwhere .= " AND oi.add_time >= '" . $filter['start_date'] . "'";
    }
    if ($filter['end_date'])
    {
        $ziwhere .= " AND oi.add_time <= '" . $filter['end_date'] . "'";
    }
	
$isping = '';
if($_SESSION["WebUserInfo"]["sa_user"] == 'haoren')
     $isping = ' and oi.is_ping = 0';

	 $where = " WHERE NOT EXISTS  (select og.goods_id from ".ORDERINFO . " AS oi, ".ODRGOODS . " AS og  where og.order_id = oi.order_id  $ziwhere  and oi.order_status > 0 and oi.order_status < 9 and g.goods_id = og.goods_id  $isping ) ";
 //
	
    if ($filter['pro_adddate'])
    {
        $where .= " AND g.add_time <= '" . (gmtime() - (86400 * $filter['pro_adddate'])) . "'";
    }
	
	if ($filter['shuxing1']){
		$where .=  ' AND g.'.$filter['shuxing1'] .' = 1 ';
	}
	if ($filter['shuxing2']){
		$where .=  ' AND g.'.$filter['shuxing2'] .' = 1 ';
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
	
	if ($_REQUEST['goods_sn'])
	{
        $where .= " AND g.goods_sn = '" .$_REQUEST['goods_sn'] . "'";
	}
	


    $sql = "SELECT COUNT(g.goods_id) FROM " .
           GOODS . ' AS g '.
           $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);


    

   /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "SELECT g.goods_id, g.goods_sn, g.goods_name, g.goods_thumb, g.cat_id,g.add_time,g.add_user,g.click_count,g.is_on_sale,g.goods_number " .
           " FROM ".GOODS." AS g " .
           $where .         
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
        //$sales_order_data[$key]['wvera_price'] = price_format($item['goods_num'] ? $item['turnover'] / $item['goods_num'] : 0);
        $sales_order_data[$key]['short_name']  = sub_str($item['goods_name'], 30, true);
        $sales_order_data[$key]['add_time']    =  local_date($GLOBALS['_CFG']['time_format'], $item['add_time']);
       // $sales_order_data[$key]['turnover']    = price_format($item['turnover']);
        $sales_order_data[$key]['cat_id']      = empty($catArr[$item['cat_id']]['cat_name'])?'':$catArr[$item['cat_id']]['cat_name'];
        $sales_order_data[$key]['taxis']       = $key + 1;
        $sales_order_data[$key]['is_on_sale']  = $sales_order_data[$key]['is_on_sale']?'<span style="color:#ff0000">已上架</span>':'已下架';
    }
	unset($catArr);
	
	

	
    $arr = array('sales_order_data' => $sales_order_data, 'filter' => $filter,'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
    return $arr;
}
$_ACT = 'no_sales';
temp_disp();

?>