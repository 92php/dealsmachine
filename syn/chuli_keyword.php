<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件


$wenjian = 'ever_keyword.txt';
$txtcon = file_get_contents($wenjian); //读取文本
$txtArr = explode("\n",$txtcon);
$cn = 0;
$ln = 0;

$pernum = 100;
$page   = empty($_GET['page'])?1:intval($_GET['page']);
$diamon = 'http://'.$_SERVER['HTTP_HOST'];
$starti = $pernum * ($page - 1);
$endi   = $pernum * $page;
echo $starti.' ';
echo $endi.'<br>';

$str = file_get_contents('xinkey.txt');


for($i = $starti;$i<$endi;$i++){
	if (empty($txtArr[$i])) {echo '完成';break;};
			$goods_keyword = addslashes(trim(str_replace('\r','',$txtArr[$i])));
			$keyArr = explode(' ',$goods_keyword);
	        $sqlwhr = " 1 ";
			foreach ($keyArr as $kk => $vk)
			{
				$sqlwhr .=  " and goods_title like '%".$vk."%' ";
			}
	
			$sql = "select goods_id,url_title from ".GOODS."  where  $sqlwhr and is_on_sale = 1 and is_delete= 0 order by goods_id desc limit 1";
			$arr = $db->selectinfo($sql);
            if(!empty($arr['goods_id'])){
				$url = get_details_link($arr['goods_id'],$arr['url_title']);
				$str .=  '<A href="'.$diamon.$url.'">'.$goods_keyword."</A> \n";
				$cn++;
			}else{
				$ln++;
			}
	
}

file_put_contents('xinkey.txt',$str);

//echo $str;
echo '找到 '.$cn.'  失败'.$ln.'<br>';
$page++;
//exit;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."'>";
?>

