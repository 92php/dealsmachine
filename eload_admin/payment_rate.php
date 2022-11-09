<?php
/**
 * payment_rate.php         付款率统计
 *
 * @author                  mashanling <msl-138@163.com>
 * @date                    2012-10-09 09:10:36
 * @lastmodify              2013-10-29 10:38:48 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require(ROOT_PATH . 'languages/en/shopping_flow.php');
require(ROOT_PATH . 'languages/en/common.php');

$Arr['no_records']      = '<span style="color: red">暂无记录！</span>';
$_GET['act']            = isset($_GET['act']) ? $_GET['act'] : 'list';    //操作
$_ACT                   = $_GET['act'];

if ('setMonthData' != $_ACT) {//定时任务，不需要登录
    require_once('../lib/is_loging.php');
    admin_priv('payment_rate');//检查权限
}

$payment_rate           = new PaymentRate();

switch ($_ACT) {
    case 'setMonthData'://将两个月前数据按月存放
        $payment_rate->setMonthData();
        break;

    default://列表
        $payment_rate->query();
        $_ACT = 'payment_rate_list';
        temp_disp();
}

/*
 * 付款率统计类
 **/
class PaymentRate {
    /**
     * @var object $_db 数据库实例
     */
    private $_db;

    /**
     * @var string $_table 表名
     */
    private $_table;

    /**
     * @var array $_payment_list 付款方式
     */
    private $_payment_list;

    /**
     * @var array $_shipping_list 运输方式
     */
    private $_shipping_list;

    /**
     * @var array $_country_list 运输方式
     */
    private $_country_list;

    /**
     * @var string $_data_flag_filename 临时表数据是否为最新数据标识文件名
     */
    private $_data_flag_filename;

    /**
     * @var string $_data_cache_path 按月存放数据保留路径
     */
    private $_data_cache_path;

    /**
     * 查询前，如建表，读取最新数据
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-10-10 10:19:44
     *
     * @param int $start_date 开始时间戳,默认null=60天前
     * @param int $end_date 结束时间戳,默认null=至今
     *
     * @return void 无返回值
     */
    private function _beforeQuery($start_date = null, $end_date = null) {

        if (null === $start_date) {
            $start_date = gmtime() - 86400 * 60;
        }

        //缓存3小时
        if (!$latest_date = read_static_cache('payment_rate_stat.log', $this->_data_cache_path)) {
            $this->_db->query('DELETE FROM ' . $this->_table);
            $sql = "INSERT INTO {$this->_table} SELECT IF(order_status=0,0,1),add_time,country,order_amount,shipping_id,pay_id FROM " . ORDERINFO . " WHERE shipping_id!=0 AND country!=0 AND pay_id!='' AND order_status<9 AND ";
            $sql .= 'add_time>' . $start_date;

            if ($end_date) {
                $sql .= ' AND add_time<' . $end_date;
            }

            $this->_db->query($sql);

            $latest_date = local_date('Y-m-d H:i:s');

            write_static_cache('payment_rate_stat.log', $latest_date, $this->_data_cache_path);
        }

        $GLOBALS['Arr']['latest_date'] = $latest_date;
    }

