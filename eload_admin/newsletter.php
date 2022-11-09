<?php
/**
 * newsletter.php           邮件期刊管理
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-07-11 14:08:57
 * @last modify             14:37 2012-08-03 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php'); //引入全局文件
require_once(ROOT_PATH . 'lib/is_loging.php');
require_once(ROOT_PATH . 'lib/time.fun.php');
require_once(ROOT_PATH . 'lib/class.newsletter.php');

admin_priv('newsletter'); //检查权限
$newsletter = new Newsletter();

$_ACT = isset($_GET['act']) ? $_GET['act'] : 'list';

switch ($_ACT) {
    case 'add'://添加或编辑
        $Arr['data'] = $newsletter->info();
        break;

    case 'save'://保存
        $newsletter->save();
        break;

    case 'preview'://预览 by mashanling on 14:56 2012-07-31
        $newsletter->preview();
        break;

    case 'delete'://删除
        $newsletter->delete();
        exit();
        break;

    default://列表
        $Arr['data'] = $newsletter->_list();
        break;
}

$_ACT = 'newsletter/' . $_ACT;
temp_disp();