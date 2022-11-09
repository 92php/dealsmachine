<?php
/**
 * pro_link.php      获取指定分类所有产品链接
 * 
 * @author           mashanling(msl-138@163.com)
 * @date             2011-10-20
 * @last modify      2011-11-22 by mashanling
 * 
 */
define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/time.fun.php');
require_once('../lib/param.class.php');
$cat_id     = Param::get('cat_id');


$html       = '';
$days = Param::get('days');
$test = Param::get('test');
$type = Param::get('type');

if($cat_id == 'all'){
        $sql      = 'SELECT goods_id,goods_title,url_title FROM ' . GOODS . ' WHERE  is_delete=0 AND is_on_sale=1 AND goods_number>0';
        $t = gmtime()-$days*3600*24;
        if($days) $sql .= " and add_time >$t";
        if($test) echo $sql;
        $db->query($sql);
        
        while (($row = $db->fetchArray()) !== false) {
            if($type == 'url'){
  				$html .= get_details_link($row['goods_id'], $row['url_title'])."\r\n";          	
            }else 
            	$html .= sprintf('<a href="%s">%s</a>' . PHP_EOL, get_details_link($row['goods_id'], $row['url_title']), $row['goods_title']);
            }
}

$cat_id_arr = explode(',', $cat_id);
$cat_id_arr = array_map('intval', $cat_id_arr);
foreach ($cat_id_arr as $cat_id) {
    if ($cat_id) {
        $children = get_children($cat_id, '');
        $sql      = 'SELECT goods_id,goods_title,url_title FROM ' . GOODS . ' WHERE ' . $children.' AND is_delete=0 AND is_on_sale=1 AND goods_number>0';
        $t = gmtime()-$days*3600*24;
        if($days) $sql .= " and add_time >$t";
        if($test) echo $sql;
        $db->query($sql);
        while (($row = $db->fetchArray()) !== false) {
          if($type == 'url'){
  				$html .= "".get_details_link($row['goods_id'], $row['url_title'])."\r\n";          	
          }else 
            	$html .= sprintf('<a href="%s">%s</a>' . PHP_EOL, get_details_link($row['goods_id'], $row['url_title']), $row['goods_title']);
          }
    }
}

exit($html);
?>