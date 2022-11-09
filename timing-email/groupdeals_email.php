<?php

define('INI_WEB', true);
ini_set('display_errors', 1);
error_reporting(E_ALL );




require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');
require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
include(ROOT_PATH . 'languages/en/common.php');
include(ROOT_PATH . 'languages/en/user.php');
$Arr['lang']   =  $_LANG;
$Tpl->caching = false;        //使用缓存
$now_time = gmtime();


$result_msg = '';

echo  '<pre>';
$sql = "select goods_title,goods_id,is_groupbuy,goods_grid,shop_price,goods_title,goods_name,groupbuy_price,groupbuy_final_price,groupbuy_people_first_number,
groupbuy_people_final_number,groupbuy_start_date,url_title,groupbuy_end_date,goods_img,groupbuy_ad_desc,groupbuy_bought,groupbuy_chengjiao_price  from eload_goods where is_emailed = 0 and is_groupbuy = 1 and 
groupbuy_end_date < ".$now_time." and is_delete = 0 order by groupbuy_start_date desc ";
//echo $sql;
$goodsArr = $db->arrQuery($sql);
//o.email = 'oootc@126.com'


if (!empty($goodsArr))
foreach($goodsArr as $row){
	
	//判断团购是否过期
	$buyers = get_groupbuyer($row['goods_id']);
	
	
	$final_price = get_groupdeals_price($row,$buyers);
	
	$email_temp_id = 25;
	if($buyers>=$row['groupbuy_people_first_number']){	
		
		
		//echo '团购成功 ————购买人数：'.$buyers.' 原价：'.$row['shop_price'].' 成交价格：'.$final_price.'<br>';
		$email_temp_id = 25;
		//更新购物车价格
		//$sql = "update ".CART." set goods_price = '".$final_price."' where goods_id = '".$row['goods_id']."' and is_groupbuy = 1 ";
		//$db->query($sql);
		

	}else{
		
		$email_temp_id = 26;
		//恢复原价格
		//$sql = "update ".CART." set goods_price = '".$row['shop_price']."' where goods_id = '".$row['goods_id']."' and is_groupbuy = 1 ";
		$db->query($sql);
		
		//echo '<font color="#ff0000">团购失败 ————购买人数：'.$buyers.' 原价：'.$row['shop_price'].' 成交价格：'.$final_price.'</font><br>';
		
	}
	
	
	

	//更新产品成交价格和成交人数
	//$sql = "update ".GOODS." set groupbuy_bought = '".$buyers."',groupbuy_chengjiao_price = '".$final_price."',is_emailed = 1  where goods_id = '".$row['goods_id']."'";
	//$db->query($sql);
    
	

	//echo $sql;
	//echo '<br>';
	//print_r($sql);
	//exit;

	

	$list['goods_title']     = $row['goods_title'];
	$list['now_price']       = $final_price;
	$list['groupbuy_bought'] = $buyers;
    $list['shop_price']      = $row['shop_price'];
    $list['save_price']      = $row['shop_price'] - $list['now_price'];
    $list['save_off']        = round($list['save_price']/$row['shop_price'],4)*100;
	$list['url_title']       = get_details_link($row['goods_id'],$row['url_title']);
	$list['goods_grid']       = get_image_path($row['goods_id'], $row['goods_grid']);
	
	$mail_subject = $mail_conf[$email_temp_id];
	//echo $mail_subject.'<br>';
//	echo $row['groupbuy_people_first_number'];
	//exit;
	if($email_temp_id == '25'){
		$mail_subject = str_replace('{$list.goods_name}',$row['goods_name'],$mail_subject);
		$mail_subject = str_replace('{$list.now_price}',$final_price,$mail_subject);
		$mail_subject = str_replace('{$list.save_price}',$list['save_price'],$mail_subject);
		
	}
	
	//echo $mail_subject;
 // 25 => 'Congratulations! The Group Deals is successful! Now you can buy {$list.goods_name} at the price of USD {$list.now_price}. Saved USD {$list.save_price}.',


    $sql = "select session_id as email,goods_number from ".CART." where goods_id = '".$row['goods_id']."' and is_groupbuy = 1 ";
	echo $sql;
    $EmailArr = $db->arrQuery($sql);
	
	foreach($EmailArr as $val){
		
		if($val['goods_number']>2){
			//修正购买数量
			//$sql = "update ".CART." set goods_number = '2' where goods_id = '".$row['goods_id']."' and is_groupbuy = 1 ";
			//$db->query($sql);
		}
		
		
		$sql = "select user_id from ".USERS." where email = '".$val['email']."'";
		$user_id = $db->getOne($sql);
		
		$sql = "select count(*) as people_number,sum(income) as incomes from eload_point_record where user_id = '".$user_id."' and note like 'Group Deals.%'";
		$inArr = $db->selectinfo($sql);
		if(!empty($inArr)){
			$list['people_number'] = $inArr['people_number'];
			$list['incomes'] = $inArr['incomes'];
		}
		
		
		
		$list['email'] = $val['email'];
		
		$Arr['list'] =  $list;		
		$Arr['email'] = md5($val['email']);
		
		foreach( $Arr as $key => $value ){
			$Tpl->assign( $key, $value );
		}
		
		
		$email        = $val['email'];
		
		if(is_email($email))$result_msg .= $email.'<br>';
		
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'.$email_temp_id.'.html');
		//echo $mail_body;
		if(is_email($email))exec_send($email,$mail_subject,$mail_body);	
	}
	
	
}
echo "result_msg:".$result_msg;
if (!empty($result_msg)){
exec_send('158642560@qq.com','Group deals Result of email report ',$result_msg);
exec_send('snipersheep@163.com','Group deals Result of email report ',$result_msg);
}

//echo $result_msg;
exit;?>

<script type="text/javascript">

	function jump(count) {   
		window.setTimeout(function(){   
			count--;   
			if(count > 0) {                          
				jump(count);   
			} else { 
				window.open('','_self','');
				window.close();
			}   
		}, 1000);
	};

jump(5);
</script>







<?

function get_groupdeals_price($goods,$buyers){
	$final_price = false;
	if($buyers >= $goods['groupbuy_people_final_number']){
		$final_price = $goods['groupbuy_final_price'];
	}else{
		if($buyers >= $goods['groupbuy_people_first_number']){
			$biao_jiage  = $goods['groupbuy_price'] - $goods['groupbuy_final_price'];
			$biao_renshu =  $goods['groupbuy_people_final_number'] - $goods['groupbuy_people_first_number'];
			$final_price = price_format($goods['groupbuy_price'] - ($biao_jiage/$biao_renshu)*($buyers - $goods['groupbuy_people_first_number']));
		}else{
			$biao_jiage  = $goods['shop_price'] - $goods['groupbuy_price'];
			$biao_renshu = $goods['groupbuy_people_first_number'];
			$final_price = price_format($goods['shop_price'] - ($biao_jiage/$biao_renshu)*$buyers);
		}
	}
	return $final_price;
}

?>

