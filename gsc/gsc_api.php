<?php
/**
 * gsc_api.php              Google Content API for Shopping，谷歌购物内容api接口
 *
 * @author                  mashanling <msl-138@163.com>
 * @date                    2014-05-05 15:50:02
 * @lastmodify              $Date: 2014-05-15 14:29:55 +0800 (周四, 15 五月 2014) $ $Author: liangwaihong $
 */
set_time_limit(0);
ini_set('memory_limit', '512M');
define('INI_WEB', true);
require(dirname(dirname(__FILE__)) . '/lib/global.php');
require_once(ROOT_PATH . 'lib/time.fun.php');

$start_time = microtime(true);

if ('cli' == PHP_SAPI) {//命令行
    $action = $_SERVER['argv'][1];
}
elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
}
else {
    $action = 'get';
}

if (strpos($action, ',')) {
    $args   = explode(',', $action);
    $action = array_shift($args);
}
else {
    $args = array();
}

$gsc = new GSC_API();

call_user_func_array(array($gsc, $action . 'Action'), $args);

class GSC_API {
    /**
     * @var string $_sql 获取商品数据sql
     */
    private $_sql = null;

    /**
     * 缓存商品属性
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-09 14:19:00
     *
     * @return void 无返回值
     */
    private function _cacheAttrs() {
        $attrs  = array();
        $sql    = 'SELECT attr_value,goods_id,attr_id FROM ' . GATTR . ' WHERE goods_id IN(' . $this->_goods_ids . ')';
        $db     = $this->_getDb();

        $db->query($sql);

        while ($row = $db->fetchArray()) {
            $attrs[$row['goods_id']][$row['attr_id']] = 'AS THE PICTURE' == $row['attr_value'] ? 'WHITE' : $row['attr_value'];
        }

        write_static_cache('gsc_attrs', $attrs, 1);
    }//end _cacheAttrs

    /**
     * 获取指定分类下所有子类id
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-09 11:32:14
     *
     * @param int $cat_id 分类id
     * @param bool $return_array true返回数组形式
     * @param bool $include_self true包含本身
     *
     * @return string 所有子类id，如果没有子类，返回空字符串或空数组
     */
    private function _getChildrenIds($cat_id, $return_array = true, $include_self = true) {
        $cat_arr    = read_static_cache('category_c_key', 2);

        if (!isset($cat_arr[$cat_id])) {
            return $return_array ? array($cat_id) : $cat_id;
        }

        $cat_info       = $cat_arr[$cat_id];
        $node           = $cat_info['node'];
        $level          = $cat_info['level'];
        $children_ids   = $include_self ? $cat_id : '';

        foreach ($cat_arr as $k => $v) {

            if (0 === strpos($v['node'], $node . ',') && $v['level'] > $level && $k != $cat_id) {
                $children_ids .= ',' . $k;
            }
        }

        $children_ids = trim($children_ids, ',');

        if ($return_array) {
            return explode(',', $children_ids);
        }
        else {
            return $children_ids;
        }
    }//end _getChildrenIds

    /**
     * 获取db
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-09 14:52:48
     *
     * @return object db实例
     */
    private function _getDb() {
        return $GLOBALS['db'];
    }

    /**
     * 获取sphinx
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-09 11:09:37
     *
     * @return object sphinx实例
     */
    private function _getSphinx() {
        require(ROOT_PATH . 'lib/sphinxapi.php');
        $sort   = 'goods_number DESC, week2sale DESC,@id DESC';
        $sphinx = new SphinxClient();
        $sphinx->SetServer(SPH_HOST, SPH_PORT);
        $sphinx->SetSelect('week2sale');
        $sphinx->SetSortMode(SPH_SORT_EXTENDED, $sort);

        return $sphinx;
    }

    /*
     * 压缩输出
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-09 13:43:36
     *
     * @param string|array $data 输出数据
     *
     * @return void 无返回值
     */
    private function _output($data) {

        if (is_string($data)) {
            echo '0' . $data;
        }
        else {
            echo base64_encode(gzcompress(json_encode($data)));
        }

        exit();
    }

