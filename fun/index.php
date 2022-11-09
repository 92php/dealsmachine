<?php
/*
+----------------------------------
* 小功能实现
+----------------------------------
*/
define('INI_WEB', true);
require_once('../lib/front_global.php');
require_once('../lib/class.image.php');     //引入图片处理文件
require_once(ROOT_PATH . 'lib/time.fun.php');
require_once('fun.global.php');
$_ACT = 'verify';
if (!empty($_GET['act'])) $_ACT = trim($_GET['act']);


/*
+----------------------------------
* 验证码
+----------------------------------
*/
if ($_ACT=='verify'){
	Image::buildImageVerify();
}


/*
+----------------------------------
* ajax验证验证码
+----------------------------------
*/
elseif ($_ACT=='chk_ver'){
	$valid = 'true';
	$ver = !empty($_REQUEST['verifcode'])?$_REQUEST['verifcode']:'';
	if ($_SESSION['verify'] != md5(trim($ver))){
		$valid = "false";
	}
	echo  $valid;         //验证验证码
}


/*
+----------------------------------
* 判断是否已经登陆
+----------------------------------
*/
elseif ($_ACT=='chk_sign'){
	$callback = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : '';
    $firstname   = empty($_SESSION['email'])?'':(empty($_SESSION['firstname'])?'&nbsp;&nbsp;':$_SESSION['firstname']);
	echo  $callback . '('.json_encode(array('ms'=>$firstname )).')';

	exit;
}


/*
+----------------------------------
//查购物车中物品件数
+----------------------------------
*/
elseif ($_ACT=='cart_item'){
    
    //由/lib/inc.main.php转至此,异步执行 by mashanling on 2013-09-05 11:42:51
    //判断是否是推广联盟（Affiliates）
	$lkid = empty($_GET['lkid']) ? 0 : intval($_GET['lkid']);
	if($lkid)
	{
        $l_arr["from_linkid"]=$lkid;
        $l_arr["HTTP_REFERER"]= empty($_GET['referer_url'])?'':$_GET['referer_url'];
        $l_arr["ips"]=real_ip();
        $l_arr["adddate"]=gmtime();
        $statusArr=$db->autoExecute(WJ_IP,$l_arr);  //记录来访IP

        setcookie ("linkid", $lkid, time() + 3600*24*30, "/", COOKIESDIAMON);	//保存链接ID到Cookie
        setcookie ("wj_referer", $l_arr["HTTP_REFERER"], time() + 3600*24*30, "/", COOKIESDIAMON);	//保存HTTP_REFERER到Cookie

        $sql="update ".WJ_LINK." set visit_count=visit_count+1 where id=" .$lkid;
        $db->query($sql);    //点击计数器加1
    }
    
	$callback = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : '';
	$noscript = empty($_GET["noscript"])?'':$_GET["noscript"];
		$sql = "SELECT COUNT(*) FROM " . CART . " WHERE session_id = '" . SESS_ID . "' ";
		$cart_items = $db->getOne($sql);
		if ($noscript == '1'){
			echo  $callback . '('.json_encode(array('ms'=>$cart_items )).')';
			//echo $cart_items;
		}else{
			$msg ="document.write('$cart_items')";
			echo  $callback . '('.json_encode(array('ms'=>$msg)).')';
			//echo "document.write('$cart_items');";
		}
		exit();
}


/*
+----------------------------------
//查购物车中物品件数
+----------------------------------
*/
elseif ($_ACT=='goods_num'){
    $goods_id = empty($_GET['id'])?0:intval($_GET['id']);
	$newres = read_static_cache('category_goods_num_key',2);
	$num = isset($newres[$goods_id])?$newres[$goods_id]:0;
	echo 'document.write("('.$num.')");';
}


/*
+----------------------------------
//产品详细页面登陆
+----------------------------------
*/

elseif ($_ACT=='goods_details'){
	//$login_state = '<a href="/m-flow-a-cart.htm" title = "View my cart" >View my cart</a>';
	$login_state = ' ';
	if (empty($_SESSION['user_id'])){
 	   echo 'var refurl = document.location.href;';
       $login_state = '<a href="/m-users-sign.htm?reffer=\'+refurl+\'">'.$_LANG['sign_in'].'</a> '.$_LANG['to_turn_on_1_Click_ordering'] ;
	}
	echo "document.write('".$login_state."');";
}

