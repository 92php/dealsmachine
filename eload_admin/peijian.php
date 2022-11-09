<?php
/**
 * peijian.php              配件管理
 * 
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-05-15 14:32:48
 * @last modify             2014-04-11 AM by fangxin
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');

admin_priv('peijian_manage');    //检查权限

$Arr['no_records']     = '<span style="color: red">暂无记录！</span>';
$_ACT                  = isset($_GET['act']) ? $_GET['act'] : 'list';    //操作
//分类联动
$cat_id   = isset($_REQUEST['cat_id']) ? ($_REQUEST['cat_id']) : '';//分类id串
if (!empty($cat_id) && is_array($cat_id)){
	foreach($cat_id as $key => $val){
		if($cat_id[$key] == '') unset($cat_id[$key]);
	}
}
$cat_id = empty($_REQUEST['cat_id']) ? 0 : intval(is_array($_REQUEST['cat_id'])?end($_REQUEST['cat_id']):$_REQUEST['cat_id']);	
if($cat_id){
	$Arr["cat_list"] = '';
	$parent_id_str = get_parent_id($cat_id);		
	$parent_id_Arr = explode(',',$parent_id_str);
	$parent_id_Arr = array_reverse ($parent_id_Arr); //数组逆序
	foreach($parent_id_Arr as $key => $val){
		if ($val!=''){
			$parent_id = $val;
			$selectid = isset($parent_id_Arr[$key+1])?$parent_id_Arr[$key+1]:$cat_id;
			$Arr["cat_list"] .=  get_lei_select($parent_id,'cat_id[]','cat_id','goods_cat',$selectid);
		}
	}
}else{
	$Arr["cat_list"] =  get_lei_select('0','cat_id[]','cat_id','goods_cat','','','所有分类');
}
$keywords = isset($_REQUEST['keywords']) ? $_REQUEST['keywords'] : '';
switch ($_ACT) {
    case 'add'://添加
        $Arr['add_type'] = array('商品分类','批量添加');
        break;        
    case 'save'://保存
        save_peijian();
    	break;        
    case 'edit_price':    //编辑价格
        edit_peijian_price();
        break;
	case 'category_add':
		break;
	case 'category_save':
		category_add_peijian();
		$_ACT='category_add';
		break;
	case 'category_list':
		$list=category_peijian_list();
		$Arr['data']=$list['data'];
		$Arr['pagestr']=$list['pagestr'];
		$Arr['filter']=$list['filter'];
		break;
    case 'delete'://删除
        delete_peijian_singer();
        break;		
	case 'del_category_peijian':
		del_category_peijian();
		echo "<script>window.location.href='peijian.php?act=category_list'</script>";
		break;
	case 'remove':
		del_category();
		echo "<script>window.location.href='peijian.php?act=category_list'</script>";
		break;
	case 'batch':
		batch();
		echo "<script>window.location.href='peijian.php?act=category_list'</script>";
		break;
	case 'edit':
		edit();
		break;
	case 'peijian_batch':
		$type = !empty($_POST['type'])?$_TYPE = $_POST['type']:'';
		if('del' == $type) {
			$data = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : '';
			delete_peijian($data);
			$link[0]["name"] = "返回上一页";
			$link[0]["url"] = $_SERVER["HTTP_REFERER"];			
		    sys_msg("批量操作成功" . (empty($return_message) ? '' : $return_message), 0, $link);			
		} 
		if('del_all' == $type) {
			$cat_id = new_get_children($cat_id);
			delete_peijian_all($cat_id,$keywords);
			$link[0]["name"] = "返回上一页";
			$link[0]["url"] = $_SERVER["HTTP_REFERER"];			
		    sys_msg("批量操作成功" . (empty($return_message) ? '' : $return_message), 0, $link);			
		}
		$order = !empty($_POST['order'])?$_TYPE = $_POST['order']:'';	
		if('order' == $type) {
			$cat_id = new_get_children($cat_id);
			update_category_peijian_order($cat_id,$order,$keywords);
			$link[0]["name"] = "返回上一页";
			$link[0]["url"] = $_SERVER["HTTP_REFERER"];			
		    sys_msg("批量操作成功" . (empty($return_message) ? '' : $return_message), 0, $link);			
		}
		break;
    default:    //管理列表
        get_peijian_list();
}

$_ACT = 'peijian/' . $_ACT;
temp_disp();

/**
 * 保存配件
 * 
 * @return void 无返回值
 */
