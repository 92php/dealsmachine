<?php
/**
 * class.analyze_customers_action.php      订单国家分析类
 * 
 * @author                                 mashanling(msl-138@163.com)
 * @date                                   2012-03-16
 * @last modify                            2012-03-16 by jim liang
 */

//!defined('INI_WEB') && exit('Access denied!');

class AnalyzeCountrySale extends Analyze {
    public $key_record = 'lastSetAnalyzeCountrySale';
    public $table      = ANALYZE_COUNTRY;
    public $name       = '购买国家';
	/**
     * 获取订单国家统计数据
     * 
     * @param int $unixtime 当天unix gmt时间戳
     * 
     */
    static function setData($unixtime_arr) {
        global $db;
       	        //print_r($unixtime_arr);
      
        $c_arr = $db->arrQuery('select country_code as region_code,country_code,country_cn from eload_country_st');
         
		//print_r($c_arr);
        //exit();
        if(empty($c_arr))return ;
        foreach ($unixtime_arr as $v) {
        	$db->delete(ANALYZE_COUNTRY,"unixtime = $v[unixtime]");
            $unixtime = $v['unixtime'] + 25200;    //当天unix gmt时间戳
            
            //self::getTodayOrder($unixtime);//当天订单信息
            //self::getCateories(true);
            //exit();
            $sql = "select r.region_code ,sum(order_amount) as order_amount,count(*) as order_count  
from eload_order_info i inner join eload_region r on i.country=r.region_id  where   add_time BETWEEN $unixtime AND ( $unixtime + 86399) group by region_code ";      
            // echo $sql;
            //exit();      
            $arr_contry_order = $db->arrQuery($sql);
            $arr_contry_order = fetch_id($arr_contry_order,'region_code') ;
    
            
            //已付款订单国家统计
            $sql = "select r.region_code,sum(order_amount) as order_amount,count(*) as order_count 
from eload_order_info i inner join eload_region r on i.country=r.region_id  where   add_time BETWEEN $unixtime AND ( $unixtime + 86399) and order_status between 1 and 8 group by region_code ";
            $arr_contry_paid=$db->arrQuery($sql);      
            $arr_contry_paid = fetch_id($arr_contry_paid,'region_code') ;
             
            //print_r($arr_contry_paid);
           // exit();
            //订单国家统计-一周前   
              
            $sql = "select r.region_code ,sum(order_amount) as order_amount,count(*) as order_count 
from eload_order_info i inner join eload_region r on i.country=r.region_id  where   add_time BETWEEN $unixtime-7*24*3600 AND ( $unixtime -6*24*3600
)   group by region_code ";
            //echo $sql;
            $arr_contry_aweek_ago=$db->arrQuery($sql);               
            $arr_contry_aweek_ago=fetch_id($arr_contry_aweek_ago,'region_code');    

            //已付款订单国家统计-一周前      
            $sql = "select r.region_code ,sum(order_amount) as order_amount,count(*) as order_count 
from eload_order_info i inner join eload_region r on i.country=r.region_id  where add_time BETWEEN $unixtime-7*24*3600 AND ( $unixtime -6*24*3600
)  and order_status between 1 and 8 group by region_code ";
            $arr_contry_paid_aweek_ago = $db->arrQuery($sql);     
            //echo $sql;          
            $arr_contry_paid_aweek_ago = fetch_id($arr_contry_paid_aweek_ago,'region_code');         
            
            $country_arr = $c_arr;
            //print_r($country_arr);
            //print_r($arr_contry_paid);
            foreach ($country_arr as $k2=>$v2){
            	$country_arr[$k2]['unixtime'] = $v['unixtime'];
            	//print_r($arr_contry_paid[$v2['region_code']]);
            	//exit();
            	$country_arr[$k2]['paid_order_count'] = $arr_contry_paid[$v2['region_code']][0]['order_count']?$arr_contry_paid[$v2['region_code']][0]['order_count']:0;
            	$country_arr[$k2]['paid_order_sum'] = $arr_contry_paid[$v2['region_code']][0]['order_amount']?$arr_contry_paid[$v2['region_code']][0]['order_amount']:0;
            	
            	$country_arr[$k2]['all_order_count'] = $arr_contry_order[$v2['region_code']][0]['order_count']?$arr_contry_order[$v2['region_code']][0]['order_count']:0;
            	$country_arr[$k2]['all_order_sum'] = $arr_contry_order[$v2['region_code']][0]['order_amount']?$arr_contry_order[$v2['region_code']][0]['order_amount']:0;
            	
            	
            	$country_arr[$k2]['all_order_sum_aweek_ago'] = $arr_contry_aweek_ago[$v2['region_code']][0]['order_amount']?$arr_contry_aweek_ago[$v2['region_code']][0]['order_amount']:0;
            	$country_arr[$k2]['paid_order_sum_aweek_ago'] = $arr_contry_paid_aweek_ago[$v2['region_code']][0]['order_amount']?$arr_contry_paid_aweek_ago[$v2['region_code']][0]['order_amount']:0;  
            	//print_r($arr_contry_paid);
            	//exit();
            	$db->autoExecute(ANALYZE_COUNTRY, $country_arr[$k2]);    	
            }
            
            //echo 1233;
           // print_r($country_arr);
         
            
           
      
           
            
        }//end foreach1
		  // exit();
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
            $GLOBALS['db']->query('INSERT INTO ' . ANALYZE_SALES . " (unixtime, cat_id) {$_v}");
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