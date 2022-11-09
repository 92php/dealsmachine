<?php
define('INI_WEB', true);
require_once('../lib/global.php');//引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('activity_manage');  //检查权限

$_ACT = 'list';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

/*------------------------------------------------------ */
//-- 管理界面
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
	//echo GIFTS;
    $gifts_info = gifts_list();
    $Arr["gifts_arr"]  =  $gifts_info['type'];
    $Arr["filter"]  =     $gifts_info['filter'];
	$page=new page(array('total'=>$gifts_info['record_count'],'perpage'=>$gifts_info['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */

elseif ($_ACT == 'add')
{
	$tag_msg = "添加";
	$url = "?act=insert&id=$_ID";
	$gifts =array();
	if ($_ID!=''){
	    $tag_msg = "修改";
		$sql       = "select * from ".GIFTS." where gifts_id = $_ID";
		$gifts          = $db -> selectInfo($sql);
		//$activityArr['exp_time'] = local_date('Y-m-d', $activityArr['exp_time']);
	}else{
		//$activityArr['exp_time'] = local_date('Y-m-d', strtotime('+1 month'));
		//$activityArr['youhuilv'] = 10;
	}
	$Arr["gifts"]   = $gifts;
	$Arr["tag_msg"] = $tag_msg;
	$Arr["url"] = $url;
	//echo $_ACT;
}

elseif ($_ACT == 'insert')
{
    $gifts['gifts_name'] = $_POST['gifts_name'];
    $gifts['need_money'] = $_POST['need_money'];
    $gifts['remark'] = $_POST['remark'];
    
	if ($_ID!=''){
		if ($db->autoExecute(GIFTS, $gifts,'UPDATE'," gifts_id = $_ID") !== false){
			$msg="修改成功";
			admin_log($sn = '', _ADDSTRING_, '赠品'.$gifts['gifts_name']);
		}else{$msg="修改失败";}
				$links = array('0'=> array('url'=>'?act=list','name'=>'返回赠品列表'),
							   '1'=> array('url'=>'?act=add','name'=>'返回添加赠品'),
							   '2'=> array('url'=>'?act=add&id='.$_ID,'name'=>'还需要修改'));
	}else{
		 //print_r($gifts);
		 //print_r($gifts);
		//exit();
		 if ($db->autoExecute(GIFTS, $gifts) !== false){
			$msg="添加成功";
			admin_log($sn = '', _ADDSTRING_, '赠品'.$gifts['gifts_name']);
		}else{$msg="添加失败";}
		$links = array('0'=> array('url'=>'?act=list','name'=>'返回赠品列表'),
					   '1'=> array('url'=>'?act=add','name'=>'返回赠品活动'));   //返回地址
	}
	create_gifts();
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
        admin_log($sn='', _DELSTRING_, '赠品列表ID为 '.$_ID);
        $sql = "update ".CART." set gifts_id =0 where goods_id in(select goods_id from".GOODS." where gifts_id=$_ID)" ;
        $db->query($sql);
		$db -> delete( GIFTS,"gifts_id = $_ID ");
		
		$msg = "删除成功！";
		$db->update(GOODS,"gifts_id=0","gifts_id=".$_ID);
		$links[] = array('url'=>'?act=list','name'=>'返回赠品列表');
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
 * 获得所有赠品分类
 *
 * @access  public
 * @return  array
 */
function gifts_list()
{
	global $db;
	/* 记录总数以及页数 */
	//$filter['record_count'] = 0 ;
	$filter['record_count'] = $db->count_info(GIFTS,"*","");
	$filter = page_and_size($filter);
	/* 查询记录 */
	$sql = "SELECT * ".
	   "FROM ".GIFTS." order by gifts_id desc limit $filter[start],$filter[page_size]";
	   
	//echo GIFTS; 
	$all = $db->arrQuery($sql);
	
	/*foreach($all as $key => $val){
		$all[$key]['create_time'] = local_date('Y-m-d', $all[$key]['create_time']);
		$all[$key]['exp_time'] = local_date('Y-m-d', $all[$key]['exp_time']);
	}*/	
    return array('type' => $all, 'filter' => $filter, 'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
}




$_ACT = $_ACT == 'msg'?'msg':'gifts/'.$_ACT;
temp_disp();

?>
