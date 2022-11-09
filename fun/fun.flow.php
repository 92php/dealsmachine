<?php
/**
 * 购物流程
 */

if (!defined('INI_WEB')){die('访问拒绝');}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require(ROOT_PATH . 'lib/lib.f.order.php');
require(ROOT_PATH . 'lib/lib.f.goods.php');
require_once(ROOT_PATH . 'lib/modules/ipb.php');
require_once(ROOT_PATH . 'lib/lib.f.transaction.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$cur_lang. '/shopping_flow.php');
$Arr['lang'] =  $_LANG;
$user = new ipb($db);
/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */
global $cur_lang_url;

if ($_ACT == 'add_to_cart')
{
    /*------------------------------------------------------ */
    //-- 添加商品到购物车
    /*------------------------------------------------------ */
    $_VAL['goods_id'] = !empty($_REQUEST['goods_id'])?intval($_REQUEST['goods_id']):0;
    $_VAL['number']   = !empty($_REQUEST['number'])?intval($_REQUEST['number']):1;
    $act_sign         = !empty($_REQUEST['act_sign'])?trim($_REQUEST['act_sign']):'';
    $spec_str         = !empty($_REQUEST['spec'])?trim($_REQUEST['spec']):'';
    $attrchage        = !empty($_REQUEST['attrchage'])?trim($_REQUEST['attrchage']):'';
    $is_groupbuy_page = !empty($_REQUEST['is_groupbuy_page'])?$_REQUEST['is_groupbuy_page']:0;
	$callback = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : '';
	$spec             = $spec_str;
	$spec             = explode(',',$spec);
	$spec_str         = str_replace(',','',$spec_str);

    /* 如果商品有规格，而post的数据没有规格，跳到商品详情页 */
    if (empty($spec_str) && empty($act_sign)){
        $sql = "SELECT attr_name " .
                "FROM " . GATTR . " AS ga, " . ATTR . " AS a " .
                "WHERE ga.attr_id = a.attr_id " .
                "AND ga.goods_id = '" . $_VAL['goods_id'] . "' " .
                "AND a.attr_type = 1 AND a.disp=1 limit 4";
        $shuxing_arr = $db->selectinfo($sql);
        if (!empty($shuxing_arr))
        {
			$shuxing_str = implode(',',$shuxing_arr);
			if (empty($attrchage)){
				$local_url = 'window.location.href="'.get_details_link($_VAL['goods_id'], '').'"';
				$msg = '<script language="javascript">
				function slideHd(){'.$local_url.';}
				ymPrompt.alert({message:"This product has Multi-attribute to choose from, You need to choose your favorite.",width:300,height:160,title:"System Message",handler:slideHd,btn:[["OK","yes"]]});</script>';
				echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
				exit;
			}
       }
	}

    foreach ($_VAL as $key => $val){
		if ($val == 0){
			echo  $callback . '('.json_encode(array('ms'=>FEIFA )).')';
			exit;
		}
	}
    /* 团购产品添加到购物车 */
	if($act_sign == 'groupbuy'){
		if(empty($_SESSION['user_id'])){
				$msg = '&nbsp;&nbsp;&nbsp;&nbsp;<b>Redirecting...</b><script language="javascript">window.location.href="'.DOMAIN_USER.'/'.$cur_lang_url.'m-users-a-sign.htm?ref=daily-deals/";</script>';
				echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
				exit;
		}
	}

	if (addto_cart($_VAL['goods_id'], $_VAL['number'],$spec,$is_groupbuy_page,$callback)){
		$peijian_goods_id = empty($_GET['peijian_id']) ? '' : $_GET['peijian_id'];
		$peijian_goods_id = map_int($peijian_goods_id, true);
		if ($peijian_goods_id) {
		     foreach ($peijian_goods_id as $item) {
		            add_peijian_to_cart($item, $_VAL['goods_id'], $_VAL['number']);

		        }
		 }
		 $msg = '&nbsp;&nbsp;&nbsp;&nbsp;<b>Redirecting...</b><script language="javascript">window.location.href="'.DOMAIN_CART.'/'.$cur_lang_url.'m-flow-a-cart.htm";</script>';
		 echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
	}else{
		$msg = '<br>Add failure';
		echo  $callback . '('.json_encode(array('ms'=>$msg )).')';
	}
	exit();
} //end add_to_cart
elseif ($_ACT == 'link_buy')
{
    $goods_id = intval($_GET['goods_id']);
    $qty = !empty($_REQUEST['qty'])?intval(trim($_REQUEST['qty'])):1;
    if (!cart_goods_exists($goods_id,array()))
    {
        addto_cart($goods_id,$qty);
        if($act_sign == 'groupbuy'){
			if(empty($_SESSION['user_id'])){
				header("Location: ".DOMAIN_USER."/". $cur_lang_url ."m-users-a-join.htm\n");
				exit;
			}
			header("Location:".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-cart.htm\n");
			exit;
		}else{
			echo '&nbsp;&nbsp;&nbsp;&nbsp;<b>Redirecting...</a><script language="javascript">window.location.href="'.".DOMAIN_CART.".'/'.$cur_lang_url.'m-flow-a-cart.htm";</script>';
		}
    }
    exit;
}
elseif ($_ACT == 'Is_Apply')
{
	$lv = empty($_SESSION['guajiang']['lv'])?0:intval($_SESSION['guajiang']['lv']);
	$jnum   = empty($_SESSION['guajiang']['num'])?0:intval($_SESSION['guajiang']['num']);
	if ($lv && $jnum){
		$_SESSION["guajiang"]['enable'] = true;
		echo 'ok';
	}
    exit;
}
elseif ($_ACT == 'sign')
{
    /*
     * 用户登录注册
     */
        include_once('lib/lib_passport.php');
        if (!empty($_POST['act']) && $_POST['act'] == 'signin')
        {
            if ($user->login($_POST['email'], $_POST['passwordsign']))
            {
                /* 检查购物车中是否有商品 没有商品则跳转到首页 */
				if (ChangeSessId()){
					update_user_info();
					$sql = "SELECT COUNT(*) FROM " . CART . " WHERE session_id = '" . $_SESSION['email'] . "' ";
					$num = $db->getOne($sql);
					if ($num  > 0)
					{
						header("Location: ". DOMAIN_CART ."/". $cur_lang_url ."m-flow-a-checkout.htm\n");
					}
					else
					{
					}
				}
                exit;
            }
            else
            {
                $_SESSION['login_fail']++;
                show_message($_LANG['signin_failed'], 'Go back', DOMAIN_CART."/". $cur_lang_url ."m-users-a-join.htm",'warning');
            }
        }
        elseif (!empty($_POST['act']) && $_POST['act'] == 'signup')
        {
			if (md5($_POST['verifcode'])!=$_SESSION["verify"])
                {
                    show_message($_LANG['invalid_captcha']);
                }

            if (register(trim($_POST['email']), trim($_POST['password'])))
            {
                /* 用户注册成功 */
				ChangeSessId();
				update_user_info();
				$result = send_email(trim($_POST['email']), 13);//发送注册邮件
                header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-checkout.htm\n");
                exit;
            }
        }
	$Arr['step'] = 'sign';
	$Arr['seo_title'] = 'Sign in  - '.$_CFG['shop_name'];
	$Arr['seo_keywords'] = 'Sign in,'.$_CFG['shop_name'];
	$Arr['seo_description'] = 'Sign in, '.$_CFG['shop_name'];
}
elseif ($_ACT == 'consignee')
{
    /*------------------------------------------------------ */
    //-- 收货人信息
    /*------------------------------------------------------ */
	include_once('lib/lib.f.transaction.php');
	$consignee_list = array();
    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        $Arr['country_list'] = area_list();
        $area = read_static_cache('area_key',2);
        foreach($area as $key=>$row) {
            $areas[$key]['state'] = $row['state'];
            $areas[$key]['region_name'] = $row['region_name'];
            $areas[$key]['region_code'] = $row['region_code'];
            $areas[$key]['code'] = $row['code'];
        }
        $Arr['country_json'] = str_replace("'",'`',json_encode($areas));
        /* 获得用户所有的收货人信息 */
        if ($_SESSION['user_id'] > 0)
        {
            $consignee_list = get_consignee_list($_SESSION['user_id']);
            if (count($consignee_list) < 5)
            {
                /* 如果用户收货人信息的总数小于 5 则增加一个新的收货人信息 */
                $consignee_list[] = array('country' => '41', 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
            }
        }
        else
        {
            if (isset($_SESSION['flow_consignee'])){
                $consignee_list = array($_SESSION['flow_consignee']);
            }else{
				$consignee_list[] = array('country' => '41');
			}
        }
        foreach($consignee_list as $key=>$val) {
            if(!empty($area[$val['country']]['state'])) {
                $consignee_list[$key]['states'] = $area[$val['country']]['state'];
            }else {
                $consignee_list[$key]['states'] = "";
            }
            $consignee_list[$key]['code'] =$area[$val['country']]['code'];
			$consignee_list[$key]['country_json'] = $Arr['country_json'];
        }
        $Arr['consignee_list'] = $consignee_list;
    }
    else
    {
        /*
         * 保存收货人信息
         */
        $consignee = array(
            'address_id'    => empty($_POST['address_id']) ? 0  : intval($_POST['address_id']),
            'firstname'     => empty($_POST['firstname'])  ? '' : trim($_POST['firstname']),
            'lastname'     => empty($_POST['lastname'])  ? '' : trim($_POST['lastname']),
            'country'       => empty($_POST['country'])    ? '' : intval($_POST['country']),
            'province'      => empty($_POST['province'])   ? '' : $_POST['province'],
            'city'          => empty($_POST['city'])       ? '' : $_POST['city'],
            'email'         => empty($_POST['email'])      ? '' : $_POST['email'],
            'addressline1'       => empty($_POST['addressline1'])    ? '' : $_POST['addressline1'],
            'addressline2'       => empty($_POST['addressline2'])    ? '' : $_POST['addressline2'],
            'zipcode'       => empty($_POST['zipcode'])    ? '' : make_semiangle(trim($_POST['zipcode'])),
            'tel'           => empty($_POST['tel'])        ? '' : make_semiangle(trim($_POST['tel'])),
            'code'          => empty($_POST['code'])       ? '' : $_POST['code'],
        );

        if ($_SESSION['user_id'] > 0)
        {
            include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
            /* 如果用户已经登录，则保存收货人信息 */
            $consignee['user_id'] = $_SESSION['user_id'];
            save_consignee($consignee, true);
        }

        /* 保存到session */
        $_SESSION['flow_consignee'] = stripslashes_deep($consignee);
        if ($_POST['from_checkout']) {
            exit;
        }
        header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-checkout.htm\n");
        exit;
    }
		$Arr['cart_step'] = 2;
		$Arr['step'] = 'consignee';
		$Arr['seo_title'] = 'Shipping Address - '.$_CFG['shop_name'];
		$Arr['seo_keywords'] = 'Shipping Address ,'.$_CFG['shop_name'];
		$Arr['seo_description'] = 'Shipping Address , '.$_CFG['shop_name'];
}
elseif ($_ACT == 'drop_consignee')
{
    /*------------------------------------------------------ */
    //-- 删除收货人信息
    /*------------------------------------------------------ */
    include_once('lib/lib.f.transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-consignee.htm\n");
        exit;
    }
    else
    {
        show_message($_LANG['not_fount_consignee']);
    }
}
elseif ($_ACT == 'checkout') {
    /*
     * 检查用户是否已经登录
     * 如果用户已经登录了则检查是否有默认的收货地址
     * 如果没有登录则跳转到登录和注册页面
     */
    if ($_SESSION['user_id'] == 0) {
        header("Location: ".DOMAIN_USER."/$cur_lang_url"."m-users-a-join.htm?flow=checkout\n");
        exit;
    }
    /*------------------------------------------------------ */
    //-- 订单确认
    /*------------------------------------------------------ */
    /* 检查购物车中是否有商品 */
	check_cart();
	$Arr['old_dan_num'] = empty($_COOKIE['WEBF-dan_num'])?0:intval($_COOKIE['WEBF-dan_num']);

	//检查drop shipping 是否近2个月满 10单
	//$Arr['IsMan10'] = get_resent60_order();
    $Arr['country_list'] = area_list();
    $consignee = get_consignee($_SESSION['user_id']);
    $is_consignee = check_consignee_info($consignee); //检查收货人信息是否完整
    $Arr['is_consignee'] = $is_consignee;
    $_SESSION['flow_consignee'] = $consignee;
	$area_Arr = read_static_cache('area_key',2);
	$area_payment = empty($area_Arr[$consignee["country"]]["payment"])?'':$area_Arr[$consignee["country"]]["payment"];

    if (41 == isset($country_id)?$country_id:'' && in_array($_SESSION['flow_consignee']['province'], array('PR', 'Puerto Rico'))) {//美国国家且洲为Puerto Rico,波多黎各,按波多黎各计算
        $shipping_country_id = 163;
    }

    if (isset($shipping_country_id)) {
        $area_shipping = empty($area_Arr[$shipping_country_id]["shipping"])?'':$area_Arr[$shipping_country_id]["shipping"];
    }
    else {
        $area_shipping = empty($area_Arr[$consignee["country"]]["shipping"])?'':$area_Arr[$consignee["country"]]["shipping"];
    }

	$country_id   = empty($consignee["country"])?0:$consignee["country"];
    $consignee["country_name"] =  empty($area_Arr[$country_id])?'':$area_Arr[$country_id]["region_name"];
    $consignee["region_code"] =  empty($area_Arr[$country_id])?'':$area_Arr[$country_id]["region_code"];
	unset($area_Arr);
    $area = read_static_cache('area_key',2);
     foreach($area as $key=>$row) {
            $areas[$key]['state'] = $row['state'];
            $areas[$key]['region_name'] = $row['region_name'];
            $areas[$key]['region_code'] = $row['region_code'];
            $areas[$key]['code'] = $row['code'];
     }
    $consignee['states'] = !empty($consignee['country'])?$area[$consignee['country']]['state']:'';
    if(!empty($consignee['states'])){
	    if(!in_array($consignee['province'],$consignee['states'])){
	          $consignee['province_not_in'] = 1;
	    }
    }
    $consignee['country_json'] = str_replace("'",'`',json_encode($areas));
    $Arr['consignee'] = $consignee;
    /* 对商品信息赋值 */
	$cart_goods = cart_goods(); // 取得商品列表，计算合计,重量
    //判断购物车中是否包含电池商品
        $is_battery = 0;
        foreach($cart_goods['goods_list'] as $val){    
            if($val['cat_id'] == 500 || $val['cat_id'] == 529 || $val['cat_id'] == 531 || $val['cat_id'] == 1784 || $val['cat_id'] == 533 || $val['cat_id'] == 532 || $val['cat_id']  == 1789  || $val['cat_id']  == 1795 || $val['cat_id']  == 337  || $val['cat_id']  == 551 || $val['cat_id']  == 1832 ||$val['cat_id']  == 1833 || $val['cat_id']  == 1834 || $val['cat_id']  == 1835 || $val['cat_id']  == 1836 || $val['cat_id']  == 1837 || $val['cat_id']  == 1838 || $val['cat_id']  == 1839){
                $is_battery = 1;
                break;
            }
        }
        $Arr['is_battery'] = $is_battery;
	$free_shipping_weight = $cart_goods['total']['free_shipping_weight'];
	$shipping_weight = $cart_goods['total']['shipping_weight'];
	$free_shipping_volume_weight  = $cart_goods['total']['free_shipping_volume_weight'];
	$shipping_volume_weight  = $cart_goods['total']['shipping_volume_weight'];
    $Arr['goods_list'] = $cart_goods['goods_list'];
    $Arr['is_include_gifts'] = gifts_in_cart($cart_goods['goods_list']);
    $Arr['Is_Out_Sotck'] = $cart_goods['Is_Out_Sotck'];
	$Arr['show_marketprice'] = $cart_goods['show_marketprice'];

	/* 修正重量显示 */
	$cart_goods['total']['free_shipping_weight']  = formated_weight($free_shipping_weight);
	/* 修正重量显示 */
	$cart_goods['total']['shipping_weight']  = formated_weight($shipping_weight);
	//单个订单重量大于2KG禁用免邮配送方式 by mashanling on 2012-12-15 15:32:26
	disabled_free_shipping_method($cart_goods);

	$Arr['cart_total'] = $cart_goods['total'];  //赋值格式化了重量

    /* 对是否允许修改购物车赋值 */
	$Arr['allow_edit_cart'] = 1;

	//取得购物流程设置
    $Arr['config'] = $_CFG;
	//取得订单信息
    $order = flow_order_info($country_id);
    $Arr['order'] = $order;
	//计算订单的费用
    $total = order_fee($order, $cart_goods['goods_list']);
    $Arr['total'] = $total;
    $Arr['shopping_money'] = $total['formated_goods_price'];
    /* 取得配送列表 */
    $region            = array(empty($consignee['country'])?'':$consignee['country'],empty($consignee['province'])?'':$consignee['province'],empty($consignee['city'])?'':$consignee['city']);
	$shipping_list     = get_best_shipping($cart_goods['shipping_method']);    //最佳配送方式  by mashanling on 2011-08-23s

     if ($Arr['is_battery'] ==1) {
         unset($shipping_list[2]  );
         unset($shipping_list[3]  );
       //var_dump( $shipping_list);
    
    }
    $area_ship_arr     = explode(',',$area_shipping);
	$area_shipping_temp_Arr = array();
	foreach($area_ship_arr as $v){
		if ($v!='' && !empty($shipping_list[$v]))
		$area_shipping_temp_Arr[$v] = $shipping_list[$v];    //取得地区配送列表
	}
     
	//加工运费列表
	// $area_shipping_temp_Arr     = shipping_fee_cost($area_shipping_temp_Arr,$country_id,$free_shipping_weight,$shipping_weight,$total['goods_price_formated']);
    $area_shipping_temp_Arr     = shipping_fee_cost($area_shipping_temp_Arr,isset($shipping_country_id) ? $shipping_country_id : $country_id,$free_shipping_weight,$shipping_weight,$free_shipping_volume_weight,$shipping_volume_weight,$total);
	if($cart_goods['Is_chaozhong']) {
	  if (isset($area_shipping_temp_Arr[1])) {
	      //unset($area_shipping_temp_Arr[1]);
	  }
	}

    $cart_weight_price = cart_weight_price();
    $insure_disabled   = true;
    $cod_disabled      = true;

	//代金券 不使用积分
	$_SESSION['is_applay']   = empty($_SESSION['is_applay'])?'':$_SESSION['is_applay'];
    if($_SESSION['is_applay'] == ''){
		//---------------BOF:积分--------------------------------
		$point=get_point_info($_SESSION['user_id'],$total['goods_price']);
	}else{
		$point=array();
	}

	$point['min_use_point'] = $_CFG['min_use_point'];  //最小使用的积分数
	$point['point_rate']    = $_CFG['point_rate'];
	$Arr['point']           = $point;
	//---------------EOF:积分--------------------------------

    $Arr['shipping_list']        =   $area_shipping_temp_Arr;
    $Arr['insure_disabled']      = $insure_disabled;
	//$Arr['Insurance']       =  $total['formated_goods_price']*0.02 + 1; //投保  产品金额0.02
	$Arr['free_shipping_weight'] = formated_weight($free_shipping_weight);
	$Arr['shipping_weight']      = formated_weight($shipping_weight);
    $payment_list = available_payment_list();
	$area_pay_Arr = explode(',',$area_payment);
	foreach ($payment_list as $key => $val){
        $payment_list[$key]['pay_desc'] = stripslashes($payment_list[$key]['pay_desc']);
		if (!in_array($val['pay_code'],$area_pay_Arr)){
			unset($payment_list[$key]);
			continue;
		}
		/*
		 * 检测用户历史订单是否符合GC付款规则
		 */
		if('webcollect' == $val['pay_code']) {
			if(0 == check_user_gc_payment($_SESSION['user_id'], $country_id)) {
				unset($payment_list[$key]);
				continue;
			}
		}
        /*
         * 不到指定金额，不能使用相应的付款方式
         */
	    if($total['formated_goods_price'] < 10 && ($val['pay_code'] == 'boletoBancario' || $val['pay_code'] == 'BankTransfer' ||$val['pay_code'] == 'DirectDebit')) {
             unset($payment_list[$key]);
             continue;
        }

        if($total['formated_goods_price'] < 90 && $val['pay_code'] == 'WesternUnion') {
             unset($payment_list[$key]);
             continue;
        }
	    if($val['pay_code'] == 'webcollect'){
        	if(disable_gc_payment($total['formated_goods_price'],$consignee["region_code"],$_SESSION['user_id'])){
        		unset($payment_list[$key]);
        	}
        }
		if($val['pay_code'] == 'WiredTransfer') {
			if ($total['amount'] < 1000) {//小于1000
                $payment_list[$key]['pay_desc'] = stripslashes($payment_list[$key]['pay_desc_lt1000']);
            }
            else {
                $payment_list[$key]['pay_desc'] = stripslashes($payment_list[$key]['pay_desc_gt1000']);
            }
        }
        //暂时去掉GC付款
        if($val['pay_code'] == 'webcollect'){
            unset($payment_list[$key]);
        }
        //暂时去掉BankTransfer付款
        /*if($val['pay_code'] == 'BankTransfer'){
            unset($payment_list[$key]);
        }*/        
        //暂时去掉DirectDebit付款
        /*if($val['pay_code'] == 'DirectDebit'){
            unset($payment_list[$key]);
        }*/ 
	}

    disable_gc_payment2($cart_goods['goods_list'], $payment_list);//电子烟干掉gc付款 by mashanling on 2014-02-19 10:57:35
    $Arr['payment_list'] = $payment_list;

    //检查美国仓产品
    $goods_id_str = '';
    for( $i = 0, $n = count($cart_goods['goods_list']); $i < $n; $i ++ )
    {
        $goods_id_str.=$cart_goods['goods_list'][$i]["goods_id"].",".$cart_goods['goods_list'][$i]["rec_id"]."|";
    }
    $Arr['goods_id_str']=substr($goods_id_str,0,-1);

    //获取订单挂号费
	$guahaofei = get_pingyou_guahaofei($consignee["country"]);
	$Arr["guahaofei"] = $guahaofei;

    /* 保存 session */
    $_SESSION['flow_order'] = $order;
	$Arr["step"] = 'checkout';
	$Arr["cart_step"] = 3;
	$Arr['seo_title'] = 'My Cart - Shipping Method - '.$_CFG['shop_name'];
	$Arr['seo_keywords'] = 'My Cart - Shipping Method  ,'.$_CFG['shop_name'];
	$Arr['seo_description'] = 'My Cart - Shipping Method  , '.$_CFG['shop_name'];

    //地址包含apo,po box,box，干掉dhl by mashanling on 2012-10-05 13:39:38
	disabled_dhl($consignee);
	//使用coupon后不可使用积分 fangxin 20140327 PM
	$pcode_code = isset($_SESSION['pcode_code'])?$_SESSION['pcode_code']:'';
	if($pcode_code) $Arr['point']['is_coupon'] = $pcode_code;
	//谷歌再营销
	$exchange = read_static_cache('exchange',2);
	$currency = get_currency();
	if(1== count($cart_goods['goods_list'])) {
		$prodid = "'". $cart_goods['goods_list'][0]['goods_sn'] . $currency['lang'] ."'";
	} else {
		if($cart_goods['goods_list']) {
			$prodid = "[";
			foreach($cart_goods['goods_list'] as $key=>$goods_list_value) {
				$prodid .= "'". $goods_list_value['goods_sn'] . $currency['lang'] . "'" . ",";
			}
			$prodid = substr($prodid,0,strlen($prodid)-1);
			$prodid .= "]";
		}
	}
	$google_tag_params = array(
		'prodid' => $prodid,
		'pagetype' => "'purchase'",
		'totalvalue' => round($total['amount_formated']*$exchange['Rate'][''.$currency['currency'].''],2),
		'currency' => "'". $currency['currency'] ."'",
		'pcat' => "''"
	);
	$Arr['google_tag_params'] = $google_tag_params;
}

elseif ($_ACT == 'exp_checkout')
{
    //第一次支付失败，重定向到paypal返回后，调转到另一个页面
    if(isset($_SESSION['paypal_10486']) && $_SESSION['paypal_10486']=='yes')
    {
        header("Location:".DOMAIN_CART."/".$cur_lang_url."m-flow-a-paypal_10486.htm?token=".$_GET['token'].'&PayerID='.$_GET['PayerID'].'&order_sn='.$_SESSION["orderno"] );
        exit;
    }

	global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
	global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
	global $gv_ApiErrorURL;
	global $sBNCode,$db;
	include("paypalfunctions.php");
	$token = !empty($_GET['token'])?$_GET['token']:'';
	$resArray = GetShippingDetails($token);
    /* 检查购物车中是否有商品 */
	check_cart();
    $consignee     = array();
    $ack = strtoupper($resArray["ACK"]); //判断取得PAYPAL地址是否成功
	if($ack == "SUCCESS"){
		$consignee['firstname'] = addslashes($resArray['FIRSTNAME']);
		$consignee['lastname'] =  addslashes($resArray['LASTNAME']);
		$consignee['addressline1'] =  addslashes($resArray['SHIPTOSTREET']);
		$consignee['addressline2'] =  !empty($resArray['SHIPTOSTREET2'])?addslashes($resArray['SHIPTOSTREET2']):'';
		$consignee['city'] =  addslashes($resArray['SHIPTOCITY']);
		$consignee['province'] =  addslashes($resArray['SHIPTOSTATE']);
		$consignee['country'] =  addslashes($resArray['SHIPTOCOUNTRYNAME']);
		$consignee['country_name'] = addslashes($resArray['SHIPTOCOUNTRYNAME']);
		$consignee['zipcode'] =  addslashes($resArray['SHIPTOZIP']);
		$consignee['tel'] =  !empty($resArray['PHONENUM'])?$resArray['PHONENUM']:'';
		$consignee['email'] =  $resArray['EMAIL'];
		if (empty($_SESSION['user_id']))
		{
			require_once(ROOT_PATH . 'lib/modules/ipb.php');
			$user = new ipb($db);
			$email = $resArray['EMAIL'];
			$sql = "delete from ".CART ." where session_id ='$email'";
			$db->query($sql);
			$user->set_session($email);
			$user->set_cookie($email);
			if ($_SESSION['user_id'] > 0)
			{
				ChangeSessId();
				update_user_info();
			}else{
				//创建会员
				$password = rand_string(8);
				include_once('lib/lib_passport.php');
				if (register($resArray['EMAIL'],$password) !== false)
				{
					$sql = "update ".USERS." SET firstname ='".$consignee['firstname']."', lastname ='".$consignee['lastname']."' where email = '".$email."'";
					$db->query($sql);
					$_SESSION['jointimes'] = time();
					ChangeSessId();
					update_user_info();
					send_email($email, 13, $password);
				 }
			}
		}
	}

    $country_id = $db->getOne("select region_id  from ".REGION." where region_code ='".trim($resArray["SHIPTOCOUNTRYCODE"])."'");
    $consignee["country"] =  empty($country_id)?'316':$country_id;
	$sql = "SELECT address_id FROM `eload_user_address`  where  email = '".$consignee['email']."'";
	if( !$db->getOne($sql) && !empty($consignee['email'])){
		include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
		/* 如果用户已经登录，则保存收货人信息 */
		$consignee['user_id'] = $_SESSION['user_id'];
		save_consignee($consignee, true);
	}
	$_SESSION['is_exp_checkout'] = 'yes';
    $Arr['old_dan_num'] = empty($_COOKIE['WEBF-dan_num'])?0:intval($_COOKIE['WEBF-dan_num']);
	//检查drop shipping 是否近2个月满 10单
	$Arr['IsMan10'] = get_resent60_order();
    $Arr['country_list'] = area_list();
    $Arr['is_consignee'] = true;
    $Arr['consignee'] = $consignee;
    $_SESSION['flow_consignee'] = $consignee;
	$area_Arr = read_static_cache('area_key',2);
	$area_payment = $area_Arr[$country_id]["payment"];

    if (41 == $consignee['country'] && in_array($_SESSION['flow_consignee']['province'], array('PR', 'Puerto Rico'))) {//美国国家且洲为Puerto Rico,波多黎各,按波多黎各计算
        $shipping_country_id = 163;
    }

	$area_shipping = $area_Arr[isset($shipping_country_id) ? $shipping_country_id : $country_id]["shipping"];
	unset($area_Arr);
    /* 对商品信息赋值 */
	$cart_goods = cart_goods(); // 取得商品列表，计算合计,重量
    //判断购物车中是否包含电池商品
              $is_battery = 0;
        foreach($cart_goods['goods_list'] as $val){    
            if($val['cat_id'] == 500 || $val['cat_id'] == 529 || $val['cat_id'] == 531 || $val['cat_id'] == 1784 || $val['cat_id'] == 533 || $val['cat_id'] == 532 || $val['cat_id']  == 1789  || $val['cat_id']  == 1795 || $val['cat_id']  == 337  || $val['cat_id']  == 551 || $val['cat_id']  == 1832 ||$val['cat_id']  == 1833 || $val['cat_id']  == 1834 || $val['cat_id']  == 1835 || $val['cat_id']  == 1836 || $val['cat_id']  == 1837 || $val['cat_id']  == 1838 || $val['cat_id']  == 1839){
                $is_battery = 1;
                break;
            }
        }
        $Arr['is_battery'] = $is_battery;
	$free_shipping_weight = $cart_goods['total']['free_shipping_weight'];
	$shipping_weight = $cart_goods['total']['shipping_weight'];
	$free_shipping_volume_weight  = $cart_goods['total']['free_shipping_volume_weight'];
	$shipping_volume_weight  = $cart_goods['total']['shipping_volume_weight'];

    $Arr['goods_list'] = $cart_goods['goods_list'];
    $Arr['Is_Out_Sotck'] = $cart_goods['Is_Out_Sotck'];
    $Arr['Is_group_alert'] = $cart_goods['Is_group_alert'];
	$Arr['show_marketprice'] = $cart_goods['show_marketprice'];

	/* 修正重量显示 */
	$cart_goods['total']['free_shipping_weight']  = formated_weight($cart_goods['total']['free_shipping_weight']);
	/* 修正重量显示 */
	$cart_goods['total']['shipping_weight']  = formated_weight($cart_goods['total']['shipping_weight']);
	//单个订单重量大于2KG禁用免邮配送方式 by mashanling on 2012-12-15 15:32:26
	disabled_free_shipping_method($cart_goods);
	$Arr['cart_total'] = $cart_goods['total'];  //赋值格式化了重量

    /* 对是否允许修改购物车赋值 */
	$Arr['allow_edit_cart'] = 1;

    /*
     * 取得购物流程设置
     */
    $Arr['config'] = $_CFG;
    /*
     * 取得订单信息
     */
    $order = flow_order_info($country_id);
    $order['pay_id'] = 'PayPal';
    $Arr['order'] = $order;

    /*
     * 计算订单的费用
     */
    $total = order_fee($order, $cart_goods['goods_list']);

	//代金券 不使用积分
	$_SESSION['is_applay']   = empty($_SESSION['is_applay'])?'':$_SESSION['is_applay'];
    if($_SESSION['is_applay'] == ''){
		//---------------BOF:积分--------------------------------
		$point=get_point_info($_SESSION['user_id'],$total['goods_price']);
	}else{
		$point=array();
	}
	$point['min_use_point'] = $_CFG['min_use_point'];  //最小使用的积分数
	$point['point_rate'] = $_CFG['point_rate'];
	$Arr['point'] = $point;
    $Arr['total'] = $total;
    $Arr['shopping_money'] = $total['formated_goods_price'];

    /* 取得配送列表 */
    $shipping_list     = get_best_shipping($cart_goods['shipping_method']);    //最佳配送方式  by mashanling on 2011-08-23
	 if ($Arr['is_battery'] ==1) {
         unset($shipping_list[2]  );
         unset($shipping_list[3]  );
       //var_dump( $shipping_list);
    
    }
    $area_ship_arr     = explode(',',$area_shipping);
	$area_shipping_temp_Arr = array();
	foreach($area_ship_arr as $v){
		if ($v!='' && !empty($shipping_list[$v]))
		$area_shipping_temp_Arr[$v] = $shipping_list[$v];    //取得地区配送列表
	}
    
	//加工运费列表
    $area_shipping_temp_Arr     = shipping_fee_cost($area_shipping_temp_Arr,isset($shipping_country_id) ? $shipping_country_id : $country_id,$free_shipping_weight,$shipping_weight,$free_shipping_volume_weight,$shipping_volume_weight,$total);
    $cart_weight_price = cart_weight_price();
    $insure_disabled   = true;
    $cod_disabled      = true;
    $Arr['shipping_list']   = $area_shipping_temp_Arr;
    $Arr['insure_disabled'] = $insure_disabled;
	$Arr['free_shipping_weight'] = $free_shipping_weight;
	$Arr['shipping_weight']      = $shipping_weight;
    $payment_list = available_payment_list();
	$area_pay_Arr = explode(',',$area_payment);
	$area_temp_pay = array();
	foreach ($area_pay_Arr as $v){
		if ($v!='' && $payment_list[$v]['pay_code'] == 'PayPal'){
			$area_temp_pay[$payment_list[$v]['sort_order']] = $payment_list[$v];
		}
	}
    $Arr['payment_list'] = $area_temp_pay;

    //检查美国仓产品
    $goods_id_str ='';
    for( $i = 0, $n = count($cart_goods['goods_list']); $i < $n; $i ++ )
    {
        $goods_id_str.=$cart_goods['goods_list'][$i]["goods_id"].",".$cart_goods['goods_list'][$i]["rec_id"]."|";
    }
    $Arr['goods_id_str']=substr($goods_id_str,0,-1);

    //获取订单挂号费
	$guahaofei = get_pingyou_guahaofei($consignee["country"]);
	$Arr["guahaofei"] = $guahaofei;

    /* 保存 session */
    $_SESSION['flow_order'] = $order;
	$Arr["step"] = 'exp_checkout';
	$Arr["PaymentOption"] = "PayPal";
	$Arr["cart_step"] = 3;
	$Arr['seo_title'] = 'Place your order - '.$_CFG['shop_name'];
	$Arr['seo_keywords'] = 'My Cart - Shipping Method  ,'.$_CFG['shop_name'];
	$Arr['seo_description'] = 'My Cart - Shipping Method  , '.$_CFG['shop_name'];
    //地址包含apo,po box,box，干掉dhl by mashanling on 2012-10-05 13:39:38
	disabled_dhl($consignee);
}
elseif ($_ACT == 'done')
{
    /*
     * 检查用户是否已经登录
     * 如果用户已经登录了则检查是否有默认的收货地址
     * 如果没有登录则跳转到登录和注册页面
     */
    if ($_SESSION['user_id'] == 0)
    {
        /* 用户没有登录转向到登录页面 */
        header("Location:".DOMAIN_USER."/". $cur_lang_url ."m-users-a-join.htm\n");
        exit;
    }

    /*------------------------------------------------------ */
    //-- 完成所有订单操作，提交到数据库
    /*------------------------------------------------------ */
    include_once('lib/lib_clips.php');
    //include_once('includes/lib_payment.php');
    if (!empty($_SESSION['is_exp_checkout']))  ChangeSessId();  //快速付款去掉
    /* 检查购物车中是否有商品 */
	check_cart();
    $consignee = get_consignee($_SESSION['user_id']);
	if(empty($consignee['tel'])){
		$consignee['tel'] = $_REQUEST['tel'];
	}
    /* 检查收货人信息是否完整 */
    if (!check_consignee_info($consignee))
    {
        /* 如果不完整则转向到收货人信息填写界面 */
        header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-consignee.htm\n");
        exit;
    }
	$_VAL['shipping']              = !empty($_POST['shipping']) && in_array($_POST['shipping'],array('1','2','3','4'))?intval($_POST['shipping']): 1;
	$_VAL['Need_Traking_number']   = !empty($_POST['Need_Traking_number'])&&intval($_VAL['shipping'])==1?get_pingyou_guahaofei($_SESSION['flow_order']['country']) : 0.00;
    $_VAL['postscript'] = isset($_POST['postscript']) ? htmlspecialchars($_POST['postscript']) : '';
    $_VAL['payment']    = isset($_POST['payment']) ? htmlspecialchars($_POST['payment']) : 'PayPal';
    $_VAL['insurance']  = !empty($_POST['insurance'])?floatval($_POST['insurance']) : 0.00;
    $_VAL['Invoice']  = !empty($_POST['Invoice'])?floatval($_POST['Invoice']) : 0; //发票   0 为不需要，1为需要发票
    $_VAL['used_point'] = empty($_POST['point'])?0:intval($_POST['point']);	  //本单使用的积分数
    $order = array(
        'shipping_id'     => intval($_VAL['shipping']),  //整型
        'pay_id'          => $_VAL['payment'],           //字符型
        'postscript'      => trim($_VAL['postscript']),
        'user_id'         => $_SESSION['user_id'],
        'add_time'        => gmtime(),
        'order_status'    => 0,
        'insure_fee'      => $_VAL['insurance'],
        'Invoice'         => $_VAL['Invoice'],

        );

    /* 检查积分余额是否合法 */
    $user_id = $_SESSION['user_id'];

    /* 订单中的商品 */
    $cart_goods = cart_goods();
    if($_VAL['used_point']>0){
    	//$total=order_fee($order, $cart_goods['goods_list'],$_VAL['insurance']);
		$point=get_point_info($_SESSION['user_id'],1000000);  //获取积分信息
		if($_VAL['used_point']>$point['use_point_max']){
			$_VAL['used_point']=$point['use_point_max'];
		}
		$_VAL['point_money']=calculate_point_money($_VAL['used_point']);
		if($_VAL['point_money']<=0)$_VAL['used_point']=0;
    }
    else{
    	$_VAL['point_money']=0;
    	$_VAL['used_point']=0;
    }
    $order['used_point']=$_VAL['used_point'];
    $order['point_money']=$_VAL['point_money'];
	if ($cart_goods['issong']['enable']) { //判断是否存在价格为0.01的商品
       $issong_id =  $cart_goods['issong']['goods_id'];
	   $rec_id    =  $cart_goods['issong']['rec_id'];

	   $sql = "select g.goods_name from ".ODRGOODS." as g  left join ".ORDERINFO." as o on o.order_id = g.order_id where g.goods_price = '0.01' AND DATEDIFF(curdate(),FROM_UNIXTIME(o.add_time,'%Y-%m-%d')) < 14
	   and o.user_id = '".$_SESSION['user_id']."'  ";
	   $songArr = $db->selectinfo($sql);
	   if (!empty($songArr['goods_name'])){
		   if(count($cart_goods['goods_list'])>1){
			   $db->query("delete from ".CART." where rec_id = '".$rec_id."'");
			   unset($cart_goods);
			   unset($rec_id);
			   $cart_goods = cart_goods();
		   }else{
			   show_message($_LANG['product'] . ' :' . $songArr['goods_name'] . $_LANG['is_limited_by_one'], '', $_SERVER['HTTP_REFERER'], 'warning');
		   }
		}
	}

    if (empty($cart_goods['goods_list']))
    {
        show_message($_LANG['no_goods_in_cart'], $_LANG['back_home'], './', 'warning');
    }

    /* 收货人信息 */
    foreach ($consignee as $key => $value)
    {
        $order[$key] = addslashes($value);
    }
    $order['consignee']  =  $order['firstname'] .' '. $order['lastname'];
    $order['address1']   = addslashes($order['addressline1']);
    $order['address2']   =  addslashes($order['addressline2']);
    $order['address']    =  $order['address1'] . ' ' . $order['address2'];
	$order_info['firstname'] = empty($_SESSION['firstname'])?$order['firstname']:$_SESSION['firstname'];;
	unset($order['firstname']);
	unset($order['lastname']);
	unset($order['addressline1']);
	unset($order['addressline2']);

    /* 订单中的总额 */
    $total = order_fee($order, $cart_goods['goods_list'],$_VAL['insurance'],$_VAL['point_money']);
    $order['goods_amount']      = $total['goods_price'];
    $order['yuan_goods_amount'] = $total['yuan_goods_amount'];
    $order['tax']          = $total['tax'];
	//$order['Need_Traking_number'] = ($total['goods_price']>=40 && $_VAL['Need_Traking_number'] > 0) ? 1.99 : $_VAL['Need_Traking_number'];
	$order['Need_Traking_number'] = $_VAL['Need_Traking_number'];
	$order['point_money'] = $_VAL['point_money'];
	$Arr['discount']  = $order['yuan_goods_amount'] - $order['goods_amount'] +$order['point_money'] ;
	$linkshare = linkshare($cart_goods['goods_list']);//linkshare 推广代码
	$Arr['linkshare'] = $linkshare;
    /* 配送方式 */
    if ($order['shipping_id'] > 0)
    {
        $shipping = available_shipping_list();
        foreach ($shipping as $k => $v)
        {
            if($v['id']==$order['shipping_id'])
            {
               $shipping_name = $v['ship_name'];
               break;
            }
        }
        $order['shipping_name'] = addslashes($shipping_name);
    }

    $order['shipping_fee'] = $total['shipping_fee'];
    $order['free_shipping_fee'] = $total['free_shipping_fee'];

    /* 支付方式 */
    if ($order['pay_id'] !='')
    {
        $payment = payment_info($order['pay_id']);
        $order['pay_name'] = addslashes($payment['pay_name']);
    }
	$_SESSION['pcode_lv']      = empty($_SESSION['pcode_lv'])?0:$_SESSION['pcode_lv'];
	$_SESSION['total_price']   = empty($_SESSION['total_price'])?0:$_SESSION['total_price'];
	$_SESSION['pcode_code']    = empty($_SESSION['pcode_code'])?'':$_SESSION['pcode_code'];
	$_SESSION['pcode_fangshi'] = empty($_SESSION['pcode_fangshi'])?'':$_SESSION['pcode_fangshi'];
	$_SESSION['pcode_goods']   = empty($_SESSION['pcode_goods'])?'':$_SESSION['pcode_goods'];
	$_SESSION['is_applay']   = empty($_SESSION['is_applay'])?'':$_SESSION['is_applay'];
	if ($_SESSION['pcode_code']){ //促销码！
		$order['promotion_code_youhuilv'] = get_code_str($_SESSION['pcode_lv'],$_SESSION['pcode_fangshi']);
		$order['promotion_code'] = $_SESSION['pcode_code'];
		$sql = "update ".PCODE." SET cishu = cishu + 1 where code ='".$_SESSION['pcode_code']."'";
		$db->query($sql);
	}

    $order['order_amount']  = number_format($total['amount'], 2, '.', '');
    $order['referer']          = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';
	$Arr['total']  = $order['order_amount'];
	$order['insure_fee']     = ($order['insure_fee']>0)?$total['insure_fee']:$order['insure_fee'];

	//广告联盟的推广ID
	$order['wj_linkid']  = empty($_COOKIE['linkid'])?0:intval($_COOKIE['linkid']);
	$order['wj_referer'] = !empty($_COOKIE['wj_referer']) ? substr($_COOKIE['wj_referer'],0,255) : '';
	if ($order['wj_linkid']){
		$sql = "select user_id from ".WJ_LINK." where id = '".$order['wj_linkid']."'";
		if(empty($_SESSION['pcode_code'])&&$db->getOne($sql) != $_SESSION['user_id']){ //不是自己,并且没有使用促销码才可以加一
			$sql="update ".WJ_LINK." set order_count=order_count+1 where id='".$order['wj_linkid']."'";
			$db->query($sql);
		}else{
			$order['wj_linkid'] = 0;
		}

	}
	$order['pay_ip'] = real_ip();

	////////////////////////////记录多币种支付开始//////////////////////////////
	$order = array_merge($order, get_currency_rate($order['pay_id'], $_COOKIE['bizhong']));
	////////////////////////////记录多币种支付结束//////////////////////////////

    /* 插入订单表 */
    $error_no = 0;
    do
    {
		$sn_qian = !empty($_SESSION['is_exp_checkout'])?'D':'';
        $order['order_sn'] = get_order_sn($sn_qian); //获取新订单号
		// 订单语言 fangxin 2013/07/22
		if(!empty($cur_lang)) {
			$order['lang'] = $cur_lang;
		} else {
			$order['lang'] = 'en';
		}
        $GLOBALS['db']->autoExecute(ORDERINFO, $order, 'INSERT');
        $new_order_id = $db->insertId();
        $error_no = $GLOBALS['db']->Errno;
        if ($error_no > 0 && $error_no != 1062)
        {
            die($GLOBALS['db']->errorMsg());
        }
		update_user_lang($_SESSION['email'], $cur_lang); // 更新用户语言 fangxin 2013/07/23
    }
    while ($error_no == 1062); //如果是订单号重复则重新提交数据

	//判断订单存不存在。
    $osqls = "select count(*) from eload_order_info where order_sn = '".$order['order_sn']."'";
	if (!$db->getOne($osqls)){
	   $GLOBALS['db']->autoExecute(ORDERINFO, $order, 'INSERT');
	}

    $order['order_id'] = $new_order_id;
    $gmtime = gmtime();
    /* 插入订单商品 */
    $sql = "INSERT INTO " . ODRGOODS . "( " .
                "order_id, goods_id,main_goods_id, goods_name, goods_sn, goods_number, market_price, ".
                "goods_price, goods_attr, goods_off,lmt_num,addtime,custom_size,attr_goods_sn,is_groupbuy,goods_type) ".
            " SELECT '$new_order_id', c.goods_id,c.main_goods_id,c.goods_name, c.goods_sn, c.goods_number, c.market_price, ".
                "c.goods_price, c.goods_attr, c.goods_off,c.lmt_num,c.addtime,c.custom_size,c.attr_goods_sn,c.is_groupbuy,g.groupbuy_start_date<{$gmtime} AND g.groupbuy_end_date>{$gmtime} || g.promote_start_date<{$gmtime} AND g.promote_end_date>{$gmtime} ".
            " FROM " .CART ." as c,".GOODS." as g".
            " WHERE c.goods_id = g.goods_id and  c.session_id = '".SESS_ID."'";
    $db->query($sql);

    //更新计算订单商品的运费占比
    update_order_goods_shipping_fee($order,$cart_goods);

	//取消下单成功后发送邮件 fangxin 2014-02-19
	//发送订单邮件
	//order_email_send($order);
	reduce_stock($order['order_sn']); //减库存
	$note = "Used on ".$order['order_sn'];

	//不是代金券才可以使用积分
	if($_SESSION['is_applay']==''){
		if ($_VAL['used_point']>0)add_point($_SESSION['user_id'],-$_VAL['used_point'],2,$note);//使用积分
	}
    /* 如果使用库存，且下订单时减库存，则减少库存 */
    if ($_CFG['use_storage'] == '1')
    {
        change_order_goods_storage($order['order_id']);
    }

    /* 给商家发邮件 */
    /* 增加是否给客服发送邮件选项 */
	$order_info['order_no']  = $order['order_sn'];
	$order_info['order_id']  = $order['order_id'];
	$email    = empty($_SESSION['email'])?'':$_SESSION['email'];

    /* 清空购物车 */
    $sql = "select count(*) from eload_order_goods where order_id = '".$order['order_id']."'";
    $osqls = "select count(*) from eload_order_info where order_sn = '".$order['order_sn']."'";
	if ($db->getOne($sql) && $db->getOne($osqls)){
		clear_cart();

	}

    /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
    /* 取得支付信息，生成支付代码 */
	$turn_payment = '';
    if ($order['order_amount'] > 0)
    {
		$payment = payment_info($order['pay_id']);
		if ($payment['pay_code'] == 'CreditCard') $payment['pay_code'] = 'PayPal';  //如果是信用卡就导向 PayPal
		if (empty($payment['pay_code'])) $payment['pay_code'] = 'PayPal';
		include_once('lib/modules/payment/' . $payment['pay_code'] . '.php');
		$pay_obj    = new $payment['pay_code'];
		$pay_online = $pay_obj->get_code($order);
		if ($payment['pay_code'] == 'PayPal' || $payment['pay_code']=='GoogleCheckout'  || $payment['pay_code']=='CreditCard'  || $payment['pay_code']=='webcollect' || $payment['pay_code']=='webmoney'|| $payment['pay_code']=='DirectDebit'|| $payment['pay_code']=='BankTransfer'|| $payment['pay_code']=='boletoBancario'){
			$Arr['pay_online_desc'] = $pay_online;
			$turn_payment = 'turn_payment';
			$_SESSION['turn_order'] = $order;
			$Arr['pay_name'] = $payment['pay_name'];
		}else{
			$order['pay_desc'] = ($_LANG['paypal_desc']=='')?$payment['pay_desc']:$_LANG['paypal_desc'];
			$Arr['pay_online'] = $pay_online;
		}
    }
	unset($_SESSION['pcode_lv']);
	unset($_SESSION['pcode_code']);
	unset($_SESSION['pcode']);
	unset($_SESSION['pcode_goods']);
	unset($_SESSION['pcode_fangshi']);
	unset($_SESSION['total_price']);
	unset($_SESSION['is_applay']);
	$PaymentOption = empty($_GET['PaymentOption'])?'':$_GET['PaymentOption'];
    $token = isset($_SESSION['TOKEN'])?$_SESSION['TOKEN']:'';
	if ($PaymentOption == 'PayPal'){
		global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
		global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
		global $gv_ApiErrorURL;
		global $sBNCode;
		require_once ("paypalfunctions.php");
		if ( $PaymentOption == "PayPal" )  //快速付款
		{
            get_google_conversion_order_goods($order);//电子商务统计商品 by mashanling on 2013-10-07 16:36:03

			$finalPaymentAmount =  $order['order_amount'] ;
			$_SESSION["Payment_Amount"]  = $finalPaymentAmount ;
			$_SESSION["orderno"]  = $order_info['order_no'] ;

			$resArray = ConfirmPayment ( $finalPaymentAmount );
			$ack = strtoupper($resArray["ACK"]);
			if( $ack == "SUCCESS" )
			{
				//$transactionId		= $resArray["TRANSACTIONID"];
				//$transactionType 	= $resArray["TRANSACTIONTYPE"];
				//$paymentType		= $resArray["PAYMENTTYPE"];
				//$orderTime 			= $resArray["ORDERTIME"];
				//$amt				= $resArray["AMT"];
				//$currencyCode		= $resArray["CURRENCYCODE"];
				//$feeAmt				= $resArray["FEEAMT"];
				//$settleAmt			= $resArray["SETTLEAMT"];
				//$taxAmt				= $resArray["TAXAMT"];
				//$exchangeRate		= $resArray["EXCHANGERATE"];
				//$paymentStatus    	= $resArray["PAYMENTSTATUS"];
				//$pendingReason	    = $resArray["PENDINGREASON"];
				//$reasonCode		    = $resArray["REASONCODE"];
				$sql = " order_status = '1' ,pay_time = '".gmtime()."' ";
				$order['order_status'] =1;
				$db->update(ORDERINFO,$sql," order_sn = '".$order_info['order_no']."'");
				//修改付款成功后的发邮件
				send_email_order_status_1($order_info['order_id'], $order_info['user_id']);
				//send_email($email, 17, $order_info);  //发送已收到款邮件
			    $order['pay_desc'] = (!empty($_LANG['paid_desc_tips'])?$_LANG['paid_desc_tips']:'').'<a href="'.DOMAIN_USER.'/'.$cur_lang_url.'m-users-a-order_list.htm">'.$_LANG['my_account'].'</a><br />
';
			}
			else
			{
                //付款被拒绝，返回paypal，换卡重新支付， 第一次重定向
                if( urldecode($resArray["L_ERRORCODE0"])=='10486' )
                {
                    $token = $_SESSION['TOKEN'];
                    $_SESSION['paypal_10486']='yes';
                    $_SESSION['count_redirect']=1;

                    Logger::filename('paypal_10486');
                    trigger_error($order['order_sn'] . ", token: {$token} ,count_redirect: 1");

                    header("Location: https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=".$token);
                    exit();
                }
                if($_SESSION['paypal_10486']=='yes')unset($_SESSION['paypal_10486']);
                if($_SESSION['count_redirect']>0)unset($_SESSION['count_redirect']);

				//Display a user friendly Error on the page using any of the following error information returned by PayPal
				$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
				$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
				$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
				$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
				$order['pay_desc'] = "GetExpressCheckoutDetails API call failed. ".
								    "<br>Detailed Error Message: " . $ErrorLongMsg.
									"<br>Short Error Message: " . $ErrorShortMsg.
									"<br>Error Code: " . $ErrorCode.
									"<br>Error Severity Code: " . $ErrorSeverityCode;
			}
            $Arr['pay_online'] = '';
		}
		unset($_SESSION['is_exp_checkout']);
		$turn_payment = '';
	}

    /* 订单信息 */
	$sql = "select g.goods_title,g.goods_name,c.cat_id,cat_name,og.* from eload_order_goods og,eload_goods g,eload_category c where og.goods_id  = g.goods_id and g.cat_id = c.cat_id and order_id = '".$order_info['order_id']."'";
	$goods_list = $db->arrQuery($sql);
	foreach($goods_list as &$val){
			 $val['goods_name'] = addslashes($val['goods_name']);
			 $val['goods_title'] = addslashes($val['goods_title']);
			 $val['cat_name'] = addslashes( $val['cat_name']);
	}
	$order['goods_list'] = $goods_list;
	$order['discount']   = $Arr['discount'];
    $Arr['order'] =  $order;
    $Arr['step']  =  'done';
    $Arr['total'] =  $total;
	$Arr['cart_step']  =  4;
    $Arr['goods_list'] =  $cart_goods;
    $Arr['order_submit_back'] = sprintf($_LANG['order_submit_back'], $_LANG['back_home'], $_LANG['goto_user_center']); // 返回提示
	$Arr['seo_title'] = 'My Cart - Payment and Confirmation - '.$_CFG['shop_name'];
	$Arr['seo_keywords'] = 'My Cart - Payment and Confirmation  ,'.$_CFG['shop_name'];
	$Arr['seo_description'] = 'My Cart - Payment and Confirmation  , '.$_CFG['shop_name'];
    unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
    unset($_SESSION['flow_order']);
    unset($_SESSION['direct_shopping']);

	//判断订单存不存在。
    $osqls = "select count(*) from eload_order_info where order_sn = '".$order['order_sn']."'";
	if (!$db->getOne($osqls)){
	   show_message($_LANG['sorry_occurred_just'], '', $_SERVER['HTTP_REFERER'], 'warning');
	}

	//统计下单情况，以便作监控
    $logfile = 'order';
    $num     = Logger::get($logfile);
    Logger::put($logfile, intval($num) + 1);

	if ($turn_payment && $order['order_amount']){
			//判断订单金额
			$Arr['amount'] = $order['order_amount'];
			if ($Arr['amount']<1){
				header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-turn1.htm\n");
			}else if($Arr['amount']<10 && $Arr['amount']>=1){
				header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-turn2.htm\n");
			}else if($Arr['amount']<40 && $Arr['amount']>=10){
				header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-turn3.htm\n");
			}else if($Arr['amount']<80 && $Arr['amount']>=40){
				header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-turn4.htm\n");
			}else if($Arr['amount']<200 && $Arr['amount']>=80){
				header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-turn5.htm\n");
			}else if($Arr['amount']>=200){
				header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-turn6.htm\n");
            }
			exit;
	}
	//谷歌再营销
	$exchange = read_static_cache('exchange',2);
	$currency = get_currency();
	if(1== count($goods_list)) {
		$prodid = "'". $goods_list[0]['goods_sn'] . $currency['lang'] ."'";
	} else {
		if($goods_list) {
			$prodid = "[";
			foreach($goods_list as $key=>$goods_list_value) {
				$prodid .= "'". $goods_list_value['goods_sn'] . $currency['lang'] . "'" . ",";
			}
			$prodid = substr($prodid,0,strlen($prodid)-1);
			$prodid .= "]";
		}
	}
	$google_tag_params = array(
		'prodid' => $prodid,
		'pagetype' => "'purchase'",
		'totalvalue' => round($total['amount_formated']*$exchange['Rate'][''.$currency['currency'].''],2),
		'currency' => "'". $currency['currency'] ."'",
		'pcat' => "''"
	);
	$Arr['google_tag_params'] = $google_tag_params;
}
elseif ($_ACT == 'turn1' || $_ACT == 'turn2' || $_ACT == 'turn3' || $_ACT == 'turn4' || $_ACT == 'turn5' || $_ACT == 'turn6')
{
	$order = empty($_SESSION['turn_order'])?array('order_amount'=>0):$_SESSION['turn_order'];
	unset($_SESSION['turn_order']);
    /* 取得支付信息，生成支付代码 */
    if ($order['order_amount'] > 0)
    {
		$payment = payment_info($order['pay_id']);
		get_google_conversion_order_goods($order);//电子商务统计商品 by mashanling on 2013-10-07 16:36:03
		$area_Arr = read_static_cache('area_key',2);
		$order['country_id'] = $order['country'];
		$order['country'] = $area_Arr[$order['country']]['region_name'];
		$Arr['order'] = $order;
		if ($payment['pay_code'] == 'CreditCard') $payment['pay_code'] = 'PayPal';  //如果是信用卡就导向 PayPal
		include_once('lib/modules/payment/' . $payment['pay_code'] . '.php');
		$pay_obj    = new $payment['pay_code'];
		$pay_online = $pay_obj->get_code($order);
		if ($payment['pay_code'] == 'PayPal' || $payment['pay_code']=='GoogleCheckout'  || $payment['pay_code']=='CreditCard' || $payment['pay_code']=='webcollect'|| $payment['pay_code']=='webmoney'|| $payment['pay_code']=='DirectDebit'|| $payment['pay_code']=='BankTransfer'|| $payment['pay_code']=='boletoBancario')
		{
			$Arr['pay_online_desc'] = $pay_online;
			$turn_payment = 'turn_payment';
			$Arr['pay_name'] = $payment['pay_name'];
		}else{
			$order['pay_desc'] = ($_LANG['paypal_desc']=='')?$payment['pay_desc']:$_LANG['paypal_desc'];
			$Arr['pay_online'] = $pay_online;
		}
    }
	$Arr['google_tj'] = 'yes';
	unset($order);
}
elseif ($_ACT == 'update_cart')
{
    /*------------------------------------------------------ */
    //-- 更新购物车
    /*------------------------------------------------------ */
    $_VAL['goods_number'] = empty($_POST['goods_number'])?0:intval($_POST['goods_number']);
	$_VAL['rid'] = empty($_POST['rid'])?0:intval($_POST['rid']);
    if ($_VAL['goods_number'] <= 0) $_VAL['goods_number'] =1;
	flow_update_cart($_VAL);
	echo 'Updated';
    exit;
}

elseif ($_ACT == 'apply_code')
{
	$pcodeArr = array();
    $_VAL['promotion_code'] = isset($_GET['pcode']) ? htmlspecialchars(trim($_GET['pcode'])) : '';
	if ($_VAL['promotion_code']!=''){
		$sql = "select youhuilv,code,exp_time from ".PCODE." where code = '".$_VAL['promotion_code']."'";
		$pcodeArr = $db->selectinfo($sql);
		if (empty($pcodeArr))
		{
			$pcodeArr['msg'] = $_LANG['code_not_exist'];
			$pcodeArr['done'] = false;
        }else{
			if($pcodeArr['exp_time'] < gmtime()){
				$pcodeArr['msg'] = 'Error,this promotion code has expired.';
				$pcodeArr['done'] = false;
			}else{
				$pcodeArr['msg'] = 'Congratulations, '.$pcodeArr['youhuilv'].'% off applied successfully.';
				$pcodeArr['done'] = true;
			}
		}
	}
	 echo json_encode($pcodeArr);
	 exit;
}

elseif ($_ACT == 'fails')
{
	$Arr['seo_title'] = 'Failure to pay - '.$_CFG['shop_name'];
	$Arr['seo_keywords'] = 'Failure to pay  ,'.$_CFG['shop_name'];
	$Arr['seo_description'] = 'Failure to pay  , '.$_CFG['shop_name'];
	if (!empty($_SESSION["Payment_Amount"])){unset($_SESSION["Payment_Amount"]);}
	if (!empty($_SESSION["orderno"])){unset($_SESSION["orderno"]);}
    show_message($_LANG['failure_to_pay'], 'Go go homepage', './', 'warning');
}


elseif ($_ACT == 'payok')
{
	$pay_msg   = 'Payment successful';
	$pay_code  = empty($_GET['code'])?'':trim($_GET['code']);
	if($pay_code!=''){
		include_once('lib/modules/payment/' . $pay_code . '.php');
		$pay_obj    = new $pay_code;
		$pay_msg = $pay_obj->respond();
	}
	$oid      = empty($_GET['oid'])?'':$_GET['oid'];
	$order_id = empty($_GET['order_id'])?'':$_GET['order_id'];
	if (!empty($oid)){
		$sql = "select order_id,order_amount from ".ORDERINFO." where order_status > 0 and order_sn = '".$oid."' ";
		$orderinfo= $db->selectInfo($sql);
		$Arr['amount'] = $orderinfo['order_amount'];
		if($Arr['amount']>0){
			$Arr['order_sn'] = $oid;
			$_SESSION["amount"]   = $Arr['amount'];
			$_SESSION["order_sn"] = $oid;
			//判断订单金额
			if ($Arr['amount']<1){
				header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-payok1.htm\n");
			}else if($Arr['amount']<10 && $Arr['amount']>=1){
				header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-payok2.htm\n");
			}else if($Arr['amount']<40 && $Arr['amount']>=10){
				header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-payok3.htm\n");
			}else if($Arr['amount']<80 && $Arr['amount']>=40){
				header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-payok4.htm\n");
			}else if($Arr['amount']<200 && $Arr['amount']>=80){
				header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-payok5.htm\n");
			}else if($Arr['amount']<500 && $Arr['amount']>=200){
				header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-payok6.htm\n");
			}else if($Arr['amount']>=500){
				header("Location: ".DOMAIN_CART."/". $cur_lang_url ."m-flow-a-payok7.htm\n");
            }
			exit;
		}
	}
	$Arr['seo_title'] = 'Pay information - '.$_CFG['shop_name'];
	$Arr['seo_keywords']   = 'Pay information , '.$_CFG['shop_name'];
	$Arr['seo_description']   = 'Pay information , '.$_CFG['shop_name'];
	$Arr['order_id']   = $order_id;
	if(!empty($_GET['type'])&&$_GET['type']=='banktransfer'){
		show_message($pay_msg, $_LANG['back_home'], './','banktransfer');
	}else{
		show_message($pay_msg, $_LANG['back_home'], './');
	}
}
//根据定单金额返回页面
elseif ($_ACT == 'payok1' || $_ACT == 'payok2' || $_ACT == 'payok3' || $_ACT == 'payok4' || $_ACT == 'payok5' || $_ACT == 'payok6' || $_ACT == 'payok7')
{
	$pay_msg = 'Payment successful';
	$Arr['seo_title'] = 'Pay information - '.$_CFG['shop_name'];
	$Arr['seo_keywords'] = 'Pay information , '.$_CFG['shop_name'];
	$Arr['seo_description'] = 'Pay information , '.$_CFG['shop_name'];
	$Arr['amount'] = $_SESSION["amount"];
	$Arr['order_sn'] = $_SESSION["order_sn"];
	$_SESSION["amount"]   = '';
	$_SESSION["order_sn"] = '';
	unset($_SESSION["amount"]);
	unset($_SESSION["order_sn"]);

    /* 订单商品 */
	if(!empty($Arr['order_sn'])){
		$sql="SELECT order_id FROM ".ORDERINFO." WHERE order_sn='".$Arr['order_sn']."'";
	    $order_id=$db->getOne($sql);
	    $goods_list = order_goods($order_id);

		if($cur_lang != $default_lang){
			$cat_arr =  read_static_cache($cur_lang.'_category_c_key',2);
		}else {
			$cat_arr =  read_static_cache('category_c_key',2);
		}
	    foreach ($goods_list as $k=>&$v){
	    	$v['cat_name']  = !empty($cat_arr[$v['cat_id']])?$cat_arr[$v['cat_id']]['cat_name']:'';
	    }
	    $i=0;
	    $Arr['linkshare'] = linkshare($goods_list);
	   	$order = get_order_detail($order_id);
	   	$order['goods_list'] = $goods_list;
	   	$order['discount']   = $order['yuan_goods_amount'] - $order['goods_amount'] +$order['point_money'] ;
	   	$Arr['order']        = $order;
	   	$Arr['order_status'] = $order['order_status'] ;
	}
	show_message($pay_msg, $_LANG['back_home'], './','success');
}


//删除购物车中的商品
elseif ($_ACT == 'drop_goods')
{
    $rec_id = intval($_GET['id']);
    flow_drop_cart_goods($rec_id);
	echo 'Deleted';
    exit;
}

/* 把优惠活动加入购物车 */
elseif ($_ACT == 'add_favourable'){
    /* 取得优惠活动信息 */
    $act_id = intval($_POST['act_id']);
    $favourable = favourable_info($act_id);
    if (empty($favourable))
    {
        show_message($_LANG['favourable_not_exist']);
    }

    /* 判断用户能否享受该优惠 */
    if (!favourable_available($favourable))
    {
        show_message($_LANG['favourable_not_available']);
    }

    /* 检查购物车中是否已有该优惠 */
    $cart_favourable = cart_favourable();
    if (favourable_used($favourable, $cart_favourable))
    {
        show_message($_LANG['favourable_used']);
    }

    /* 赠品（特惠品）优惠 */
    if ($favourable['act_type'] == FAT_GOODS)
    {
        /* 检查是否选择了赠品 */
        if (empty($_POST['gift']))
        {
            show_message($_LANG['pls_select_gift']);
        }

        /* 检查是否已在购物车 */
        $sql = "SELECT goods_title" .
                " FROM " . CART .
                " WHERE session_id = '" . SESS_ID . "'" .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
                " AND is_gift = '$act_id'" .
                " AND goods_id " . db_create_in($_POST['gift']);
        $gift_name = $db->getCol($sql);
        if (!empty($gift_name))
        {
            show_message(sprintf($_LANG['gift_in_cart'], join(',', $gift_name)));
        }

        /* 检查数量是否超过上限 */
        $count = isset($cart_favourable[$act_id]) ? $cart_favourable[$act_id] : 0;
        if ($favourable['act_type_ext'] > 0 && $count + count($_POST['gift']) > $favourable['act_type_ext'])
        {
            show_message($_LANG['gift_count_exceed']);
        }

        /* 添加赠品到购物车 */
        foreach ($favourable['gift'] as $gift)
        {
            if (in_array($gift['id'], $_POST['gift']))
            {
                add_gift_to_cart($act_id, $gift['id'], $gift['price']);
            }
        }
    }
    elseif ($favourable['act_type'] == FAT_DISCOUNT)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], cart_favourable_amount($favourable) * (100 - $favourable['act_type_ext']) / 100);
    }
    elseif ($favourable['act_type'] == FAT_PRICE)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], $favourable['act_type_ext']);
    }
    /* 刷新购物车 */
    header("Location: flow.php\n");
    exit;
}

