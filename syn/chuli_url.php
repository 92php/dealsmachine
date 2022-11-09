<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
$uploadfile = '1111.xls';
require_once '../lib/Excel/reader.php';
$data = new Spreadsheet_Excel_Reader();
$data->setOutputEncoding('CP936');
$data->read($uploadfile);
$Arr = $data->sheets[0]['cells'];
unset($data);

$page   = empty($_GET['page'])?1:intval($_GET['page']);
$pernum = 2;
$total = count($Arr);
$total_page = ceil($total/$pernum);                                    //zong ye shu
$start      = ($page - 1) * $pernum + 1;
$end = $start + $pernum;

if (empty($Arr[$start])) {echo '完成';exit;}

$chaoshi_nr  = '';
$czaipath_nr = '';
$buczpath_nr = '';

$chaoshi_wj  = 'chaoshi.txt';
$czaipath_wj = 'cz.txt';
$buczpath_wj = 'bcz.txt';

for($i=$start;$i<$end;$i++){
	
  if (empty($Arr[$i])) {echo '完成';exit;}
  $url = $Arr[$i][2];
  $content = fopen_url($url);
  
  
  if (!$content){
		$chaoshi_nr  .= "$url\n";
		echo '获取网页内容超时<br>';
  }else{
	  if (strpos($content,'davismicro.com') === false){
		  $buczpath_nr  .= "$url\n";
		  echo '目标网址不存在<br>';
	  }else{
		  $czaipath_nr  .= "$url\n";
	  }
  }
  $content = '';
  
  
  
  
}

unset($Arr);
//echo $chaoshi_nr.'<br>';
//echo $czaipath_nr.'<br>';
//echo $buczpath_nr.'<br>';

if ($chaoshi_nr){
	$chaoshi_nr  .= file_get_contents($chaoshi_wj);
	file_put_contents($chaoshi_wj,$chaoshi_nr);
}

if ($czaipath_nr){
	$czaipath_nr  .= file_get_contents($czaipath_wj);
	file_put_contents($czaipath_wj,$czaipath_nr);
}

if ($buczpath_nr){
	$buczpath_nr  .= file_get_contents($buczpath_wj);
	file_put_contents($buczpath_wj,$buczpath_nr);
}



$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."'>";
exit;
?>

