<?php
if (!defined('INI_WEB')){die('访问拒绝');}

/**
 * 修改个人资料（Email, 性别，生日)
 * @access  public
 * @param   array       $profile       array_keys(user_id int, email string, sex int, birthday string);
 * @return  boolen      $bool
 */
function edit_profile($profile)
{
    if (empty($profile['user_id']))
    {
        echo $GLOBALS['_LANG']['not_login'];
        return false;
    }

    $cfg = array();
	$cfg['firstname'] = $profile['firstname'];
	$cfg['lastname']  = $profile['lastname'];

    $cfg['email'] = $GLOBALS['db']->getOne("SELECT email FROM " . USERS . " WHERE user_id='" . $profile['user_id'] . "'");
    if (isset($profile['sex']))
    {
        $cfg['gender'] = intval($profile['sex']);
    }
    if (isset($profile['paypal_account']))
    {
        $cfg['paypal_account'] = $profile['paypal_account'];
    }
    if (isset($profile['introduction']))
    {
        $cfg['introduction'] = $profile['introduction'];
    }
    if (isset($profile['bbs_profile']))
    {
        $cfg['bbs_profile'] = $profile['bbs_profile'];
    }
    if (isset($profile['bbs_id']))
    {
        $cfg['bbs_id'] = $profile['bbs_id'];
    }
    if (isset($profile['affiliates_apply_time']))
    {
        $cfg['affiliates_apply_time'] = $profile['affiliates_apply_time'];
    }
    if (isset($profile['user_type']))
    {
        $cfg['user_type'] = $profile['user_type'];
    }

    //print_r($cfg);
    //exit();

    if (!$GLOBALS['user']->edit_user($cfg))  //保存用户资料
    {
        return false;
    }

    /* 过滤非法的键值 */
    $other_key_array = array('msn', 'phone');
    foreach ($profile['other'] as $key => $val)
    {
        //删除非法key值
        if (!in_array($key, $other_key_array))
        {
            unset($profile['other'][$key]);
        }
        else
        {
            $profile['other'][$key] =  htmlentities($val); //防止用户输入javascript代码
        }
    }
    /* 修改在其他资料 */
    //print_r($profile['other']);
    //exit();
    if (!empty($profile['other']))
    {
        $GLOBALS['db']->autoExecute(USERS, $profile['other'], 'UPDATE', "user_id = '$profile[user_id]'");
    }

    return true;
}

/**
 * 获取用户帐号信息
 *
 * @access  public
 * @param   int       $user_id        用户user_id
 *
 * @return void
 */
function get_profile($user_id)
{
    global $user;


    /* 会员帐号信息 */
    $info  = array();
    $infos = array();
    $sql  = "SELECT * ".
           "FROM " .USERS . " WHERE user_id = '$user_id'";
    $infos = $GLOBALS['db']->selectinfo($sql);
    //echo $sql;
    $row['email'] = addslashes($infos['email']);
    //$row = $user->get_profile_by_name($infos['email']); //获取用户帐号信息
    $_SESSION['email'] = $row['email'];    //注册SESSION


    /* 会员等级 */
    if ($infos['user_rank'] !='')
    {
        $info['rank_name']     = $GLOBALS['_LANG']['rank_name'][$infos['user_rank']];
    }
    else
    {
        $info['rank_name'] = $GLOBALS['_LANG']['undifine_rank'];
    }

    $cur_date = date('Y-m-d H:i:s');

    $info['email']       = $_SESSION['email'];
    $info['sex']         = isset($infos['sex'])      ? $infos['sex']      : 0;

    $info['msn']          = $infos['msn'];
    $info['firstname']    = $infos['firstname'];
    $info['lastname']     = $infos['lastname'];
    $info['phone'] = $infos['phone'];
    $info['introduction'] = $infos['introduction'];
    $info['paypal_account'] = $infos['paypal_account'];
	$info['bbs_id'] = $infos['bbs_id'];
	$info['bbs_profile'] = $infos['bbs_profile'];
	$info['avatar'] = $infos['avatar'];
	$info['avaid_point'] = $infos['avaid_point'];
    return $info;
}

/**
 * 取得收货人地址列表
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_consignee_list($user_id)
{
    $sql = "SELECT * FROM " . ADDR .
            " WHERE user_id = '$user_id' LIMIT 5";

    return $GLOBALS['db']->arrQuery($sql);
}

/**
 *  给指定用户添加一个指定红包
 *
 * @access  public
 * @param   int         $user_id        用户ID
 * @param   string      $bouns_sn       红包序列号
 *
 * @return  boolen      $result
 */
