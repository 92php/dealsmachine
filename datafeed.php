<?php
define('INI_WEB', true);
require_once('config/config.php');              //引入全局文件
require_once('lib/global.php');              //引入全局文件
require_once('lib/time.fun.php');
require_once('lib/cls_image.php');
require_once('lib/syn_public_fun.php');
require_once('lib/csv.class.php');



$page = empty($_GET['page'])?1:$_GET['page'];
$max_page =19999;
$from = ($page-1)*$max_page;
$to =$page*$max_page;
$total_num = $db->getOne("SELECT count(*)
FROM eload_goods AS g inner JOIN eload_category AS t 
ON g.cat_id=t.cat_id 
WHERE g.is_delete=0 AND is_on_sale=1 
AND goods_number>0 ");

$sql = "
SELECT g.goods_sn AS SKU,g.goods_title,
CONCAT('http://www.dealsmachine.com/product-', g.goods_id, '.html') AS `Landing Page URL`,
t.cat_name AS `Category`,
goods_desc,
'Y' AS `Assigned To All`,
 g.shop_price AS `Price`, 
CONCAT('http://cloud.faout.com/', g.goods_thumb) AS `Image URL`
FROM eload_goods AS g inner JOIN eload_category AS t 
ON g.cat_id=t.cat_id 
WHERE g.is_delete=0 AND is_on_sale=1 
AND goods_number>0  ORDER BY goods_id limit $from,$to
";


//echo  $sql;
$a=$db->arrQuery($sql);
//print_r(array_keys($a[0]));

//print_r($a);
foreach ($a as $k=>$v){
	//exit();
	//if($v['Name']){
		$a[$k]['goods_title'] = preg_replace('/[^\w\.\(\)]/',' ',$a[$k]['goods_title']);
		$v['goods_desc'] = str_replace("\n",' ',$v['goods_desc']);
		$v['goods_desc'] = str_replace("\r",' ',$v['goods_desc']);
		$v['goods_desc'] = str_replace("\t",' ',$v['goods_desc']);
		$a[$k]['Short Description'] = substr(strip_tags($v['goods_desc']),0,254);
		$a[$k]['Product Name (Link Text)'] = substr(strip_tags($v['goods_title']),0,254);
		unset($a[$k]['goods_desc']);
		unset($a[$k]['goods_title']);
	//}
}
//print_r($a);

if(!empty($a[0]))$a[0] = array_keys($a[0]);

$csvfile = new csvDataFile("datafeed$page.csv", ",", "w");

$csvfile->printcsv($a);
	

echo '生成成功。共生成连接'.$total_num.'条';
$page++;
if($page>ceil($total_num/$max_page))exit('completed');
echo '<script>window.location.href="?page='.$page.'";</script>';
	



// gen the content of the csv file
//$csvfile = new csvDataFile("", ",", "w");
//echo $csvfile->printcsv($datas);





?>
