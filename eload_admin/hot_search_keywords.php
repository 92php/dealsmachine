<?php
/**
 * hot_search_keywords.php    热门搜索关键字设置
 *
 * @author                    mashanling(msl-138@163.com)
 * @date                      2012-04-28 14:06:36
 * @last modify               2013-01-17 09:41:58 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/param.class.php');


define('SEARCH_KEYWORDS_FILE', basename(__FILE__, '.php'));

admin_priv(SEARCH_KEYWORDS_FILE);    //检查权限

$Arr['enable_arr']     = array(0 => '关闭', 1 => '开启');
$_ACT                  = Param::get('act');    //操作

switch ($_ACT) {
    case 'save':    //保存
        hot_search_keywords_save();
        break;

    default:    //列表
        hot_search_keywords_list();
        break;
}

$_ACT = 'hot_search_keywords';
temp_disp();

function hot_search_keywords_list() {
    $arr = read_static_cache(SEARCH_KEYWORDS_FILE);

    if (!$arr) {
        return false;
    }

    $data = '';

    foreach ($arr['keywords'] as $item) {
        $data .= join('|', $item) . "\n";
    }

    $arr['keywords'] = $data;

    $GLOBALS['Arr']['data'] = $arr;
}

function hot_search_keywords_save() {
    $keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : '';
    $default  = isset($_POST['default']) ? $_POST['default'] : '';
    $keywords = explode("\n", $keywords);
    $data     = array('default' => $default, 'keywords' => array());

    foreach ($keywords as $item) {
        $item = trim($item);

        if ($item) {
            $arr = explode('|', $item);
            $arr = array_map('trim', $arr);

            if ($arr[0] && !empty($arr[1])) {
                $arr[1] = 0 !== strpos($arr[1], $v = 'http://') ? $v . $arr[1] : $arr[1];
                $data['keywords'][] = $arr;
            }
        }
    }
    write_static_cache(SEARCH_KEYWORDS_FILE, $data);
    admin_log('', _EDITSTRING_ . '热门搜索关键字', var_export($data, true));
    exit();
}