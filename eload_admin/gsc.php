<?php
/**
 * Google Content API for Shopping，谷歌购物内容api，手工指定需要更新及删除的产品
 *
 * @file                    gsc.php
 * @author                  mashanling <msl-138@163.com>
 * @date                    2014-04-03 11:12:41
 * @lastmodify              $Date: 2014-04-04 09:00:16 +0800 (周五, 2014-04-04) $ $Author: msl $
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once(ROOT_PATH . 'lib/is_loging.php');
require_once(ROOT_PATH . 'lib/time.fun.php');

admin_priv('gsc');    //检查权限

$_ACT = isset($_GET['action']) ? $_GET['action'] : '';    //操作
$gsc  = new GSC();

if ('save' == $_ACT) {//保存
    $gsc->save();
    exit(json_encode(array('success' => true)));
}
else {
    $Arr['data'] = $gsc->get();
    $_ACT = 'gsc';
    temp_disp();
}

class GSC {
    /**
     * 获取数据
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2014-04-03 11:19:32
     *
     * @return string 数据,一行一个
     */
    public function get() {
        $data   = read_static_cache('gsc');
        $result = array();

        if (is_array($data)) {

            foreach ($data as $k => $v) {
                $result[$k] = join("\n", $v);
            }
        }

        return $result;
    }

    /**
     * 保存
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2014-04-03 11:19:54
     *
     * @return void 无返回值
     */
    public function save() {
        $data_arr = array();

        foreach($_POST['data'] as $k => $v) {
            $data_arr[$k] = array();

            foreach (explode("\n", $v) as $goods_sn) {
                $goods_sn   = trim($goods_sn);

                if ($goods_sn) {
                    $data_arr[$k][$goods_sn] = $goods_sn;
                }
            }
        }

        write_static_cache('gsc' , $data_arr);

        if (class_exists('Logger', false)) {
            Logger::filename(LOG_FILENAME_PATH);
            trigger_error($_SESSION['WebUserInfo']['real_name'] . 'gsc' . var_export($data_arr, true));
        }
    }
}