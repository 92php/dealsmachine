<?php
// 多语言 fangxin 2013/07/02
function get_lang() {
	global $db;
	$lang_sql = "SELECT * FROM " . Mtemplates_language. " WHERE status = 1 ORDER BY orders ASC";
	$lang_res = $db->arrQuery($lang_sql);
	if($lang_res) {
		foreach($lang_res as $key=>$value) {
			$lang_key = $value['title_e'];	
			$lang_res_n[$lang_key] = $value;
		}
	}		
	return $lang_res_n;
}	

//检测语言权限
function check_lang_power($lang) {
	if(!empty($lang)) {
		foreach($lang as $value) {
			$user_power = $_SESSION['WebUserInfo']['group_power'];
			if(!strpos($user_power, 'lang_' . $value['title_e'])) {
				unset($lang[$value['title_e']]);
			}
		}	
	}
	return $lang;
}

function check_default_lang_power($lang) {
	//if(!empty($lang)) {
		$user_power = $_SESSION['WebUserInfo']['group_power'];
		if(strpos($user_power, 'lang_en')) {
			$result = 1;
		} else {
			$result = 0;
		}
		return $result;		
	//}	
}

// 去掉指定HTML标签
/* 参数说明
$str  => 目标字符串
$html => 指定替换标签
$html = array( "<p>"    => '',
			   "</p>"   => ''
			 );	
*/
function format_html($str, $html='') {
	if(empty($html)) {
		$html = array( "<p>"    => '',
					   "</p>"   => ''
					 );	
	}
	if(!empty($str)) {
		foreach($html as $key => $value) {
			$str = str_ireplace($key, $value, $str);
		}
	}
	return $str;
}
?>