function add_bonus($user_id, $bouns_sn)
{
    if (empty($user_id))
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['not_login']);

        return false;
    }

    /* 查询红包序列号是否已经存在 */
    $sql = "SELECT bonus_id, bonus_sn, user_id, bonus_type_id FROM " .$GLOBALS['ecs']->table('user_bonus') .
           " WHERE bonus_sn = '$bouns_sn'";
    $row = $GLOBALS['db']->selectinfo($sql);
    if ($row)
    {
        if ($row['user_id'] == 0)
        {
            //红包没有被使用
            $sql = "SELECT send_end_date, use_end_date ".
                   " FROM " . $GLOBALS['ecs']->table('bonus_type') .
                   " WHERE type_id = '" . $row['bonus_type_id'] . "'";

            $bonus_time = $GLOBALS['db']->selectinfo($sql);

            $now = gmtime();
            if ($now > $bonus_time['use_end_date'])
            {
                $GLOBALS['err']->add($GLOBALS['_LANG']['bonus_use_expire']);
                return false;
            }

            $sql = "UPDATE " .$GLOBALS['ecs']->table('user_bonus') . " SET user_id = '$user_id' ".
                   "WHERE bonus_id = '$row[bonus_id]'";
            $result = $GLOBALS['db'] ->query($sql);
            if ($result)
            {
                 return true;
            }
            else
            {
                return $GLOBALS['db']->errorMsg();
            }
        }
        else
        {
            if ($row['user_id']== $user_id)
            {
                //红包已经添加过了。
                $GLOBALS['err']->add($GLOBALS['_LANG']['bonus_is_used']);
            }
            else
            {
                //红包被其他人使用过了。
                $GLOBALS['err']->add($GLOBALS['_LANG']['bonus_is_used_by_other']);
            }

            return false;
        }
    }
    else
    {
        //红包不存在
        $GLOBALS['err']->add($GLOBALS['_LANG']['bonus_not_exist']);
        return false;
    }

}

