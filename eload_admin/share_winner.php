<?php
define('INI_WEB', true);
require_once('../lib/global.php');//引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('share_winner');    //检查权限
$_ACT = 'list';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if($_ACT == 'list'){
	$winner_list = winner_list();
	$Arr['winner_list'] =    $winner_list['winner_list'];
	$page=new page(array('total'=>$winner_list['record_count'],'perpage'=>$winner_list['page_size']));
    $Arr["pagestr"]  = $page->show();
}
elseif($_ACT=='edit'){
	$id = isset($_GET['id'])?intval($_GET['id']):'';
	if(!empty($id)){
		$sql = "select * from eload_share_winner where id = '".$id."'";
		$Arr['winner'] = $db->selectInfo($sql);
		$tag_msg = "修改";
	}else{
		$tag_msg = "添加";
	}
	$Arr['tag_msg'] = $tag_msg ;
}
elseif($_ACT=='add'){
	$id = isset($_POST['id'])?intval($_POST['id']):'';
	$email = isset($_POST['email'])?trim($_POST['email']):'';
	$goods_sn = isset($_POST['goods_sn'])?trim($_POST['goods_sn']):'';
	$fb_uid = isset($_POST['fb_uid'])?intval($_POST['fb_uid']):'';
	$user_id = isset($_POST['user_id'])?intval($_POST['user_id']):'';
	$add_time = isset($_POST['add_time'])?strtotime($_POST['add_time']):gmtime();
	$link[0]['name'] = "返回中奖列表" ;
	$link[0]['url'] ='/eload_admin/share_winner.php';
	if($email && $fb_uid ){
		if(!empty($id)){
			$sql = "update eload_share_winner set email = '".$email."',user_id = '".$user_id."',fb_uid ='".$fb_uid."',goods_sn ='".$goods_sn."' where id ='".$id."'";	
		}else{
			$sql = "insert into eload_share_winner (`email`,`user_id`,`fb_uid`,`goods_sn`,`add_time`) values('".$email."','".$user_id."','".$fb_uid."','".$goods_sn."','".$add_time."')";
		}
		$db->query($sql);
		//如果没有添加SKU就是送50积分
		
		if(empty($goods_sn) && !empty($user_id)){
			$note = "Get EB Points from share winner";
			add_point($user_id,50,2,$note);
		
		}
		/* 记录管理员操作 */
		admin_log(addslashes($email), 'add', 'share_winner');

		/* 提示信息 */
		
		sys_msg(sprintf('添加成功', $email), 1, $link);
	}else{
	
		/* 提示信息 */
		sys_msg(sprintf('添加失败',''), 1, $link);
	}
}
elseif($_ACT =='del'){
	$id = isset($_GET['id'])?intval($_GET['id']):'';
	$link[0]['name'] = "返回中奖列表" ;
	$link[0]['url'] ='/eload_admin/share_winner.php';
	if($id){
		$db->query("delete from eload_share_winner where id ='".$id."'");
		/* 记录管理员操作 */
		admin_log(addslashes($id), 'del', 'share_winner');

		/* 提示信息 */
		
		sys_msg(sprintf('删除成功', $id), 0, $link);
	}else{
		/* 提示信息 */
		sys_msg(sprintf('删除失败', $id), 0, $link);
	}
}
elseif($_ACT =='admin_share'){
	$data = read_static_cache('admin_share',1);
	$str = '';
	$count = count($data);
	if(!empty($data)){
		foreach($data as $key=>$row){
			if($key >= $count-1){
				$str .= $key.'=>'.$row;
			}else{
				$str .= $key.'=>'.$row.',';
			}
		}
	}
	$Arr['data'] = $str;
	if($_POST){
		$admin_share = isset($_POST['admin_share'])?$_POST['admin_share']:'';
		$data = explode(',',$admin_share);
		$data = array_filter($data);
		$goods_info = array();
		foreach($data as $row){
			$val = explode('=>',$row);
			$goods_info[$val[0]]=$val[1];
		}
		write_static_cache('admin_share',$goods_info,1);
		$link[0]['name'] = "返回列表" ;
		$link[0]['url'] ='/eload_admin/share_winner.php?act=admin_share';
		sys_msg('添加成功', 0, $link);
	}
	$_ACT = 'goods';
}
//分享统计查询
elseif($_ACT == 'share_list'){
	$size = 24;
	$page = isset($_GET['page'])?intval($_GET['page']):1;
	$start = ($page - 1)*$size;
	if(isset($_REQUEST) && !empty($_REQUEST['start_date'])){
		$start_date = empty($_REQUEST['start_date']) ? '' : local_strtotime($_REQUEST['start_date']);    //链接添加时间--开始
		$end_date = empty($_REQUEST['end_date']) ?gmtime() : local_strtotime($_REQUEST['end_date']);          //链接添加时间--结束
	}else{
		$time = gmtime() + 8*3600; //当前北京时间 +8
		$w=date( "w ",$time);
		//本周星期一时间戳
		$monday = $w==1?mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday"))):mktime(0, 0, 0, date("m",strtotime("next Monday")), date("d",strtotime("next Monday")), date("Y",strtotime("next Monday")));
		$last_monday = mktime(0, 0, 0, date("m",strtotime("last Monday")), date("d",strtotime("last Monday")), date("Y",strtotime("last Monday")));	
		//当前时间大于周一10点结束时间取下周一
		if($w==1 ){
			if(date("H",$time)<15 && 10<date("H",$time))
			{
				$monday	= mktime(0, 0, 0, date("m",strtotime("next Monday")), date("d",strtotime("next Monday")), date("Y",strtotime("next Monday")))+7*3600*24;
			}
			else 
			{
				$monday	= mktime(0, 0, 0, date("m",strtotime("next Monday")), date("d",strtotime("next Monday")), date("Y",strtotime("next Monday")));
			}
			if(date("H",$time)<10){

				$last_monday = mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday")))-7*3600*24;
			}else{
				$last_monday = mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday")));
			}
		}
		elseif($w<3 && $w != 0){
			if($w ==2 && date("H",$time)>14){
				$last_monday = mktime(0, 0, 0, date("m",strtotime("last Monday")), date("d",strtotime("last Monday")), date("Y",strtotime("last Monday")));
			}else{
				$last_monday = mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday")));
			}
		}
		else{
			$last_monday = mktime(0, 0, 0, date("m",strtotime("last Monday")), date("d",strtotime("last Monday")), date("Y",strtotime("last Monday")));
		}
		$start_date = $last_monday+2*3600; //只能加2个小时
		$end_date = $monday+10*3600;
	}
	$count_info = $db->arrQuery("select w.user_id from ".WJ_LINK." as w left join ".WJ_SHARE." as s on s.link_id = w.id where w.adddate> '".$start_date."' and w.adddate<'".$end_date."' group by s.user_id");
	$count = count($count_info);
	$sql = "select count(*) as sum,s.user_id,w.adddate,u.visit_count,u.last_login,u.email from ".WJ_LINK." as w left join ".WJ_SHARE." as s on w.id = s.link_id left join ".USERS." as u on u.user_id = s.user_id   where w.adddate> '".$start_date."' and w.adddate<'".$end_date."' group by s.user_id order by sum desc limit ".$start.",".$size;//取出时间段内分享的链接数和user_id
	$data = $db->arrQuery($sql);
	$info = array();
	if(!empty($data)){
		foreach($data as $key=>$row){
			$info[$row['user_id']] = $row;
			$info[$row['user_id']]['last_time'] = date('Y-m-d H:i:s',$row['last_login']);
			$adddate = $db->getOne("select adddate from ".WJ_LINK. " as w left join ".WJ_SHARE." as s on w.id = s.link_id where s.user_id ='".$row['user_id']."' order by w.adddate desc  limit 1");
			$info[$row['user_id']]['adddate'] = $adddate?date('Y-m-d H:i:s',$adddate):'';
			unset($adddate);
		}
		$page=new page(array('total'=>$count,'perpage'=>$size));
		$Arr["pagestr"]  = $page->show();
	}
	$Arr['start_date'] = empty($_REQUEST['start_date']) ? '' : ($_REQUEST['start_date']);
	$Arr['end_date'] = empty($_REQUEST['end_date']) ? '' : ($_REQUEST['end_date']);
	$Arr['data'] = $info;

}
/**
 *  返回列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function winner_list()
{
    $result = get_filter();
    if ($result === false)
    {
       
        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);
        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM  eload_share_winner" );

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT * from eload_share_winner  ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $winner_list = $GLOBALS['db']->arrQuery($sql);
	foreach($winner_list as $key=>$row){
	
		$winner_list[$key]['add_time'] = date('Y-m-d H:i:s',$row['add_time']);
		$winner_list[$key]['email']		= email_disp_process($row['email']);
	}
    $arr = array('winner_list' => $winner_list, 'filter' => $filter,
        'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}
$_ACT = $_ACT == 'msg'?'msg':'winner_'.$_ACT;
temp_disp();
?>