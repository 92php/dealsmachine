<?php
/**
 * syn_reviewlib.php        同步评论库
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2013-02-01 17:03:51
 * @lastmodify              $Author: msl $ $Date: 2013-03-22 09:35:59 +0800 (星期五, 2013-03-22) $
 */

set_time_limit(0);
define('INI_WEB', true);
require('../lib/global.php');
require(ROOT_PATH . 'lib/time.fun.php');
require(ROOT_PATH . 'lib/class.reviewlib.php');

$lib = new ReviewLib();
$act = isset($_GET['act']) ? $_GET['act'] : 'syn_data';

switch ($act) {
    case 'testSyn':
        echo serialize(array_keys(unserialize(urldecode(stripslashes($_POST['data'])))));
        break;

    case 'add_reviews'://添加评论
        $lib->addReviews();
        break;

    case 'all'://全部
        $lib->execSyn();
        $lib->addReviews();
        break;

    default://同步数据到ERP
        $lib->execSyn();
        break;
}