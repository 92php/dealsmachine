<?php
if (!defined('INI_WEB')){die('访问拒绝');}
/**
 * 购物流程函数库
 */


/**
 * 取得配送方式信息
 * @param   int     $shipping_id    配送方式id
 * @return  array   配送方式信息
 */
function shipping_info($shipping_id)
{
	$ShipArr =  read_static_cache('shipping_method', ADMIN_STATIC_CACHE_PATH);
    return $ShipArr[$shipping_id];
}

/**
 * 取得可用的配送方式列表
 * @param   array   $region_id_list     收货人地区id数组（包括国家、省、市、区）
 * @return  array   配送方式数组
 */
function available_shipping_list()
{
    $shipping_Arr = read_static_cache('shipping_method', ADMIN_STATIC_CACHE_PATH);
    return $shipping_Arr;
}

/**
 * 获取最佳配送方式
 *
 *
 * @param  array $shipping_arr 所有商品配送方式
 */
function get_best_shipping($shipping_arr = array()) {
    $shipping_list = available_shipping_list();
    //$return_arr    = array();
	if(!empty($shipping_arr[0]))
    foreach ($shipping_arr as $value) {
    	foreach ($shipping_list as $k=>$v)
    	{
        	if(!in_array($v['id'], $value))unset($shipping_list[$k]);
    	}
    }
    return $shipping_list;
}

/**
 * 取得某配送方式对应于某收货地址的区域信息
 * @param   int     $shipping_id        配送方式id
 * @param   array   $region_id_list     收货人地区id数组
 * @return  array   配送区域信息（config 对应着反序列化的 configure）
 */
function shipping_area_info($shipping_id)
{
}

/**
 * 计算运费
 *
 * @param   string  $qizhong_price      起重价格
 * @param   float   $xuzhong_price      续重价格
 * @param   float   $shipping_weight    重量
 * @return  float   运费
 */
function count_shipping_fee($qizhong_price, $xuzhong_price, $shipping_weight) {
	$qizhong_duan = 0.5;  //起重段，单位千克
	$xuzhong_duan = 0.5;  //续重段，单位千克
	$xian_yunfei  = $qizhong_price;    //现运费
	!$shipping_weight && ($shipping_weight = 0.5);
    !$qizhong_price && ($qizhong_price = 50);

	if ($shipping_weight - $qizhong_duan > 0){    //重量超出起重段
		$xuzhong_temp = ceil(($shipping_weight - $xuzhong_duan) / $xuzhong_duan); //取整数
        $xian_yunfei = $qizhong_price + ($xuzhong_temp * $xuzhong_price);
    }
    return price_format($xian_yunfei, false);
}

/**
 * 获取指定配送的保价费用
 *
 * @access  public
 * @param   string      $shipping_code  配送方式的code
 * @param   float       $goods_amount   保价金额
 * @param   mix         $insure         保价比例
 * @return  float
 */
function shipping_insure_fee($shipping_code, $goods_amount, $insure)
{
    if (strpos($insure, '%') === false)
    {
        /* 如果保价费用不是百分比则直接返回该数值 */
        return floatval($insure);
    }
    else
    {
        $path = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';

        if (file_exists($path))
        {
            include_once($path);

            $shipping = new $shipping_code;
            $insure   = floatval($insure) / 100;

            if (method_exists($shipping, 'calculate_insure'))
            {
                return $shipping->calculate_insure($goods_amount, $insure);
            }
            else
            {
                return ceil($goods_amount * $insure);
            }
        }
        else
        {
            return false;
        }
    }
}

/**
 * 取得已安装的支付方式列表
 * @return  array   已安装的配送方式列表
 */
function payment_list()
{
    $sql = 'SELECT pay_id, pay_name ' .
            'FROM ' . $GLOBALS['ecs']->table('payment') .
            ' WHERE enabled = 1';

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得支付方式信息
 * @param   int     $pay_id     支付方式id
 * @return  array   支付方式信息
 */
function payment_info($pay_id)
{
   // $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment') .
      //      " WHERE pay_id = '$pay_id' AND enabled = 1";

    //return $GLOBALS['db']->selectinfo($sql);
	$payArr = read_static_cache('payment', ADMIN_STATIC_CACHE_PATH);

	return $payArr[$pay_id];

}

/**
 * 获得订单需要支付的支付费用
 *
 * @access  public
 * @param   integer $payment_id
 * @param   float   $order_amount
 * @param   mix     $cod_fee
 * @return  float
 */
function pay_fee($payment_id, $order_amount, $cod_fee=null)
{
    $pay_fee = 0;
    $payment = payment_info($payment_id);
/*    $rate    = ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];

    if (strpos($rate, '%') !== false)
    {
         支付费用是一个比例
        $val     = floatval($rate) / 100;
        $pay_fee = $val > 0 ? $order_amount * $val /(1- $val) : 0;
    }
    else
    {
        $pay_fee = floatval($rate);
    }
*/
    //return round($pay_fee, 2);

}

/**
 * 取得可用的支付方式列表
 */
function available_payment_list()
{
	$list = read_static_cache('payment', ADMIN_STATIC_CACHE_PATH);
    return $list;
}


/**
 * 取得订单信息
 * @param   int     $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）
 * @param   string  $order_sn   订单号
 * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
 */
function order_info($order_id, $order_sn = '') {
    /* 计算订单各种费用之和的语句 */
    $total_fee = ' (goods_amount  + tax + shipping_fee + insure_fee + pay_fee) AS total_fee ';
    $order_id = intval($order_id);
    if ($order_id > 0)
    {
        $sql = "SELECT *, " . $total_fee . " FROM " . ORDERINFO .
                " WHERE order_id = '$order_id'";
    }
    else
    {
        $sql = "SELECT *, " . $total_fee . "  FROM " . ORDERINFO .
                " WHERE order_sn = '$order_sn'";
    }
    $order = $GLOBALS['db']->selectinfo($sql);

    /* 格式化金额字段 */
    if ($order)
    {
        //$order['formated_goods_amount']   = price_format($order['goods_amount'], false);
		$order['formated_goods_amount']   = price_format($order['goods_amount']*($order['order_rate']>0?$order['order_rate']:1), false);
        $order['formated_tax']            = price_format($order['tax']*($order['order_rate']>0?$order['order_rate']:1), false);
        $order['formated_shipping_fee']   = price_format($order['shipping_fee']*($order['order_rate']>0?$order['order_rate']:1), false);
        $order['formated_insure_fee']     = price_format($order['insure_fee']*($order['order_rate']>0?$order['order_rate']:1), false);
        $order['formated_pay_fee']        = price_format($order['pay_fee']*($order['order_rate']>0?$order['order_rate']:1), false);
        $order['formated_total_fee']      = price_format($order['total_fee']*($order['order_rate']>0?$order['order_rate']:1), false);
        $order['formated_order_amount']   = price_format(abs($order['order_amount']*($order['order_rate']>0?$order['order_rate']:1)), false);
        $order['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
        $order['formated_pay_time']       = local_date($GLOBALS['_CFG']['time_format'], $order['pay_time']);
    }

    return $order;
}

/**
 * 判断订单是否已完成
 * @param   array   $order  订单信息
 * @return  bool
 */
function order_finished($order)
{
    return $order['order_status']  == OS_CONFIRMED &&
        ($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED) &&
        ($order['pay_status']      == PS_PAYED   || $order['pay_status'] == PS_PAYING);
}

/**
 * 取得订单商品
 * @param   int     $order_id   订单id
 * @return  array   订单商品数组
 */
function order_goods($order_id)
{
	$goods_list = array();
    $sql = "SELECT o.rec_id, o.goods_id, o.goods_name, o.goods_sn, o.market_price, o.goods_number, " .
            "o.goods_price, o.goods_attr,o.goods_off,o.custom_size, " .
            "o.goods_price * o.goods_number AS subtotal, g.goods_title,g.cat_id " .
            "FROM " . ODRGOODS . " as o ".
			" left join ".GOODS." as g  on o.goods_id =g.goods_id".
            " WHERE order_id = '$order_id'";
	if($order_id<566323)$sql.=" group by o.goods_id";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
		$row['custom_size'] = json_decode($row['custom_size'], true);
		$row['goods_attr'] = stripslashes($row['goods_attr']);
		$row['url_title'] = get_details_link($row['goods_id'],'');
        $goods_list[] = $row;
    }
    //return $GLOBALS['db']->getAll($sql);
    return $goods_list;
}

/**
 * 取得订单总金额
 * @param   int     $order_id   订单id
 * @param   bool    $include_gift   是否包括赠品
 * @return  float   订单总金额
 */
function order_amount($order_id, $include_gift = true)
{
    $sql = "SELECT SUM(goods_price * goods_number) " .
            "FROM " . ODRGOODS .
            " WHERE order_id = '$order_id'";
    if (!$include_gift)
    {
        $sql .= " AND is_gift = 0";
    }

    return floatval($GLOBALS['db']->getOne($sql));
}

/**
 * 取得某订单商品总重量和总金额（对应 cart_weight_price）
 * @param   int     $order_id   订单id
 * @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
 */
function order_weight_price($order_id)
{
    $sql = "SELECT SUM(g.goods_weight * o.goods_number) AS weight, " .
                "SUM(o.goods_price * o.goods_number) AS amount ," .
                "SUM(o.goods_number) AS number " .
            "FROM " . ODRGOODS . " AS o, " .
                GOODS . " AS g " .
            "WHERE o.order_id = '$order_id' " .
            "AND o.goods_id = g.goods_id";

    $row = $GLOBALS['db']->selectinfo($sql);
    $row['weight'] = floatval($row['weight']);
    $row['amount'] = floatval($row['amount']);
    $row['number'] = intval($row['number']);

    /* 格式化重量 */
    $row['formated_weight'] = formated_weight($row['weight']);

    return $row;
}

/**
 * 获得订单中的费用信息
 *
 * @access  public
 * @param   array   $order
 * @param   array   $goods
 * @param   array   $consignee
 * @param   bool    $is_gb_deposit  是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
 * @return  array
 */
function order_fee($order, $goods, $is_add_baoxian = true, $point_money = 0) {
    require_once(ROOT_PATH.'lib/syn_public_fun.php');
    $total = array(
            'goods_price'      => 0,
			'yuan_goods_amount'=> 0,
            'real_goods_count' => 0,
            'market_price'     => 0,
            'shipping_fee'     => 0,
            'free_shipping_fee'=> 0,
            'pay_fee'          => 0,
            'free_total_fee'   => 0,
            'tax'              => 0);
    $weight                    = 0;    //重量
    $free_total_fee            = 0;    //免运费金额

    $pcode_lv = 0;
	$pcode                     = empty($_SESSION['pcode_code'])?'':$_SESSION['pcode_code'];  //优惠码
	$pcode_about               = empty($_SESSION['pcode_lv'])?'':$_SESSION['pcode_lv'];          //优惠率    8%
	$pcode_fangshi             = empty($_SESSION['pcode_fangshi'])?'':$_SESSION['pcode_fangshi'];   //方式，百分比，直减去  1，2
	$pcode_goods               = empty($_SESSION['pcode_goods'])?'':$_SESSION['pcode_goods'];       //针对产品的优惠，‘CEL0001’，‘CEL0021’
	$code_cat_id               = empty($_SESSION['code_cat_id'])?0:$_SESSION['code_cat_id'];
	if($code_cat_id)//获得当前商品的包括当前分类ID和所有下级分类ID
	{
		$code_cat_array = explode(',',$code_cat_id);
		$code_cat_array = array_filter($code_cat_array);
		$childs_array = array();
		if(!empty($code_cat_array)){
			$typeArray =  read_static_cache('category_c_key',2);
			foreach($code_cat_array as $row){
				$childs_array = array_merge($childs_array,getChilds($typeArray,$row));
			}
			if(is_array($childs_array)){
				foreach($childs_array as $val){
					$code_cat_array[] = $val['cat_id'];

				}

			}
			unset($childs_array);
			$childs_array = $code_cat_array;
		}
	}
    /* 商品总价 */
    foreach ($goods AS $val)
    {
		$total['real_goods_count'] ++;
        $total['market_price'] += $val['market_price'] * $val['goods_number'];
        $val['subtotal_yuan']     = price_format($val['goods_price'] * $val['goods_number'], false);
		//指定分类下的产品参加促销
        $flag=0;
        if($code_cat_id)
        {
            $goods_cat_id = $GLOBALS['db']->getOne("select cat_id from ".GOODS." where goods_sn='".$val['goods_sn']."'");
            if(in_array($goods_cat_id, $childs_array))
            {
                $flag=1;
            }
            else
            {
                $flag=0;
            }
        }
        if ($pcode && strpos($pcode_goods,$val['goods_sn'])!==false || $flag){ //有促销码并且针对商品
			$spec = explode(',',$val['goods_attr_id']);
			$final_price = get_final_price($val['goods_id'], $val['goods_number'], true,$spec);
		    //先查是否是促销产品
			//$sql = "select is_promote from ".GOODS." where goods_id = '$val[goods_id]'";
			//$is_promote = $GLOBALS['db']->getOne($sql);
			//不是促销产品，就允许用优惠码
			//if(!$is_promote){
				$pcode_lv = 0;
				$pcode_lv = get_youhui($pcode_about,$final_price);  //取得相应的优惠
			//}
			if ($pcode_fangshi == '1') {//百分比计算
				$val['subtotal'] = price_format($val['subtotal_yuan']*(1 - $pcode_lv*0.01), false);
			}else{
				$val['subtotal'] = price_format(($val['goods_price'] - $pcode_lv) * $val['goods_number'], false);
			}

        }else{
			$val['subtotal']  = $val['subtotal_yuan'];
		}

		$weight += ($val['goods_number'] * $val['goods_weight']);
		if($val['is_free_shipping']) {
		    $free_total_fee += (get_shipping_fee($val['goods_price'],$val['goods_weight'])* $val['goods_number']) ;
		}

		$total['goods_price'] += $val['subtotal'];
		$total['yuan_goods_amount'] += $val['subtotal_yuan'];

    }
    $total['free_total_fee'] = $free_total_fee;


	//执行针对订单的优惠码
	if (empty($pcode_goods) && $pcode_about && empty($code_cat_id)){
		$pcode_lv = get_youhui($pcode_about,$total['goods_price']);  //取得相应的优惠
		$is_applay = $_SESSION['is_applay'];

        if(($is_applay && $total['goods_price'] > 50) || (!$is_applay && $pcode_goods == '')) {    //代金券 且金额大于50 或 优惠码针对所有产品

            if ($pcode_fangshi == '1') {//百分比计算
                $total['goods_price'] = price_format($total['goods_price'] * (1 - $pcode_lv * 0.01), false);
                $software_price = price_format($software_price * (1 - $pcode_lv * 0.01), false);
            }
            else {
                $total['goods_price'] = price_format($total['goods_price'] - $pcode_lv, false);
                $software_price = price_format($software_price - $pcode_lv, false);
            }
            $_SESSION['total_price'] = $total['goods_price'];
        }
        elseif($is_applay && $total['goods_price'] < 50) {

            if(!empty($_SESSION['pcode_lv'])) {
                unset($_SESSION['pcode_lv']);
            }

            if(!empty($_SESSION['pcode_code'])) {
                unset($_SESSION['pcode_code']);
            }

            if(!empty($_SESSION['pcode_fangshi'])) {
                unset($_SESSION['pcode_fangshi']);
            }

            if(!empty($_SESSION['pcode_goods'])) {
                unset($_SESSION['pcode_goods']);
            }

            if(!empty($_SESSION['is_applay'])) {
                unset($_SESSION['is_applay']);
            }
        }
	}

    $total['saving']    = price_format(($total['market_price'] - $total['goods_price']), false);
    $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

    $total['goods_price_formated']  = price_format($total['goods_price'], false);
    $total['yuan_goods_amount']     = price_format($total['yuan_goods_amount'], false);
    $total['market_price_formated'] = price_format($total['market_price'], false);
    $total['saving_formated']       = price_format($total['saving'], false);

    /* 配送费用 */
    $shipping_cod_fee = NULL;
    if ($order['shipping_id'] >0 && $total['real_goods_count'] > 0)
    {
		$weight_price_arr = cart_weight_price();
		$country          = $order['country'];

        if (41 == $country && in_array($_SESSION['flow_consignee']['province'], array('PR', 'Puerto Rico'))) {//美国国家且洲为Puerto Rico,波多黎各,按波多黎各计算
            $country = 163;
        }

		$shipping_fee_arr = read_static_cache('shipping_fee',2);
		$qizhong_price = 0;
		$xuzhong_price = 0;
		$free_weight   = empty($weight_price_arr[1]['weight'])? 0 : floatval($weight_price_arr[1]['weight']); //免邮费重量
		$goods_weight  = empty($weight_price_arr[0]['weight'])? 0 : floatval($weight_price_arr[0]['weight']); //不免邮费重量
		$free_shipping_volume_weight   = empty($weight_price_arr[1]['volume_weight'])? 0 : floatval($weight_price_arr[1]['volume_weight']); //免邮费体积重量
		$shipping_volume_weight  = empty($weight_price_arr[0]['volume_weight'])? 0 : floatval($weight_price_arr[0]['volume_weight']); //不免邮费体积重量
		//$free_weight = $free_weight + $goods_weight;
		//$goods_weight = 0;
        if($free_weight>=0 && $goods_weight==0)//只有免运费商品
        {
            switch ($order['shipping_id']){
                case '4'://中国邮政
                    $total['shipping_fee'] = get_chinapost_cost(0,$free_weight);
                break;
                case '3'://快递
                   $total['shipping_fee'] = $shipping_fee_arr[$country]['free_exp_fee'];
                break;
                case '2'://标准
                   $total['shipping_fee'] = $shipping_fee_arr[$country]['free_sta_fee'];
                break;
                case '1'://平邮
                   $total['shipping_fee'] = 0.00;
                break;
            }
        }
		elseif ($goods_weight > 0){ // 不免邮费重量,或者混合
            if(($shipping_volume_weight + $free_shipping_volume_weight) > ($goods_weight + $free_weight))
            {
                $order_shipping_weight = $shipping_volume_weight + $free_shipping_volume_weight;
            }
            else
            {
                $order_shipping_weight = $goods_weight + $free_weight;
            }            
            switch ($order['shipping_id']){
                case '3':
                   $qizhong_price = $shipping_fee_arr[$country]['exp_fee'];
                   $xuzhong_price = $shipping_fee_arr[$country]['exp_xu_fee'];
                   $total['shipping_fee'] = count_shipping_fee($qizhong_price,$xuzhong_price,$order_shipping_weight);
                break;
                case '2':
                   $qizhong_price = $shipping_fee_arr[$country]['org_tran_fee'];
                   $xuzhong_price = $shipping_fee_arr[$country]['org_tran_xu_fee'];
                   $total['shipping_fee'] = count_shipping_fee($qizhong_price,$xuzhong_price,$goods_weight);
                break;
                case '1':
                   $qizhong_price = $shipping_fee_arr[$country]['org_fee'];
                   $xuzhong_price = $shipping_fee_arr[$country]['org_xu_fee'];
                   $total['shipping_fee']=round(($goods_weight*$shipping_fee_arr[$country]['regular_mail'])/HUILV,2); //fangxin 2013-10-24
                break;
            }
            /*
            $total['shipping_fee'] = count_shipping_fee($qizhong_price,$xuzhong_price,$goods_weight);
            if($order['shipping_id']==1)
            {
               // if($goods_weight<0.5 && $total['goods_price']<40)
                //{
                    $total['shipping_fee']=round(($goods_weight*$shipping_fee_arr[$country]['regular_mail'])/HUILV,2); //fangxin 2013-10-24
                //}
            }
            elseif($order['shipping_id']==4)
            {
                $total['shipping_fee'] = get_chinapost_cost($goods_weight,0);
            }
            */
		}
		//$total['free_shipping_fee'] = ($order['shipping_id'] == 1)?0.00:(count_shipping_fee($qizhong_price,$xuzhong_price,$weight) - $free_total_fee);
        //$total['free_shipping_fee'] = count_shipping_fee($qizhong_price,$xuzhong_price,$weight) - $free_total_fee;


		//新加优惠运费  作者邬文龙
		//产品总金额大于100
		/*
		if($total['goods_price']>100){
			$youhuiduan  = ceil($total['shipping_fee']/5); //取整

			if($youhuiduan > 1){
				$total['shipping_fee'] = ($youhuiduan - 1) * 5 - 0.01;
			}else{
				$total['shipping_fee'] = 0;
			}

			$youhuiduan  = ceil($total['free_shipping_fee']/5);

			if($youhuiduan > 1){
				$total['free_shipping_fee'] = ($youhuiduan - 1) * 5 - 0.01;
			}else{
				$total['free_shipping_fee'] = 0;
			}
		}*/
		//优惠运费结束

    }

    $total['shipping_insure'] = 0;
    $total['shipping_insure'] = empty($total['shipping_insure'])?0:$total['shipping_insure'];
    $total['shipping_fee_formated']    = price_format($total['shipping_fee'], false);
    $total['shipping_insure_formated'] = price_format($total['shipping_insure'], false);
	$total['point_money']   =  $point_money;
    /* 计算订单总额 */

    //挂号费
    if($order['shipping_id'] == 1 &&!empty($_POST['Need_Traking_number'])){
    	//$total['Need_Traking_number'] = ($total['goods_price']>=40) ? 1.99 : get_pingyou_guahaofei($shipping_fee_arr[$country]);
		$total['Need_Traking_number'] = get_pingyou_guahaofei($country);
    }else {
    	$total['Need_Traking_number'] =0;
    }
	$total['insure_fee'] = $is_add_baoxian?(price_format(($total['goods_price']*0.02 + 1), false)):0; //投保  产品金额0.02//empty($order['insure_fee'])?0:$order['insure_fee'];
    $total['amount'] = $total['goods_price'] + $total['tax'] + $total['shipping_fee'] +  $total['insure_fee'] + $total['Need_Traking_number']- $total['point_money'];
    /* 保存订单信息 */
    $_SESSION['flow_order'] = $order;

    /* 支付费用 */
    if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 ))
    {
        $total['pay_fee']      = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
    }

    $total['pay_fee_formated'] = price_format($total['pay_fee'], false);

    $total['amount']           += $total['pay_fee']; // 订单总额累加上支付费用
    $total['amount_formated']  = price_format($total['amount'], false);
    $total['formated_goods_price']  = price_format($total['goods_price'], false);
    $total['formated_market_price'] = price_format($total['market_price'], false);
    $total['formated_saving']       = price_format($total['saving'], false);
    return $total;
}

