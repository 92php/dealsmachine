<?php
/**
 * delete_goods.php  删除商品，包括图片，商品相册及关联表
 *
 * @author           mashanling(msl-138@163.com)
 * @date             2011-10-31
 * @last modify      2012-02-10 18:25:21 by mashanling
 */
set_time_limit(0);
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require_once(LIB_PATH . 'param.class.php');
require_once(LIB_PATH . 'class.function.php');

while (ob_get_level() != 0) {
    ob_end_clean();
}

ob_implicit_flush(true);

date_default_timezone_set('asia/shanghai');

$goods_sn   = Param::get('goods_sn');    //商品编码
$result     = '';
$time_start = microtime(true);

if ($goods_sn) {
    $goods_sn = explode(',', $goods_sn);
    $img      = 'goods_thumb,goods_img,original_img';    //商品图片
    $img_arr  = explode(',', $img);

    $photo    = 'img_url,thumb_url,img_original';    //商品相册
    $photo_arr= explode(',', $photo);

    foreach ($goods_sn as $v) {
        $v = trim($v);
        $sql  = "SELECT goods_id,{$img} FROM " . GOODS . " WHERE goods_sn='{$v}'";

        $data = $db->selectInfo($sql);

        if (empty($data)) {
            $temp    = "商品编码{$v} 不存在<br />" . PHP_EOL;
            $result .= $temp;

            echo $temp;
        }
        else {
            $goods_id = $data['goods_id'];

            $db->query('INSERT INTO ' . DELETE_GOODS . ' SELECT * FROM ' . GOODS . " WHERE goods_id={$goods_id} ON DUPLICATE KEY UPDATE " . DELETE_GOODS . '.goods_id=' . DELETE_GOODS . '.goods_id');

            $db->query('DELETE FROM ' . GOODS . ' WHERE goods_id=' . $goods_id);    //删除商品

            foreach ($img_arr as $item) {    //删除图片
                $file = ROOT_PATH . str_replace('E/', 'uploads/', $data[$item]);

                if (is_file($file)) {
                    unlink($file);
                    echo 'delete ', $file, '<br />';
                }
            }

            $sql = "SELECT {$photo} FROM " . GGALLERY . " WHERE goods_id={$goods_id}";    //商品相册
            $res = $db->query($sql);

            while ($row = $db->fetchRow($res)) {    //删除相册
                foreach ($photo_arr as $item) {
                    $file = ROOT_PATH . str_replace('E/', 'uploads/', $row[$item]);

                    if (is_file($file)) {
                        unlink($file);
                        echo 'delete ', $file, '<br />';
                    }
                }
            }

            $db->query('DELETE FROM ' . CART . ' WHERE goods_id=' . $goods_id);    //删除购物车
            $db->query('DELETE FROM ' . GGALLERY . ' WHERE goods_id=' . $goods_id);    //删除相册
            $db->query('DELETE FROM ' . COLLECT . ' WHERE goods_id=' . $goods_id);    //删除收藏的商品
            $db->query('DELETE FROM ' . GATTR . ' WHERE goods_id=' . $goods_id);    //删除商品属性
            $db->query('DELETE FROM ' . COMMENT . ' WHERE id_value=' . $goods_id);    //删除商品评论

            $temp    = "删除商品{$goods_id},编码{$v}成功<br />" . PHP_EOL;
            $result .= $temp;

            echo $temp;
        }
    }
}

Logger::filename(LOG_FILENAME_PATH);
trigger_error(str_replace('<br />', '', $result));