<?php
/**
 * Google Content API for Shopping，谷歌购物内容api定时任务
 *
 * @file                    gsc_crontab.php
 * @author                  mashanling <msl-138@163.com>
 * @date                    2013-07-22 08:59:00
 * @lastmodify              $Author: msl $ $Date: 2014-03-03 17:05:52 +0800 (周一, 2014-03-03) $
 */

set_time_limit(0);
$action = isset($_GET['action']) ? $_GET['action'] : 'update';
echo file_get_contents('http://www.dealsmachine.com/gsc/gsc.php?action=' . $action);