    /**
     * 按国家,运输方式,付款方式group by
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2013-10-28 17:59:44
     *
     * @return array 数据信息
     */
    private function _queryCountryShippingData() {
        global $Arr;

        //按运输方式,付款方式group by
        $sql   = 'SELECT country_id,shipping_id,payment_key,
        SUM(order_amount) AS total_amount,
        COUNT(*) AS total_count,
        SUM(IF(order_status=0,0,order_amount)) AS payed_amount,
        SUM(IF(order_status=0,0,1)) AS payed_count
        FROM ' . $this->_table . ' GROUP BY country_id,shipping_id,payment_key WITH ROLLUP';

/*
返回结果格式：
country_id  shipping_id     payment_key         total_amount        total_count     payed_amount        payed_count
11          1	              CreditCard	              7861.90	            193	            5873.32	               144
11	         1	              PayPal	                 58699.31	           1744	           53097.57	              1658
11	         1	              webcollect	             12735.23	            160	            7459.80	               120
11	         1	              WesternUnion	             166.70	              1	               0.00	                 0
11	         1		             NULL                    79463.14	           2098	           66430.69	              1922
11	         2	              CreditCard	               283.24	              3	             251.25	                 2
11	         2	              PayPal	                  2155.08	             16	            1955.75	                15
11	         2	              webcollect	               698.48	              5	             301.31	                 3
11	         2		             NULL                     3136.80	             24	            2508.31	                20
11	         3	              CreditCard	              4561.56	             43	            3024.53	                31
11	         3	              PayPal	                 19348.80	            177	           17194.24	               154
11	         3	              webcollect	              6028.13	             56	            4478.61	                42
11	         3		             NULL                    29938.49	            276	           24697.38	               227
11			       NULL            NULL                   112538.43	           2398	           93636.38	              2169
12	         1	              CreditCard	              5531.57	            199	            3118.19	               150
...
...
*/

        $shipping_amount    = array();//运输方式付款总额
        $country_data       = array();//国家数据

        $this->_db->query($sql);

        while ($row = $this->_db->fetchArray()) {
            $country_id     = $row['country_id'];

            if (!$country_id) {//最后所有汇总，即 NULL,NULL,NULL,.....
                break;
            }

            $shipping_id    = $row['shipping_id'];
            $payment_key    = $row['payment_key'];

            if (!$shipping_id && !$payment_key) {//汇总，即51,NULL,NULL,....
                $shipping_amount[$country_id] = $row['payed_amount'];
                continue;
            }

            $shipping_id    = $shipping_id ? $shipping_id : 'all';

            if (!isset($country_data[$country_id])) {
                $country_data[$country_id] = array();
            }

            if (!isset($country_data[$country_id][$shipping_id])) {//运输方式数据
                $country_data[$country_id][$shipping_id] = array(
                    'count'         => 0,//付款方式数目,模板中rowspan调用
                    'item'          => array(),//付款方式数据
                    'payed_amount'  => 0,//已付款金额
                    'name'          => isset($this->_shipping_list[$shipping_id]) ? $this->_shipping_list[$shipping_id]['ship_name'] : 'All',//付款方式名称
                );
            }

            if ($payment_key) {
                $row['payment'] = isset($this->_payment_list[$payment_key]) ? $this->_payment_list[$payment_key]['pay_name'] : $payment_key;
            }
            else {//汇总，即51,1,NULL,....
                $row['payment'] = 'All';
                $country_data[$country_id][$shipping_id]['payed_amount'] += $row['payed_amount'];
            }

            $row['payed_count_rate'] = $row['total_count'] ? round($row['payed_count'] / $row['total_count'] * 100, 2) : 0;
            $row['payed_amount_rate'] = $row['total_amount'] ? round($row['payed_amount'] / $row['total_amount'] * 100, 2) : 0;
            $country_data[$country_id][$shipping_id]['item'][] = $row;
            $country_data[$country_id][$shipping_id]['count']++;
        }//end while

        if ($country_data) {

            foreach($country_data as $country_id => &$item) {//付款占比
                $all_payed_amount = $shipping_amount[$country_id];

                foreach ($item as $k => &$v) {

                    if ('all' != $k) {
                        $v['payed_amount_rate'] = $all_payed_amount > 0 ? round($v['payed_amount'] / $all_payed_amount * 100, 2) : 0.00;

                        if ($all_payed_amount > 0 && 0 == $v['payed_amount_rate']) {//占比太小,保留至四位
                            $v['payed_amount_rate'] = str_replace('.', '.00', round($v['payed_amount'] / $all_payed_amount * 10000, 2));
                        }
                    }
                }
            }
        }

        write_static_cache($this->_month . '.country.shipping.data', $country_data, $this->_data_cache_path);

        return $country_data;
    }//end _queryCountryShippingData

    /**
     * 按付款金额大小排序国家数据，越大越靠前
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2013-10-15 15:31:02
     *
     * @return int $a > $b返回-1; $a < $b返回1; $a = $b返回0
     */
    private function _usort($a, $b) {

        if ('All' == $a['name']) {
            return 1;
        }

        $result = floatval($a['payed_amount']) - floatval($b['payed_amount']);

        if ($result > 0) {
            return -1;
        }

        return 0 == $result ? 0 : 1;
    }

    /**
     * 构造函数
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-10-10 09:41:50
     *
     * @return void 无返回值
     */
    function __construct() {
        $this->_db = IS_LOCAL ? $GLOBALS['db'] : $GLOBALS['db'];//A，暂不切从服务器
        $this->_table = ORDERINFO . '_payment_rate_stat';
        require(ROOT_PATH . 'eload_admin/cache_files/shipping_method.php');
        $this->_shipping_list = $data;
        require(ROOT_PATH . 'eload_admin/cache_files/payment.php');
        $this->_payment_list = $data;
        $this->_country_list = read_static_cache('area_key', 2);
        $this->_data_cache_path = 'eload_admin/cache_files/payment_rate_data/';
        $this->_data_flag_filename = ROOT_PATH . $this->_data_cache_path . 'payment_rate_stat.log';

        if (!is_dir($dir = ROOT_PATH . $this->_data_cache_path)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * 查询前，如建表，读取最新数据
        }
    }