/**
 *  获取用户指定范围的订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function get_user_orders($user_id, $num = 10, $start = 0)
{
    /* 取得订单列表 */
    $arr    = array();

    $sql = "SELECT order_id, order_sn, order_status,add_time,order_amount,promotion_code_youhuilv,order_currency, order_rate, " .
           "order_amount AS total_fee ".
           " FROM " .ORDERINFO .
           " WHERE user_id = '$user_id' ORDER BY add_time DESC limit  $start,$num";
	//echo $sql;
	//exit();
    $res = $GLOBALS['db']->arrQuery($sql);
    $now = gmtime();
    foreach ($res as $row)
    {

        $row['order_status_str'] = $GLOBALS['_LANG']['os'][$row['order_status']];

        $arr[] = array('order_id'       => $row['order_id'],
                       'order_sn'       => $row['order_sn'],
                       'order_time'     => local_date($GLOBALS['_CFG']['AM_time_format'], $row['add_time']),
                       'order_status'   => $row['order_status'],
                       'order_status_str'=> $row['order_status_str'],
					   'order_currency' => $row['order_currency'],
                       //'total_fee'      => price_format($row['total_fee'], false),
					   'total_fee'      => price_format($row['total_fee']*($row['order_rate']>0?$row['order_rate']:1), false),
                       'is_can_del'        => ($now-$row['add_time'])>3*24*3600||!(empty($_SESSION["WebUserInfo"]["said"])) ?1:0
					   );
    }
    return $arr;
}
function get_user_links($user_id, $num = 10, $start = 0,$unique_id=0)
{
    /* 取得链接列表 */
	if(!$unique_id)return;
    $arr    = array();
	global $db;

    $sql = "SELECT wj.*  ,(select count(distinct(ips)) from ".WJ_IP." where from_linkid = wj.id) as ip_count   FROM ".WJ_LINK." as wj  WHERE wj.user_id = '".$user_id."'  ORDER BY if(wj.id =".$unique_id.",1,0)desc,wj.id desc limit  $start,$num";
    //echo $sql;
    $res = $GLOBALS['db']->arrQuery($sql);
    //print_r($res);
    $ids="0";
    foreach ($res as $k=>$v){
    	$ids.=",$v[id]";
    }
    //echo $ids;
    //echo "SELECT SUM(order_amount) as amount FROM ".ORDERINFO." WHERE month(FROM_UNIXTIME(pay_time,'%Y-%m-%d'))=month(curdate())-1  AND order_status > 0 and order_status < 9 and wj_linkid in($ids) group by wj_linkid";
    //exit();
    $order_finish = $db->arrQuery("SELECT wj_linkid,count(*) as amount FROM ".ORDERINFO." WHERE   order_status > 0 and order_status < 9 and wj_linkid in($ids) group by wj_linkid" );
	$order_finish=fetch_id($order_finish,'wj_linkid');

    $last_complete_issue = $db->arrQuery("SELECT wj_linkid,SUM(order_amount) as amount FROM ".ORDERINFO." WHERE month(FROM_UNIXTIME(pay_time,'%Y-%m-%d'))=month(curdate())-1  AND order_status > 0 and order_status < 9 and wj_linkid in($ids) group by wj_linkid" );
	$last_complete_issue=fetch_id($last_complete_issue,'wj_linkid');


    //exit();
    $this_complete_issue = $db->arrQuery("SELECT wj_linkid,SUM(order_amount) as amount FROM ".ORDERINFO." WHERE month(FROM_UNIXTIME(pay_time,'%Y-%m-%d'))=month(curdate())  AND order_status > 0 and order_status < 9 and wj_linkid in($ids) group by wj_linkid");
    $this_complete_issue=fetch_id($this_complete_issue,'wj_linkid');


    $this_pending_issue= $db->arrQuery("SELECT wj_linkid, SUM(order_amount) as amount FROM ".ORDERINFO." WHERE order_status=0 and wj_linkid in($ids) group by wj_linkid");
    $this_pending_issue=fetch_id($this_pending_issue,'wj_linkid');

    $order_count = $db->arrQuery("SELECT wj_linkid,count(*) as amount FROM ".ORDERINFO." WHERE wj_linkid in($ids) group by wj_linkid");
    $order_count=fetch_id($order_count,'wj_linkid');
     //print_r($order_count);


    foreach ($res as $row)
    {

        //$row['order_status_str'] = $GLOBALS['_LANG']['os'][$row['order_status']];
		//$row['link_url_jump'] = preg_match ('/\?/', $row['link_url']) ? $row['link_url'] ."&lkid=" . $row['id'] : $row['link_url'] ."?lkid=" . $row['id'];
        $s   = false === strpos($row['link_url'], '#') ? '#' : '';
        $row['link_url_jump'] = $row['link_url'] . $s . 'lkid=' . $row['id'];
        $arr[] = array('id'       => $row['id'],
                       'link_name'       => $row['link_name'],
                       //'order_time'     => local_date($GLOBALS['_CFG']['AM_time_format'], $row['actio']),
                       'link_text'   => $row['link_text'],
                       'link_url'=> $row['link_url'],
                       'link_url_jump'=> $row['link_url_jump'],
                       'link_desc'=> $row['link_desc'],
                       'link_url'=> $row['link_url'],
                       //select count(distinct(ips)) from wj_visitor_ip where from_linkid="&rs("id")&"  group by from_linkid
                       'ip_count'=> $row['ip_count'],
                       'order_finish'=>empty($order_finish[$row['id']][0]['amount'])?0:$order_finish[$row['id']][0]['amount'],

                       'last_complete_issue'=> empty($last_complete_issue[$row['id']][0]['amount'])?0:$last_complete_issue[$row['id']][0]['amount'],
                       'this_complete_issue'=> empty($this_complete_issue[$row['id']][0]['amount'])?0:$this_complete_issue[$row['id']][0]['amount'],//$GLOBALS['db']->getOne("SELECT SUM(order_amount) FROM ".ORDERINFO." WHERE month(FROM_UNIXTIME(pay_time,'%Y-%m-%d'))=month(curdate())  AND order_status > 0 and order_status < 9 and wj_linkid='".$row['id']."'"),
                       'this_pending_issue'=> empty($this_pending_issue[$row['id']][0]['amount'])?0:$this_pending_issue[$row['id']][0]['amount'],//$GLOBALS['db']->getOne("SELECT SUM(order_amount) FROM ".ORDERINFO." WHERE order_status=0 and wj_linkid='".$row['id']."'"),
                       'img'=> $row['img'],
                       'visit_count'=> $row['visit_count'],
                       'reg_count'=> $row['reg_count'],
                       'order_count'=> empty($order_count[$row['id']][0]['amount'])?0:$order_count[$row['id']][0]['amount'],//$GLOBALS['db']->getOne("SELECT count(*) FROM ".ORDERINFO." WHERE wj_linkid='".$row['id']."'"),
                       'adddate'=>local_date($GLOBALS['_CFG']['AM_time_format'], $row['adddate']),

                       //'total_fee'      => price_format($row['total_fee'], false),
                       //'handler'        => $row['handler']
					   );

    }
    //print_r($arr);
    return $arr;
}
/**
 * 取消一个用户订单
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return void
 */
