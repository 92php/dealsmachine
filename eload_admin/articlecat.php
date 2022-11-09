<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('lib/common.fun.php');
admin_priv('articlecat'); /* 权限检查 */
define('ARTICLECATMUTILANG', PFIX . 'article_cat_muti_lang');   //多语言资讯分类表
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


/*------------------------------------------------------ */
//-- 分类列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
	$tree=array();
	$sql = "SELECT * FROM ".ARTICLECAT." ORDER BY parent_id,sort_order ASC,cat_id ASC";
	$catArr = $db -> arrQuery($sql);
	$catArr = toTree($catArr,$pk='cat_id');
	treetoary($catArr,0,'cat_name');
	$catArr = $tree;

    $articlecat = $catArr;
    $Arr['articlecat'] = $articlecat;
	
}


/*------------------------------------------------------ */
//-- 添加分类
/*------------------------------------------------------ */
elseif ($_ACT == 'add')
{
    $tag_msg = "添加";
    $Arr['cat_select'] =  article_cat_list(0);
    $Arr['form_action'] =  'insert';
    $Arr['tag_msg'] =   $tag_msg ;
}

// 多语言保存 fangxin 2013/07/17
elseif ($_ACT == 'add_save') {
	$article_cat_lang_table = 'eload_article_cat_muti_lang';
	$cat_id        = $_POST['cat_id'];
	if(empty($cat_id)) {
		echo '请先添加原始语言信息!';
		exit(0);
	}
	$cat_name    = addslashes(stripslashes($_POST['cat_name']));
	$keywords    = addslashes(stripslashes($_POST['keywords']));
	$cat_desc    = addslashes(stripslashes($_POST['cat_desc']));
	$lang        = stripslashes($_POST['lang']);
	$sql = "SELECT count(1) record_num FROM ". $article_cat_lang_table ." WHERE cat_id = $cat_id and lang = '". $lang ."' LIMIT 1";
	$res = $db->selectInfo($sql);
	if($res['record_num'] > 0) {
		$sql = "UPDATE ". $article_cat_lang_table ." SET ".
			   "cat_name = '" .str_replace('\\\'\\\'', '\\\'', $cat_name). "', ".
			   "keywords = '$keywords', ".
			   "cat_desc = '$cat_desc', ".
			   "lang = '$lang' ".			   
			   "WHERE cat_id = '$cat_id' AND lang = '". $lang ."'";	
	} else {
		$sql = "INSERT ". $article_cat_lang_table ." (cat_id, cat_name, keywords, cat_desc, lang) VALUES ('$cat_id', '" .str_replace('\\\'\\\'', '\\\'', $cat_name). "', '$keywords', '$cat_desc', '$lang') ";			
	}
	$res = $db->query($sql);
	if($res) {
		echo '操作成功';
	} else {
		echo '操作失败';	
	}
	exit(0);
}

elseif ($_ACT == 'insert')
{
    /*检查分类名是否重复*/
	$_POST["url_title"] = title_to_url($_POST['cat_name']);
	if ($db->count_info(ARTICLECAT,"*"," cat_name = '".$_POST['cat_name']."'")>=1){
		$msg = '添加失败,文章分类名称不能有重复，请更换！';
        sys_msg(sprintf($msg, stripslashes($_POST['cat_name'])), 1);
	}else{
		$db->autoExecute(ARTICLECAT,$_POST);
		$id = $db->insertId();
	}
    admin_log('',_ADDSTRING_,'文章分类：'.$_POST['cat_name']);
    $link[0]['name'] = '继续添加其它语言！';
    $link[0]['url'] = 'articlecat.php?act=edit&id='.$id.'';
    sys_msg($_POST['cat_name'].'添加成功',0, $link);
}

/*------------------------------------------------------ */
//-- 编辑文章分类
/*------------------------------------------------------ */
elseif ($_ACT == 'edit')
{
	$cat_id = isset($_GET['id'])?$_GET['id']:'';
	if(!empty($cat_id)) {
		$sql = "SELECT * FROM ". ARTICLECAT . " WHERE cat_id='". $cat_id ."'";
		$cat = $db->selectinfo($sql);
		$select    =   article_cat_list($cat['parent_id']);
		$Arr['cat'] =        $cat;
		$Arr['cat_select'] =  $select;
		$Arr['form_action'] = 'update';
		$_ACT = "add";		
		// 多语言查询	 fangxin
		if(!empty($lang)) {
			foreach($lang as $value) {
				$lang = $value['title_e'];
				$sql = "SELECT * FROM eload_article_cat_muti_lang WHERE cat_id = $cat_id and lang = '". $lang ."'";
				$res = $db->selectInfo($sql);
				$cat_lang[$lang] = $res;
			}
			$Arr['cat_lang'] = $cat_lang;
		}
		// end 	
	}
}

