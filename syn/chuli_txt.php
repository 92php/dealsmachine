<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件


$n = empty($_GET['n'])?1:intval($_GET['n']);
$wenjian = 'tootoomail2.txt';
$txtcon = file_get_contents($wenjian); //读取文本
$txtArr = explode("\n",$txtcon);
$cn = 0;
$ln = 0;

$pernum = 1000;
$page   = empty($_GET['page'])?1:intval($_GET['page']);

$starti = $pernum * ($page - 1);
$endi   = $pernum * $page;
echo $starti.' ';
echo $endi.'<br>';

for($i = $starti;$i<$endi;$i++){
	if (empty($txtArr[$i])) {echo '完成';exit;};
	$email = str_replace('\r','',$txtArr[$i]);
	//"  . USERS . "
	        $sql = "SELECT user_id FROM eload_users_temp  WHERE email='". $email. "' LIMIT 1";
            if($db->getOne($sql)){
				$cn++;
			}else{
				$password = md5('123123');
				$sql = "INSERT INTO eload_users_temp ( `password`, `email`, `reg_time`,`last_ip`)
						VALUES ( '$password', '$email', 0,'0.0.0.0')";
				$db->query($sql);
				$ln++;
			}
	
}

echo '重复 '.$cn.'  成功'.$ln.'<br>';
$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."&n=".$n."'>";
?>

