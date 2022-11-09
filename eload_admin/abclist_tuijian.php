<?php
/**
 * abclist_tuijian.php      abc列表重点推荐词
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2013-01-23 09:10:31
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require_once(LIB_PATH . 'class.function.php');

$_ACT  = isset($_GET['act']) ? $_GET['act'] : '';    //操作
$class = new Abc();

switch ($_ACT) {
    case 'get_data'://获取数据
        exit($class->getData());
        break;

    case 'save':    //保存
        $class->save();
        break;

    default://首页
        $class->index();
        temp_disp();
        break;
}

class Abc {
    private $_basename;//文件名
    private $_cache_dir;//缓存路径
    private $_letter_arr = array();//A-Z
    private $_index = 0;//字母索引
    private $_letter = 'A';//字母

    /**
     * 分割一行一个字符串成数组
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2013-01-23 09:30:45
     *
     * @param string $data 待分割字符串
     *
     * @return array 数组
     */
    private function _explodeData($data) {
        $data_arr = array();

        foreach (explode("\n", $data) as $v) {
            $v   = trim($v);

            if ($v) {
                $data_arr[] = ucwords(stripslashes($v));
            }
        }

        return array_unique($data_arr);
    }

    /**
     * 将数组转化成一行一个字符串
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2013-01-23 09:31:13
     *
     * @param array $data 数据
     *
     * @return string 一行一个字符串
     */
    private function _joinData($data) {
        return is_array($data) ? join("\n", $data) : '';
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
        write_static_cache($this->_letter , $data, $this->_cache_dir);
        admin_log('', _EDITSTRING_, "abc列表重点推荐词({$this->_letter})");//管理员日志

        Logger::filename(LOG_FILENAME_PATH);
        trigger_error($_SESSION['WebUserInfo']['real_name'] . $this->_letter . var_export($data, true));
    }

    /**
     * 构造函数
     *
     * @author      mashanling(msl-138@163.com)
     * @date        2013-01-23 09:17:37
     *
     * @return void 无返回值
     */
    public function __construct() {
        $this->_basename = basename(__FILE__, '.php');
        $this->_cache_dir = 'data-cache/' . $this->_basename;
        admin_priv($this->_basename);    //检查权限

        $this->_letter_arr = range('A', 'Z');//A-Z
        $this->_letter_arr[] = '0-9';//0-9

        $index  = isset($_GET['index']) ? intval($_GET['index']) : 0;
        $this->_index  = isset($this->_letter_arr[$index]) ? $index : 0;
        $this->_letter = $this->_letter_arr[$index];
    }

    /**
     * 获取关键字数据
     *
     * @author      mashanling(msl-138@163.com)
     * @date        2013-01-23 09:24:31
     *
     * @return string 关键字数据，一行一个
     */
    public function getData() {
        $data = read_static_cache($this->_letter, $this->_cache_dir);

        return $data ? $this->_joinData($data) : '';
    }

    /**
     * 首页
     *
     * @author      mashanling(msl-138@163.com)
     * @date        2013-01-23 09:40:24
     *
     * @return void 无返回值
     */
    public function index() {
        global $Arr, $_ACT;
        $_ACT = $this->_basename;
        $Arr['data'] = $this->getData();
        $Arr['letter_arr'] = $this->_letter_arr;
        $Arr['index'] = $this->_index;
    }

    /**
     * 保存
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-01-23 09:27:07
     *
     * @return void 无返回值
     */
    public function save() {
        $this->_writeCache($this->_explodeData($_POST['data']));
    }
}