elseif ($_ACT == 'clear'){
    $sql = "DELETE FROM " . CART . " WHERE session_id='" . SESS_ID . "'";
    $db->query($sql);
    header("Location:./\n");
}

elseif ($_ACT == 'drop_to_collect')
{
    if ($_SESSION['user_id'] > 0)
    {
        $goods_id = intval($_GET['id']);
        $count = $db->getOne("SELECT goods_id FROM " . COLLECT . " WHERE user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id'");
        if (empty($count))
        {
            $time = gmtime();
            $sql = "INSERT INTO " .COLLECT. " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";
            $db->query($sql);
        }
		header("Location:".DOMAIN_USER."/". $cur_lang_url ."m-users-a-collection_list.htm\n");
		exit;
    }else{
		 header("Location:".DOMAIN_USER."/". $cur_lang_url ."m-users-a-join.htm\n");
	}
    exit;
}

//清coupon
elseif ($_ACT == 'clearCoupon') {
	clearCoupon();
	header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-cart.htm\n");
	exit();
}

//清赠品
elseif ($_ACT == 'cleargift') {
	$db->delete(CART,"gifts_id>0 and session_id='".SESS_ID."'");
	header("Location: ".DOMAIN_CART."/$cur_lang_url"."m-flow-a-cart.htm\n");
	exit();
}

