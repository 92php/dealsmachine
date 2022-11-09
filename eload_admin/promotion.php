<?php
/**
 * promotion.php		代金券管理
 *
 * @author				mashanling
 * @date				2011-08-11
 * @last modify         2011-08-12 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/param.class.php');

admin_priv('promotion_list');    //检查权限

$nav_arr     = array('name' => '代金券申请用户管理列表', 'url' => 'promotion.php');
$Arr['no_records'] = '<span style="color: red">暂无记录！</span>';
$Arr['method_arr'] = array(1 => '百分比', 2 => '直减金额');
$_ACT        = Param::get('act');    //操作
$Arr['act']  = $_ACT;

switch ($_ACT) {
    case 'remove':    //删除
        promotion_remove();
        break;

    case 'add':    //增加或编辑
        promotion_add();
        break;

    case 'update':    //更新
        promotion_update();
        break;

    case 'email':    //用户具体代金券
        promotion_users();
        break;

    default:    //首页
        promotion_index();
        break;
}
$_ACT = $_ACT == 'msg' ? 'msg' : 'new_promotion';
temp_disp();

/**
 * 首页列表
 *
 */
function promotion_index() {
    global $Arr, $nav_arr;

    $Arr['nav']      = $nav_arr['name'];
    //$Arr['nav_right'] = '<a href="?act=add">添加</a>';
    $select_field    = 'users.email,users.user_id,users.user_type,users.last_login,users.last_ip,users.reg_time,users.visit_count,users.user_rank,promotion.create_time,SUM(promotion.cishu) AS cishu,SUM(promotion.times) AS times';
    $count_field     = 'DISTINCT users.email';
    promotion_list($count_field, $select_field, ' GROUP BY users.email');
}

/**
 * 用户代金券列表
 *
 */
function promotion_users() {
    global $Arr, $nav_arr;

    $Arr['nav']     = "<a href=\"{$nav_arr['url']}\">{$nav_arr['name']}</a> -&gt;&gt; 用户个人代金券管理列表";
    //$Arr['nav_right'] = '<a href="promotion.php">返回用户管理列表</a>';
    $email          = Param::get('email');    //用户email
    $where          = " AND users.email='{$email}'";
    $select_field   = 'promotion.*';
    $count_field    = 'promotion.id';
    promotion_list($count_field, $select_field, $where, $where, $email);
}

/**
 * 添加或编辑
 *
 */
function promotion_add() {
    global $db, $Arr, $nav_arr;
    $id         = Param::get('id', 'int');
    $title      = '添加';
    $data['exp_time'] = strtotime('+1 month');
    if ($id) {
        $title   = '编辑';
        $data    = $db->select(PCODE, '*', 'id=' . $id);
        $data     = empty($data) ? array() : $data[0];
    }
    $Arr['data']      = $data;
    $Arr['nav']       = "<a href=\"{$nav_arr['url']}\">{$nav_arr['name']}</a> -&gt;&gt; {$title}代金券";
    //$Arr['nav_right'] = '<a href="promotion.php">返回用户管理列表</a>';
    $Arr['title']     = $title;
    $Arr['id']        = $id;
}

/**
 * 添加或编辑入库
 *
 */
function promotion_update() {
    global $db, $Arr, $_ACT;

    $data['code']        = Param::post('code');
    $data['users']       = Param::post('users');
    $data['exp_time']    = strtotime(Param::post('exp_time', 'int'));
    $data['youhuilv']    = Param::post('youhuilv');
    $data['goods']       = Param::post('goods');
    $data['fangshi']     = Param::post('fangshi', 'int');
    $data['times']       = Param::post('times', 'int');

    $id                  = Param::post('id', 'int');

	if ($id) {    //编辑
		if ($db->autoExecute(PCODE, $data, 'UPDATE', 'id=' . $id) !== false){
			$msg = '修改成功！';
			admin_log('', _EDITSTRING_, '代金券 ' . $id);
		}
		else{
		    $msg = '修改失败';
		}
		$links  = array(
			0  =>  array('url' => 'javascript: history.back()', 'name' => '返回代金券列表'),
			//1  =>  array('url' => 'promotion.php?act=add', 'name' => '返回添加代金券'),
		    2  =>  array('url' => 'promotion.php?act=add&amp;id=' . $id, 'name' => '还需要修改')
		);
	}
	else{    //添加
		$data['create_time']    = gmtime();
		if ($db->autoExecute(PCODE, $data) !== false){
			$msg = '添加成功';
			admin_log($sn = '', _ADDSTRING_, '代金券 ' . $data['code']);
		}
		else{
		    $msg = '添加失败';
		}
		$links = array('0' =>  array('url' => 'promotion.php', 'name' => '返回代金券列表'));
					   //'1' =>  array('url' => 'promotion.php?act=add', 'name' => '返回添加代金券'));
	}
	$_ACT         = 'msg';
	$Arr['msg']   = $msg;
	$Arr['links'] = $links;
}

/**
 * 删除
 *
 */
function promotion_remove() {
    global $db, $Arr, $_ACT, $nav_arr;
    $id = Param::get('id', 'int');
    if ($id) {
        admin_log('', _DELSTRING_, '代金券id为 ' . $id);
		$db->delete(PCODE, 'id=' . $id);
		//$db->update(PCODE, "users='mrmsl@qq.com'", 'id=' . $id);
		sys_msg('删除成功！');
		$_ACT         = 'msg';
    }
}

/**
 * 代金券列表
 *
 * @param string 	$count_field	count(?)
 * @param string	$select_field   选择字段
 * @param string	$select_where	select where
 * @param string	$count_where	count where
 * @param string	$email			用户email
 */
function promotion_list($count_field, $select_field, $select_where = '', $count_where = '', $email = '') {
    global $db, $Arr, $_Act;

    $where           = 'promotion.is_applay=1 AND users.email=promotion.users';
    $count_where     = $where . $count_where;
    $where          .= $select_where;

    $table           = PCODE . ' AS promotion JOIN ' . USERS . ' AS users';    //表名
    $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
    $record_count    = $record_count > 0 ? $record_count : $db->getOne("SELECT COUNT({$count_field}) FROM {$table} WHERE {$count_where}");

    if (!$record_count) {
        return;

    }
    $filter          = array('record_count' => $record_count);
    $filter          = page_and_size($filter);    //分页信息
    $page            = new page(array('total' => $record_count, 'perpage' => $filter['page_size'], 'url' => "?act={$_Act}&amp;record_cound={$record_count}&amp;email={$email}"));
	$Arr['pagestr']  = $page->show();
    $Arr['filter']   = $filter;

    $limit           = $filter['start'] . ',' . $filter['page_size'];    //sql limit

    $data            = $db->select($table, $select_field, $where, 'promotion.id DESC', $limit);
    
    if($data)
    {
    	foreach($data as $k=>$v)
    	{
    		$data[$k]['email']		= email_disp_process($v['email']);
    	}
    }

    $Arr['data']     = $data;
    $Arr['user_rank']= read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);    //用户等级
}
?>