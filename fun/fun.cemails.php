<?
/*
+----------------------------------
* 首页
+----------------------------------
*/


$Tpl->caching = false;        //使用缓存


require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');

$Arr['seo_title'] = $_CFG['shop_name'].' - Newsletters';
$Arr['shop_name'] = $_CFG['shop_name'];
$msg = '';

$act = empty($_POST['act'])?'':varFilter($_POST['act']);
$action = empty($_POST['action'])?'2':varFilter($_POST['action']);
$txtEMail = empty($_POST['txtEMail'])?'':varFilter($_POST['txtEMail']);


//判断是否提交数据
if($act == 'add'){
	
	$input_data['email'] = $txtEMail;
	$input_data['stat'] = 1;
	$input_data['hash'] = 'newletter';
	$input_data['firstname'] = '';
	
	
	$sql = "select * from ".Email_list." where email = '".$txtEMail."'";
	$emailArr = $db->selectinfo($sql);
	if ($action == '2')//订阅
	{
		if(!empty($emailArr)){
			$db->query("update ".Email_list." set stat = '1' where email = '".$txtEMail."'");
		}else{
			$db->autoExecute(Email_list, $input_data);
		}
		$msg = 'E-mail: '.$txtEMail.' has been subscribed.';
	}else{  //退订
	
		if(!empty($emailArr)){
			$db->query("update ".Email_list." set stat = '8' where email = '".$txtEMail."'");
		}
		$msg = 'E-mail: '.$txtEMail.' has been unsubscribed.';
	}
}






$Arr['msg'] = $msg;





$sql = "select cat_name,cat_id from ".ARTICLECAT." where parent_id = 13 ORDER BY sort_order,cat_id";
$ArticleCatArr = $db -> arrQuery($sql);
foreach ($ArticleCatArr as $k => $v){
	$ArticleCatArr[$k]["_childlist"] = get_article_list($v['cat_id']);
}
$Arr['ArticleCatArr'] = $ArticleCatArr;



/**
 * 获得指定的分类的文章列表
 *
 * @access  private
 * @param   integer     $cat_id
 * @return  array
 */
function get_article_list($cat_id,$key='')
{
	$sql = '';
	if ($key!=''){
		$sql = " AND (title like '%".$key."%' or content like '%".$key."%') ";
	}else{
		$sql = "AND cat_id = '$cat_id'";
	}
	
    /* 获得文章的信息 */
    $sql = "SELECT title,url_title,article_id,link ".
            "FROM " .ARTICLE. "  ".
            "WHERE is_open = 1  $sql  order by article_id limit 5";
    $arr = $GLOBALS['db']->arrQuery($sql);
    return $arr;
}



?>  