//购物车
elseif ($_ACT == 'cart') {
	remove_over_gift(); //检查赠品
    $cart_goods             = get_cart_goods();    //商品信息
//判断购物车中是否包含电池商品
        $is_battery = 0;
        foreach($cart_goods['goods_list'] as $val){    
            if($val['cat_id'] == 500 || $val['cat_id'] == 529 || $val['cat_id'] == 531 || $val['cat_id'] == 1784 || $val['cat_id'] == 533 || $val['cat_id'] == 532 || $val['cat_id']  == 1789  || $val['cat_id']  == 1795 || $val['cat_id']  == 337  || $val['cat_id']  == 551 || $val['cat_id']  == 1832 ||$val['cat_id']  == 1833 || $val['cat_id']  == 1834 || $val['cat_id']  == 1835 || $val['cat_id']  == 1836 || $val['cat_id']  == 1837 || $val['cat_id']  == 1838 || $val['cat_id']  == 1839){
                $is_battery = 1;
                break;
            }
        }
        $Arr['is_battery'] = $is_battery;
	if(!empty($_GET['pcode'])) $cart_goods             = get_cart_goods();    //商品信息
	$gifts_in_cart = gifts_in_cart($cart_goods['goods_list']);
	$normal_goods_amount = get_cart_available_amount($cart_goods['goods_list']);
    $Arr['normal_goods_amount']  = $normal_goods_amount;
    $gift_arr_available ='';
    if($normal_goods_amount>0)
		$gift_arr_available =get_gifts_list_cart($cart_goods['goods_list'],$normal_goods_amount,5);  //满足条件的可选产品
	$gift_arr_recommend = '';
	$min_gifts_need_money = $db->getOne("select need_money from ".GIFTS." f inner join ".GOODS." g on f.gifts_id = g.gifts_id where is_on_sale =1 and goods_number>0 order by need_money asc limit 1");
	$Arr['min_gifts_need_money'] = $min_gifts_need_money;
	if(empty($gift_arr_available)){
		$Arr['gift_lack_of_money'] = $min_gifts_need_money -$normal_goods_amount>0?$min_gifts_need_money -$normal_goods_amount:'0';
		$gift_arr_recommend = get_gifts_list_cart($cart_goods['goods_list'],$min_gifts_need_money,5);  //如果金额不够就取推荐的赠品
	}
	$Arr['gift_arr_recommend'] = $gift_arr_recommend;
	$Arr['gift_arr_available'] = $gift_arr_available;
    $Arr['goods_list']      = $cart_goods['goods_list'];    //商品列表
    $item_detail_count =0; //产品个数
    $i=0;
    $vizury_st = ''; //vizury 统计用
    foreach ($cart_goods['goods_list'] as $v){
    	$item_detail_count  += $v['goods_number'];
    	if($i<3){ //vizury 统计用
    		$i++;
    		$top_cat_id = get_category_top_parent_id($v['cat_id']);
    		$vizury_st .="&pid$i=$v[goods_id]&catid$i=$top_cat_id&quantity$i=$v[goods_number]&price$i=$v[goods_price]";
    	}
    }
    $Arr['vizury_st']= $vizury_st;//vizury 统计用
    $Arr['items_count']     = count($cart_goods['goods_list']);    //商品种类
    $Arr['item_detail_count'] = $item_detail_count;    //产品个数
    if(empty($Arr['items_count'])){
		$Arr['best_goods'] = get_cache_best_goods(10);
	}
    $goods_price             = $cart_goods['total']['goods_price'];    //商品价格
    $Arr['is_include_gifts'] = $gifts_in_cart;
    $Arr['items_total']      = $goods_price;
    $Arr['shopping_money']   = $goods_price;
	$_SESSION['Payment_Amount'] = $goods_price;
    $Arr['total']            = $cart_goods['total'];
    $Arr['show_marketprice'] = $cart_goods['show_marketprice'];
	$Arr['step']             = $_ACT;
	$Arr['seo_title']       = 'My Cart - '.$_CFG['shop_name'];
	$Arr['seo_keywords']         = 'My Cart ,'.$_CFG['shop_name'];
	$Arr['seo_description']         = 'My Cart , '.$_CFG['shop_name'];
	$Arr['cart_step']        = 1;
    if ($_SESSION['user_id'] > 0){    //显示收藏夹内的商品
        require_once(ROOT_PATH . 'lib/lib_clips.php');
        $collection_goods = get_collection_goods($_SESSION['user_id']);
        $Arr['collection_goods'] = $collection_goods;
    }

    //估算运费
    /* 修正重量显示 */
	$cart_goods['total']['free_shipping_weight']  = formated_weight($cart_goods['total']['free_shipping_weight']);
	$cart_goods['total']['shipping_weight']  = formated_weight($cart_goods['total']['shipping_weight']);
	$cart_goods['total']['free_shipping_volume_weight']  = formated_weight($cart_goods['total']['free_shipping_volume_weight']);
	$cart_goods['total']['shipping_volume_weight']  = formated_weight($cart_goods['total']['shipping_volume_weight']);
    $free_shipping_weight = $cart_goods['total']['free_shipping_weight'];	//免邮费重量合计
	$shipping_weight = $cart_goods['total']['shipping_weight'];		//重量合计
	$free_shipping_volume_weight = $cart_goods['total']['free_shipping_volume_weight'];		//免邮费体积重量合计
	$shipping_volume_weight = $cart_goods['total']['shipping_volume_weight'];		//体积重量合计
    $country_list    =  area_list();
	$country_shipping_lang = read_static_cache('lang_country',2);
	$_GET["country"] = empty($_GET["country"])?(($cur_lang != $default_lang && !empty($country_shipping_lang[$cur_lang])) ? $country_shipping_lang[$cur_lang] : 41) :intval($_GET["country"]);
	$shipping_list     = get_best_shipping($cart_goods['shipping_method']);
    $shipping_list     = shipping_fee_cost($shipping_list,$_GET['country'],$free_shipping_weight,$shipping_weight,$free_shipping_volume_weight,$shipping_volume_weight,$cart_goods['total']['goods_price']);
	$Arr['country_list'] = $country_list;
	$Arr['country_post_id'] = $_GET['country'];
	$Arr['shipping_list'] = $shipping_list;
    if(isset($_SESSION['paypal_10486'])) unset($_SESSION['paypal_10486']);//清楚购买失败标记
	//谷歌再营销
	$exchange = read_static_cache('exchange',2);
	$currency = get_currency();
	if(1 == count($cart_goods['goods_list'])) {
		$prodid = "'". $cart_goods['goods_list'][0]['goods_sn'] . $currency['lang'] ."'";
	} else {
		if($cart_goods['goods_list']) {
			$prodid = "[";
			foreach($cart_goods['goods_list'] as $key=>$goods_list_value) {
				$prodid .= "'". $goods_list_value['goods_sn'] . $currency['lang'] . "'" . ",";
			}
			$prodid = substr($prodid,0,strlen($prodid)-1);
			$prodid .= "]";
		}
	}
	$google_tag_params = array(
		'prodid' => $prodid,
		'pagetype' => "'cart'",
		'totalvalue' => round($cart_goods['total']['goods_amount']*$exchange['Rate'][''.$currency['currency'].''],2),
		'currency' => "'". $currency['currency'] ."'",
		'pcat' => "''"
	);
	$Arr['google_tag_params'] = $google_tag_params;
    //meta设置
    $Arr['seo_title'] = $_LANG_SEO['cart']['title'];
    $Arr['seo_keywords'] = $_LANG_SEO['cart']['keywords'];
    $Arr['seo_description'] = $_LANG_SEO['cart']['description'];
} //end cart

