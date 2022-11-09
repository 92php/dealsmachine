<?php
/**
 * class.analyze_order.php       网站订单分析类
 * 
 * @author                       mashanling(msl-138@163.com)
 * @date                         2011-08-30
 * @last modify                  2011-09-07 by mashanling
 */

!defined('INI_WEB') && exit('Access denied!');

class AnalyzeOrders extends Analyze {
    public $key_record = 'lastSetAnalyzeOrder';
    public $table      = ANALYZE_ORDERS;
    public $name       = '订单';
    static private $today_order_arr = array();
    
	/**
     * 获取当天订单数据
     * 
     * @param int $unixtime 当天unix gmt时间戳
     * 
     */
    static function getData($unixtime) {
        self::getTodayOrder($unixtime);    //当天订单信息
        $today_order_arr = self::$today_order_arr[$unixtime];
        $amount        = $today_order_arr['order_amount'];    //订单总额
        $single_arr    = self::getSingleOrders($today_order_arr['order_id'], $unixtime);    //单商品、多商品订单
        $multi_buy_arr = self::getMultiBuyAmount($today_order_arr['user_id'], $unixtime);
        $data          = array(
            'order_amount'         => round($amount, 2),    //订单总额
            'order_payed_amount'   => round($today_order_arr['order_payed_amount'], 2),    //已付款订单总额
            'order_nums'           => $today_order_arr['order_num'],             //订单总数
            'order_payed_nums'     => $today_order_arr['order_payed_num'],       //已付款订单数
        	'new_registers_orders' => $today_order_arr['new_registers_num'],     //新注册用户订单数
            'new_registers_amount' => round($today_order_arr['new_registers_amount'], 2),  //新注册用户订单总额
            'point_amount'	       => round($today_order_arr['point_amount'], 2),          //积分折扣总额
            'promotion_amount'     => self::getPromotionAmount($unixtime),    //代金券金额
            'lt_15_usd_nums'       => $today_order_arr['lt_15_usd_num'],      //小于15美金订单数
            'gt_100_usd_nums'	   => $today_order_arr['gt_100_usd_num'],     //大于100美金订单数
        	'gt_500_usd_nums'      => $today_order_arr['gt_500_usd_num'],     //大于500美金订单数
        
            'multi_buy_nums'	   => $multi_buy_arr['num'],                  //多次下单数
            'multi_buy_amount'     => round($multi_buy_arr['amount'], 2),               //多次下单总额
            'first_buy_nums'	   => $today_order_arr['order_num'] - $multi_buy_arr['num'],  //
            'first_buy_amount'     => round($amount - $multi_buy_arr['amount'], 2),  //
            'single_good_nums'     => $single_arr['single_num'],                   //单商品订单数
            'multi_good_nums'      => $single_arr['multi_num'],                    //多商品订单数
        
        );
        unset($today_order_arr, $single_arr, $multi_buy_arr, self::$today_order_arr[$unixtime]);
        return $data;
    } //end getData()
    
    /**
     * 获取单商品、多商品订单数
     * 
     * @param array $order_id 订单id
     * @param int   $unixtime 当天unix gmt时间戳
     */
    static private function getSingleOrders(&$order_id, $unixtime) {
        $single_num    = $multi_num    = 0;
        $single_amount = $multi_amount = 0.00;
        if (!empty($order_id)) {
            $sql        = 'SELECT COUNT(o.order_id) AS order_num,o.order_amount FROM ' . ORDERINFO . ' AS o JOIN ' . ODRGOODS . ' AS g ON o.order_id=g.order_id WHERE o.order_id IN(' . join(',', $order_id) . ') GROUP BY o.order_id';
            //echo __FUNCTION__ . $sql . PHP_EOL;
            $data       = $GLOBALS['db']->arrQuery($sql);
            foreach ($data as $v) {
                if ($v['order_num'] > 1) {
                    $multi_num++;
                    $multi_amount += $v['order_amount'];
                }
                else {
                    $single_num++;
                    $single_amount += $v['order_amount'];
                }
            }
            unset($data);
        }
        return array(
                'single_num'    => $single_num,
                'single_amount' => $single_amount,
                'multi_num'     => $multi_num,
                'multi_amount'  => $multi_amount,
        );
    } 
    
