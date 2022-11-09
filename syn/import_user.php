<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件

include ('arr.php');

foreach($shuliang as $key => $val){
	$zriqi = $riqi[$key];
	
$sql = "update eload_users_temp set reg_time = UNIX_TIMESTAMP(concat( '$zriqi-', floor( 2 + ( rand( ) *27 ) ) , ' ', floor( ( rand( ) *24 ) ) , ':', floor( 10 + ( rand( ) *49 ) ) , ':', floor( 10 + ( rand( ) *49 ) ) ))  where  user_id > '".$shuliang[$key-1]."' and user_id <= '".$shuliang[$key]."';";
  echo $sql.'<br>';
}

exit;

////生成数量
//$a = 0;
//$zongshuliang = 0;
//foreach($shuliang as $key => $val){
//$zongshuliang = $zongshuliang + $val;
//echo $zongshuliang."','";
//}
//exit;

$cn = 0;
$ln = 0;
$pernum = 1000;
$page   = empty($_GET['page'])?1:intval($_GET['page']);

$total_record = $db->getOne("SELECT count(id) FROM temp_email ");
$total_page   = ceil($total_record/$pernum);
$start        = ($page - 1) * $pernum;
if($page>$total_page) {echo '导入完成';exit;}

$sql = "select * from temp_email  LIMIT   $start ,$pernum";
$Arr = $db->arrQuery($sql);
foreach ($Arr as $row){
//		$sql = "SELECT user_id FROM eload_users_temp WHERE email='". $email. "' LIMIT 1";
//		if($db->getOne($sql)){
//			$cn++;
//		}else{
	echo get_month($row['id']).'<br>';
	
			$email       = $row['email'];
			$reg_time    = get_month($row['id']);
			$password    = md5('kI9LoKMjnHGbG');
			$sex         = rand(0,2);
			//$address_id  = get_address_id(); 
			$last_login  = $reg_time;
			$last_time   = $reg_time; 
			$visit_count = rand(1,20);
//			
//			$sql = "INSERT INTO eload_users_temp 
//          ( email,password,sex,address_id,reg_time,last_login,last_time,visit_count) VALUES 
//          ( '$email','$password','$sex','$address_id','$reg_time','$last_login','$last_time','$visit_count')";
//			$db->query($sql);
            $user_id = $db->insertId();
			
			//新建地址
			$sql = "INSERT INTO `eload_user_address`( " .
						"address_id,user_id, firstname, lastname,email,country,province,city,addressline1,addressline2,zipcode,tel) ".
					" SELECT address_id,$user_id, firstname, lastname,email,country,province,city,addressline1,addressline2,zipcode,tel ".
					" FROM `eload_user_address` " .
					" WHERE address_id = '".$address_id."'";
		//	$db->query($sql);

//			$ln++;
//		}	
	    echo $row['email'].'<br>';
}

$sql = "INSERT INTO temp_log ( `page`, `chongfu`, `chenggong`)
		VALUES ( '$page', '$ln', '$cn')";
$db->query($sql);



echo '重复 '.$cn.'  成功'.$ln.'<br>';

$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."'>";
exit;






function get_month($id){
	$return = '0';
	include ('arr.php');
	foreach($shuliang as $key => $val){
		if($id > $shuliang[$key]){
			$return = ($key+1);
			break;
		}else{
			$return = 0;
			break;
		}
	}
	return $riqi[$return];
}




function get_address_id(){
	$sql = "SELECT count(*) FROM `eload_user_address` ";
	$num = $db->getOne($sql);
    $key = rand(1,$num);
	
}
function get_sex(){
	return rand(0,2);
}
























echo $starti.' ';
echo $endi.'<br>';

for($i = $starti;$i<$endi;$i++){
	if (empty($txtArr[$i])) {echo '完成';exit;};
	$email = str_replace('\r','',$txtArr[$i]);
	
	        $sql = "SELECT user_id FROM "  . USERS . " WHERE email='". $email. "' LIMIT 1";
            if($db->getOne($sql)){
				$cn++;
			}else{
				$password = md5('123123');
				$sql = "INSERT INTO ".USERS." ( `password`, `email`, `reg_time`,`last_ip`)
						VALUES ( '$password', '$email', 0,'0.0.0.0')";
				$db->query($sql);
				$ln++;
			}
	
}

echo '重复 '.$cn.'  成功'.$ln.'<br>';
$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."&n=".$n."'>";
?>

