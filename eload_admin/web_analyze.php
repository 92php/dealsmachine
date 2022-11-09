<?php
/**
 * web_anaylze.php           网站客户分析
 * 
 * @author                   mashanling(msl-138@163.com)
 * @date                     2011-08-24
 * @last modify              2011-09-07 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/param.class.php');
date_default_timezone_set('asia/shanghai');
admin_priv('web_analyze');    //检查权限

$Arr['no_records']     = '<span style="color: red">暂无记录！</span>';
$_ACT                  = Param::get('act');    //操作
switch ($_ACT) {
    case 'order':    //订单分析
        admin_priv('order_analyze');
        $table = ANALYZE_ORDERS . ' AS o JOIN ' . ANALYZE_CUSTOMERS_ACTION . ' AS a ON o.unixtime=a.unixtime';
        web_analyze($table, '', 'o.*,a.promotion_nums,a.point_nums');
        break;
        
    case 'sale':    //销售分析
        admin_priv('sales_analyze');
        web_analyze_sales();
        break;
        
    case 'customer_action':    //客户行为分析
        admin_priv('customer_action_analyze');
        web_analyze(ANALYZE_CUSTOMERS_ACTION);
        break;
    case 'country':    //客户行为分析
        admin_priv('country_analyze');
        //echo 3132;
        web_analyze_coutry();
        break;        
             
    default:    //客户分析
        admin_priv('customer_analyze');
        web_analyze(ANALYZE_CUSTOMERS, '');
        break;
}
$_ACT = 'web_analyze/' . ($_ACT ? $_ACT : 'customer');
temp_disp();

/**
 * 网站统计分析
 * 
 * @param string $table
 * @param unknown_type $where
 * @param unknown_type $field
 */
function web_analyze($table, $where = '', $field = '*') {
    global $Arr, $db, $_ACT;
    $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
    $record_count    = $record_count > 0 ? $record_count : $db->getOne('SELECT COUNT(*) FROM ' . $table);    
    if (!$record_count) {
        return;
    }
    $filter          = array('record_count' => $record_count);
    $filter          = page_and_size($filter);    //分页信息
    $page            = new page(array('total' => $record_count, 'perpage' => $filter['page_size'], 'url' => "web_analyze.php?act={$_ACT}&amp;record_cound={$record_count}"));
	$Arr['pagestr']  = $page->show();
    $Arr['filter']   = $filter;   
    $limit           = $filter['start'] . ',' . $filter['page_size'];    //sql limit
    $data            = $db->select($table, $field, $where, 'unixtime DESC', $limit);
    $Arr['data']     = $data;
    return $data;
}