/**
 * 修改订单
 * @param   int     $order_id   订单id
 * @param   array   $order      key => value
 * @return  bool
 */
function update_order($order_id, $order)
{
    return $GLOBALS['db']->autoExecute(ORDERINFO,
        $order, 'UPDATE', "order_id = '$order_id'");
}

/**
 * 得到新订单号
 * @return  string
 */
function get_order_sn($qian = '')
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    return $qian.'D'.date('ymdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 取得购物车商品
 *
 */
function cart_goods() {
    global $db;

	$show_marketprice = 0;
	$Is_Out_Sotck     = '';
    $Is_chaozhong     = false;
    $Is_group_alert   = false;
	$issong['enable'] = false;
	$total['shipping_weight']           = 0;	//重量合计（有格式）
	$total['free_shipping_weight']      = 0;	//免邮费重量合计（有格式）
	$total['free_shipping_volume_weight']  = 0; // 免邮费体积重量合计（有格式）
    $total['shipping_volume_weight']	= 0; // 体积重量合计（有格式）
	$cart_total['free_shipping_weight'] = 0;
	$cart_total['shipping_weight']      = 0;
    $_SESSION['email'] = empty($_SESSION['email']) ? SESS_ID : $_SESSION['email'];

	$sql      = 'SELECT SUM(goods_number) AS snum,COUNT(rec_id) AS cnum,max(rec_id) AS cid FROM ' . CART . " WHERE goods_price='0.01' AND session_id='{$_SESSION['email']}'";
	$spec_num = $GLOBALS['db']->selectinfo($sql);    //启动检查0.01的产品个数

	if ($spec_num['snum'] > 1){    //启动修改产品数量程序。

		if ($spec_num['cnum'] > 1){    //如果存在多条记录，则删除除最后一个其他0.01的产品
			$sql = 'DELETE FROM ' . CART . " WHERE rec_id <> '{$spec_num['cid']}' AND goods_price='0.01' AND session_id='{$_SESSION['email']}'";
			$GLOBALS['db']->query($sql);
		}

		$GLOBALS['db']->query('UPDATE ' . CART . " SET goods_number='1' WHERE rec_id='{$spec_num['cid']}'");    //将数量设置为1个
	}


	$sql     = 'SELECT g.goods_id,g.goods_title,c.rec_id,g.is_on_sale FROM ' . CART . ' AS c,' . GOODS . " AS g WHERE c.session_id='{$_SESSION['email'] }' AND c.goods_id=g.goods_id AND (g.goods_number=0 OR g.is_on_sale=0 or is_delete=1)";
	$delArr  = $GLOBALS['db']->arrQuery($sql);    //删除没有库存的或下架或放回收站的购物车记录
	$tempArr = array();

	if (!empty($delArr)){
		foreach($delArr as $key => $val){
			$tempArr[] = $val['goods_title'];
			$sql       = 'DELETE FROM ' . CART . " WHERE session_id='{$_SESSION['email']}' AND rec_id='{$val['rec_id']}'";
			$GLOBALS['db']->query($sql);

			if ($val['is_on_sale']){    //添加到收藏夹里
				$goods_id = $val['goods_id'];
				$count    = $GLOBALS['db']->getOne('SELECT goods_id FROM ' . COLLECT . " WHERE user_id='{$_SESSION['user_id']}' AND goods_id='{$goods_id}'");

				if (empty($count)) {
					$time = gmtime();
					$sql  = 'INSERT INTO ' . COLLECT . '(user_id, goods_id, add_time)' .
							" VALUES ('{$_SESSION['user_id']}', '{$goods_id}', '{$time}')";
					$GLOBALS['db']->query($sql);
				}

			}
		}
	}

	$Is_Out_Sotck  = implode(',', $tempArr);
    $pcode_lv      = 0;
	$pcode         = empty($_SESSION['pcode_code']) ? '' : $_SESSION['pcode_code'];  //优惠码
	$pcode_about   = empty($_SESSION['pcode_lv']) ? '' : $_SESSION['pcode_lv'];      //优惠率
	$pcode_fangshi = empty($_SESSION['pcode_fangshi']) ? '' : $_SESSION['pcode_fangshi'];   //方式
	$pcode_goods   = empty($_SESSION['pcode_goods']) ? '' : $_SESSION['pcode_goods'];       //针对产品的优惠
    $sql           = 'SELECT c.rec_id,c.gifts_id,c.main_goods_id,c.shipping_method,c.user_id,c.goods_id,c.goods_name,c.goods_sn,c.goods_number,' .
                    'c.market_price,if(c.main_goods_id=0,c.rec_id,(select rec_id from '.CART." c1 where c1.session_id='" . $_SESSION['email'] . '\' and c1.goods_id= c.main_goods_id limit 1)) as goods_order,c.goods_price,c.goods_attr,c.goods_attr_id,g.goods_thumb,g.goods_title,g.is_free_shipping,g.goods_weight,g.goods_volume_weight,g.cat_id,g.url_title,g.is_groupbuy,g.groupbuy_price,g.groupbuy_final_price,g.groupbuy_people_first_number,g.groupbuy_people_final_number,g.groupbuy_start_date,g.groupbuy_end_date,g. promote_start_date,g. promote_end_date,g.is_promote,' .
                    'c.goods_price * c.goods_number AS subtotal,c.goods_off,c.custom_size FROM ' .
                    CART . ' AS c LEFT JOIN ' . GOODS .
                    ' AS g ON c.goods_id=g.goods_id ' .
                    " WHERE c.session_id='{$_SESSION['email']}' ORDER BY goods_order desc,c.main_goods_id, c.rec_id DESC";
    $arr             = $GLOBALS['db']->arrQuery($sql);
    $gifts_in_cart = gifts_in_cart($arr);
    $Arr['is_include_gifts'] = $gifts_in_cart>0?1:0;
    if($gifts_in_cart>0){
		clearCoupon(); //有赠品则不能用优惠券
		$pcode = '';
		remove_over_gift($arr);
		$arr = $GLOBALS['db']->arrQuery($sql);
	}

    $shipping_method = array();
	$style[0]="style='background-color:#F5FDFE'";
	$style[1]="style='background-color:#FCFCF3'";
	$now_style_index = 0;
    foreach ($arr as $key => $value) {
    		$k = $key;
    		$row = $value;
    	    if((!empty($arr[$k+1])&&$arr[$k+1]['goods_order']==$row['goods_order']) || ($k&&!empty($arr[$k-1])&&$arr[$k-1]['goods_order']==$row['goods_order'])){
    			$goods_order = $row['goods_order'];
    			$arr[$key]['style'] = $style[$now_style_index];
    			if((!empty($arr[$k+1])&&$arr[$k+1]['goods_order']!=$row['goods_order']))$now_style_index = $now_style_index?0:1;
    		}
    		else {
    			$arr[$key]['style'] = '';
    		}

        $shipping_arr = explode(',', $value['shipping_method']);
		!in_array($shipping_arr, $shipping_method) && $shipping_method[] = $shipping_arr;    //配送方式

    	$pcode_lv = get_youhui($pcode_about,$arr[$key]['goods_price']);  //取得相应的优惠
		$arr[$key]['formated_goods_price']  = price_format($value['goods_price'], false);
		!empty($_SESSION['pcode_goods'])&&strpos($_SESSION['pcode_goods'], $value['goods_sn']) !== false?$arr[$key]['using_coupon']  = 1:0;
		 if ($pcode  && strpos($pcode_goods, $arr[$key]['goods_sn']) !== false)
		 {

	        if ($pcode_fangshi == '1') {//百分比计算

	        	$value['subtotal'] = price_format($value['subtotal'] * (1 - $pcode_lv * 0.01), false);
	      		//$value['goods_price'] = $value['goods_price']*(1 - $pcode_lv * 0.01);
	      		$arr[$key]['formated_goods_price']  = price_format($value['goods_price']*(1 - $pcode_lv * 0.01), false);
	        }
	        else{
	        	$arr[$key]['formated_goods_price'] = $value['goods_price']- $pcode_lv ;
	        	//$value['goods_price'] = $value['goods_price']*(1 - $pcode_lv * 0.01);
	        	$value['subtotal'] = price_format(($value['goods_price']-$pcode_lv) * $arr[$key]['goods_number'], false);
	        }
    	}


		($value['market_price'] > $value['goods_price']) && ($show_marketprice = 1);     //是否显示第一段价格与最优惠价格比较
        $arr[$key]['url_title']             = get_details_link($value['goods_id'],$value['url_title']);
        $arr[$key]['formated_market_price'] = price_format($value['market_price'], false);
        $arr[$key]['goods_price']  = price_format($value['goods_price'], false);

		$arr[$key]['formated_subtotal']     = price_format($value['subtotal'], false);
        $arr[$key]['goods_thumb']           = get_image_path($value['goods_id'], $value['goods_thumb'], true);
        $arr[$key]['groupbuy_start_date']            = $value['groupbuy_start_date'];
        $arr[$key]['groupbuy_end_date']            = $value['groupbuy_end_date'];
        $arr[$key]['goods_attr']            = stripslashes($value['goods_attr']);

		$arr[$key]['custom_size']           = json_decode($value['custom_size'], true);
		 if($arr[$key]['gifts_id']){
    			$gifts = read_static_cache('gifts_c_key',2);
    			if(!empty($gifts[$arr[$key]['gifts_id']])){
    				$arr[$key]['gifts_name'] = $gifts[$arr[$key]['gifts_id']]['gifts_name'];
    			}
    	}
		if ($value['goods_price'] == '0.01') {
			$issong['enable']   = true;
			$issong['goods_id'] = $value['goods_id'];
			$issong['rec_id']   = $value['rec_id'];
		}

        //是否要锁定，不能改
		//$arr[$key]['islock'] = ($arr[$key]['is_groupbuy'] && $arr[$key]['groupbuy_end_date'] < gmtime()) ? true : false;
		if((gmtime() - $arr[$key]['groupbuy_end_date']) > 3 * 86400) {
		    $arr[$key]['islock'] = 0;
		}
    	if (!$arr[$key]['is_groupbuy'] || $arr[$key]['groupbuy_end_date'] < gmtime()) {
    		   // $total['groupbuy_goods'] = 0;
    		    $arr[$key]['is_groupbuy'] = 0;
    	}
		if ($value['is_free_shipping'] == 1){
			$total['free_shipping_weight'] += $value['goods_weight'] * $value['goods_number'];
			$total['free_shipping_volume_weight'] += $value['goods_volume_weight'] * $value['goods_number'];
		}
		else {
			$total['shipping_weight']      += $value['goods_weight'] * $value['goods_number'];
			$total['shipping_volume_weight']   += $value['goods_volume_weight'] * $value['goods_number'];
		}

    }

    return array('goods_list'       => $arr,
                 'shipping_method'  => $shipping_method,
				 'show_marketprice' => $show_marketprice,
				 'total'            => $total,
                 'issong'           => $issong,
				 'Is_Out_Sotck'     => $Is_Out_Sotck,
				 'Is_chaozhong'     => $Is_chaozhong,
				 'Is_group_alert'   => $Is_group_alert
				 );
}

/**
 * m-flow-a-cart.html 获得购物车中的商品
 *
 */
function get_cart_goods() {
	global $Arr, $db,$_LANG, $cur_lang, $default_lang;;


	$sql = "SELECT c.*,g.is_promote,g.promote_end_date,promote_start_date FROM %s AS c, %s AS g WHERE g.goods_id=c.goods_id AND c.session_id='%s' AND (c.is_groupbuy=0 OR g.groupbuy_end_date>%d)";
	$sql = sprintf($sql, CART, GOODS, SESS_ID, gmtime());
	$arr = $db->arrQuery($sql);
	$gifts_in_cart = gifts_in_cart($arr);
	if(!empty($arr)) {
		foreach ($arr as $row) {
   			$spec        = explode(',', $row['goods_attr_id']);    //商品属性规格id
			$final_price = get_final_price($row['goods_id'], $row['goods_number'], true, $spec,$row['main_goods_id']);    //最终购买价格，包括促销价格、商品属性规格价格、团购价格
			$sql         = "UPDATE %s SET goods_price='{$final_price}' WHERE rec_id='{$row['rec_id']}' AND session_id='%s'";

			if($row['is_promote']==1){
				if(!(gmtime()>$row['promote_start_date']&&gmtime()<$row['promote_end_date']))
				$row['is_promote'] = 0;
			}
			$sql         = sprintf($sql, CART, SESS_ID);
			$db->query($sql);
		}
	}

	if (empty($_GET['pcode'])) {    //促销码
	    $pcode = empty($_SESSION['pcode_code']) ? '' : $_SESSION['pcode_code'];
	}
	else {
	    $pcode = htmlspecialchars(trim($_GET['pcode']));
	}
	$pcode_about = empty($_SESSION['pcode_about']) ? '' : $_SESSION['pcode_about'];

	if($gifts_in_cart>0){
		clearCoupon(); //有赠品则不能用优惠券
		$pcode = '';
		//remove_over_gift($arr);
	}

	unset($arr);

	$pcode_lv      = 0;   //优惠率
	$pcode_about   = '';  //优惠多少
	$pcode_fangshi = 1;   //优惠方式，是直接减还是百分比，默认为1，是百分比。
	$pcode_users   = '';  //优惠用户，默认是无限制的。
	$pcode_goods   = '';  //优惠产品，默认是无限制的。
	$is_applay     = '0'; //是否为申请的。
	$code_cat_id   = 0;   //分类促销
	$Arr['pcode_msg'] = '';
    if($pcode){ //是否有促削过来
		$sql = 'SELECT * FROM ' . PCODE . " WHERE code='$pcode'";
		$pcodeArr = $db->selectinfo($sql);

		if (empty($pcodeArr)) {
			$Arr['pcode_msg'] = $_LANG['code_not_exist'];
		}
		else {
			$pcode_fangshi = $pcodeArr['fangshi'];    //优惠方式，是直接减还是百分比，默认为1，是百分比。
			$pcode_users   = $pcodeArr['users'];      //允许使用的用户
			$pcode_goods   = $pcodeArr['goods'];      //允许使用的产品
			$pcode_times   = $pcodeArr['times'];      //允许使用的次数
			$pcode_cishu   = $pcodeArr['cishu'];      //已经使用的次数
			$pcode_about   = $pcodeArr['youhuilv'];   //优惠段
			$exp_time      = $pcodeArr['exp_time'];   //过期时间
			$is_applay     = $pcodeArr['is_applay'];  //是否为申请的
			$code_cat_id   = $pcodeArr['cat_id'];
            if($code_cat_id)//获得当前商品的包括当前分类ID和所有下级分类ID
			{
				$code_cat_array = explode(',',$code_cat_id);
				$code_cat_array = array_filter($code_cat_array);
				$childs_array = array();
				if(!empty($code_cat_array)){
					$typeArray =  read_static_cache('category_c_key',2);
					foreach($code_cat_array as $row){
						$childs_array = array_merge($childs_array,getChilds($typeArray,$row));
					}
					if(is_array($childs_array)){
						foreach($childs_array as $val){
							$code_cat_array[] = $val['cat_id'];

						}

					}
					unset($childs_array);
					$childs_array = $code_cat_array;
				}
			}
			if($exp_time < gmtime()){//查过期
				$Arr['pcode_msg'] = 'Error,this promotion code has expired.';
				$pcode_about = '';
			}
			elseif($pcode_times && ($pcode_cishu >= $pcode_times)) {  //查是否超过使用次数
				$Arr['pcode_msg'] = 'Error,this promotion code has frequency of use restrictions.';
				$pcode_about = '';
			}
			elseif(!empty($pcode_users)){	//查会员，检查是否限制用户使用

				if(empty($_SESSION['email'])){//判断用户是否登陆 ， 没有登陆则提醒需要登陆才能使用
					$Arr['pcode_msg'] = 'Error,The need to login to use the promotional code.';
					$pcode_about = '';
				}else{
					//elseif(strpos($pcode_users, $_SESSION['email']) === false){
					//前后都加','是为了防止出现 abc@qq.com,bbabc@qq.com;8,18,28,989892等情况的出现
                	$tmp_pusers		= ','.$pcode_users.',';
                	$tmp_uid		= ','.$_SESSION['user_id'].',';
                    if (strpos(','.$pcode_users.',',','.$_SESSION['email'].',') === false && strpos($tmp_pusers,$tmp_uid) === false){
				
						$Arr['pcode_msg'] = 'Error,You can not use this promotional code.';
						$pcode_about = '';
                    }
				}
			}
		}
    }//end if($pcode)

    $goods_list = array();
    $total      = array(
        'goods_price'           => 0, // 本店售价合计（有格式）
        'free_shipping_weight'  => 0, // 免邮费重量合计（有格式）
        'shipping_weight'       => 0, // 重量合计（有格式）
        'free_shipping_volume_weight'  => 0, // 免邮费体积重量合计（有格式）
        'shipping_volume_weight'=> 0, // 体积重量合计（有格式）
        'market_price'          => 0, // 市场售价合计（有格式）
        'saving'                => 0, // 节省金额（有格式）
        'save_rate'             => 0, // 节省百分比
        'goods_amount'          => 0, // 商品价格
        'groupbuy_goods'        => 1, // 团购商品
    );
    $shipping_method     = array();    //配送方式
    $goods_id            = array();    //商品id
    $real_goods_count    = 0;          //实际商品个数
    $show_marketprice    = 0;          //显示市场价格
    $is_free_shipping     =array();

    $sql = 'SELECT c.*,if(c.main_goods_id=0,c.rec_id,(select rec_id from '.CART." c1 where c1.session_id='" . SESS_ID . '\' and c1.goods_id= c.main_goods_id limit 1)) as goods_order, g.goods_weight,g.goods_volume_weight,g.is_free_shipping,g.goods_thumb,g.goods_title,g.url_title,g.is_groupbuy,g.groupbuy_end_date,g.cat_id,g.is_promote,g.promote_end_date,promote_start_date FROM ' . CART . ' AS c JOIN ' . GOODS . " g ON c.goods_id=g.goods_id AND c.session_id='" . SESS_ID . "' ORDER BY goods_order DESC,c.main_goods_id, c.rec_id DESC";


    $goods_order =0;
    $res = $db->arrQuery($sql);    //购物车商品
	$style[0]="style='background-color:#F5FDFE'";
	$style[1]="style='background-color:#FCFCF3'";

	if(!empty($res)) {
		$now_style_index=0;
    	foreach ($res as $k=> $row) {
    		if((!empty($res[$k+1])&&$res[$k+1]['goods_order']==$row['goods_order']) || ($k&&!empty($res[$k-1])&&$res[$k-1]['goods_order']==$row['goods_order'])){
    			$goods_order = $row['goods_order'];
    			$row['style'] = $style[$now_style_index];
    			if((!empty($res[$k+1])&&$res[$k+1]['goods_order']!=$row['goods_order']))$now_style_index = $now_style_index?0:1;
    		}
    		else {
    			$row['style'] = '';
    		}
    		if($row['gifts_id']){
    			$gifts = read_static_cache('gifts_c_key',2);
    			if(!empty($gifts[$row['gifts_id']])){
    				$row['gifts_name'] = $gifts[$row['gifts_id']]['gifts_name'];
    			}
    		}
    	    $goods_id[] = $row['goods_id'];
            $is_free_shipping[]=$row['is_free_shipping'];
    		$shipping_arr = explode(',', $row['shipping_method']);
    		!in_array($shipping_arr, $shipping_method) && $shipping_method[] = $shipping_arr;    //配送方式
            $row['goods_price']     = price_format($row['goods_price'], false);
            $total['market_price'] += $row['market_price'] * $row['goods_number'];
            if ($row['is_free_shipping'] == 1){
			   $total['free_shipping_weight'] += $row['goods_weight'] * $row['goods_number'];
			   $total['free_shipping_volume_weight'] += $row['goods_volume_weight'] * $row['goods_number'];
		    }else{
			   $total['shipping_weight'] += $row['goods_weight'] * $row['goods_number'];
			   $total['shipping_volume_weight']   += $row['goods_volume_weight'] * $row['goods_number'];
		    }


    		if ($row['market_price'] > $row['goods_price']) {
    		    $show_marketprice = 1;
    		}
			//指定分类下的产品参加促销
            $flag=0;
            if($code_cat_id)
            {
                $goods_cat_id = $GLOBALS['db']->getOne("select cat_id from ".GOODS." where goods_sn='".$row['goods_sn']."'");
                if(in_array($goods_cat_id, $childs_array)){
                    $pcode_used = 1;
                    $flag=1;
                }
                else{
                    $flag=0;
                }
            }
            $flag = (!empty($pcode_about)&&$flag)?1:0;

            $row['subtotal_yuan'] = price_format($row['goods_price'] * $row['goods_number'], false);
            if ($pcode && empty($is_applay) && strpos($pcode_goods, $row['goods_sn']) !== false || ($flag && empty($is_applay))){    //是否有促削过来，并针对了该产品
            	$spec        = explode(',', $row['goods_attr_id']);
    			$final_price = get_final_price($row['goods_id'], $row['goods_number'], true, $spec);
    			//$sql         = 'SELECT is_promote FROM ' . GOODS . " WHERE goods_id='{$row['goods_id']}'";
    			//$is_promote = $db->getOne($sql);    //先查是否是促销产品

    			//if(!$is_promote){    //不是促销产品，就允许用优惠码
    				$pcode_lv = get_youhui($pcode_about, $final_price);  //取得相应的优惠
    				($Arr['pcode_msg'] == '') && ($Arr['pcode_msg'] = get_code_str($pcode_about, $pcode_fangshi));
        			$_SESSION['pcode_lv']       = $pcode_about;
        			$_SESSION['pcode_code']     = $pcode;
        			$_SESSION['pcode_fangshi']  = $pcode_fangshi;
        			$_SESSION['pcode_goods']    = $pcode_goods;
        			$_SESSION['is_applay']      = $is_applay;
					$_SESSION['code_cat_id']    = $code_cat_id;    //针对分类的促销码
        			if ($pcode_fangshi == '1') {//百分比计算
        				$row['goods_price'] = $row['goods_price']* (1 - $pcode_lv * 0.01);
        				$row['subtotal'] = price_format($row['subtotal_yuan'] * (1 - $pcode_lv * 0.01), false);

        			}
        			else{
        				$row['goods_price'] = $row['goods_price']- $pcode_lv;
        				$row['subtotal'] = price_format($row['goods_price'] * $row['goods_number'], false);
        			}

        			if(strpos($pcode_goods, $row['goods_sn']) !== false) {
        			   $_SESSION['pcode'][$row['goods_sn']] = $row['subtotal'];
					   $pcode_goods_used = 1;
        			}
					if(@$exp_time < gmtime()){//查过期
						$row['using_coupon'] = 0;
					} else {
						$row['using_coupon'] = 1;
					}

    			//}
            }
            else{    //无促销码
    			$row['subtotal']  = $row['subtotal_yuan'];
    		}
			if(@$exp_time < gmtime()){//查过期
				$row['using_coupon'] = 0;
			} else {
				!empty($_SESSION['pcode_goods'])&&strpos($_SESSION['pcode_goods'], $row['goods_sn']) !== false?$row['using_coupon']  = 1:0;
			}
    		$total['goods_price']  += $row['subtotal'];
            $row['market_price']    = price_format($row['market_price'], false);
            $row['custom_size']     = json_decode($row['custom_size'], true);
    		$real_goods_count++;

            /*if (trim($row['goods_attr']) != '') {    //查询规格
                $sql = 'SELECT attr_value FROM ' . GATTR . ' WHERE goods_attr_id ' . db_create_in($row['goods_attr']);
                $attr_list = $db->getCol($sql);
                foreach ($attr_list as $attr) {
                    $row['goods_title'] .= " [' {$attr} '] ";
                }
            }*/

            $row['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $row['url_title']   = get_details_link($row['goods_id'], $row['url_title']);
    		$row['goods_attr']  = stripslashes($row['goods_attr']);

    		//是否要锁定，不能改
    		//$row['islock'] = ($row['is_groupbuy'] && $row['groupbuy_end_date'] < gmtime()) ? true : false;

    		if((gmtime() - $row['groupbuy_end_date']) > 3 * 86400) {
    		    $row['islock'] = 0;
    		}

    		if (!$row['is_groupbuy'] || $row['groupbuy_end_date'] < gmtime()) {
    		    $total['groupbuy_goods'] = 0;
    		    $row['is_groupbuy'] = 0;
    		}
     		if($row['is_promote']){//echo $row['goods_sn'];
				if(!(gmtime()>$row['promote_start_date']&&gmtime()<$row['promote_end_date']))
					$row['is_promote'] = 0;
			}
            $goods_list[] = $row;
        }//end foreach

		// 多语言 fangxin 2013/07/05
		if($cur_lang != $default_lang) {
			if(is_array($goods_list)) {
				foreach($goods_list as $key=>$value) {
					$goods_id = $value['goods_id'];
					$sql = 'SELECT g.*' .
							' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
							" WHERE g.goods_id = '$goods_id'";
					if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
						$goods_list[$key]['goods_title'] = $row_lang['goods_title'];
						$goods_list[$key]['goods_name']  = $row_lang['goods_name'];
					}
				}
			}
		}

	}

	$total['goods_amount'] = $total['goods_price'];
	//分类促销码应用失败提示
    if(empty($pcode_used) && $pcode && $code_cat_id){
        $Arr['pcode_msg'] = "Sorry, this coupon is not available.";
    }
    //针对产品促销码应用失败提示
    if(!empty($pcode_goods) && empty($pcode_goods_used)){
         $Arr['pcode_msg'] = "Sorry, this coupon is just available on ".$pcode_goods;
    }
	if (empty($pcode_goods) && $pcode_about && empty($code_cat_id)) {    //不针对商品并有优惠率
		$pcode_lv = get_youhui($pcode_about, $total['goods_price']);  //取得相应的优惠
		 if(($is_applay && $total['goods_amount'] > 50) || (!$is_applay && $pcode_goods == '' && empty($code_cat_id))){    //代金券 且金额大于50 或 优惠码针对所有产品
			$_SESSION['pcode_lv']      = $pcode_about;
			$_SESSION['pcode_code']    = $pcode;
			$_SESSION['pcode_fangshi'] = $pcode_fangshi;
			$_SESSION['pcode_goods']   = $pcode_goods;
			$_SESSION['is_applay']     = $is_applay;

			if ($pcode_fangshi == '1') {//百分比计算
				$total['goods_price'] = price_format($total['goods_price'] * (1 - $pcode_lv * 0.01),false);
			}
			else{
				$total['goods_price'] = price_format($total['goods_price'] - $pcode_lv,false);
			}

			$Arr['pcode_msg'] = get_code_str($pcode_about,$pcode_fangshi);
			$_SESSION['total_price'] = $total['goods_price'];
		 }
		 elseif($is_applay && $total['goods_amount'] < 50){

			 if(!empty($_SESSION['pcode_lv'])) {
			     unset($_SESSION['pcode_lv']);
			 }

			 if(!empty($_SESSION['pcode_code'])) {
			     unset($_SESSION['pcode_code']);
			 }

			 if(!empty($_SESSION['pcode_fangshi'])) {
			     unset($_SESSION['pcode_fangshi']);
			 }

			 if(!empty($_SESSION['pcode_goods'])) {
			     unset($_SESSION['pcode_goods']);
			 }

			 if(!empty($_SESSION['is_applay'])) {
			     unset($_SESSION['is_applay']);
			 }

			 $Arr['pcode_msg'] = "Sorry, the order amount is less than $50, so cash coupon can't be used.";
		 }
	}

    $total['saving']       = price_format($total['market_price'] - $total['goods_price'], false);

    if ($total['market_price'] > 0) {
        $total['save_rate'] = round(($total['market_price'] - $total['goods_price']) * 100 / $total['market_price']) . '%';
    }

    $total['goods_price']  = price_format($total['goods_price'], false);
    $total['market_price'] = price_format($total['market_price'], false);
    $total['real_goods_count'] = $real_goods_count;

    if (!$pcode_lv) {    //应用失败，清空优惠码
        unset($_SESSION['pcode_lv'], $_SESSION['pcode_code'], $_SESSION['pcode_fangshi'], $_SESSION['pcode_goods'], $_SESSION['total_price']);
    }

	$Arr['pcode'] = $pcode;

    return array(
            'goods_list'      => $goods_list,
            'goods_id'        => $goods_id,
            'shipping_method' => $shipping_method,
            'total'           => $total,
            'show_marketprice'=> $show_marketprice,
            'is_free_shipping' => $is_free_shipping
    );
}

/**
 * 取得购物车总金额
 * @params  boolean $include_gift   是否包括赠品
 * @param   int     $type           类型：默认普通商品
 * @return  float   购物车总金额
 */
function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS)
{
    $sql = "SELECT SUM(goods_price * goods_number) " .
            " FROM " . CART .
            " WHERE session_id = '" . SESS_ID . "' " .
            "AND rec_type = '$type' ";

    if (!$include_gift)
    {
        $sql .= ' AND is_gift = 0 AND goods_id > 0';
    }

    return floatval($GLOBALS['db']->getOne($sql));
}

