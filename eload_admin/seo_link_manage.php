<?php
define('INI_WEB', true);
require_once('../lib/global.php');//引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('seo_link');  //检查权限
$table = 'eload_seo_link';
$_ACT = 'list';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

/*------------------------------------------------------ */
//-- 管理界面
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
	//echo $table;
    $link_info = link_list();
 
    $Arr["link_info"]  =  $link_info['type'];
    $Arr["filter"]  =     $link_info['filter'];
	$page=new page(array('total'=>$link_info['record_count'],'perpage'=>$link_info['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */

elseif ($_ACT == 'add')
{
	$tag_msg = "添加";
	$url = "?act=insert&id=$_ID";
	$seo_links =array();
	if ($_ID!=''){
	    $tag_msg = "修改";
		$sql       = "select * from ".$table." where id = $_ID";
		$seo_links          = $db -> selectInfo($sql);
		//$activityArr['exp_time'] = local_date('Y-m-d', $activityArr['exp_time']);
	}else{
		//$activityArr['exp_time'] = local_date('Y-m-d', strtotime('+1 month'));
		//$activityArr['youhuilv'] = 10;
	}
	$Arr["seo_links"]   = $seo_links;
	$Arr["tag_msg"] = $tag_msg;
	$Arr["url"] = $url;
	//echo $_ACT;
}

elseif ($_ACT == 'insert')
{
    $link['category_link'] = $_POST['category_link'];
    $link['keywords_link'] = $_POST['keywords_link'];
    $link['title'] = $_POST['title'];
    
	if ($_ID!=''){
		if ($db->autoExecute($table, $link,'UPDATE'," id = $_ID") !== false){
			$msg="修改成功";
			admin_log($sn = '', _ADDSTRING_, '链接');
		}else{$msg="修改失败";}
				$links = array('0'=> array('url'=>'?act=list','name'=>'返回链接列表'),
							   '1'=> array('url'=>'?act=add','name'=>'返回添加链接'),
							   '2'=> array('url'=>'?act=add&id='.$_ID,'name'=>'还需要修改'));
	}else{
		 //print_r($link);
		 //print_r($link);
		//exit();
		 if ($db->autoExecute($table, $link) !== false){
			$msg="添加成功";
			admin_log($sn = '', _ADDSTRING_, '链接');
		}else{$msg="添加失败";}
		$links = array('0'=> array('url'=>'?act=list','name'=>'返回链接列表'),
					   '1'=> array('url'=>'?act=add','name'=>'返回链接活动'));   //返回地址
	}
	//create_gifts();
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 删除活动
/*------------------------------------------------------ */

elseif ($_ACT == 'remove')
{
       if ($_ID!=''){
        admin_log($sn='', _DELSTRING_, '链接列表ID为 '.$_ID);
       // $sql = "update ".CART." set id =0 where goods_id in(select goods_id from".GOODS." where id=$_ID)" ;
       // $db->query($sql);
		$db -> delete( $table,"id = $_ID ");
		
		$msg = "删除成功！";
		//$db->update(GOODS,"id=0","id=".$_ID);
		$links[] = array('url'=>'?act=list','name'=>'返回链接列表');
		$_ACT = 'msg';
		$Arr["msg"]   = $msg;
		$Arr["links"] = $links;
    }
}
/*------------------------------------------------------ */
//-- ajax修改活动名称
/*------------------------------------------------------ 
elseif ($_ACT == 'editinplace')
{
    $id  = intval($_POST['id']);
    $val = trim($_POST['value']);
    $db->update(PCODE," code = '$val' ", " id = '$id'");
    admin_log('', _EDITSTRING_,'活动 '.$val);
    echo $val;
	exit();
}
*/

/**
 * 获得所有链接
 *
 * @access  public
 * @return  array
 */
function link_list()
{
	global $db,$table;
	/* 记录总数以及页数 */
	//$filter['record_count'] = 0 ;
	$filter['record_count'] = $db->count_info($table,"*","");
	$filter = page_and_size($filter);
	/* 查询记录 */
	$sql = "SELECT * ".
	   "FROM ".$table." order by id desc limit $filter[start],$filter[page_size]";
	   
	//echo $table; 
	$all = $db->arrQuery($sql);
	
	/*foreach($all as $key => $val){
		$all[$key]['create_time'] = local_date('Y-m-d', $all[$key]['create_time']);
		$all[$key]['exp_time'] = local_date('Y-m-d', $all[$key]['exp_time']);
	}*/	
    return array('type' => $all, 'filter' => $filter, 'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
}




$_ACT = $_ACT == 'msg'?'msg':'seo_link/'.$_ACT;
temp_disp();

?>