    /**
     * 查询，需求如下：
     * 给出默认时间为最近一个星期，默认地区为所有，
     * 如果不选择地区，则根据选择的时间段，展示如下两个表的内容，
     * 如果选择地区，则只根据所选地区，给出第一个表的内容；
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-10-10 09:44:36
     *
     * @param int $start_date 开始时间戳,默认null=60天前
     * @param int $end_date 结束时间戳,默认null=至今
     *
     * @return void 无返回值
     */
    function query($start_date = null, $end_date = null) {
        global $Arr;

        $Arr['payment_list']  = $this->_payment_list;//付款方式
        $Arr['shipping_list'] = $this->_shipping_list;//运输方式
        $Arr['country_list']  = $this->_country_list;//国家列表

        $Arr['max_date'] = gmtime();
        $Arr['min_date'] = $Arr['max_date'] - 86400 * 60;

        if (!isset($_GET['start_date'])) {//未查询，给出最近一个星期默认时间
            $Arr['start_date'] = local_date('Y-m-d', $Arr['max_date'] - 7 * 86400);
            return;
        }

        $this->_beforeQuery($start_date, $end_date);

        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';//开始时间
        $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';//结束时间
        $country_id = isset($_GET['country_id']) ? intval($_GET['country_id']) : '';//国家
        $where      = '';//where条件

        $Arr['query'] = true;
        $Arr['country_id'] = $country_id;

        if ($start_date) {//开始时间
            $Arr['start_date'] = $start_date;
            $where .= ' AND add_time>=' . local_strtotime($start_date);

            if (time() - strtotime($start_date) > 86400 * 60 && 'list' == $_GET['act'] && !isset($this->_from_set_month_data)) {//超出60天,取月份数据
                $month = local_date('Ym', local_strtotime($start_date));

                if ($data  = read_static_cache($month, $this->_data_cache_path)) {
                }
                else {
                    $data = $this->setMonthData(local_date('Y-m-01', local_strtotime($start_date)));
                }

                if ($country_id) {
                    $Arr['country_data']    = isset($data['country_data'][$country_id]) ? array($data['country_data'][$country_id]) : array();
                    $shipping_data          = read_static_cache(local_date('Ym', local_strtotime($start_date)) . '.country.shipping.data', $this->_data_cache_path);
                    $Arr['shipping_data']   = isset($shipping_data[$country_id]) ? $shipping_data[$country_id] : array();
                }
                else {
                    $Arr['shipping_data'] = $data['shipping_data'];
                    $Arr['country_data'] = $data['country_data'];
                }

                $Arr['month'] = substr($month, 0, 4) . '年' . substr($month, 4) . '月份';

                return;
            }

        }

        if ($end_date) {//开始时间
            $Arr['end_date'] = $end_date;
            $where .= ' AND add_time<=' . local_strtotime($end_date);
        }

        if ($country_id) {//国家
            $where .= ' AND country_id=' . $country_id;
        }

        $where = $where ? ' WHERE ' . substr($where, 4) : '';

        $this->queryShippingData($where);//运输方式，付款方式

        if ($country_id) {//如果选择地区，则只根据所选地区，给出第一个表的内容
            return;
        }

        $this->queryCountryData($where);//国家，付款方式

    }//end query

