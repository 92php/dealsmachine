<?php
/**
 * 模板语言中心管理程序
*/
define('INI_WEB', true);
require_once('../lib/global.php');             
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('language');

/* act操作项的初始化 */
$_ACT = 'list';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = trim($_GET['id']);

/*------------------------------------------------------ */
//-- 列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
	//国家地区
	$region_arr = area_list();
    
    $sql = "SELECT * FROM " . Mtemplates_language. " ORDER BY orders ASC";
    $res = $db->arrQuery($sql);
    foreach ($res as $key=>$value)
    {
    	if(!empty($value['country']))
    	{
    		$res[$key]['region_name'] = $region_arr[$value['country']]['region_name'];	
    	}
    	else 
    	{
    		$res[$key]['region_name'] = '';
    	}
    }
	$Arr['data'] = $res;		
}

elseif ($_ACT == 'add')
{
    $language_id    = empty($_GET['id'])?0:intval($_GET['id']);
	$type_id        = empty($_GET['type_id'])?0:intval($_GET['type_id']);
	if ($language_id !='0'){
		$sql = "SELECT * FROM " . Mtemplates_language. " WHERE id = $language_id";
		$res = $db->selectinfo($sql);
		$Arr['data'] = $res;
	}
	$Arr['language_struct']['language_type'] = 0;	
	
	//国家地区
	$region_arr = area_list();
    $Arr['region_arr'] =   $region_arr;
	
	if(!empty($type_id)) {
		$sql = "SELECT * FROM " . Mtemplates_language. " ORDER BY orders ASC LIMIT 1";
		$res = $db->selectinfo($sql);
		if($res) {
			$language = $res['title_e'];
			// 多语言产品表
			$sql = "DESCRIBE ". GOODS ."_" . $language;
			$result = $db->arrQuery($sql);
			foreach($result as $value) {
				$product_struct .= "`" . $value['Field'] . "` " . strtoupper($value['Type']);
				if($value['Null'] == 'NO') {
					$product_struct .= ' NOT NULL';	
				} else {
					$product_struct .= ' DEFAULT NULL';	
				}
				$product_struct .= " ,\r\n";
			}
			$language_table['product'] = trim($product_struct);
			// 多语言邮件表
			$sql = "DESCRIBE ". Mtemplates ."_" . $language;
			$res = $db->arrQuery($sql);
			foreach($res as $value) {
				$email_struct .= "`" . $value['Field'] . "` " . strtoupper($value['Type']);
				if($value['Null'] == 'NO') {
					$email_struct .= ' NOT NULL';	
				} else {
					$email_struct .= ' DEFAULT NULL';	
				}
				$email_struct .= " ,\r\n";
			}
			$language_table['email'] = trim($email_struct);			
		}
		$language_table['language_type'] = 1;
		$Arr['language_struct'] = $language_table;
	}
}


elseif ($_ACT == 'del')
{
	exit('删除功能关闭');
    $language_id    = empty($_GET['id'])?0:intval($_GET['id']);
	$table_title    = strtolower(empty($_GET['table_title'])?'':trim($_GET['table_title']));	
	if ($language_id !='0'){
		$sql = "DELETE FROM " . Mtemplates_language. " WHERE id = '$language_id'";
		if($db->query($sql)){
			//删除相应产品语言表
			$table_sql = "DROP TABLE `". GOODS ."_". $table_title ."`";
			$db->query($table_sql);							
			//删除相应邮件语言表
			$table_sql = "DROP TABLE `". Mtemplates ."_". $table_title ."`";
			$db->query($table_sql);	
									
			sys_msg("删除成功！", 1, array(), false);
			admin_log('', _DELSTRING_, '邮件语言 ' . $language_id);
		}else{
			sys_msg("删除失败！", 1, array(), false);
		}
	}
	//多语言对应货币
	write_lang_currency_js();	
}




/*------------------------------------------------------ */
//-- 保存语言内容
/*------------------------------------------------------ */

