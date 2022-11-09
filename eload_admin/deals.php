<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
$_ACT = 'deals_list';
$_ID  = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
if (!empty($_GET['goods_id'])) $goods_id = intval(trim($_GET['goods_id']));

/*------------------------------------------------------ */
//-- deals列表
/*------------------------------------------------------ */
admin_priv('deals');   //检查权限

if ($_ACT == 'deals_list' ) {
    //$goods_list = goods_list($_ACT == 'list' ? 0 : 1, ($_ACT == 'list') ? (($code == '') ? 1 : 0) : -1);
    $Arr['data'] =   deals_list();
    //print_r($Arr['data']);
    //$sort_flag  = sort_flag($goods_list['filter']);
    //$Arr[$sort_flag['tag']] = $sort_flag['img'];
    //$goods_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];

    //$Arr['filter'] =       $goods_list['filter'];
    /* 排序标记 */

    $page=new page(array('total'=>$Arr['data']['record_count'],'perpage'=>$Arr['data']['page_size']));
    $Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加新商品 编辑商品
/*------------------------------------------------------ */

elseif ($_ACT == 'deals_add' || $_ACT == 'deals_edit' || $_ACT == 'copy') {
    admin_priv('deals'); // 检查权限

    $deals_id    = empty($_GET['id'])?0:intval($_GET['id']);
	if ($deals_id !='0'){
		$sql = "SELECT * FROM " . DEALS. " WHERE  deals_id = '$deals_id'";
		$res = $db->selectinfo($sql);
		$res['expried_time'] = local_date($GLOBALS['_CFG']['time_format'], $res['expried_time']);
		$sql = "select * from ".DEALS_ITEM." WHERE DEALS_ID = $deals_id";
    	$Arr['items'] = $db->arrquery($sql);;
	}else{
		$res['ups'] = 0;
	}   
    $Arr['deals'] = $res;


    /* 显示商品信息页面 */
}

elseif($_ACT == 'del_select'){
	    $id_arr = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : '';
//print_r($_POST['checkboxes']);
    if ($id_arr) {
        global $db;
		
        $ids = implode(',', $id_arr);//
        if($ids){
        	$db->query("delete from ".DEALS."  where deals_id in($ids)");
        	$db->query("delete from ".DEALS_ITEM." where deals_id in($ids)");
   			admin_log('', _ADDSTRING_, "deals(ID:$ids)");
        }
    }
	header("Location:deals.php");exit();
}
elseif($_ACT == 'del_item'){
	$item_id    = empty($_GET['item_id'])?0:intval($_GET['item_id']);
    if ($item_id) {
        	
        	$db->query("delete from ".DEALS_ITEM." where item_id = '$item_id'");
   			admin_log('', _ADDSTRING_, "deals item (ID:$item_id)");
    }
	sys_msg("删除成功！", 1, array(), false);
}





//保存deals信息
elseif ($_ACT == 'save_deals')
{	
    $id     = empty($_POST['id'])?'0':trim($_POST['id']);
    $title    = empty($_POST['title'])?'':trim($_POST['title']);
    $ups    = empty($_POST['ups'])?'':trim($_POST['ups']);
    $brand    = empty($_POST['brand'])?'':trim($_POST['brand']);
    $goods_sn    = empty($_POST['goods_sn'])?'':trim($_POST['goods_sn']);
    $cost_price    = empty($_POST['cost_price'])?'':trim($_POST['cost_price']);
    $current_price    = empty($_POST['current_price'])?'':trim($_POST['current_price']);
    $deals_desc   = empty($_POST['deals_desc'])?'':trim($_POST['deals_desc']);
    $expried_time   = empty($_POST['expried_time'])?0:local_strtotime($_POST['expried_time']);
    $img_url   = empty($_POST['img_url'])?'':$_POST['img_url'];
    $img_link_to   = empty($_POST['img_link_to'])?'':$_POST['img_link_to'];
   
    $now = gmtime();  
    $temp['title']  = $title;
	$temp['ups']  = $ups;
	$temp['brand']  = $brand;
	$temp['goods_sn']  = $goods_sn;
    $temp['cost_price']  = $cost_price;
    $temp['current_price']  = $current_price;
	$temp['deals_desc']  = $deals_desc;
	$temp['expried_time']  = $expried_time;
	$temp['img_link_to']  = $img_link_to;
	$temp['img_url']  = $img_url;
	
	//print_r($temp);exit;
	if($id){
		$db->autoExecute(DEALS,$temp,"UPDATE","deals_id='{$id}'");
		admin_log('', _EDITSTRING_, "deals({$title})");
		sys_msg("修改成功！", 1, array(), false);
		//$db->autoExecute(DEALS,$temp,"INSERT");
	}else{
		$temp['add_time']  = $now;
		$db->autoExecute(DEALS,$temp,"INSERT");
		admin_log('', _ADDSTRING_, "deals({$title})");
		sys_msg("添加成功！", 1, array(), false);
	}
}
elseif ($_ACT == 'item_add') {
    admin_priv('deals'); // 检查权限
	$deals_id    = empty($_GET['deals_id'])?0:intval($_GET['deals_id']);
	$item_id    = empty($_GET['item_id'])?0:intval($_GET['item_id']);
	if(!$deals_id){
		sys_msg("找不到所属deals！", 1, array(), false);
	}
	$sql = "SELECT * FROM " . DEALS. " WHERE  deals_id = '$deals_id'";
	$deals = $db->selectinfo($sql);
	if(!$deals){
		sys_msg("找不到所属deals！", 1, array(), false);
	}	
	if ($item_id){
		
		$sql = "SELECT * FROM " . DEALS_ITEM. " WHERE  $item_id = '$item_id'";
		$item = $db->selectinfo($sql);
		if(!$item){
			sys_msg("找不到item！", 1, array(), false);
		}	
	}
	$item['deals_id'] = $deals['deals_id']; 
	$item['title'] = $deals['title']; 
    $Arr['item'] = $item;

    /* 显示商品信息页面 */
}
elseif ($_ACT == 'save_item'){  //添加或编辑 item
	$deals_id    = empty($_POST['deals_id'])?0:intval($_POST['deals_id']);
	$item_id    = empty($_POST['item_id'])?0:intval($_POST['item_id']);
	if(!$deals_id){
		sys_msg("找不到所属deals！", 1, array(), false);
	}
	$arr['deals_id'] = empty($_POST['deals_id'])?'0':trim($_POST['deals_id']);
	$arr['list_price'] = empty($_POST['list_price'])?'0':trim($_POST['list_price']);
	$arr['price'] = empty($_POST['price'])?'0':trim($_POST['price']);
	$arr['img_url'] = empty($_POST['img_url'])?'':trim($_POST['img_url']);
	$arr['img_link_to'] = empty($_POST['img_link_to'])?'':trim($_POST['img_link_to']);	
		
	if($item_id){//修改
		$db->autoExecute(DEALS_ITEM,$arr,"UPDATE","item_id='{$item_id}'");
		admin_log('', _EDITSTRING_, "deals(deals_id:$deals_id)");
		sys_msg("修改成功！", 1, array(), false);		
	}else{
		$arr['add_time'] = gmtime();
		$db->autoExecute(DEALS_ITEM,$arr,"INSERT");
		admin_log('', _ADDSTRING_, "deals item (deals_id:$deals_id)");
		sys_msg("添加成功！", 1, array(), false);
	}
}


function deals_list(){

	global $db,$Arr,$_CFG;
	$table = DEALS;
	

        $where = '';
        /* 分页大小 */
		$sql = "select count(1) from {$table}  $where"; 
	//echo $sql;
		//$arr = $db->arrquery($sql);
	
		$filter = array();

        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
		$filter = page_and_size($filter);

        /* 查询 */    
        $sql = "select * from {$table} $where" .
                "  ORDER BY deals_id desc ".
                " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";
        //set_filter($filter, $sql);

   //echo $sql;
        $row = $GLOBALS['db']->arrQuery($sql);

    //$user_ids = arr2str($row,'user_id');
    

    
    
    /* 格式数据 */
    foreach ($row AS $key => &$v)
    {
    	$v['expried_time'] = local_date($_CFG['time_format'], $v['expried_time']);
    	$v['add_time'] = local_date($_CFG['time_format'], $v['add_time']);
    	//$row[$key]['month_time_str'] = local_date('Y-m',$value['month_time']);		//时间格式化
  	}
    $arr = array('list' => $row, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
	
}







$_ACT = $_ACT == 'msg'?'msg':'deals/'.$_ACT;
temp_disp();

?>