function cancel_order($order_id, $user_id = 0)
{
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, order_id, order_sn , surplus , integral , bonus_id, order_status, shipping_status, pay_status FROM " .ORDERINFO ." WHERE order_id = '$order_id'";
    $order = $GLOBALS['db']->selectinfo($sql);

    if (empty($order))
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
        return false;
    }

    // 如果用户ID大于0，检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id)
    {
        $GLOBALS['err'] ->add($GLOBALS['_LANG']['no_priv']);

        return false;
    }

    // 订单状态只能是“未确认”或“已确认”
    if ($order['order_status'] != OS_UNCONFIRMED && $order['order_status'] != OS_CONFIRMED)
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['current_os_not_unconfirmed']);

        return false;
    }

    //订单一旦确认，不允许用户取消
    if ( $order['order_status'] == OS_CONFIRMED)
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['current_os_already_confirmed']);

        return false;
    }

    // 发货状态只能是“未发货”
    if ($order['shipping_status'] != SS_UNSHIPPED)
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['current_ss_not_cancel']);

        return false;
    }

    // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
    if ($order['pay_status'] != PS_UNPAYED)
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['current_ps_not_cancel']);

        return false;
    }

    //将用户订单设置为取消
    $sql = "UPDATE ".ORDERINFO ." SET order_status = '".OS_CANCELED."' WHERE order_id = '$order_id'";
    if ($GLOBALS['db']->query($sql))
    {
        /* 记录log */
        order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED,$GLOBALS['_LANG']['buyer_cancel'],'buyer');
        /* 退货用户余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['surplus'] > 0)
        {
            $change_desc = sprintf($GLOBALS['_LANG']['return_surplus_on_cancel'], $order['order_sn']);
            log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
        }
        if ($order['user_id'] > 0 && $order['integral'] > 0)
        {
            $change_desc = sprintf($GLOBALS['_LANG']['return_integral_on_cancel'], $order['order_sn']);
            log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
        }
        if ($order['user_id'] > 0 && $order['bonus_id'] > 0)
        {
            change_user_bonus($order['bonus_id'], $order['order_id'], false);
        }

        /* 如果使用库存，且下订单时减库存，则增加库存 */
        if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE)
        {
            change_order_goods_storage($order['order_id'], false);
        }

        /* 修改订单 */
        $arr = array(
            'bonus_id'  => 0,
            'bonus'     => 0,
            'integral'  => 0,
            'integral_money'    => 0,
            'surplus'   => 0
        );
        update_order($order['order_id'], $arr);

        return true;
    }
    else
    {
        die($GLOBALS['db']->errorMsg());
    }

}

/**
 * 确认一个用户订单
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return  bool        $bool
 */
function affirm_received($order_id, $user_id = 0)
{
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, order_sn , order_status, shipping_status, pay_status FROM ".ORDERINFO ." WHERE order_id = '$order_id'";

    $order = $GLOBALS['db']->selectinfo($sql);

    // 如果用户ID大于 0 。检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id)
    {
        $GLOBALS['err'] -> add($GLOBALS['_LANG']['no_priv']);

        return false;
    }
    /* 检查订单 */
    elseif ($order['shipping_status'] == SS_RECEIVED)
    {
        $GLOBALS['err'] ->add($GLOBALS['_LANG']['order_already_received']);

        return false;
    }
    elseif ($order['shipping_status'] != SS_SHIPPED)
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['order_invalid']);

        return false;
    }
    /* 修改订单发货状态为“确认收货” */
    else
    {
        $sql = "UPDATE " . ORDERINFO . " SET shipping_status = '" . SS_RECEIVED . "' WHERE order_id = '$order_id'";
        if ($GLOBALS['db']->query($sql))
        {
            /* 记录日志 */
            order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', $GLOBALS['_LANG']['buyer']);

            return true;
        }
        else
        {
            die($GLOBALS['db']->errorMsg());
        }
    }

}

/**
 * 保存用户的收货人信息
 * 如果收货人信息中的 id 为 0 则新增一个收货人信息
 *
 * @access  public
 * @param   array   $consignee
 * @param   boolean $default        是否将该收货人信息设置为默认收货人信息
 * @return  boolean
 */
function save_consignee($consignee, $default=false)
{
    if (!empty($consignee['address_id']))
    {
        /* 修改地址 */
        $res = $GLOBALS['db']->autoExecute(ADDR, $consignee, 'UPDATE', 'address_id = ' . $consignee['address_id']);
    }
    else
    {
        /* 添加地址 */
        $res = $GLOBALS['db']->autoExecute(ADDR, $consignee, 'INSERT');
        $consignee['address_id'] = $GLOBALS['db']->insertId();
    }

    if ($default)
    {
        /* 保存为用户的默认收货地址 */
        $sql = "UPDATE " . USERS .
            " SET address_id = '$consignee[address_id]' WHERE user_id = '$_SESSION[user_id]'";
        $res = $GLOBALS['db']->query($sql);
    }

    return $res !== false;
}

/**
 * 删除一个收货地址
 *
 * @access  public
 * @param   integer $id
 * @return  boolean
 */
function drop_consignee($id)
{
    $sql = "SELECT user_id FROM " .ADDR . " WHERE address_id = '$id'";
    $uid = $GLOBALS['db']->getOne($sql);

    if ($uid != $_SESSION['user_id'])
    {
        return false;
    }
    else
    {
        $sql = "DELETE FROM " .ADDR . " WHERE address_id = '$id'";
        $res = $GLOBALS['db']->query($sql);

        return $res;
    }
}

/**
 *  添加或更新指定用户收货地址
 *
 * @access  public
 * @param   array       $address
 * @return  bool
 */
function update_address($address)
{
    $address_id = intval($address['address_id']);
    unset($address['address_id']);

    if ($address_id > 0)
    {
         /* 更新指定记录 */
        $GLOBALS['db']->autoExecute(ADDR, $address, 'UPDATE', 'address_id = ' .$address_id);
    }
    else
    {
        /* 插入一条新记录 */
       // print_r($address);
        if(!empty($address))$GLOBALS['db']->autoExecute(ADDR, $address, 'INSERT');
        $address_id = $GLOBALS['db']->insertId();
    }

//var_dump(isset($address['defalut']) && $address['default'] > 0 );
    if ( isset($address['user_id']))
    {
        $sql = "UPDATE ".USERS .
                " SET address_id = '".$address_id."' ".
                " WHERE user_id = '" .$address['user_id']. "'";
        $GLOBALS['db'] ->query($sql);
        //echo $sql;
        $address['address_id'] = $address_id;
        $_SESSION['flow_consignee'] = $address;

    }

    return true;
}