/**
 * 检查某商品是否已经存在于购物车
 *
 * @access  public
 * @param   integer     $id
 * @param   array       $spec
 * @param   int         $type   类型：默认普通商品
 * @return  boolean
 */
function cart_goods_exists($id, $spec)
{
    /* 检查该商品是否已经存在在购物车中 */
    $sql = "SELECT COUNT(*) FROM " .CART.
            " WHERE session_id = '" .SESS_ID. "' AND goods_id = '$id' ".
            " AND goods_attr = '" .get_goods_attr_info($spec). "' ";

    return ($GLOBALS['db']->getOne($sql) > 0);
}

/**
 * 获得购物车中商品的总重量、总价格、总数量
 *
 * @access  public
 * @param   int     $type   类型：默认普通商品
 * @return  array
 */
function cart_weight_price()
{

    /* 获得购物车中商品的总重量 */
    $sql    = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, SUM(g.goods_volume_weight * c.goods_number) AS volume_weight, g.is_free_shipping, ' .
                    'SUM(c.goods_price * c.goods_number) AS amount, ' .
                    'SUM(c.goods_number) AS number '.
                ' FROM ' . CART . ' AS c '.
                ' LEFT JOIN ' . GOODS . ' AS g ON g.goods_id = c.goods_id '.
                "WHERE c.session_id = '" . (empty($_SESSION['email']) ? SESS_ID : $_SESSION['email']) . "' GROUP BY is_free_shipping " ;

    $rss = $GLOBALS['db']->arrQuery($sql);
	$row = array();
	foreach($rss as $v){
		$k = $v['is_free_shipping'];
		$row[$k]['volume_weight'] = floatval($v['volume_weight']);
		$row[$k]['weight'] = floatval($v['weight']);
		$row[$k]['amount'] = floatval($v['amount']);
		$row[$k]['number'] = intval($v['number']);
	}
    return $row;
}

