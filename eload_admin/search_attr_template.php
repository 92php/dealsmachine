<?php
/**
 * 添加，编辑商品属性查找模板
 * */

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');

admin_priv('search_attr_template');  //检查权限

/*------------------------------------------------------ */
//-- 查找模板列表
/*------------------------------------------------------ */
$_ACT = empty($_GET['act']) ? 'manage' : trim($_GET['act']);
if($_ACT == 'manage')
{
	$template_info_arr = read_static_cache('search_attr_template',2);	//查找模板信息
	$Arr["template_info_arr"] = $template_info_arr;
}

/*------------------------------------------------------ */
//-- 添加查找模板
/*------------------------------------------------------ */
elseif ($_ACT == 'add')
{
	$template_id     = empty($_GET['template_id']) ? 0 : intval(trim($_GET['template_id']));
	$tag_msg = "添加模板";
	$url = "search_attr_template.php?act=insert&template_id=$template_id";
	
	if (!empty($template_id)){
	    $tag_msg = "修改模板";
	    $template_info_arr = read_static_cache('search_attr_template',2);	//查找模板信息
		$Arr["template_info"]   = $template_info_arr[$template_id];
	}
	$Arr["tag_msg"] = $tag_msg;
	$Arr["url"] = $url;
}

/*------------------------------------------------------ */
//-- 保存查找模板数据
/*------------------------------------------------------ */
elseif ($_ACT == 'insert')
{
    $template_id     = empty($_GET['template_id']) ? 0 : intval(trim($_GET['template_id']));	//模板ID
	$template_info_arr['template_name']   = htmlspecialchars(trim($_POST['template_name']));	//模板名称
    $template_info_arr['template_site']    = intval($_POST['template_site']);					//前台模板显示位置
    if(empty($template_info_arr['template_name']))
    {
    	sys_msg("模板名称不能为空！", 1, array(), false);
    }
    
    if(empty($template_info_arr['template_site']))
    {
    	sys_msg("前台模板显示位置不能为空！", 1, array(), false);
    }
    
	if (!empty($template_id)){
		if ($db->autoExecute(SEARCH_TEMPLATE, $template_info_arr,'UPDATE'," template_id = $template_id") !== false){
			//写查找模板缓存文件
			write_search_atrr_template_func();
			
			$msg="修改成功";
			admin_log($sn = '', _EDITSTRING_, '查找模板 '.$template_info_arr['template_name'].'(模板ID：'.$template_id.')');
		}else{$msg="修改失败";}
		$links = array('0'=> array('url'=>'search_attr_template.php?act=manage','name'=>'返回商品查找模板列表'),
					   '1'=> array('url'=>'search_attr_template.php?act=add','name'=>'返回添加查找模板'),
					   '2'=> array('url'=>'search_attr_template.php?act=add&template_id='.$template_id,'name'=>'还需要修改'));
	}else{
		if ($db->autoExecute(SEARCH_TEMPLATE, $template_info_arr) !== false){
			//写查找模板缓存文件
			write_search_atrr_template_func();
			
			$msg="添加成功";
			admin_log($sn = '', _ADDSTRING_, '查找模板 '.$template_info_arr['template_name'].'(模板ID：'.$template_id.')');
		}else{$msg="添加失败";}
		$links = array('0'=> array('url'=>'search_attr_template.php?act=manage','name'=>'返回商品查找模板列表'),
					   '1'=> array('url'=>'search_attr_template.php?act=add','name'=>'返回添加查找模板'));   //返回地址
	}
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 删除查找模板数据
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{
	$template_id     = empty($_GET['template_id']) ? 0 : intval(trim($_GET['template_id']));	//模板ID
	
	//判断商品分类是否有使用当前查找模板
	$sql = "SELECT COUNT(*) FROM " . CATALOG ." WHERE search_template_id = " . $template_id;
	$count = $GLOBALS['db']->getOne($sql);
	if($count >0)
	{
		$msg="有商品分类使用到此模板，删除查找模板失败！";
		$links = array('0'=> array('url'=>'search_attr_template.php?act=manage','name'=>'返回商品查找模板列表'));
	}
	else 
	{
		$db -> delete(SEARCH_TEMPLATE," template_id = $template_id ");	//清除模板
		$db -> delete(SEARCH_ATTR,"  template_id = $template_id ");  //清除模板属性
		
		//写查找模板缓存文件
		write_search_atrr_template_func();
		
		$msg="删除查找模板成功";
		admin_log($sn='', _DELSTRING_, '查找模板ID为 '.$template_id);
		$links = array('0'=> array('url'=>'search_attr_template.php?act=manage','name'=>'返回商品查找模板列表'));
	}
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 模板属性列表
/*------------------------------------------------------ */
elseif ($_ACT == 'attr_list')
{
	$template_id     = empty($_GET['template_id']) ? 0 : intval(trim($_GET['template_id']));	//模板ID
	if(empty($template_id))
	{
		$msg="查找模板ID不存在！";
		$links = array('0'=> array('url'=>'search_attr_template.php?act=manage','name'=>'返回商品查找模板列表'));
		
		$_ACT = 'msg';
		$Arr["msg"] = $msg;
		$Arr["links"] = $links;
	}
	else 
	{
		$template_info_arr = read_static_cache('search_attr_template',2);	//查找模板信息
		foreach ($template_info_arr[$template_id]['attr_list'] as $key => $value)
		{
			$value['search_value'] = implode('|',$value['search_value']);
			$template_info_arr[$template_id]['attr_list'][$key] = $value;
		}
		$Arr["template_info"]   = $template_info_arr[$template_id];
		$Arr["template_id"]   = $template_id;
	}
}

/*------------------------------------------------------ */
//-- 添加模板属性
/*------------------------------------------------------ */
elseif ($_ACT == 'attr_add')
{
	$template_id     = empty($_GET['template_id']) ? 0 : intval(trim($_GET['template_id']));	//模板ID
	$search_attr_id     = empty($_GET['search_attr_id']) ? 0 : intval(trim($_GET['search_attr_id']));

    /* 取得属性信息 */
    if(!empty($template_id))
    {
    	/* 添加还是编辑的标识 */
		$tag_msg = "添加";
		$url = 'search_attr_template.php?act=attr_insert&template_id='.$template_id.'&search_attr_id='.$search_attr_id;
		
		$template_info_arr = read_static_cache('search_attr_template',2);	//查找模板信息
	
	    if (empty($search_attr_id))
	    {
	        $search_attr = array(
	            'search_attr_id' => 0,
	            'template_id' => $template_id,
	            'search_attr_name' => '',
	            'search_attr_brief' => '',
	            'search_value' => '',
	            'odr' => 0,
	        );
	    }
	    else
	    {
			$template_attr_info   = $template_info_arr[$template_id]['attr_list'][$search_attr_id];
			$search_attr = array(
	            'search_attr_id' => $search_attr_id,
	            'template_id' => $template_id,
	            'search_attr_name' => $template_attr_info['search_attr_name'],
	            'search_attr_brief' => $template_attr_info['search_attr_brief'],
	            'search_value' => implode('|',$template_attr_info['search_value']),
	            'odr' => $template_attr_info['odr'],
	        );
	    }
	
	    $Arr["template_id"] =  $template_id;
	    $Arr["search_attr"] =  $search_attr;
	    $Arr["url"] =  $url;
		$Arr["tag_msg"] =  $tag_msg;
		
		$Arr["template_info_arr"] = $template_info_arr;		//查找模板列表
    }
}

/*------------------------------------------------------ */
//-- 保存模板属性数据
/*------------------------------------------------------ */
elseif ($_ACT == 'attr_insert')
{
	$search_attr_id = intval($_GET['search_attr_id']);												//查找属性ID
	
	$template_info_arr['search_attr_name']   = htmlspecialchars(trim($_POST['search_attr_name']));	//查找属性名称
    $template_info_arr['search_attr_brief']    = htmlspecialchars(trim($_POST['search_attr_brief']));//查找属性简称
    $template_info_arr['template_id']    = intval($_POST['template_id']);							//属性查找模板ID
    $template_info_arr['search_value']    = htmlspecialchars(trim($_POST['search_value']));							//可选择查询词
    $template_info_arr['odr']    = intval($_POST['odr']);											//排序号
    
    if(empty($template_info_arr['search_attr_name']))
    {
    	sys_msg("查找属性名称不能为空！", 1, array(), false);
    }
    
    if(empty($template_info_arr['search_attr_brief']))
    {
    	sys_msg("查找属性简称不能为空！", 1, array(), false);
    }
    
    if(empty($search_attr_id))
    {
	     $sql = "SELECT COUNT(*) FROM " . SEARCH_ATTR . " WHERE search_attr_brief = '".$template_info_arr['search_attr_brief']."'";
	     $num_brief = $GLOBALS['db']->getOne($sql);
	     if($num_brief)
	     {
	     	sys_msg("查找属性简称已经存在，请换一个！", 1, array(), false);
	     }
    }
    
    if(empty($template_info_arr['template_id']))
    {
    	sys_msg("属性查找模板ID不能为空！", 1, array(), false);
    }
    
	if (!empty($search_attr_id)){
		if ($db->autoExecute(SEARCH_ATTR, $template_info_arr,'UPDATE'," search_attr_id = $search_attr_id") !== false){
			//写查找模板缓存文件
			write_search_atrr_template_func();
			
			$msg="修改成功";
			admin_log($sn = '', _EDITSTRING_, '查找属性 '.$template_info_arr['search_attr_name'].'(属性ID:'.$search_attr_id.')');
		}else{$msg="修改失败";}
		$links = array('0'=> array('url'=>'search_attr_template.php?act=attr_list&template_id='.$template_info_arr['template_id'],'name'=>'返回查找模板属性列表'),
					   '1'=> array('url'=>'search_attr_template.php?act=attr_add&template_id='.$template_info_arr['template_id'],'name'=>'返回添加查找模板属性'),
					   '2'=> array('url'=>'search_attr_template.php?act=attr_add&template_id='.$template_info_arr['template_id'].'&search_attr_id='.$search_attr_id,'name'=>'还需要修改'));
	}else{
		if ($db->autoExecute(SEARCH_ATTR, $template_info_arr) !== false){
			//写查找模板缓存文件
			write_search_atrr_template_func();
			
			$msg="添加成功";
			admin_log($sn = '', _ADDSTRING_, '查找属性 '.$template_info_arr['search_attr_name'].'(属性ID:'.$search_attr_id.')');
		}else{$msg="添加失败";}
		$links = array('0'=> array('url'=>'search_attr_template.php?act=attr_list&template_id='.$template_info_arr['template_id'],'name'=>'返回查找模板属性列表'),
					   '1'=> array('url'=>'search_attr_template.php?act=attr_add&template_id='.$template_info_arr['template_id'],'name'=>'返回添加查找模板属性'));   //返回地址
	}
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 删除查找模板数据
/*------------------------------------------------------ */
elseif ($_ACT == 'attr_remove')
{
	$search_attr_id     = empty($_GET['search_attr_id']) ? 0 : intval(trim($_GET['search_attr_id']));	//模板属性ID
	$template_id     = empty($_GET['template_id']) ? 0 : intval(trim($_GET['template_id']));	//模板ID
	
	$db -> delete(SEARCH_ATTR,"  search_attr_id = $search_attr_id ");  //清除模板属性
	
	//写查找模板缓存文件
	write_search_atrr_template_func();
	
	$msg="删除模板属性成功";
	admin_log($sn='', _DELSTRING_, '模板属性ID为 '.$search_attr_id);
	$links = array('0'=> array('url'=>'search_attr_template.php?act=attr_list&template_id='.$template_id,'name'=>'返回模板属性列表'));
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

$_ACT = $_ACT == 'msg'?'msg':'search_attr_template_'.$_ACT;
temp_disp();


function write_search_atrr_template_func()
{
	$sql = "SELECT * FROM " . SEARCH_TEMPLATE . " ORDER BY template_id ASC";
	$template_list = $GLOBALS['db']->arrQuery($sql);
	
	$sql = "SELECT * FROM " . SEARCH_ATTR . " ORDER BY template_id , odr , search_attr_id ASC";
	$attr_list = $GLOBALS['db']->arrQuery($sql);
	foreach ($attr_list as $key => $value)
	{
		$value['search_value'] = explode('|',$value['search_value']);
		$attr_list_key[$value['template_id']][$value['search_attr_id']] = $value;
	}
	
	foreach ($template_list as $key_1 => $value_1)
	{
		$template_list_key[$value_1['template_id']] = $value_1;
		$template_list_key[$value_1['template_id']]['attr_list'] = empty($attr_list_key[$value_1['template_id']]) ? array() : $attr_list_key[$value_1['template_id']];
		$template_list_key[$value_1['template_id']]['attr_num'] = empty($attr_list_key[$value_1['template_id']]) ? 0 : count($attr_list_key[$value_1['template_id']]);
	}
	write_static_cache('search_attr_template', $template_list_key,2);
}		
?>