/**
 *  获取指订单的详情
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return   arr        $order          订单所有信息的数组
 */
function get_order_detail($order_id, $user_id = 0)
{
    include_once(ROOT_PATH . 'lib/lib.f.order.php');

    $order_id = intval($order_id);
    if ($order_id <= 0)
    {
        //echo 'Orders you are looking for is not found';
        return 1;
    }
    $order = order_info($order_id);

    //检查订单是否属于该用户
    if ($user_id > 0 && $user_id != $order['user_id'])
    {
        //echo 'You are not allowed to see other people\'s orders';
        return 2;
    }

    /* 对发货号处理 */
    if (!empty($order['invoice_no']))
    {
         $shipping_code = $GLOBALS['db']->GetOne("SELECT shipping_code FROM ".$GLOBALS['ecs']->table('shipping') ." WHERE shipping_id = '$order[shipping_id]'");
         $plugin = ROOT_PATH.'includes/modules/shipping/'. $shipping_code. '.php';
         if (file_exists($plugin))
        {
              include_once($plugin);
              $shipping = new $shipping_code;
              $order['invoice_no'] = $shipping->query($order['invoice_no']);
        }
    }

    $order['allow_update_address'] = 0;

    /* 获取订单中实体商品数量 */
    $order['exist_real_goods'] = exist_real_goods($order_id);

    /* 如果是未付款状态，生成支付按钮 */
    if ($order['order_status'] < 1)
    {
        /*
         * 在线支付按钮
         */
        //支付方式信息
        $payment_info = array();
        $payment_info = payment_info($order['pay_id']);

        //无效支付方式
        if ($payment_info === false)
        {
            $order['pay_online'] = '';
        }
        else
        {
            //取得支付信息，生成支付代码
            // $payment = $payment_info['pay_config'];
			$payment = payment_info($order['pay_id']);
            $order['user_name'] = $_SESSION['user_name'];


            $order['pay_desc']  = $payment_info['pay_desc'];
			if ($payment_info['pay_code'] == 'CreditCard') $payment_info['pay_code'] = 'PayPal';
			if (empty($payment_info['pay_code'])) $payment_info['pay_code'] = 'PayPal';
           /* 调用相应的支付方式文件 */
           //echo $payment_info['pay_code'];
           //exit;
            include_once(ROOT_PATH . 'lib/modules/payment/' . $payment_info['pay_code'] . '.php');

            /* 取得在线支付方式的支付按钮 */
            $pay_obj    = new $payment_info['pay_code'];
            $order['pay_online'] = $pay_obj->get_code($order, $payment);
        }
    }
    else
    {
        $order['pay_online'] = '';
    }


	$order['order_status_str'] = $GLOBALS['_LANG']['os'][$order['order_status']];
    if($order['order_status']>2 && $order['order_status']<9){
	$shipping_web = read_static_cache('ship_query', ADMIN_STATIC_CACHE_PATH);
		$sql = "select * from ".SHIPDETAILS." where order_sn='".$order['order_sn']."'";
		$ship_info = $GLOBALS['db']->arrQuery($sql);
		foreach($ship_info as $k => $val){
			$shipping_method = $val['shipping_name'];
			$ship_info[$k]['display_tracking'] = $val['add_time'] > 1351724400;//2012-11-01 后显示物流轨迹 by mashanling on 2012-11-16 14:00:03
			$ship_info[$k]['add_time'] = local_date($GLOBALS['_CFG']['AM_time_format'], $val['add_time']);
			$ship_info[$k]['ship_url'] = !empty($shipping_web[$shipping_method]['url'])?$shipping_web[$shipping_method]['url']:'';
			$ship_info[$k]['ship_desc'] = $shipping_web[$shipping_method]['date'] . ' ' . $shipping_web[$shipping_method]['desc'];
			$ship_info[$k]['track_goods'] = !empty($val['track_goods'])?unserialize($val['track_goods']):'';
		}
		$GLOBALS['Arr']['ship_info'] = $ship_info;
	}


    /* 无配送时的处理 */
    $order['shipping_id'] == -1 and $order['shipping_name'] = $GLOBALS['_LANG']['shipping_not_need'];
    return $order;

}


/**
 *  将指定订单中的商品添加到购物车
 *
 * @access  public
 * @param   int         $order_id
 *
 * @return  mix         $message        成功返回true, 错误返回出错信息
 */
