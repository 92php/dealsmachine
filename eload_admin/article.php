<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('lib/common.fun.php');
admin_priv('article');
define('ARTICLEMUTILANG', PFIX . 'article_muti_lang');          //多语言资讯表
$_ACT = 'list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

//多语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;	

/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|';
/*------------------------------------------------------ */
//-- 文章列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $Arr['cat_select'] =  article_cat_list(0);
    $Arr['filter'] =  $filter;
    $article_list = get_articleslist();
    $Arr['article_list'] =     $article_list['arr'];
    $sort_flag  = sort_flag($article_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$article_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
    $Arr['filter'] =  $article_list['filter'];
	$page=new page(array('total'=>$article_list['record_count'],'perpage'=>$article_list['page_size'])); 
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_ACT == 'add')
{
    /*初始化*/
    $article = array();
    $article['is_open'] = 1;
    $Arr['article'] =   $article;
    $Arr['cat_select'] =  article_cat_list(0);
    $Arr['form_action'] = 'insert';
	$Arr['tag_msg']='添加';
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_ACT == 'insert')
{
	$_POST["url_title"] = title_to_url($_POST['title']);
	if ($db->count_info(ARTICLE,"*"," title = '".$_POST['title']."' and  cat_id ='$_POST[article_cat]'")>=1){
		$msg = '添加失败,文章标题已经存在，请更换！';
        sys_msg(sprintf($msg, stripslashes($_POST['title'])), 1);
	}

    /* 取得文件地址 */
    $file_url = '';
    if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types))
        {
            sys_msg('该文件类型不允许上传');
        }

        // 复制文件
        $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        }
    }

    if ($file_url == '')
    {
        $file_url = $_POST['file_url'];
    }
    /*插入数据*/
    $add_time = gmtime();
    if (empty($_POST['cat_id']))
    {
        $_POST['cat_id'] = 0;
    }
    $sql = "INSERT INTO ".ARTICLE."(title, cat_id, article_type, is_open,article_desc, url_title,keywords, content, add_time, file_url, link,sort_order,site_link) ".
            " VALUES ('$_POST[title]', '$_POST[article_cat]', '$_POST[article_type]','$_POST[is_open]','$_POST[article_desc]','$_POST[url_title]', '$_POST[keywords]', '$_POST[content]', '$add_time', '$file_url',  '$_POST[link_url]','$_POST[sort_order]','$_POST[site_link]')";
    $db->query($sql);


    $link[0]['name'] = '继续添加';
    $link[0]['url'] = 'article.php?act=add';

    $link[1]['name'] = '返回文章列表';
    $link[1]['url'] = 'article.php?act=list';

    admin_log('',_ADDSTRING_,'文章:'.$_POST['title']);
    sys_msg('文章添加成功',0, $link);
}

// 多语言保存 fangxin 2013/07/17
elseif ($_ACT == 'add_save') {
	$article_id    = $_POST['article_id'];
	if(empty($article_id)) {
		echo '请先添加原始语言信息!';
		exit(0);
	}
	$title         = addslashes(stripslashes($_POST['title']));
	$keywords      = addslashes(stripslashes($_POST['keywords']));
	$article_desc  = addslashes(stripslashes($_POST['article_desc']));
	$content       = addslashes(stripslashes($_POST['content']));
	$link_url      = addslashes(stripslashes($_POST['link_url']));
    $site_link     = addslashes(stripslashes($_POST['site_link']));
	$lang          = stripslashes($_POST['lang']);
	$sql = "SELECT count(1) record_num FROM ". ARTICLEMUTILANG ." WHERE article_id = $article_id and lang = '". $lang ."' LIMIT 1";
	$res = $db->selectInfo($sql);
	if($res['record_num'] > 0) {
		$sql = "UPDATE ". ARTICLEMUTILANG ." SET ".
			   "title = '" .str_replace('\\\'\\\'', '\\\'', $title). "', ".
			   "keywords = '$keywords', ".
			   "article_desc = '$article_desc', ".
			   "content = '$content', ".			   
			   "link = '$link_url', ".			   			   
			   "lang = '$lang' ".			   
               "site_link = '$site_link' ".
			   "WHERE article_id = '$article_id' AND lang = '". $lang ."'";	
	} else {
		$sql = "INSERT ". ARTICLEMUTILANG ." (article_id, title, keywords, article_desc, content, lang, link, site_link) VALUES ('$article_id', '" .str_replace('\\\'\\\'', '\\\'', $title). "', '$keywords', '$article_desc',  '$content', '$lang', '$link_url', '$site_link') ";			
	}
	$res = $db->query($sql);
	if($res) {
		echo '操作成功';
	} else {
		echo '操作失败';	
	}
	exit(0);
}


/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_ACT == 'edit')
{
	$article_id = isset($_GET['id'])?$_GET['id']:'';
    /* 取文章数据 */
    $sql = "SELECT * FROM " .ARTICLE. " WHERE article_id='". $article_id ."'";
    $article = $db->selectinfo($sql);
    $Arr['article'] = $article;
    $Arr['cat_select'] =   article_cat_list($article['cat_id']);
    $Arr['form_action'] =  'update';
    $_ACT = "add";
	// 多语言查询	 fangxin
	if(!empty($lang)) {
		foreach($lang as $value) {
			$lang = $value['title_e'];
			$sql = "SELECT * FROM ". ARTICLEMUTILANG ." WHERE article_id = $article_id and lang = '". $lang ."'";
			$res = $db->selectInfo($sql);
			$article_lang[$lang] = $res;
		}
		$Arr['article_lang'] = $article_lang;
	}
	// end 		
}