//检查美国仓
elseif($_ACT == 'check_us_warehouse')
{
     $goods_ids_str=empty($_POST['goods_ids_str'])?'':$_POST['goods_ids_str'];
     $goods_ids_arr=explode("|",$goods_ids_str);
     $rec_id_arr=array();
     $gids ="";
     for( $i = 0, $n = count($goods_ids_arr); $i < $n; $i ++ )
     {
         $goods_ids_arr1=explode(",",$goods_ids_arr[$i]);
         $gids.="'".$goods_ids_arr1[0]."',";
         $rec_id_arr[$goods_ids_arr1[0]]=$goods_ids_arr1[1];
     }
     $gids=substr($gids,0,-1);
     if($gids)
     {
         $sql = "SELECT goods_id,cat_id,goods_title,similar_goods,goods_sn FROM " . GOODS . " WHERE goods_id IN($gids)";
         $cat_id_arr = $db->arrQuery($sql);
         $cat_id_str="";
         if($cat_id_arr)
         {
             for( $i = 0, $n = count($cat_id_arr); $i < $n; $i ++ )
             {
                 $s='';
                 $isusa='0';
                 if(substr($cat_id_arr[$i]["goods_sn"],0,2)=='KA' || in_array(intval($cat_id_arr[$i]['goods_id']), array(70787, 70786, 70785, 70788)))
                 {
                     $isusa='1';
                     if(!empty($cat_id_arr[$i]["similar_goods"]))
                     {
                         $s.='<p>See more similar item:</p>';
                         $similar_goods_arr =explode(",",$cat_id_arr[$i]["similar_goods"]);
                         for( $j = 0, $m = count($similar_goods_arr); $j < $m; $j ++ )
                         {
                             $sql = "SELECT goods_id,goods_thumb FROM " . GOODS . " WHERE goods_sn='$similar_goods_arr[$j]'";
                             $similar_goods_info = $db->arrQuery($sql);

                             $s.='<a href="/product-'.$similar_goods_info[0]["goods_id"].'.html" target="_blank"><img style="width:50px;height:50px;" src="'.get_image_path(0,$similar_goods_info[0]["goods_thumb"]).'" title="'.$similar_goods_arr[$j].'"/></a>&nbsp;&nbsp;';
                         }
                     }
                 }
                 $cat_id_str.=$rec_id_arr[$cat_id_arr[$i]["goods_id"]]."|".$cat_id_arr[$i]["goods_id"]."|".$cat_id_arr[$i]["cat_id"]."|".$cat_id_arr[$i]["goods_title"]."|".$s."|".$isusa."@@";
             }
         }
         echo $cat_id_str = substr($cat_id_str,0,-2);
     }
     exit();
}