    /**
     * 设置从数据库中获取的一行商品数据
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-09 14:23:20
     *
     * @param array $row 商品信息
     *
     * @return void 无返回值
     */
    private function _setGoodsRowData(&$row) {
        static $now = null;
        static $attrs = null;
        static $exchange_arr = null;
        static $color_attr_number = null;
        static $size_attr_number = null;
        static $cat_arr = null;

        if (null === $now) {
            $now                = gmtime();
            $attrs              = read_static_cache('gsc_attrs', 1);
            $exchange_arr       = read_static_cache('exchange', 2);
            $exchange_arr       = $exchange_arr['Rate'];
            $color_attr_number  = $GLOBALS['public_goods_type_spec_id']['color'];
            $size_attr_number   = $GLOBALS['public_goods_type_spec_id']['size'];
        }

        $goods_id = $row['goods_id'];

        $row['goods_number'] = 0 == $row['goods_number'] || 0 != $row['is_delete'] || 1 != $row['is_on_sale'] ? 0 : 1;
        $row['goods_title'] = htmlspecialchars_decode($row['goods_title'], ENT_QUOTES);
        $row['goods_desc'] = preg_replace('/\s+/', ' ', strip_tags($row['goods_desc']));
        $row['color'] = isset($attrs[$goods_id][$color_attr_number]) ? $attrs[$goods_id][$color_attr_number] : 'WHITE';
        $row['size'] = isset($attrs[$goods_id][$size_attr_number]) ? $attrs[$goods_id][$size_attr_number] : 'ONE SIZE';

        //价格
        $shop_price     = $row['shop_price'];
        $promote_price  = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);

        $row['is_promote'] = $promote_price > 0 ? 1 : 0;

        if ($row['is_promote']) {
            $row['shop_price'] = $promote_price;
        }

        $row['metainfo'] = array();

        foreach($this->_post_data['sync_country'] as $country) {

            $price_unit = $this->_post_data['country_arr'][$country][1];

            if ('USD' != $price_unit) {
                $row['metainfo']['shop_price_' . $country] = round($exchange_arr[$price_unit] * $row['shop_price'], 2);
            }
            else {
                $row['metainfo']['shop_price_' . $country] = $row['shop_price'];
            }
        }

        //站点差异数据
        $row['goods_desc'] = substr('Dealsmachine' . $row['goods_title'] .htmlspecialchars_decode($row['goods_desc'], ENT_QUOTES), 0, 300);
        $row['original_img'] = get_image_path(false, str_replace('uploads/', 'ba/', $row['original_img']));
        $row['goods_grid'] = get_image_path(false, $row['goods_grid']);
        $row['link_url']     = get_details_link($row['goods_id'], $row['url_title']);

        //运费
        /*if (!empty($this->_post_data['add_shipping_cat_id_arr'])) {

            if (null === $cat_arr) {
                $cat_arr    = read_static_cache('category_c_key', 2);
            }

            if ($intersect = array_intersect($cat_id_arr, $this->_post_data['add_shipping_cat_id_arr'])) {

                $cat_id_arr = explode(',', $cat_arr[$row['cat_id']]['node']);

                foreach($this->_post_data['sync_country'] as $country) {

                    $price_unit = $this->_post_data['country_arr'][$country][1];

                    if ('USD' != $price_unit) {
                        $row['metainfo']['shop_price_' . $country] = round($exchange_arr[$price_unit] * $row['shop_price'], 2);
                    }
                    else {
                        $row['metainfo']['shop_price_' . $country] = $row['shop_price'];
                    }
                }
            }

        }*/