function return_to_cart($order_id)
{
    /* 初始化基本件数量 goods_id => goods_number */
    $basic_number = array();

    /* 查订单商品：不考虑赠品 */
    $sql = "SELECT goods_id, goods_number, goods_attr, parent_id" .
            " FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = '$order_id' AND is_gift = 0 AND extension_code <> 'package_buy'" .
            " ORDER BY parent_id ASC";
    $res = $GLOBALS['db']->query($sql);

    $time = gmtime();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        // 查该商品信息：是否删除、是否上架

        $sql = "SELECT goods_sn, goods_title, goods_number, market_price, " .
                "IF(is_promote = 1 AND '$time' BETWEEN promote_start_date AND promote_end_date, promote_price, shop_price) AS goods_price," .
                "is_real, extension_code, is_alone_sale, goods_type " .
                "FROM " . $GLOBALS['ecs']->table('goods') .
                " WHERE goods_id = '$row[goods_id]' " .
                " AND is_delete = 0 LIMIT 1";
        $goods = $GLOBALS['db']->selectinfo($sql);

        // 如果该商品不存在，处理下一个商品
        if (empty($goods))
        {
            continue;
        }

        // 如果使用库存，且库存不足，修改数量
        if ($GLOBALS['_CFG']['use_storage'] == 1 && $goods['goods_number'] < $row['goods_number'])
        {
            if ($goods['goods_number'] == 0)
            {
                // 如果库存为0，处理下一个商品
                continue;
            }
            else
            {
                // 库存不为0，修改数量
                $row['goods_number'] = $goods['goods_number'];
            }
        }

        // 如果有属性值，查询有效的属性值
        if ($row['goods_attr'] != '' && $goods['goods_type'] > 0)
        {
            $sql = "SELECT goods_attr_id " .
                    "FROM " . $GLOBALS['ecs']->table('attribute') . " AS a, " . $GLOBALS['ecs']->table('goods_attr') . " AS ga " .
                    "WHERE a.cat_id = '$goods[goods_type]' " .
                    "AND a.attr_id = ga.attr_id " .
                    "AND ga.goods_id = '$row[goods_id]' " .
                    "AND ga.goods_attr_id " . db_create_in($row['goods_attr']);
            $attr_id = $GLOBALS['db']->getCol($sql);
            $row['goods_attr'] = join(',', $attr_id);
        }

        //检查商品价格是否有会员价格
        $sql = "SELECT goods_number FROM" . $GLOBALS['ecs']->table('cart') . " " .
                "WHERE session_id = '" . SESS_ID . "' " .
                "AND goods_id = '" . $row['goods_id'] . "' " .
                "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
        $temp_number = $GLOBALS['db']->getOne($sql);
        $row['goods_number'] += $temp_number;

        $attr_array           = empty($attr_id) ? array() : explode(',', $attr_id);
        $goods['goods_price'] = get_final_price($row['goods_id'], $row['goods_number'], true, $attr_array);

        // 要返回购物车的商品
        $return_goods = array(
            'goods_id'      => $row['goods_id'],
            'goods_sn'      => addslashes($goods['goods_sn']),
            'goods_title'    => addslashes($goods['goods_title']),
            'market_price'  => $goods['market_price'],
            'goods_price'   => $goods['goods_price'],
            'goods_number'  => $row['goods_number'],
            'goods_attr'    => addslashes($row['goods_attr']),
            'is_real'       => $goods['is_real'],
            'extension_code'=> addslashes($goods['extension_code']),
            'parent_id'     => '0',
            'is_gift'       => '0',
            'rec_type'      => CART_GENERAL_GOODS
        );

        // 如果是配件
        if ($row['parent_id'] > 0)
        {
            // 查询基本件信息：是否删除、是否上架、能否作为普通商品销售
            $sql = "SELECT goods_id " .
                    "FROM " . $GLOBALS['ecs']->table('goods') .
                    " WHERE goods_id = '$row[parent_id]' " .
                    " AND is_delete = 0 AND is_on_sale = 1 AND is_alone_sale = 1 LIMIT 1";
            $parent = $GLOBALS['db']->selectinfo($sql);
            if ($parent)
            {
                // 如果基本件存在，查询组合关系是否存在
                $sql = "SELECT goods_price " .
                        "FROM " . $GLOBALS['ecs']->table('group_goods') .
                        " WHERE parent_id = '$row[parent_id]' " .
                        " AND goods_id = '$row[goods_id]' LIMIT 1";
                $fitting_price = $GLOBALS['db']->getOne($sql);
                if ($fitting_price)
                {
                    // 如果组合关系存在，取配件价格，取基本件数量，改parent_id
                    $return_goods['parent_id']      = $row['parent_id'];
                    $return_goods['goods_price']    = $fitting_price;
                    $return_goods['goods_number']   = $basic_number[$row['parent_id']];
                }
            }
        }
        else
        {
            // 保存基本件数量
            $basic_number[$row['goods_id']] = $row['goods_number'];
        }

        // 返回购物车：看有没有相同商品
        $sql = "SELECT goods_id " .
                "FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' " .
                " AND goods_id = '$return_goods[goods_id]' " .
                " AND goods_attr = '$return_goods[goods_attr]' " .
                " AND parent_id = '$return_goods[parent_id]' " .
                " AND is_gift = 0 " .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $cart_goods = $GLOBALS['db']->getOne($sql);
        if (empty($cart_goods))
        {
            // 没有相同商品，插入
            $return_goods['session_id'] = SESS_ID;
            $return_goods['user_id']    = $_SESSION['user_id'];
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $return_goods, 'INSERT');
        }
        else
        {
            // 有相同商品，修改数量
            $sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET " .
                    "goods_number = '" . $return_goods['goods_number'] . "' " .
                    ",goods_price = '" . $return_goods['goods_price'] . "' " .
                    "WHERE session_id = '" . SESS_ID . "' " .
                    "AND goods_id = '" . $return_goods['goods_id'] . "' " .
                    "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
            $GLOBALS['db']->query($sql);
        }
    }

    // 清空购物车的赠品
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND is_gift = 1";
    $GLOBALS['db']->query($sql);

    return true;
}