elseif ($_ACT == 'paypal_10486')
{
    unset($_SESSION['paypal_10486']);
	$PayerID = !empty($_GET['PayerID']) ? trim($_GET['PayerID']) : '';
	$token = !empty($_GET['token']) ? trim($_GET['token']) : '';
    $order_sn = !empty($_GET['order_sn']) ? trim($_GET['order_sn']) : '';
    $order_info=order_info(0,$order_sn);
    $order_goods=order_goods($order_info['order_id']);
    foreach ( $order_goods as $kk=>$vv )
    {
        $goodsinfo=$db->selectinfo("select goods_id,goods_thumb from eload_goods where goods_id='".$vv['goods_id']."'");
        $order_goods[$kk]['goods_thumb'] = get_image_path($goodsinfo['goods_id'], $goodsinfo['goods_thumb'], true);
    }
    $Arr["order_info"] = $order_info;
    $Arr["order_goods"] = $order_goods;
    $Arr["step"] = 'paypal_10486';
	$Arr['seo_title'] = 'Checkout - '.$_CFG['shop_name'];
    $Arr["token"] = $token;
    $Arr["order_sn"] = $order_sn;
}

elseif ($_ACT == 'paypal_redirect_pay')
{
    $token = !empty($_GET['token']) ? trim($_GET['token']) : '';
    $order_sn = !empty($_GET['order_sn']) ? trim($_GET['order_sn']) : '';
    $order_info=order_info(0,$order_sn);
    if($token && $order_sn)
    {
            global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
            global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
            global $gv_ApiErrorURL;
            global $sBNCode;
            require_once ("paypalfunctions.php");
			$finalPaymentAmount =  $order_info['order_amount'] ;
			$resArray = ConfirmPayment ( $finalPaymentAmount );
			$ack = strtoupper($resArray["ACK"]);
			if( $ack == "SUCCESS" )
			{
				$transactionId		= $resArray["TRANSACTIONID"];
				$transactionType 	= $resArray["TRANSACTIONTYPE"];
				$paymentType		= $resArray["PAYMENTTYPE"];
				$orderTime 			= $resArray["ORDERTIME"];
				$amt				= $resArray["AMT"];
				$currencyCode		= $resArray["CURRENCYCODE"];
				$feeAmt				= $resArray["FEEAMT"];
				$settleAmt			= $resArray["SETTLEAMT"];
				$taxAmt				= $resArray["TAXAMT"];
				$exchangeRate		= $resArray["EXCHANGERATE"];
				$paymentStatus    	= $resArray["PAYMENTSTATUS"];
				$pendingReason	    = $resArray["PENDINGREASON"];
				$reasonCode		    = $resArray["REASONCODE"];
				$db->query("update ". ORDERINFO." set order_status = '1' ,pay_time = '".gmtime()."' where order_sn = '$order_sn'");
				//发邮件
			   //send_email($order_info['email'], 17, $order_info);  //发送已收到款邮件
			   send_email_order_status_1($order_info['order_id'], $order_info['user_id']);
			   $order_info['pay_desc'] = ' Order query:&nbsp;You can view your order history by going to the <a href="/'. $cur_lang_url .'m-users-a-order_list.htm">My Account</a><br />';
               $order_info['order_status'] = 1;

                $logfile = 'order_payed';
                $num     = Logger::get($logfile);
                Logger::put($logfile, intval($num) + 1);
			}
			else
			{
                //付款被拒绝，返回paypal，换卡重新支付，   第2次重定向
                if( urldecode($resArray["L_ERRORCODE0"])=='10486' && $_SESSION['count_redirect']<2)
                {
                    $_SESSION['paypal_10486']='yes';
                    $_SESSION['count_redirect']=2;

                    Logger::filename('paypal_10486');
                    trigger_error($order_sn . ", token: {$token} ,count_redirect: 2");

                    header("Location: https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=".$token);
                    exit();
                }
                if($_SESSION['paypal_10486']=='yes')unset($_SESSION['paypal_10486']);
                if($_SESSION['count_redirect']>0)unset($_SESSION['count_redirect']);
				//Display a user friendly Error on the page using any of the following error information returned by PayPal
				$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
				$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
				$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
				$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
				$order_info['pay_desc'] = "GetExpressCheckoutDetails API call failed. ".
								    "<br>Detailed Error Message: " . $ErrorLongMsg.
									"<br>Short Error Message: " . $ErrorShortMsg.
									"<br>Error Code: " . $ErrorCode.
									"<br>Error Severity Code: " . $ErrorSeverityCode;
			}
    }
    $area_Arr = read_static_cache('area_key',2);
	$order_info['country_str'] = $area_Arr[$order_info['country']]['region_name'];
    $Arr['order'] = $order_info;
    $Arr['total'] = array('amount_formated'=>$order_info['order_amount']);
    $Arr['step']  = 'done';
}