        $row['metainfo'] = json_encode($row['metainfo']);
    }//end _setGoodsRowData

    /**
     * 构造函数
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-05 16:00:17
     *
     * @return void 无返回值
     */
    public function __construct() {
        $sql    = 'SELECT `goods_id`, `cat_id`, `goods_sn`, `goods_title`, `url_title`,add_time,';
        $sql   .= '`goods_weight`, `shop_price`, `promote_price`, `promote_start_date`, `promote_end_date`,';
        $sql   .= 'is_delete,goods_number,is_on_sale,';
        $sql   .= '`goods_desc`, `goods_grid`, `original_img`, `week2sale` ';
        $sql   .= ' FROM  ' . GOODS . ' WHERE goods_id IN(%s)';

        $this->_sql = $sql;
    }

    /**
     * 比较数据差异
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-10 13:42:56
     *
     * @return void 无返回值
     */
    public function diffDataAction() {
        $now    = gmtime();

        if (class_exists('Filter')) {
            $data   = Filter::string('data');
        }
        else {
            $data = $_POST['data'];
        }

        if (!$data) {
            $this->_output('数据为空');
        }
        elseif (!$data = json_decode(gzuncompress(base64_decode($data)), true)) {
            $this->_output('json_decode出错');
        }
        elseif (!isset($data['manual_arr']) || empty($data['sync_country']) || empty($data['goods'])) {
            $this->_output('数据格式错误');
        }

        $this->_post_data   = $data;

        $manual_arr = $data['manual_arr'];
        $old_data   = $data['goods'];
        $new_data   = array();

        if (!$old_data) {
            $this->_output('无数据');
        }

        $db     = $this->_getDb();
        $sql    = 'SELECT goods_id,goods_sn,cat_id,goods_number,is_on_sale,is_delete,shop_price,promote_end_date,promote_start_date,promote_price FROM ' . GOODS . ' WHERE goods_id IN(' . join(',', array_keys($old_data)) . ')';

        if (!$db->query($sql)) {
            $this->_output($db->Error);
        }
        else {

            while($row = $db->fetchArray()) {
                $row['goods_number'] = 0 == $row['goods_number'] || 0 != $row['is_delete'] || 1 != $row['is_on_sale'] ? 0 : 1;

                $promote_price  = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);

                if ($promote_price > 0) {
                    $row['shop_price'] = $promote_price;
                }

                $new_data[$row['goods_id']] = $row;
            }
        }

        $update_arr = array();//更新
        $delete_arr = array();//删除
        $delete_cat = array();//对应类
        $goods_id_0 = '';//下架
        $goods_id_1 = '';//上架
        $cat_arr    = read_static_cache('category_c_key', 2);

        foreach($old_data as $goods_id => $item) {
            $goods_sn = $item['goods_sn'];

            if ($manual_arr && isset($manual_arr['delete'][$goods_sn])
                || !isset($new_data[$goods_id])
                || 0 == $new_data[$goods_id]['goods_number'] && 1 == $item['goods_number'])
            {//下架
                $delete_arr[$goods_id] = isset($cat_arr[$item['cat_id']]) ? explode(',', $cat_arr[$item['cat_id']]['node']) : array($item['cat_id']);
                $delete_cat = array_merge($delete_cat, $delete_arr[$goods_id]);
                $goods_id_0 .= ',' . $goods_id;

                if ($manual_arr && isset($manual_arr['delete'][$goods_sn])) {
                    $reset_goods_sn_cache = true;
                    unset($manual_arr['delete'][$goods_sn]);
                }
            }
            elseif (1 == $new_data[$goods_id]['goods_number']) {

                if ($manual_arr && isset($manual_arr['update'][$goods_sn])
                    || 0 == $item['goods_number']
                    || $item['shop_price'] != $new_data[$goods_id]['shop_price'])
                {//价格变更                    
                    $update_arr[$goods_id] = $new_data[$goods_id];
                    $update_arr[$goods_id]['old_price'] = $item['shop_price'];

                    if (0 == $item['goods_number']) {
                        $goods_id_1 .= ',' . $goods_id;
                    }

                    if ($manual_arr && isset($manual_arr['update'][$goods_sn])) {
                        $reset_goods_sn_cache = true;
                        unset($manual_arr['update'][$goods_sn]);
                    }
                }
            }
        }

        if ($update_arr) {
            $sql    = sprintf($this->_sql, join(',', array_keys($update_arr)));

            if (!$db->query($sql)) {
                $this->_output($db->Error);
            }
            else {

                while($row = $db->fetchArray()) {
                    $this->_setGoodsRowData($row);
                    $row['old_price'] = $update_arr[$row['goods_id']];
                    $update_arr[$row['goods_id']] = $row;
                }
            }
        }

        $data               = array(
            'update_arr'    => $update_arr,
            'delete_arr'    => $delete_arr,
            'delete_cat'    => array_unique($delete_cat),'a' => $manual_arr,
            'manual_arr'    => isset($reset_goods_sn_cache) ? $manual_arr : null,
            'goods_id_0'    => $goods_id_0,
            'goods_id_1'    => $goods_id_1,
            //'new_data'      => $new_data,
            //'old_data'      => $old_data,
        );

        unset($old_data, $new_data);

        $this->_output($data);
    }//end diffDataAction

    /**
     * 重置商品数据
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-09 10:41:33
     *
     * @return void 无返回值
     */
    public function resetDataAction() {

        if (class_exists('Filter')) {
            $data   = Filter::string('data');
        }
        else {
            $data = $_POST['data'];
        }

        if (!$data) {
            $this->_output('数据为空');
        }
        elseif (!$data = json_decode(gzuncompress(base64_decode($data)), true)) {
            $this->_output('json_decode出错');
        }
        elseif (empty($data['sync_country']) || empty($data['cat_arr'])) {
            $this->_output('数据格式错误');
        }

        $this->_post_data      = $data;

        $result_arr     = array();
        $cat_id_map     = array();//批量查询对应分类id
        $cat_arr        = $data['cat_arr'];
        $cat_arr        = array_chunk($cat_arr, 30, true);
        $sphinx         = $this->_getSphinx();

        unset($this->_post_data['cat_arr']);

        /*
        0 =>
          array (
            0 =>
                array (
                  'cat_id' => 1264,
                  'num' => '800',
                  'filter_cat_id' => 0,
                  'filter_price' => 0,
                  'add_color' => '0',
                  'add_size' => '0',
                  'add_shipping' => '0',
                ),
            1 =>
                array (
                  'cat_id' => 1265,
                  'num' => '800',
                  'filter_cat_id' => 0,
                  'filter_price' => 0,
                  'add_color' => '0',
                  'add_size' => '0',
                  'add_shipping' => '0',
                ),
            ...
        1 => ...*/
        foreach($cat_arr as $k => $item) {
            $n = 0;

            foreach($item as $cat_id => $cat_info) {
                $sphinx->resetFilters();
                $sphinx->SetLimits(0, intval($cat_info['num']), SPH_MAX_MATCHES);
                $sphinx->SetFilterRange('add_time', 1325376000, 1577836800);//2012年, 2012-2020
                $sphinx->SetFilter('goods_number', array(1));//正常销售
                $sphinx->SetFilter('cat_id', $this->_getChildrenIds($cat_id));//分类id

                if (!empty($cat_info['filter_cat_id'])) {//排除分类id
                    $sphinx->SetFilter('cat_id', array_map('intval', explode(',', $cat_info['filter_cat_id'])), true);
                }

                if (!empty($cat_info['filter_price'])) {//排除价格
                    $sphinx->SetFilterFloatRange('shop_price', floatval($cat_info['filter_price']), 9999.0);//3美金以上
                }

                if (!empty($this->_post_data['filter_goods_id_arr'])) {
                    $sphinx->SetFilter('@id', $this->_post_data['filter_goods_id_arr'], true);
                }

                $sphinx->AddQuery('', SPH_INDEX_MAIN, 'gsc');
                $cat_id_map[$k][$n] = $cat_id;
                $n++;
            }

            $result_arr[$k] = $sphinx->RunQueries();
        }

        $cat_goods_map  = array();//分类对应商品id
        $goods_ids      = '';

        foreach($result_arr as $k => $v) {

            foreach($v as $n => $item) {

                if (!empty($item['matches'])) {
                    $ids = join(',', array_keys($item['matches']));
                    $cat_goods_map[$cat_id_map[$k][$n]] = $ids;
                    $goods_ids .= ',' . $ids;
                }
            }

        }

        unset($result_arr, $sphinx);

        if (!$goods_ids) {
            $this->_output('无商品数据');
        }

        $this->_goods_ids = substr($goods_ids, 1);
        $this->_cacheAttrs();

        $attrs  = read_static_cache('gsc_attrs', 1);
        $db     = $this->_getDb();
        $sql    = sprintf($this->_sql, $this->_goods_ids);
        $data   = array();

        if (!$db->query($sql)) {
            $this->_output($db->Error);
        }
        else {

            while($row = $db->fetchArray()) {
                $this->_setGoodsRowData($row);
                $data[$row['goods_id']] = $row;
            }

            if (class_exists('Logger')) {
                Logger::filename('gsc.reset');
                trigger_error(count($data));
            }

            $this->_output(array('cat_goods_map' => $cat_goods_map, 'goods' => $data));
        }
    }//end resetDataAction

    /**
     * 同步分类
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2014-05-05 16:00:17
     *
     * @return void 无返回值
     */
    public function syncCategoryAction() {
        $cat_arr = read_static_cache('category_c_key', 2);

        foreach($cat_arr as $cat_id => $item) {
            $cat_arr[$cat_id] = array(
                'id'        => $cat_id,
                'text'      => $item['cat_name'],
                'parent_id' => $item['parent_id'],
				'level'     => $item['level'],
                'node'      => $item['node'],
            );
        }

        $this->_output($cat_arr);
    }
}