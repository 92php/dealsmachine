<?php
define('INI_WEB', true);
$payment_list = "";
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
admin_priv('email_batch_send');  //检查权限
set_time_limit(0);

/* act操作项的初始化 */
$_ACT = 'form';
$_ID  = '';
if(!empty($_GET['act'])) $_ACT=$_GET['act'];
if(!empty($_GET['id'])) $_ID=$_GET['id'];

if($_ACT == 'form'){
	
    $sql = "SELECT template_id,template_subject FROM " . Mtemplates. " WHERE  type = 'template'";
    $res = $db->arrQuery($sql);
	$Arr['mailtemp'] = $res;
}

elseif($_ACT == 'list'){
	
    $listdb = get_sendlist();
	
    $Arr['listdb']  =  $listdb['listdb'];
	
    $sort_flag  = sort_flag($listdb['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$listdb['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
    $Arr['filter']  =  $listdb['filter'];

    $page=new page(array('total'=>$listdb['record_count'],'perpage'=>$listdb['page_size'])); 
	$Arr["pagestr"]  = $page->show();
	
}

elseif($_ACT == 'addlist'){
	
	$_VAL['email_type']         = empty($_GET['email_type'])?0:intval($_GET['email_type']);
	
	$_VAL['is_login']           = empty($_GET['is_login'])?0:intval($_GET['is_login']);
	$_VAL['login_times_from']   = empty($_GET['login_times_from'])?0:intval($_GET['login_times_from']);
	$_VAL['login_times_to']     = empty($_GET['login_times_to'])?0:intval($_GET['login_times_to']);
	
	$_VAL['order_times_from']   = empty($_GET['order_times_from'])?0:intval($_GET['order_times_from']);
	$_VAL['order_times_to']     = empty($_GET['order_times_to'])?0:intval($_GET['order_times_to']);
	$_VAL['order_amount_from']  = empty($_GET['order_amount_from'])?0:intval($_GET['order_amount_from']);
	$_VAL['order_amount_to']    = empty($_GET['order_amount_to'])?0:intval($_GET['order_amount_to']);
	
	$_VAL['start_time']         = empty($_GET['start_time'])?0:intval(strtotime($_GET['start_time']));
	$_VAL['end_time']           = empty($_GET['end_time'])?0:intval(strtotime($_GET['end_time'].' 23:59:59'));
	
	$_VAL['mail_temp']          = empty($_GET['mail_temp'])?0:intval($_GET['mail_temp']);
	
	
	$_VAL['creat_time']         = gmtime();   //邮件类型创建时间
	//插入Batch_email_type 表数据
	
	if($db->autoExecute(Batch_email_type,$_VAL)){
	    $email_type_id = $db->insertId();
	}else{
	    $email_type_id = 0;
	}
	
	if ($email_type_id == 0) { sys_msg("发送邮件队列类型不存在，你重新选择", 1, array(), false);}
	if($_VAL['start_time']==0 || $_VAL['end_time']==0 || ($_VAL['end_time'] < $_VAL['start_time'])) {
		sys_msg("时间段选择有错误，请重新选择！", 1, array(), false);
	}
	
	$sql ='';
	
	
	switch ($_VAL['email_type']){
		
		case '1':
		     $sql = 'SELECT firstname,lastname,email from '.USERS.' WHERE reg_time >='.$_VAL['start_time'].' AND  reg_time <='.$_VAL['end_time'].' ';
		break;
		
		case '2':
		     if($_VAL['is_login']==1){
				 if ($_VAL['login_times_to']!=0){
				   $sql = ' AND  last_login >='.$_VAL['start_time'].' AND  last_login <='.$_VAL['end_time'].' AND visit_count >='.$_VAL['login_times_from'].' AND  visit_count <='.$_VAL['login_times_to'].'';
				 }
			 }else{
				 $sql = 'AND last_login <='.$_VAL['start_time'].' ';
			 }
		
		     $sql = 'SELECT firstname,lastname,email from '.USERS.' WHERE 1=1  '.$sql;
		break;
		
		case '3':
			$where = "WHERE u.user_id = o.user_id ".
					 "AND u.user_id > 0 ";
					 
			$start_date = $_VAL['start_time'];
			$end_date   = $_VAL['end_time'];
			if ($start_date)$where .= " AND o.add_time >= '$start_date' ";
			if ($end_date)$where .= " AND o.add_time <= '$end_date' ";
			if($_VAL['order_times_from']) 	 $have = " HAVING order_num >= '$_VAL[order_times_from]' ";
			if($_VAL['order_times_to']) 	 $have .= " AND order_num <= '$_VAL[order_times_to]' ";
			if($_VAL['order_amount_from']) 	 $have .= " AND turnover >= '$_VAL[order_amount_from]' ";
			if($_VAL['order_amount_to']) 	     $have .= " AND turnover <= '$_VAL[order_amount_to]' ";
			
			$sql = "SELECT u.user_id, u.firstname,u.lastname,u.email, COUNT(*) AS order_num, sum(order_amount) AS turnover FROM ".USERS." AS u, ".ORDERINFO." AS o " .$where .
               "GROUP BY u.user_id  $have ORDER BY turnover DESC, order_num DESC";
			
		break;
	}
	//echo $sql;
//	exit;
	
	if ($sql!=''){
		$emailArr = $db->arrQuery($sql);
	}
	
	if(!empty($emailArr)){
		foreach($emailArr as $k => $val){
			$val['firstname']     = addslashes($val['firstname']);
			$val['lastname']      = addslashes($val['lastname']);
			$val['email']      = addslashes($val['email']);
			$val['template_id']   = $_VAL['mail_temp'];
			$val['email_type_id'] = $email_type_id;
			$db->autoExecute(Email_sendlist,$val); //插入邮件队列
		}
	}
	header("Location:email_batch_send.php?act=list");
    exit;
}

elseif ($_ACT == 'del')
{
    $id = (int)$_REQUEST['id'];
    $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$id' LIMIT 1";
    $db->query($sql);
	header("Location: ?act=list");
    exit;
}



/*------------------------------------------------------ */
//-- 批量删除
/*------------------------------------------------------ */

elseif ($_ACT == 'batch_remove')
{
    /* 检查权限 */
    if (isset($_POST['checkboxes']))
    {
        $sql = "DELETE FROM " . Email_sendlist . " WHERE id " . db_create_in($_POST['checkboxes']);
        $db->query($sql);

        $links[] = array('name' => '返回邮件队列', 'url' => '?act=list');
        sys_msg('删除成功', 0, $links);
    }
    else
    {
        $links[] = array('name' => $_LANG['返回邮件队列'], 'url' => '?act=list');
        sys_msg('你没有选择邮件！', 0, $links);
    }
}

/*------------------------------------------------------ */
//-- 批量发送
/*------------------------------------------------------ */

elseif ($_ACT == 'batch_send')
{
    /* 检查权限 */
    if (isset($_POST['checkboxes']))
    {
        $sql = "SELECT * FROM " . Email_sendlist . " WHERE id " . db_create_in($_POST['checkboxes']) . " ORDER BY pri DESC, last_send ASC LIMIT 1";
        $row = $db->selectinfo($sql);
        //发送列表为空
        if (empty($row['id']))
        {
            $links[] = array('name' => '返回邮件队列', 'url' => '?act=list');
            sys_msg('发送失败，邮件发送队列为空!', 0, $links);
        }

        $sql = "SELECT * FROM " . Email_sendlist . " WHERE id " . db_create_in($_POST['checkboxes']) . " ORDER BY pri DESC, last_send ASC";
		$res = $db->arrQuery($sql);
        foreach ($res as $row)
        {
            //发送列表不为空，邮件地址为空
            if (!empty($row['id']) && empty($row['email']))
            {
                $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
                $db->query($sql);
                continue;
            }

            //查询相关模板
            $sql = "SELECT * FROM " . Mtemplates . " WHERE template_id = '$row[template_id]'";
            $rt = $db->selectinfo($sql);

            if ($rt['template_id'] && $rt['template_content'])
            {
				
				$mail_temp =  varResume($rt['template_content']);
				$mail_subject = $rt['template_subject'];
				
				$row['firstname'] = empty($row['firstname'])?'':$row['firstname'];
				$row['order_no'] = empty($row['order_no'])?'':$row['order_no'];
				$row['order_id'] = empty($row['order_id'])?'':$row['order_id'];
				$row['Tracking_web'] = empty($row['Tracking_web'])?'':$row['Tracking_web'];
				$row['Tracking_NO'] = empty($row['Tracking_NO'])?'':$row['Tracking_NO'];
				
				$mail_temp        = str_replace('{$firstname}',$row['firstname'],$mail_temp);
				$mail_temp        = str_replace('{$order_no}',$row['order_no'],$mail_temp);
				$mail_temp        = str_replace('$order_id$',$row['order_id'],$mail_temp);
				$mail_temp        = str_replace('$Tracking_web$',$row['Tracking_web'],$mail_temp);
				$mail_temp        = str_replace('$Tracking_NO$',$row['Tracking_NO'],$mail_temp);
				$mail_subject     = str_replace('{$order_no}',$row['order_no'],$mail_subject);;
						
						
               if (exec_send($row['email'],$mail_subject,$mail_temp)){
				   
                    //发送成功
                    //从列表中删除
                    $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
                    $db->query($sql);
					
					$row['last_send'] = time();
					$row['state'] = 1;
 					$db->autoExecute(Email_send_history,$row);
                }
                else
                {
                    //发送出错
                    if ($row['error'] < 3)
                    {
                        $time = time();
                        $sql = "UPDATE " . Email_sendlist . " SET error = error + 1, pri = 0, last_send = '$time' WHERE id = '$row[id]'";
                    }
                    else
                    {
                        //将出错超次的纪录删除
                        $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
                    }
                    $db->query($sql);
                }
            }
            else
            {
                //无效的邮件队列
                $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
                $db->query($sql);
            }
        }

        $links[0] = array('name' => '返回邮件群发历史记录', 'url' => 'email_send_history.php');
        $links[1] = array('name' => '返回邮件队列', 'url' => '?act=list');
        sys_msg('全部邮件发送完成!', 0, $links);
    }
    else
    {
        $links[] = array('name' => '返回邮件队列', 'url' => '?act=list');
        sys_msg('未选择对象!', 0, $links);
    }
}

/*------------------------------------------------------ */
//-- 全部发送
/*------------------------------------------------------ */

elseif ($_ACT == 'all_send')
{
    $sql = "SELECT * FROM " . Email_sendlist . " ORDER BY pri DESC, last_send ASC LIMIT 1";
    $row = $db->selectinfo($sql);

    //发送列表为空
    if (empty($row['id']))
    {
        $links[] = array('name' => '返回邮件队列', 'url' => '?act=list');
		sys_msg('发送失败，邮件发送队列为空!', 0, $links);
    }

    $sql = "SELECT * FROM " . Email_sendlist . " ORDER BY pri DESC, last_send ASC";
	$res = $db->arrQuery($sql);
	foreach ($res as $row)
   {
        //发送列表不为空，邮件地址为空
        if (!empty($row['id']) && empty($row['email']))
        {
            $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
            $db->query($sql);
            continue;
        }

        //查询相关模板
        $sql = "SELECT * FROM " . Mtemplates . " WHERE template_id = '$row[template_id]'";
        $rt = $db->selectinfo($sql);


        if ($rt['template_id'] && $rt['template_content'])
        {
				               
				$rt['template_content'] =  varResume($rt['template_content']);
			$rt['template_content'] = str_replace(array('{$user_name}','{$shop_name}','{$send_date}'),
												  array($row['firstname'],$_CFG['email_shop_name'],date('M,d,Y')),
														$rt['template_content']);		   
			
            if (send_mail('', $row['email'], $rt['template_subject'], $rt['template_content'], $rt['is_html']))
            {
                //发送成功

                //从列表中删除
                $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
                $db->query($sql);
				
                $row['last_send'] = time();
				$row['state'] = 1;
				$db->autoExecute(Email_send_history,$row);
           }
            else
            {
                //发送出错

                if ($row['error'] < 3)
                {
                    $time = time();
                    $sql = "UPDATE " . Email_sendlist . " SET error = error + 1, pri = 0, last_send = '$time' WHERE id = '$row[id]'";
                }
                else
                {
                    //将出错超次的纪录删除
                    $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
                }
                $db->query($sql);
            }
        }
        else
        {
            //无效的邮件队列
            $sql = "DELETE FROM " . Email_sendlist . " WHERE id = '$row[id]'";
            $db->query($sql);
        }
    }

        $links[0] = array('name' => '返回邮件群发历史记录', 'url' => 'email_send_history.php');
        $links[1] = array('name' => '返回邮件队列', 'url' => '?act=list');
        sys_msg('全部邮件发送完成!', 0, $links);
}









function get_sendlist()
{
	$filter['sort_by']      = empty($_GET['sort_by']) ? 'template_subject' : trim($_GET['sort_by']);
	$filter['sort_order']   = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
	
	$sql = "SELECT count(*) FROM " . Email_sendlist . " e LEFT JOIN " . Mtemplates . " m ON e.template_id = m.template_id";
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	
	/* 分页大小 */
	$filter = page_and_size($filter);
	
	/* 查询 */
	$sql = "SELECT e.id, e.email, e.pri, e.error, FROM_UNIXTIME(e.last_send) AS last_send, m.template_subject, m.is_html FROM " . Email_sendlist . " e LEFT JOIN " . Mtemplates . " m ON e.template_id = m.template_id" .
		" ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
	   " LIMIT " . $filter['start'] . ",$filter[page_size]";
	set_filter($filter, $sql);
    $listdb = $GLOBALS['db']->arrQuery($sql);
    if($listdb)
    {
    	foreach($listdb as $k=>$v)
    	{
    		$listdb[$k]['email']	= email_disp_process($v['email']);
    	}
    }
	
    $arr = array('listdb' => $listdb, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}




$_ACT = $_ACT == 'msg'?'msg':'email_batch_'.$_ACT;
temp_disp();
?>