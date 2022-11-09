<?php
/**
 * create_category_top_goods.php     定时生成分类商品销售前10名
 *
 * @author                           mashanling(msl-138@163.com)
 * @date                             2011-11-30
 * @last modify                      2011-12-19 17:17:30 by mashanling
 */

define('INI_WEB', true);

require_once('../../lib/global.php');
require_once(ROOT_PATH . 'lib/class.function.php');
require_once(ROOT_PATH . 'lib/param.class.php');
require_once(ROOT_PATH . 'lib/time.fun.php');
require_once(ROOT_PATH . 'lib/sphinxapi.php');

while (ob_get_level() != 0) {
    ob_end_clean();
}

ob_start();

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
    $cl->SetLimits(0, 10);
    $cl->SetFilter('is_login', array(0));
    $sort = 'goods_number DESC, week2sale DESC,  add_time DESC';
    $cl->SetSortMode(SPH_SORT_EXTENDED, $sort);    //排序
    $cl->SetGroupBy('group_goods_id', SPH_GROUPBY_ATTR, $sort);

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
	//
    if ($result === false) {
        Logger::filename(LOG_FILENAME_PATH);
        trigger_error('查询出错，错误信息：' . $cl->GetLastError());
        unset($cl, $result, $query_arr, $cat_arr);
        continue;
    }

    foreach ($result as $k => $match) {

    	//print_r($match);
        $cat_info = $query_arr[$k];
        $cat_msg  = "分类 {$cat_info['cat_name']}({$cat_info['cat_id']})";

        if (empty($match['matches'])) {    //无数据，可能出错

            $log .= "{$cat_msg} 没有匹配结果，错误信息：{$match['error']}" . PHP_EOL;
            $match['error'] != '' && ($ext = '_error');
        }
        else {

            $_t = "{$cat_msg} 匹配结果：{$match['total_found']}，查询用时：{$match['time']}";
            $log .= $_t . PHP_EOL;
            echo $_t . '<br />';
            ob_flush();
            flush();
           // print_r($log);
            //exit();
            here_get_top_goods($match['matches'], $cat_info);
        }
    }

    unset($result, $cl, $query_arr, $cat_arr);
} //end foreach分类

$log .= '执行结束，用时' . execute_time($time_start);

Logger::filename(LOG_FILENAME_PATH);
trigger_error($log);

/**
 * 计算价格区间
 *
 * @param array $match    匹配数组
 * @param int   $cat_info 分类信息
 * @see lib/lib.f.goods.php
 */
function here_get_top_goods(&$match, $cat_info){
    global $db;

    $goods_ids = join(',', array_keys($match));

    //print_r($match);
    //exit();

    $sql       = 'SELECT goods_id,goods_img,goods_title,url_title,goods_thumb,market_price,shop_price,goods_name_style,promote_price,promote_start_date,promote_end_date FROM ' . GOODS . " WHERE goods_id IN({$goods_ids}) and is_on_sale=1 and is_login=0 ORDER BY FIND_IN_SET(goods_id, '{$goods_ids}')";
    $data      = array();
    $db->query($sql);

    while($row = $db->fetchArray()) {
        $goods_id = $row['goods_id'];
        $data[$goods_id]['goods_id']     = $row['goods_id'];
        $data[$goods_id]['goods_title']     = $row['goods_title'];
        $data[$goods_id]['short_name']      = sub_str($row['goods_title'], 60);
        $data[$goods_id]['goods_img']       = get_image_path(false, $row['goods_img']);
        $data[$goods_id]['goods_thumb']     = get_image_path(false, $row['goods_thumb']);
        $data[$goods_id]['goods_style_name']= add_style($row['goods_title'], $row['goods_name_style']);
        $data[$goods_id]['url_title']       = get_details_link($goods_id, $row['url_title']);
        $data[$goods_id]['market_price']    = price_format($row['market_price']);
        $data[$goods_id]['review_count']    = $match[$goods_id]['attrs']['reviews'];
        $data[$goods_id]['is_free_shipping']    = $match[$goods_id]['attrs']['is_free_shipping'];
        $data[$goods_id]['is_24h_ship']    = $match[$goods_id]['attrs']['is_24h_ship'];

        $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        $data[$goods_id]['shop_price']      = price_format($promote_price > 0 ? $promote_price : $row['shop_price']);
        $data[$goods_id]['zhekou'] = ($row['promote_price'] == 0||$row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['promote_price'])/$row['market_price'])*100;

    }

    write_static_cache('top_goods', $data, CATEGORY_DATA_CACHE_PATH . $cat_info['cat_id'] . '/');
}