if ($_ACT =='update')
{
    /*检查文章名是否相同*/
	$_POST["url_title"] = title_to_url($_POST['title']);	
	if ($db->count_info(ARTICLE,"*"," title = '".$_POST['title']."' and  article_id <> $_POST[id] and  cat_id ='$_POST[article_cat]'")>=1){
		$msg = '添加失败,文章标题已经存在，请更换！';
        sys_msg(sprintf($msg, stripslashes($_POST['title'])), 1);
	}

    if (empty($_POST['cat_id']))
    {
        $_POST['cat_id'] = 0;
    }
    $file_url = $_POST['file_url'];
    if ($db->update(ARTICLE,"title='$_POST[title]',article_type = '$_POST[article_type]', cat_id='$_POST[article_cat]',is_open='$_POST[is_open]', article_desc='$_POST[article_desc]', url_title='$_POST[url_title]', keywords ='$_POST[keywords]', file_url ='$file_url', content='$_POST[content]', link='$_POST[link_url]',site_link='$_POST[site_link]',sort_order = '$_POST[sort_order]' ", "article_id = '$_POST[id]'"))
    {
        $link[0]['name'] = "返回文章列表";
        $link[0]['url'] = 'article.php?act=list';

        $note = sprintf('编辑成功', stripslashes($_POST['title']));
        admin_log('', _EDITSTRING_, '文章:'.$_POST['title']);
        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}



/*------------------------------------------------------ */
//-- 批量删除文章
/*------------------------------------------------------ */
elseif ($_ACT == 'batch_remove')
{
    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg('你还没有选择需要删除的文章', 1);
    }

    /* 删除原来的文件 */
    $sql = "SELECT file_url FROM " . ARTICLE .
            " WHERE article_id " . db_create_in(join(',', $_POST['checkboxes']));
    $res = $db->arrQuery($sql);
    foreach ($res as $k => $val)
    {
        $old_url = $val["file_url"];
        if (strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
        {
            @unlink(ROOT_PATH . $old_url);
        }
    }

    $count = 0;
    foreach ($_POST['checkboxes'] AS $key => $id)
    {
		$sql = "SELECT title FROM " . ARTICLE .	" WHERE article_id = " .$id." ";
		$res = $db->selectinfo($sql);
		$name = $res["title"];
		$db->delete(ARTICLE," article_id = '$id'");
		admin_log('',_DELSTRING_,'文章：'.$name);
		$count++;
    }

    $lnk[] = array('name' => '返回文章列表', 'url' => 'article.php?act=list');
    sys_msg(sprintf('您已经成功删除 %d 篇文章', $count), 0, $lnk);
}

/*------------------------------------------------------ */
//-- ajax修改文章排序
/*------------------------------------------------------ */
elseif ($_ACT == 'editinplace')
{
	$dataArr = explode('||',$_POST['id']);
    $article_id    = intval($dataArr[0]);
    $article_field = trim($dataArr[1]);
    $val         = trim($_POST['value']);
    $db->update(ARTICLE," $article_field = '$val'", "  article_id = '$article_id'");
	admin_log('', _EDITSTRING_,'文章排序ID为 '.$article_id);
    echo $val;
	exit();
}

/*------------------------------------------------------ */
//-- 删除文章主题
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{

    $id = intval($_GET['id']);
    /* 删除原来的文件 */
    $sql = "SELECT file_url,title,url_title FROM " . ARTICLE . " WHERE article_id = '$id'";
    $Arrart = $db->selectinfo($sql);
    $old_url = $Arrart["file_url"];
    $url_title = $Arrart["url_title"];
    if ($old_url != '' && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false)
    {
        @unlink(ROOT_PATH . $old_url);
    }
	$path_dir = ROOT_PATH .ARTICLE_DIR.$url_title;
	if (file_exists($path_dir)){@unlink($path_dir);}
	
	
	$db->delete(ARTICLE," article_id = '$id'");
	admin_log('',_DELSTRING_,'文章列表的'.$Arrart["title"]);
    exit;
}


/* 获得文章列表 */
function get_articleslist()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
		$filter['lang']       = empty($_REQUEST['lang']) ? '' : trim($_REQUEST['lang']);
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['cat_id']     = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['page']       = empty($_REQUEST['page']) ? 0 : intval($_REQUEST['page']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.article_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND a.title LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if ($filter['cat_id'])
        {
            $where .= " AND a." . get_article_children($filter['cat_id']);
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .ARTICLE. ' AS a '.
               'LEFT JOIN ' .ARTICLECAT. ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);
		echo $filter['lang'];
        /* 获取文章数据 */
        $sql = 'SELECT a.* , ac.cat_name '.
               'FROM ' .ARTICLE. ' AS a '.
               'LEFT JOIN ' .ARTICLECAT. ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'].' LIMIT '.$filter['start'].','.$filter['page_size'];
        $filter['keyword'] = stripslashes($filter['keyword']);
        //set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $arr = $GLOBALS['db']->arrQuery($sql);
    foreach($arr as $k => $v){
        $arr[$k]['date'] = local_date($GLOBALS['_CFG']['time_format'], $arr[$k]['add_time']);
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
}

/* 上传文件 */
function upload_article_file($upload)
{
	include_once("../lib/cls_image.php");
    if (!make_dir("../" . IMAGE_DIR . "/article"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    $path     = ROOT_PATH. IMAGE_DIR . "/article/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return IMAGE_DIR . "/article/" . $filename;
    }
    else
    {
        return false;
    }
}


$_ACT = $_ACT == 'msg'?'msg':'article_'.$_ACT;
temp_disp();
?>