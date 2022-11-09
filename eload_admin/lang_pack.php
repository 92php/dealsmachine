<?php
/**
 * 模板语言中心管理程序
*/
define('INI_WEB', true);
require_once('../lib/global.php');             
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
require_once('lib/common.fun.php');
admin_priv('lang_pack');
//多语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;

/* act操作项的初始化 */
$_ACT  = 'list';
$_ID   = '';
$table = 'eload_pack_muti_lang';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = trim($_GET['id']);

//列表
if ($_ACT == 'add' || $_ACT == 'update' || $_ACT == 'insert')
{
    $cur_lang    = $_GET['cur_lang'];
	$id          = empty($_GET['id'])?0:intval($_GET['id']);
	//默认排序号
	$sql = "SELECT orders FROM " . $table. " ORDER BY orders DESC LIMIT 1";
	$result = $db->selectInfo($sql);
	if(!empty($result)) {
		$Arr['data']['orders'] = $result['orders'] + 1;
	} else {
		$Arr['data']['orders'] = 1;
	}
	if(!empty($id)){
		$sql = "SELECT * FROM " . $table. " WHERE id = $id AND lang = 'en' LIMIT 1";
		$res = $db->selectInfo($sql);
		$Arr['data'] = $res;
		// 多语言查询	 fangxin
		if(!empty($lang)) {
			foreach($lang as $value) {
				$lang = $value['title_e'];
				$sql = "SELECT * FROM ". $table ." WHERE id = $id and lang = '". $lang ."'";
				$res = $db->selectInfo($sql);
				$pack_lang[$lang] = $res;
			}
			$Arr['pack_lang'] = $pack_lang;
		}		
	}else {		
		$title         = isset($_POST['title'])?$_POST['title']:'';
		$title_value   = isset($_POST['title_value'])?$_POST['title_value']:'';
		$orders        = isset($_POST['orders'])?$_POST['orders']:'';
		$sort          = isset($_POST['sort'])?$_POST['sort']:'';
		$cur_lang      = $_REQUEST['cur_lang'];
		$file_name     = preg_replace('/\.\w+/', "", isset($_POST['file'])?$_POST['file']:'');
		if($_ACT == 'update') {
			$id  = $_POST['id'];
			$sql = "SELECT * FROM ". $table ." WHERE id=". $id ." LIMIT 1";
			$res = $db->selectInfo($sql);
			$sort = $res['sort'];
			$lang = $res['lang'];
			$file_name = $res['file_name'];		
			$sql = "UPDATE ". $table ." SET ".
				   "title = '" .str_replace('\\\'\\\'', '\\\'', $title). "', ".
				   "title_value = '$title_value', ".
				   "orders = '$orders', ".
				   "sort = '$sort', ".			   
				   "lang = '$cur_lang' ".			   
				   "WHERE id = '$id' AND lang = '". $cur_lang ."'";					   
			$res = $db->query($sql);
			create_pack_lang($cur_lang, $file_name, $table);				   
			$_ACT = 'list';
		} elseif($_ACT == 'insert') {
			$sql = "SELECT * FROM ". $table ." ORDER BY id DESC LIMIT 1";
			$res = $db->selectInfo($sql);
			$end_id = $res['id'] + 1;
			$sql = "INSERT ". $table ." (id, title, title_value, orders, sort, lang, file_name) VALUES ('$end_id', '" .str_replace('\\\'\\\'', '\\\'', $title). "', '$title_value', '$orders',  '$sort', '$cur_lang', '$file_name') ";
			$res = $db->query($sql);
			create_pack_lang($cur_lang, $file_name, $table);
			$_ACT = 'list';		
		}						
	}
	$Arr['cur_lang'] = $cur_lang;
}
//删除
elseif ($_ACT == 'del')
{
    $id           = empty($_GET['id'])?0:intval($_GET['id']);
	$file_name    = empty($_GET['file_name'])?'':$_GET['file_name'];
	if (!empty($id)){
		$sql = "DELETE FROM ". $table ." WHERE id=". $id ."";
		$db->query($sql);	
	}
	create_pack_lang('en', $file_name, $table);
	foreach($lang as $value) {
		create_pack_lang($value['title_e'], $file_name, $table);
	}
	$_ACT = 'list';
}
//多语言保存 fangxin 2013/07/29
elseif ($_ACT == 'add_save') {
	$id    = $_POST['id'];
	if(empty($id)) {
		echo '请先添加原始语言包信息!';
		exit(0);
	}
	$title         = $_POST['title'];
	$title_value   = $_POST['title_value'];
	$orders        = $_POST['orders'];
	$sort          = $_POST['sort_type'];
	$lang          = $_POST['lang'];
	$file_name     = $_POST['file_name'];
	$sql = "SELECT count(1) record_num FROM ". $table ." WHERE id = $id and lang = '". $lang ."' LIMIT 1";
	$res = $db->selectInfo($sql);
	if($res['record_num'] > 0) {
		$sql = "UPDATE ". $table ." SET ".
			   "title = '" .str_replace('\\\'\\\'', '\\\'', $title). "', ".
			   "title_value = '$title_value', ".
			   "orders = '$orders', ".
			   "sort = '$sort', ".			   
			   "lang = '$lang', ".			   
			   "file_name = '$file_name' ".			   
			   "WHERE id = '$id' AND lang = '". $lang ."'";	
		$res = $db->query($sql);	
		create_pack_lang($lang, $file_name, $table);			   
	} else {
		$sql = "INSERT ". $table ." (id, title, title_value, orders, sort, lang, file_name) VALUES ('$id', '" .str_replace('\\\'\\\'', '\\\'', $title). "', '$title_value', '$orders',  '$sort', '$lang', '$file_name') ";
		$res = $db->query($sql);
		create_pack_lang($lang, $file_name, $table);			
	}
	if($res) {
		echo '操作成功';
	} else {
		echo '操作失败';	
	}
	exit(0);
} 
//导入原始语言包
elseif($_ACT == 'import') {
	$Arr['act'] = $_ACT;
	$type       = isset($_POST['type'])?$_POST['type']:'0';
	$lang_file  = isset($_POST['lang_file'])?$_POST['lang_file']:'0';
	$file_name   = basename($lang_file);
	$file_name   = str_replace(strrchr($file_name, "."),"",$file_name);		
	if($type == 1) {
		$last_id = get_lastid($table);
		$lang    = 'en';
		$sort    = 1;
		//$last_id = 1170;				
		//JS文件导入
		if($file_name == 'var_html_languages' || $file_name == 'var_languages') {
			$_LANG = file($lang_file);
			foreach($_LANG as $key=>$value) {
				preg_match_all('/var(.+)=/', $value, $match_title);
				preg_match_all('/\'(.+)\'/', $value, $match_title_value);				
				$title       = $match_title[1][0];
				$title_value = $match_title_value[1][0];
				if(!empty($title) && !empty($title_value)) {
					$sql = "INSERT ". $table ." (id, title, title_value, orders, sort, lang, file_name) VALUES ('$last_id', '". $title ."', '". $title_value ."', '$last_id',  '$sort', '$lang', '". $file_name ."') ";
					$db->query($sql);
					$last_id ++;					
				}
			}		
		} else {
			//PHP文件导入
			require_once($lang_file);
			foreach($_LANG as $key=>$value) {
				$title       = addslashes($key);
				$title_value = addslashes($value);
				$sql = "INSERT ". $table ." (id, title, title_value, orders, sort, lang, file_name) VALUES ('$last_id', '". $title ."', '". $title_value ."', '$last_id',  '$sort', '$lang', '". $file_name ."') ";
				$db->query($sql);
				$last_id ++;
			}			
		}	
		echo '<script>alert("导入成功");location.href="lang_pack.php";</script>';	
	}	
	$_ACT = 'add';	
}
//列表
if ($_ACT == 'list')
{
	$search_lang      = isset($_POST['lang'])?$_POST['lang']:'';
	$lang_file = isset($_POST['lang'])?$_POST['lang_file']:'';	
    $sql = "SELECT * FROM " . $table. " WHERE 1"; 
	if(is_array($lang)) {
		$sql .= " AND (";
		foreach($lang as $value) {
			$sql .= " lang = '". $value['title_e'] ."' OR";	
		}
		$sql .= " lang = 'en')";	
	}
	if(!empty($search_lang)) {
		$sql .= " AND lang = '". $search_lang ."'";
	}
	if(!empty($lang_file)) {
		$sql .= " AND file_name = '". $lang_file ."'";
	}	
	$sql .= " ORDER BY orders ASC";
    $res = $db->arrQuery($sql);
	$Arr['lang'] = $lang;
	$Arr['lang_file'] = $lang_file;
	$Arr['data'] = $res;			
}

