<?php
/**
 提交用户评论
*/
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');

if (!isset($_REQUEST['cmt']) && !isset($_ACT))
{
    /* 只有在没有提交评论内容以及没有act的情况下才跳转 */
    header("Location: ./\n");
    exit;
}



if ($_ACT == 'index')
{
	$cmt = new stdClass();
    $cmt->id   = !empty($_POST['id'])   ? intval($_POST['id'])   : 0;
    $cmt->type = !empty($_POST['type']) ? intval($_POST['type']) : 0;
    $cmt->page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
    $cmt->rank = !empty($_POST['rank']) ? intval($_POST['rank']) : 5;
    $cmt->content = !empty($_POST['content']) ? trim($_POST['content']) : '';
    $cmt->email = !empty($_POST['email']) ? trim($_POST['email']) :'';
    $cmt->nickname = !empty($_POST['nickname']) ? trim($_POST['nickname']) : 'Anonymous User';
	/* 没有验证码时，用时间来限制机器人发帖或恶意发评论 */
	if (!isset($_SESSION['send_time']))
	{
		$_SESSION['send_time'] = 0;
	}
	$cur_time = gmtime();
	if (($cur_time - $_SESSION['send_time']) < 30) // 小于30秒禁止发评论
	{
		echo 'I am sorry, please do not comment frequently made, 30 seconds in order to continue to issue!';
		exit();
	}
	else
	{
		$factor = intval($_CFG['comment_factor']);
		if ($cmt->type == 0 && $factor > 0)
		{
			/* 只有商品才检查评论条件 */
			switch ($factor)
			{
				case COMMENT_LOGIN :
					if ($_SESSION['user_id'] == 0)
					{
						$result['error']   = 1;
						$result['message'] = $_LANG['comment_login'];
					}
					break;

				case COMMENT_CUSTOM :
					if ($_SESSION['user_id'] > 0)
					{
						$sql = "SELECT o.order_id FROM " . $ecs->table('order_info') . " AS o ".
							   " WHERE user_id = '" . $_SESSION['user_id'] . "'".
							   " AND o.order_status = '" . OS_CONFIRMED . "' ".
							   " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') ".
							   " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ".
							   " LIMIT 1";


						 $tmp = $db->getOne($sql);
						 if (empty($tmp))
						 {
							$result['error']   = 1;
							$result['message'] = $_LANG['comment_custom'];
						 }
					}
					else
					{
						$result['error'] = 1;
						$result['message'] = $_LANG['comment_custom'];
					}
					break;

				case COMMENT_BOUGHT :
					if ($_SESSION['user_id'] > 0)
					{
						$sql = "SELECT o.order_id".
							   " FROM " . $ecs->table('order_info'). " AS o, ".
							   $ecs->table('order_goods') . " AS og ".
							   " WHERE o.order_id = og.order_id".
							   " AND o.user_id = '" . $_SESSION['user_id'] . "'".
							   " AND og.goods_id = '" . $cmt->id . "'".
							   " AND o.order_status = '" . OS_CONFIRMED . "' ".
							   " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') ".
							   " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ".
							   " LIMIT 1";
						 $tmp = $db->getOne($sql);
						 if (empty($tmp))
						 {
							$result['error']   = 1;
							$result['message'] = $_LANG['comment_brought'];
						 }
					}
					else
					{
						$result['error']   = 1;
						$result['message'] = $_LANG['comment_brought'];
					}
			}
		}
		/* 无错误就保存留言 */
		if (empty($result['error']))
		{
			add_comment($cmt);
			$_SESSION['send_time'] = $cur_time;
		}
	}
}
else
{
    /*
     * act 参数不为空
     * 默认为评论内容列表
     * 根据 _GET 创建一个静态对象
     */
    $cmt = new stdClass();
    $cmt->id   = !empty($_POST['id'])   ? intval($_POST['id'])   : 0;
    $cmt->type = !empty($_POST['type']) ? intval($_POST['type']) : 0;
    $cmt->page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
}


echo $_LANG['Comment_on_the_success'];
exit();

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 添加评论内容
 *
 * @access  public
 * @param   object  $cmt
 * @return  void
 */
function add_comment($cmt)
{
    /* 评论是否需要审核 */
    $status = 1 - $GLOBALS['_CFG']['comment_check'];

    $user_id = empty($_SESSION['user_id']) ? 0 : $_SESSION['user_id'];
    $email = empty($cmt->email) ? $_SESSION['email'] : trim($cmt->email);
    $nickname = !empty($cmt->nickname) ?  trim($cmt->nickname) :'Anonymous User';
    $email = htmlspecialchars($email);
    $nickname = htmlspecialchars($nickname);

    /* 保存评论内容 */
    $sql = "INSERT INTO " .COMMENT .
           "(comment_type, id_value, email, nickname, content, comment_rank, add_time, ip_address, status, parent_id, user_id) VALUES " .
           "('" .$cmt->type. "', '" .$cmt->id. "', '$email', '$nickname', '" .$cmt->content."', '".$cmt->rank."', ".gmtime().", '".real_ip()."', '$status', '0', '$user_id')";
    $result = $GLOBALS['db']->query($sql);
    return $result;
}

?>