<?php
/**
 * class.analyze_sale.php        网站销售分析类
 * 
 * @author                       mashanling(msl-138@163.com)
 * @date                         2011-09-01
 * @last modify                  2011-09-07 by mashanling
 */

!defined('INI_WEB') && exit('Access denied!');

class AnalyzeSales extends Analyze {
    public $key_record = 'lastSetAnalyzeSale';
    public $table      = ANALYZE_SALES;
    public $name       = '销售';
    static private $category = array();
    static private $today_order_arr = array();
    static private $today_hits_arr = array();
    
	/**
     * 统计当天销售数据
     * 
     * @param array $unixtime_arr 统计时间
     * 
     */
    static function setData($unixtime_arr) {
        global $db;
        foreach ($unixtime_arr as $v) {
            $unixtime = $v['unixtime'] + 25200;    //当天unix gmt时间戳
            self::getTodayOrder($unixtime);//当天订单信息
            self::getCateories(true);
            foreach (self::$category as $parent_id => $array) {
                $child_id = $array['child_id'];
                $cat_id   = join(',', $child_id);
                self::getGoodHits($unixtime);    //当天点击次数
                self::getSalesInfo($unixtime, $parent_id, $child_id);    //当天订单信息
                $category      = self::$category[$parent_id];
                $amount        = $category['sole_amount'];    //总额
                $sole_nums     = $category['sole_num'];       //销售个数
                $hits          = $category['click_count'];    //点击次数
                $new_sole_nums = self::getNewSoleNums($unixtime, $cat_id);   //新产品销售个数
                $new_good_nums = self::getNewGoodNums($unixtime, $cat_id);   //新产品数(30天内);
                $week_amount   = self::get7daysAgoAmount($unixtime, $parent_id);    //七天前销售金额
                
                $data = array(
                    'amount' 	        => round($amount, 2),    //销售金额
                    'sole_good_nums'    => $sole_nums,       //销售个数
                    'new_nums'          => self::getTodayGoodNums($unixtime, $cat_id),    //当天新产品数
                    'new_sole_good_nums'=> $new_sole_nums,   //新产品销售个数
                    'new_good_nums'	    => $new_good_nums,   //新产品数(30天内)
                    'week_amount'       => $week_amount,
                    'week_rate'         => $week_amount > 0 ? round(($amount - $week_amount) / $week_amount * 100, 2) : 0.00,    //周增长率
                    'new_sole_good_rate'=> $new_good_nums > 0 ? round($new_sole_nums / $new_good_nums * 100, 2) : 0.00,    //新产品购买率
                	'buy_rate'          => $hits > 0 ? round($sole_nums / $hits * 100, 2) : 0.00,    //当日产品购买率
                    'hits'              => $hits
                
                );
                self::executeAnalyze(ANALYZE_SALES, $data, $v['unixtime'], "unixtime={$v['unixtime']} AND cat_id={$parent_id}");
            }
            
        }//end foreach1
    } //end setData()
    
	/**
     * 获取当天订单
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getTodayOrder($unixtime) {
        $sql   = 'SELECT o.order_id,g.goods_id,g.goods_price,c.cat_id,c.add_time FROM ' . ORDERINFO . ' AS o JOIN ' . ODRGOODS . ' AS g ON o.order_id=g.order_id JOIN ' . GOODS . ' AS c ON c.goods_id=g.goods_id WHERE ' . self::getTimeWhere('o.add_time', $unixtime) . ' AND o.order_status BETWEEN 1 AND 8';
        self::$today_order_arr = $GLOBALS['db']->arrQuery($sql);
    }
    
    /**
     * 获取当天统计信息
     * 
     * @param int   $unixtime  当天unix gmt时间戳
     * @param int   $parent_id 商品顶级类id
     * @param array $cat_id    顶级类下所有子类id
     */
    function getSalesInfo($unixtime, $parent_id, $child_id) {
        foreach (self::$today_order_arr as $k => $v) {    //循环订单，统计信息
            //echo __FUNCTION__ . count(self::$today_order_arr) . PHP_EOL;
            if (in_array($v['cat_id'], $child_id)) {
                self::$category[$parent_id]['sole_amount'] += $v['goods_price'];    //销售金额
                self::$category[$parent_id]['sole_num']    += 1;    //销售个数
                unset(self::$today_order_arr[$k]);
            }
        }
        foreach (self::$today_hits_arr as $k => $v) {    //循环商品，统计新产品数
            if (in_array($v['cat_id'], $child_id)) {
                self::$category[$parent_id]['click_count'] += $v['click_count'];    //点击数
                unset(self::$today_hits_arr[$k]);
            }
        }
    }
    
