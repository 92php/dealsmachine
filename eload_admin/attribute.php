<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('goods_type');  //检查权限

$_ACT = 'list';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
$goods_type = isset($_GET['goods_type']) ? intval($_GET['goods_type']) : 0;


/*------------------------------------------------------ */
//-- 属性列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    $Arr['goods_type_list'] =  goods_type_list($goods_type); // 取得商品类型

    $list = get_attrlist();
    $Arr['attr_list'] =   $list['item'];
	
    $sort_flag           = sort_flag($list['filter']);
	$Arr[$sort_flag['tag']] = $sort_flag['img'];
	$list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	$Arr["filter"]       = $list['filter'];
	
	$page=new page(array('total'=>$list['record_count'],'perpage'=>$list['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加/编辑属性
/*------------------------------------------------------ */
elseif ($_ACT == 'add')
{
    /* 添加还是编辑的标识 */
	$tag_msg = "添加";
	$url = "attribute.php?act=insert&id=$_ID&goods_type=$goods_type";

    /* 取得属性信息 */
    if ($_ID=='')
    {
        $attr = array(
            'attr_id' => 0,
            'cat_id' => $goods_type,
            'attr_name' => '',
            'attr_input_type' => 0,
            'attr_index'  => 0,
            'attr_values' => '',
            'attr_type' => 0,
        );
    }
    else
    {
        $sql = "SELECT * FROM " . ATTR . " WHERE attr_id = '$_ID'";
        $attr = $db->selectInfo($sql);
    }

    $Arr["attr"] =  $attr;
    $Arr["url"] =  $url;
	$Arr["tag_msg"] =  $tag_msg;

    /* 取得商品分类列表 */
    $Arr["goods_type_list"] = goods_type_list($attr['cat_id']);

}

/*------------------------------------------------------ */
//-- 插入/更新属性
/*------------------------------------------------------ */

elseif ($_ACT == 'insert' || $_ACT == 'update')
{
    /* 检查名称是否重复 */
	if (($_ID=='') && ($db->count_info(ATTR,"*"," attr_name = '".$_POST['attr_name']."' and  cat_id = '$_POST[cat_id]'")>=1)){
			$msg = '添加失败,属性名称有重复，请更换！';
			$links = array('0'=> array('url'=>'javascript:history.go(-1)','name'=>'返回添加属性'),
						   '1'=> array('url'=>'?goods_type='.$goods_type,'name'=>'返回属性列表'));  	    
	}else{

	
		/* 取得属性信息 */
		$attr = array(
			'cat_id'          => $_POST['cat_id'],
			'attr_name'       => $_POST['attr_name'],
			'attr_index'      => $_POST['attr_index'],
			'attr_input_type' => $_POST['attr_input_type'],
			'attr_values'     => isset($_POST['attr_values']) ? $_POST['attr_values'] : '',
			'attr_type'       => empty($_POST['attr_type']) ? '0' : intval($_POST['attr_type']),
			'sort_order'      => empty($_POST['sort_order']) ? '0' : intval($_POST['sort_order']),
			'disp'            => empty($_POST['disp']) ? '0' : intval($_POST['disp']),
			'isnes'           => empty($_POST['isnes']) ? '0' : intval($_POST['isnes']),
		);
	
		/* 入库、记录日志、提示信息 */
		if ($_ID=='')
		{
			$db->autoExecute(ATTR, $attr, 'INSERT');
			admin_log($sn='', _ADDSTRING_, '商品属性'.$_POST['attr_name']);
			$msg = '添加成功！';
			$links = array('0'=> array('url'=>'?goods_type='.$goods_type,'name'=>'返回属性列表'),
							'1'=> array('url'=>'javascript:history.go(-1)','name'=>'返回添加属性')
						   );  	    
		}else{
			$db->autoExecute(ATTR, $attr, 'UPDATE', "attr_id = '$_ID'");
			admin_log($sn='', _EDITSTRING_, '商品属性'.$_POST['attr_name']);
			$msg = '修改成功！';
			$links = array('0'=> array('url'=>'?goods_type='.$_POST['cat_id'],'name'=>'返回属性列表'),
							'1'=> array('url'=>'javascript:history.go(-1)','name'=>'返回添加属性')
						   );
		}
	}
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;

}

/*------------------------------------------------------ */
//-- 删除属性(一个或多个)
/*------------------------------------------------------ */
elseif ($_ACT == 'batch')
{
    /* 检查权限 */

    /* 取得要操作的编号 */
    if (isset($_POST['checkboxes']))
    {
        $count = count($_POST['checkboxes']);
        $ids   = isset($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;

        $sql = "DELETE FROM " . ATTR . " WHERE attr_id " . db_create_in($ids);
        $db->query($sql);

        /* 记录日志 */
        admin_log('', _DELSTRING_, '商品属性 '.$ids);
        $msg ="删除成功！";
        $links = array('0'=>array('name' => "返回属性列表", 'url' => 'attribute.php?act=list&goods_type='.$goods_type),);
    }
    else
    {
        $links = array('0'=>array('name' => "返回属性列表", 'url' => 'attribute.php?act=list&goods_type='.$goods_type),);
        $msg ="删除失败！";
    }
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 编辑属性名称
/*------------------------------------------------------ */

elseif ($_ACT == 'edit_attr_name')
{
    check_authz_json('attr_manage');

    $id = intval($_POST['id']);
    $val = json_str_iconv(trim($_POST['val']));

    /* 取得该属性所属商品类型id */
    $cat_id = $exc->get_name($id, 'cat_id');

    /* 检查属性名称是否重复 */
    if (!$exc->is_only('attr_name', $val, $id, " cat_id = '$cat_id'"))
    {
        make_json_error($_LANG['name_exist']);
    }

    $exc->edit("attr_name='$val'", $id);

    admin_log($val, 'edit', 'attribute');

    make_json_result(stripslashes($val));
}

/*------------------------------------------------------ */
//-- 编辑排序序号
/*------------------------------------------------------ */

elseif ($_ACT == 'edit_sort_order')
{
    check_authz_json('attr_manage');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $exc->edit("sort_order='$val'", $id);

    admin_log(addslashes($exc->get_name($id)), 'edit', 'attribute');

    make_json_result(stripslashes($val));
}

/*------------------------------------------------------ */
//-- 删除商品属性
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{
	if($_ID!=''){
		$db->query("DELETE FROM " .ATTR. " WHERE attr_id='$_ID'");
		admin_log('', _DELSTRING_, '商品属性 '.$_ID);
		$url = 'attribute.php?act=list&goods_type='.$goods_type;
		header("Location: $url\n");
		exit;
	}
}

/*------------------------------------------------------ */
//-- 获取某属性商品数量
/*------------------------------------------------------ */
elseif ($_ACT == 'get_attr_num')
{
    check_authz_json('attr_manage');

    $id = intval($_GET['attr_id']);

    $sql = "SELECT COUNT(*) ".
           " FROM " . $ecs->table('goods_attr') . " AS a, ".
           $ecs->table('goods') . " AS g ".
           " WHERE g.goods_id = a.goods_id AND g.is_delete = 0 AND attr_id = '$id' ";

    $goods_num = $db->getOne($sql);

    if ($goods_num > 0)
    {
        $drop_confirm = sprintf($_LANG['notice_drop_confirm'], $goods_num);
    }
    else
    {
        $drop_confirm = $_LANG['drop_confirm'];
    }

    make_json_result(array('attr_id'=>$id, 'drop_confirm'=>$drop_confirm));
}


/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 获取属性列表
 *
 * @return  array
 */
function get_attrlist()
{
    /* 查询条件 */
	global $db;
    $filter = array();
    $filter['goods_type'] = empty($_GET['goods_type']) ? 0 : intval($_GET['goods_type']);
    $filter['sort_by']    = empty($_GET['sort_by']) ? 'sort_order' : trim($_GET['sort_by']);
    $filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
   
    $where = (!empty($filter['goods_type']))?" a.cat_id = '$filter[goods_type]' " : '';

    //$sql = "SELECT COUNT(*) FROM " . ATTR . " AS a $where";
    $filter['record_count'] = $db->count_info(ATTR.' AS a ',"*","$where");;
    $where = $where==''?'':' where '.$where;
    /* 分页大小 */
    $filter = page_and_size($filter);

	$_LANG['value_attr_input_type'][0] = '手工录入';
	$_LANG['value_attr_input_type'][1] = '从列表中选择';
	$_LANG['value_attr_input_type'][2] = '多行文本框';
    
    /* 查询 */
    $sql = "SELECT a.*, t.cat_name " .
            " FROM " . ATTR . " AS a ".
            " LEFT JOIN " . GTYPE . " AS t ON a.cat_id = t.cat_id  " . $where .
            " ORDER BY $filter[sort_by] $filter[sort_order] ";
    $row = $db->selectLimit($sql, $filter['page_size'], $filter['start']);

    foreach ($row AS $key => $val)
    {
        $row[$key]['attr_input_type_desc'] = $_LANG['value_attr_input_type'][$val['attr_input_type']];
        $row[$key]['attr_values']      = str_replace("\n", ", ", $val['attr_values']);
    }

    $arr = array('item' => $row, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}






$_ACT = $_ACT == 'msg'?'msg':'attribute_'.$_ACT;
temp_disp();


?>
