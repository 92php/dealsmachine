<?php
/**
 * class.analyze.php               网站客户分析类
 * 
 * @author                         mashanling(msl-138@163.com)
 * @date                           2011-08-30
 * @last modify                    2011-09-07 by mashanling
 */

!defined('INI_WEB') && exit('Access denied!');

class AnalyzeCustomers extends Analyze {
    public $key_record = 'lastSetAnalyzeCustomers';
    public $table      = ANALYZE_CUSTOMERS;
    public $name       = '客户';
    /**
     * 获取当天客户统计数据
     * 
     * @param int $unixtime 当天unix gmt时间戳
     * 
     */
    static function getData($unixtime) {
        $user_orders   = self::getUserOrders($unixtime);      //新老用户下单数
        $data          = array(
            'new_registers'     => self::getNewRegisters($unixtime),    //新注册用户数
            'old_logins'        => self::getOldLogins($unixtime),       //老用户登陆数
            'first_orders'      => $user_orders['first'],    //第一次购买
            'multi_orders'	    => $user_orders['multi'],    //多次购买
            'new_registers_orders' => $user_orders['reg']    //新注册用户下单数
        ); 
        unset($user_orders);
        return $data;
    }
    
    /**
     * 获取新注册客户数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getNewRegisters($unixtime) {
        return self::getTodayNums(USERS, 'reg_time', $unixtime);
    }
    
    /**
     * 获取老客户登陆数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getOldLogins($unixtime) {
        return self::getTodayNums(USERS, 'last_login', $unixtime, ' AND reg_time<' . $unixtime);
    }
    
    /**
     * 获取订单数，包括第一次购买的客户数、多次购买客户数、新注册客户下单数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getUserOrders($unixtime) {
        //当天下单sql
        $sql = 'SELECT o.user_id,o.order_status,u.reg_time FROM ' . ORDERINFO . ' AS o JOIN ' . USERS . ' AS u ON o.user_id=u.user_id WHERE ' . self::getTimeWhere('o.add_time', $unixtime) . ' GROUP BY o.user_id';
        //return $sql;
        $today_arr = $GLOBALS['db']->arrQuery($sql);
        $first_num = $multi_num = $reg_num = 0;
        $user_id = array();
        foreach ($today_arr as $k => $v) {
            if ($v['order_status'] > 0 && $v['order_status'] < 9) {    //购买用户id
                $user_id[] = $v['user_id'];
            }
            
            //当日注册用户下单数
            if ($v['reg_time'] >= $unixtime && $v['reg_time'] < $unixtime + 86399) {
                $reg_num++;
            }
        }
        $history   = self::getHistoryBuyNums($user_id, $unixtime);    //第一次购买，多次购买
        $first_num = $history['first'];
        $multi_num = $history['multi'];
        unset($today_arr, $history, $user_id);
        
        return array('first' => $first_num, 'multi' => $multi_num, 'reg' => $reg_num);
    }
    
    /**
     * 获取当天历史第一次，大于一次购买客户数
     * 
     * @param array $user_id  用户id
     * @param int   $unixtime 当天unix gmt时间戳
     */
    static private function getHistoryBuyNums(&$user_id, $unixtime) {
        $user_id   = array_unique($user_id);
        $first_num = $multi_num = 0;
        if (!empty($user_id)) {
            $sql       = 'SELECT COUNT(DISTINCT(user_id)) FROM ' . ORDERINFO . ' WHERE user_id IN(' . join(',', $user_id) . ") AND add_time<{$unixtime} AND order_status BETWEEN 1 AND 8";
            $multi_num = $GLOBALS['db']->getOne($sql);
            $first_num = count($user_id) - $multi_num;
            //echo $sql, PHP_EOL;
        }
        return array('first' => $first_num, 'multi' => $multi_num);
    }
}
?>