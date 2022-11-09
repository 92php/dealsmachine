<?php

/*******detals数据统计********/

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/lib_order.php');
require_once(ROOT_PATH . 'lib/class.page.php');

$_REQUEST['keyword'] = empty($_REQUEST['keyword'])?'':$_REQUEST['keyword'];
$page                = empty($_GET['page'])?1:intval($_GET['page']);
$now_time = gmtime();

/* 权限检查 */
admin_priv('groupdeals');

$sql = "select count(*) from  eload_deals as b  left join eload_goods as e on  b.goods_sn=e.goods_sn";	
$filter['record_count'] = $GLOBALS['db']->getOne($sql);

$filter['page_size']=10;
$sql="select e.goods_id,e.goods_thumb,e.goods_title,e.goods_sn,e.sort_order,b.deals_id,b.ups,e.shop_price,b.expried_time from  eload_deals as b  left join eload_goods as e on  b.goods_sn=e.goods_sn";

$goodsArr = $db->arrQuery($sql);	
foreach($goodsArr as &$row){		
	$row['goods_thumb'] = get_image_path($row['goods_id'],$row['goods_thumb']);	
	$row['expried_time'] =local_date($GLOBALS['_CFG']['time_format'], $row['expried_time']);
}

$Arr['tongji_data'] = $goodsArr;
$Arr['keyword']  = $_REQUEST['keyword'];
$page=new page(array('total'=>$filter['record_count'],'perpage'=>$filter['page_size']));
$Arr["pagestr"]  = $page->show();

$_ACT = 'groupdeals';
temp_disp();//


?>