/**
 *  保存用户收货地址
 *
 * @access  public
 * @param   array   $address        array_keys(consignee string, email string, address string, zipcode string, tel string, mobile stirng, sign_building string, best_time string, order_id int)
 * @param   int     $user_id        用户ID
 *
 * @return  boolen  $bool
 */
function save_order_address($address, $user_id)
{
    $GLOBALS['err']->clean();
    /* 数据验证 */
    empty($address['consignee']) and $GLOBALS['err']->add($GLOBALS['_LANG']['consigness_empty']);
    empty($address['address']) and $GLOBALS['err']->add($GLOBALS['_LANG']['address_empty']);
    $address['order_id'] == 0 and $GLOBALS['err']->add($GLOBALS['_LANG']['order_id_empty']);
    if (empty($address['email']))
    {
        $GLOBALS['err']->add($GLOBALS['email_empty']);
    }
    else
    {
        if (!is_email($address['email']))
        {
            $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], $address['email']));
        }
    }
    if ($GLOBALS['err']->error_no > 0)
    {
        return false;
    }

    /* 检查订单状态 */
    $sql = "SELECT user_id, order_status FROM " .ORDERINFO. " WHERE order_id = '" .$address['order_id']. "'";
    $row = $GLOBALS['db']->selectinfo($sql);
    if ($row)
    {
        if ($user_id > 0 && $user_id != $row['user_id'])
        {
            $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
            return false;
        }
        if ($row['order_status'] != OS_UNCONFIRMED)
        {
            $GLOBALS['err']->add($GLOBALS['_LANG']['require_unconfirmed']);
            return false;
        }
        $GLOBALS['db']->autoExecute(ORDERINFO, $address, 'UPDATE', "order_id = '$address[order_id]'");
        return true;
    }
    else
    {
        /* 订单不存在 */
        $GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
        return false;
    }
}

/**
 *
 * @access  public
 * @param   int         $user_id         用户ID
 * @param   int         $num             列表显示条数
 * @param   int         $start           显示起始位置
 *
 * @return  array       $arr             红保列表
 */
function get_user_bouns_list($user_id, $num = 10, $start = 0)
{
    $sql = "SELECT u.bonus_sn, u.order_id, b.type_name, b.type_money, b.min_goods_amount, b.use_start_date, b.use_end_date ".
           " FROM " .$GLOBALS['ecs']->table('user_bonus'). " AS u ,".
           $GLOBALS['ecs']->table('bonus_type'). " AS b".
           " WHERE u.bonus_type_id = b.type_id AND u.user_id = '" .$user_id. "'";
    $res = $GLOBALS['db']->selectLimit($sql, $num, $start);
    $arr = array();

    $day = getdate();
    $cur_date = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        /* 先判断是否被使用，然后判断是否开始或过期 */
        if (empty($row['order_id']))
        {
            /* 没有被使用 */
            if ($row['use_start_date'] > $cur_date)
            {
                $row['status'] = $GLOBALS['_LANG']['not_start'];
            }
            else if ($row['use_end_date'] < $cur_date)
            {
                $row['status'] = $GLOBALS['_LANG']['overdue'];
            }
            else
            {
                $row['status'] = $GLOBALS['_LANG']['not_use'];
            }
        }
        else
        {
            $row['status'] = '<a href="user.php?act=order_detail&order_id=' .$row['order_id']. '" >' .$GLOBALS['_LANG']['had_use']. '</a>';
        }

        $row['use_startdate']   = local_date($GLOBALS['_CFG']['date_format'], $row['use_start_date']);
        $row['use_enddate']     = local_date($GLOBALS['_CFG']['date_format'], $row['use_end_date']);

        $arr[] = $row;
    }
    return $arr;

}

/**
 * 获得会员的团购活动列表
 *
 * @access  public
 * @param   int         $user_id         用户ID
 * @param   int         $num             列表显示条数
 * @param   int         $start           显示起始位置
 *
 * @return  array       $arr             团购活动列表
 */
