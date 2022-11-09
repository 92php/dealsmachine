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
    $activity_list = activity_list();
    $Arr["activity_list"]  =  $activity_list['type'];
    $Arr["filter"]  =     $activity_list['filter'];
	$page=new page(array('total'=>$activity_list['record_count'],'perpage'=>$activity_list['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */

elseif ($_ACT == 'add')
{
	$tag_msg = "添加";
	$url = "?act=insert&id=$_ID";
	$activityArr ="";
	if ($_ID!=''){
	    $tag_msg = "修改";
		$sql       = "select * from eload_activity where id = $_ID";
		$activityArr          = $db -> selectInfo($sql);
		//$activityArr['exp_time'] = local_date('Y-m-d', $activityArr['exp_time']);
	}else{
		//$activityArr['exp_time'] = local_date('Y-m-d', strtotime('+1 month'));
		//$activityArr['youhuilv'] = 10;
	}
	if($_ID){
		$act_goods_list = $db->getOne("select act_goods_list from eload_activity where id = $_ID");
		if(empty($act_goods_list)){
			$act_goods_arr =  $db->arrQuery("select goods_sn from ".GOODS." where activity_list like '%,$_ID,%' order by sort_order");
			$act_goods_list = arr2str($act_goods_arr,'goods_sn');
		}
	}else {
		$act_goods_list = "";
	}
	//固定活动列表 fangxin 2013-10-28
    $res = read_static_cache('activity_redirection');
    $Arr['activity'] = $res['activity'];

	$Arr['act_goods_list'] = str_replace("'",'',  $act_goods_list);
	$Arr["activityArr"]   = $activityArr;
	$Arr["tag_msg"] = $tag_msg;
	$Arr["url"] = $url;
}

elseif ($_ACT == 'insert')
{
    $activityArr['name'] = $_POST['activity_name'];
    $activityArr['type'] = $_POST['type'];
    $activityArr['title'] = $_POST['title'];
    $activityArr['keywords'] = $_POST['keywords'];
    $activityArr['description'] = $_POST['description'];
    $activityArr['remark'] = $_POST['remark'];
    $activityArr['act_goods_list'] = trim($_POST['act_goods_list']);
	if($activityArr['act_goods_list']){
		$activityArr['act_goods_list'] = str_replace('，',',',$activityArr['act_goods_list']);
		$activityArr['act_goods_list'] = str_replace('，',',',$activityArr['act_goods_list']);
		$activityArr['act_goods_list'] = preg_replace("/[^\w,]/",'', $activityArr['act_goods_list']);
	}
	$activityArr['redirection'] = trim($_POST['redirection']);
	if(!empty($activityArr['redirection'])) {
		preg_match('/active-(.*)-/', $activityArr['redirection'], $matches);
		$rediretion_arr = explode('-', $matches[1]);
		$activityArr['redirection_name'] = $rediretion_arr[0];		
	}
	if ($_ID!=''){
		$msg="修改成功";
		if ($db->autoExecute("eload_activity", $activityArr,'UPDATE'," id = $_ID") !== false){
			$act_goods_arr =  $db->arrQuery("select goods_sn from ".GOODS." where activity_list like '%,$_ID,%' order by sort_order");
			$act_goods_list = arr2str($act_goods_arr,'goods_sn');
			$act_goods_list = str_replace("'",'',  $act_goods_list);
			if($act_goods_list != $activityArr['act_goods_list']){
				$sql ="update ".GOODS." set activity_list =REPLACE(activity_list,',$_ID,',',') where activity_list like '%,$_ID,%'";
				if($db->query($sql)!==false&&$activityArr['act_goods_list']){
					$activityArr['act_goods_list'] = "'".str_replace(',',"','",$activityArr['act_goods_list'])."'";
					$sql = "update ".GOODS." set activity_list =replace(concat(activity_list,',$_ID,'),',,',',') where goods_sn in(".$activityArr['act_goods_list'].")";
					$db->query($sql);
				}else{
					if(!empty($activityArr['act_goods_list']))$msg="修改失败";
				}
			}
			admin_log($sn = '', _ADDSTRING_, '活动'.$activityArr['name']);
		}else{$msg="添加失败";}
			$links = array('0'=> array('url'=>'?act=list','name'=>'返回活动列表'),
						   '1'=> array('url'=>'?act=add','name'=>'返回添加活动'),
						   '2'=> array('url'=>'?act=add&id='.$_ID,'name'=>'还需要修改'));
	}else{
		if ($db->autoExecute("eload_activity", $activityArr) !== false){
			$_ID = $db->insertId();
			$activityArr['act_goods_list'] = "'".str_replace(',',"','",$activityArr['act_goods_list'])."'";
			$sql = "update ".GOODS." set activity_list =replace(concat(activity_list,',$_ID,'),',,',',') where goods_sn in(".$activityArr['act_goods_list'].")";
			$db->query($sql);
			$msg="添加成功";
			admin_log($sn = '', _ADDSTRING_, '活动'.$activityArr['name']);
		}else{$msg="添加失败";}
			$links = array('0'=> array('url'=>'?act=list','name'=>'返回活动列表'),
						   '1'=> array('url'=>'?act=add','name'=>'返回添加活动'));   //返回地址
	}

    here_write_activity_cache();//写活动编码缓存 by mashanling on 2013-03-27 15:02:56

	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

//删除活动
elseif ($_ACT == 'remove')
{
       if ($_ID!=''){
        admin_log($sn='', _DELSTRING_, '活动列表ID为 '.$_ID);
		$db -> delete("eload_activity"," id = $_ID ");
		$msg = "删除成功！";
		$links[] = array('url'=>'?act=list','name'=>'返回活动列表');
		$_ACT = 'msg';
		$Arr["msg"]   = $msg;
		$Arr["links"] = $links;

        here_write_activity_cache();//写活动编码缓存 by mashanling on 2013-03-27 15:02:56
    }
}

//固定活动列表 fangxin 2013-10-28
elseif($_ACT == 'festival')
{
    $arr = read_static_cache('activity_redirection');
    if (!$arr) {
        return false;
    }
    $data = '';
    foreach ($arr['activity'] as $item) {
        $data .= join('|', $item) . "\n";
    }
    $arr['activity'] = $data;
    $GLOBALS['Arr']['data'] = $arr;	
}
//固定活动列表修改 fangxin 2013-10-28
elseif($_ACT == 'festivalSave')
{
    $activity = isset($_POST['activity']) ? trim($_POST['activity']) : '';
    $activity = explode("\n", $activity);
    foreach ($activity as $item) {
        $item = trim($item);

        if ($item) {
            $arr = explode('|', $item);
            $arr = array_map('trim', $arr);

            if ($arr[0] && !empty($arr[1])) {
                $arr[1] = 0 !== strpos($arr[1], $v = 'http://') ? $v . $arr[1] : $arr[1];
                $data['activity'][] = $arr;
            }
        }
    }
    write_static_cache('activity_redirection', $data);
    exit();	
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
 * 获得所有活动
 *
 * @access  public
 * @return  array
 */
function activity_list()
{
	global $db;
	/* 记录总数以及页数 */

	$filter['record_count'] = 0 ;
	$filter['record_count'] = $db->count_info("eload_activity","*","");
	$filter = page_and_size($filter);
	/* 查询记录 */
	$sql = "SELECT * ".
	   "FROM eload_activity order by id desc limit $filter[start],$filter[page_size]";

	$all = $db->arrQuery($sql);

	foreach($all as $key => $val){
		//$sql = "select act_goods_list from eload_activity where id=$val[id]";
		$sql = 'SELECT * FROM eload_activity WHERE id='.$val['id'];
		$activity_info = $GLOBALS['db']->selectinfo($sql);
		$goods_list_sn = $activity_info['act_goods_list'];

		if(empty($goods_list_sn)){
			//echo $sql."<br>";
			$all[$key]['onsale'] = 0 ;
			$all[$key]['notnosale'] = 0;
			continue;
		}
		$goods_list_sn = "'".str_replace(',',"','",$goods_list_sn)."'";

		$all[$key]['onsale'] =$db->getone("select count(1) from eload_goods where goods_sn in($goods_list_sn) and is_on_sale=1 and goods_number >0");
		//if(empty($all[$key]['onsale']))echo "select count(1) from eload_goods where goods_sn in($goods_list_sn) and is_on_sale=1"."<br>";
		 $not_on_sale =$db->arrQuery("select goods_sn from eload_goods where goods_sn in($goods_list_sn) and(is_on_sale=0 or goods_number =0)");

		$all[$key]['notnosale'] = count($not_on_sale);
		$all[$key]['notnosale_goods_sn'] = str_replace("'",'',arr2str($not_on_sale, 'goods_sn'));
		//$sql = "select count(1),is_on_sale from eload_goods where goods_sn in() and is_on_sale=1";
		//$all[$key]['onsale'] = $db->getone($sql);
		//echo $sql;
		//exit;
		//$all[$key]['exp_time'] = local_date('Y-m-d', $all[$key]['exp_time']);
	}
    return array('type' => $all, 'filter' => $filter, 'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
}




$_ACT = $_ACT == 'msg'?'msg':'activity_manage_'.$_ACT;
temp_disp();

/**
 * 获取写缓存数据
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-03-27 15:25:47
 *
 * @return void 无返回值
 */
function here_write_activity_cache() {
    global $db;

    $arr = array();
    $db->query('SELECT id,act_goods_list FROM eload_activity');

    while($row = $db->fetchArray()) {
        $arr[$row['id']] = trim((string)$row['act_goods_list'], ',');
    }

    write_static_cache('activity', $arr, 2);
}