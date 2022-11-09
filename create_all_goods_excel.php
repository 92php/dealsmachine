<?
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('lib/global.php');              //引入全局文件
require(ROOT_PATH.'lib/time.fun.php');

header("Content-type:application/vnd.ms-excel");
header("Content-Disposition:filename=goods.xls");


$str = '<table width="100" border="1">
  <tr>
    <td>Product title</td>
    <td>URL</td>
  </tr>';

$sql = "select g.goods_title,g.goods_img,g.goods_id,g.shop_price,g.cat_id from ".GOODS." as g where g.is_on_sale = 1 and g.is_delete= 0 and  g.goods_title <>'' order by g.cat_id ";
$arr = $db->arrQuery($sql);
foreach ($arr as $key => $row){
	
	$str .=  '<tr>
    <td>'.$row['goods_title'].'</td>
    <td>http://www.dealsmachine.com'.get_details_link($row['goods_title'],$row['cat_id'],$row['goods_id']).'</td>
  </tr>';

}
		 
	$str .= '</table>';
	
	echo $str;
?>