//根据属性ID 获取属性的产品编号
function get_attr_goods_sn($arr){
    $attr_name   = '';
    if (!empty($arr))
    {
        $fmt = "%s:%s[%s] \n";

        $sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price,ga.attr_goods_sn ".
                "FROM ".GATTR." AS ga, ".
                    ATTR ." AS a ".
                "WHERE " .db_create_in($arr, 'ga.goods_attr_id')." AND a.attr_id = ga.attr_id";


     //   $sql = "SELECT ga.attr_goods_sn,ga.attr_value ".
      //          "FROM ".GATTR." AS ga  ".
          //      "WHERE " .db_create_in($arr, 'ga.goods_attr_id')." ";
        $res = $GLOBALS['db']->query($sql);

		$attr_name = '';
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
			if ($attr_name) $attr_name .=',';

			$attr_name .= empty($row['attr_goods_sn'])?$row['attr_name'].':'.$row['attr_value']:$row['attr_goods_sn'];
		}
	}
	return addslashes($attr_name);

}



/**
 * 添加商品到购物车
 *
 * @access  public
 * @param   integer $goods_id   商品编号
 * @param   integer $num        商品数量
 * @param   array   $spec       规格
 * @param   integer $parent     基本件
 * @return  boolean
 */
function addto_cart($goods_id, $num = 1,$spec = array(),$is_groupbuy_page=0,$callback='')
{
    global $_LANG ;
    if(empty($callback))$callback='';
	$now_time = gmtime();
	$real_ip = real_ip();
    /* 取得商品信息 */
    $sql = "SELECT g.goods_title,gifts_id, g.goods_sn, g.is_on_sale,g.is_groupbuy,g.groupbuy_max_number,g.groupbuy_price,g.groupbuy_final_price,g.groupbuy_people_first_number,g.groupbuy_people_final_number,g.groupbuy_start_date,g.groupbuy_end_date , ".
                "g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date, ".
                "g.promote_end_date, g.goods_weight,  ".
                "g.goods_number,c.shipping_method  ".
            " FROM " .GOODS. " AS g,". CATALOG . " AS c" .
            " WHERE g.goods_id = '$goods_id' " .
            " AND g.is_delete = 0 AND g.cat_id=c.cat_id";
    $goods = $GLOBALS['db']->selectinfo($sql);

    if (empty($goods))
    {
    	$msg ='goods not exists';
        echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
		exit;
    }
    $gifts_id = $goods['gifts_id'];
    if($goods['gifts_id']){
    	$gifts_id = $goods['gifts_id'];
    	$cart_goods = cart_goods_g();

    	if(gifts_in_cart($cart_goods)){//购物车已经有赠品

	    	$msg ='<script language="javascript">ymPrompt.alert({message:"'.$_LANG['addtocart_msg5'].'",width:350,height:170,title:"Failed to add to cart",btn:[["OK"]]});</script>';
	        echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
			exit;
    	}
		if(is_used_promo_code()){  //使用优惠卷
			$msg = '<script language="javascript">ymPrompt.alert({message:"'.$_LANG['addtocart_msg1'].'",width:350,height:170,title:"Failed to add to cart",btn:[["OK"]]});</script>';
	        echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
			exit;

		}

    	$gifts = read_static_cache('gifts_c_key',2);
    	$available_amount =  get_cart_available_amount($cart_goods);
    	if(!empty($gifts[$goods['gifts_id']])){//该赠品活动已取消
    		if($available_amount<$gifts[$goods['gifts_id']]['need_money']){
				$msg = '<script language="javascript">ymPrompt.alert({message:"'.$_LANG['addtocart_msg2'].number_format($gifts[$goods['gifts_id']]['need_money'],2).$_LANG['addtocart_msg3'].'",width:350,height:170,title:"Failed to add to cart",btn:[["OK"]]});</script>';
		        echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
				exit;
    		}
    	}else {
    		$gifts_id = 0;
    	}
    }

	//判断团购是否过期
	$goods['is_groupbuy'] = (!empty($goods['is_groupbuy']) && $goods['groupbuy_start_date'] < $now_time && $goods['groupbuy_end_date'] > $now_time )?1:0;
	if($goods['is_groupbuy'] &&$goods['groupbuy_max_number'] &&$num>$goods['groupbuy_max_number']) $num = $goods['groupbuy_max_number'];

    /* 是否正在销售 */
    if ($goods['is_on_sale'] == 0)
    {
		$msg = 'not on sale';
		echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
		exit;

    }

    /* 检查库存 */
    if ($GLOBALS['_CFG']['use_storage'] == 1 && $num > $goods['goods_number'])
    {
        $num = $goods['goods_number'];

		$msg = $_LANG['addtocart_msg4'].$num." ,<br><a  gid='".$goods_id."' class='blue_link'  href='?qty=".$num."'> ".$_LANG['addtocart_msg7'].$num.$_LANG['addtocart_msg8']."</a>";
		echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
		exit;
    }

    /* 计算商品的促销价格 */
    $spec_price             = spec_price($spec);
    $goods_price            = get_final_price($goods_id, $num, true, $spec);

	//限制用户
	if($goods['is_groupbuy']){
		$sql = "select count(*) from eload_cart where user_id = '".$_SESSION['user_id']."' and goods_id = '".$goods_id."'";
		$sql_order = "select count(*) from eload_order_goods as og,eload_order_info as oi where og.order_id = oi.order_id and is_groupbuy =1 and user_id = '".$_SESSION['user_id']."' and goods_id = '".$goods_id."'";

		$have_bought=0;
		if($GLOBALS['db']->getOne($sql)>0){
			$have_bought=1;   //已经在购物车
		}elseif ($GLOBALS['db']->getOne($sql_order)>0){
			$have_bought=2; //购买过
		}


		if($have_bought>0){
			//die($have_bought);
			//产品页点击
			if($is_groupbuy_page==0){
				if($have_bought==1){

					$msg = '<script language="javascript">location.href="m-flow-a-cart.htm"</script>';
					echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
					exit;
				}else{
					$msg =  '<script language="javascript">ymPrompt.alert({message:"'.$_LANG['addtocart_msg9'].'",width:350,height:170,title:"System Message",btn:[["OK"]]});</script>';
					echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
					exit;

				}
			}else{
			   if($have_bought==1){
					 header("Location:m-flow-a-cart.htm");
					 //header("Location: /500.html");
					 exit();
				}else{
			   		show_message($_LANG['addtocart_msg9'], '', $_SERVER['HTTP_REFERER'], 'warning',true);
			   		exit;
				}
			}

		}
	}


    $goods['market_price'] += $spec_price;
    $goods_attr             = get_goods_attr_info($spec);
	$attr_goods_sn          = get_attr_goods_sn($spec);
    $goods_attr_id          = join(',', $spec);
	if ($goods_attr_id == '') $goods_attr_id = 0;

	/*衣服类自定义尺寸*/
	$custom_size = empty($_SESSION['custom_size'])?'':$_SESSION['custom_size'];
	if (!empty($custom_size)){
		$sizeArr = json_decode($custom_size, true);
		if ($goods_id != $sizeArr['goods_id']){
			$custom_size = '';
		}
		unset($sizeArr);
		unset($_SESSION['custom_size']);
	}
	//exit;


    /* 初始化要插入购物车的基本件数据 */
    $parent = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'custom_size'   => $custom_size,
        'goods_id'      => $goods_id,
        'shipping_method'	=> $goods['shipping_method'],    //增加配送方式 by mashanling on 2011-08-22
        'goods_sn'      => addslashes($goods['goods_sn']),
        'goods_name'    => addslashes($goods['goods_title']),
        'market_price'  => $goods['org_price'],
        'goods_attr'    => $goods_attr,
        'goods_attr_id' => $goods_attr_id,
        'is_groupbuy'   => $goods['is_groupbuy'],
        'attr_goods_sn' => $attr_goods_sn,
		'real_ip'       => $real_ip,
        'addtime'       => gmtime(),
        'gifts_id'       => $gifts_id,
    );

    if($gifts_id)$_SESSION['last_gift_goods_id'] = $goods_id;  //记录上一次选择的赠品
	$lv     = empty($_SESSION['guajiang']['lv'])?0:intval($_SESSION['guajiang']['lv']);
	$jnum   = empty($_SESSION['guajiang']['num'])?0:intval($_SESSION['guajiang']['num']);
	$enable = empty($_SESSION['guajiang']['enable'])?false:true;
	$jiang_goods_price = price_format(($goods_price*(100-$lv)*0.01), false);
    /* 如果数量不为0，作为基本件插入 */
    if ($num > 0)
    {
		if($enable && $lv){//判断是否中奖
				$goods_price  = $jiang_goods_price;
		}


	if($goods_price == '0.01'){
		//检查购物车是否已经有0.01的商品
		$sql = "SELECT count(*) FROM " .CART." WHERE session_id = '" .SESS_ID. "' AND goods_price = '0.01' and is_groupbuy = 0 ";
		if ($GLOBALS['db']->getOne($sql)){
			$msg = '<script language="javascript">
			ymPrompt.alert({message:"'.$_LANG['addtocart_msg10'].$goods['goods_title'].$_LANG['addtocart_msg11'].'",width:350,height:170,title:"System Message",btn:[["OK"]]});</script>';

			echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
			exit;
		}

	   $sql = "select count(*) from ".ODRGOODS." as g  left join ".ORDERINFO." as o on o.order_id = g.order_id where g.goods_price = '0.01' and g.is_groupbuy = 0  AND DATEDIFF(curdate(),FROM_UNIXTIME(o.add_time,'%Y-%m-%d')) < 14
	   and o.user_id = '".$_SESSION['user_id']."'  ";
		if ($GLOBALS['db']->getOne($sql)){
			$msg = '<script language="javascript">
			ymPrompt.alert({message:"'.$_LANG['addtocart_msg10'].$goods['goods_title'].$_LANG['addtocart_msg11'].'",width:350,height:170,title:"System Message",btn:[["OK"]]});</script>';
			echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
			exit;
		}


	}


         /* 检查该商品是否已经存在在购物车中 */
        $sql = "SELECT goods_number FROM " .CART.
                " WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id'  AND goods_price = '$goods_price' ".
                " AND goods_attr = '" .get_goods_attr_info($spec). "' and custom_size = '$custom_size' " ;

        if ($goods['is_groupbuy']){
			$sql = "SELECT goods_number FROM " .CART.
					" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id'  ".
					" AND goods_attr = '" .get_goods_attr_info($spec). "' and custom_size = '$custom_size' " ;
			}


        $row = $GLOBALS['db']->selectinfo($sql);

        if($row) //如果购物车已经有此物品，则更新
        {

            if ( $num > $goods['goods_number'])
            {
				$num =$goods['goods_number'];
			}

			if ($goods_price == '0.01'||$gifts_id){
				$num = 1;
			}else{
				$num += $row['goods_number'];
			}

			//if($num>$goods['goods_number']) $num =$goods['goods_number'];
			if($gifts_id>0){
				$goods_price = 0;
			}else {
				$goods_price = get_final_price($goods_id, $num, true, $spec);
			}
			$sql = "UPDATE " . CART . " SET gifts_id=$gifts_id,goods_number = '$num'" .
				   " , goods_price = '$goods_price'".
				   " WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' AND goods_price = '$goods_price' ".
				   " AND goods_attr = '" .get_goods_attr_info($spec). "'  " ;
			$GLOBALS['db']->query($sql);
        }
        else //购物车没有此物品，则插入
        {

			if($enable && $lv && $jnum){//判断是否中奖
			      $qznum = $jnum;
			      if ($jnum == 3){
					  if ($num > $jnum){
						 $qznum = $num;
					  }
					  $num  = 0; // 清空原购买数量
				  }else{
					  if ($num > $jnum){
						 $num = $num - $jnum;
					  }else{
						 $num = 0 ;
					  }
				  }

					$parent['goods_price']  = $jiang_goods_price;
					if($parent['goods_price']>$parent['market_price'])$parent['market_price'] = $parent['goods_price'];
					$parent['goods_number'] = $qznum;
					$parent['lmt_num'] = $jnum;    //强制购买数量
					$parent['goods_off'] = $lv;

					$GLOBALS['db']->autoExecute(CART, $parent, 'INSERT');

					if ($num>0){
						$goods_price = get_final_price($goods_id, $num, true, $spec);
						 /* 检查该商品是否已经存在在购物车中 */
						$sql = "SELECT goods_number FROM " .CART.
								" WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id'  AND goods_price = '$goods_price' ".
								" AND goods_attr = '" .get_goods_attr_info($spec). "'  and custom_size = '$custom_size'" ;

						$row = $GLOBALS['db']->selectinfo($sql);


						if($row) //如果购物车已经有此物品，则更新
						{
							$num += $row['goods_number'];
							if ($GLOBALS['_CFG']['use_storage'] == 0 || $num <= $goods['goods_number'])
							{
								if($num > $goods['goods_number']) $num =30;
								$goods_price = get_final_price($goods_id, $num, true, $spec);
								$sql = "UPDATE " . CART . " SET gifts_id=$gifts_id,goods_number = '$num'" .
									   " , goods_price = '$goods_price'".
									   " WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' AND goods_price = '$goods_price' ".
									   " AND goods_attr = '" .get_goods_attr_info($spec). "' " ;
								$GLOBALS['db']->query($sql);
							}
							else
							{
							    echo $_LANG['addtocart_msg12']. $goods['goods_number'].$_LANG['addtocart_msg13'];
								return false;
							}
						}else{
							$parent['goods_price']  = max($goods_price, 0);
							if($parent['goods_price']>$parent['market_price'])$parent['market_price'] = $parent['goods_price'];
							$parent['goods_number'] = $num;
							$parent['goods_off'] = 0;
							$parent['lmt_num'] = 0;
							$GLOBALS['db']->autoExecute(CART, $parent, 'INSERT');
						}
					}

			}else{

				$goods_price = get_final_price($goods_id, $num, true, $spec);
				if($goods_price <= 0.01)$num=1;
				$parent['goods_price']  = max($goods_price, 0);
				if($parent['goods_price']>$parent['market_price'])$parent['market_price'] = $parent['goods_price'];
				$parent['goods_number'] = $num;
				$parent['gifts_id'] = $gifts_id;
				$parent['goods_off'] = 0;
				$parent['lmt_num'] = 0;    //强制购买数量`
				$GLOBALS['db']->autoExecute(CART, $parent, 'INSERT');
			}
        }
    }

    return true;
}

