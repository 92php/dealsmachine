<?php
/**
 * collect_info.php         收集用户信息后台管理
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2013-05-28 15:25:10
 * @lastmodify              $Author: msl $ $Date: 2013-06-21 09:40:12 +0800 (周五, 2013-06-21) $
 */
define('INI_WEB', true);
set_time_limit(0);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
admin_priv('collect_info');    //检查权限

$_ACT           = isset($_GET['act']) ? $_GET['act'] : 'list';
$collect_info   = new CollectInfo();
call_user_func(array($collect_info, $_ACT . 'Action'));

$_ACT = 'collect_info_' . $_ACT;

temp_disp();

class CollectInfo {
    /**
     * 构造函数
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2013-05-28 15:27:08
     *
     * @return void 无返回值
     */
    public function __construct() {
        $this->_db = $GLOBALS['db'];
    }

    /**
     * 处理结果
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2013-05-29 14:54:19
     *
     * @return void 无返回值
     */
    public function action_resultAction() {
        $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        $result = isset($_POST['result']) ? trim($_POST['result']) : '';

        if ($id && $status) {
            $now = gmtime();

            $this->_db->update(COLLECT_INFO, $update = "action_time={$now},action_admin='{$_SESSION['WebUserInfo']['real_name']}',status={$status},action_result='{$result}'", 'info_id=' . $id);
            admin_log('', "编辑收集信息：{$id},", $update);
        }

        exit();
    }

    /**
     * 删除
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2013-06-20 13:46:28
     *
     * @return void 无返回值
     */
    public function deleteAction() {
        $id = isset($_POST['id']) ? map_int($_POST['id']) : 0;

        if ($id) {
            $this->_db->delete(COLLECT_INFO, "info_id IN({$id})");
            admin_log('', '删除收集信息：', $id);
        }

        exit();
    }

    /**
     * 列表
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2013-05-28 15:29:48
     *
     * @return void 无返回值
     */
    public function listAction() {
        global $Arr;

        $cat_arr    = read_static_cache('category_c_key', 2);    //分类
        $data       = array(-1 => '--分类--');

        foreach($cat_arr as $v) {

            if (!$v['parent_id'] && $v['is_show']) {
                $data[$v['cat_id']] = $v['cat_name'];
            }
        }

        $data[0] = 'Others';
        $Arr['cat_arr'] = $data;

        $Arr['status_arr'] = array(
            99 => '--状态--',
            0  => '待处理',
            1  => '已处理',
            2  => '不处理',
            3  => '处理中',
        );

        $Arr['column_arr'] = array(
            'username'  => '用户名',
            'email'     => '用户邮箱',
        );

        $filter     = page_and_size(array());
        $size       = $filter['page_size'];
        $keyword    = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $column     = isset($_GET['column']) ? trim($_GET['column']) : '';
        $status     = isset($_GET['status']) ? trim($_GET['status']) : '';
        $start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
        $end_date   = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
        $start_date2= isset($_GET['start_date2']) ? trim($_GET['start_date2']) : '';
        $end_date2  = isset($_GET['end_date2']) ? trim($_GET['end_date2']) : '';
        $cat_id     = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : -1;
        $type       = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $where      = ' WHERE type=' . $type;

        if ($keyword && $column && isset($Arr['column_arr'])) {//关键字
            $where .= " AND {$column}='{$keyword}'";
        }

        if ('' !== $status) {    //状态
            $status = intval($status);

            if (99 != $status) {
                $where .= ' AND status=' . $status;
            }
        }

        if ($start_date) {
            $where .= ' AND add_time>' . local_strtotime($start_date);
        }

        if ($end_date) {
            $where .= ' AND add_time<' . local_strtotime($end_date);
        }

        if ($start_date2) {
            $where .= ' AND action_time >' . local_strtotime($start_date2);
        }

        if ($end_date2) {
            $where .= ' AND action_time<' . local_strtotime($end_date2);
        }

        if (-1 != $cat_id) {
            $where .= ' AND cat_id=' . $cat_id;
        }

        $Arr['keyword']  = $keyword;
        $Arr['status']   = $status;
        $Arr['start_date'] = $start_date;
        $Arr['end_date'] = $end_date;
        $Arr['start_date2'] = $start_date2;
        $Arr['end_date2'] = $end_date2;
        $Arr['column'] = $column;
        $Arr['cat_id'] = $cat_id;
        $Arr['type'] = $type;

        $sql        = 'SELECT COUNT(*) FROM ' . COLLECT_INFO . $where;
        $count      = $this->_db->getOne($sql);

        if (!$count) {
            return;
        }

        $filter         = array('record_count' => $count);
        $filter         = page_and_size($filter);    //分页信息
        $page           = new page(array(
            'total'   => $count,
            'perpage' => $filter['page_size'],
        ));

        $Arr['pagestr'] = $page->show();
        $Arr['filter']  = $filter;

        $limit          =  ' LIMIT ' . $filter['start'] . ',' . $filter['page_size'];    //sql limit
        $sql            = "SELECT * FROM " . COLLECT_INFO . $where . ' ORDER BY info_id DESC ' . $limit;
        $arr            = $this->_db->arrQuery($sql);

        foreach ($arr as $k => $row) {
            $arr[$k]['add_time'] = local_date('Y-m-d H:i:s', $row['add_time']);
            $arr[$k]['action_time'] = $row['action_time'] ? local_date('Y-m-d H:i:s', $row['action_time']) : '';
            $arr[$k]['cat_name'] = isset($Arr['cat_arr'][$row['cat_id']]) ? $Arr['cat_arr'][$row['cat_id']] : $Arr['cat_arr'][0];
			$arr[$k]['email']	= email_disp_process($row['email']);
            /*if ($row['get_point'] > 10) {
                $arr[$k]['pics'] = $this->_db->arrQuery('SELECT paths,thumb_paths FROM ' . TESTIMONIAL_PIC . ' WHERE rid=' . $row['rid']);
                $arr[$k]['videos'] = $this->_db->arrQuery('SELECT paths FROM ' . TESTIMONIAL_VIDEO . ' WHERE rid=' . $row['rid']);
            }

            $arr[$k]['pass'] = rw_state($row['is_pass']);*/
        }

        $Arr['data']     = $arr;
    }//end listAction

    /**
     *
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2013-06-20 17:10:51
     *
     * @return void 无返回值
     */
    public function moveAction() {
        $id     = isset($_POST['id']) ? map_int($_POST['id']) : 0;
        $cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;

        if ($id) {
            $this->_db->update(COLLECT_INFO, 'cat_id=' . $cat_id, "info_id IN({$id})");
            admin_log('', '转移收集信息：', $id);
        }

        exit();
    }
}