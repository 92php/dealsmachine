<?
/*
+----------------------------------
* 首页
+----------------------------------
*/
$goods_id = 0;
foreach ($_GET as $key => $val){if (intval($key)) $goods_id = $key;}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');

$_POST['act'] = empty($_POST['act'])?'':$_POST['act'];
$goods_id     = empty($_POST['goods_id'])?intval($goods_id):intval($_POST['goods_id']);
$verifycode   = isset($_POST['verifycode']) && is_string($_POST['verifycode']) ? trim($_POST['verifycode']) : '';
$sql          = "select goods_title, goods_thumb,goods_id,url_title,market_price,shop_price,promote_price,is_free_shipping,promote_start_date,promote_end_date from ".GOODS." where goods_id = '$goods_id' ";
$goods         = $db->selectinfo($sql); 
if(!empty($goods)) {
	$promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
	if(!empty($promote_price)) {
		$goods['shop_price'] = $promote_price;
		$goods['is_promote_price'] = 1;
	} else {
		$goods['is_promote_price'] = 0;
	}
	//多语言
	if($cur_lang != $default_lang) {		
		$sql = "SELECT goods_id, goods_title FROM ". GOODS ."_". $cur_lang ." WHERE goods_id = ". $goods['goods_id'] ." LIMIT 1";
		if($res = $db->selectInfo($sql)) {
			$goods['goods_title'] = $res['goods_title'];
		}
	}
	$Arr['goods']  = $goods;
	$zhekou        = ($goods['promote_price'] > 0 && $goods['market_price']>0) ? round(($goods['market_price'] - $goods['promote_price'])/$goods['market_price'],2) * 100 : '';
	$Arr['promote_zhekou'] = $zhekou;
	$Arr['review'] = get_review($goods_id,1,3,'(is_top = 1)');  //有帮助的评论
	$Arr['goods']['goods_thumb'] = get_image_path($Arr['goods']['goods_id'],$Arr['goods']['goods_thumb']);
}
if ($_POST['act'] == 'save'){	
    if (md5($verifycode) != $_SESSION['verify']) {
            $error = $_LANG['invalid_captcha'];
            $_SESSION['verify'] = null;
            show_message($error,'','', 'warning');
     }
	foreach ($_POST as $key => $val)
	{
		if ($key == 'txtQuantity' or $key == 'goods_id'){
			$_POST[$key] = intval($_POST[$key]);
		}else{
			$_POST[$key] = htmlspecialchars($_POST[$key]);
		}
		if(empty($goods_id)) {$goods_id = 1;}
		if ($key != 'txtCorpName' && empty($goods_id)){
			show_message($_LANG['the_information_you'],'','', 'warning');
		}
	}
	$_POST['addtime'] = gmtime();
	$db->autoExecute(INQUIRY, $_POST, 'INSERT');	
	if($goods_id == 1) {
		$links = array('Return to Home');
		$hrefs = array('/');
	} else {
		$links = array("Return to Product Page",'Return to Home');
		$hrefs = array(get_details_link($Arr['goods']['goods_id'],$Arr['goods']['url_title']),'/');	
	}
	show_message($_LANG['enquiry_Sub_success'],$links,$hrefs, 'success');
}
$Arr['seo_title'] = 'Wholesale Inquiry - '.$_CFG['shop_name'];
$Arr['seo_keywords']   = 'Wholesale Inquiry';
$Arr['seo_description']   = $_CFG['shop_desc'];
$Arr['shop_name']  = $_CFG['shop_name'];

?>  