function save_peijian() {
    global $db;
    $add_type = isset($_POST['add_type']) ? intval($_POST['add_type']) : 0;//添加类型，0编码添加，1分类添加，2批量添加
    $cat_id   = isset($_POST['cat_id']) ? map_int($_POST['cat_id']) : '';//分类id串
    $good_id  = isset($_POST['goods_id']) ? array_map('intval', $_POST['goods_id']) : '';//配件商品id
    $parent_id= isset($_POST['parent_id']) ? array_map('intval', $_POST['parent_id']) : '';//主商品id
    $price    = isset($_POST['price']) ? array_map('floatval', $_POST['price']) : '';//配件价格
    $main_sn  = isset($_POST['main_goods_sn']) ? $_POST['main_goods_sn'] : '';//主商品编码
    $goods_sn = isset($_POST['goods_sn']) ? $_POST['goods_sn'] : '';//配件编码
	$pl_goods_sn = isset($_POST['pl_goods_sn'])?$_POST['pl_goods_sn']:''; //多个用,分割的主商品sku
	$start_date  = isset($_POST['start_date'])?local_strtotime($_POST['start_date']):'';
	$end_date    = isset($_POST['end_date'])?local_strtotime($_POST['end_date']):'';
	$add_date    = time();
	$parent_goods_id = $pl_goods_sn;
	if($pl_goods_sn){
		$pl_goods_sn_str = "'";
		$pl_goods_sn_str .= str_replace(',',"','",$pl_goods_sn);
		$pl_goods_sn_str .="'";
	}else{
		$pl_goods_sn_str = '';
	}	
	$sort_order= isset($_POST['sort_order'])? array_map('intval',$_POST['sort_order']):0; //配件排序
    $log      = ',配件：' . var_export($goods_sn, true) . ',价格：' . var_export($price, true);//管理员操作日志
    $admin_id = $_SESSION['WebUserInfo']['said'];//管理员id
    $sql_arr  = array(array(), array());//sql数组	
	//批量添加，查询产品最大排序号
	if(1 == $add_type) {
		$sql_order = "SELECT g.goods_id,g.goods_sn,max(gg.sort_order) as sort_order FROM eload_group_goods gg LEFT JOIN eload_goods g ON gg.parent_id=g.goods_id WHERE g.goods_sn IN(". $pl_goods_sn_str .") GROUP BY g.goods_sn ORDER BY max(gg.sort_order) DESC";
		$res_order = $db->arrQuery($sql_order);
		if($res_order) {
			foreach($res_order as $key=>$value) {
				$sort_order[$key] = $value['sort_order'] + 1;
			}
		}
	}
	//循环配件信息
    foreach ($price as $k => $v) {	
		$v = $v*0.9; //配件价格设置为当前配件价格的90%		
        //分类添加
        $sql  = 'INSERT INTO ' . GROUPGOODS . '(parent_id,goods_id,goods_price,admin_id,sort_order,start_date,end_date,add_date) ';
        $sql .= " SELECT goods_id,{$good_id[$k]},{$v},{$admin_id},{$sort_order[$k]},{$start_date},{$end_date},{$add_date} FROM " . GOODS;
        $sql .= " WHERE cat_id IN({$cat_id}) AND is_delete=0 AND is_on_sale=1 AND goods_number>0";
        $sql .= ' ON DUPLICATE KEY UPDATE goods_price=' . $v;		
        $sql_arr[1][] = $sql;
    }
	//分类添加
    if ($add_type==0) {
        //删除该类下的商品的配件
        //$db->query('DELETE a FROM ' . GROUPGOODS . ' AS a JOIN ' . GOODS . " AS b ON a.parent_id=b.goods_id WHERE b.cat_id IN({$cat_id})");
        foreach ($sql_arr[1] as $sql) {
            $db->query($sql);
        }        
        $log = '分类id: ' . $cat_id . ', ' . $log;
    } 
	//批量添加
	elseif($add_type==1) {
		if(!empty($parent_goods_id)) {
			$parent_goods = explode(",", $parent_goods_id);
			foreach($parent_goods as $key=>$value) {
				$sql_order = "SELECT g.goods_id,g.goods_sn,max(gg.sort_order) as sort_order FROM eload_group_goods gg LEFT JOIN eload_goods g ON gg.parent_id=g.goods_id WHERE g.goods_sn IN('". $value ."') ORDER BY max(gg.sort_order) DESC";	
				$res = $db->selectInfo($sql_order);
				$sort_order = $res['sort_order'] + 1;
				if(empty($res['goods_id'])) {				
					$sql_order = "SELECT goods_id,goods_sn,cat_id FROM eload_goods WHERE goods_sn IN('". $value ."')";	
					$res = $db->selectInfo($sql_order);	
					$sort_order = 1;				
				}
				$parent_id = $res['goods_id'];
				$sort_order = $sort_order + 1;
				foreach($good_id as $key_g=>$value) {
					if(empty($res['goods_id'])) $res['goods_id'] = 0;
					$sql_ch = 'SELECT * FROM '. GROUPGOODS .' WHERE parent_id = '. $res['goods_id'] .' AND goods_id = '. $value .'';
					$res_ch = $db->selectInfo($sql_ch);
					if(empty($res_ch['parent_id'])) {				
						$sql = "INSERT INTO " . GROUPGOODS . "(parent_id,goods_id,goods_price,admin_id,sort_order,start_date,end_date,add_date) VALUES({$parent_id},{$value},{$price[$key_g]},{$admin_id},{$sort_order},{$start_date},{$end_date},{$add_date})";
						$db->query($sql);
						$sort_order += 1;
					}
				}
			}
		}
		/*
		foreach ($sql_arr[2] as $sql) {
            $db->query($sql);
        }
		*/
		$log = '主商品：' . var_export($main_sn, true) . ', ' . $log;
	}
    else {
        
        foreach ($parent_id as $k => $v) {//循环主商品
            
            foreach ($sql_arr[0] as $sql) {//添加配件
                $sql = str_replace('@parent_id', $v, $sql);
                $db->query($sql);
            }
        }
        
        $log = '主商品：' . var_export($main_sn, true) . ', ' . $log;
    }
    admin_log('', '批量' . _ADDSTRING_, '配件,' . $log);
    exit();
}//end save_peijian