    /**
     * 第二个表，按国家付款方式,付款方式group by
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-10-10 10:00:04
     *
     * @return void 无返回值
     */
    private function queryCountryData($where) {
        global $Arr;

        //按国家地区,付款方式group by，返回格式见_queryCountryShippingData说明
        $sql   = 'SELECT country_id, payment_key,
        SUM(order_amount) AS total_amount,
        COUNT(*) AS total_count,
        SUM(IF(order_status=0,0,order_amount)) AS payed_amount,
        SUM(IF(order_status=0,0,1)) AS payed_count
        FROM ' . $this->_table . $where . ' GROUP BY country_id,payment_key WITH ROLLUP';

        $country_data       = array();//国家付款方式数据
        $shipping_amount    = array();//运输方式付款总额

        $this->_db->query($sql);

        while ($row = $this->_db->fetchArray()) {
            $country_id     = $row['country_id'];
            $country_id     = $country_id ? $country_id : 'all';
            $payment_key    = $row['payment_key'];

            if (!isset($country_data[$country_id])) {
                $country_data[$country_id] = array(
                    'count'         => 0,//付款方式数目,模板中rowspan调用
                    'item'          => array(),//付款方式数据
                    'payed_amount'  => 0,//总金额
                    'name'          => isset($this->_country_list[$country_id]) ? $this->_country_list[$country_id]['region_name'] : 'All',//国家名称
                );
            }

            if ($payment_key) {

                $row['payment'] = isset($this->_payment_list[$payment_key]) ? $this->_payment_list[$payment_key]['pay_name'] : $payment_key;

                if (!isset($shipping_amount[$payment_key])) {//运输方式付款总额
                    $shipping_amount[$payment_key] = $row;
                }
                else {
                    $shipping_amount[$payment_key]['total_amount'] += $row['total_amount'];
                    $shipping_amount[$payment_key]['total_count'] += $row['total_count'];
                    $shipping_amount[$payment_key]['payed_amount'] += $row['payed_amount'];
                    $shipping_amount[$payment_key]['payed_count'] += $row['payed_count'];
                }
            }
            else {//汇总
                $row['payment'] = 'All';
                $country_data[$country_id]['payed_amount'] += $row['payed_amount'];
            }

            $row['payed_count_rate'] = $row['total_count'] ? round($row['payed_count'] / $row['total_count'] * 100, 2) : 0;
            $row['payed_amount_rate'] = $row['total_amount'] ? round($row['payed_amount'] / $row['total_amount'] * 100, 2) : 0;
            $country_data[$country_id]['item'][] = $row;
            $country_data[$country_id]['count']++;

            /*$row = array (
              'shipping_id' => '1',
              'payment_key' => 'PayPal',
              'total_amount' => '204728.31',
              'total_count' => '5056',
              'payed_amount' => '141437.16',
              'payed_count' => '4177',
              'payment' => 'PayPal',
              'payed_count_rate' => 82.61,
              'payed_amount_rate' => 69.09,
            ),*/
        }//end while

        if ($country_data) {
            $all_data       = end($country_data);//汇总数据
            $all_data       = $all_data['item'][0];
            $all_data_item  = array();

            foreach($shipping_amount as $k => &$v) {//计算占比
                $v['payed_count_rate'] = $v['total_count'] ? round($v['payed_count'] / $v['total_count'] * 100, 2) : 0;
                $v['payed_amount_rate'] = $v['total_amount'] ? round($v['payed_amount'] / $v['total_amount'] * 100, 2) : 0;
                $all_data_item[] = $v;
                $country_data['all']['count']++;
            }

            //附加到汇总
            $country_data['all']['item'] = array_merge($all_data_item, $country_data['all']['item']);

            foreach($country_data as $k => &$v) {//付款占比

                if ('all' != $k) {
                    $v['total_amount_rate'] = round($v['payed_amount'] / $all_data['payed_amount'] * 100, 2);

                    if (0 == $v['total_amount_rate']) {//占比太小,保留至四位
                        $v['total_amount_rate'] = str_replace('.', '.00', round($v['payed_amount'] / $all_data['payed_amount'] * 10000, 2));
                    }
                }
            }

            uasort($country_data, array($this, '_usort'));//按销售额倒序
        }

        $Arr['country_data'] = $country_data;//按付款金额倒序
    }//end queryCountryData

