<?php
/**
 * 定期设置一元店的产品
 * URL:http://www.dealsmachine.com/special_offer.html
 */
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');

$now=gmtime();
$sql = "select goods_id,promote_start_date,promote_end_date,shop_price,market_price from ".GOODS."  where shop_price between 1 and 1.2 and not ( promote_start_date< $now and promote_end_date >$now) and is_on_sale=1 and goods_number>0 and promote_price<>0.99  order by rand() limit 40 ";
$arr  = $db->arrQuery($sql);
$db->query('update '.GOODS.' set promote_price=0,is_promote =0,promote_start_date=0,promote_end_date=0,market_price=0  where promote_price=0.99');
foreach ($arr as $v){
	$g = array();
	$g['is_promote'] = 1;
	$g['promote_price'] = 0.99;
	$g['market_price'] = round($v['shop_price']*(100+rand(15, 50))/100,2);
	$g['promote_start_date'] = $now;
	$g['promote_end_date'] = $now+3600*8*24;
	$db->autoExecute(GOODS,$g,'UPDATE','goods_id='.$v['goods_id']);
}

echo count($goodsArr).'一元店产品更成功';	

?>
