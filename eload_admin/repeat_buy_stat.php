<?php
/**
 * 重复购买统计
 * 非安全版本,即非目录设置权限,增加日志
 *
 * @file                repeat_buy_stat.php
 * @author              mashanling <msl-138@163.com>
 * @date                2013-12-10 15:22:02
 * @lastmodify          $Date: 2014-01-06 09:32:00 +0800 (周一, 2014-01-06) $ $Author: msl $
 */

set_time_limit(0);
define('INI_WEB', true);
require(dirname(dirname(__FILE__)) . '/lib/global.php');
require(ROOT_PATH . 'lib/class.filter.php');

if (!isset($_GET['crontab'])) {
    require(ROOT_PATH . 'lib/is_loging.php');
}

require(ROOT_PATH . 'lib/time.fun.php');

$action             = Filter::string('action', INPUT_GET, 'list');
$method             = $action . 'Action';
$repeat_buy_stat    = new RepeatBuyStat();

if ('list' == $action) {
    $data = $repeat_buy_stat->listAction();
    $Arr['data'] = $data[0];
    $Arr['type'] = $data[1];
    $Arr['type_arr'] = array('请选择') + $data[2];
    temp_disp();
}
elseif (!method_exists($repeat_buy_stat, $method)) {
    /*$log = Logger::filename(LOG_INVALID_PARAM);
    trigger_error($log . '请求方法不存在“' . $action . '”');*/

    sys_msg('非法访问', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
}
else {
    call_user_func(array($repeat_buy_stat, $method));
}

class RepeatBuyStat {
    /**
     * 导入订单最小id,从数据库中查询出来
     */
    const MIN_ORDER_ID = 875790;

    /**
     * 导入用户最小id,从数据库中查询出来
     */
    const MIN_USER_ID = 765064;

    /**
     * @var object $_db 数据库实例
     */
    private $_db;

    /**
     * @var string $_table_order 统计订单表
     */
    private $_table_order;

    /**
     * @var string $_table_user 统计用户表
     */
    private $_table_user;

    /**
     * @var string $_insert_data_cache_filename 导入订单信息及用户信息数据缓存文件名
     */
    private $_insert_data_cache_filename = 'insert_data';

    /**
     * @var string $_data_cache_path 缓存数据保存路径
     */
    private $_data_cache_path;

    /**
     * @var string $_filename 当前文件名
     */
    private $_filename;

    /**
     * @var array $_months 月份
     */
    private $_months;

    /**
     * @var array $_type_arr 统计类型
     */
    private $_type_arr = array(
        1 => '单月',
        2 => '双月',
        3 => '三个月',
        6 => '六个月'
    );

    /**
     * @var string were时间格式
     */
    private $_where_time_format = '%s BETWEEN %d AND %d';

    /**
     * @var string $_website 网站网址
     */
    private $_website;

    /**
     * 按文件名倒序文件
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-19 14:26:20
     *
     * @param array $filename1 文件1
     * @param array $filename2 文件2
     *
     * @return int -1,0,1
     */
    private function _cmp($a, $b) {
        return strnatcmp($b, $a);
    }

    /**
     * 获取数据
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-12 09:29:40
     *
     * @param int $type 类型
     *
     * @return array 数据信息
     */
    private function _getData($type) {
        $return     = array();
        $data_path  = str_replace(ROOT_PATH, '', $this->_data_cache_path . $type . '/');
        $sql        = 'SELECT content FROM ' . MEM_CACHE . " WHERE filename LIKE '{$data_path}%'";

        $this->_db->query();

        while($row = $this->_db->fetchArray()) {
            $return[$filename] = unserialize($row['content']);
        }

        if ($return) {
            uksort($return, array(&$this, '_cmp'));
        }

        return $return;
    }//end _getData

    /**
     * 历史重复购买率 = 统计时间段内下单的客户历史曾有其他订单（包括统计时间段内的订单）/所有下单客户
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 15:11:06
     *
     * @return array 客户信息array(客户总数,用时)
     */
    private function _historyBuyUsers() {
        G($key = __FUNCTION__);
        $total  = 0;
        $data   = array_chunk($this->_total_users_id, 10000);
        $sql    = 'SELECT COUNT(order_id) AS num FROM '  . $this->_table_order;
        $sql   .= ' WHERE add_time<=' . $this->_end_time;
        $sql   .= ' AND user_id IN(%s)';
        $sql   .= ' GROUP BY user_id HAVING(num)>1';

        foreach($data as $k => &$item) {
            $this->_db->query(sprintf($sql, join(',', $item)));
            $total += $this->_db->numRows();
        }

        return array($total, G($key, $key . 'end'));
    }

    /**
     * 导入用订单信息
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 13:43:35
     *
     * @param int $min_order_id 最小订单id
     *
     * @return array 导入订单信息array(本次导入最大id,导入数)
     */
    private function _inserOrders($min_order_id) {

        $where  = ' WHERE order_id>' . $min_order_id . ' AND add_time<' . (gmtime() - 3 * 86400);
        $sql    = 'INSERT INTO ' . $this->_table_order . ' SELECT order_id,user_id,add_time,order_status FROM ' . ORDERINFO . $where;

        if ($this->_db->query($sql)) {
            $orders = $this->_db->affectedRows();;//导入数
            $order_id = $this->_db->getOne('SELECT MAX(order_id) FROM ' . ORDERINFO . $where);//最大id
            $this->_db->query('DELETE FROM ' . $this->_table_order . $where . ' AND order_status=0');//删除未付款

            return array($order_id, $orders);
        }
        else {
            $this->_db->query('DELETE FROM ' . $this->_table_order . $where);
            $log = '';//Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            throw new Exception($log . '导入订单信息失败: ' . $this->_db->Error);
        }
    }

    /**
     * 导入用户信息
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 13:43:35
     *
     * @param int $min_user_id 最小用户id
     *
     * @return array 导入用户信息array(本次导入最大id,导入数)
     */
    private function _insertUsers($min_user_id) {

        $where  = ' WHERE user_id>' . $min_user_id;
        $sql    = 'INSERT INTO ' . $this->_table_user . ' SELECT user_id,reg_time FROM ' . USERS . $where;

        if ($this->_db->query($sql)) {
            $users = $this->_db->affectedRows();//导入数
            $user_id = $this->_db->getOne('SELECT MAX(user_id) FROM ' . USERS);//最大id

            return array($user_id, $users);
        }
        else {
            $this->_db->query('DELETE FROM ' . $this->_table_user . $where);
            $log = '';//Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            throw new Exception($log . '导入用户信息失败: ' . $this->_db->Error);
        }
    }

    /**
     * 下单新注册客户数：统计时间段内注册的，并且下单的客户总数量
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 14:47:00
     *
     * @return array 客户信息array(客户总数,用时)
     */
    private function _newOrderUsers() {
        G($key = __FUNCTION__);
        $sql    = 'SELECT COUNT(DISTINCT u.user_id) FROM '  . $this->_table_order . ' AS o JOIN ';
        $sql   .= $this->_table_user . ' AS u ON o.user_id=u.user_id WHERE ';
        $sql   .= sprintf($this->_where_time_format, 'o.add_time', $this->_start_time, $this->_end_time);
        $sql   .= ' AND ' . sprintf($this->_where_time_format, 'u.reg_time', $this->_start_time, $this->_end_time);

        $total = $this->_db->getOne($sql);

        return array(intval($total), G($key, $key . 'end'));
    }

    /**
     * 新客户注册数：统计时间段内，注册的新客户总数
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 14:40:47
     *
     * @return array 客户信息array(客户总数,用时)
     */
    private function _newUsers() {
        G($key = __FUNCTION__);

        $total = $this->_db->count_info($this->_table_user, '*', sprintf($this->_where_time_format, 'reg_time', $this->_start_time, $this->_end_time));

        return array(intval($total), G($key, $key . 'end'));
    }

    /**
     * 新客户重复购买率：发生重复购买的新注册客户数/统计时间段内下单新注册客户数
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 15:11:06
     *
     * @return array 客户信息array(客户总数,用时)
     */
    private function _newUsersRepeatBuys() {
        G($key = __FUNCTION__);
        $sql    = 'SELECT COUNT(u.user_id) AS num FROM '  . $this->_table_order . ' AS o JOIN ';
        $sql   .= $this->_table_user . ' AS u ON o.user_id=u.user_id WHERE ';
        $sql   .= sprintf($this->_where_time_format, 'o.add_time', $this->_start_time, $this->_end_time);
        $sql   .= ' AND ' . sprintf($this->_where_time_format, 'u.reg_time', $this->_start_time, $this->_end_time);
        $sql   .= ' GROUP BY o.user_id HAVING(num)>1';

        $this->_db->query($sql);
        $total = $this->_db->numRows();

        return array(intval($total), G($key, $key . 'end'));
    }

    /**
     * 统计时段内的复购率=统计时间段内重复下单的客户数/统计时间段内所有下单的客户数（包括当天下单多次，并成功付款的）
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 15:11:06
     *
     * @return array 客户信息array(客户总数,用时)
     */
    private function _repeatBuyUsers() {
        G($key = __FUNCTION__);
        $sql    = 'SELECT COUNT(user_id) AS num FROM '  . $this->_table_order;
        $sql   .= ' WHERE ' . sprintf($this->_where_time_format, 'add_time', $this->_start_time, $this->_end_time);
        $sql   .= ' GROUP BY user_id HAVING(num)>1';

        $this->_db->query($sql);
        $total = $this->_db->numRows();

        return array(intval($total), G($key, $key . 'end'));
    }

    /**
     * 订单总数：统计时间段内，新老客户下单的总数量
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 13:55:02
     *
     * @return array 订单信息array(订单总数,用时)
     */
    private function _totalOrders() {
        G($key = __FUNCTION__);

        $total = $this->_db->count_info($this->_table_order, '*', sprintf($this->_where_time_format, 'add_time', $this->_start_time, $this->_end_time));

        return array(intval($total), G($key, $key . 'end'));
    }

    /**
     * 下单用户数
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 14:40:47
     *
     * @return array 客户信息array(客户总数,用时)
     */
    private function _totalUsers() {
        G($key = __FUNCTION__);


        $sql    = 'SELECT DISTINCT user_id FROM '  . $this->_table_order;
        $sql   .= ' WHERE ' . sprintf($this->_where_time_format, 'add_time', $this->_start_time, $this->_end_time);

        //$sql   .= ' GROUP BY user_id';
        $data   = array();
        $num    = 0;

        $this->_db->query($sql);

        while($row = $this->_db->fetchArray()) {
            $num++;
            $data[] = $row['user_id'];
        }

        $this->_total_users_id = $data;

        return array($num, G($key, $key . 'end'));
    }

    /**
     * 构造函数
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2012-10-10 09:41:50
     *
     * @return void 无返回值
     */
    public function __construct() {
        $this->_filename = basename(__FILE__, '.php');//repeat_buy_stat
        $GLOBALS['_ACT'] = $this->_filename;

        if (!isset($_GET['crontab'])) {
            admin_priv($this->_filename);//检查权限
        }

        //安全版本,站点移植唯一需要修改地方
        //$this->_db = $GLOBALS['db'];//get_db(DB_ADMIN);
		$this->_db = get_slave_db();
        $this->_website = 'http://' . $_SERVER['HTTP_HOST'] . '/';
        $this->_data_cache_path = 'eload_admin/cache_files/' . $this->_filename . '/';
        $this->_table_order = ORDERINFO . '_' . $this->_filename;
        $this->_table_user = USERS . '_' . $this->_filename;
        $this->_months = range(0, 12);
        unset($this->_months[0]);

        if (!is_dir($dir = ROOT_PATH . $this->_data_cache_path)) {
            mkdir($dir, 0770, true);
        }
    }//end __construct

    /**
     * 批量统计,通常为定时任务
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 19:24:13
     *
     * @return void 无返回值
     */
    public function batchStatAction() {
        $year  = Filter::int('year', INPUT_GET, date('Y'));//年
        $month = Filter::int('month', INPUT_GET, date('n'));//月份

        if (!isset($this->_months[$month])) {
            $log = '';//Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            throw new Exception($log . '月份不存在: ' . $month);
        }

        G('start_time');
        $mh   = curl_multi_init();
        $url  = $this->_website . 'eload_admin/' . $this->_filename . '.php?crontab=1&action=stat&month=%d&type=%d';
        $m    = local_date('n');

        foreach($this->_type_arr as $type => $text) {//1,2,3,6

            if (1 == $type || $month > $type) {//2013年过后干掉此if
                $ch = curl_init($v = sprintf($url, $month, $type));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_multi_add_handle($mh, $ch);
            }
        }

        $running = null;

        do {
            curl_multi_exec($mh, $running);
        }
        while($running > 0);

        curl_multi_close($mh);

        //Logger::filename(__METHOD__, __LINE__, $this->_filename);
        //trigger_error(sprintf(LOG_STRONG_FORMAT, G('start_time', 'end_time')));
    }//end batchStatAction

    /**
     * 导入订单信息及用户信息
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 10:38:51
     *
     * @return void 无返回值
     */
    public function insertDataAction() {
        G('start_time');
        $last_info = read_static_cache($this->_insert_data_cache_filename, 3, $this->_data_cache_path);

        if (!$last_info) {
            //Logger::filename(__METHOD__, __LINE__, $this->_filename);
            //trigger_error('未能获取导入订单信息及用户信息,重新设置');

            $insert_data = array(self::MIN_ORDER_ID, self::MIN_USER_ID);
            write_static_cache($this->_insert_data_cache_filename, $insert_data, 3, $this->_data_cache_path);

            return;
        }

        $error      = '';
        $order_id   = $last_info[0];
        $user_id    = $last_info[1];

        //防止整表导入
        if ($order_id < self::MIN_ORDER_ID || $user_id < self::MIN_USER_ID) {
            $log = '';//Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            throw new Exception($log . '导入订单信息及用户信息数据有误' . var_export($last_info, true));
        }

        $orders = $this->_inserOrders($order_id);
        $users  = $this->_insertUsers($user_id);

        //array(最大订单id,最大用户id,导入订单数,导入用户数)
        $insert_data = array($orders[0], $users[0], $orders[1], $users[1]);
        write_static_cache($this->_insert_data_cache_filename, $insert_data, 3, $this->_data_cache_path);

        //Logger::filename(__METHOD__, __LINE__, $this->_filename);
        //trigger_error('导入订单信息及用户信息成功: (' . sprintf(LOG_STRONG_FORMAT, G('start_time', 'end_time')) . ')' . var_export($insert_data, true));

        exit('导入结束');
    }//end insertDataAction

    /**
     * 获取统计数据
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-12 09:11:08
     *
     * @return array 统计数据
     */
    public function listAction() {
        $type = Filter::int('type', INPUT_GET); //类型

        if ($type) {

            if (isset($this->_type_arr[$type])) {
                $data = $this->_getData($type);
            }
            else {
                //$log = Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
                //trigger_error($log . '统计类型错误: ' . $type);

                sys_msg('统计类型错误', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
            }
        }
        else {
            $data = array();
        }

        return array($data, $type, $this->_type_arr);
    }

    /**
     * 统计
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-12-11 13:56:29
     *
     * @return void 无返回值
     */
    public function statAction() {
        G('start_time');
        $year  = Filter::int('year', INPUT_GET, date('Y'));//年
        $month = Filter::int('month', INPUT_GET);//月份
        $type  = Filter::int('type', INPUT_GET); //类型

        if (!isset($this->_months[$month])) {
            $log = '';//Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            throw new Exception($log . '月份不存在: ' . $month);
        }
        elseif (!isset($this->_type_arr[$type])) {
            $log = '';//Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            throw new Exception($log . '统计类型错误: ' . $type);
        }

        $date               = $year . '-' . $month;
        $this->_start_time  = local_strtotime('-' . $type . ' month', strtotime($date));
        $this->_end_time    = local_strtotime($date . '-1') - 1;//2013-10-31 23:59:59

        $total_orders           = $this->_totalOrders();//订单数
        $total_users            = $this->_totalUsers();//下单用户数
        $new_users              = $this->_newUsers();//注册用户数
        $new_order_users        = $this->_newOrderUsers();//下单新注册客户数
        $new_users_repeat_buys  = $this->_newUsersRepeatBuys();//重复购买的新注册客户数
        $repeat_buy_users       = $this->_repeatBuyUsers();//重复购买用户数
        $history_buy_users      = $this->_historyBuyUsers();//历史购买用户数

        /*
1、	订单总数：统计时间段内，新老客户下单的总数量；
2、	新客户注册数：统计时间段内，注册的新客户总数；
3、	下单新注册客户数：统计时间段内注册的，并且下单的客户总数量；
4、	新客户重复购买率：发生重复购买的新注册客户数/统计时间段内下单新注册客户数；
5、	统计时段内的复购率=统计时间段内重复下单的客户数/统计时间段内所有下单的客户数（包括当天下单多次，并成功付款的）
6、	历史重复购买率 = 统计时间段内下单的客户历史曾有其他订单（包括统计时间段内的订单）/所有下单客户
        */

        $data = array(
            'total_orders'              => $total_orders[0],//订单总数
            'total_users'               => $total_users[0],//所有下单客户
            'new_users'                 => $new_users[0],////新客户注册数
            'new_order_users'           => $new_order_users[0],//下单新注册客户数
            'new_users_repeat_buys'     => $new_users_repeat_buys[0],//重复购买的新注册客户数
            'new_users_repeat_buys_rate'=> $new_order_users[0] ? round($new_users_repeat_buys[0] / $new_order_users[0] * 100, 2) : 0,//新客户重复购买率：
            'repeat_buy_users'          => $repeat_buy_users[0],//重复下单的客户数
            'repeat_buy_users_rate'     => $total_users[0] ? round($repeat_buy_users[0] / $total_users[0] * 100, 2) : 0,//统计时段内的复购率
            'history_buy_users'         => $history_buy_users[0],//客户历史曾有其他订单
            'history_buy_users_rate'     => $total_users[0] ? round($history_buy_users[0] / $total_users[0] * 100, 2) : 0,
        );

        /*if (0 == $v['total_amount_rate']) {//占比太小,保留至四位
            $v['total_amount_rate'] = str_replace('.', '.00', round($v['payed_amount'] / $all_data['payed_amount'] * 10000 * 100, 2));
        }*/

        /*$log = Logger::filename(__METHOD__, __LINE__, $this->_filename) . sprintf(LOG_STRONG_FORMAT, G('start_time', 'end_time')) . PHP_EOL .
        'data: ' . var_export($data, true) . PHP_EOL .
        'total_orders: ' . var_export($total_orders, true) . PHP_EOL .
        'total_users: ' . var_export($total_users, true) . PHP_EOL .
        'new_users: ' . var_export($new_users, true) . PHP_EOL .
        'new_order_users: ' . var_export($new_order_users, true) . PHP_EOL .
        'new_users_repeat_buys: ' . var_export($new_users_repeat_buys, true) . PHP_EOL .
        'repeat_buy_users: ' . var_export($repeat_buy_users, true) . PHP_EOL .
        'history_buy_users: ' . var_export($history_buy_users, true) . PHP_EOL;
        trigger_error($log);*/

        $path = $this->_data_cache_path . $type . '/';

        if (!is_dir($dir = ROOT_PATH . $path)) {
            mkdir($dir, 0750);
        }

        $filename = local_date('Y-n', $this->_start_time);//2013-10

        if (1 != $type) {//2013-8 - 2013-9
            $filename .= ' - ' . local_date('Y-n', $this->_end_time);
        }

        write_static_cache($filename, $data, 3, $path);
        /*var_export($total_orders);
        var_export($total_users);
        var_export($new_users);
        var_export($new_order_users);
        var_export($new_users_repeat_buys);
        var_export($repeat_buy_users);
        var_export($history_buy_users);
        var_export($data);*/

        exit('统计结束');
    }//end statAction
}

/*
DROP TABLE IF EXISTS eload_order_info_repeat_buy_stat;
CREATE TABLE eload_order_info_repeat_buy_stat(
  `order_id` mediumint(8) unsigned NOT NULL DEFAULT 0 COMMENT '订单id',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT 0 COMMENT '用户id',
  `add_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '添加时间',
  `order_status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '订单状态',
  PRIMARY KEY (`order_id`),
  KEY (`user_id`,`add_time`),
  KEY (`add_time`),
  KEY (order_status)
)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '重复购买统计订单表 by mashanling on 2013-12-10 15:55:55';
INSERT INTO eload_order_info_repeat_buy_stat SELECT order_id,user_id,add_time,order_status FROM eload_order_info;
DELETE FROM eload_order_info_repeat_buy_stat WHERE order_status=0

DROP TABLE IF EXISTS eload_users_repeat_buy_stat;
CREATE TABLE eload_users_repeat_buy_stat(
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT 0 COMMENT '用户id',
  `reg_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '注册时间',
  PRIMARY KEY (`user_id`),
  KEY (`reg_time`)
)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '重复购买统计用户表 by mashanling on 2013-12-10 15:55:55';
INSERT INTO eload_users_repeat_buy_stat SELECT user_id,reg_time FROM eload_users;
 */