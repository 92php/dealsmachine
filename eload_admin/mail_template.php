<?php

/**
 * 管理中心模版管理程序
*/
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
require_once('lib/common.fun.php');
admin_priv('mail_template');

/* act操作项的初始化 */
$_ACT = 'list';
$_ID  = '';
$default_folder = 'eload_admin/email_temp/';
$default_mail_template = 'en';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = trim($_GET['id']);

// 语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;

/*------------------------------------------------------ */
//-- 模版列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    /* 获得所有邮件模板 */
	$cur_lang = isset($_REQUEST['lang'])?$_REQUEST['lang']:'';
	$Arr['cur_lang'] = $cur_lang;
	if(!empty($cur_lang)) {
	    $sql = "SELECT * FROM " . Mtemplates. " m LEFT JOIN ". Mtemplates ."_". $cur_lang ." ml ON m.template_id=ml.template_id WHERE  m.type = 'template'  AND m.template_id NOT IN(20,21,23,25,26,27,29,30,35,36,37,38,39,40,41,49) ORDER BY m.template_id ASC";
	} else {
    	$sql = "SELECT * FROM " . Mtemplates. " WHERE  type = 'template' AND template_id NOT IN(20,21,23,25,26,27,29,30,35,36,37,38,39,40,41,49) ORDER BY template_id ASC";
	}
    $res = $db->arrQuery($sql);
	foreach($res as $k => $row){
		$res[$k]['last_modify'] = local_date($_CFG['time_format'], $row['last_modify']);
	}
	$Arr['mailtemp'] = $res;
	
}

/*------------------------------------------------------ */
//-- 载入指定模版
/*------------------------------------------------------ */
elseif ($_ACT == 'add')
{
    $tpl_id    = empty($_GET['id'])?0:intval($_GET['id']);
	if ($tpl_id !='0'){
		// 原语言
		$sql = "SELECT * FROM " . Mtemplates. " WHERE  template_id = '$tpl_id'";
		$res = $db->selectinfo($sql);
		$template_id = $res['template_id'];
		$content = varResume($res['template_content']);
		$type = $res['is_html'];		
		$res['template_content'] = $content;
		$Arr['mailtemp'] = $res;		
		// 多语言		
		$language_sql = "SELECT * FROM ". Mtemplates_language ." WHERE status = 1 ORDER BY orders ASC";
		$res = $db->arrQuery($language_sql);
		foreach($res as $key => $value) {
			$language_title = $value['title_e'];
			$sql = "SELECT * FROM " . Mtemplates. "_". $language_title ." WHERE template_id = ". $template_id ." LIMIT 1";
			$res_language[$language_title] = $db->selectinfo($sql);
			$res_language[$language_title]['action'] = 'update';					
		}
		$Arr['mailtemp_language'] = $res_language;		
	}
	/*
	// 语言
	$language_sql = "SELECT * FROM " . Mtemplates_language. " WHERE status = 1 ORDER BY orders ASC";
	$language_res = $db->arrQuery($language_sql);
	$Arr['lang_arr'] = $language_res;
	*/
}

elseif ($_ACT == 'del')
{
    $tpl_id    = empty($_GET['id'])?0:intval($_GET['id']);
	if ($tpl_id !='0'){
		$sql = "DELETE FROM " . Mtemplates. " WHERE  template_id = '$tpl_id'";
		if($db->query($sql)){
			if (file_exists(ROOT_PATH . $default_folder . $default_mail_template . '/' . $tpl_id.'.html'))
			 @unlink(ROOT_PATH . $default_folder . $default_mail_template . '/' . $tpl_id.'.html');
			// 删除所有其它语言模板信息
			$language_sql = "SELECT * FROM ". Mtemplates_language ." ORDER BY orders ASC";
			$res = $db->arrQuery($language_sql);
			foreach($res as $value) {
				$language_title = $value['title_e'];
				$sql = "DELETE FROM " . Mtemplates. "_". $language_title ." WHERE  template_id = '$tpl_id'";
				$db->query($sql);
				if (file_exists(ROOT_PATH . $default_folder . $language_title . '/' . $tpl_id.'.html'))
				 @unlink(ROOT_PATH . $default_folder . $language_title . '/' . $tpl_id.'.html');				
			}			 
			sys_msg("删除成功！", 1, array(), false);
			admin_log('', _DELSTRING_, '邮件模板 ' . $tpl_id);
		}else{
			sys_msg("删除失败！", 1, array(), false);
		}
	}
}



/*------------------------------------------------------ */
//-- 保存模板内容
/*------------------------------------------------------ */