elseif ($_ACT == 'save_language')
{
	$language_id   = empty($_POST['language_id'])?'':trim($_POST['language_id']);
    $title         = empty($_POST['title'])?'':trim($_POST['title']);
    $title_e       = strtolower(empty($_POST['title_e'])?'':trim($_POST['title_e']));
    $huobi         = strtoupper(empty($_POST['huobi'])?'':trim($_POST['huobi']));	
    $country	   = empty($_POST['country'])?0:intval($_POST['country']);
    $orders        = empty($_POST['orders'])?'':trim($_POST['orders']);
    $status        = empty($_POST['status'])?'':trim($_POST['status']);	
	$product_table_struct = empty($_POST['product_table_struct'])?'':trim($_POST['product_table_struct']);
	$email_table_struct   = empty($_POST['email_table_struct'])?'':trim($_POST['email_table_struct']);
	
	if(empty($title))
	{
		sys_msg("语言名称不能为空！", 1, array(), false);
	}
	
	if(empty($title_e))
	{
		sys_msg("语言名称（英文缩写)不能为空！", 1, array(), false);
	}
	
	if(empty($huobi))
	{
		sys_msg("货币简码不能为空！", 1, array(), false);
	}
	
	if(empty($country))
	{
		sys_msg("运费国家不能为空！", 1, array(), false);
	}
	
	if ($language_id != 0){
		$sql = "UPDATE " .Mtemplates_language. " SET ".
					"title   = '" .str_replace('\\\'\\\'', '\\\'', $title). "', ".
					"title_e = '" .str_replace('\\\'\\\'', '\\\'', $title_e). "', ".
					"huobi = '" .str_replace('\\\'\\\'', '\\\'', $huobi). "', ".
					"country = '$country', ".
					"orders  = '$orders', ".
					"status  = '$status'".
				"WHERE id = '$language_id'";
	}else{
		$sql = "SELECT COUNT(*) FROM " . Mtemplates_language . " WHERE title_e = '" . $title_e ."'";
		$count = $db->getOne($sql);
		if($count >0 )
		{
			sys_msg("语言名称（英文缩写)已经存在！", 1, array(), false);
		}
	
		$sql = "INSERT " .Mtemplates_language. " (title, title_e, orders, status, huobi, country) VALUES ('" .str_replace('\\\'\\\'', '\\\'', $title). "', '" .str_replace('\\\'\\\'', '\\\'', $title_e). "', '$orders', '$status', '".str_replace('\\\'\\\'', '\\\'', $huobi)."', '$country') ";
	}
	
	if ($db->query($sql, "SILENT"))
	{	
		//多语言对应货币
		write_lang_currency_js();
			
		if ($language_id != 0){			
			admin_log('', _EDITSTRING_, '邮件语言 ' . $language_id);
			sys_msg("修改成功！", 1, array(), false);
		}else{
			$title_e = strtolower($title_e);
			//创建相应语言产品表
			$product_table_sql = "CREATE TABLE `". GOODS ."_" . $title_e."` (".
									"`goods_id` MEDIUMINT(8) NOT NULL ,".
									"`goods_title` VARCHAR(200) DEFAULT NULL ,".
									"`goods_name` VARCHAR(120) DEFAULT NULL ,".
									"`keywords` TEXT DEFAULT NULL ,".
									"`goods_brief` TEXT DEFAULT NULL ,".
									"`goods_desc` MEDIUMTEXT DEFAULT NULL ,". 
									"`seller_note` VARCHAR(255) DEFAULT NULL ,".
									"`update_time` INT(11) unsigned DEFAULT '0'".
								 ") ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
			$db->query($product_table_sql);				
			//创建相应邮件产品表
			$email_table_sql = "CREATE TABLE `". Mtemplates ."_" . $title_e."` (".
								"`template_id` TINYINT(2) NOT NULL ,".
								"`template_subject` VARCHAR(200) NOT NULL ,".
								"`template_content` TEXT NOT NULL".
								") ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
			$db->query($email_table_sql);							
			sys_msg("添加成功！", 1, array(), false);
			admin_log('', _ADDSTRING_, '邮件语言 ' . $title);
		}

	}else{
		sys_msg("添加失败！", 1, array(), false);
	}
}
$_ACT = $_ACT == 'msg'?'msg':'language_'.$_ACT;
temp_disp();

/**
 * 生成多语言对应货币JS缓存文件 和 生成多语言对应运费国家缓存文件
 * */
function write_lang_currency_js()
{
	$sql = "SELECT title_e , huobi , country FROM " . Mtemplates_language . " ORDER BY orders ASC";
    $res = $GLOBALS['db']->arrQuery($sql);
    
    $js_str = "var lang_currency_array = new Array();\r\n";
    $country_arr = array();
    foreach ($res as $key=>$value)
    {
    	$js_str = $js_str."lang_currency_array['" . $value['title_e'] . "'] = '" . $value['huobi'] . "';\r\n";
    	$country_arr[$value['title_e']] = $value['country'];
    }
    
    //生成多语言对应运费国家缓存文件
    write_static_cache('lang_country', $country_arr,2);
    
    //生成多语言对应货币JS缓存文件
    $full_path =ROOT_PATH."/data-cache/lang_currency.js";
	file_put_contents($full_path,$js_str,LOCK_EX); 
	
	//清除前端缓存
	@file_get_contents('http://dsa.bestafford.com/purge/data-cache/lang_currency.js');
	
	//清除CDN缓存
	$purgeUrlList = "purge_url=" . DOMAIN .'/data-cache/lang_currency.js';
	post_purge_cache(CDN_API_PATH,$purgeUrlList);
}
?>