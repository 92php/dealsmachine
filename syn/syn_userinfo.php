<?php
/**
 * syn_userinfo.php         同步传输用户信息至erp
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-03-28 14:53:07
 * @last modify             2012-03-28 16:42:06 by mashanling
 */

set_time_limit(0);
define('INI_WEB', true);
require('../lib/global.php');
require('../lib/time.fun.php');
require('../lib/class.function.php');

$time   = microtime(true);
$email  = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) && $_GET['email'] == 'mrmsl@qq.com' ? 'mrmsl@qq.com' : '');
$user_id = empty($_REQUEST['user_id'])?0:$_REQUEST['user_id'];
$result = array(
    'info'    => false,//用户信息
    'address' => false,//用户收货地址
);

if ($user_id) {
    $info = $db->selectinfo('SELECT * FROM ' . USERS . " WHERE user_id='{$user_id}'");
    //echo 'SELECT * FROM ' . USERS . " WHERE user_id='{$user_id}'";
    if (!empty($info)) {
        $result['info'] = $info;
       // print_r($info);
        $user_id  = $info['user_id'];
        $sql      = 'SELECT a.*,c.region_code AS country FROM ' . UADDR . ' AS a JOIN ' . REGION . ' AS c ON a.country=c.region_id WHERE user_id=' . $user_id;
        $address  = $db->arrQuery($sql);
        $result['address']   = empty($address) ? false : $address;
    }
}

Logger::filename(LOG_FILENAME_PATH);
trigger_error(var_export($result, true) . execute_time($_BEGINTIME));

exit(serialize($result));
?>