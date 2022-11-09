<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');  
require(ROOT_PATH . 'lib/lib.f.order.php');
//查地址信息 随机取地址
$sql = "SELECT * FROM " . ADDR . " order by rand() limit 300";
$addArr = $db->arrQuery($sql);



//print_r($arr);

//查询三月份的倒入用户数据 随机取用户
$yrdate = '2011-03';
$sql = " select user_id,FROM_UNIXTIME(reg_time,'%Y-%m') as reg_time,last_login from ".USERS ." where FROM_UNIXTIME(reg_time,'%Y-%m') = '".$yrdate."' and is_dao = 1  order by rand() limit 300 ";
$userArr = $db->arrQuery($sql);


$order_date = '2011-07';

//查导入产品
$sql = "SELECT * FROM `eload_order_goods_temp` ";
$goodsArr = $db->arrQuery($sql);
foreach($goodsArr as $key => $val){
	$consignee = $addArr[$key];
	$user      = $userArr[$key];
	
 	$order['add_time']        = local_strtotime($order_date.'-'.rand(1,5)) +  rand(1,20)*3600 + rand(1,59)*rand(1,59) + rand(1,59) ;
	$user['last_login']       = $order['add_time'] + rand(1,22)*3600*30;
	
	$db->query("update ".USERS ." set last_login = (last_login + 24*3600*30) where user_id = '".$user['user_id']."'");
	
	if ($order['add_time']>$user['last_login']){
		////echo 'shijia dayu le '.local_date($GLOBALS['_CFG']['time_format'], $user['last_login']).'<br>';
		$order['add_time']    = $user['reg_time'] - (($user['last_login'] - $user['reg_time'])/rand(1,4) + rand(1,3600));
	}
	
	
	$order['user_id']         = $user['user_id'];
	$order['order_status'] = 3;
	
    $order['consignee']  =  addslashes($consignee['firstname'] .' '. $consignee['lastname']);
    $order['address']    =  addslashes($consignee['addressline1']) .' '. addslashes($consignee['addressline2']);

	$order['country']      = $consignee['country'];
	$order['province']     = addslashes($consignee['province']);
	$order['city']         = addslashes($consignee['city']);
	$order['zipcode']      = addslashes($consignee['zipcode']);
	$order['tel']          = addslashes($consignee['tel']);
	$order['email']        = addslashes($consignee['email']);
	
	$order['shipping_id']     = 1;
	$shipping = shipping_info($order['shipping_id']);
	$order['shipping_name'] = addslashes($shipping['ship_name']);
	
	$order['pay_id']       = 'PayPal';
	$order['pay_name']     = 'PayPal';    
	$order['is_dao']       = '1';    
	//$order['is_ping']      = '1';    
 	$order['Invoice']      = rand(0,1); //需要发票
	$order['pay_time']     = $order['add_time'] + rand(90,1200) ;
	
	
	//保险费用
	$insure_fee   =  rand(0,1);
	
    /* 订单中的总额 */
	$goods[0] = $val;
	
	
    $total = order_fee($order, $goods,$insure_fee);
    $order['goods_amount'] = $total['goods_price'];
    $order['shipping_fee'] = $total['shipping_fee'];
    $order['free_shipping_fee'] = $total['free_shipping_fee'];
	
	
    $order['order_amount']   = number_format($total['amount'], 2, '.', '');
	$order['insure_fee']     = ($insure_fee > 0 )?$total['insure_fee']:0;
	$order['postscript']     = ' ';
	$order['tax']            = $total['tax'];
	echo  local_date($GLOBALS['_CFG']['time_format'],$order['add_time']).' <br>';	
	echo  get_zhiding_order_sn($order['add_time']).' <br>';
    //echo $order['shipping_name'];
    echo '付款时间： '.local_date($GLOBALS['_CFG']['time_format'], $order['pay_time']).'<br><br><br>';	
//	echo  local_date($GLOBALS['_CFG']['time_format'], $user['reg_time']).' '.local_date($GLOBALS['_CFG']['time_format'], $user['last_login']).'<br><br><br>';
	
    /* 插入订单表 */
    $error_no = 0;
    do
    {
        $order['order_sn'] = get_zhiding_order_sn($order['add_time']); //获取新订单号
		echo $order['order_sn'].'<br>';
        $db->autoExecute(ORDERINFO, $order, 'INSERT');
        $error_no = $db->Errno;
        if ($error_no > 0 && $error_no != 1062)
        {
            die('DB insert error');
        }
    }
    while ($error_no == 1062); //如果是订单号重复则重新提交数据
    $new_order_id = $db->insertId();

    //新增产品
    $sql = "INSERT INTO " . ODRGOODS . "( " .
                "order_id, goods_id, goods_name, goods_sn, goods_number, market_price, ".
                "goods_price, goods_attr, goods_off,lmt_num,addtime,custom_size,attr_goods_sn,is_dao) ".
            " SELECT '$new_order_id', goods_id, goods_name, goods_sn, goods_number, market_price, ".
                "goods_price, goods_attr, goods_off,lmt_num,addtime,custom_size,attr_goods_sn,1 ".
            " FROM  eload_order_goods_temp "  .
            " WHERE rec_id  = '".$val['rec_id']."'";
    $db->query($sql);
	
	
	
	
	//exit;
	
	
	
}


/**
 * 得到新订单号
 * @return  string
 */
function get_zhiding_order_sn($time)
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    return 'E'.local_date('ymdHis',$time) . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}



$sql = 'order_sn,user_id,order_status,consignee,country,province,city,address,zipcode,tel,email,shipping_id,shipping_name,shipping_method, pay_id,pay_name,goods_amount,shipping_fee,free_shipping_fee,insure_fee,pay_fee,order_amount,add_time,pay_time,tax,Invoice';




exit;


echo '重复 '.$cn.'  成功'.$ln.'<br>';
$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."&n=".$n."'>";
?>

