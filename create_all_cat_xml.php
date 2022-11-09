<?
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('lib/global.php');              //引入全局文件
require(ROOT_PATH.'lib/time.fun.php');

$page   = empty($_GET['page'])?1:intval($_GET['page']);
$where  = " where is_on_sale = 1 and is_delete = 0 ";
$pernum = 10000;
$total_record = $db->getOne("SELECT ccount(*) FROM " . GOODS ." as g $where ");
$total_page   = ceil($total_record/$pernum);                                    //zong ye shu
$start        = ($page - 1) * $pernum;
exit;
if($page>$total_page){
	echo "$total_record 完成 ";
	exit;
}else{
	echo "总计：".$total_record." 当前第 $page 页，已经处理了 $start 个产品。 <br>";
}
		


$str = '<?xml version="1.0" encoding="UTF-8" ?> 
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title>Wholesale, wholesale fashion clothing, wholesale lots of low price clothing.</title> 
<description>We are professional and reliable China wholesaler, who provide all kinds of high quality wholesale fashion clothing, wholesale lots of low price clothing... </description> 
<link>http://www.sammydress.com</link> ';

$catArr = read_static_cache('category_c_key',2);

$sql = "select g.goods_title,g.goods_img,g.goods_id,g.goods_sn,g.shop_price,g.cat_id,g.goods_number,g.goods_weight,g.goods_name from ".GOODS." as g  $where   LIMIT $start ,$pernum "; 
$res = $GLOBALS['db']->arrQuery($sql);

$arr = $db->arrQuery($sql);