    /**
     * 获取新产品（30天内）销售数
     * 
     * @param int   $unixtime 当天unix gmt时间戳
     * @param array $cat_id   分类id
     */
    static private function getNewSoleNums($unixtime, $cat_id) {
        $sql = 'SELECT COUNT(DISTINCT g.goods_id) FROM ' . ORDERINFO . ' AS o JOIN ' . ODRGOODS . ' AS g ON o.order_id=g.order_id JOIN ' . GOODS . " AS c ON c.goods_id=g.goods_id WHERE c.cat_id IN({$cat_id}) AND o.add_time BETWEEN " . ($unixtime - 30 * 86400) . " AND {$unixtime} AND c.add_time BETWEEN " . ($unixtime - 30 * 86400) . " AND {$unixtime} AND o.order_status BETWEEN 1 AND 8";
        //echo $sql; exit;
        return $GLOBALS['db']->getOne($sql);
    }
    
	/**
     * 获取当天新产品数
     * 
     * @param int   $unixtime 当天unix gmt时间戳
     * @param array $cat_id   分类id
     */
    static private function getTodayGoodNums($unixtime, $cat_id) {
        $sql = 'SELECT COUNT(cat_id) FROM ' . GOODS . " WHERE cat_id IN({$cat_id}) AND add_time BETWEEN {$unixtime} AND " . ($unixtime + 86399);
        //echo $sql; exit;
        return $GLOBALS['db']->getOne($sql);
    }
    
	/**
     * 获取新产品（30天内）数
     * 
     * @param int   $unixtime 当天unix gmt时间戳
     * @param array $cat_id   分类id
     */
    static private function getNewGoodNums($unixtime, $cat_id) {
        $sql = 'SELECT COUNT(cat_id) FROM ' . GOODS . " WHERE cat_id IN({$cat_id}) AND add_time BETWEEN " . ($unixtime - 30 * 86400) . " AND {$unixtime}";
        //echo $sql; exit;
        return $GLOBALS['db']->getOne($sql);
    }
    
    /**
     * 获取七天前销售总额
     * 
     * @param int $unixtime  当天unix gmt时间戳
     * @param int $parent_id 顶级分类id
     */
    static private function get7daysAgoAmount($unixtime, $cat_id) {
        $sql = 'SELECT SUM(amount) FROM ' . ANALYZE_SALES . " WHERE cat_id ={$cat_id} AND " . self::getTimeWhere('unixtime', $unixtime - 7 * 86400);
        //echo $sql . PHP_EOL . PHP_EOL;
        return $GLOBALS['db']->getOne($sql);
    }
    
	/**
     * 获取当天商品点击次数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getGoodHits($unixtime) {
        $sql   = 'SELECT g.cat_id,SUM(c.hitnum) AS click_count FROM ' . GOODS_HITS . ' AS c JOIN ' . GOODS . ' AS g ON c.goods_id=g.goods_id WHERE ' . self::getTimeWhere('c.daytime', $unixtime) . ' GROUP BY g.cat_id';
        self::$today_hits_arr = $GLOBALS['db']->arrQuery($sql);
        //var_export(self::$today_hits_arr);exit;
    }
    
    /**
     * 插入统计时间
     * 
     * @param string $value 由Analyze::insertDatetime算出来的要插入时间values
     */
    static function insertDatetime($value) {
        self::getCateories();
        foreach (self::$category as $k => $v) {
            $_v = str_replace(')', ",{$k})", $value);
            $GLOBALS['db']->query('INSERT INTO ' . ANALYZE_SALES . " (unixtime, cat_id) {$_v} ON DUPLICATE KEY UPDATE unixtime=unixtime");
        }
    }
    
    /**
     * 获取商品顶级分类及其下所有分类id
     * 
     * @param bool $refresh 是否重新获取，默认false
     */
    static function getCateories($refresh = false) {
        if (!empty(self::$category) && !$refresh) {
            return self::$category;
        }
        $category = read_static_cache('category_c_key', 2);
        foreach ($category as $k => $v) {
            if($v['parent_id'] == 0){
	            $child_id = self::getChildrenCategoryId($category, $k);
	            self::$category[$k] = array(
                        'sole_num'    => 0,     //销售总数
	                    'sole_amount' => 0.00,  //销售总额
	                    'click_count' => 0,     //点击数
                        'child_id'    => explode(',', $child_id . $k)
	            );
	            
	        }
        }
        unset($category);
    }
    
    /**
     * 获取指定商品分类下所有分类id
     * 
     * @param array $category 所有分类数组
     * @param int   $cat_id   指定分类id
     */
    static private function getChildrenCategoryId($category, $cat_id) {
        $id = '';
    	foreach($category as $k => $v){
    		if($v['parent_id'] == $cat_id){
    			$id .= $v['cat_id'] . ',' . self::getChildrenCategoryId($category,$v['cat_id']);
    		}
    		unset($category[$k]);
    	}
    	return $id;
    }
}
?>