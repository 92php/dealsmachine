<?php
/**
 * 保存ERP同步过来的的订单物流单号（新增）
 *
 * @file                    add_shipping_info.php
 */
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require('../lib/lib.f.order.php');
require_once('../lib/lib.f.transaction.php');
include(ROOT_PATH.'languages/en/user.php');
$shipping_web = read_static_cache('ship_query', ADMIN_STATIC_CACHE_PATH);
$returnArr = array();
$fstr = $_REQUEST['xxx'];
//file_put_contents('post_add_shipping_20140307.txt',var_export($_POST,true).'\t\r<br><br><br><br>',FILE_APPEND);  //写入日志
//$fstr = 'DD1401180315084699,,13939249473385,2014-03-06 18:25:05,1,OR0032201_1;';
if(empty($fstr)) {
    exit('parameters error!');
}
else {
	//handling the request url
    $qstr = urldecode($fstr);
    // explode $qstr as array
    $transArray = explode(";",trim($qstr));
	$log = '';
    foreach($transArray as $perRow) {
        $perArr = explode (',', $perRow );
        $order_sn = $perArr[0];		//订单编号
        $ship_method = $perArr[1];		//物流方式
        $track_no = $perArr[2];			//物流单号
        $returnArr[] = $perArr[2];
        $shippping_time = local_strtotime($perArr[3]);	//发货时间
        $is_display_track_goods = $perArr[4];	//是否是订单的最后一个物流单号，在商城订单详情页显示物流单号的商品明细（0:不显示，1：显示）
        $track_goods_arr1 = explode("|",$perArr[5]);		//物流单对应商品信息
        $track_goods_arr2 = array();
        $ship_sn = array();
        foreach ($track_goods_arr1 as $key => $res) {
            $list_goods = explode("_",$res);
            $ship_sn[] =$track_goods_arr2[$key]['goods_sn'] = $list_goods[0];
            $track_goods_arr2[$key]['goods_num'] = $list_goods[1];
        }
        $track_goods_str = serialize($track_goods_arr2);	//序列化之后的物流单对应商品信息

        //订单号，货运方式，追踪号码，发货时间
        //order_sn, shipping_method,Tracking_NO,shipping_time
        /*
        1。运单号的录入
        2。订单状态的改成发货状态。 order_status = 3
        3。积分更新   a.积分计算 b.积分消费记录
        */
        $sm = trim(strtoupper($ship_method));
        $is_py = ($sm != 'EMS' && $sm != 'UPS' && $sm != 'DHL' && $sm != 'FEDEX')?TRUE:FALSE;
        $_Arr['shipping_name']   = $ship_method;
        $_Arr['shipping_no']   = $track_no;
        $_Arr['order_sn']      = $order_sn;
        $_Arr['add_time']      = $shippping_time;
        $_Arr['demo']          = '';
        $_Arr['track_goods']    = $track_goods_str;
        $sql = "select order_id,pay_time,order_status,user_id,country from ".ORDERINFO." where order_sn='".$order_sn."'";
        $odrArr = $db->selectinfo($sql);
        $is_sent_mail = 1;  //已发送邮件
        if (!empty($odrArr['order_id'])) {
            $sql_count = "select count(*) from ".SHIPDETAILS." where shipping_no = '".$_Arr['shipping_no']."' and order_sn = '".$order_sn."' ";
            if (!$db->getOne($sql_count)) {
                $is_sent_mail = 0;//没有记录则未发
                $db->autoExecute(SHIPDETAILS, $_Arr);
            }
        }
        $new_order_status = '3';
        //修复支付时间
        $pay_time = '';
        if (empty($odrArr['pay_time'])) {
            $pay_time = " , pay_time = '".gmtime()."'";
        }
        $is_display = '';
        if($is_display_track_goods) {
            $is_display = " , is_display_track_goods = 1 ";
        }
        $get_points = caculate_order_get_point($order_sn);  //计算订单可以获得的积分数
        $sql_status = " order_status = '$new_order_status' $pay_time $is_display ";
        $db->update(ORDERINFO,$sql_status," order_sn = '".$order_sn."'");
        if(is_array($odrArr)) {
            act_caculate_point($new_order_status,$odrArr['order_status'],$odrArr['user_id'],$get_points,$order_sn); //积分加减
        }
        if (!empty($odrArr['user_id']) && 0==$is_sent_mail) {
			//获得收货人信息
            $sql_user = "SELECT email , firstname, lang FROM " . USERS . " WHERE user_id = " . $odrArr['user_id'];
            $user_info =  $db->selectinfo($sql_user);
			$firstname = $user_info['firstname'];
			if(empty($firstname)) {
				$firstname = $_LANG['my_friend'];
				if(empty($firstname)) $firstname = 'my friend';
			}
			if(!empty($ship_method)) {
				$ship_url = $shipping_web[$ship_method]['url'];
				$ship_date = $shipping_web[$ship_method]['date'];
			}
            $email = $user_info['email'];
            $lang      = $user_info['lang'];
            if(empty($lang)){
                $lang = 'en';
                $typ_arr = read_static_cache('category_c_key',2);
            }else{
                $typ_arr = read_static_cache('category_c_key_'.$lang,2);
            }
            //已经发送的产品
            $sql_f = "select * from ".SHIPDETAILS." where shipping_no != '".$_Arr['shipping_no']."' and order_sn = '".$order_sn."' ";
			$log .= '己发货：'. $sql_f .'<br><br><br><br>';
            $ship_goods_array = $db->arrQuery($sql_f);

            $old_ship_goods = array();
            if(!empty($ship_goods_array)){
                foreach($ship_goods_array  as $key=>$row){
                    $old_ship_goods[$key]['shipping_name'] = $row['shipping_name'];
					$old_ship_goods[$key]['shipping_no'] = $row['shipping_no'];
                    $goods_list = unserialize($row['track_goods']);
                    if($goods_list){
                        foreach($goods_list as $key_g=>$val){
							$goods_sn = $val['goods_sn'];
							$goods_num = $val['goods_num'];
							$sql_s = "SELECT eoi.order_id,eoi.order_sn,eog.goods_price,eog.goods_id,eog.goods_name,eog.goods_number,eog.goods_sn
									FROM eload_order_info eoi LEFT JOIN eload_order_goods eog ON eoi.order_id = eog.order_id
									WHERE eoi.order_sn = '". $order_sn ."'
									AND eog.goods_sn = '". $goods_sn ."'";
                            $shiped_goods = $db->selectInfo($sql_s);
							$shiped_goods_list[$key_g]['goods_title']  = $shiped_goods['goods_name'];
							$shiped_goods_list[$key_g]['url_title']    = 'http://www.dealsmachine.com/best_'. $shiped_goods['goods_id'] .".html";
							$shiped_goods_list[$key_g]['goods_sn']     = $shiped_goods['goods_sn'];
							$shiped_goods_list[$key_g]['goods_number'] = $goods_num;
							$shiped_goods_list[$key_g]['goods_price']  = $shiped_goods['goods_price'];
							$shiped_goods_list[$key_g]['subtotal']     = price_format($goods_num*$shiped_goods['goods_price']);
                            $ship_sn[] = $val['goods_sn'];  //已经发送的goods_sn
                            $old_ship_goods[$key]['goods_list'] = $shiped_goods_list;
                        }
						unset($shiped_goods_list);
                    }
                    unset($goods_list);
                }
            }

            //当前发送的产品
            if(!empty($track_goods_arr2)){
                foreach($track_goods_arr2 as $a=>$arr){
					$goods_num_c = $arr['goods_num'];
					if(empty($goods_num_c)) $goods_num_c = 1;
                    /*
					$sql_cu = "SELECT eoi.order_id,eoi.order_sn,eog.goods_price,eog.goods_id,eog.goods_name,eog.goods_number,eog.goods_sn
							FROM eload_order_info eoi LEFT JOIN eload_order_goods eog ON eoi.order_id = eog.order_id
							WHERE eoi.order_sn = '". $order_sn ."'
							AND eog.goods_sn = '". $arr['goods_sn'] ."'";
                    */
                    $sql_cu = "SELECT goods_id,goods_title,goods_sn,shop_price FROM eload_goods WHERE goods_sn = '". $arr['goods_sn'] ."' LIMIT 1";
                    $track_goods_list = $db->selectInfo($sql_cu);
                    $track_goods_arr2[$a]= $track_goods_list;
					$track_goods_arr2[$a]['goods_title'] = $track_goods_list['goods_title'];
                    $track_goods_arr2[$a]['url_title']   = 'http://www.dealsmachine.com/best_'. $track_goods_list['goods_id'] .".html";
					$track_goods_arr2[$a]['goods_sn']    = $track_goods_list['goods_sn'];
					$track_goods_arr2[$a]['goods_num']   = $goods_num_c;
					$track_goods_arr2[$a]['goods_price'] = $track_goods_list['shop_price'];
                    $track_goods_arr2[$a]['subtotal'] = price_format($track_goods_list['shop_price']*$goods_num_c);
                }
            }
            //未发送的产品
            $un_ship_goods = get_partial_goods($order_sn);
            //订单详情
			$order_detail = get_order_detail($odrArr['order_id'], 0);
            $shipping_note = 0;
            if(41 != $odrArr['country'] && 'DHL' == $ship_method) {
                $shipping_note = 1;
            }
            //发送发货邮件
            require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
            $mail_subject = str_replace('{$order_sn}',$order_sn,$mail_conf[$lang][18]);
            $Tpl->assign("order_id",$odrArr['order_id']);
            $Tpl->assign("order_sn",$order_sn);
            $Tpl->assign('date',$ship_date);
            $Tpl->assign("firstname",$firstname);
            $Tpl->assign('ship_goods',$track_goods_arr2);
            $Tpl->assign('order',$order_detail);
            $Tpl->assign('un_ship_goods',$un_ship_goods);
            $Tpl->assign('old_ship_goods',$old_ship_goods);
            $Tpl->assign("Tracking_NO",$track_no);
            $Tpl->assign("Tracking_web",$ship_method);
            $Tpl->assign("shipping_note",$shipping_note);
            $Tpl->assign("is_py",$is_py);
			$Tpl->assign('email',$email);
			$Tpl->assign('ship_url',$ship_url);
			$Tpl->assign('recommend_goods', get_mail_template_goods(18, $mail_conf));
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/18.html');
			//file_put_contents('post_add_shipping_mail_body_20140307.txt',$mail_body.'\t\r<br><br><br><br>',FILE_APPEND);  //写入日志
			//file_put_contents('post_add_shipping_log_20140307.txt',$log.'\t\r<br><br><br><br>',FILE_APPEND);  //写入日志
			if(exec_send($email,$mail_subject,$mail_body)){
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
				'template_id' => 18,
				'pri' => 5,
				'state' => $email_send_state,
				'last_send' => time()
			);
			add_mail_log($data);
			//exec_send('snipersheep@aliyun.com',$mail_subject,$mail_body,18);
			//sleep(5);
			//exec_send('senv-1988@163.com',$mail_subject,$mail_body,18);
            //exec_send('jiangzheng2013@hotmail.com',$mail_subject,$mail_body);
        }
        unset($old_ship_goods);
        unset($un_ship_goods);
        unset($track_goods_arr2);
        unset($mail_body);
    }
}
$restr = implode(',',$returnArr);
echo $restr;
exit;

