<?php
/**
 * syn_rma.php              同步RMA
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-03-05 13:47:08
 * @last modify             2012-10-18 14:25:00 by mashanling
 */

set_time_limit(0);
define('INI_WEB', true);
require('../lib/global.php');
require(ROOT_PATH . 'lib/time.fun.php');
require(ROOT_PATH . 'lib/class.rma.php');
global $cur_lang, $default_lang;
if (file_exists(ROOT_PATH . 'lib/class.function.php')) {//E网class.function.php
   require(ROOT_PATH . 'lib/class.function.php');
}

$act = isset($_GET['act']) ? $_GET['act'] : 'syn_data';

switch ($act) {
    case 'update_status'://ERP同步更改状态
        empty($_POST['data']) && exit('0');
        $data = unserialize(stripslashes_deep($_POST['data']));
        $data = RMA::updateStatus($data);
        echo serialize($data);
        break;

    case 'update_return'://ERP同步更改是否退回状态
        empty($_POST['data']) && exit('0');
        $data = unserialize(stripslashes_deep($_POST['data']));
        $data = RMA::updateIsReturn($data);
        echo serialize($data);
        break;

    case 'test_update'://测试更新
        $data   = RMA::getSynDataMsg();
        $return = array();

        foreach ($data as $item) {
            $return = array(
                'rma_number' => $item['rma_number'],
                'time'		 => local_date('Y-m-d H:i:s'),
                'result'	 => 2,
                'status'	 => 9,
            );
            var_dump(RMA::updateStatus($return));
        }
        break;

    case 'test_syn'://测试同步

        $data   = unserialize(stripslashes_deep($_POST['RMA_data']));
        $return = array();

        $data   = RMA::getSynDataTrackingNumber();
        $return = array();
        foreach ($data as $item) {
            $return[] = $item['rma_order_id'];
        }

        echo serialize($return);
        break;

    case 'erp_syn_msg'://ERP同步留言至网站
        empty($_POST['data']) && exit('0');
        require(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');  //取得模版标题
		if($cur_lang != $default_lang) {
			$mail_subject = $mail_conf[$cur_lang][45];
			$mail_content = file_get_contents(ROOT_PATH . 'eload_admin/email_temp/'. $cur_lang .'/45.html');
		} else {
			$mail_subject = $mail_conf['en'][45];
			$mail_content = file_get_contents(ROOT_PATH . 'eload_admin/email_temp/en/45.html');
		}
        $data         = unserialize(stripslashes_deep($_POST['data']));
        $data         = RMA::execERPMsg($data);
        echo serialize($data);
        break;

    case 'syn_msg'://同步留言
        //$url = IS_LOCAL ? 'http://www.e.com/syn/syn_rma.php?act=test_syn' : 'http://www.davismicro.com.cn:9000/stock_admin/syn_rma.php?act=in_phase';
        $url = IS_LOCAL ? 'http://192.168.3.92:81/stock_admin/syn_rma.php?act=take_over_message' : 'http://www.davismicro.com.cn:9000/stock_admin/syn_rma.php?act=take_over_message';
        RMA::execSyn($url, 'msg');
        echo 'OK';
        break;

    case 'syn_tracking_number'://同步跟踪号by mashanling on 2012-08-29 15:28:33
        $url = IS_LOCAL ? 'http://192.168.3.92:81/stock_admin/syn_rma.php?act=take_over_tracking_number' : 'http://www.davismicro.com.cn:9000/stock_admin/syn_rma.php?act=take_over_tracking_number';
        //$url = IS_LOCAL ? 'http://v2.e.com/syn/syn_rma.php?act=test_syn' : 'http://www.davismicro.com.cn:9000/stock_admin/syn_rma.php?act=take_over_message';
        RMA::execSyn($url, 'tracking_number');
        echo 'OK';
        break;

    default://同步数据到ERP
        $url = IS_LOCAL ? 'http://192.168.3.92:81/stock_admin/syn_rma.php?act=in_phase' : 'http://www.davismicro.com.cn:9000/stock_admin/syn_rma.php?act=in_phase';
        RMA::execSyn($url);
        echo 'OK';
        break;
}

//Func::crontab_log($_SERVER['SCRIPT_NAME'], $msg, $time_start);
?>