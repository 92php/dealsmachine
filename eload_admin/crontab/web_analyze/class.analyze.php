<?php
/**
 * class.analyze.php               网站统计分析基础类
 *
 * @author                         mashanling(msl-138@163.com)
 * @date                           2011-08-26
 * @last modify                    2011-09-07 by mashanling
 */

!defined('INI_WEB') && exit('Access denied!');

class Analyze {

    /**
     * 执行统计分析
     *
     * @param string 统计类名
     * @param mixed  $unixtime 三种格式参数：(true:首次统计;false:从上次统计时间开始统计，默认;否则，从指定日期(yyyymmdd)前一天开始统计)
     * @param int    $endtime  结束时间，默认0，仅对unixtime为yyyymmdd时有效
     *
     */
    static function setAnalyze($class, $unixtime = false, $endtime = 0) {
        if ($unixtime === true) {
            $unixtime = 0;
        }
        else {
            $unixtime  = $unixtime === false ? self::getAnalyzeInfo($class->key_record) : $unixtime;
             //       var_dump($class->key_record);
       // exit();
            $is_sunday = date('N', $unixtime) == 1 || date('N', $unixtime) == 7;    //星期一或星期天
            $unixtime  = strtotime($unixtime) - ($is_sunday ? 2 * 86400 : 0);
        }

        $unixtime_arr = self::getDatetime($class->table, $unixtime, ($endtime ? strtotime($endtime) : 0));    //获取可用统计时间
            //获取可用统计时间


        if ($unixtime_arr === false) {    //统计时间为空
            return false;
        }
        $time_start = microtime(true);
        if (get_class($class) == 'AnalyzeSales' || get_class($class) == 'AnalyzeCountrySale') {    //销售分析
        	//echo 9999;
        	//print_r($unixtime_arr);
            $class->setData($unixtime_arr);
        }
        else {
            foreach ($unixtime_arr as $value) {
                $unixtime = $value['unixtime'];    //当天unix 中国gmt时间戳
                $data     = $class->getData($unixtime + 25200);    //获取统计数据
                //print_r($data);
                self::executeAnalyze($class->table, $data, $unixtime);
            }
        }
        self::setAnalyzeInfo($class->key_record, date('Ymd'));    //记录本次统计时间
        echo '更新网站' . $class->name . '分析结束，用时' . execute_time($time_start) . PHP_EOL;
    }

    /**
     * 执行更新分析数据表操作
     *
     * @param string $table     表名
     * @param array  $data      数据
     * @param int    $unixtime  unix 中国gmt时间戳
     * @param wtring $where     where条件
     */
    static protected function executeAnalyze($table, $data, $unixtime, $where = '') {
        $where = $where ? $where : 'unixtime=' . $unixtime;
        $GLOBALS['db']->autoExecute($table, $data, 'UPDATE', $where);
        echo date('Y-m-d', $unixtime) . PHP_EOL . var_export($data, true) . PHP_EOL . PHP_EOL;
        /*ob_flush();
        flush();*/
    }

	/**
     * 获取统计时间
     *
     * @param string $table    表名
     * @param int    $unixtime unix 中国gmt时间戳
     * @param int    $endtime  结束时间戳
     */
    static protected function getDatetime($table, $unixtime = 0, $endtime = 0) {
    	//echo $unixtime;
        $where     = $unixtime ? ' WHERE unixtime>=' . ($unixtime - ($table == ANALYZE_ORDERS ? 1 * 86400 : 0)) . ($endtime ? ' AND unixtime<=' . $endtime : '') : '';
        $sql       = 'SELECT DISTINCT(unixtime) FROM ' . $table . $where;
        //echo $sql, PHP_EOL;
        $data_arr  = $GLOBALS['db']->arrQuery($sql);
        if (empty($data_arr)) {
            echo $table . '数据已是最新(1)' . PHP_EOL;
            return false;
        }
        return $data_arr;
    }