/**
 * 清空购物车
 * @param   int     $type   类型：默认普通商品
 */
function clear_cart()
{
   // $sql = "DELETE FROM " . CART .
       //     " WHERE session_id = '" . SESS_ID . "'";

    $sql = "DELETE c FROM " . CART ." as c ".
	        ", ".GOODS." as g ".
        " WHERE g.goods_id = c.goods_id and c.session_id = '" . SESS_ID . "'";
	//die($sql);
    $GLOBALS['db']->query($sql);
}

/**
 * 获得指定的商品属性
 *
 * @access  public
 * @param   array   $arr
 * @return  string
 */
function get_goods_attr_info($arr)
{
    $attr   = '';
    if (!empty($arr))
    {
        $fmt = "%s:%s[%s] \n";

        $sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price ".
                "FROM ".GATTR." AS ga, ".
                    ATTR ." AS a ".
                "WHERE " .db_create_in($arr, 'ga.goods_attr_id')." AND a.attr_id = ga.attr_id";
        $res = $GLOBALS['db']->query($sql);

		$attr_name = '';
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            //$attr_price = round(floatval($row['attr_price']), 2);
            //$attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
			if ($attr_name =='' or $attr_name != $row['attr_name']){
				if ($attr_name!=''){
					$attr .= '<br>'.$row['attr_name'].':';
				}else{
					$attr .= $row['attr_name'].':';
				}
				$attr_name = $row['attr_name'];
				$jiange = '';
			}else{
				$jiange = ',';
			}
				$attr .= $jiange.$row['attr_value'];
        }

      //  $attr = str_replace('[0]', '', $attr);
		//foreach ($arr as $v){//自定义接收数据
		  //$attr .= $v;
		//}
    }
    return addslashes($attr);
}