//checkout页面付款调转动作
elseif ($_ACT == 'checkout_page_paypal_ec')
{
    global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature,$PAYPAL_URL;
    global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $gv_ApiErrorURL;
    global $sBNCode;
    $order_sn = empty($_GET['order_sn'])?'':$_GET['order_sn'];
    $pay_step = 'checkout';
	if (!empty($order_sn)){
		$sql = "SELECT order_id ,order_sn, goods_amount , free_shipping_fee, order_amount, shipping_fee, insure_fee, Need_Traking_number, city, province, country,point_money FROM ".ORDERINFO." WHERE order_sn = '".$order_sn."' ";
		$order = $db->selectinfo($sql);//print_r($order);
    }
    $cart_goods['goods_list']=order_goods($order['order_id']);//print_r($cart_goods);
    $finalPaymentAmount = $order['order_amount'] ;
	$_SESSION["Payment_Amount"] = $finalPaymentAmount ;
    $_SESSION["orderno"] = $order['order_sn'] ;
	require_once ("expresscheckout.php");
    exit;
}

elseif($_ACT == 'DoExpressCheckoutPayment')
{
    global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature,$PAYPAL_URL;
    global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $gv_ApiErrorURL;
    global $sBNCode;
    $pay_step = 'checkout';
    $order_sn = $_GET["order_sn"];
    if($order_sn)
    {
            $order_info_1=order_info(0,$order_sn);
            $_SESSION['TOKEN']=$_GET["token"];
            $_SESSION['payer_id']=$_GET["PayerID"];
            $finalPaymentAmount = $order_info_1['order_amount'];
            require_once ("paypalfunctions.php");
            $resArray = ConfirmPayment ( $finalPaymentAmount, $order_sn );
            $ack = strtoupper($resArray["ACK"]);
            if( $ack == "SUCCESS" )
            {
                $transactionId		= isset($resArray["TRANSACTIONID"])?$resArray["TRANSACTIONID"]:'';
                $transactionType 	= isset($resArray["TRANSACTIONTYPE"])?$resArray["TRANSACTIONTYPE"]:'';
                $paymentType		= isset($resArray["PAYMENTTYPE"])?$resArray["PAYMENTTYPE"]:'';
                $orderTime 			= isset($resArray["ORDERTIME"])?$resArray["ORDERTIME"]:'';
                $amt				= isset($resArray["AMT"])?$resArray["AMT"]:'';
                $currencyCode		= isset($resArray["CURRENCYCODE"])?$resArray["CURRENCYCODE"]:'';
                $feeAmt				= isset($resArray["FEEAMT"])?$resArray["FEEAMT"]:'';
                $settleAmt			= isset($resArray["SETTLEAMT"])?$resArray["SETTLEAMT"]:'';
                $taxAmt				= isset($resArray["TAXAMT"])?$resArray["TAXAMT"]:'';
                $exchangeRate		= isset($resArray["EXCHANGERATE"])?$resArray["EXCHANGERATE"]:'';
                $paymentStatus    	= isset($resArray["PAYMENTSTATUS"])?$resArray["PAYMENTSTATUS"]:'';
                $pendingReason	    = isset($resArray["PENDINGREASON"])?$resArray["PENDINGREASON"]:'';
                $reasonCode		    = isset($resArray["REASONCODE"])?$resArray["REASONCODE"]:'';
                $sql = " order_status = '1' ,pay_time = '".gmtime()."' ";
                $db->update(ORDERINFO,$sql," order_sn = '".$order_sn."'");
                //发邮件
                $order_info=array('order_no'=>$order_sn);
                //send_email($order_info_1['email'],17,$order_info);  //发送已收到款邮件
                send_email_order_status_1($order_info_1['order_id'], $order_info_1['user_id']);
                $order_info_1['pay_desc'] = ' Order query:&nbsp;You can view your order history by going to the <a href="/m-users-a-order_list.htm">My Account</a><br />';
                $order_info_1['order_status'] = 1;
                $goods_list = order_goods($order_info_1['order_id']);
                $Arr['discount']  = ($order_info_1['yuan_goods_amount'] - $order_info_1['goods_amount'] +$order_info_1['point_money'])*100;
            }
            else
            {
                //付款被拒绝，返回paypal，换卡重新支付， 第一次重定向---
                if( urldecode($resArray["L_ERRORCODE0"])=='10486' )
                {
                    $_SESSION['paypal_10486']='yes';
                    $_SESSION['count_redirect']=1;
                    Logger::filename(basename(__FILE__, '.php') . '.paypal_10486');
                    trigger_error(date("Y-m-d H:i:s").", ".$order_sn.", token: $token ,count_redirect:1");
                    header("Location: https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=".$token);
                    exit();
                }
                //Display a user friendly Error on the page using any of the following error information returned by PayPal
                $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
                $order_info_1['pay_desc'] = "GetExpressCheckoutDetails API call failed. ".
                                    "<br>Detailed Error Message: " . $ErrorLongMsg.
                                    "<br>Short Error Message: " . $ErrorShortMsg.
                                    "<br>Error Code: " . $ErrorCode.
                                    "<br>Error Severity Code: " . $ErrorSeverityCode;
            }
    }
    $Arr['order']  = $order_info_1;
    $Arr['total'] = array('amount_formated'=>$order_info_1['order_amount']);
    $Arr['step']  = 'done';
}

