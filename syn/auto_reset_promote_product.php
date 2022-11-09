<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');

//$content =  var_export($_REQUEST, true);
//file_put_contents(realpath('post.txt'),$content);
$time = gmtime();


$sql = "delete t from ".GOODSTUIJIAN." as t join ".GOODS." as g on t.goods_id=t.goods_id where g.is_promote =1 and g.promote_end_date<=$time";
$db->query($sql);

$sql = "update ".GOODS." set is_promote =0 where is_promote =1 and promote_end_date<=$time";
$db->query($sql);
//echo "success:".$yuan_shop_price;//";// => $first_price

?>