<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('goods_type');  //检查权限

$_ACT = 'manage';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

/*------------------------------------------------------ */
//-- 管理界面
/*------------------------------------------------------ */
if ($_ACT == 'manage')
{
    $good_type_list = get_goodstype();
    $Arr["goods_type_arr"]  =  $good_type_list['type'];
    $Arr["filter"]  =     $good_type_list['filter'];
	$page=new page(array('total'=>$good_type_list['record_count'],'perpage'=>$good_type_list['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加商品类型
/*------------------------------------------------------ */

elseif ($_ACT == 'add')
{
	$tag_msg = "添加";
	$url = "goods_type.php?act=insert&id=$_ID";
	
	if ($_ID!=''){
	    $tag_msg = "修改";
		$sql       = "select * from ".GTYPE." where cat_id = $_ID";
		$goods_type          = $db -> selectInfo($sql);
		$Arr["goods_type"]   = $goods_type;
	}
	$Arr["tag_msg"] = $tag_msg;
	$Arr["url"] = $url;
}

elseif ($_ACT == 'insert')
{
    $goods_type['cat_name']   = htmlspecialchars(trim($_POST['cat_name']));
    $goods_type['enabled']    = intval($_POST['enabled']);
    
	if ($_ID!=''){
		if ($db->autoExecute(GTYPE, $goods_type,'UPDATE'," cat_id = $_ID") !== false){
			$msg="修改成功";
			admin_log($sn = '', _ADDSTRING_, '商品类型 '.$goods_type['cat_name']);
		}else{$msg="添加失败";}
				$links = array('0'=> array('url'=>'goods_type.php?act=manage','name'=>'返回商品类型列表'),
							   '1'=> array('url'=>'goods_type.php?act=add','name'=>'返回添加商品类型'),
							   '2'=> array('url'=>'goods_type.php?act=add&id='.$_ID,'name'=>'还需要修改'));
	}else{
		if ($db->autoExecute(GTYPE, $goods_type) !== false){
			$msg="添加成功";
			admin_log($sn = '', _ADDSTRING_, '商品类型 '.$goods_type['cat_name']);
		}else{$msg="添加失败";}
		$links = array('0'=> array('url'=>'goods_type.php?act=manage','name'=>'返回商品类型列表'),
					   '1'=> array('url'=>'goods_type.php?act=add','name'=>'返回添加商品类型'));   //返回地址
	}
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 删除商品类型
/*------------------------------------------------------ */

elseif ($_ACT == 'remove')
{
       if ($_ID!=''){
        admin_log($sn='', _DELSTRING_, '商品类型列表ID为 '.$_ID);
		$db -> delete(GTYPE," cat_id = $_ID ");
		$db -> delete(ATTR,"  cat_id = $_ID ");  //清除属性
		$msg = "删除成功！";
		$links[] = array('url'=>'goods_type.php?act=manage','name'=>'返回商品类型列表');
		$_ACT = 'msg';
		$Arr["msg"]   = $msg;
		$Arr["links"] = $links;
    }
}
/*------------------------------------------------------ */
//-- ajax修改商品类型名称
/*------------------------------------------------------ */
elseif ($_ACT == 'editinplace')
{
    $id  = intval($_POST['id']);
    $val = trim($_POST['value']);
    $db->update(GTYPE," cat_name = '$val' ", " cat_id = '$id'");
    admin_log('', _EDITSTRING_,'商品类型 '.$val);
    echo $val;
	exit();
}


/**
 * 获得所有商品类型
 *
 * @access  public
 * @return  array
 */
function get_goodstype()
{
	global $db;
	/* 记录总数以及页数 */
	$filter['record_count'] = 0 ;
	$filter['record_count'] = $db->count_info(GTYPE,"*","");
	$filter = page_and_size($filter);
	/* 查询记录 */
	$sql = "SELECT t.*, COUNT(a.cat_id) AS attr_count ".
	   "FROM ". GTYPE. " AS t ".
	   "LEFT JOIN ". ATTR. " AS a ON a.cat_id=t.cat_id ".
	   "GROUP BY t.cat_id ";
	$all = $db->selectLimit($sql, $filter['page_size'], $filter['start']);
    return array('type' => $all, 'filter' => $filter, 'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
}





$_ACT = $_ACT == 'msg'?'msg':'goods_type_'.$_ACT;
temp_disp();

?>
