<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');


$goods_sn = empty($_POST['goods_sn'])?'':$_POST['goods_sn'];
$goods_name = empty($_POST['goods_name'])?'':$_POST['goods_name'];
$goods_title = empty($_POST['goods_title'])?'':$_POST['goods_title'];
$market_price = empty($_POST['market_price'])?0:floatval($_POST['market_price']);
$shop_price = empty($_POST['shop_price'])?0:floatval($_POST['shop_price']);

$add_user = empty($_POST['add_user'])?'':$_POST['add_user'];
$add_time = empty($_POST['add_time'])?0:intval($_POST['add_time']);


if ($goods_sn!=''){
	
	$goods_id = $db->getOne("select goods_id from ".GOODS." where goods_sn = '$goods_sn'");
	
	if ($goods_id>0){
/*	$sql = " update ".GOODS." set add_user = '$add_user',add_time = '$add_time',goods_name = '$goods_name',goods_title = '$goods_title',market_price = '$market_price',shop_price = '$shop_price'  where goods_sn = '".$goods_sn ."' ";
	$db->query($sql);
	
    if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
    {
        $temp_num = array_count_values($_POST['volume_number']);
        foreach($temp_num as $v)
        {
            if ($v > 1)
            {
                sys_msg("优惠数量重复！", 1, array(), false);
                break;
            }
        }
        handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
    }
*/	    echo 'ok';
	}else{
	    echo '不存在商品';
	}
	
}







/**
 * 保存某商品的优惠价格
 * @param   int     $goods_id    商品编号
 * @param   array   $number_list 优惠数量列表
 * @param   array   $price_list  价格列表
 * @return  void
 */
function handle_volume_price($goods_id, $number_list, $price_list)
{
    $sql = "DELETE FROM " . VPRICE .
           " WHERE price_type = '1' AND goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);


    /* 循环处理每个优惠价格 */
    foreach ($price_list AS $key => $price)
    {
        /* 价格对应的数量上下限 */
        $volume_number = $number_list[$key];

        if (!empty($price))
        {
            $sql = "INSERT INTO " . VPRICE .
                   " (price_type, goods_id, volume_number, volume_price) " .
                   "VALUES ('1', '$goods_id', '$volume_number', '$price')";
            $GLOBALS['db']->query($sql);
        }
    }
}




?>

