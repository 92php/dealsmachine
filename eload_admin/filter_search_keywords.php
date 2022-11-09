<?php
/**
 * filter_search_keywords.php   搜索关键字过滤设置，包括
 * 1、单复数处理
2、不参与搜索词处理
3、排除词处理，如men 排除包含women
 * 
 * @author                      mashanling(msl-138@163.com)
 * @date                        2012-11-22 10:33:57
 * @last modify                 2013-8-28  by Jim
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require_once(ROOT_PATH . 'eload_admin/libs/class.filterkeyword.php');

$class     = new FilterKeyword();
$priv_key  = basename(__FILE__ , '.php');

admin_priv(basename(__FILE__ , '.php'));    //检查权限

$_ACT          = isset($_GET['act']) ? $_GET['act'] : 'get_data';    //操作

switch ($_ACT) {
    case 'get_data'://获取数据
        $Arr['data'] = $class->getData();
        //print_r( $Arr['data'] );
        $_ACT = $priv_key;
        temp_disp();
        break;
        
    case 'save':    //保存
        sleep(1);
        $not_search  = isset($_POST['not_search']) ? strtolower(stripslashes(trim($_POST['not_search']))) : '';//不参与搜索
        $not_search  = strpos($not_search, ' ') !== false ? preg_replace('/ +/', '', $not_search) : $not_search;
        
        $no_this_start  = isset($_POST['no_this_start']) ? strtolower(stripslashes(trim($_POST['no_this_start']))) : '';//以此些词开始就不入库
        $no_this_start  = strpos($no_this_start, ' ') !== false ? preg_replace('/ +/', '', $no_this_start) : $no_this_start;        

        $no_this_end  = isset($_POST['no_this_end']) ? strtolower(stripslashes(trim($_POST['no_this_end']))) : '';//以此些词开始就不入库
        $no_this_end  = strpos($no_this_end, ' ') !== false ? preg_replace('/ +/', '', $no_this_end) : $no_this_end;        
        
        $dan_fu      = isset($_POST['dan_fu']) ? strtolower(stripslashes(trim($_POST['dan_fu']))) : '';//单复数
        //$dan_fu      = strpos($dan_fu, ' ') !== false ? preg_replace('/ +/', '', $dan_fu) : $dan_fu;
        
        $exclude     = isset($_POST['exclude']) ? strtolower(stripslashes(trim($_POST['exclude']))) : '';//排除
        $exclude     = strpos($exclude, ' ') !== false ? preg_replace('/ +/', '', $exclude) : $exclude;
        $data        = array(
            'not_search' => $not_search ? explode(',', $not_search) : array(),
            'exclude'    => $class->explodeExclude($exclude),
        	'dan_fu'     => $class->explodeDanfu($dan_fu),
        	'no_this_start'     => $no_this_start ? explode(',', $no_this_start) : array(),
        	'no_this_end'     => $no_this_end ? explode(',', $no_this_end) : array(),
        );
       // print_r($data);
        //exit;
        $class->setData($data);
        exit();
		//搜索词包含不加入abc词库
	case 'search_no_abc':
		if($_POST){
			$admin_share = isset($_POST['search_no_abc'])?$_POST['search_no_abc']:'';
			$data = explode(',',$admin_share);
			$data = array_filter($data);
			$goods_info = array();
			foreach($data as $row){
				$goods_info[]=strtolower($row);
			}
			write_static_cache('search_no_abc',$goods_info,1);
			$link[0]['name'] = "返回列表" ;
			$link[0]['url'] ='/eload_admin/filter_search_keywords.php?act=search_no_abc';
			sys_msg('添加成功', 0, $link);
		}
		$data = read_static_cache('search_no_abc',1);
		if(!empty($data)){
			$Arr['data'] = implode(',',$data);
		}
		$_ACT = "search_no_abc";
        temp_disp();
		break;
}