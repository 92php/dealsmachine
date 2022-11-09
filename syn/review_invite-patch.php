<?php
define('INI_WEB', true);
set_time_limit(0);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
require_once(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');
$Tpl->caching = false;        //使用缓存
$host         = "http://".$_SERVER['HTTP_HOST'];
$orders       = review_invite();
$i            = 0;

foreach ($orders as $k=>$order){
	$Arr['order'] = $order;
	$cat_id=get_cat_id($order);
	$goods_id=get_goods_id($order);
	$related_goods=get_hot_product_by_cat_id($cat_id,'',4,$goods_id);
	foreach ($related_goods as $k=>$v){
		$related_goods[$k]['url_title'] = $host.$related_goods[$k]['url_title'];	
		$related_goods[$k]['goods_grid'] = get_image_path($v['goods_id'],$related_goods[$k]['goods_grid']);	
	}
	$Arr['related_goods'] = $related_goods;		
	$consignee = $order[0]['consignee'];
	$email = $order[0]['email'];
	$Arr['consignee'] = $consignee;
	$Arr['demail'] = $email;
	$Arr['email'] = $email;
	foreach( $Arr as $key => $value ){
		$Tpl->assign($key, $value );
	}
	
	//获得用户信息 fangxin 2013/07/18
	$sql = "SELECT email, firstname, lang FROM " . USERS . " WHERE email = '" . $email . "'";
	$user_info =  $db->selectinfo($sql);
	$lang      = $user_info['lang'];
	if(!empty($lang)) {
		$mail_subject = $mail_conf[$lang][22];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/22.html');
	} 
	if(empty($mail_subject) || empty($mail_body)) {
		$mail_subject = $mail_conf['en'][22];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/22.html');		
	}
	$i++;
	if($email && $mail_subject && $mail_body){
		exec_send($email,$mail_subject,$mail_body);
	}	
}
$mail_subject ="dealsmachine 评论邀请数量：$i";
$mail_body = $mail_subject;
exec_send('979540037@qq.com',$mail_subject,$mail_body);
exec_send('snipersheep@163.com',$mail_subject,$mail_body);

function get_cat_id($order){
	$cat_id="";
	foreach ($order as $goods){
		$cat_id.=$goods['cat_id'].",";
	}
	$cat_id.="0";
	return $cat_id;
}

function get_goods_id($order){
	$goods_id="";
	foreach ($order as $goods){
		$goods_id.=$goods['goods_id'].",";
	}
	$goods_id.="0";
	return $goods_id;
}

/**
 * 评论邀请 
 */
function review_invite()
{
	global $db;
	global $host;	
    $sql = "select distinct(order_sn) as order_sn,min(add_time) a from eload_shipping_details  group by order_sn having a between (UNIX_TIMESTAMP(curdate())-31*24*3600) and (UNIX_TIMESTAMP(curdate())-20*24*3600)";//2012-02-04 00:00:00 -2012-02-14 00:00:00
    $orders_nos=$db->arrQuery($sql);    
    $order_str = "";
    foreach ($orders_nos as $k=>$v){   //把订单号连成字符串
    	$order_str.="'".$v['order_sn']."'," ;
    }
	$order_str.="'0'";
	$sql = "select i.order_sn,email,i.add_time,consignee,g.goods_id,g.goods_name,g.goods_sn,goods_grid,gs.cat_id from eload_order_info i,eload_order_goods g,eload_goods gs where i.order_id=g.order_id and g.goods_id = gs.goods_id and i.order_sn in($order_str) order by order_sn" ;
	$arr        = $db->arrQuery($sql);
	$orders     = array();
	$goods_str  = "";
	foreach ($arr as $k=>$v){   //加工订单数据
		$arr[$k]['url_title']        = $host.get_details_link($v['goods_id'],'');
		$arr[$k]['write_review_url'] = $host."/m-review-a-write_review-goods_id-".$v['goods_id'].".htm";
		$arr[$k]['goods_grid']       = get_image_path($v['goods_id'],$arr[$k]['goods_grid']);	
		$arr[$k]['add_time']         = local_date('Y-m-d h:m:s',$v['add_time']);		
		$goods_str                   .= $v['goods_id'].",";
	}
	$goods_str.="0";			
	//BEF:review 
	$sql = "select goods_id,count(rid) as review_count,sum(rate_overall),round(sum(rate_overall)/count(rid),1) as avg_rate from eload_review where is_pass=1 and goods_id in($goods_str) group by goods_id";
	$rev=$db->arrQuery($sql);
	foreach ($rev as $k=>$v){   
		$review[$v['goods_id']]=$v;
		$review[$v['goods_id']]['avg_rate_img'] = $host.showRate($v['avg_rate']);
		
	}
	foreach ($arr as $k=>$v){
		if(!empty($review[$v['goods_id']])){
			$v['review'] = $review[$v['goods_id']];
		}else {
			$v['review'] = "";
		}
		$orders[$v['order_sn']][] = $v;
	}
	return $orders;
}

?>

