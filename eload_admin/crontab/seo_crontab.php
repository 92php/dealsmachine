<?php
/**
 * seo_crontab.php          seo定时任务
 *
 * @author                  mashanling <msl-138@163.com>
 * @date                    2013-07-22 08:59:00
 * @lastmodify              $Author: msl $ $Date: 2013-08-29 11:32:01 +0800 (Thu, 29 Aug 2013) $
 */

define('INI_WEB', true);
set_time_limit(0);
require('../../lib/global.php');
require_once(ROOT_PATH . 'lib/time.fun.php');
require(ROOT_PATH . 'lib/seo/class.seo_crontab.php');

new SEO_Crontab();