/**
 * 取得用户信息
 * @param   int     $user_id    用户id
 * @return  array   用户信息
 */
function user_info($user_id)
{
    $sql = "SELECT * FROM " . USERS .
            " WHERE user_id = '$user_id'";
    $user = $GLOBALS['db']->selectinfo($sql);

    return $user;
}

/**
 * 修改用户
 * @param   int     $user_id   订单id
 * @param   array   $user      key => value
 * @return  bool
 */
function update_user($user_id, $user)
{
    return $GLOBALS['db']->autoExecute(USERS,
        $user, 'UPDATE', "user_id = '$user_id'");
}

/**
 * 取得用户地址列表
 * @param   int     $user_id    用户id
 * @return  array
 */
function address_list($user_id)
{
    $sql = "SELECT * FROM " . ADDR .
            " WHERE user_id = '$user_id'";

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得用户地址信息
 * @param   int     $address_id     地址id
 * @return  array
 */
function address_info($address_id)
{
    $sql = "SELECT * FROM " . ADDR .
            " WHERE address_id = '$address_id'";

    return $GLOBALS['db']->selectinfo($sql);
}

/**
 * 取得用户当前可用红包
 * @param   int     $user_id        用户id
 * @param   float   $goods_amount   订单商品金额
 * @return  array   红包数组
 */
function user_bonus($user_id, $goods_amount = 0)
{
    $day    = getdate();
    $today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    $sql = "SELECT t.type_id, t.type_name, t.type_money, b.bonus_id " .
            "FROM " . $GLOBALS['ecs']->table('bonus_type') . " AS t," .
                $GLOBALS['ecs']->table('user_bonus') . " AS b " .
            "WHERE t.type_id = b.bonus_type_id " .
            "AND t.use_start_date <= '$today' " .
            "AND t.use_end_date >= '$today' " .
            "AND t.min_goods_amount <= '$goods_amount' " .
            "AND b.user_id<>0 " .
            "AND b.user_id = '$user_id' " .
            "AND b.order_id = 0";
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得红包信息
 * @param   int     $bonus_id   红包id
 * @param   string  $bonus_sn   红包序列号
 * @param   array   红包信息
 */
function bonus_info($bonus_id, $bonus_sn = '')
{
    $sql = "SELECT t.*, b.* " .
            "FROM " . $GLOBALS['ecs']->table('bonus_type') . " AS t," .
                $GLOBALS['ecs']->table('user_bonus') . " AS b " .
            "WHERE t.type_id = b.bonus_type_id ";
    if ($bonus_id > 0)
    {
        $sql .= "AND b.bonus_id = '$bonus_id'";
    }
    else
    {
        $sql .= "AND b.bonus_sn = '$bonus_sn'";
    }

    return $GLOBALS['db']->selectinfo($sql);
}

/**
 * 检查红包是否已使用
 * @param   int $bonus_id   红包id
 * @return  bool
 */
function bonus_used($bonus_id)
{
    $sql = "SELECT order_id FROM " . $GLOBALS['ecs']->table('user_bonus') .
            " WHERE bonus_id = '$bonus_id'";

    return  $GLOBALS['db']->getOne($sql) > 0;
}

/**
 * 设置红包为已使用
 * @param   int     $bonus_id   红包id
 * @param   int     $order_id   订单id
 * @return  bool
 */
function use_bonus($bonus_id, $order_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('user_bonus') .
            " SET order_id = '$order_id', used_time = '" . gmtime() . "' " .
            "WHERE bonus_id = '$bonus_id' LIMIT 1";

    return  $GLOBALS['db']->query($sql);
}

/**
 * 设置红包为未使用
 * @param   int     $bonus_id   红包id
 * @param   int     $order_id   订单id
 * @return  bool
 */
function unuse_bonus($bonus_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('user_bonus') .
            " SET order_id = 0, used_time = 0 " .
            "WHERE bonus_id = '$bonus_id' LIMIT 1";

    return  $GLOBALS['db']->query($sql);
}

/**
 * 计算积分的价值（能抵多少钱）
 * @param   int     $integral   积分
 * @return  float   积分价值
 */
function value_of_integral($integral)
{
    $scale = floatval($GLOBALS['_CFG']['integral_scale']);

    return $scale > 0 ? round(($integral / 100) * $scale, 2) : 0;
}

/**
 * 计算指定的金额需要多少积分
 *
 * @access  public
 * @param   integer $value  金额
 * @return  void
 */
function integral_of_value($value)
{
    $scale = floatval($GLOBALS['_CFG']['integral_scale']);

    return $scale > 0 ? round($value / $scale * 100) : 0;
}

/**
 * 订单退款
 * @param   array   $order          订单
 * @param   int     $refund_type    退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
 * @param   string  $refund_note    退款说明
 * @param   float   $refund_amount  退款金额（如果为0，取订单已付款金额）
 * @return  bool
 */
function order_refund($order, $refund_type, $refund_note, $refund_amount = 0)
{
    /* 检查参数 */
    $user_id = $order['user_id'];
    if ($user_id == 0 && $refund_type == 1)
    {
        die('anonymous, cannot return to account balance');
    }

    $amount = $refund_amount > 0 ? $refund_amount : $order['money_paid'];
    if ($amount <= 0)
    {
        return true;
    }

    if (!in_array($refund_type, array(1, 2, 3)))
    {
        die('invalid params');
    }

    /* 备注信息 */
    if ($refund_note)
    {
        $change_desc = $refund_note;
    }
    else
    {
        include_once(ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/admin/order.php');
        $change_desc = sprintf($GLOBALS['_LANG']['order_refund'], $order['order_sn']);
    }

    /* 处理退款 */
    if (1 == $refund_type)
    {
        log_account_change($user_id, $amount, 0, 0, 0, $change_desc);

        return true;
    }
    elseif (2 == $refund_type)
    {
        /* 如果非匿名，退回余额 */
        if ($user_id > 0)
        {
            log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
        }

        /* user_account 表增加提款申请记录 */
        $account = array(
            'user_id'      => $user_id,
            'amount'       => (-1) * $amount,
            'add_time'     => gmtime(),
            'user_note'    => $refund_note,
            'process_type' => SURPLUS_RETURN,
            'admin_user'   => $_SESSION['admin_name'],
            'admin_note'   => sprintf($GLOBALS['_LANG']['order_refund'], $order['order_sn']),
            'is_paid'      => 0
        );
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_account'), $account, 'INSERT');

        return true;
    }
    else
    {
        return true;
    }
}




function get_youhui($youhuilv = '',$goods_price){
	if(strpos($youhuilv,'-')!==false){
		$youhuilv_arr  = explode(',',$youhuilv);
		foreach($youhuilv_arr as $row){
			$zong_price =  explode('-',$row);
			if (!empty($zong_price[0])){
				if ($goods_price >= $zong_price[0]){
					$youhuilv = $zong_price[1];
				}
			}
		}
		if (strpos($youhuilv,'-')>0) $youhuilv = '0';  //如果没有处理，则归0
		if (!$goods_price) $youhuilv = '0';
	}

	return $youhuilv;
}


function get_code_str($youhuilv = '',$pcode_fangshi = '1'){
	$youhui_str = '';
	if(strpos($youhuilv,'-')!==false){
		$codeArr = explode(',',$youhuilv);
		$cArrCount = count($codeArr);
		foreach($codeArr as $key => $val){
			$valArr = explode('-',$val);
			if ($pcode_fangshi == '1'){
				$youhui_str .= 'Over $'.price_format($valArr[0],false).', '.$valArr[1].'% discount';
			}else{
				$youhui_str .= 'Over $'.price_format($valArr[0],false).', save $'.price_format($valArr[1],false);
			}
			if($key+1 < $cArrCount) $youhui_str .=',';
		}
	}else{
		if ($pcode_fangshi == '1'){
			$youhui_str = $youhuilv.'% Off';
		}else{
			$youhui_str = 'Save $'.price_format($youhuilv,false);
		}
	}
	return $youhui_str;
}



/**
 * 取得收货人信息
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_consignee($user_id)
{
    if (isset($_SESSION['flow_consignee']))
    {
        /* 如果存在session，则直接返回session中的收货人信息 */
        //print_r($_SESSION['flow_consignee']);
        return $_SESSION['flow_consignee'];
    }
    else
    {
        /* 如果不存在，则取得用户的默认收货人信息 */
        $arr = array();

        if ($user_id > 0)
        {
            /* 取默认地址 */
            $sql = "SELECT ua.*".
                    " FROM " . ADDR . " AS ua, ".USERS.' AS u '.
                    " WHERE u.user_id='$user_id' AND ua.address_id = u.address_id";

            $arr = $GLOBALS['db']->selectinfo($sql);
        }

        return $arr;
    }
}

/**
 * 查询购物车（订单id为0）或订单中是否有实体商品
 * @param   int     $order_id   订单id
 * @param   int     $flow_type  购物流程类型
 * @return  bool
 */
function exist_real_goods($order_id = 0)
{
    if ($order_id <= 0)
    {
        $sql = "SELECT COUNT(*) FROM " . CART .
                " WHERE session_id = '" . SESS_ID . "'";
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " . ODRGOODS . " WHERE order_id = '$order_id'";
    }

    return $GLOBALS['db']->selectinfo($sql) > 0;
}

/**
 * 检查收货人信息是否完整
 * @param   array   $consignee  收货人信息
 * @param   int     $flow_type  购物流程类型
 * @return  bool    true 完整 false 不完整
 */
function check_consignee_info($consignee)
{
	$res = !empty($consignee['zipcode']) &&!empty($consignee['firstname']) && !empty($consignee['lastname']) &&
		!empty($consignee['country']) && !empty($consignee['addressline1']) && !empty($consignee['tel']) &&
		!empty($consignee['email']);
	return $res;
}

/**
 * 获得上一次用户采用的支付和配送方式
 *
 * @access  public
 * @return  void
 */
function last_shipping_and_payment()
{
    $sql = "SELECT shipping_id, pay_id " .
            " FROM " . ORDERINFO .
            " WHERE user_id = '$_SESSION[user_id]' " .
            " ORDER BY order_id DESC LIMIT 1";
    $row = $GLOBALS['db']->selectinfo($sql);

    if (empty($row))
    {
        /* 如果获得是一个空数组，则返回默认值 */
        $row = array('shipping_id' => 3, 'pay_id' => "PayPal");
    }

    return $row;
}


/**
 * 获得订单信息
 *
 * @access  private
 * @return  array
 */
function flow_order_info($country_id)
{
    $order = isset($_SESSION['flow_order']) ? $_SESSION['flow_order'] : array();

    /* 初始化配送和支付方式 */
    if (!isset($order['shipping_id']) || !isset($order['pay_id']))
    {
        /* 如果还没有设置配送和支付 */
        if ($_SESSION['user_id'] > 0)
        {
            /* 用户已经登录了，则获得上次使用的配送和支付 */
            $arr = last_shipping_and_payment();

            if (!isset($order['shipping_id']))
            {
                $order['shipping_id'] = $arr['shipping_id'];
            }
            if (!isset($order['pay_id']))
            {
                $order['pay_id'] = $arr['pay_id'];
            }
            if (!isset($order['pay_id']))
            {
                $order['pay_id'] = $arr['pay_id'];
            }
        }
        else
        {
            if (!isset($order['shipping_id']))
            {
                $order['shipping_id'] = 0;
            }
            if (!isset($order['pay_id']))
            {
                $order['pay_id'] = 0;
            }
        }
    }

	$order['country'] = empty($country_id)?0:$country_id;
    return $order;
}

/**
 * 合并订单
 * @param   string  $from_order_sn  从订单号
 * @param   string  $to_order_sn    主订单号
 * @return  成功返回true，失败返回错误信息
 */
function merge_order($from_order_sn, $to_order_sn)
{
    /* 订单号不能为空 */
    if (trim($from_order_sn) == '' || trim($to_order_sn) == '')
    {
        return $GLOBALS['_LANG']['order_sn_not_null'];
    }

    /* 订单号不能相同 */
    if ($from_order_sn == $to_order_sn)
    {
        return $GLOBALS['_LANG']['two_order_sn_same'];
    }

    /* 取得订单信息 */
    $from_order = order_info(0, $from_order_sn);
    $to_order   = order_info(0, $to_order_sn);

    /* 检查订单是否存在 */
    if (!$from_order)
    {
        return sprintf($GLOBALS['_LANG']['order_not_exist'], $from_order_sn);
    }
    elseif (!$to_order)
    {
        return sprintf($GLOBALS['_LANG']['order_not_exist'], $to_order_sn);
    }

    /* 检查合并的订单是否为普通订单，非普通订单不允许合并 */
    if ($from_order['extension_code'] != '' || $to_order['extension_code'] != 0)
    {
        return $GLOBALS['_LANG']['merge_invalid_order'];
    }

    /* 检查订单状态是否是已确认或未确认、未付款、未发货 */
    if ($from_order['order_status'] != OS_UNCONFIRMED && $from_order['order_status'] != OS_CONFIRMED)
    {
        return sprintf($GLOBALS['_LANG']['os_not_unconfirmed_or_confirmed'], $from_order_sn);
    }
    elseif ($from_order['pay_status'] != PS_UNPAYED)
    {
        return sprintf($GLOBALS['_LANG']['ps_not_unpayed'], $from_order_sn);
    }
    elseif ($from_order['shipping_status'] != SS_UNSHIPPED)
    {
        return sprintf($GLOBALS['_LANG']['ss_not_unshipped'], $from_order_sn);
    }

    if ($to_order['order_status'] != OS_UNCONFIRMED && $to_order['order_status'] != OS_CONFIRMED)
    {
        return sprintf($GLOBALS['_LANG']['os_not_unconfirmed_or_confirmed'], $to_order_sn);
    }
    elseif ($to_order['pay_status'] != PS_UNPAYED)
    {
        return sprintf($GLOBALS['_LANG']['ps_not_unpayed'], $to_order_sn);
    }
    elseif ($to_order['shipping_status'] != SS_UNSHIPPED)
    {
        return sprintf($GLOBALS['_LANG']['ss_not_unshipped'], $to_order_sn);
    }

    /* 检查订单用户是否相同 */
    if ($from_order['user_id'] != $to_order['user_id'])
    {
        return $GLOBALS['_LANG']['order_user_not_same'];
    }

    /* 合并订单 */
    $order = $to_order;
    $order['order_id']  = '';
    $order['add_time']  = gmtime();

    // 合并商品总额
    $order['goods_amount'] += $from_order['goods_amount'];

    // 合并折扣
    $order['discount'] += $from_order['discount'];

    if ($order['shipping_id'] > 0)
    {
        // 重新计算配送费用
        $weight_price       = order_weight_price($to_order['order_id']);
        $from_weight_price  = order_weight_price($from_order['order_id']);
        $weight_price['weight'] += $from_weight_price['weight'];
        $weight_price['amount'] += $from_weight_price['amount'];
        $weight_price['number'] += $from_weight_price['number'];

        $region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
        $shipping_area = shipping_area_info($order['shipping_id'], $region_id_list);

        $order['shipping_fee'] = shipping_fee($shipping_area['shipping_code'],
            unserialize($shipping_area['configure']), $weight_price['weight'], $weight_price['amount'], $weight_price['number']);

        // 如果保价了，重新计算保价费
        if ($order['insure_fee'] > 0)
        {
            $order['insure_fee'] = shipping_insure_fee($shipping_area['shipping_code'], $order['goods_amount'], $shipping_area['insure']);
        }
    }

    // 重新计算包装费、贺卡费
    if ($order['pack_id'] > 0)
    {
        $pack = pack_info($order['pack_id']);
        $order['pack_fee'] = $pack['free_money'] > $order['goods_amount'] ? $pack['pack_fee'] : 0;
    }
    if ($order['card_id'] > 0)
    {
        $card = card_info($order['card_id']);
        $order['card_fee'] = $card['free_money'] > $order['goods_amount'] ? $card['card_fee'] : 0;
    }

    // 红包不变，合并积分、余额、已付款金额
    $order['integral']      += $from_order['integral'];
    $order['integral_money'] = value_of_integral($order['integral']);
    $order['surplus']       += $from_order['surplus'];
    $order['money_paid']    += $from_order['money_paid'];

    // 计算应付款金额（不包括支付费用）
    $order['order_amount'] = $order['goods_amount'] - $order['discount']
                           + $order['shipping_fee']
                           + $order['insure_fee']
                           + $order['pack_fee']
                           + $order['card_fee']
                           - $order['bonus']
                           - $order['integral_money']
                           - $order['surplus']
                           - $order['money_paid'];

    // 重新计算支付费
    if ($order['pay_id'] > 0)
    {
        // 货到付款手续费
        $cod_fee          = $shipping_area ? $shipping_area['pay_fee'] : 0;
        $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);

        // 应付款金额加上支付费
        $order['order_amount'] += $order['pay_fee'];
    }

    /* 插入订单表 */
    do
    {
        $order['order_sn'] = get_order_sn();
        if ($GLOBALS['db']->autoExecute(ORDERINFO, addslashes_deep($order), 'INSERT'))
        {
            break;
        }
        else
        {
            if ($GLOBALS['db']->errno() != 1062)
            {
                die($GLOBALS['db']->errorMsg());
            }
        }
    }
    while (true); // 防止订单号重复

    /* 订单号 */
    $order_id = $GLOBALS['db']->insertId();

    /* 更新订单商品 */
    $sql = 'UPDATE ' . ODRGOODS .
            " SET order_id = '$order_id' " .
            "WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $GLOBALS['db']->query($sql);

    include_once(ROOT_PATH . 'includes/lib_clips.php');
    /* 插入支付日志 */
    insert_pay_log($order_id, $order['order_amount'], PAY_ORDER);

    /* 删除原订单 */
    $sql = 'DELETE FROM ' . ORDERINFO .
            " WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $GLOBALS['db']->query($sql);

    /* 删除原订单支付日志 */
    $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('pay_log') .
            " WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $GLOBALS['db']->query($sql);

    /* 返还 from_order 的红包，因为只使用 to_order 的红包 */
    if ($from_order['bonus_id'] > 0)
    {
        unuse_bonus($from_order['bonus_id']);
    }

    /* 返回成功 */
    return true;
}


