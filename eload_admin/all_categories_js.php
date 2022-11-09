<?php
/**
 * js所有分类,用于js树
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2014-06-16 11:13:24
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/time.fun.php');

header('content-type: text/javascript; charset=utf-8');
echo 'var ALL_CATEGORIES = ' . read_static_cache('all_categories', 2);