	/**
     * 插入统计时间
     *
     * @param string $class 分析类
     * @param mixed  $unixtime 三种格式参数：(true:首次插入;false:从上次统计时间开始插入，默认;否则，从指定日期(yyyymmdd)开始插入)
     * @param int    $endtime  结束时间，默认0，仅对unixtime为yyyymmdd时有效
     */
    static function insertDatetime($class, $unixtime = false, $endtime = 0) {
        $now_date = $endtime ? $endtime : date('Ymd');    //当前日期
        if ($unixtime === true) {
            $from = 20120426;
        }
        else {
            $from = $unixtime === false ? self::getAnalyzeInfo($class->table . '_lastInsertDatetime') : $unixtime;
        }

        if(!$from) $from = 20120426;
        if ($now_date <= $from && !$endtime) {
            echo $now_date . '已经是最新时间，不需要重新插入' . PHP_EOL;
            return false;
        }
        $time_start  = microtime(true);
        $value       = '';
        $from        = strtotime($from);
        $to          = strtotime($now_date);
        for($i = $from + ($endtime ? 0 : 86400); $i <= $to; $i += 86400) {
            $value .= ",({$i})";
        }
        $value = 'VALUES' . substr($value, 1);
        if ($unixtime === true) {    //重新插入时间
            echo '重新插入统计时间将导致所有分析数据被清空，请确认';
            exit;
            $GLOBALS['db']->query('TRUNCATE ' . $class->table);
        }
        elseif ($unixtime) {    //指定插入时间
            $GLOBALS['db']->query("DELETE FROM {$class->table} WHERE unixtime BETWEEN {$from} AND {$to}");
        }
        if (get_class($class) == 'AnalyzeSales') {    //销售
            AnalyzeSales::insertDatetime($value);
        }
        else {
            $GLOBALS['db']->query("INSERT INTO {$class->table} (unixtime) {$value} ON DUPLICATE KEY UPDATE unixtime=unixtime");
        }
        unset($value);
        self::setAnalyzeInfo($class->table . '_lastInsertDatetime', $now_date);    //记录本次插入时间
        echo "{$class->table}插入统计时间结束，最新时间：{$now_date}。用时" . execute_time($time_start) . PHP_EOL;
    }//end insertDatetime()

	/**
     * 获取当天***总数。形如: SELECT COUNT(*) FROM @table WHERE @where;
     *
     * @param string $table           表名
     * @param string $time_column     时间字段名
     * @param int    $unixtime        当天unix gmt时间戳
     * @param string $extra_where     额外where
     * @param string $count_column    COUNT(DISTINCT(@column))
     */
    static protected function getTodayNums($table, $time_column, $unixtime, $extra_where = '', $count_column = false) {
        $where         = ' WHERE ' . self::getTimeWhere($time_column, $unixtime);
        $count_column  = $count_column ? 'DISTINCT ' . $count_column : $time_column;
        $sql           = "SELECT COUNT({$count_column}) FROM {$table} {$where} {$extra_where}";
        //return $sql;
        return $GLOBALS['db']->getOne($sql);
    }

	/**
     * 获取当天***总和。形如: SELECT SUM(@column) FROM @table WHERE @where;
     *
     * @param string $table           表名
     * @param string $time_column     时间字段名
     * @param string $sum_column      SUM(@column)
     * @param int    $unixtime        当天unix gmt时间戳
     * @param string $extra_where     额外where
     */
    static protected function getTodaySum($table, $time_column, $sum_column, $unixtime, $extra_where = '') {
        $where = ' WHERE ' . self::getTimeWhere($time_column, $unixtime);
        $sql   = "SELECT SUM({$sum_column}) FROM {$table} {$where} {$extra_where}";
        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 获取当天时间where
     *
     * @param string $column   字段名
     * @param int    $unixtime 当天unix gmt时间戳
     */
    static protected function getTimeWhere($column, $unixtime) {
        return " {$column} BETWEEN {$unixtime} AND " . ($unixtime + 86399);
    }

	/**
     * 设置分析信息
     *
     * @param string $key       分析内容键值
     * @param array  $data      内容
     * @param string $filename  文件名
     */
    static protected function setAnalyzeInfo($key, $data, $filename = FILE_ANALYZE_RECORD_NAME) {
        $info_arr       = self::getAnalyzeInfo('', $filename);
        $info_arr[$key] = $data;

        write_static_cache('analyze.log', $info_arr, 2);
    }

	/**
     * 获取分析信息
     *
     * @param string $key       分析内容键值
     * @param string $filename  文件名
     */
    static protected function getAnalyzeInfo($key = '', $filename = FILE_ANALYZE_RECORD_NAME) {
        //!file_exists($filename) && file_put_contents($filename, serialize(array()));
        //$info_arr = file_get_contents($filename);
        $info_arr   = read_static_cache('analyze.log', 2);

        if (!$info_arr) {
            $info_arr = array();
        }
        else {
            $info_arr = unserialize($info_arr);
        }

        if ($key == '') {
        	//print_r($info_arr);
        	//exit();
            return $info_arr;
        }
        //print_r($info_arr);
        return isset($info_arr[$key]) ? $info_arr[$key] : false;
    }

    /**
     * 返回中国日期的中国gmt时间戳
     *
     * @param mixed $date 日期
     */
    static private function cnTime($date) {
        return local_strtotime($date) + date('Z');
    }
}
?>