/**
 * 改变订单中商品库存
 * @param   int     $order_id   订单号
 * @param   bool    $is_dec     是否减少库存
 */
function change_order_goods_storage($order_id, $is_dec = true)
{

	return true;
	/* 查询订单商品信息 */
    $sql = "SELECT goods_id, SUM(goods_number) AS num FROM " . ODRGOODS .
            " WHERE order_id = '$order_id'  GROUP BY goods_id ";
    $res = $GLOBALS['db']->arrQuery($sql);

    foreach ($res as $row)
    {
			if ($is_dec)
			{
				$sql = "SELECT goods_number AS num FROM " . GOODS . " WHERE goods_id = '".$row['goods_id'] ."'";
				$goodnum = $GLOBALS['db'] -> selectinfo($sql);
				if ($goodnum["num"]!=0){
					$sql = "UPDATE " . GOODS .
							" SET goods_number = goods_number - '" . $row['num'] . "' " .
							" WHERE goods_id = '" . $row['goods_id'] . "' LIMIT 1";
					$GLOBALS['db']->query($sql);
				}
			}
			else
			{
				$sql = "UPDATE " . GOODS .
						" SET goods_number = goods_number + '" . $row['num'] . "' " .
						" WHERE goods_id = '" . $row['goods_id'] . "' LIMIT 1";
					$GLOBALS['db']->query($sql);
			}
    }
}

/**
 * 取得支付方式id列表
 * @param   bool    $is_cod 是否货到付款
 * @return  array
 */
function payment_id_list($is_cod)
{
    $sql = "SELECT pay_id FROM " . $GLOBALS['ecs']->table('payment');
    if ($is_cod)
    {
        $sql .= " WHERE is_cod = 1";
    }
    else
    {
        $sql .= " WHERE is_cod = 0";
    }

    return $GLOBALS['db']->getCol($sql);
}

/**
 * 生成查询订单的sql
 * @param   string  $type   类型
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_query_sql($type = 'finished', $alias = '')
{
    /* 已完成订单 */
    if ($type == 'finished')
    {
        return " AND {$alias}order_status = '" . OS_CONFIRMED . "'" .
               " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
               " AND {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " ";
    }
    /* 待发货订单 */
    elseif ($type == 'await_ship')
    {
        return " AND   {$alias}order_status = '" . OS_CONFIRMED . "'" .
               " AND   {$alias}shipping_status " . db_create_in(array(SS_UNSHIPPED, SS_PREPARING)) .
               " AND ( {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " OR {$alias}pay_id " . db_create_in(payment_id_list(true)) . ") ";
    }
    /* 待付款订单 */
    elseif ($type == 'await_pay')
    {
        return " AND   {$alias}order_status = '" . OS_CONFIRMED . "'" .
               " AND   {$alias}pay_status = '" . PS_UNPAYED . "'" .
               " AND ( {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR {$alias}pay_id " . db_create_in(payment_id_list(false)) . ") ";
    }
    /* 未确认订单 */
    elseif ($type == 'unconfirmed')
    {
        return " AND {$alias}order_status = '" . OS_UNCONFIRMED . "' ";
    }
    /* 未处理订单：用户可操作 */
    elseif ($type == 'unprocessed')
    {
        return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
               " AND {$alias}shipping_status = '" . SS_UNSHIPPED . "'" .
               " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
    }
    /* 未付款未发货订单：管理员可操作 */
    elseif ($type == 'unpay_unship')
    {
        return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
               " AND {$alias}shipping_status " . db_create_in(array(SS_UNSHIPPED, SS_PREPARING)) .
               " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
    }
    /* 已发货订单：不论是否付款 */
    elseif ($type == 'shipped')
    {
        return " AND {$alias}order_status = '" . OS_CONFIRMED . "'" .
               " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " ";
    }
    else
    {
        die('函数 order_query_sql 参数错误');
    }
}

