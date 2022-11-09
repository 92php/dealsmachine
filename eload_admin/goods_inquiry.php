<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('lib/common.fun.php');
admin_priv('goods_inquiry');
$_ACT = 'list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
// 语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;
/*------------------------------------------------------ */
//-- 信息列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
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
//-- 批量删除信息
/*------------------------------------------------------ */
elseif ($_ACT == 'batch_remove')
{
    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg('你还没有选择需要删除的信息', 1);
    }

	$db->delete(INQUIRY," id " . db_create_in(join(',', $_POST['checkboxes'])));
	admin_log('',_DELSTRING_,'信息id：'.join(',', $_POST['checkboxes']));
    $count = count($_POST['checkboxes']);
    $lnk[] = array('name' => '返回信息列表', 'url' => '?act=list');
    sys_msg(sprintf('您已经成功删除 %d 篇信息', $count), 0, $lnk);
}



/* 获得信息列表 */
function get_articleslist()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['page'] = empty($_REQUEST['page']) ? 0 : intval($_REQUEST['page']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		$filter['lang'] = empty($_REQUEST['lang']) ? '' : trim($_REQUEST['lang']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND (ac.goods_title LIKE '%" . mysql_like_quote($filter['keyword']) . "%' or a.txtTrueName LIKE '%" . mysql_like_quote($filter['keyword']) . "%'  or a.txtMessage LIKE '%" . mysql_like_quote($filter['keyword']) . "%' )";
        }
        if (!empty($filter['lang']))
        {
			$where .= " AND a.lang='". $filter['lang'] ."'";
		}

        /* 信息总数 */
        $sql = 'SELECT COUNT(*) FROM ' .INQUIRY. ' AS a '.
               'LEFT JOIN ' .GOODS. ' AS ac ON ac.goods_id = a.goods_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取信息数据 */
        $sql = 'SELECT a.* , ac.goods_title,ac.cat_id,ac.url_title  '.
               'FROM ' .INQUIRY. ' AS a '.
               'LEFT JOIN ' .GOODS. ' AS ac ON ac.goods_id = a.goods_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'].' LIMIT '.$filter['start'].','.$filter['page_size'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $arr = $GLOBALS['db']->arrQuery($sql);
	
	$str_arr = array(chr(10)=>'<br>',chr(32)=>'&nbsp;');
	
    foreach($arr as $k => $v){
        $arr[$k]['date'] = local_date($GLOBALS['_CFG']['time_format'], $arr[$k]['addtime']);
        $arr[$k]['goods_url'] = get_details_link($v['goods_id'],$arr[$k]['url_title']);
        $arr[$k]['txtMessage'] = strtr($arr[$k]['txtMessage'], $str_arr);
        //$arr[$k]['txtEMail']	= email_disp_process($v['txtEMail']);
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
	
}

$_ACT = $_ACT == 'msg'?'msg':'inquiry_'.$_ACT;
temp_disp();
?>