    /**
     * 第一个表，按运输方式,付款方式group by，返回格式见_queryCountryShippingData说明
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-10-10 10:00:04
     *
     * @return void 无返回值
     */
    private function queryShippingData($where) {
        global $Arr;

        //按运输方式,付款方式group by
        $sql   = 'SELECT shipping_id,payment_key,
        SUM(order_amount) AS total_amount,
        COUNT(*) AS total_count,
        SUM(IF(order_status=0,0,order_amount)) AS payed_amount,
        SUM(IF(order_status=0,0,1)) AS payed_count
        FROM ' . $this->_table . $where . ' GROUP BY shipping_id,payment_key WITH ROLLUP';

        $shipping_data      = array();//运输方式,付款方式数据
        $shipping_amount    = array();//运输方式付款总额

        $this->_db->query($sql);

        while ($row = $this->_db->fetchArray()) {
            $shipping_id    = $row['shipping_id'];
            $shipping_id    = $shipping_id ? $shipping_id : 'all';
            $payment_key    = $row['payment_key'];

            if (!isset($shipping_data[$shipping_id])) {
                $shipping_data[$shipping_id] = array(
                    'count'         => 0,//付款方式数目,模板中rowspan调用
                    'item'          => array(),//付款方式数据
                    'payed_amount'  => 0,//已付款金额
                    'name'          => isset($this->_shipping_list[$shipping_id]) ? $this->_shipping_list[$shipping_id]['ship_name'] : 'All',//付款方式名称
                );
            }

            if ($payment_key) {

                $row['payment'] = isset($this->_payment_list[$payment_key]) ? $this->_payment_list[$payment_key]['pay_name'] : $payment_key;

                if (!isset($shipping_amount[$payment_key])) {//运输方式付款总额
                    $shipping_amount[$payment_key] = $row;
                }
                else {
                    $shipping_amount[$payment_key]['total_amount'] += $row['total_amount'];
                    $shipping_amount[$payment_key]['total_count'] += $row['total_count'];
                    $shipping_amount[$payment_key]['payed_amount'] += $row['payed_amount'];
                    $shipping_amount[$payment_key]['payed_count'] += $row['payed_count'];
                }
            }
            else {
                $row['payment'] = 'All';
                $shipping_data[$shipping_id]['payed_amount'] += $row['payed_amount'];
            }

            $row['payed_count_rate'] = $row['total_count'] ? round($row['payed_count'] / $row['total_count'] * 100, 2) : 0;
            $row['payed_amount_rate'] = $row['total_amount'] ? round($row['payed_amount'] / $row['total_amount'] * 100, 2) : 0;
            $shipping_data[$shipping_id]['item'][] = $row;
            $shipping_data[$shipping_id]['count']++;

            /*$row = array (
              'shipping_id' => '1',
              'payment_key' => 'PayPal',
              'total_amount' => '204728.31',
              'total_count' => '5056',
              'payed_amount' => '141437.16',
              'payed_count' => '4177',
              'payment' => 'PayPal',
              'payed_count_rate' => 82.61,
              'payed_amount_rate' => 69.09,
            ),*/
        }//end while

        if ($shipping_data) {
            $all_data       = end($shipping_data);//汇总数据
            $all_data       = $all_data['item'][0];
            $all_data_item  = array();

            foreach($shipping_amount as $k => &$v) {//计算占比
                $v['payed_count_rate'] = $v['total_count'] ? round($v['payed_count'] / $v['total_count'] * 100, 2) : 0;
                $v['payed_amount_rate'] = $v['total_amount'] ? round($v['payed_amount'] / $v['total_amount'] * 100, 2) : 0;
                $all_data_item[] = $v;
                $shipping_data['all']['count']++;
            }

            //附加到汇总
            $shipping_data['all']['item'] = array_merge($all_data_item, $shipping_data['all']['item']);

            foreach($shipping_data as $k => &$v) {//付款占比

                if ('all' != $k) {
                    $v['payed_amount_rate'] = round($v['payed_amount'] / $all_data['payed_amount'] * 100, 2);
                }
            }
        }//end if

        $Arr['shipping_data'] = $shipping_data;
    }//end queryShippingData

    /**
     * 将两个月前数据按月存放
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2013-10-16 10:39:05
     *
     * @param string $month 月份,默认null=自动获取当前月份
     *
     * @return array 月数据
     */
    public function setMonthData($month = null) {
        global $Arr;
        $start_time = microtime(true);

        $this->_from_set_month_data = true;

        if ($month) {
            $first = strtotime($month . ' 0:0:0');
            $last  = strtotime('+1 month ' . $month) - 1 ;
        }
        else {
            $date   = date('Y-m-01');//日期
            $first  = strtotime('-3 months', strtotime($date . ' 0:0:0'));//前两个月第一天,
            $last   = strtotime('-2 month ' . $date) - 1 ;//最后一天
        }

        //is_file($this->_data_flag_filename) && unlink($this->_data_flag_filename);
        write_static_cache('payment_rate_stat.log', null, $this->_data_cache_path);

        $_GET['start_date'] = date('Y-m-d H:i:s', $first);//开始时间
        $_GET['end_date'] = date('Y-m-d H:i:s', $last);//结束时间
        $_GET['country_id'] = 0;

        $this->query($first = local_strtotime($_GET['start_date']), local_strtotime($_GET['end_date']));

        $this->_month   = local_date('Ym', $first);
        $data           = array('shipping_data' => $Arr['shipping_data'], 'country_data' => $Arr['country_data']);
        $path           = $this->_data_cache_path;

        $this->_queryCountryShippingData();//按国家，运输方式，付款方式统计
        write_static_cache($this->_month, $data, $path);

        //is_file($this->_data_flag_filename) && unlink($this->_data_flag_filename);
        write_static_cache('payment_rate_stat.log', null, $this->_data_cache_path);

        if (function_exists('e_log')) {
            e_log('', '', $start_time);
        }

        return $data;
    }//end setMonthData
}