function web_analyze_sales() {
    global $Arr, $db, $_ACT;
    $_GET['page_size'] = 1;
    
    $start_date      = Param::get('start_date');    //开始时间
    $end_date        = Param::get('end_date');      //结束时间
    $start_date      = $start_date ? $start_date : date('Y-m-d');
    $Arr['end_date'] = $end_date;
    $Arr['start_date'] = $start_date;
    
    $where           = '';
    $unixtime        = strtotime($start_date);
    
    /*if ($start_date !='' && $end_date != '') {
        $start_date  = strtotime($start_date);
        $end_date    = strtotime($end_date);
        $unixtime    = $end_date;
        $where       = " WHERE unixtime BETWEEN {$start_date} AND {$end_date}";
    }*/
    if ($start_date != '') {
        //$where       = ' WHERE unixtime =' . strtotime($start_date);
        //$unixtime    = strtotime($start_date);
    }/*
    elseif ($end_date != '') {
        $end_date    = strtotime($end_date);
        $unixtime    = $end_date;
        $where       = ' WHERE unixtime <=' . $end_date;
    }
    
    $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
    $record_count    = $record_count > 0 ? $record_count : $db->getOne('SELECT COUNT(DISTINCT(unixtime)) FROM ' . ANALYZE_SALES . $where);
    
    if (!$record_count) {
        return;
    }
    $filter          = array('record_count' => $record_count);
    $filter          = page_and_size($filter);    //分页信息
    //$page            = new page(array('total' => $record_count, 'perpage' => $filter['page_size'], 'url' => "web_analyze.php?act=sale&amp;record_cound={$record_count}&amp;start_date={$start_date}&amp;end_date={$end_date}"));
	//$Arr['pagestr']  = $page->show();
    $Arr['filter']   = $filter;
    
    $unixtime_end    = $unixtime - ($filter['page'] - 1) * $filter['page_size'] * 86400;
    $unixtime_start  = $unixtime - $filter['page'] * $filter['page_size'] * 86400 + 1;
    if ($Arr['start_date']!= '' && $unixtime_start < $start_date) {
        $unixtime_start = $start_date;
    }
    
    $sql             = 'SELECT a.*,c.cat_name FROM ' . ANALYZE_SALES . ' AS a JOIN ' . CATALOG . " AS c ON a.cat_id=c.cat_id WHERE a.unixtime BETWEEN {$unixtime_start} AND {$unixtime_end} GROUP BY a.unixtime DESC,a.cat_id";
    */
    if (!$end_date || ($end_date = strtotime($end_date)) == $unixtime) {//无结束时间
        $sql  = 'SELECT a.*,c.cat_name FROM ' . ANALYZE_SALES . ' AS a JOIN ' . CATALOG . " AS c ON a.cat_id=c.cat_id WHERE a.unixtime ={$unixtime}";
        $data = $db->arrQuery($sql);
    } else {
        $data     = array();
        $days     = ($end_date - $unixtime) / 86400;
        $sql      = 'SELECT c.cat_name,SUM(a.amount) AS amount,SUM(a.sole_good_nums) AS sole_good_nums,SUM(a.new_nums) AS new_nums,SUM(a.new_sole_good_nums) AS new_sole_good_nums,SUM(a.week_amount) AS week_amount,SUM(a.hits) AS hits,new_good_nums,SUM(a.buy_rate) AS buy_rate,SUM(a.new_sole_good_rate) AS new_sole_good_rate FROM ' . ANALYZE_SALES . ' AS a JOIN ' . CATALOG . " AS c ON a.cat_id=c.cat_id
        WHERE a.unixtime BETWEEN {$unixtime} AND {$end_date}-86400 GROUP BY a.cat_id";
        $db->query($sql);
        while ($row = $db->fetchArray()) {
            $row['week_rate'] = $row['week_amount'] > 0 ? round(($row['amount'] - $row['week_amount']) / $row['week_amount'] * 100, 2) : 0.00;    //周增长率
            $row['new_sole_good_rate'] = round($row['new_sole_good_rate'] / $days, 2);    //新产品购买率
            $row['new_sole_good_nums'] = round($row['new_sole_good_nums'] / $days);    //30天内新产品日平均销售数
            $row['buy_rate'] = round($row['buy_rate'] / $days, 2);    //当日产品购买率
            $data[] = $row;
        }		
	}
    //$sql  = 'SELECT a.*,c.cat_name FROM ' . ANALYZE_SALES . ' AS a JOIN ' . CATALOG . " AS c ON a.cat_id=c.cat_id WHERE a.unixtime ={$unixtime} GROUP BY a.unixtime DESC,a.cat_id";
    //$data            = $db->arrQuery($sql);
    $Arr['data']      = $data;
}

function web_analyze_coutry() {
    global $Arr, $db, $_ACT;
    $_GET['page_size'] = 1;    
    $start_date      = Param::get('start_date');    //开始时间
    $end_date        = Param::get('end_date');      //结束时间
    $start_date      = $start_date ? $start_date : date('Y-m-d');
    $Arr['end_date'] = $end_date;
    $Arr['start_date'] = $start_date;    
    $where           = '';
    $start_unixtime        = strtotime($start_date);       
	$end_unixtime        = strtotime($end_date);       
    $sql = 'SELECT a.* FROM ' . ANALYZE_COUNTRY . " a LEFT JOIN eload_country_st c ON a.country_code = c.country_code WHERE unixtime >={$start_unixtime}";
	if($end_unixtime) {
		$sql .= " AND unixtime <= {$end_unixtime}";
	}
	$sql .= ' GROUP BY unixtime,country_code order by sort_order,unixtime';
    $data            = $db->arrQuery($sql);
    $Arr['data']     = $data;
}
?>