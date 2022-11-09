<?php

define('INI_WEB', true);
set_time_limit(0);
@ini_set('memory_limit','1024M');
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/lib.f.goods.php');
require_once('../lib/class.function.php');
require_once('lang/sitemap.php');

$_ACT = isset($_GET['action']) ? $_GET['action'] : 'index';

if (false === strpos($_ACT, 'crontab')) {
    require('../lib/is_loging.php');
    admin_priv('sitemap');//检查权限
    $Arr['lang'] = $_LANG;
}
else {//定时任务,curl不能?a=a&b=b
    list(, $_ACT, $is_mobile) = explode(',', $_ACT);

    if ($is_mobile) {
        $_GET['mobile'] = true;
    }
}

require(ROOT_PATH . 'lib/seo/class.seo_admin_sitemap.php');

new SEO_Admin_Sitemap($_ACT);

if ('index' == $_ACT) {//首页
    $_ACT = 'sitemap_new';
    temp_disp();
}