/**
 * 编辑配件价格
 * 
 * @return void 无返回值
 */
function edit_peijian_price() {
    global $db;    
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    $goods_id  = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
    $edit_all  = isset($_POST['edit_all']) ? intval($_POST['edit_all']) : 0;//修改该配件所有价格 1是 0否
    $price     = isset($_POST['price']) ? floatval($_POST['price']) : 0;    
    if ($parent_id && $goods_id && $price) {
        $where  = 'goods_id=' . $goods_id;
        $where .= $edit_all ? '' : ' AND parent_id=' . $parent_id;
        $db->update(GROUPGOODS, 'goods_price=' . $price, $where);
        admin_log('', _EDITSTRING_ , ($edit_all ? '全部' . $goods_id : $parent_id . '_' . $goods_id) . "配件价格为{$price}");
    }    
    exit();
}



//删除分类下所有配件
function delete_peijian_all($cat_id,$keywords='') {
	$data = get_category_peijian($cat_id,$keywords='');
	delete_peijian($data);
}

//更新分类下所有配件排序
function update_category_peijian_order($cat_id,$order,$keywords='') {
	$keywords = $keywords;
	$data = get_category_peijian($cat_id,$keywords);
	if(!empty($order)) {
		update_peijian($data,$order);
	}
}

//更新配件
function update_peijian($data,$order) {
	global $db;
	if(is_array($data)) {
		foreach($data as $key=>$value) {
            list($parent_id, $goods_id) = explode('_', $value);
            $parent_id = intval($parent_id);
            $goods_id  = intval($goods_id);			
			$sql = 'UPDATE '. GROUPGOODS .' SET sort_order='. $order .' WHERE parent_id = '. $parent_id .' AND goods_id = '. $goods_id .'';
			$db->query($sql);
		}
	}
}

/**
 * 删除配件
 * @return void 无返回值
 */
function delete_peijian($data) {
    //$ids = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : '';
    if ($data) {
        global $db;        
        foreach ($data as $item) {
            list($parent_id, $goods_id) = explode('_', $item);
            $parent_id = intval($parent_id);
            $goods_id  = intval($goods_id);
            $db->delete(GROUPGOODS, "parent_id={$parent_id} AND goods_id={$goods_id}");
            $db->delete(CART, "main_goods_id={$parent_id} AND goods_id={$goods_id}");
        }        
        admin_log('', _DELSTRING_ , '配件' . $data);
    }    
}