elseif ($_ACT == 'digg') {    //digg
    $item_id = empty($_POST['itemId']) ? 0 : intval($_POST['itemId']);
    !$item_id && exit('');
    $is_set = empty($_POST['set']) ? 0 : 1;    //是否更新digg

    if ($is_set) {    //更新
        $db->query("INSERT INTO eload_goods_digg VALUES({$item_id},1) ON DUPLICATE KEY UPDATE digg_num=digg_num+1");
        $return = 'success';
    }
    else {
        $hitnum  = 0;
        $sql     = 'SELECT SUM(hitnum) AS hitnum FROM ' . GOODS_HITS ." WHERE goods_id={$item_id}";
        $hitnum += intval($db->getOne($sql));

        $sql     = "SELECT hitnum FROM eload_goods_hits_temp WHERE goods_id={$item_id}";
        $hitnum += intval($db->getOne($sql));

        $sql     = "SELECT digg_num FROM eload_goods_digg WHERE goods_id={$item_id}";
        $diggnum = intval($db->getOne($sql));

        $return  = (ceil($hitnum / 10)) + $diggnum;
    }

    echo $return;
}
elseif ($_ACT == 'rma_msg_count') {//RMA未读留言数 by mashanling on 2012-10-31 11:20:59
    empty($_SESSION['user_id']) && exit('0');

    require('../lib/class.rma.php');

    $count = RMA::getRMAMsgUnreadCount();

    exit($count);
}
elseif ('collect_info' == $_ACT) {//收集用户信息 by mashanling on 2013-05-28 14:55:21
    $data = array(
        'username'   => isset($_POST['username']) ? trim($_POST['username']) : '',
        'email'      => isset($_POST['email']) ? trim($_POST['email']) : '',
        'note'       => isset($_POST['note']) ? trim($_POST['note']) : '',
        'cat_id'     => isset($_POST['cat_id']) ? intval($_POST['cat_id']) : '',
        'url'        => isset($_POST['url']) ? trim($_POST['url']) : '',
        'price'      => isset($_POST['price']) ? floatval($_POST['price']) : '',
        'type'       => isset($_POST['type']) ? intval($_POST['type']) : 0,
        'add_time'   => gmtime(),
    );

    if ($data['username'] && $data['email'] && $data['note']) {
        $db->autoExecute(COLLECT_INFO, $data);
    }

    exit();
}



if ($_ACT=='getbizhong'){

	$language = @strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,5));
	$language_les = @strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,4));
	$language_les2 = substr($language_les,0,3);
	$eur_array = array('de-at', 'nl-be', 'fr-be', 'nl-nl', 'fi-fi', 'se-fi', 'fr-fr', 'de-de', 'en-ie', 'it-it', 'fr-lu', 'de-lu', 'pt-pt', 'es-es', 'el-gr', 'sl-si', 'el-cy', 'tr-cy', 'en-mt', 'mt-mt', 'sk-sk', 'et-ee');

	if(!(isset($_COOKIE['setbizhong'])) || $_COOKIE['setbizhong'] == 2){
		if(in_array($language, $eur_array) || preg_match("/nl/i",$language_les) || preg_match("/se/i",$language_les) || preg_match("/it/i",$language_les) || preg_match("/el/i",$language_les) || preg_match("/sl/i",$language_les) || preg_match("/mt/i",$language_les) || preg_match("/sk/i",$language_les) || preg_match("/et/i",$language_les) || (preg_match("/fr/i",$language_les) && $language != 'fr-ca'))
			echo 'EUR';
		elseif($language == 'en-us')
			echo 'USD';
		elseif($language == 'en-gb' || $language_les2 == 'en,')
			echo 'GBP';
		elseif($language == 'en-au')
			echo 'AUD';
		elseif($language == 'en-ca' || $language == 'fr-ca')
			echo 'CAD';
		elseif($language == 'ru-ru' || $language_les2 == 'ru,')
			echo 'RUB';
		else
			echo 'USD';
	}
}


/*
+----------------------------------
//产品浏览历史记录
+----------------------------------
*/
/*if ($_ACT=='history'){

		$goods_id = empty($_GET['goods_id'])?0:intval($_GET['goods_id']);

		if (!empty($_COOKIE['WEB-history']))
		{
			$history = explode(',', $_COOKIE['WEB-history']);
			array_unshift($history, $goods_id);
			$history = array_unique($history);

			while (count($history) > $_CFG['history_number'])
			{
				array_pop($history);
			}

			setcookie('WEB-history', implode(',', $history), gmtime() + 3600 * 24 * 30);
		}
		else
		{
			setcookie('WEB-history', $goods_id, gmtime() + 3600 * 24 * 30);
		}

}
*/
/*
+----------------------------------
//产品浏览历史记录列表
+----------------------------------
*/
/*if ($_ACT=='his_list'){
	$his_list = insert_history();
	if ($his_list!='')
	echo "document.write('".$his_list."');";
}
*/
?>