//更新语言包文件
/* 
 $cur_lang  需更新语言文件夹
 $file_name 需更新的语言文件
 $table     数据库表
 */
function create_pack_lang($cur_lang, $file_name, $table) {
	/*
	global $db;
	$sql = "SELECT * FROM ". $table ." WHERE lang = '". $cur_lang ."' AND file_name = '". $file_name ."' ORDER BY orders ASC";
	$result = $db->arrQuery($sql);
	$str = '';
	if($file_name == 'CreditCard' || $file_name == 'GoogleCheckout' || $file_name == 'WesternUnion' || $file_name == 'WiredTransfer' || $file_name == 'paypal') {
		$str .= "global " . '$_LANG;' . "\r\n";
	}
	foreach($result as $value) {	
		$sort = $value['sort'];					
		$title_value = $value['title_value'];	
		switch($file_name) {
			default:
				//判断是否是数组变量
				if(!check_array($title_value)) {					
					//单引号加转义符					
					if(strpos($title_value, "'")) {
						$title_value = str_replace("'", "\'", $title_value);	
					} elseif(strpos($title_value, "'")) {
						$title_value = str_replace("'", "\'", $title_value);
					}		
				}
				break;	
		}	
		// sort 1为PHP文件； sort 2为JS文件	
		if($sort == 1) {
			//非数组不加单引号
			if(check_array($title_value)) {		
				$str .= "\$_LANG['". $value['title'] ."'] = ". $title_value .";\r\n";
			} else {
				$title = $value['title'];
				//对字符串末尾字符为数字0的字符串进行截取，例：oselect'][0
				$title_end   = substr($title, (strlen($title)-1), strlen($title));
				if(is_numeric($title_end)) {
					//对 rma_apply_tip_1 等特殊字符串进行判断
					if(check_special($title)) {
						$str .= "\$_LANG['". $title ."'] = '". $title_value ."';\r\n";
					} else {
						$str .= "\$_LANG['". $title ."] = '". $title_value ."';\r\n";
					}					
				} else {
					$str .= "\$_LANG['". $title ."'] = '". $title_value ."';\r\n";
				}				
			}
		} elseif ($sort == 2) {
			$str .= "var ". $value['title'] ." = '". $title_value ."';\r\n";
		}
		$title_value = '';
	}
	switch($file_name){
		case 'common':
			$folder_path  = 'languages/'. $cur_lang .'/';
			$file_path    = 'languages/'. $cur_lang .'/common.php';
			break;
		case 'shopping_flow':
			$folder_path  = 'languages/'. $cur_lang .'/';
			$file_path    = 'languages/'. $cur_lang .'/shopping_flow.php';
			break;
		case 'user':
			$folder_path  = 'languages/'. $cur_lang .'/';
			$file_path    = 'languages/'. $cur_lang .'/user.php';
			break;	
		case 'CreditCard':
			$folder_path  = 'languages/'. $cur_lang .'/payment/';
			$file_path    = 'languages/'. $cur_lang .'/payment/CreditCard.php';
			break;
		case 'GoogleCheckout':
			$folder_path  = 'languages/'. $cur_lang .'/payment/';
			$file_path    = 'languages/'. $cur_lang .'/payment/GoogleCheckout.php';
			break;
		case 'WesternUnion':
			$folder_path  = 'languages/'. $cur_lang .'/payment/';
			$file_path    = 'languages/'. $cur_lang .'/payment/WesternUnion.php';
			break;
		case 'WiredTransfer':
			$folder_path  = 'languages/'. $cur_lang .'/payment/';
			$file_path    = 'languages/'. $cur_lang .'/payment/WiredTransfer.php';
			break;
		case 'paypal':
			$folder_path  = 'languages/'. $cur_lang .'/payment/';
			$file_path    = 'languages/'. $cur_lang .'/payment/paypal.php';
			break;	
		case 'var_html_languages':
			$folder_path  = 'temp/skin3/minjs/languages/'. $cur_lang .'/';
			$file_path    = 'temp/skin3/minjs/languages/'. $cur_lang .'/var_html_languages.js';
			break;	
		case 'var_languages':
			$folder_path  = 'temp/skin3/minjs/languages/'. $cur_lang .'/';		
			$file_path    = 'temp/skin3/minjs/languages/'. $cur_lang .'/var_languages.js';
			break;																				
	}
	$folder_path = ROOT_PATH . $folder_path;
	$file_path   = ROOT_PATH . $file_path;
	if(!empty($file_path)) {
		if(!file_exists($folder_path)){
			mkdir($folder_path);
		}	
		if($sort == 1) {
			$data = "<?php\r\n";
			$data .= $str;
			$data .= "\r\n?>";		
		} elseif ($sort == 2) {
			$data = $str;					
		}
		file_put_contents($file_path, $data);				
	}
	*/
}

