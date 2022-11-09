<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('promotion_manage');  //检查权限
$Arr['method_arr'] = array(1 => '百分比', 2 => '直减金额', 3 => '抽奖直减金额');
$Arr['type_arr'] = array(0 => '促销码', 1 => '代金券');
$_ACT = 'list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

/*------------------------------------------------------ */
//-- 管理界面
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    $pcode_list = pcode_list();
    $Arr["pcode_list"]  =  $pcode_list['type'];
    $Arr["filter"]  =     $pcode_list['filter'];
	$page=new page(array('total'=>$pcode_list['record_count'],'perpage'=>$pcode_list['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加促销码
/*------------------------------------------------------ */

elseif ($_ACT == 'add')
{
	$tag_msg = "添加";
	$url = "?act=insert&id=$_ID";
	if ($_ID!=''){
	    $tag_msg = "修改";
		$sql       = "select * from ".PCODE." where id = $_ID";
		$pcodeArr          = $db -> selectInfo($sql);
		$pcodeArr['users'] = userid2email($pcodeArr['users'],true);
		$pcodeArr['exp_time'] = local_date('Y-m-d H:i', $pcodeArr['exp_time']);
	}else{
		$pcodeArr['exp_time'] = local_date('Y-m-d H:i', strtotime('+1 month'));
		$pcodeArr['youhuilv'] = 10;
	}
	$Arr["pcodeArr"]   = $pcodeArr;
	$Arr["tag_msg"] = $tag_msg;
	$Arr["url"] = $url;
}

elseif ($_ACT == 'insert')
{
    $pcodeArr['code']        = htmlspecialchars(trim($_POST['code']));
    $pcodeArr['users']       = htmlspecialchars(trim($_POST['users']));
    $pcodeArr['exp_time']    = local_strtotime($_POST['exp_time']);
    $pcodeArr['youhuilv']    = htmlspecialchars(trim($_POST['youhuilv']));
    $pcodeArr['goods']    = htmlspecialchars(trim($_POST['goods']));
    $pcodeArr['fangshi']     = intval($_POST['fangshi']);
    $pcodeArr['times']       = intval($_POST['times']);
    $pcodeArr['is_applay']   = empty($_POST['is_applay'])?0:intval($_POST['is_applay']);
    $category_id             = isset($_POST['cat_id'])?$_POST['cat_id']:0;
    $pcodeArr['cat_id'] = $category_id;
    
	if($pcodeArr['users'] ){
        $arr = explode(',', strtolower($pcodeArr['users']));
        $pcodeArr['users'] = join(',', $arr);
	    $user = str_replace(',',"','",$pcodeArr['users']);

	    $sql = "select user_id from ".USERS." where email in('$user')";

	    $user_arr = $db->arrquery($sql);//print_r($user_arr);
	    $user_ids = arr2str($user_arr,'user_id');
	    $user_ids = str_replace("'",'',$user_ids);
	    $pcodeArr['users'] = $user_ids;

    }
    
	if ($_ID!=''){
		if ($db->autoExecute(PCODE, $pcodeArr,'UPDATE'," id = $_ID") !== false){
			$msg="修改成功";
			admin_log($sn = '', _ADDSTRING_, '促销码 '.$pcodeArr['code']);
		}else{$msg="添加失败";}
				$links = array('0'=> array('url'=>'?act=list','name'=>'返回促销码列表'),
							   '1'=> array('url'=>'?act=add','name'=>'返回添加促销码'),
							   '2'=> array('url'=>'?act=add&id='.$_ID,'name'=>'还需要修改'));
	}else{
		$code = $db->getOne("SELECT code FROM ".PCODE." where code = '".$pcodeArr['code']."'");
		if(!empty($code)){
			sys_msg("coupon号重复");
		}
		$pcodeArr['s_type']		    = 1;
		$pcodeArr['create_time']    = gmtime();
		if ($db->autoExecute(PCODE, $pcodeArr) !== false){
			$msg="添加成功";
			admin_log($sn = '', _ADDSTRING_, '促销码 '.$pcodeArr['code']);
		}else{$msg="添加失败";}
		$links = array('0'=> array('url'=>'?act=list','name'=>'返回促销码列表'),
					   '1'=> array('url'=>'?act=add','name'=>'返回添加促销码'));   //返回地址
	}
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 删除促销码
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{
    if (!empty($_POST['checkboxes'])) {//选中删除
        $_ID = join(',', $_POST['checkboxes']);
    }
       if ($_ID!=''){
        admin_log($sn='', _DELSTRING_, '促销码列表ID为 '.$_ID);
		$db -> delete(PCODE," id IN($_ID) ");
		$msg = "删除成功！";
		$links[] = array('url'=>'?act=list','name'=>'返回促销码列表');
		$_ACT = 'msg';
		$Arr["msg"]   = $msg;
		$Arr["links"] = $links;
    }
}

/*------------------------------------------------------ */
//-- ajax修改促销码名称
/*------------------------------------------------------ */
elseif ($_ACT == 'editinplace')
{
    $id  = intval($_POST['id']);
    $val = trim($_POST['value']);
    $db->update(PCODE," code = '$val' ", " id = '$id'");
    admin_log('', _EDITSTRING_,'促销码 '.$val);
    echo $val;
	exit();
}
elseif($_ACT =='check_code'){
	$code = isset($_GET['code'])?trim($_GET['code']):'';
	if(!empty($code)){
		$code = $db->getOne("SELECT id FROM ".PCODE." where code = '".$code."'");
		if($code){
			echo 1;
		}else{
			echo 0;
		}
	}else{
		echo 2;
	}
	exit;
}

//统计促销码订单总金额
elseif('get_pro_order' == $_ACT){
    //获取促销码
    $pcode = $_GET['pcode'];
    if(!$pcode){
        echo json_encode(array('res' => 'fail'));
        exit;
    }
    $sql    = 'select sum(order_amount) as amount from eload_order_info where promotion_code = "'.$pcode.'" and order_status > 0 and order_status < 7';
    $total_amount = $GLOBALS['db']->getOne($sql);    
    $total_amount = $total_amount?$total_amount:0;    
    exit(json_encode(array('res' => $total_amount)));
}

/**
 * 获得所有促销码
 *
 * @access  public
 * @return  array
 */
function pcode_list()
{
	global $db;
	/* 记录总数以及页数 */
	$filter['record_count'] = 0 ;
	$keyword = empty($_GET['keyword']) ? '' : $_GET['keyword'];//关键字
	$fangshi = isset($_GET['fangshi']) && $_GET['fangshi'] !== '' ? intval($_GET['fangshi']): '';//方式
	$is_applay = isset($_GET['is_applay']) ? ($_GET['is_applay'] === '' ? 2 : intval($_GET['is_applay'])) : 0;//代金券或促销码
	$column  = isset($_GET['column']) ? $_GET['column'] : '';//字段
	$expired  = isset($_GET['expired']) ? 1 : 0;//过期
	$use_status  = isset($_GET['use_status']) ? intval($_GET['use_status']) : 0;//使用情况
	$cat_id  = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
	$type = isset($_GET['s_type']) ? intval($_GET['s_type']) :3;//coupon来源 0为系统发送 1为后台添加
	$filter['s_type'] = $type;
	$filter['keyword'] = $keyword;
	$filter['fangshi'] = $fangshi;
	$filter['column'] = $column;
	$filter['is_applay'] = $is_applay;
	$filter['expired'] = $expired;
	$filter['use_status'] = $use_status;
	$filter['cat_id'] = $cat_id;
	$where = '1';
	$where .= $is_applay != 2 ? ' AND is_applay= ' . $is_applay : '';
	$where .= $keyword ? " AND {$column} LIKE '%{$keyword}%'" : '';
	$where .= $fangshi === '' ? '' : " AND fangshi={$fangshi}";
	$where .= $expired ? ' AND exp_time<' . gmtime() : '';
	if(3!=$type){
		$where .= ' AND s_type="'.$type.'"';
	}
	if($use_status == 1) {//从未使用
	    $where .= ' AND cishu=0';
	}
	elseif ($use_status == 2) {
	    $where .= ' AND times>0 AND cishu>=times';
	}
	$where_cat_2 = '';
	$where_cat_1 = '';
	if(1 == $cat_id) {//产品限制
	    $where_cat_1 = " AND (goods <> '')";
	}
	elseif(2 == $cat_id) {
	    $where_cat_1 = " AND (goods = '')";
	}	
	$filter['record_count'] = $db->count_info(PCODE,"*",$where.$where_cat_1);
	$filter = page_and_size($filter);	
	/* 查询记录 */
	if(1 == $cat_id) {//产品限制
	    $where_cat_2 = " AND (pd.goods <> '')";
	}
	elseif(2 == $cat_id) {
	    $where_cat_2 = " AND (pd.goods = '')";
	}	
	$sql = "SELECT pd.*, c.cat_name ".
	   "FROM ". PCODE. " pd LEFT JOIN ". CATALOG ." c ON pd.cat_id = c.cat_id  WHERE {$where}{$where_cat_2} order by id desc limit $filter[start],$filter[page_size]";
	
	$all = $db->arrQuery($sql);
	foreach($all as $key => $val){
		$all[$key]['create_time'] = local_date('Y-m-d', $all[$key]['create_time']);
		$all[$key]['exp_time'] = local_date('Y-m-d H:i', $all[$key]['exp_time']);
		//$all[$key]['users']		= email_disp_process($val['users']);
		$all[$key]['users'] 		= userid2email($val['users'],false);
	}	
    return array('type' => $all, 'filter' => $filter, 'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
}


function userid2email($userid,$display_full_email= false){
	global $db;
	if(empty($userid))return '';
	if(strpos($userid,'@') !== false){
		if(!$display_full_email)$userid=email_disp_process($userid); //是否隐藏部分email)
		return $userid;
	}
	$userid = str_replace(',',"','",$userid);
	$userid = "'$userid'";
	$sql = "select email from ".USERS." where user_id in($userid)";
	$email_arr = $db ->arrquery($sql);

//    foreach($email_arr as &$v) {
//        $v['email'] = decrypt_email($v['email']);
//    }

	if(!$display_full_email){  //是否隐藏部分email
		foreach ($email_arr as &$v){
			$v['email'] = email_disp_process($v['email']);
		}
	}
	$emails = arr2str($email_arr,'email');
	$emails = str_replace("'","",$emails);
	return $emails;
}



$_ACT = $_ACT == 'msg'?'msg':'promotion_'.$_ACT;
temp_disp();