elseif ($_ACT == 'save_template')
{
    $subject    = empty($_POST['subject'])?'':trim($_POST['subject']);
    $note       = empty($_POST['note'])?'':trim($_POST['note']);
    $content   = empty($_POST['content'])?'':trim($_POST['content']);
    $content   = str_replace('%7B','{',$content);
    $content   = str_replace('%7D','}',$content);
    $content   = str_replace('%7b','{',$content);
    $content   = str_replace('%7d','}',$content);    
    
    $type      = empty($_POST['mail_type'])?0:intval($_POST['mail_type']);
    
    $tpl_id    = empty($_GET['tpl_id'])?0:intval($_GET['tpl_id']);
    $goods_sn  = empty($_POST['goods_sn'])?'':trim($_POST['goods_sn']);//邮件需要读取的商品编码 by mashanling on 2013-06-06 18:05:12
	if ($tpl_id!=0){

	    $mail_template_goods = read_static_cache('mail_template_goods', 2);
	    unset($mail_template_goods[$tpl_id]);
	    write_static_cache('mail_template_goods', $mail_template_goods, 2);

		$sql = "UPDATE " .Mtemplates. " SET ".
					"template_subject = '" .str_replace('\\\'\\\'', '\\\'', $subject). "', ".
					"template_content = '" .str_replace('\\\'\\\'', '\\\'', $content). "', ".
					"note = '" .str_replace('\\\'\\\'', '\\\'', $note). "', ".
					"goods_sn = '{$goods_sn}',".
					"is_html = '$type', ".
					"last_modify = '" .gmtime(). "' ".
				"WHERE template_id='$tpl_id'";
	}else{
		$sql = "INSERT " .Mtemplates. " (template_subject,template_content,goods_sn,is_html,last_modify,type,note) VALUES ('" .str_replace('\\\'\\\'', '\\\'', $subject). "','" .str_replace('\\\'\\\'', '\\\'', $content). "','$goods_sn','$type','" .gmtime(). "','template','$note') ";
	}
    if ($db->query($sql, "SILENT"))
    {
		$temp_arr = array();
		$cache = array();
		$rss = $db->arrQuery("SELECT template_id,template_subject,goods_sn FROM " . Mtemplates. "");
		foreach ($rss as $key => $val) {
			$temp_arr[$default_mail_template][$val['template_id']] = $val['template_subject'];
			$cache[$val['template_id']] = array('goods_sn' => $val['goods_sn']);
			//其它语言
			$language_sql = "SELECT * FROM ". Mtemplates_language ." ORDER BY orders ASC";
			$res = $db->arrQuery($language_sql);
			foreach($res as $value) {
				$language_title = $value['title_e'];
				$sql = "SELECT * FROM " . Mtemplates. "_". $language_title ." WHERE  template_id = ". $val['template_id'] ."";
				$res = $db->selectinfo($sql);
				$temp_arr[$language_title][$val['template_id']] = $res['template_subject'];
			}						
		}
				
		write_static_cache('mail_template', $cache, 2);
		$str = "<?php\r\n";
		$str .= "\$mail_conf = " . var_export($temp_arr, true) . ";\r\n";
		$str .= "?>";		
		if(!file_exists(ROOT_PATH . $default_folder . $default_mail_template)){
			mkdir(ROOT_PATH . $default_folder . $default_mail_template);
		}	
		file_put_contents(ROOT_PATH . $default_folder . '/mail_conf.php',$str);

		
		$content = stripslashes($content);
		$content = str_replace(array('%7B','%7D','/eload_admin/'),array('{','}',''),$content);
		
		if ($tpl_id!=0){
		    file_put_contents(ROOT_PATH . $default_folder . $default_mail_template . '/' . $tpl_id.'.html',$content);
		    admin_log('', _EDITSTRING_, '邮件模板 ' . $tpl_id);
			//sys_msg("修改成功！", 1, array(), false);
			echo '<script>alert("修改成功");location.href="?act=add&id='.$tpl_id.'";</script>';
		}else{
			$tpl_id = $db->insertId();
		    file_put_contents(ROOT_PATH . $default_folder . $default_mail_template . '/' . $tpl_id.'.html',$content);			
			//sys_msg("添加成功！", 1, array(), false);
			admin_log('', _ADDSTRING_, '邮件模板 ' . $subject);
			echo '<script>alert("添加成功");location.href="?act=add&id='.$tpl_id.'";</script>';
		}

    }else{
		sys_msg("添加失败！", 1, array(), false);
	}
}