//计算订单有效的积分数
function caculate_order_get_point($order_sn){
	//require_once('../lib/lib.f.transaction.php');
	global $db;
	$order_info = $GLOBALS['db']->selectInfo("select order_id,point_money,order_sn,promotion_code,add_time from ".ORDERINFO." where order_sn='$order_sn'");

    //判断是不是代金券的
	$sql = "select is_applay from eload_promotion_code where code = '".$order_info['promotion_code']."'";
    $is_applay = $GLOBALS['db']->getOne($sql);

	if(is_array($order_info)&&$order_info['point_money'] == 0 && empty($is_applay)){

	    $sql = "select order_id from ".ORDERINFO." where order_sn = '$order_sn' ";
	    $order_id = $db->getOne($sql);
	    if ($order_id === false){
			return 0;
	     }
			/* 订单详情 */

		/*获取订单返回的积分
	     * 用当前产品表中的该产品point_rate乘以order_goods表中的goods_price
	     * */
		$sql = "SELECT o.goods_price as product_price, o.goods_number AS goods_number, g.point_rate FROM " . ODRGOODS . " AS o " . "LEFT JOIN " . GOODS . " AS g ON o.goods_id = g.goods_id  WHERE o.order_id = {$order_id} ";
		$arr = $db->arrQuery ( $sql );
		$pointtotal = '';
		foreach ( $arr as $row ) {
			$pointtotal += price_format ( $row ['product_price'] * $row ['goods_number'] * $row ['point_rate'] );
		}

        //2013.11周末送双倍积分
        $order_time = local_date($GLOBALS['_CFG']['AM_time_format'], $order_info['add_time']);////美国时区 30/10/2013 18:14:42 PM
        $order_time_arr = explode(' ',$order_time);
        $order_time_arr_1 = explode('/',$order_time_arr[0]);
        if($order_time_arr_1[2]=='2013')
        {
            //11月
            if($order_time_arr_1[1]=='11' && ($order_time_arr_1[0]=='02' || $order_time_arr_1[0]=='03' || $order_time_arr_1[0]=='09' || $order_time_arr_1[0]=='10' || $order_time_arr_1[0]=='16' || $order_time_arr_1[0]=='17' || $order_time_arr_1[0]=='23' || $order_time_arr_1[0]=='24' || $order_time_arr_1[0]=='30'))
            {
                $pointtotal = $pointtotal*2;
            }
            //12月
            if($order_time_arr_1[1]=='12' && $order_time_arr_1[0]=='01')
            {
                $pointtotal = $pointtotal*2;
            }
        }


		return $pointtotal;

	}
	else {
		return 0;
	}
}