$_MDL = empty($turn_payment) ? 'flow' : 'turn_payment';

/**
 * 获得用户的可用积分
 *
 * @access  private
 * @return  integral
 */
function flow_available_points()
{
    $sql = "SELECT SUM(g.integral * c.goods_number) ".
            "FROM " . CART . " AS c, " . GOODS . " AS g ". "WHERE c.session_id = '" . SESS_ID . "' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " . "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
    $val = intval($GLOBALS['db']->getOne($sql));
    return integral_of_value($val);
}

/**
 * 更新购物车中的商品数量
 *
 * @access  public
 * @param   array   $arr
 * @return  void
 */
function flow_update_cart($_VAL) {
	global $db;
    $key = intval($_VAL['rid']);
    $val = intval($_VAL['goods_number']);
    $sql = "SELECT c.gifts_id,g.goods_title,g.goods_id,c.main_goods_id, g.goods_number,g.groupbuy_max_number,g.is_groupbuy,g.groupbuy_price,g.groupbuy_final_price,g.groupbuy_people_first_number,g.groupbuy_people_final_number,g.groupbuy_start_date,g.groupbuy_end_date, g.shop_price,c.goods_number AS cart_num,c.goods_off,c.lmt_num,c.goods_attr_id,c.rec_id  FROM " .GOODS. " AS g, ".CART. " AS c ".
			"WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
	$row = $GLOBALS['db']->selectinfo($sql);

	/* 系统启用了库存，检查输入的商品数量是否有效 */
	if (intval($GLOBALS['_CFG']['use_storage']) > 0)
	{
		if ($row['goods_number'] < $val)
		{
			echo 'stock insufficiency '.$row['goods_number'];
			exit;
		}
	}

	$attr_id    =  explode(',',$row['goods_attr_id']);
	$main_goods_id = $row['main_goods_id'];
	$goods_price = get_final_price($row['goods_id'], $val, true, $attr_id,$row['main_goods_id']);
	if($goods_price == 0.01) $val =1;
	if($row['gifts_id'])$val =1;
	if ($main_goods_id) {//配件
	    //主商品在购物车中的数量
	    $num = $db->getOne('SELECT goods_number FROM ' . CART . " WHERE session_id='" . SESS_ID . "' AND goods_id={$main_goods_id}");
	    $num = min($num, $val);
	    return $db->update(CART, "goods_number={$num},goods_price={$goods_price}", 'rec_id=' . $key);
	}

	//检查中奖折扣是不是个数限制
	if ($row['lmt_num'] != '0'){
			if ($val < $row['lmt_num'])
            {
				echo 'To buy at least '.$row['lmt_num'];
                exit;
            }
		$goods_price = price_format(($row['shop_price']*(100-$row['goods_off'])*0.01), false);
	}

	if($goods_price == '0.01'){
		//检查购物车是否已经有0.01的商品
		$sql = "SELECT count(*) FROM " .CART." WHERE session_id = '" .SESS_ID. "' AND goods_price = '0.01'  and rec_id <> '".$row['rec_id']."'";
		if ($GLOBALS['db']->getOne($sql)){
			$sql = "delete FROM " .CART." WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
			$GLOBALS['db']->query($sql);
			echo '<script language="javascript">
			function slideHd(){window.location.reload();}
			ymPrompt.alert({message:"Product :'.$row['goods_title'].' is limited by one piece per customer in 2 weeks deal $0.01/unit! ",width:350,height:170,title:"System Message",handler:slideHd,btn:[["OK"]]});</script>';
			exit;
		}

	   $sql = "select count(*) from ".ODRGOODS." as g  left join ".ORDERINFO." as o on o.order_id = g.order_id where g.goods_price = '0.01' AND DATEDIFF(curdate(),FROM_UNIXTIME(o.add_time,'%Y-%m-%d')) < 14
	   and o.user_id = '".$_SESSION['user_id']."'  ";
		if ($GLOBALS['db']->getOne($sql)){
			echo '<script language="javascript">
			function slideHd(){window.location.reload();}
			ymPrompt.alert({message:"Product :'.$row['goods_title'].' is limited by one piece per customer in 2 weeks deal $0.01/unit!",width:350,height:170,title:"System Message",handler:slideHd,btn:[["OK"]]});</script>';
			exit;
		}

	}

	//判断团购是否过期
	$row['is_groupbuy'] = (!empty($row['is_groupbuy']) && $row['groupbuy_start_date'] < gmtime() && $row['groupbuy_end_date'] > gmtime() )?1:0;
	//团购数量限制2个
	if($row['is_groupbuy'] && $val>$row['groupbuy_max_number']){
			echo '<script language="javascript">
			function slideHd(){$("#goods_number_'.$key.'").val('.$row['groupbuy_max_number'].');}
			ymPrompt.alert({message:"Sorry, This is Gruop Deals product and you are limited to buy '.$row['groupbuy_max_number'].' PCS at most each time!",width:350,height:170,title:"System Message",handler:slideHd,btn:[["OK"]]});</script>';
			exit;
	}

	/* 更新购物车中的商品数量 */
	$sql = "UPDATE " .CART.
			" SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
	$GLOBALS['db']->query($sql);
	//减少主商品数，保证配件个数不能大于主商品数
	$val < $row['cart_num'] && $db->update(CART, 'goods_number=' . $val, "session_id='" . SESS_ID . "' AND main_goods_id={$row['goods_id']} AND goods_number>{$val}");
}

/**
 * 删除购物车中的商品
 *
 * @access  public
 * @param   integer $id
 * @return  void
 */
function flow_drop_cart_goods($id)
{
	global $db;
	$goods_id = $db->getOne("select goods_id FROM " . CART ." WHERE session_id = '" . SESS_ID . "' " ."AND rec_id = '$id' LIMIT 1");
	$sql = "DELETE FROM " . CART ." WHERE session_id = '" . SESS_ID . "' " ."AND rec_id = '$id' LIMIT 1";
	$GLOBALS['db']->query($sql);
	$goods_id && $db->update(CART,'main_goods_id=0', "session_id='" . SESS_ID . "' AND main_goods_id={$goods_id}");//删除配件
	//清空购物车时清除优惠码
	if (!empty($_SESSION['pcode_code'])){
		$sql = "select count(*) from ".CART." WHERE session_id = '" . SESS_ID . "' ";
		$getNum = $GLOBALS['db']->getOne($sql);
		if($getNum == 0){
			unset($_SESSION['pcode_lv']);
			unset($_SESSION['pcode_code']);
			unset($_SESSION['pcode']);
			unset($_SESSION['pcode_goods']);
			unset($_SESSION['pcode_fangshi']);
			unset($_SESSION['total_price']);
		}
	}
}

//检查drop shipping 是否近2个月满 10单
function get_resent60_order(){
	$re_str = '';
    return '';
	//	$sql = "select `is_need_chknum` from ".USERS." WHERE user_id = '".$_SESSION['user_id']."' ";
	//	if($GLOBALS['db']->getOne($sql)){
	//		$sql = "select count(*) from ".ORDERINFO." WHERE user_id = '".$_SESSION['user_id']."' and DATEDIFF(curdate(),FROM_UNIXTIME(add_time,'%Y-%m-%d')) < 60 AND order_status > 0 ";
	//		$orderNo = $GLOBALS['db']->getOne($sql);
	//		if ($orderNo > 10){
	//			$re_str = '1';
	//		}
	//	}
	//    return $re_str;
}

function order_email_send($order){
	global $Tpl, $db, $cur_lang, $default_lang, $_LANG;
	$sql = " SELECT c.goods_id, c.goods_name, c.goods_sn, c.goods_number, c.market_price, ".
                "c.goods_price, c.goods_attr, c.goods_off, c.lmt_num, c.addtime, c.custom_size, c.attr_goods_sn ".
            " FROM " .CART . ' AS c JOIN ' . GOODS . ' AS g' .
            " ON c.goods_id=g.goods_id WHERE c.session_id = '".SESS_ID."' AND (c.is_groupbuy=0 OR g.groupbuy_end_date<" . gmtime() . ')';
	$goods_list = $db->arrQuery($sql);
	$goods_amount = 0;
	foreach ($goods_list as $key => $value)
	{
		$urlfile = $urlfile = get_details_link($value['goods_id'],'');
		$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
		$goods_list[$key]['shop_price']  = price_format($value['goods_price'], false);
		$goods_list[$key]['subtotal']     = price_format($value['goods_price'] * $value['goods_number'], false);
		$goods_list[$key]['goods_title']  = $value['goods_name'];
		$goods_list[$key]['url_title']    = $urlfile;
		$goods_amount = $goods_amount + $goods_list[$key]['subtotal'];
	}
	$order['formated_goods_amount'] = price_format($goods_amount, false);
	$order['formated_shipping_fee'] =  price_format($order['shipping_fee'], false);
	$order['Need_Traking_number']   =  price_format($order['Need_Traking_number'], false);
	$order['insure_fee']            =   price_format($order['insure_fee'], false);
	$area_Arr = read_static_cache('area_key',2);
	$order['country'] = $area_Arr[$order['country']]['region_name'];
	$firstname         = str_replace("&nbsp;", "", $_SESSION['firstname']);
	if(empty($firstname)) {
		$firstname     = $_LANG['my_friend'];
	}
	$Arr['order']      =  $order;
	$Arr['goods_list'] = $goods_list;
	$Arr['order_id']   = $order['order_id'];
	$Arr['firstname']  = $firstname;
    $Arr['email'] = $_SESSION['email'];
    require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');    //邮件模版配制缓存文件
	$Arr['recommend_goods'] =  get_mail_template_goods(20, $mail_conf); //邮件产品 by mashanling on 2013-06-14 09:17:30
	foreach( $Arr as $key => $value ){
		$Tpl->assign( $key, $value );
	}
	$email        = $_SESSION['email'];
	$mail_subject = str_replace('{$order_sn}',$order['order_sn'],$mail_conf[$cur_lang][20]);
	$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $cur_lang .'/20.html');
	if(empty($mail_subject)) {
		$mail_subject = str_replace('{$order_sn}',$order['order_sn'],$mail_conf['en'][20]);
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/20.html');
	}
	exec_send2($email,$mail_subject,$mail_body);
}

/**
 * 检查购物车中是否有商品
 *
 */
function check_cart() {
	global $Tpl,$db,$_LANG ;
	$_SESSION['email'] = empty($_SESSION['email']) ? SESS_ID : $_SESSION['email'];
    $sql = 'SELECT g.goods_id FROM ' . CART . ' AS c JOIN ' . GOODS . ' AS g ON c.goods_id=g.goods_id' .
     " WHERE c.session_id='".$_SESSION['email']."'";
    !$db->getOne($sql) && show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
}

