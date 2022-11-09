<?php
/**
 * weekly_template.php 每周邮件模板商品循环
 * 
 * @author             mashanling(msl-138@163.com)
 * @date               2011-11-15
 * @last modify        2011-11-21 by peterzhao
 */
define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/time.fun.php');
$domain='http://www.bestafford.com';
$goods_sn = empty($_GET['goods_sn']) ? '' : $_GET['goods_sn'];

!$goods_sn && exit('请输入商品编码！');
$query = $db->query('SELECT goods_id,goods_title,goods_img,shop_price,promote_price,promote_start_date,promote_end_date FROM ' . GOODS  . " WHERE FIND_IN_SET(goods_sn, '{$goods_sn}') ORDER BY FIND_IN_SET(goods_sn, '{$goods_sn}')");
?>

<table width="650" height="540" border="0" align="center" cellpadding="0" cellspacing="0" style="border:1px #ededed solid; padding:5px 0px; margin-top:6px;">
    <tr>
    <?php 
        $i = 1;
    	while (($row = $db->fetchRow($query)) !== false) {
		$price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
		$row['shop_price'] = $price > 0 ? $price : $row['shop_price'];
	?>
    <td align="center" valign="top">
        <table width="150" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td><a target="_blank" href="http://www.bestafford.com/product-<?php echo $row['goods_id']; ?>.html">
                        <img width="150" height="150" border="0" src="http://www.faout.com/<?php echo $row['goods_img']; ?>" />
                    </a>
                </td>
            </tr>
            <tr>
                <td height="58" align="center">
                    <div style="font-size:12px; font-family:Arial; line-height:14px; height:42px; overflow:hidden; margin:8px 0px;">
                        <a target="_blank" href="http://www.bestafford.com/product-<?php echo $row['goods_id']; ?>.html" style="color:#434343; text-decoration:none;"><?php echo $row['goods_title']; ?></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td height="25" align="center" valign="top" style="font-size:16px; font-family:Arial; color: #CC0000; font-weight:bold;">US $<?php printf('%.2f', $row['shop_price']); ?></td>
            </tr>
        </table>
    </td>
    <?php
        $i % 4 == 0 && print('</tr></tr>');
        $i++;                  
        } 
    ?>
  </tr>
</table>