//获取部分未发货产品数据
function get_partial_goods($order_sn) {
    global $db;
    //订单产品
    $order_info   = $db->selectinfo('SELECT o.order_id,u.email FROM ' . ORDERINFO . ' AS o JOIN ' . USERS . " AS u ON o.user_id=u.user_id WHERE o.order_sn='{$order_sn}'");
    $order_id     = $order_info['order_id'];
    $order_detail = get_order_detail($order_id, 0);
    $goods_list   = order_goods($order_id);
    //订单己发货产品
    $sql = "SELECT * FROM ". SHIPDETAILS ." WHERE order_sn = '". $order_sn ."'";
    $ship_goods_array = $db->arrQuery($sql);
    $old_ship_goods = array();
    if(!empty($ship_goods_array)){
        foreach($ship_goods_array  as $key=>$row){
            $old_ship_goods[$key]['shipping_name'] = $row['shipping_name'];
            $old_ship_goods[$key]['shipping_no'] = $row['shipping_no'];
            $goods_list_1 = unserialize($row['track_goods']);
            if($goods_list_1){
                foreach($goods_list_1 as $key_g=>$val){
                    $goods_sn = $val['goods_sn'];
                    $goods_num = $val['goods_num'];
                    $sql_s = "SELECT eoi.order_id,eoi.order_sn,eog.goods_price,eog.goods_id,eog.goods_name,eog.goods_number,eog.goods_sn
                            FROM eload_order_info eoi LEFT JOIN eload_order_goods eog ON eoi.order_id = eog.order_id
                            WHERE eoi.order_sn = '". $order_sn ."'
                            AND eog.goods_sn = '". $goods_sn ."'";
                    $shiped_goods = $db->selectInfo($sql_s);
                    $shiped_goods_list[$key_g]['goods_title']  = $shiped_goods['goods_name'];
                    $shiped_goods_list[$key_g]['url_title']    = 'http://www.dealsmachine.com/best_'. $shiped_goods['goods_id'] .".html";
                    $shiped_goods_list[$key_g]['goods_sn']     = $shiped_goods['goods_sn'];
                    $shiped_goods_list[$key_g]['goods_number'] = $goods_num;
                    $shiped_goods_list[$key_g]['goods_price']  = $shiped_goods['goods_price'];
                    $shiped_goods_list[$key_g]['subtotal']     = price_format($goods_num*$shiped_goods['goods_price']);
                    $ship_sn[] = $val['goods_sn'];  //已经发送的goods_sn
                    $old_ship_goods[$key]['goods_list'] = $shiped_goods_list;
                }
                unset($shiped_goods_list);
            }
        }
    }
    /*
    //订单产品SKU
    foreach($goods_list as $key=>$value) {
        $goods_sn_list[] = $value['goods_sn'];
    }
    */
    //订单产品SKU，数量
    foreach($goods_list as $key=>$value) {
        $goods_sn = $value['goods_sn'];
        $goods_sn_num_list[$goods_sn]['goods_sn'] = $goods_sn;
        $goods_sn_num_list[$goods_sn]['goods_id'] = $value['goods_id'];
        $goods_sn_num_list[$goods_sn]['goods_number'] = $value['goods_number'];
        $goods_sn_num_list[$goods_sn]['goods_price'] = $value['goods_price'];
        $goods_sn_num_list[$goods_sn]['goods_name'] = $value['goods_name'];
        $goods_sn_num_list[$goods_sn]['goods_title'] = $value['goods_title'];
        $goods_sn_num_list[$goods_sn]['url_title'] = 'http://www.dealsmachine.com/best_'. $value['goods_id'] .".html";
        $goods_sn_num_list[$goods_sn]['goods_attr'] = $value['goods_attr'];
    }
    //己发货订单转为一维数组
    foreach($old_ship_goods as $key=>$old_value) {
        foreach($old_value['goods_list'] as $key=>$value) {
            $goods_list_old[$value['goods_sn']] = $value;
        }
    }
    //未发货订单产品
    foreach($goods_sn_num_list as $key=>$value) {
        if(isset($goods_list_old[$key])) {
            $number = $value['goods_number'] - $goods_list_old[$key]['goods_number'];
        } else {
            $number = $value['goods_number'];
        }
        if($number > 0) {
            $un_ship_goods[$key]['goods_id'] = $value['goods_id'];
            $un_ship_goods[$key]['goods_sn'] = $value['goods_sn'];
            $un_ship_goods[$key]['goods_number'] = $number;
            $un_ship_goods[$key]['goods_price'] = $value['goods_price'];
            $un_ship_goods[$key]['goods_name'] = $value['goods_name'];
            $un_ship_goods[$key]['goods_title'] = $value['goods_title'];
            $un_ship_goods[$key]['url_title'] = 'http://www.dealsmachine.com/best_'. $value['goods_id'] .".html";
            $un_ship_goods[$key]['subtotal'] = $number*$value['goods_price'];
            $un_ship_goods[$key]['goods_attr'] = $value['goods_attr'];
            $un_ship_goods[$key]['goods_attr'] = '';
            $un_ship_goods[$key]['main_goods_id'] = '';
        }
    }
    return $un_ship_goods;
}