// 多语言保存
elseif ($_ACT == 'save_template_language')
{	
	$id                = empty($_POST['id'])?0:trim($_POST['id']);
    $subject           = empty($_POST['subject'])?'':trim($_POST['subject']);
    $template_id       = $_POST['template_id'];
	$mail_language     = $_POST['mail_language'];
	$content           = empty($_POST['content_'. $mail_language .''])?'':trim($_POST['content_'. $mail_language .'']);				
	$action            = $_POST['action'];
    $content   = str_replace('%7B','{',$content);
    $content   = str_replace('%7D','}',$content);
    $content   = str_replace('%7b','{',$content);
    $content   = str_replace('%7d','}',$content);
	if(empty($template_id)) {
		echo '<script>alert("请先添加原始语言");location.href="?act=add";</script>';
	}	
	if ($action == 'update'){
		//如果没有任何相关记录则添加，存在则修改
		$sql = "SELECT * FROM " . Mtemplates. "_" .$mail_language. " WHERE template_id = $template_id";
		$res = $db->selectinfo($sql);
		if(empty($res)) {
			$sql = "INSERT " . Mtemplates. "_". $mail_language ." (template_id, template_subject, template_content) VALUES ('$template_id', '" .str_replace('\\\'\\\'', '\\\'', $subject). "','" .str_replace('\\\'\\\'', '\\\'', $content). "') ";	
		} else {
			$sql = "UPDATE " . Mtemplates. "_" .$mail_language. " SET ".
				   "template_subject = '" .str_replace('\\\'\\\'', '\\\'', $subject). "', ".
				   "template_content = '" .str_replace('\\\'\\\'', '\\\'', $content). "' ".					
				   "WHERE template_id='$template_id'";
		}
	}else{
		$sql = "INSERT " . Mtemplates. "_". $mail_language ." (template_id, template_subject, template_content) VALUES ('$template_id', '" .str_replace('\\\'\\\'', '\\\'', $subject). "','" .str_replace('\\\'\\\'', '\\\'', $content). "') ";
	}
    if ($db->query($sql, "SILENT"))
    {		

		$temp_arr = array();
		$cache = array();
		$rss = $db->arrQuery("SELECT template_id,template_subject,goods_sn FROM " . Mtemplates. "");
		foreach ($rss as $key => $val) {
			$temp_arr[$default_mail_template][$val['template_id']] = $val['template_subject'];
			$cache[$val['template_id']] = array('goods_sn' => $val['goods_sn']);
			//其它语言
			$language_sql = "SELECT * FROM ". Mtemplates_language ." ORDER BY orders ASC";
			$res = $db->arrQuery($language_sql);
			foreach($res as $value) {
				$language_title = $value['title_e'];
				$sql = "SELECT * FROM " . Mtemplates. "_". $language_title ." WHERE  template_id = ". $val['template_id'] ."";
				$res = $db->selectinfo($sql);
				$temp_arr[$language_title][$val['template_id']] = $res['template_subject'];
			}						
		}
				
		write_static_cache('mail_template', $cache, 2);
		$str = "<?php\r\n";
		$str .= "\$mail_conf = " . var_export($temp_arr, true) . ";\r\n";
		$str .= "?>";		
		if(!file_exists(ROOT_PATH . $default_folder . $mail_language)){
			mkdir(ROOT_PATH . $default_folder . $mail_language);
		}	
		file_put_contents(ROOT_PATH . $default_folder . '/mail_conf.php',$str);

		
		$content = stripslashes($content);
		$content = str_replace(array('%7B','%7D','/eload_admin/'),array('{','}',''),$content);	
	
		if ($template_id != 0 && empty($action)){
		    file_put_contents(ROOT_PATH . $default_folder . $mail_language . '/' . $template_id.'.html',$content);			
		    admin_log('', _EDITSTRING_, '邮件模板 ' . $template_id);
			echo '<script>alert("添加成功");location.href="?act=add&id='.$template_id.'";</script>';
		}else{
		    file_put_contents(ROOT_PATH . $default_folder . $mail_language . '/' . $template_id.'.html',$content);			
			admin_log('', _ADDSTRING_, '邮件模板 ' . $subject);
			echo '<script>alert("修改成功");location.href="?act=add&id='.$template_id.'";</script>';
		}

    }else{
		sys_msg("添加失败！", 1, array(), false);
	}
}

/**
 * 加载指定的模板内容
 *
 * @access  public
 * @param   string  $temp   邮件模板的ID
 * @return  array
 */
function load_template($temp_id)
{
    $sql = "SELECT template_subject, template_content, is_html ".
            "FROM " .Mtemplates. " WHERE template_id='$temp_id'";
    $row = $GLOBALS['db']->GetRow($sql);

    return $row;
}

$_ACT = $_ACT == 'msg'?'msg':'mailtemp_'.$_ACT;
temp_disp();

?>