/**
 * 获得购买过该商品的人还买过的商品
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_also_bought($goods_id) {
    $GLOBALS['Arr']['also_bought'] = 'Other Customers Bought These also Buy';
    $db = $GLOBALS['db'];
    if (!empty($goods_id)) {
        $goods_id = join(',', $goods_id);
        $sql = 'SELECT COUNT(b.goods_id ) AS num, g.goods_id, g.cat_id, g.goods_title, g.is_free_shipping, g.goods_thumb, g.goods_img, g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date,g.url_title ' .
                'FROM ' . ODRGOODS . ' AS a ' .
                'LEFT JOIN ' . ODRGOODS . ' AS b ON b.order_id = a.order_id ' .
                'LEFT JOIN ' . GOODS . ' AS g ON g.goods_id = b.goods_id ' .
                "WHERE a.goods_id IN({$goods_id}) AND b.goods_id NOT IN({$goods_id}) AND g.add_time>" . (gmtime() - 60 * 86400) . " AND g.is_on_sale = 1  and g.is_login = 0  AND g.goods_number > 0  and g.is_alone_sale = 1  AND g.is_delete = 0 AND g.goods_thumb !='' " .
                'GROUP BY b.goods_id ORDER BY num DESC LIMIT 10';
        $res = $db->query($sql);
    }
    if (empty($res) || !$db->nr($res)) {
        $res = get_new_arrival();
    }
    $key = 0;
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$key]['goods_id']    = $row['goods_id'];
        $arr[$key]['goods_title']  = $row['goods_title'];
        $arr[$key]['is_free_shipping']  = $row['is_free_shipping'];
        $arr[$key]['short_name']  = sub_str($row['goods_title'], 70);
        $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$key]['goods_img']   = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$key]['shop_price']  = price_format($row['shop_price']);
	    $arr[$key]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);
        if ($row['promote_price'] > 0)
        {
            $arr[$key]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$key]['formated_promote_price'] = price_format($arr[$key]['promote_price']);
        }
        else
        {
            $arr[$key]['promote_price'] = 0;
        }
        $key++;
    }
    return $arr;
}

/**
 * 禁用GC付款方式
 * @author: Jim
 * @return true 不允许GC付款
 *
 */
function  disable_gc_payment($order_amount,$region_code,$user_id){
	global $db;
	$is_new_user = is_new_customer($user_id);
	$now = gmtime();
	if(strtoupper($region_code)=='US'){
		if($is_new_user&&$order_amount>140)return true;
		if(!$is_new_user&&$order_amount>300)return true;
	}else{
		if($is_new_user&&$order_amount>300)return true;
	}
	if($is_new_user){
		$gc_order_count_24h = $db->getone("select count(1) from eload_order_info where user_id=$user_id and order_status between 1 and 9 and add_time>$now-24*3600 and pay_id='webcollect'");//24小时内GC付款单数
		if($is_new_user&&$gc_order_count_24h>0&&$order_amount>100)return true;
		$gc_order_count_aWeek = $db->getone("select count(1) from eload_order_info where user_id=$user_id and order_status between 1 and 9 and add_time>$now-24*7*3600 and pay_id='webcollect'");//24小时内GC付款单数
		if($is_new_user&&$gc_order_count_aWeek>0&&$order_amount>200)return true;
	}
}

/**
 * 含电子烟产品，禁用GC付款方式
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2014-02-19 09:23:46
 *
 * @param array $goods_arr 商品数组
 * @param array $payment_arr 付款方式
 *
 */
function disable_gc_payment2($goods_arr, &$payment_arr) {
    $key = 'webcollect';

    if (isset($payment_arr[$key])) {
        $cat_arr    = read_static_cache('category_c_key', 2);
        $cat_id     = 191;

        foreach($goods_arr as $item) {
            $cat_info   = $cat_arr[$item['cat_id']];
            $node_arr   = explode(',', $cat_info['node']);

            if (in_array($cat_id, $node_arr)) {
                unset($payment_arr[$key]);

                return;
            }
        }
    }
}

/**
 * 是否新客户
 */
function is_new_customer($user_id){
	global $db;
	if(!empty($user_id)){
		$first_order_date = $db->getone("select add_time from eload_order_info where user_id=$user_id and order_status between 1 and 9 order by order_id asc limit 1");
		if(empty($first_order_date)){
			return true;
		}else{
			if((gmtime()-$first_order_date)>30*3600*24){
				return false;
			}
		}
	}else{
		return true;
	}
	return true;
}

/**
 * 获取新上架商品
 *
 * @param unknown_type $cat_id
 * @param unknown_type $goods_id
 */
function get_new_arrival($cat_id = 0, $goods_id = 0){
    $GLOBALS['Arr']['also_bought'] = 'The Best Selling New Arrivals Recommendation';
	$sql = 'SELECT goods_id, cat_id, goods_title, is_free_shipping, goods_thumb, goods_img, shop_price, promote_price, promote_start_date, promote_end_date,url_title FROM ' . GOODS .
           " WHERE is_on_sale=1  AND goods_number>0 AND is_delete=0 AND goods_thumb != ''" .
		   ' ORDER BY goods_id DESC LIMIT 10';
	return $GLOBALS['db']->query($sql);
}

//减库存
function reduce_stock($order_number){
	global $db;
	$sql = "select g.goods_id,g.goods_number as kucun,og.goods_number as salenumber,shelf_down_type from eload_order_goods og ,eload_order_info oi,eload_goods g where og.order_id=oi.order_id and og.goods_id=g.goods_id and g.goods_number<5001 and g.goods_number>0 and oi.order_sn='$order_number'";
	$order = $db->arrQuery($sql);
	foreach ($order as $o){
		if($o['kucun'] >0 && $o['kucun']<=5000|| $o['shelf_down_type'] == 100){
			$new_kucun = $o['kucun']-$o['salenumber'];
			if($new_kucun<0)$new_kucun=0;
			$db->query("update eload_goods set goods_number = $new_kucun where goods_id=".$o['goods_id']);
		}
	}
}

/**
 * 返回linkshare需要的信息
 * @param 产品数组 $goods_list
 */
function linkshare($goods_list){
	$linkshare = array();
	$linkshare['skulist'] ='';
	$linkshare['qlist'] ='';
	$linkshare['amtlist'] ='';
	foreach ($goods_list as  $v){
		$linkshare['skulist'] .=$v['goods_sn'].'|';
		$linkshare['qlist'] .=$v['goods_number'].'|';
		$linkshare['amtlist'] .= ($v['subtotal']*100).'|';
	}
	foreach ($linkshare as &$v){
		$v = substr($v,0, -1);
	}
	return $linkshare;
}

/**
 * 地址包含apo,po box,box，禁用dhl配送方式
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-12-13 11:47:40
 * @last modify  2012-12-13 11:47:40 by mashanling
 *
 * @param array $consignee 收货地址信息
 *
 * @return void 无返回值
 */
function disabled_dhl($consignee) {
    $address1 = isset($consignee['addressline1']) ? $consignee['addressline1'] : '';
    $address2 = isset($consignee['addressline2']) ? $consignee['addressline2'] : '';
    $city     = isset($consignee['city']) ? $consignee['city'] : '';
    $address  = $address1 . ' ' . $address2 . ' ' . $city;
	preg_match('/\bpo[.\-\/]*box\b|\bpo\b|\bapo\b|\bbox\b/i', $address, $matches);
	if(!empty($matches[0])) {
		$GLOBALS['Arr']['disabled_dhl'] = 1;
	} else {
		$GLOBALS['Arr']['disabled_dhl'] = FALSE;
	}
}

/**
 * 单个订单重量大于2KG禁用免邮配送方式
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-11-21 10:24:38
 * @last modify  2012-11-21 10:24:38 by mashanling
 *
 * @param array $cart_goods 购物车信息
 *
 * @return void 无返回值
 */
function disabled_free_shipping_method(&$cart_goods) {
    if (count($cart_goods['goods_list']) == 1 && $cart_goods['goods_list'][0]['goods_number'] == 1 && $cart_goods['total']['free_shipping_weight'] + $cart_goods['total']['shipping_weight'] > 2) {
        $GLOBALS['Arr']['disabled_free_shipping_method'] = true;
    }
}


/**
 * 更新计算订单商品的运费占比
 *
 * @param array	$order			//订单信息
 * @param array	$cart_goods		//商品信息
 * */
function update_order_goods_shipping_fee($order,$order_goods)
{
	global $db;
    foreach ($order_goods['goods_list'] as $key => $value)
    {
    	$shipping_weight    = 0;		//当前商品重量
    	$goods_weight_total = 0;		//订单商品总重量
    	$free_shipping_fee  = 0;		//免邮商品销售价格所占运费
    	$shipping_fee       = 0;   	    //非免邮商品运费或者是免邮商品使用DHL,EMS不交运费
    	switch ($order['shipping_id'])
		{
			case 1:		//香港小包
				$goods_free_weight  = $goods_weight = $value['goods_weight'];
				$goods_weight_total = $order_goods['total']['shipping_weight'] + $order_goods['total']['free_shipping_weight'];
				break;
			case 2:		//快递
				$goods_free_weight  = $goods_weight = $value['goods_weight'];
				$goods_weight_total = $order_goods['total']['shipping_weight'] + $order_goods['total']['free_shipping_weight'];
				break;
			/*case 3:
				$goods_weight = $value['goods_weight'];
				break;*/
			case 3:		//DHL
				$goods_free_weight  = $value['goods_weight'];
				$goods_weight       = $value['goods_volume_weight'];
				$goods_weight_total = $order_goods['total']['shipping_volume_weight'] +$order_goods['total']['free_shipping_volume_weight'];

				break;
		}

		if($value['is_free_shipping'])	//免邮商品
    	{
    		$free_shipping_fee = price_format($value['goods_number']*get_shipping_fee(0,$goods_free_weight),false);
    		$shipping_fee = price_format($order['shipping_fee'] * $goods_weight * $value['goods_number']/$goods_weight_total,false);
    	}
    	else
    	{
    		$free_shipping_fee = 0.00;
    		$shipping_fee = price_format($order['shipping_fee'] * $goods_weight * $value['goods_number']/$goods_weight_total,false);
    	}
    	$sql = "UPDATE " . ODRGOODS . " SET free_shipping_fee = " . $free_shipping_fee .", shipping_fee = " . $shipping_fee . " WHERE order_id = " . $order['order_id'] . " AND goods_id = " . $value['goods_id'];
    	$db ->query($sql);
    }
}

/**
 * 获取电子商务统计商品信息
 *
 * @author          mashanling(msl-138@163.com)
 * @date            2013-10-07 15:16:21
 *
 * @param array $order_info 商品信息
 *
 * @return void 无返回值
 */
function get_google_conversion_order_goods(&$order_info) {
    global $db;

    $sql    = 'SELECT * FROM ' . ODRGOODS . ' WHERE order_id=' . $order_info['order_id'];
    $goods  = $db->arrQuery($sql);

    foreach($goods as $k => $v) {
        $goods[$k]['goods_name'] = addslashes($v['goods_name']);

        $cat_info = $db->arrQuery('SELECT c.cat_name FROM ' . CATALOG . ' AS c JOIN ' . GOODS . ' AS g ON c.cat_id=g.cat_id AND g.goods_id =' . $v['goods_id']);

        if (isset($cat_info[0])) {
            $goods[$k]['cat_name'] = addslashes($cat_info[0]['cat_name']);
        }
    }

    $order_info['goodsListArr'] = $goods;
}

/**
 * 检测用户是否符合GC付款规则
 * 新规则：一、当客户曾经有GC付款记录，且当前订单发货地址国家和原订单国家一致，显示GC付款方式。

 * @author          fangxin
 * @date            2013-12-31 PM
 *
 * @param $user_id 用户信息
 * @param $country_id 用户收货地址国家ID
 */
function check_user_gc_payment($user_id, $country_id) {
    global $db;
	$sql    = 'SELECT order_id, user_id, country, add_time FROM ' . ORDERINFO . ' WHERE user_id='. $user_id .' AND (order_status BETWEEN 1 AND 4) AND country = '. $country_id .' AND pay_id = \'webcollect\'';
	$result  = $db->arrQuery($sql);
	if(!empty($result[0]['country'])) {
		return 1;
	} else {
		return 0;
	}
}

//用户付款成功后发送邮件
function send_email_order_status_1($order_id, $user_id) {
	global $Tpl, $db, $cur_lang, $default_lang, $_LANG;
	//订单产品列表
	$sql = "SELECT * FROM ". ODRGOODS ." WHERE order_id = ". $order_id ."";
	$goods_list = $db->arrQuery($sql);
	$goods_amount = 0;
	foreach ($goods_list as $key => $value)
	{
		$urlfile = $urlfile = get_details_link($value['goods_id'],'');
		$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
		$goods_list[$key]['shop_price']   = price_format($value['goods_price'], false);
		$goods_list[$key]['subtotal']     = price_format($value['goods_price'] * $value['goods_number'], false);
		$goods_list[$key]['goods_title']  = $value['goods_name'];
		$goods_list[$key]['url_title']    = $urlfile;
	}

	//订单详情
	$order = order_info($order_id);
	$Arr['order_id'] = $order['order_id'];
	$Arr['order_no'] = $order['order_sn'];
	$order['formated_goods_amount'] = $order['formated_goods_amount'];
	$order['insure_fee'] = $order['formated_insure_fee'];
	$order['formated_shipping_fee'] = $order['formated_shipping_fee'];
	$order['Need_Traking_number'] = $order['Need_Traking_number'];
	$order['order_amount'] = $order['order_amount'];
	$Arr['order'] = $order;
	$Arr['goods_list'] = $goods_list;
	//用户信息
	$sql = "SELECT user_id,email,firstname,lastname,lang FROM ". USERS ." WHERE user_id = ". $user_id ." LIMIT 1";
	$row_user = $db->selectInfo($sql);
	$firstname = $row_user['firstname'];
	if(empty($firstname)) {
		$firstname     = $_LANG['my_friend'];
	}
	$Arr['firstname'] = $firstname;
	$email = $row_user['email'];
	if(empty($row_user)) {
		$lang = 'en';
	} else {
		$lang = $row_user['lang'];
	}
	foreach( $Arr as $key => $value ){
		$Tpl->assign( $key, $value );
	}
	$email_temp_id = 17;
	require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');
	if(empty($lang) || $lang == 'en') {
		$mail_subject = $mail_conf['en'][$email_temp_id];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/'.$email_temp_id.'.html');
	} else {
		$mail_subject = $mail_conf[$lang][$email_temp_id];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/'.$email_temp_id.'.html');
	}
	$mail_subject = str_replace('{$order_no}',$order['order_sn'],$mail_subject);
	if(empty($email)) {$email = 'snipersheep@aliyun.com';}
	if(exec_send2($email, $mail_subject, $mail_body)){
		$email_send_state = 1;
	} else {
		$email_send_state = 0;
	}
	//邮件发送记录保存 20140304 fangxin PM
	$data = array(
		'firstname' => '',
		'lastname' => '',
		'email' => $email,
		'order_num' => '',
		'turnover' => '',
		'template_id' => 17,
		'pri' => 0,
		'state' => $email_send_state,
		'last_send' => time()
	);
	add_mail_log($data);
}
