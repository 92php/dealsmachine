<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
//admin_priv('memcache_manage');


$_ACT = 'list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

$new_db		= &$db;//这样写是便于后面修改;

/*------------------------------------------------------ */
//-- 列出缓存中的所有列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{

	$filter 	= page_and_size($filter);
	$pagesize	= $filter['page_size'];
	$page		= intval($_GET['page'])>0?intval($_GET['page']):1;
	$start		= ($page - 1)*$pagesize;

	$count		= $new_db->getOne('select count(*) as count from '.MEM_CACHE);
	$sql		= 'select * from '.MEM_CACHE.' limit '.$start.','.$pagesize;
	$res		= $new_db->arrQuery($sql);

    foreach($res as &$v) {

        if ($v['is_compress']) {
            $v['content'] = gzuncompress($v['content']);
        }
    }

	$page		= new page(array('total'=>$count,'perpage'=>$pagesize));

	$Arr["pagestr"]  	= $page->show();
	$Arr['memlist']		= $res;
}

elseif ($_ACT == 'add')
{
	$id		= intval($_GET['id']);
	if(isset($id) && $mid !== 0)
	{
	    $res			= $new_db->arrQuery('select * from '.MEM_CACHE.' where id='.$id);
	    $Arr['meminfo']	= $res[0];
	}
}
elseif ($_ACT == 'update')
{

}
elseif ($_ACT == 'submit')
{
	static $memcache 	= null;

	$id					= intval($_POST['id']);

    $minfo['filename']	= str_replace(ROOT_PATH, '', $_POST['filename']);
    $minfo['content']	= stripslashes($_POST['content']);
    $minfo['content_length'] = strlen($minfo['content']);
    $minfo['mid']		= sprintf('%u',crc32($minfo['filename']));
    $minfo['update_time']=gmtime();

    if ($minfo['content_length'] > 10000) {//长度大于1万,压缩
        $minfo['content'] = gzcompress($minfo['content']);
        $minfo['is_compress'] = 1;
        $minfo['gzcompress_length'] = strlen($minfo['content']);
    }
    else {
        $minfo['is_compress'] = 0;
        $minfo['gzcompress_length'] = 0;
    }

    $minfo['content'] = addslashes($minfo['content']);

    $pd_res				= $new_db->arrQuery('select * from '.MEM_CACHE.' where mid='.$minfo['mid']);

    if($id != '' && $id != 0 )
    {//修改

    	if(!empty($pd_res) && $pd_res[0]['id'] != $id)
    	{
    		$msg				= '系统中已经有对应的缓存文件!';
    		$links[0]['name']	= '重新修改!';
    		$links[0]["url"] 	= "memcache_manage.php?act=add&id=".$id;
    		sys_msg($msg,0,$links);
    	}

    	$new_db->autoExecute(MEM_CACHE,$minfo,'UPDATE'," id = $id");

    	admin_log('', _EDITSTRING_,'缓存管理:'.$id);
	    $links[0]["name"] 	= "继续编辑";
		$links[0]["url"] 	= "memcache_manage.php?act=add&id=".$id;
		$links[1]["name"] 	= "返回缓存列表";
		$links[1]["url"] 	= "memcache_manage.php";
		$msg 				= "编辑成功！";


    }else{

    	if($pd_res)
    	{
    		$msg				= '系统中已经有对应的缓存文件!';
    		$links[0]['name']	= '重新添加!';
    		$links[0]["url"] 	= "memcache_manage.php?act=add";
    		sys_msg($msg,0,$links);
    	}

    	$new_db->autoExecute(MEM_CACHE,$minfo,'INSERT');
    	$mem_id = $db->insertId();

    	admin_log('', _ADDSTRING_,'MEM缓存:'.$mem_id);

		$links[0]["name"] 	= "继续添加";
		$links[0]["url"] 	= "memcache_manage.php?act=add";
    	$links[1]["name"] 	= "返回列表";
		$links[1]["url"] 	= "memcache_manage.php";
		$msg 				= "添加成功！";
    }

    $data				= unserialize($minfo['content']);
    $filename			= $minfo['filename'];

    cache($filename,$data,false);

    sys_msg($msg,0,$links);
}
elseif ($_ACT == 'del')
{

}
elseif('clear' == $_ACT) {//清除memcache

    if ('POST' == REQUEST_METHOD) {
        $_LANG = array();
        clear_memcache();
    }
}

$_ACT = 'memcache_'.$_ACT;

temp_disp();

/**
 * 清除memcache
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2014-06-16 16:50:09
 *
 * @return void 无返回值
 */
function clear_memcache() {
    global $db;

    $filename = Filter::string('filename');

    if (!$filename) {
        sys_msg('请输入您要清理的缓存文件名');
    }
    else {
        $delete_db      = Filter::int('delete_db');
        $filename_arr   = explode("\n", $filename);
        $memcache       = get_memcache();

        foreach($filename_arr as $item) {
            $item = trim($item);

            if ($item) {

                if ('all' == $item) {//全部
                    $memcache->flush();

                    break;
                }
                elseif ('payment' == $item) {//付款方式
                    clear_memcache_payment();
                }
                else {
                    cache(ROOT_PATH . $item, null, $delete_db);
                }
            }
        }
    }

    admin_log('', '','清理memcache缓存: ' . nl2br($filename));

    $links[0]['name'] 	= '继续清理';
    $links[0]['url'] 	= 'memcache_manage.php?act=clear';
    $links[1]['name'] 	= '返回列表';
    $links[1]['url'] 	= 'memcache_manage.php';
    $msg 				= '清理成功！';

    sys_msg($msg, 0, $links);
}//end clear_memcache

/**
 * 清除付款方式memcache缓存
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2014-06-17 09:05:29
 *
 * @todo 如何重新设置多语言
 *
 * @return void 无返回值
 */
function clear_memcache_payment() {
    //todo
    return true;

    global $_LANG;
    $dir = Dir::listDirOnly(ROOT_PATH . 'languages/');

    foreach($dir as $item) {
        $dirname    = $item->getBasename();
        $path       = $item->getPathname() . '/';

        if (!isset($dirname{2})) {//en,ru...多语言

            foreach(glob($path . 'payment/*.php') as $item) {
                require($item);
                require($path . 'common.php');
                include(ROOT_PATH . ADMIN_STATIC_CACHE_PATH . 'payment.php');

                //cache($data);
            }
        }
    }
}