foreach ($arr as $key => $row){
	
	
	
	//Generating upc code:
	
	// Create 11 random numbers under 9
	$d1 = rand(1,9);
	$d2 = rand(1,9);
	$d3 = rand(1,9);
	$d4 = rand(1,9);
	$d5 = rand(1,9);
	
	$d6 = rand(1,9);
	$d7 = rand(1,9);
	$d8 = rand(1,9);
	$d9 = rand(1,9);
	$d10 = rand(1,9);
	$d11 = rand(1,9);
	
	// Formula to find check sum number
	//$check = $d1 * ($d1 + $d3 + $d5 + $d7 + $d9 + $d11) + ($d2 + $d4 +$d6 +$d8 +$d10);
	$check = 3 * ($d1 + $d3 + $d5 + $d7 + $d9 + $d11) + ($d2 + $d4 +$d6 +$d8 +$d10);
	// Remove final last digit from check to find check sum
	$check_digit = substr($check, -1);
	
	// The check sum  number will be the remainder of 10 minus the last digit of check
	// If the last digit is a zero than the check digit is also zero
	if((10 - $check_digit) == "10"){
	$d12 = "0";
	} else {
	$d12 = (10 - $check_digit);
	} 

    // Display upc i example 1 123456 78912 3
	$upc = $d1.' '.$d2.''.$d3.''.$d4.''.$d5.''.$d6.' '.$d7.''.$d8.''.$d9.''.$d10.''.$d11.' '.$d12;
	
	
	$goods_title = str_replace(array('&','>','<',"'",'"','Free','Shipping'),array('&amp;','&gt;','&lt;','&apos;','&quot;','',''),$row['goods_title']);
	$goods_title =  preg_replace('/[\x80-\xff]+/',"",$goods_title);   //去掉双字节字符
	
	$goods_img = str_replace(array('&','>','<',"'",'"'),array('&amp;','&gt;','&lt;','&apos;','&quot;'),$row['goods_img']);
	
	$brand = empty($catArr['cat_id']['cat_name'])?'':$catArr['cat_id']['cat_name'];
	$link = get_details_link($row['goods_title'],$row['cat_id'],$row['goods_id']);
	//$upc = '6912520665352';
	
	//$idlen = (9-strlen($row['goods_id']));
	///$upc = '';
	//for($i=1;$i<=$idlen;$i++){
		//$upc .= rand(0,9);
	//}
	
	//$upc = '6912'.$upc.$row['goods_id'];
	
	$nav_title = '';
	$cat_id = $row['cat_id'];	
	if($cat_id!="" && !empty($catArr[$cat_id]["parent_id"])) $nav_title = getpath($catArr,$catArr[$cat_id]["parent_id"]);
	$cat_name = empty($catArr[$cat_id]["cat_name"])?'':$catArr[$cat_id]["cat_name"];
	$cat_name = str_replace(array('&','>','<',"'",'"'),array('&amp;','&gt;','&lt;','&apos;','&quot;'),$cat_name);
	$nav_title = $nav_title.$cat_name;
	
	
	if (strpos($nav_title,'NFL Jerseys') === false && strpos($nav_title,'Video Games') === false ){
		$model_number = $cat_name;
		if (strpos($row['goods_name'],',') !== false){
			$arr = explode(',',$row['goods_name']);
			$model_number = $arr[0];
		}
		
		//$model_number = (strpos($row['goods_name'],',') === false)?$cat_name:
		
		$color = get_color();
		$Y=date('Y');
		$m=date('m');
		$d=date('d');
		
		$edata = date( "Y/m/d", mktime(0,0,0,$m,$d+20,$Y) );//一周后
		//date("Y-m-d",strtotime("+90 day")); 90天后
		$str .= "\n\n<item>\n".'<title>'.$goods_title.'  at China wholesale price</title> '."\n";
		$str .= '<g:brand>YILUTONG</g:brand>'."\n";  //品牌
		$str .= '<g:condition>new</g:condition>'."\n";
		$str .= '<description>Buy '.$goods_title.' from best China '.$model_number.' wholesale,Paypal,credict card payment accepted, China wholesale dropshipping</description> '."\n";
		$str .= '<guid>'.($key+1).'</guid> '."\n";
		$str .= '<g:image_link>http://www.sammydress.com/'.$goods_img.'</g:image_link> '."\n";
		$str .= '<link>http://www.sammydress.com'.$link.'</link> '."\n";
		$str .= '<g:mpn>'.$row['goods_sn'].'</g:mpn> '."\n";
		$str .= '<g:price>'.$row['shop_price'].'</g:price> '."\n";
		$str .= '<g:product_type>'.$nav_title.'</g:product_type> '."\n";
		$str .= '<g:quantity>'.$row['goods_number'].'</g:quantity> '."\n";
		$str .= '<g:upc>'.$upc.'</g:upc> '."\n";
		$str .= '<g:weight>'.$row['goods_weight'].' kg</g:weight>'."\n";
		$str .= '<g:color>'.$color.'</g:color>'."\n";
		$str .= '<g:year>2011</g:year>'."\n";
		$str .= '<g:model_number>'.$model_number.'</g:model_number> '."\n";	
		$str .= '<g:expiration_date>'.$edata.'</g:expiration_date> '."\n";	
		$str .= '<g:height></g:height> '."\n";	
		$str .= '<g:length></g:length> '."\n";	
		$str .= '<g:width></g:width> '."\n";	
		$str .= '<g:online_only>y</g:online_only> '."\n";	
		$str .= '<g:payment_accepted>PayPal</g:payment_accepted> '."\n";	
		$str .= '<g:payment_accepted>wiretransfer</g:payment_accepted> '."\n";	
		$str .= '<g:payment_accepted>GoogleCheckout</g:payment_accepted> '."\n";	
		$str .= '<g:price_type>negotiable</g:price_type> '."\n";		
		$str .= '</item>';
	}
}
$str .= '
</channel>
</rss>';
$filename = 'all-items'.$page.'.xml';
file_put_contents(realpath($filename),$str);
echo 'success <a href="'.$filename.'">下载'.$filename.'</a>';

	
$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."'>";
exit;


	
	
function getpath($typeArray,$Parent){
	global $nav_title;
	if($Parent !=0){
		foreach($typeArray as $keys =>$row){
			if($row["cat_id"]==$Parent){
				
	$row["cat_name"] = str_replace(array('&','>','<',"'",'"'),array('&amp;','&gt;','&lt;','&apos;','&quot;'),$row["cat_name"]);
				$nav_title = $row["cat_name"]." > ".$nav_title;
				if($row["parent_id"]!=0)getpath($typeArray,$row["parent_id"]);
			}
		}
	}
	return $nav_title;
}
	
	
function get_color(){
	$color_str = 'Black , White , Red , Silver , Pink , Brown , Grey , Golden , Blue , Green , Champagne , purple , Black with red , Black with silver , White with soft pink , Titanium with bright blue , Titanium black , Front silver,back black , Silver-white , Black with Golden Side , Black with Yello , Gold with brown , Black with Metallic Grey , Silver with chrome highlights';
   $color_arr = explode(',',$color_str);
  return $color_arr[rand(0, 24)];
}
	
	
	
	


//if (preg_match("/^[".chr(0xa1)."-".chr(0xff)."]+$/", $str)) { //只能在GB2312情况下使用 
//if (preg_match("/^[\x7f-\xff]+$/", $str)) { //兼容gb2312,utf-8
//    echo "正确输入";
//} else {
//    echo "错误输入";
//}
?>


