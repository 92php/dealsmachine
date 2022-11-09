<?php
/**
 * 批量设置商品促销脚本
 * */

set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once('../lib/inc.html.php');
require_once('../lib/syn_public_fun.php');

$cat_array = array(
			0 => array(
					'cat_id' =>63,
					'price_fangwei' => 50
					),
			1 => array(
					'cat_id' => 107,
					'price_fangwei' => 20
					)
			);

$promote_start_date = 1332889200;		//2012-03-28
$promote_end_date = 1335567600;			//2012-04-28
$change_date = array();

$where = array();
foreach ($cat_array as $key=>$cat)
{
	$children = get_children($cat['cat_id']);
	$where[] = '(chuhuo_price <= ' . $cat['price_fangwei'] . ' AND (' . $children . 'OR g.cat_id=' . $cat['cat_id'] . '))';
}

$sql = "SELECT goods_id , goods_sn , cat_id , goods_weight , shop_price , chuhuo_price , promote_price , promote_start_date , promote_end_date , is_free_shipping " .
		" FROM " . GOODS . " AS g " .
		" WHERE " . implode(" OR " ,$where);
$rss = $GLOBALS['db']->arrQuery($sql);

foreach($rss as $k => $row){
	$fenleiArr      = get_zhuijia_price_and_fenlei_bili($row['cat_id'], $row['chuhuo_price']);  //根据出货价取出相应的比例，追加价格，个数分级
	$zhuijia_price  = round(($fenleiArr['zhuijia_price'] / HUILV),2); //追加价格
	$shop_price     = round(($row['chuhuo_price']/HUILV),2);//转成美元
	$shipping_fee   = get_shipping_fee($shop_price, $row['goods_weight']);		//运费
	$shop_price     = round($shop_price * 1.15, 2);	//商品价格
	if(empty($row['is_free_shipping']))
	{
		$cuxiao_price = $shop_price + $zhuijia_price;
	}
	else
	{
		$cuxiao_price = $shop_price + $zhuijia_price + $shipping_fee;
	}

	if($cuxiao_price < $row['shop_price'])
	{
		$update_cuxiao = array(
						'promote_price' => $cuxiao_price,
						'promote_start_date' => $promote_start_date,
						'promote_end_date' => $promote_end_date,
						'is_promote' => 1,
						'market_price' => $row['shop_price']
						);
		$GLOBALS['db']->autoExecute(GOODS, $update_cuxiao, 'UPDATE', 'goods_id='.$row['goods_id']);
		$change_date[] = $row;
	}
}

Logger::put('cuxiao_price', var_export($change_date,true));