	/**
     * 获取当天历史多次下单数、总额
     * 
     * @notice 第一次下单、总数用对应总数相减即可
     * @param array $user_id  用户id
     * @param int   $unixtime 当天unix gmt时间戳
     */
    static private function getMultiBuyAmount($user_id, $unixtime) {
        $num = 0;
        $amount = 0.00;
        if (!empty($user_id)) {
            $sql  = 'SELECT DISTINCT(user_id) FROM ' . ORDERINFO . ' WHERE user_id IN(' . join(',', $user_id) . ") AND add_time<{$unixtime}";
            $data = $GLOBALS['db']->arrQuery($sql);    //多次下单
            //echo __FUNCTION__ . $sql . PHP_EOL;
            
            $multi_id = array();
            foreach ($data as $v) {
                $multi_id[] = $v['user_id'];    //多次下单用户id
            }
            if (!empty($multi_id)) {
                $sql    = 'SELECT SUM(order_amount) FROM ' . ORDERINFO . ' WHERE user_id IN(' . join(',', $multi_id) . ") AND add_time BETWEEN {$unixtime} AND " . ($unixtime + 86399);
                $amount = $GLOBALS['db']->getOne($sql);
                //echo __FUNCTION__ . $sql . PHP_EOL;
            }
            $num = count($multi_id);
        }
        return array('num' => $num, 'amount' => $amount);
    }
    
	/**
     * 获取当天订单信息
     * 
     * @param int    $unixtime        当天unix gmt时间戳
     */
    static private function getTodayOrder($unixtime) {
        $time_start = microtime(true);
        self::$today_order_arr[$unixtime] = array(
            'order_id'          => array(), //订单id
            'user_id'           => array(), //用户id
            'user_payed_id'     => array(), //已付款用户id
            'yuan_goods_amount' => 0.00,    //原商品价格
            'goods_amount'      => 0.00,    //打折后商品价格
            'order_amount'      => 0.00,    //订单总额
        	'order_payed_amount'=> 0.00,    //已付款订单总额
            'order_num'			=> 0,       //订单总数
            'order_payed_num'   => 0,       //已付款订单总数
            'promotion_num'     => 0,       //折扣券订单数
            'point_num'         => 0,       //积分订单数
        	'point_amount'      => 0,       //积分折扣总额
            'new_registers_num' => 0,       //新注册用户订单数
            'new_registers_amount' => 0,    //新注册用户订单总额
            'lt_15_usd_num'     => 0,       //小于15美金订单数
            'gt_100_usd_num'    => 0,       //大于100美金订单数
            'gt_500_usd_num'    => 0,       //大于500美金订单数
        
        );
        $sql   = 'SELECT o.order_id,o.user_id,o.yuan_goods_amount,o.goods_amount,o.order_amount,o.promotion_code,o.used_point,o.point_money,o.order_status,u.reg_time FROM ' . ORDERINFO . ' AS o JOIN ' . USERS . ' AS u ON o.user_id=u.user_id WHERE ' . self::getTimeWhere('o.add_time', $unixtime);
        $order = $GLOBALS['db']->arrQuery($sql);
        foreach ($order as $v) {
            $amount = $v['order_amount'];
            self::$today_order_arr[$unixtime]['order_id'][]             = $v['order_id'];
            self::$today_order_arr[$unixtime]['user_id'][]              = $v['user_id'];
            self::$today_order_arr[$unixtime]['yuan_goods_amount']     += $v['yuan_goods_amount'];
            self::$today_order_arr[$unixtime]['goods_amount']          += $v['goods_amount'];
            self::$today_order_arr[$unixtime]['order_amount']          += $amount;
            self::$today_order_arr[$unixtime]['point_amount']          += $v['point_money'];
            self::$today_order_arr[$unixtime]['order_num']++;
            
            //$v['promotion_code'] != '' && self::$today_order_arr[$unixtime]['promotion_num']++;
            $v['used_point'] > 0 && self::$today_order_arr[$unixtime]['point_num']++;
            $amount < 15  && self::$today_order_arr[$unixtime]['lt_15_usd_num']++;
            $amount > 100 && self::$today_order_arr[$unixtime]['gt_100_usd_num']++;
            $amount > 500 && self::$today_order_arr[$unixtime]['gt_500_usd_num']++;
            
            if ($v['reg_time'] > $unixtime && $v['reg_time'] < $unixtime + 86399) {    //当天注册
                self::$today_order_arr[$unixtime]['new_registers_num']++;
                self::$today_order_arr[$unixtime]['new_registers_amount'] += $amount;
            }
            if ($v['order_status'] > 0 && $v['order_status'] < 9) {    //已付款
                self::$today_order_arr[$unixtime]['user_payed_id'][] = $v['user_id'];
                self::$today_order_arr[$unixtime]['order_payed_num']++;
                self::$today_order_arr[$unixtime]['order_payed_amount'] += $amount;
            }
            
        }//end foreach
        //echo '获取' . local_date('Y-m-d', $unixtime) . '订单结束，用时' . execute_time($time_start) . PHP_EOL;
        //echo PHP_EOL;
        unset($order);
    }//end getTodayOrder()
    
	/**
     * 统计折扣券金额
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getPromotionAmount($unixtime) {
        return self::getTodaySUM(ORDERINFO . ' AS o JOIN ' . PCODE . ' AS c ON o.promotion_code=c.code', 'o.add_time', 'o.order_amount', $unixtime, " AND is_applay=0 AND promotion_code!=''");
    }
}
?>