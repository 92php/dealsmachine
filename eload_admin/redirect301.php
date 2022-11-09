<?php
/**
 * redirect301.php              商品及分类301跳转设置
 *
 * @author                      mashanling(msl-138@163.com)
 * @date                        2012-11-22 10:33:57
 * @last modify                 2013-01-12 14:39:00 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require_once(LIB_PATH . 'class.function.php');

$_ACT = isset($_GET['act']) ? $_GET['act'] : '';    //操作
$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($type) {
    $class = new redirect301($type);

    if ('save' == $_ACT) {//保存
        $class->save();
    }
    else {
        exit($class->getData());
    }
}
else {//首页
    $_ACT = basename(__FILE__, '.php');
    temp_disp();
}

exit;

class Redirect301 {
    private $_basename;//文件名
    private $_cache_key;//缓存key

    /**
     * 分割一行一个字符串成数组
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2013-01-12 09:50:38
     * @last modify  2013-01-12 09:50:38 by mashanling
     *
     * @param string $data 待分割字符串
     *
     * @access private
     * @return array 数组
     */
    private function _explodeData($data) {
        $data_arr = array();

        foreach (explode("\n", $data) as $v) {
            $v   = trim($v);
            $arr = explode('=>', $v);

            if (isset($arr[1])) {
                $data_arr[$arr[0]] = $arr[1];
            }
        }

        return $data_arr;
    }

    /**
     * 将数组转化成一行一个字符串
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2013-01-12 09:48:04
     * @last modify  2013-01-12 09:48:04 by mashanling
     *
     * @param array $data 数据
     *
     * @return string 一行一个字符串
     */
    private function _joinData($data) {
        $result = '';

        if (is_array($data)) {

            foreach ($data as $k => $v) {
                $result .= trim(str_replace("'", "", $k)) ."=>". trim(str_replace("'", "", $v)) ."\n";
            }
        }

        return $result;
    }

    /**
     * 生成缓存
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-01-12 09:53:16
     * @last modify     2013-01-12 14:38:56 by mashanling
     *
     * @param array  $data 数据
     *
     * @access private
     * @return void 无返回值
     */
    private function _writeCache($data) {
        $data && ksort($data);
        write_static_cache($this->_cache_key , $data);
        admin_log('', _EDITSTRING_, $this->_cache_key);//管理员日志
        Logger::filename(LOG_FILENAME_PATH);
        trigger_error($_SESSION['WebUserInfo']['real_name'] . $this->_cache_key . var_export($data, true));
    }

    /**
     * 构造函数，并检测权限
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-01-12 09:44:11
     * @last modify     2013-01-12 09:44:11 by mashanling
     *
     * @param string $type 类型, cat分类 goods商品
     *
     * @access public
     * @return void 无返回值
     */
    public function __construct($type) {
        $this->_basename  = basename(__FILE__, '.php');
        $this->_cache_key = $this->_basename . $type;
        admin_priv($this->_basename);//检查权限
    }

    /**
     * 获取数据
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-01-12 09:55:32
     * @last modify     2013-01-12 09:55:32 by mashanling
     *
     * @access public
     * @return string 数据,一行一个
     */
    public function getData() {
        $data = read_static_cache($this->_cache_key);

        return $this->_joinData($data);
    }

    /**
     * 保存
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-01-12 10:01:50
     * @last modify     2013-01-12 10:01:50 by mashanling
     *
     * @access public
     * @return void 无返回值
     */
    public function save() {
        $this->_writeCache($this->_explodeData($_POST['data']));
    }
}