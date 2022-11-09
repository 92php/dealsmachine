<?php
/**
 * fun.newsletter.php       邮件期刊
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-07-11 13:49:08
 * @last modify             2012-07-11 13:49:08 by mashanling
 */

!defined('INI_WEB') && exit('Access Denied!');

require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/class.newsletter.php');
$newsletter = new Newsletter();

$Arr['data'] = $newsletter->_list();
$Arr['seo_title'] = 'News letter - '.$_CFG['shop_title'];
$Arr['seo_keywords'] = 'News letter , '.$_CFG['shop_keywords'];
$Arr['seo_description'] = 'News letter , '.$_CFG['shop_desc'];