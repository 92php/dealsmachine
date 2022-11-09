<?php
/**
 * abc_search_info.php      abc搜索关键字信息设置，包括描述、Hot Searches、Related Tags
 * 
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-06-06 13:57:36
 * @last modify             2012-07-03 14:15:01 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');

admin_priv('abc_search_info');    //检查权限

$_ACT          = isset($_GET['act']) ? $_GET['act'] : '';    //操作
$letter_arr    = range('A', 'Z');//A-Z
$letter_arr[]  = '0-9';//0-9 替代0,1,2,3,4,5,6,7,8,9 by mashanling on 2012-07-03 14:15:33
$index         = isset($_GET['index']) ? intval($_GET['index']) : 0;
$index         = isset($_POST['index']) ? intval($_POST['index']) : $index;
$index         = array_key_exists($index, $letter_arr) ? $index : 0;
$letter        = $letter_arr[$index];
$dir           = FRONT_DATA_CACHE_PATH . 'abc_search_info/';

!is_dir($dir) && mkdir($dir);

define('ABC_SEARCH_INFO_FILE', $dir . $letter . '.php');

if ($_ACT != 'save') {
    $data = file_exists(ABC_SEARCH_INFO_FILE) ? include(ABC_SEARCH_INFO_FILE) : array();
}

switch ($_ACT) {
    case 'get_data'://获取数据
        exit($data ? json_encode($data) : '');
        break;
        
    case 'save':    //保存
        $hot         = isset($_POST['hot']) ? stripslashes(trim($_POST['hot'])) : '';
        $related     = isset($_POST['related']) ? stripslashes(trim($_POST['related'])) : '';
        
        $info['web_title']   = isset($_POST['web_title']) ? stripslashes(trim($_POST['web_title'])) : '';
        $info['meta_keyword']   = isset($_POST['meta_keyword']) ? stripslashes(trim($_POST['meta_keyword'])) : '';
        $info['meta_description'] = isset($_POST['meta_description']) ? stripslashes(trim($_POST['meta_description'])) : '';
        $info['description'] = isset($_POST['description']) ? stripslashes(trim($_POST['description'])) : '';
        $info['hot']         = $hot ? explode_textarea_keywords($hot) : '';
        $info['related']     = $related ? explode_textarea_keywords($related) : '';
        $data                = var_export($info['description'] || $info['hot'] || $info['related'] || $info['web_title'] || $info['meta_keyword'] || $info['meta_description'] ? $info : array(), true);
        $abc_info            = '<?php /*abc关键字信息，字母#LETTER#.后台自动生成，最后更新时间：' . local_date('Y-m-d H:i:s') . "*/ \nreturn {$data};\n?>";
        
        $file_arr = isset($_POST['all']) ? $letter_arr : array($letter);
        
        foreach($file_arr as $v) {
            file_put_contents($dir . $v . '.php', str_replace('#LETTER#', $v, $abc_info), LOCK_EX);
        }
        
        admin_log('', _EDITSTRING_ . 'abc关键字信息'  . $letter, '');
        exit(json_encode($info));
        break;
}

$Arr['letter_arr'] = $letter_arr;
$Arr['data']       = $data;
$_ACT = 'abc_search_info';
temp_disp();
?>