<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');

$stime = date('Y-m-d', gmstr2time('-14 day'));
$etime = date('Y-m-d', gmtime());
//不参与排序的产品
$fliterArr = array();//'CON0039','MBE9193','CON0635','CON0634','CON0633'


$sql = "SELECT g.goods_id ,g.goods_sn ,g.cat_id,if(g.is_delete = 1 or g.is_on_sale = 0,0,g.goods_number) as goods_number FROM eload_goods AS g WHERE g.is_alone_sale = 1 and g.goods_thumb <>'' AND (( 1 and g.is_login = 0 AND DATEDIFF(curdate(),FROM_UNIXTIME(add_time,'%Y-%m-%d')) <= 15 ) ) ORDER BY if( goods_number =0, 0, 1 ) DESC, is_on_sale DESC,is_delete ASC, goods_id desc,click_count desc limit 232 ";

$goodsArr = $db->arrQuery($sql);
echo '<pre>';
$order = 1;
$carodr = array();
//print_r($goodsArr);
foreach($goodsArr as $key => &$val){
	if (!in_array(strtoupper($val['goods_sn']),$fliterArr)){
		
		//print_r(array_keys($carodr));
		//exit();
		//echo $val['cat_id'];
		
		if (in_array($val['cat_id'],array_keys($carodr))){
		    $val['newordervalue'] = $carodr[$val['cat_id']];
		    
		}else{
			//echo "1<<<";
		    $val['newordervalue'] = 1;
			$order = 1;
		}
		$UpdateSql = "update ".GOODS."  SET newordervalue = '".$val['newordervalue']."'  where goods_id = '".$val['goods_id']."'";
		$db->query($UpdateSql);
		
		$order++;
		$carodr[$val['cat_id']] = $order;
		
	}
}


//print_r($goodsArr);
echo 'New Arrival 排序更新成功';		

?>