//检测字符串是否为数组
function check_array($str) {
	if(!empty($str)) {
		if(!stristr($str, 'array')) {
			return 0;		
		} else {
			return 1;
		}
	}
}
//获取表中记录最大排序数
function get_orders() {
	global $db;
	$sql = "SELECT orders FROM " . $table. " ORDER BY orders DESC LIMIT 1";
	$result = $db->selectInfo($sql);	
	if($result) {
		$orders = $result['orders'] + 1;
		return $orders;
	}
}

//获取表中记录最后ID+1
function get_lastid($table) {
	global $db;
	$sql = "SELECT id FROM " . $table. " ORDER BY id DESC LIMIT 1";
	$result = $db->selectInfo($sql);	
	if($result) {
		$last_id = $result['id'] + 1;
		return $last_id;
	} else {
		return 1;
	}
}

//检测特殊字符
function check_special($str) {
	$special_arr = array(
					'By_creating1',
					'By_creating2',
					'Ordering_from1',
					'Ordering_from2',
					'Registration_str1',
					'addressline1',
					'addressline2',
					'Affiliate_desc1',
					'Affiliate_desc2',
					'rma_apply_tip_1',
					'rma_apply_tip_2',
					'rma_apply_tip_3',
					'rma_apply_tip_4',
					'rma_apply_tip_5',
					'rma_apply_tip_6',
					'rma_apply_tip_7',
					'rma_apply_tip_8',
					'rma_apply_tip_9',
					'rma_apply_tip_10',
					'rma_apply_tip_11',
					'rma_apply_12',
					'rma_attachment_1',
					'rma_attachment_2',
					'rma_attachment_3',
					'Flat_Rate_Shipping1',
					'Flat_Rate_Shipping2',
					'Standard_Shipping1',
					'Standard_Shipping2',
					'Expedited_Shipping1',
					'Expedited_Shipping2',
					'whsm_desc_1',
					'whsm_desc_2',
					'whsm_desc_3',
					'whsm_desc_4',
					'whsm_desc_5',
					'flow_str1',
					'flow_str2',
					'flow_str3',
					'flow_str4',
					'flow_str5',
					'flow_str6',
					'flow_str7',
					'flow_str8',
					'flow_str9',
					'flow_str10',
					'flow_str11',
					'flow_str13',
					'flow_str14',
					'Use_Points1',
					'Use_Points2',
					'Use_Points3',
					'Use_Points4',
					'Use_Points5',
					'Use_Points6',
					'Use_Points7',
					'Use_Points8',
					'Use_Points9',
					'addtocart_msg1',
					'addtocart_msg2',
					'addtocart_msg3',
					'addtocart_msg4',
					'addtocart_msg5',
					'addtocart_msg6',
					'addtocart_msg7',
					'addtocart_msg8',
					'addtocart_msg9',
					'addtocart_msg10',
					'addtocart_msg11',
					'addtocart_msg12',
					'addtocart_msg13',
					'help_des_1',
					'point_des_1',
					'point_des_2',
					'point_des_3',
					'shipping_des_1',
					'shipping_des_2',
					'shipping_des_3',
					'shipping_des_4',
					'shipping_des_5',
					'foot_help11',
					'foot_help12',
					'foot_help21',
					'foot_help22',
					'foot_help31',
					'foot_help32',
					'foot_help41',
					'foot_help42',
					'foot_help51',
					'foot_help52',
					'foot_help61',
					'foot_help62',
					'Bottom_Navigation1',
					'Bottom_Navigation2',
					'Bottom_Navigation3',
					'Bottom_Navigation4',
					'Bottom_Navigation5',
					'Bottom_Navigation6',
					'Bottom_Navigation7',
					'Bottom_Navigation8',
					'Bottom_Navigation9',
					'Bottom_Navigation10',
					'Bottom_Navigation11',
					'Bottom_Navigation12',
					'Bottom_Navigation13',
					'add_to_cart_img_src1',
					'add_to_cart_img_src0',
					'inquiry_des_1',
					'Reviews_1',
					'Reviews_2',
					'Reviews_3',
					'Reviews_4',
					'Reviews_5',
					'Reviews_6',
					'Reviews_8',
					'Reviews_9',
					'Reviews_10',
					'Reviews_11',
					'Reviews_12',
					'Inquiries_1',
					'Inquiries_2',
					'Inquiries_3',
					'Inquiries_4',
					'Inquiries_5',
					'Product_Reviews_desc1',
					'Product_Reviews_desc2',
					'Product_Reviews_desc3',
					'get_code_WesternUnion_1',
					'get_code_WesternUnion_2',
					'ship_in_24_hrs_1',
					'customer_service_1',
					'customer_service_2',
					'customer_service_3',
					'category_des_1',
					'category_des_2',
					'category_des_3',
					'category_des_4',
					'category_des_5',
					'category_des_6',
					'category_des_7',
					'category_des_8',
					'win_point_des_1',
					'webad_des_1',
					'method_des_2',
					'method_des_3',
					'special_des_1',
					'special_des_2',
					'no_results1',
					'no_results2',
					'no_results3',
					'no_results4',
					'Search_Feedback1',
					'Search_Feedback2',
					'Search_Feedback3',
					'Write_a_review_1',
					'Write_a_review_2',
					'Write_a_review_3',
					'Write_a_review_4',
					'Write_a_review_5',
					'review_des_1',
					'review_des_2',
					'review_des_3',
					'review_des_4',
					'review_des_5',
					'review_des_6',
					'review_des_7',
					'review_des_8',
					'review_des_9',
					'review_des_10',
					'review_des_11',
					'review_des_12',
					'review_des_13',
					'review_des_14',
					'review_des_15',
					'review_des_16',
					'review_des_17',
					'review_des_18',
					'review_des_20',
					'review_des_21',
					'review_des_22',
					'review_des_23',
					'review_des_24',
					'review_des_25',
					'review_des_26',
					'review_des_27',
					'group_limit_qty_tips_str1',
					'group_limit_qty_tips_str2',
					'Submit_an_inquiry_desc1'																																																						
					);
	if(in_array($str, $special_arr)) {
		return 1;
	} else {
		return 0;
	}				
}

$_ACT = $_ACT == 'msg'?'msg':'lang_pack_'.$_ACT;
temp_disp();

?>