/**
 * 生成查询订单总金额的字段
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_amount_field($alias = '')
{
    return "   {$alias}goods_amount + {$alias}tax + {$alias}shipping_fee" .
           " + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
           " + {$alias}card_fee ";
}

/**
 * 生成计算应付款金额的字段
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_due_field($alias = '')
{
    return order_amount_field($alias) .
            " - {$alias}money_paid - {$alias}surplus - {$alias}integral_money" .
            " - {$alias}bonus - {$alias}discount ";
}


/**
 * 根据国家计算运费列表,相当与加工shipping_method.php
 * @param   $shipping_list           array
 * @param   $country                 int
 * @param   $cart_goods_total_price  int
 * @return                           array
 */

function shipping_fee_cost($shipping_list,$country,$free_shipping_weight,$shipping_weight,$free_shipping_volume_weight,$shipping_volume_weight,&$total){
	$shipping_fee     = read_static_cache('shipping_fee',2);
	if (empty($shipping_fee[$country])) $country = '316'; //设置为其他国家
	$area_arr = read_static_cache('area_key',2);
	$shipping_list_country = $area_arr[$country];
	foreach($shipping_list as $k => $v) {
		if(!strpos($shipping_list_country['shipping'], (string)$v['id'])) {
			unset($shipping_list[$k]);
		}
	}
	foreach ($shipping_list as $k => $v){
	   $qizhong_price = 0;
	   $xuzhong_price = 0;
        if($free_shipping_weight>=0 && $shipping_weight==0)//只有免运费商品
        {
            switch ($v['id']){
                case '4'://中国邮政
                    $shipping_list[$k]['ship_price'] = get_chinapost_cost(0,$free_shipping_weight);
                break;
                case '3'://快递
                   $shipping_list[$k]['ship_price'] = $shipping_fee[$country]['free_exp_fee'];
                break;
                case '2'://标准
                   $shipping_list[$k]['ship_price'] = $shipping_fee[$country]['free_sta_fee'];
                break;
                case '1'://平邮
                   $shipping_list[$k]['ship_price'] = 0.00;
                break;
            }
        }
        elseif($shipping_weight>0)//只有运费商品,或者混合
        {
            if(($shipping_volume_weight + $free_shipping_volume_weight) > ($shipping_weight + $free_shipping_weight))
            {
                $order_shipping_weight = $shipping_volume_weight + $free_shipping_volume_weight;
            }
            else
            {
                $order_shipping_weight = $shipping_weight + $free_shipping_weight;
            }             
            switch ($v['id']){
                case '3'://快递
                    $qizhong_price = $shipping_fee[$country]['exp_fee'];
				    $xuzhong_price = $shipping_fee[$country]['exp_xu_fee'];
				    $shipping_list[$k]['ship_price']=count_shipping_fee($qizhong_price,$xuzhong_price,$order_shipping_weight);
                break;
                case '2'://标准
                    $qizhong_price = $shipping_fee[$country]['org_tran_fee'];
				    $xuzhong_price = $shipping_fee[$country]['org_tran_xu_fee'];
				    $shipping_list[$k]['ship_price'] = count_shipping_fee($qizhong_price,$xuzhong_price,$shipping_weight);
                break;
                case '1'://平邮
                   $qizhong_price = $shipping_fee[$country]['org_fee'];
				   $xuzhong_price = $shipping_fee[$country]['org_xu_fee'];
				   $shipping_list[$k]['ship_price']=round(($shipping_weight*$shipping_fee[$country]['regular_mail'])/HUILV,2); //fangxin 2013-10-24
                break;
            }

            if($v['id']=='4')
            {
                $shipping_list[$k]['ship_price'] = get_chinapost_cost($shipping_weight,0);
            }
        }
	}
    return $shipping_list;
}

/*
*china post
*weight_standard  不免邮的产品重量
*weight_freeship  免邮的产品重量
*/
function get_chinapost_cost($weight_standard,$weight_freeship)
{
    $get_chinapost_cost=0;

	if (!$weight_standard>0)
    {
		$flat=($weight_freeship*95)/HUILV;
		$chinapost= (18 + (ceil($weight_freeship*10)-1)*15 +8)*0.45/HUILV;
		if ($flat>=$chinapost)
			$get_chinapost_cost=0;
		else
			$get_chinapost_cost = round($chinapost-$flat,2);
    }
	else{
		$get_chinapost_cost=round((18 + (ceil($weight_standard*10)-1)*15 +8)*0.45/HUILV,2);
	}
    return $get_chinapost_cost;
}


/**
 * 增加配件至购物车
 *
 * @param int $peijian_goods_id 配件商品id
 * @param int $main_goods_id    主商品id
 * @param int $num              购买数量
 *
 * @return bool 如果添加成功，返回true，否则返回false
 */
function add_peijian_to_cart($peijian_goods_id, $main_goods_id, $num = 1) {
    global $db;
    if (isset($_SESSION['user_order_sn'])) {
        unset($_SESSION['user_order_sn']);
    }
    //主商品数量
    $main_goods_number = $db->getOne('SELECT goods_number FROM ' . CART . " WHERE session_id='" . SESS_ID . "' AND goods_id={$main_goods_id}");
    if (!$main_goods_number) {
        return false;
    }
    $now_time = gmtime();
    $real_ip  = real_ip();
    $sql      = 'SELECT g.goods_title,g.goods_sn,g.shop_price,gg.goods_price,c.shipping_method';
    $sql     .= ' FROM ' . GOODS . ' AS g JOIN ' . CATALOG . ' AS c ON g.cat_id=c.cat_id JOIN ';
    $sql     .= GROUPGOODS . ' AS gg ON gg.goods_id=g.goods_id';
    $sql     .= " WHERE gg.goods_id={$peijian_goods_id} AND gg.parent_id={$main_goods_id} AND g.is_delete=0 AND g.is_on_sale=1 AND g.goods_number>0";
    $info     = $db->selectinfo($sql);
    if (empty($info)) {
        return false;
    }

    //是否已经存在购物车中
    $cart_number = $db->getOne('SELECT goods_number FROM ' . CART . " WHERE session_id='" . SESS_ID . "' AND goods_id={$peijian_goods_id} AND main_goods_id={$main_goods_id}");

    if ($cart_number) {
        $num = min($main_goods_number, $cart_number + $num);
        $db->update(CART, 'goods_number=' . $num, "session_id='" . SESS_ID . "' AND goods_id={$peijian_goods_id} AND main_goods_id={$main_goods_id}");
    }
    else {
        $data = array(
            'user_id'         => $_SESSION['user_id'],
            'session_id'      => SESS_ID,
            'goods_id'        => $peijian_goods_id,
            'main_goods_id'   => $main_goods_id,
            'goods_sn'        => addslashes($info['goods_sn']),
            'goods_name'      => addslashes($info['goods_title']),
            'goods_price'     => $info['goods_price'],
            'market_price'    => $info['shop_price'],
            'goods_number'    => $num,
            'shipping_method' => $info['shipping_method'],
            'real_ip'         => $real_ip,
            'addtime'         => $now_time,
        );

        $db->autoExecute(CART, $data);
    }

    return true;
}


/**
 * 获取正价产品总金额
 *
 * @return $amount
 * @param array $cart
 */
function get_cart_available_amount($cart){
	//global $db;
	//$sql = "SELECT c.*,g.promote_price, g.promote_start_date, g.promote_end_date FROM ".CART." AS c, ".GOODS." AS g WHERE g.goods_id=c.goods_id AND c.session_id='".SESS_ID."' ";
	//$cart = $db->arrQuery($sql);
	$now = gmtime();
	$amount =0 ;
	foreach ($cart as $k=>$v){
		//if($v['is_groupbuy']==1){
		//	if(empty($v['groupbuy_start_date'])||empty($v['groupbuy_end_date'])||!(gmtime()>$v['groupbuy_start_date']&&gmtime()<$v['groupbuy_end_date'])){
				$v['is_groupbuy'] =0;
		//	}
		//}
		//if(($v['is_groupbuy']==0)&&!($now>$v['promote_start_date'] && $now<$v['promote_end_date'])){
			$amount +=$v['goods_price']*$v['goods_number'];
		//}
	}
	return $amount;
}

/**
 * 获取购物车里的赠品数
 *
 * @param array $cart
 * @return unknown
 */
function gifts_in_cart($cart_goods){
	if(empty($cart_goods)){
		$cart_goods = cart_goods_g();
	}
	$c =0 ;
	foreach ($cart_goods as $v){
		if($v['gifts_id']>0){
			$c++;
		}
	}
	return $c;
}
/**
 * 获取购物车里的产品
 *
 * @param array $cart
 * @return unknown
 */
function cart_goods_g(){
	global $db;
	$sql = "SELECT c.*,g.promote_price, g.promote_start_date, g.promote_end_date FROM ".CART." AS c, ".GOODS." AS g WHERE g.goods_id=c.goods_id AND c.session_id='".SESS_ID."' ";
	return $db->arrQuery($sql);
}



/**
 * 检查是否有效的赠品
 *
 * @param array $cart
 * @return unknown
 */
function is_available_gift($amount,$gifts_id){
	$gifts = read_static_cache('gifts_c_key',2);
    if(!empty($gifts[$gifts_id])){
    	if($amount<$gifts[$gifts_id]['need_money'])return false;
    }else {
    	return false;
    }
    return true;
}


/**
 * 检查并移除多余的赠品
 *
 * @param array $cart
 * @return unknown
 */
function remove_over_gift($cart_goods =''){
	global $db;
	if(empty($cart_goods))$cart_goods = cart_goods_g();
	$gifts_in_cart = gifts_in_cart($cart_goods);
	$available_amount = get_cart_available_amount($cart_goods);
	if($gifts_in_cart >0){
		foreach ($cart_goods as $v){
			if($v['gifts_id']&&!is_available_gift($available_amount,$v['gifts_id'])){
				$db->delete(CART,"rec_id='".$v['rec_id']."'");
			}
		}
	}
	if($gifts_in_cart>1){ //多于一个赠品
		$lastest_gift_rec_id =0;
		foreach ($cart_goods as $v){
			if($v['gifts_id']&&$v['rec_id']>$lastest_gift_rec_id){
				$lastest_gift_rec_id = $v['rec_id'];
			}
		}
		$sql = "delete from ".CART." where session_id ='".SESS_ID."' and gifts_id>0 and rec_id<$lastest_gift_rec_id";
		$db->query($sql);
		$cart_goods = cart_goods_g();
		$gifts_in_cart = gifts_in_cart($cart_goods);
	}


}

/**
 * 获取购物车里的产品
 *
 * @param array $cart
 * @return unknown
 */
function is_used_promo_code(){
    if (empty($_GET['pcode'])) {    //促销码
	   	 $pcode = empty($_SESSION['pcode_code']) ? '' : $_SESSION['pcode_code'];
	}
	else {
	    $pcode = htmlspecialchars(trim($_GET['pcode']));
	}
	if(empty($pcode))
		return false;
	else
		return true;
}

/**
 * 计算订单的平邮挂号费用,默认以美国ID=48计算
 * */
function get_pingyou_guahaofei($country_id = 48)
{
	$shipping_fee     = read_static_cache('shipping_fee',2);
	$sql = 'SELECT sum(g.goods_weight*c.goods_number) as total_weight , (g.chuhuo_price*c.goods_number) as total_chuhuo_price FROM ' . CART . ' AS c,' . GOODS . " AS g WHERE c.session_id='{$_SESSION['email'] }' AND c.goods_id=g.goods_id";
	$info= $GLOBALS['db']->selectinfo($sql);
	$differ_pingyou = $shipping_fee[$country_id]['registered_mail'] - $shipping_fee[$country_id]['regular_mail']; //fangxin 2013-10-24
	if(empty($differ_pingyou) || $differ_pingyou < 0)	{
		$differ_pingyou = 15;
	}
	$diff = ($info['total_weight']*$differ_pingyou)/HUILV-($info['total_chuhuo_price']*0.04)/HUILV;
	$diff = $diff>=0 ? $diff : 0;
	return price_format(floor(($diff + 2)*2)/2-0.01, false);
}

/**
 * 确认该付款方式是否支持所选的币种支付
 *
 * @param string $pay_name 付款方式
 * @param string $currency 币种
 * @param float $rate      汇率
 *
 * @return array 如果不支持返回'usd','1'，支持则返回所选币种和汇率
 */
function get_currency_rate($pay_name, $currency = 'USD', $rate = 1) {
	$currency_rate = array('order_currency'=>'USD', 'order_rate'=>$rate);
	if($currency == 'USD') return $currency_rate;
	$exchange_arr = read_static_cache('exchange',2);
	if(isset($currency) && $currency != 'USD' && isset($exchange_arr['Rate'][$currency]) && $exchange_arr['Rate'][$currency] > 0){
		$payment_arr = read_static_cache('payment', ADMIN_STATIC_CACHE_PATH);
		if(isset($pay_name) && isset($payment_arr[$pay_name])){
			$used_currencies_arr = array();
			if(isset($payment_arr[$pay_name]['used_currencies']) && $payment_arr[$pay_name]['used_currencies']){
				$used_currencies_arr = explode(",", $payment_arr[$pay_name]['used_currencies']);
				if(in_array($currency, $used_currencies_arr)){
					$currency_rate['order_currency'] = $currency; //币种
					$currency_rate['order_rate'] = $exchange_arr['Rate'][$currency]; //汇率
				}
			}
		}
	}
	return $currency_rate;
}
?>