/**
 * 删除配件
 *
 * @return void 无返回值
 */
function delete_peijian_singer() {
    $ids = isset($_POST['ids']) ? $_POST['ids'] : '';

    if ($ids) {
        global $db;

        $id_arr = explode(',', $ids);//主商品id_配件商品id,主商品id_配件商品id...

        foreach ($id_arr as $item) {
            list($parent_id, $goods_id) = explode('_', $item);
            $parent_id = intval($parent_id);
            $goods_id  = intval($goods_id);
            $db->delete(GROUPGOODS, "parent_id={$parent_id} AND goods_id={$goods_id}");
            $db->delete(CART, "main_goods_id={$parent_id} AND goods_id={$goods_id}");
        }

        admin_log('', _DELSTRING_ , '配件' . $ids);
    }

    exit();
}

//查询分类下所有配件数据
function get_category_peijian($cat_id,$keywords='') {
	global $db,$keywords;
    $table   = GROUPGOODS . ' AS a';
    $table  .= ' JOIN ' . GOODS . ' AS b ON b.goods_id=a.goods_id';
    $table  .= ' JOIN ' . GOODS . ' AS c ON c.goods_id=a.parent_id';
	if(!empty($cat_id)) {
		$cat_arr = explode(',',$cat_id);
		foreach($cat_arr as $key=>$value) {
			$cat_list .= "'" . $value . "',";
		}
		$cat_list = substr($cat_list,0,strlen($cat_list)-1);
		$where = ' WHERE c.cat_id IN(' . $cat_list . ')';
	}
	if(!empty($keywords)) {
		$where .= " AND b.goods_sn LIKE '". $keywords ."'";
	}
    $sql   = 'SELECT a.parent_id,a.goods_id,a.goods_price,a.sort_order,';
    $sql  .= 'b.shop_price,b.goods_sn,b.goods_title,b.goods_thumb,b.is_on_sale,';
    $sql  .= 'c.goods_sn AS main_goods_sn,c.goods_title AS main_goods_title,c.goods_thumb AS main_goods_thumb';
    $sql  .= ' FROM ' . $table . $where . ' ORDER BY a.parent_id,a.goods_id';	
	$res = $db->arrQuery($sql);	
	if($res) {
		foreach($res as $key=>$value) {
			$data[] = $value['parent_id'] . '_' . $value['goods_id'];
		}
	}
	return $data;	
}

/**
 * 获取配件 
 * 
 * @return void 无返回值
 */