function get_user_group_buy($user_id, $num = 10, $start = 0)
{
    return true;
}

 /**
  * 获得团购详细信息(团购订单信息)
  *
  *
  */
 function get_group_buy_detail($user_id, $group_buy_id)
 {
     return true;
 }

 //获取用户的可用积分
 function get_point_records($user_id, $num = 10, $start = 0,$where = '')
{
     //获取用户的可用积分
    $arr    = array();

    $sql = "SELECT * FROM ".POINT_RECORD .
           " WHERE user_id = '$user_id' $where ORDER BY adddate desc limit  $start,$num";
    //echo $sql;
           $res = $GLOBALS['db']->arrQuery($sql);
    foreach ($res as $k=>$v)
    {
    	$res[$k]['adddate'] = local_date($GLOBALS['_CFG']['AM_time_format'], $res[$k]['adddate']);
    }
    return $res;
}


/**
 *  添加或更新指定用户帐单地址
 *
 * @access  public
 * @param   array       $address
 * @return  bool
 */
function update_billing_address($address) {
    $address_id = intval($address['address_id']);
    unset($address['address_id']);

    if ($address_id > 0) {
         /* 更新指定记录 */
        $GLOBALS['db']->autoExecute(BILLADDR, $address, 'UPDATE', 'address_id = ' .$address_id);
    }
    else {
        /* 插入一条新记录 */
        $GLOBALS['db']->autoExecute(BILLADDR, $address, 'INSERT');
        $address_id = $GLOBALS['db']->insertId();
    }

    return true;
}

/*
 * 获取指定用户购买过没有写评论的产品 2013-10-30
 */
function get_no_review_goods($user_id,$page,$size) {
    global $db;
    //取出已经评论过的产品
    $goods_info = array();
    $data = array();
    $start = ($page-1)*$size;
    $sql = "select goods_id from ".REVIEW." as r  where r.user_id = '".$user_id."' group by goods_id";
    $review_id = $db->arrQuery($sql);  //已经评论过的产品id
    $r1 = array(); $r2 = array(); $r3 = array();
    if(!empty($review_id)) {
        foreach($review_id as $r) {
            $r1[] = $r['goods_id'];
        }
    }
    unset($review_id);
	/*
    $review_id = array();
    $rm_id =$db->arrQuery("select goods_id from ".REVIEW_MEDIA." where user_id = '".$user_id."' and type=0");
    if(!empty($rm_id)) {
        foreach($rm_id as $r) {
            $r2[] = $r['goods_id'];
        }
    }
    $video_rm_id =$db->arrQuery("select goods_id from ".REVIEW_MEDIA." where user_id = '".$user_id."' and type=1");
    if(!empty($video_rm_id)) {
        foreach($video_rm_id as $r) {
            $r3[] = $r['goods_id'];
        }
    }
    $review_id = array_intersect($r1,$r2); //取交集（文字、图片、视频都已经写）
    $review_id = array_intersect($r3,$review_id);
    */
	$review_id = $r1;
    $where = '';
    if(!empty($review_id)) {
        foreach ($review_id as $row) {
            $goods_info[] = $row;
        }
        if(!empty($goods_info)) {
            $where = " and g.goods_id not in (".implode(',',$goods_info).")";
        }
    }
    $count = $db->arrQuery("select og.order_id from ".GOODS." as g left join ".ODRGOODS." as og on g.goods_id =og.goods_id
            left join ".ORDERINFO." as r on r.order_id = og.order_id  where r.user_id = '".$user_id."' and order_status >0 and order_status <10 and g.is_on_sale =1 and g.goods_number >0 group by g.goods_id");
    $sql = "select g.goods_title,g.goods_thumb,g.url_title,g.goods_id,g.goods_grid from ".GOODS." as g left join ".ODRGOODS." as og on g.goods_id =og.goods_id
            left join ".ORDERINFO." as r on r.order_id = og.order_id where r.user_id = '".$user_id."' and order_status >0 and order_status <10  and g.is_on_sale =1 and g.goods_number >0 ".$where." group by g.goods_id order by
            og.order_id desc limit ".$start.",".$size;
    $order_goods = $db->arrQuery($sql); //取出购买过的产品
    if(!empty($order_goods)) {
        foreach($order_goods as $row) {
            $goods_id[] = $row['goods_id'];
            $row['url_title'] = get_details_link($row['goods_id'],$row['url_title']);
            $row['goods_grid'] = get_image_path($row['goods_id'],$row['goods_grid']);
            $row['shirt_title'] = sub_str($row['goods_title']);
            $row['goods_thumb'] = get_image_path($row['goods_id'],$row['goods_thumb']);
			$sql = "SELECT COUNT(goods_id) as review_count FROM ". REVIEW ." WHERE goods_id = ". $row['goods_id'] ." AND is_pass = 1";
			$res = $db->selectInfo($sql);
			if(!empty($res)) {
				$row['reviews']	= $res['review_count'];
			}
            $data['list'][] = $row;
        }

    }
    $data['count'] = count($data['list']);
    return $data;
}

//生成促销码
function randstr($length) {
   $hash = '';
   $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
   $max = strlen($chars) - 1;
   mt_srand((double)microtime() * 1000000);
   for($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
   }
   return $hash;
}
?>