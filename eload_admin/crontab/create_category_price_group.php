<?php
/**
 * create_category_price_group.php   定时生成分类价格区间
 *
 * @author                           mashanling(msl-138@163.com)
 * @date                             2011-11-17
 * @last modify                      2012-10-22 10:01:25 by mashanling
 */
set_time_limit(0);
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once(ROOT_PATH . 'lib/class.function.php');
require_once(ROOT_PATH . 'lib/sphinxapi.php');

$script     = $_SERVER['SCRIPT_NAME'];
$time_start = microtime(true);
$size       = 30;
$log        = '';
$ext        = '';

$cat_all    = read_static_cache('category_c_key', 2);    //分类

empty($cat_all) && exit('无分类循环');

$loops      = ceil(count($cat_all) / $size);

for ($i = 0; $i < $loops; ++$i) {
    $offset  = $i * $size;
    $cat_arr = array_slice($cat_all, $offset, $size);    //当前执行分类
    $cl      = new SphinxClient();    //实例化sphinx
    $cl->SetServer(SPH_HOST, SPH_PORT);    //链接sphinx
    $cl->SetLimits(0, SPH_MAX_MATCHES);
    $cl->SetFilter ('is_on_sale', array(1));    //上架
    $cl->SetFilter ('is_delete', array(0));//删除
    $cl->SetGroupBy('shop_price', SPH_GROUPBY_ATTR, 'shop_price ASC');
    $cl->SetArrayResult(true);

    $query_arr = array();
    $k         = 0;
    $cat_arr   = array_slice($cat_all, $offset, $size);

    foreach ($cat_arr as $key => $val) {

        if ($val['is_show'] == 1) {
            Func::reset_sphinx_filter($cl, 'cat_id_all');    //重置cat_id
            $cat_id  = $val['cat_id'];
            $cat_ids = Func::get_category_children_ids($cat_all, $cat_id);
            $cl->SetFilter('cat_id_all', explode(',', $cat_ids . $cat_id));
            $cl->AddQuery('', SPH_INDEX_MAIN);
            $query_arr[$k] = array('cat_id' => $val['cat_id'], 'cat_name' => $cat_all[$val['cat_id']]['cat_name']);    //key值索引，循环结果时一一对应
            $k++;
        }
    }

    $result = $cl->RunQueries();

    if ($result === false) {
        Logger::filename(LOG_FILENAME_PATH);
        trigger_error('查询出错，错误信息：' . $cl->GetLastError());
        unset($cl, $result, $query_arr, $cat_arr);
        continue;
    }

    foreach ($result as $k => $match) {
        $cat_info = $query_arr[$k];
        $cat_msg  = "分类 {$cat_info['cat_name']}({$cat_info['cat_id']})";

        if (empty($match['matches'])) {    //无数据，可能出错
            $log .= "{$cat_msg} 没有匹配结果，错误信息：{$match['error']}" . PHP_EOL;
            $match['error'] != '' && ($ext = '_error');
        }
        else {
            $log .= "{$cat_msg} 匹配结果：{$match['total_found']}，查询用时：{$match['time']}" . PHP_EOL;
            get_price_group($match['matches'], $cat_info);
        }
    }

    unset($result, $cl, $query_arr, $cat_arr);
} //end foreach分类

$log .= '执行结束，用时' . execute_time($time_start);
echo $log;

Logger::filename(LOG_FILENAME_PATH);
trigger_error($log);

/**
 * 计算价格区间
 *
 * @param array $match    匹配数组
 * @param int   $cat_info 分类信息
 * @see lib/lib.f.goods.php
 */
function get_price_group(&$match, $cat_info){
    global $log;
	$price_num = count($match);
	$looptimes = $price_num < 2 ? 2 : 6;
	$pp = ceil($price_num / 5);
	$format_arr = array();

	for ($i = 1; $i < $looptimes; $i++) {

		$price1 = empty($match[($i - 1) * $pp]['attrs']['shop_price']) ? $match[$price_num - 1]['attrs']['shop_price'] : $match[($i - 1) * $pp]['attrs']['shop_price'];
        $price2 = empty($match[$i * $pp]['attrs']['shop_price']) ? $match[$price_num - 1]['attrs']['shop_price'] : $match[$i * $pp]['attrs']['shop_price'];
		if($i>1){
			$price1 = intval($price1)+0.99;
		}
        $price2 = intval($price2)+0.99;
        if ($price1 != $price2) {
			/*
            if ($i == 1) {
                $price1 = $price1 - 1 < 0 ? 0.01 : $price1 - 1;
            }
			*/
            $price1 = round($price1, 2);
            $price2 = round($price2, 2);
            $format_arr[$i] = array($price1, $price2);
            $format_arr[$i]['url'] = '/' . title_to_url($cat_info['cat_name']) . '-' . $cat_info['cat_id'] .'-price_num-' . $i . '-Wholesale.html';
            $format_arr[$i]['cat_name'] = $cat_info['cat_name'];
        }
	}

    write_static_cache('price_group', $format_arr, CATEGORY_DATA_CACHE_PATH . $cat_info['cat_id'] . '/');
}