function get_peijian_list() {
    global $Arr, $db;
    $Arr['column_arr'] = array(
	    'b.goods_title'   => '配件标题',
	    'b.goods_sn'      => '配件编码',
    	//'b.cat_id'        => '配件分类',
	    'c.goods_title'   => '主商品标题',
	    'c.goods_sn'      => '主商品编码',
    	//'c.cat_id'        => '主商品分类',
	);
    $keywords = !empty($_GET['keywords']) ? $_GET['keywords'] : '';
    $column  = !empty($_GET['column']) && array_key_exists($_GET['column'], $Arr['column_arr']) ? $_GET['column'] : '';
    $cat_id  = !empty($_GET['cat_id']) ? $_GET['cat_id'] : '';
	if (!empty($_REQUEST['cat_id'])){
		foreach($_REQUEST['cat_id'] as $key => $val){
			if($_REQUEST['cat_id'][$key] == '') unset($_REQUEST['cat_id'][$key]);
		}
	}
	if (!empty($_GET['cat_id'])){
		foreach($_GET['cat_id'] as $key => $val){
			if($_GET['cat_id'][$key] == '') unset($_GET['cat_id'][$key]);
		}
	}
    $cat_id = empty($_GET['cat_id']) ? 0 : intval(is_array($_GET['cat_id'])?end($_GET['cat_id']):$_GET['cat_id']);
    $_REQUEST['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval(is_array($_REQUEST['cat_id'])?end($_REQUEST['cat_id']):$_REQUEST['cat_id']);
    $cat_id_list =     str_replace("g","c",get_children($cat_id));
	//$Arr['orher_cat_list'] =     cat_list($cat_id);	
    $Arr['keywords'] = $keywords;
    $Arr['column'] = $column;
    $Arr['cat_id'] = $cat_id;    
    $where = '';    
    if ($cat_id_list) {//分类
        $Arr['cat_name'] = isset($_GET['cat_name']) ? stripslashes($_GET['cat_name']) : '';
        $where .= " {$cat_id_list}";
    }
    if ($column) {
        if ($keywords) {
            if (strpos($column, 'goods_title') === false) {//编码
                if (strpos($keywords, ',') === false) {//单个编码
                    $where .= " AND {$column} LIKE '{$keywords}%'";
                }
                else {
                    $keywords = trim($keywords, ', ');
                    $keywords = str_replace(' ', '', $keywords);
                    $keywords = "'" . str_replace(',', "','", $keywords) . "'";
                    $where  .= " AND {$column} IN({$keywords})";
                }
            }
            else {
                $where .= " AND {$column} LIKE '%{$keywords}%'";
            }
        }
    }    
    $where   = $where ? ' WHERE ' . $where : '';
    $table   = GROUPGOODS . ' AS a ';
    $table  .= ' JOIN ' . GOODS . ' AS b ON b.goods_id=a.goods_id ';
    $table  .= ' JOIN ' . GOODS . ' AS c ON c.goods_id=a.parent_id ';	
    $sql     = 'SELECT COUNT(a.goods_id) FROM ' . $table . $where;
    $count    = $db->getOne($sql);
	//echo $sql;
    if (!$count) {
        return;
    }
    $filter          = array('record_count' => $count);
    $filter          = page_and_size($filter);    //分页信息
    $page            = new page(array(
        'total'   => $count, 
        'perpage' => $filter['page_size']
        )
    );
	$Arr['pagestr']  = $page->show();
    $Arr['filter']   = $filter;
    $limit =  ' LIMIT ' . $filter['start'] . ',' . $filter['page_size'];    //sql limit
    $sql   = 'SELECT a.parent_id,a.goods_id,a.goods_price,a.sort_order,a.start_date,a.end_date,';
    $sql  .= 'b.shop_price,b.goods_sn,b.goods_title,b.goods_thumb,b.is_on_sale,b.goods_number,b.promote_price,b.promote_start_date,b.promote_end_date,';
    $sql  .= 'c.goods_sn AS main_goods_sn,c.goods_title AS main_goods_title,c.goods_thumb AS main_goods_thumb';
    $sql  .= ' FROM ' . $table . $where;
    $sql  .= ' ORDER BY a.add_date DESC ' . $limit;    
	//echo $sql;
    $data = $db->arrQuery($sql);
    foreach ($data as $key => $item) {
        $promote_price = bargain_price($item['promote_price'], $item['promote_start_date'], $item['promote_end_date']);
        $data[$key]['shop_price']  = $promote_price > 0 ? $promote_price : $item['shop_price'];
        $data[$key]['goods_thumb'] = get_image_path(false, $item['goods_thumb']); 
        $data[$key]['main_goods_thumb'] = get_image_path(false, $item['main_goods_thumb']);
		$data[$key]['start_date'] = date("m.d",$item['start_date']);
		$data[$key]['end_date'] = date("m.d",$item['end_date']);
    }
    $Arr['data'] = $data;
}//end get_peijian_list


//分类添加配件
function category_add_peijian(){
	global $db;
	$cat_id   = isset($_POST['cat_id']) ? map_int($_POST['cat_id']) : '';//分类id串
	$cat_id = explode(',',$cat_id);
    $good_id  = isset($_POST['goods_id']) ? array_map('intval', $_POST['goods_id']) : '';//配件商品id
    $price    = isset($_POST['price']) ? array_map('floatval', $_POST['price']) : '';//配件价格
    $goods_sn = isset($_POST['goods_sn']) ? $_POST['goods_sn'] : '';//配件编码
	$sort_order= isset($_POST['sort_order'])? array_map('intval',$_POST['sort_order']):0; //配件排序
	 $admin_id = $_SESSION['WebUserInfo']['said'];//管理员id
	foreach($cat_id as $row){
		foreach($price as $key=>$val){
			$sql="insert into eload_category_peijian (goods_id,price,cat_id,sort_order,admin_id) values($good_id[$key],$val,$row,$sort_order[$key],$admin_id)";
			$count=$db->getOne("select count(*) from eload_category_peijian where cat_id=$row and goods_id=$good_id[$key]");
			if(!$count){
				$db->query($sql);
			}
		}	
	}
	creat_peijian();
	$log      = '分类：' . var_export($cat_id, true) . ',配件：' . var_export($good_id, true);
	admin_log('', '批量' . _ADDSTRING_, '配件,' . $log);
	exit;
}

//分类配件列表
function category_peijian_list(){
	global $db;
	$typeArray =  read_static_cache('category_c_key',2);
	$cat_id  = !empty($_GET['cat_id']) ? $_GET['cat_id'] : '';
    $Arr['cat_id'] = $cat_id;   
    $where = '';    
    if ($cat_id) {//分类
        $where .= " where cat_id IN({$cat_id})";
    }
	$sql="select count(*) from eload_category_peijian ".$where." group by cat_id";
	$sum    = $db->arrQuery($sql);
	$count=count($sum);
	$filter          = array('record_count' => $count);
    $filter          = page_and_size($filter);    //分页信息
    $page            = new page(array(
        'total'   => $count, 
        'perpage' => $filter['page_size']
        )
    );
	$list['pagestr']  = $page->show();
    $list['filter']   = $filter;
    $limit =  ' LIMIT ' . $filter['start'] . ',' . $filter['page_size'];    //sql limit
    $sql   = 'SELECT cat_id from eload_category_peijian '.$where." group by cat_id ".$limit;
	$cat_id = $db->arrQuery($sql);
	$data=array();
	foreach ($cat_id as $key=>$row){
		$data[$key]['cat_id']=$row['cat_id'];
		$data[$key]['cat_name']=$typeArray[$row['cat_id']]['cat_name'];
		$data[$key]['peijian']=$db->arrQuery("select p.*,g.goods_sn from eload_category_peijian as p left join eload_goods as g on g.goods_id =p.goods_id  where p.cat_id = ".$row['cat_id']);
	}
	$list['data']=$data;
	return $list;
}

//删除分类
function del_category(){
	global $db;
	$cat_id=isset($_GET['cat_id'])?intval($_GET['cat_id']):0;
	if($cat_id){
		$sql="delete from eload_category_peijian where cat_id = $cat_id";
		$db->query($sql);
		creat_peijian();
		echo "<script>alert('删除成功')</script>";
	}else{	
		echo "<script>alert('删除失败')</script>";
	}
}

//删除分类下的一个配件
function del_category_peijian(){
	global $db;
	$cat_id = isset($_GET['cat_id'])?intval($_GET['cat_id']):0;
	$goods_id = isset($_GET['goods_id'])?intval($_GET['goods_id']):0;
	if($cat_id && $goods_id){
		$sql="delete from eload_category_peijian where goods_id=$goods_id and cat_id = $cat_id";
		$db->query($sql);
		creat_peijian();
		echo "<script>alert('删除成功')</script>";
	}else{	
		echo "<script>alert('删除失败')</script>";
	}	
}

//批量删除
function batch(){
	global $db;
	$category_ids = !empty($_REQUEST['checkboxes']) ? join(',', $_REQUEST['checkboxes']) : 0;
	if($category_ids){
		$sql="delete from eload_category_peijian where cat_id in ($category_ids)";
		$db->query($sql);
		creat_peijian();
		echo "<script>alert('删除成功')</script>";
		echo "<script>history.go(-1)</script>";
	}else{	
		echo "<script>alert('删除失败')</script>";
	}
}

//修改配件
function edit(){
	global $db;
	admin_priv('goods_list');
	$dataArr = explode('||',$_POST['id']);
	$goods_id   =intval($dataArr[0]);
	$cat_id =trim($dataArr[1]);

    $val  = explode("|",$_POST['value']);
	$price=trim($val[0]);
	$sort_order = intval($val[1]);
    $db->query("update eload_category_peijian set sort_order=$sort_order ,price=$price where cat_id=$cat_id and goods_id=$goods_id");
	creat_peijian();
    echo $price."|".$sort_order;
	exit();

} 
function creat_peijian() {
	global $db;
	$cate_peijian = $db->arrQuery("select * from eload_category_peijian");
	$peijian = array();
	if($cate_peijian){
		foreach($cate_peijian as $row){
			$peijian[$row['cat_id']][] = $row;		
		}
		write_static_cache('cat_peijian',$peijian,2);
	}
}
