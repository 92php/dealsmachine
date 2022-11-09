<?php
define('INI_WEB', true);
$payment_list = "";
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('cart_manage');  //检查权限

/* act操作项的初始化 */
$_ACT = 'list';
$_ID  = '';
if(!empty($_GET['act'])) $_ACT=$_GET['act'];
if(!empty($_GET['id'])) $_ID=$_GET['id'];

if ($_ACT == 'list')
{
	$cart_list = array();
    $cart_list = get_cart_list();
	$Arr["cart_list"]     = $cart_list['list'];
    $sort_flag           = sort_flag($cart_list['filter']);
	$Arr[$sort_flag['tag']] = $sort_flag['img'];
	$cart_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	$Arr["filter"]       = $cart_list['filter'];
	//$page=new page(array('total'=>1000,'perpage'=>20,'page_name'=>'test')); 
	$page=new page(array('total'=>$cart_list['record_count'],'perpage'=>$cart_list['page_size']));
	$Arr["pagestr"]  = $page->show();

}
elseif($_ACT == 'remove'){
	$id = intval($_GET['id']);
    /*方便日志记录 */
	$sql = "SELECT * FROM " . CART . " WHERE rec_id = '$id'";
    $Arrcart = $db->selectinfo($sql);
   	$db->delete(CART," rec_id = '$id'");
	admin_log('',_DELSTRING_,'购物车列表的'.$Arrcart["goods_sn"]);
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除购物车记录列表
/*------------------------------------------------------ */
elseif ($_ACT == 'batch_remove')
{
    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg('你还没有选择需要删除的记录', 1);
    }

    $count = 0;
    foreach ($_POST['checkboxes'] AS $key => $id)
    {
    	/*方便日志记录 */
		$sql = "SELECT goods_sn FROM " . CART .	" WHERE rec_id = " .$id." ";
		$res = $db->selectinfo($sql);
		$name = $res["goods_sn"];
		$db->delete(CART," rec_id = '$id'");
		print_r(admin_log('',_DELSTRING_,'购物车列表：'.$name));
		admin_log('',_DELSTRING_,'购物车列表：'.$name);
		$count++;
    }

    $lnk[] = array('name' => '购物车列表', 'url' => 'cart_manage.php?act=list');
    sys_msg(sprintf('您已经成功删除 %d 记录', $count), 0, $lnk);
}

elseif ($_ACT == 'add')
{

}


$_ACT =  'cart_'.$_ACT;
temp_disp();
/*  */
function get_cart_list()
{   global $db, $Arr;

    $email = !empty($_GET['email']) ? $_GET['email'] : '';
    $filter = array();
    $filter['sort_by']    = empty($_GET['sort_by']) ? 'rec_id' : trim($_GET['sort_by']);
    $filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
    $filter['start_date'] = empty($_GET['start_date']) ? '' : $_GET['start_date'];
    $filter['end_date']   = empty($_GET['end_date']) ? '' : $_GET['end_date'];
    $filter['goods_sn']   = empty($_GET['goods_sn']) ? '' : $_GET['goods_sn'];
    
    $where  = ' 1 ';
    $where .= $filter['start_date'] ? 'AND addtime>=' . local_strtotime($filter['start_date']) : '';
    $where .= $filter['end_date'] ? ' AND addtime<=' . local_strtotime($filter['end_date']) : '';
    
    if ($filter['goods_sn']) {
        $goods_sn = $filter['goods_sn'];
    	$goods_sn = preg_replace('/\s/','',$goods_sn);
    	$goods_sn = str_replace(',',"','",$goods_sn);
    	$goods_sn ="'{$goods_sn}'";
        $where .= " AND (goods_sn IN({$goods_sn}) OR goods_sn LIKE '%" . mysql_like_quote($filter['goods_sn']) . "%')";
    }
    //$where .= $email ? " AND '" . "'" : ''; 

    /* 获得总记录数据 */
    $filter['record_count'] = $db->count_info(' ' .CART. ' AS al ',"*"," $where");
    $filter = page_and_size($filter);
	
    /* 获取管理员日志记录 */
    $list  = array();
    $sql   = 'SELECT al.* FROM ' .CART. ' AS al '.
            ' where '.
            $where .' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'] ;
    $list  = $db->selectLimit($sql, $filter['page_size'], $filter['start']);
	foreach ($list as $k => $v){
		$list[$k]["addtime"] = local_date($GLOBALS['_CFG']['time_format'], $list[$k]['addtime']);
		$list[$k]['session_id']	= email_disp_process($v['session_id']);
	}
	
	$Arr['start_date'] = $filter['start_date'];
	$Arr['end_date']   = $filter['end_date'];
	$Arr['goods_sn']   = $filter['goods_sn'];
    return array('list' => $list, 'filter' => $filter,'record_count' => $filter['record_count'],'page_size'=> $filter['page_size']);
}








?>