elseif ($_ACT == 'update')
{
	$_POST["url_title"] = title_to_url($_POST['cat_name']);
    /*检查重名*/
    if ($_POST['cat_name'] != $_POST['old_catname'])
    {
       if ($db->count_info(ARTICLECAT,"*"," cat_name = '".$_POST['cat_name']."' and  cat_id <> '$_POST[id]'")>=1)
	   {
            sys_msg(sprintf('分类名称已经存在', stripslashes($_POST['cat_name'])), 1);
        }
    }

    if(!isset($_POST['parent_id']))
    {
        $_POST['parent_id'] = 0;
    }
    $row = $db->selectinfo("SELECT  parent_id FROM " . ARTICLECAT . " WHERE cat_id='$_POST[id]'");

    /* 检查设定的分类的父分类是否合法 */
    $child_cat = article_cat_list($_POST['parent_id'], true);
    if (!empty($child_cat))
    {
        foreach ($child_cat as $child_data)
        {
            $catid_array[] = $child_data['cat_id'];
        }
    }
    if (in_array($_POST['parent_id'], $catid_array))
    {
        sys_msg(sprintf('所在的父分类不合法', stripslashes($_POST['cat_name'])), 1);
    }
	$db->autoExecute(ARTICLECAT,$_POST,'UPDATE'," cat_id='$_POST[id]'");		
	$link[0]['name'] = '返回分类列表';
	$link[0]['url'] = 'articlecat.php?act=list';
	$note = sprintf('编辑成功', $_POST['cat_name']);
	admin_log('', _EDITSTRING_, '文章分类：'.$_POST['cat_name']);
	sys_msg($note, 0, $link);
}



/*------------------------------------------------------ */
//-- 编辑文章分类的排序
/*------------------------------------------------------ */
elseif ($_ACT == 'edit_sort_order')
{
    check_authz_json('article_cat');

    $id    = intval($_POST['id']);
    $order = json_str_iconv(trim($_POST['val']));

    /* 检查输入的值是否合法 */
    if (!preg_match("/^[0-9]+$/", $order))
    {
        make_json_error(sprintf($_LANG['enter_int'], $order));
    }
    else
    {
        if ($exc->edit("sort_order = '$order'", $id))
        {
            clear_cache_files();
            make_json_result(stripslashes($order));
        }
        else
        {
            make_json_error($db->error());
        }
    }
}

/*------------------------------------------------------ */
//-- 删除文章分类
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{
    $id = intval($_GET['id']);

    $sql = "SELECT COUNT(*) FROM " . ARTICLECAT . " WHERE parent_id = '$id'";
    if ($db->getOne($sql) > 0)
    {
        /* 还有子分类，不能删除 */
        sys_msg('还有子分类，不能删除', 0);
    }

    /* 非空的分类不允许删除 */
    $sql = "SELECT COUNT(*) FROM ".ARTICLE." WHERE cat_id = '$id'";
    if ($db->getOne($sql) > 0)
    {
        sys_msg('该分类下有文章，请先删除文章', 0);
    }
    else
    {
		$sql = "SELECT cat_name FROM " . ARTICLECAT . " WHERE cat_id = '$id'";
		$cat_name = $db->getOne($sql);
		$db->delete(ARTICLECAT," cat_id = '$id'");
		// 删除多语言分类信息
		$db->delete(ARTICLECATMUTILANG, " cat_id = '$id'");
		
        admin_log('', _DELSTRING_, '文章分类 '.$cat_name);
        sys_msg('删除成功', 0);
    }
    exit;
}

/**
 * 添加商品分类
 *
 * @param   integer $cat_id
 * @param   array   $args
 *
 * @return  mix
 */
function cat_update($cat_id, $args)
{
    if (empty($args) || empty($cat_id))
    {
        return false;
    }

    return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('article_cat'), $args, 'update', "cat_id='$cat_id'");
}



$_ACT = $_ACT == 'msg'?'msg':'articlecat_'.$_ACT;
temp_disp();

?>
