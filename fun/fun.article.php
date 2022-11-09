<?php
/*
+----------------------------------
* 服务中心
+----------------------------------
*/
//公共部分
include(ROOT_PATH . 'languages/' .$cur_lang. '/user.php');
global $cur_lang, $default_lang;
$Arr['lang']     = $_LANG;
$parent_id       = empty($_GET['pid'])?13:intval($_GET['pid']);
$_GET['cid']     = empty($_GET['cid'])?0:intval($_GET['cid']);
$_GET['id']      = empty($_GET['id'])?0:intval($_GET['id']);
$_REQUEST['key'] = empty($_REQUEST['key'])?'':htmlspecialchars(trim($_REQUEST['key']));
$my_cache_id     = $parent_id . '-'.$_GET['cid'].'-'.$cur_lang.'-'.$_GET['id'].'-'.$_REQUEST['key'];
$my_cache_id     = sprintf('%X', crc32($my_cache_id));

if (!$Tpl->is_cached('article_content.htm', $my_cache_id))
{
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	$cat_list = get_cat_list(0,$parent_id);
	foreach ($cat_list as $key => $val){
		$cat_id =  $cat_list[$key]['cat_id'];
		$cat_list[$key]['parent_id'] = $parent_id;
		$cat_list[$key]['_child'] = get_article_list($cat_id,'',10);
	}			
	$Arr['center_cat_list'] = $cat_list;		
	if(empty($_GET['cid']) && empty($_GET['id']) && empty($_REQUEST['key'])){						
		foreach ($cat_list as $key => $val){
			$cat_id =  $cat_list[$key]['cat_id'];
			$cat_list[$key]['_child'] = get_article_list($cat_id,'',6);
		}						
		$Arr['seo_title'] = 'Help center - '.$_CFG['shop_name'];
		$Arr['seo_keywords'] = 'Help center';
		$Arr['seo_description'] = 'We have developed this help page to assist you with standard information you may need, It also includes all the frequently asked questions from our customers.You can browse by ...';
	
	}
	elseif(!empty($_REQUEST['key'])){			
		$key = empty($_REQUEST['key'])?'':trim($_REQUEST['key']);
		if ($key != ''){
			$Arr['article_list'] = get_article_list(0,$key);
			$Arr['seo_title'] = "Search result - $key - Help center - ".$_CFG['shop_name'];
			$Arr['seo_keywords'] = 'Search result ,'.$key.',Help center';
		}
		$Arr['seo_description'] = 'We have developed this help page to assist you with standard information you may need, It also includes all the frequently asked questions from our customers.You can browse by ...';	
	}
	elseif(!empty($_GET['cid'])){
		$cat_id = empty($_GET['cid'])?0:intval($_GET['cid']);
		$Arr['cat_arr'] = get_cat_list($cat_id);
		$Arr['article_list'] = get_article_list($cat_id);
		$Arr['seo_title'] = $Arr['cat_arr'][0]['cat_name'].'- Help center - '.$_CFG['shop_name'];
		$Arr['seo_keywords'] = $Arr['cat_arr'][0]['cat_name'].',Help center';			
	}elseif(!empty($_GET['id'])){			
		$_GET['id'] = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$article_id     = $_GET['id'];
		if(isset($_GET['cid']) && $_GET['cid'] < 0)
		{
			$article_id = $db->getOne("SELECT article_id FROM " . ARTICLE . " WHERE cat_id = '".intval($_GET['cat_id'])."' ");
		}
		/* 文章详情 */
		$article = get_article_info($article_id);
		if (empty($article))
		{
			header("Location: /$cur_lang_url"."m-page_not_found.htm");
			exit;
		}
		
		if (!empty($article['link']) && $article['link'] != 'http://' && $article['link'] != 'https://')
		{
			header("location:$article[link]\n");
			exit;
		}
		
		$filepath = ARTICLE_DIR;
		$Arr['id'] =                $article_id;
		
		/* 验证码相关设置 */
		$Arr['article'] =  $article;
		$Arr['seo_title'] = 'DealsMachine: ' . $article['title'];
		$Arr['seo_keywords'] = '';
		$Arr['seo_description'] = '';
		$Arr['nav_title'] = $article['title'];
		
		if ($_GET['id'] == '20') 	$Arr['dropship_s'] = '_s';
		
		if ($article['article_id'] == 20){
			$Arr['drop_flag'] = '_s';
		}
	}	
}

/**
 * 获得指定的文章的详细信息
 *
 * @access  private
 * @param   integer     $article_id
 * @return  array
 */
function get_article_info($article_id)
{
	global $cur_lang, $default_lang;
    /* 获得文章的信息 */
    $sql = "SELECT * ".
            "FROM " .ARTICLE. "  ".
            "WHERE is_open = 1 AND article_id = '$article_id' ";
    $row = $GLOBALS['db']->selectinfo($sql);
    if ($row !== false)
    {
        $row['add_time']     = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']); // 修正添加时间显示
        $row['content']      = varResume($row['content']);
		if($cur_lang != $default_lang) {
			$sql = "SELECT * FROM eload_article_muti_lang WHERE article_id = ". $article_id ." AND lang = '". $cur_lang ."'";
			$lang_res = $GLOBALS['db']->selectInfo($sql);
			if($lang_res) {
				$row['title']          = $lang_res['title'];
				$row['keywords']       = $lang_res['keywords'];
				$row['link']           = $lang_res['link'];
				$row['article_desc']   = $lang_res['article_desc'];
				$row['content']        = varResume($lang_res['content']);
			}
		}
    }
    return $row;
}

function get_cat_list($cat_id = 0,$parent_id = 13){
	global $cur_lang, $default_lang;
	$sql = "SELECT cat_id,cat_name FROM " . ARTICLECAT . " where parent_id = '$parent_id' ORDER BY sort_order ";
	if ($cat_id != 0) 
		$sql = "SELECT cat_id,cat_name FROM " . ARTICLECAT . " where cat_id = '$cat_id' ORDER BY sort_order ";
	$res = 	$GLOBALS['db']->arrQuery($sql);
	if(is_array($res) && $cur_lang != $default_lang) {
		foreach($res as $key => $value) {
			$sql = "SELECT cat_id, cat_name FROM eload_article_cat_muti_lang WHERE cat_id = ". $value['cat_id'] ." AND lang = '". $cur_lang ."'";
			$lang_res = $GLOBALS['db']->selectInfo($sql);
			if($lang_res) {
				$res[$key]['cat_name'] = $lang_res['cat_name'];
			}
			
		}
	}
	return $res;
}

$_MDL = 'article_content';

/**
 * 获得指定的分类的文章列表
 *
 * @access  private
 * @param   integer     $cat_id
 * @return  array
 */
/*function get_article_list($cat_id,$key='',$limit = '')
{
	$sql = '';
	if ($key!=''){
		$sql = " AND (title like '%".$key."%' or content like '%".$key."%') ";
	}else{
		$sql = "AND cat_id = '$cat_id'";
	}
	
    // 获得文章的信息 
    $sql = "SELECT title,url_title,article_id,link ".
            "FROM " .ARTICLE. "  ".
            "WHERE is_open = 1  $sql $limit ";
    $arr = $GLOBALS['db']->arrQuery($sql);
    return $arr;
}*/

?>  