<?php
/**
 * special_mgr.php      专题活动管理
 *
 * @author              mashanling(msl-138@163.com)
 * @date                2011-11-03
 * @last modify         2012-12-15 09:10:45 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/param.class.php');

define('SPECIAL_CHILDREN', 'special_positions');    //专题板块key值

admin_priv('special_mgr');    //检查权限

$Arr['no_records']     = '<span style="color: red">暂无记录！</span>';
$_ACT                  = Param::get('act');    //操作

switch ($_ACT) {
    case 'special_add':    //添加或编辑专题
        admin_priv('special_add');
        Special::addSpecial();
        break;

    case 'save_special':    //保存专题
        admin_priv('special_add');
        Special::saveSpecial();
        break;

    case 'delete_special':  //删除专题
        admin_priv('special_add');
        Special::deleteSpecial();
        break;

    case 'special_position_list':    //专题板块列表
        //admin_priv('special_position_list');
        $special_id = Param::get('special_id', 'int');
        $Arr['data'] = Special::specialList($special_id);
        break;

    case 'special_position_add':    //添加或编辑专题板块
        admin_priv('special_position_add');
        SpecialPosition::addPosition();
        break;

    case 'save_special_position':    //保存专题板块
        admin_priv('special_position_add');
        SpecialPosition::savePosition();
        break;

    case 'delete_special_position':  //删除专题
        admin_priv('special_position_add');
        SpecialPosition::deletePosition();
        break;

    case 'special_goods':    //专题商品列表
        admin_priv('special_goods_add');
        $special_id  = Param::get('special_id', 'int');
        $position_id = Param::get('position_id', 'int');
        SpecialGoods::goodsList($special_id, $position_id);
        break;

    case 'special_goods_add':    //添加或编辑专题商品
        admin_priv('special_goods_add');
        SpecialGoods::addGoods();
        break;

    case 'save_special_goods':    //保存专题商品
        admin_priv('special_goods_add');
        SpecialGoods::saveGoods();
        break;

    case 'delete_special_goods':    //删除专题商品
        admin_priv('special_goods_add');
        SpecialGoods::deleteGoods();
        break;
    case 'save_sort_order':   //保存排序
        SpecialGoods::saveSort();
        break;
    case 'batch':
         SpecialGoods::batch();
         break;
    default:    //专题列表
        admin_priv('special_list');    //权限
        $data = Special::specialList();
        $data && ($Arr['data'] = $data);
}

$_ACT = 'special/' . ($_ACT ? $_ACT : 'special_list');
temp_disp();

//专题管理类
class Special {

    /**
     * 专题列表
     *
     * @param int $special_id 专题id
     */
    static function specialList($special_id = 0) {
        $special_arr = read_static_cache('special_arr', 2);    //专题

        if (empty($special_arr)) {    //无专题
            return false;
        }
		
        if ($special_id) {    //指定专题

            if (!isset($special_arr[$special_id])) {    //指定专题不存在
                return false;
            }

            $special_arr = $special_arr[$special_id];
        }else{
			foreach($special_arr as $key=>$row){
				if(!empty($row['temp']) && $row['url']!="/promotion-".title_to_url($row['url'])."-special-".$key.".html"){
				
					$special_arr[$key]['url'] = "/promotion-".title_to_url($row['url'])."-special-".$key.".html";
				}
			}
		
		}

        return $special_arr;

    }

    /**
     * 添加或编辑专题
     *
     */
    static function addSpecial() {
        global $Arr;

        $special_id = Param::get('special_id', 'int');

        if ($special_id) {
            $Arr['data'] = self::specialList($special_id);
            $Arr['action'] = '编辑专题';
        }
        else {
            $Arr['action'] = '添加专题';
        }
    }

    /**
     * 保存专题
     *
     */
    static function saveSpecial() {
        global $db;

        $special_id = Param::post('special_id', 'int');
        $name       = Param::post('name');

        $result     = self::checkSpecialExists($special_id, $name);    //检查专题是否已经存在
		$temp       = Param::post('temp');							   //专题模板
		$banner     = Param::post('banner');						   //专题banner
        $result !== true && exit($result);

        $msg  = '';
        $data = array('name' => $name, 'memo' => Param::post('memo'), 'url' => Param::post('url'),'temp'=>$temp,'banner'=>$banner,'title'=>$_POST['title'],'keyword'=>$_POST['keyword'],'description'=>$_POST['description'],'remark'=>$_POST['remark']);

        if ($special_id) {    //编辑

    		if ($db->autoExecute(SPECIAL, $data, 'UPDATE', 'special_id=' . $special_id) !== false){
    			admin_log('', _EDITSTRING_, "专题id:  {$special_id}, 专题名称： {$name}");
    		}
    		else{
    		    $msg = '修改失败';
    		}

    	}
    	else {    //添加

    		if ($db->autoExecute(SPECIAL, $data) !== false) {
    			admin_log('', _ADDSTRING_, '专题: ' . $name);
    		}
    		else{
    		    $msg = '添加失败';
    		}

    	}

    	self::createSpecial();    //写专题缓存

        exit($msg);
    }//end saveSpecial

	/**
     * 删除主题
     *
     */
    static function deleteSpecial() {
        global $db;

        $special_id  = Param::post('special_id', 'int');
        $special_arr = self::specialList($special_id);

        if ($special_id && $special_arr) {
            $where = 'special_id=' . $special_id;
    		$db->delete(SPECIAL, $where);          //删除专题
    		$db->delete(SPECIAL_POSITION, $where); //删除板块
    		$db->delete(SPECIAL_GOODS, $where);    //删除专题商品
            admin_log('', _DELSTRING_, '专题 ：' . $special_arr['name']);

            self::createSpecial();
    		exit;
        }

        exit('删除失败');
    }

    /**
     * 判断专题是否存在
     *
     * @param int    $special_id 专题id
     * @param string $name       专题名称
     */
    private static function checkSpecialExists($special_id, $name) {
        $data = self::specialList();

        if (!$data) {    //无专题
            return true;
        }

        foreach ($data as $special) {

            //!$special_id 添加；($special_id && $special_id != $special['special_id'])编辑，id不相同
            if ((!$special_id || ($special_id && $special_id != $special['special_id'])) && !strcasecmp($name, $special['name'])) {
                return "专题 {$name} 已经存在";
            }
        }

        return true;
    }

    /**
     * 写专题缓存
     *
     */
    static function createSpecial() {
        global $db;

        $data = array();

        $special_arr = $db->select(SPECIAL, '*', '', 'special_id DESC');    //专题

        foreach ($special_arr as $special) {
            $special[SPECIAL_CHILDREN] = array();
            $special_id = $special['special_id'];
            $sql        = 'SELECT * FROM ' . SPECIAL_POSITION . ' WHERE special_id=' . $special_id . ' ORDER BY position_id DESC';

            $query      = $db->query($sql);    //板块

            while (($row = $db->fetchRow($query)) !== false) {
                $special[SPECIAL_CHILDREN][$row['position_id']] = $row;
            }

            $data[$special['special_id']] = $special;
        }

        write_static_cache('special_arr', $data, 2);
        file_put_contents(ROOT_PATH . 'temp/skin3/eload_admin/js/special_arr.js', "/**\n * special_arr.js        所有专题及其板块js，自动生成，请毋修改\n *\n * @author               mashanling(msl-138@163.com)\n * @last modify          " . local_date('Y-m-d H:i:s') . "\n */\nspecialArr = " . json_encode($data) . ';');
    }
}

//专题板块管理类
class SpecialPosition {

    /**
     * 添加或编辑板块
     *
     */
    static function addPosition() {
        global $Arr;

        $special_id   = Param::get('special_id', 'int');
        $position_id  = Param::get('position_id', 'int');
        $special_arr  = Special::specialList();    //专题
        $Arr['action']= '添加板块';
        $Arr['special_arr']  = $special_arr;
        $Arr['position_id']  = $position_id;

        if (!empty($special_arr[$special_id])) {
            $Arr['data'] = $special_arr[$special_id];

            !empty($Arr['data'][SPECIAL_CHILDREN][$position_id]) && ($Arr['action'] = '编辑板块');
        }
    }

    /**
     * 保存板块
     *
     */
    static function savePosition() {
        global $db;

        $special_id = Param::post('special_id', 'int');
        $position_id= Param::post('position_id', 'int');
        $name       = Param::post('name');
        $memo       = Param::post('memo');
        $url        = Param::post('url');
        $result     = self::checkPositionExists($special_id, $position_id, $name);    //判断板块是否存在
        $result !== true && exit($result);
		$goods_sn   = Param::post('goods_sn');


        $msg  = '';
        $data = array('name' => $name, 'memo' => $memo, 'special_id' => $special_id,'url'=>$url);
        if ($position_id) {    //编辑

    		if ($db->autoExecute(SPECIAL_POSITION, $data, 'UPDATE', 'position_id=' . $position_id) !== false){
    			admin_log('', _EDITSTRING_, "板块id:  {$position_id}, 板块名称： {$name}");
    		}
    		else{
    		    $msg = '修改失败';
    		}

    	}
    	else {    //添加

    		if ($db->autoExecute(SPECIAL_POSITION, $data) !== false) {
				$position_id = $db->insertId();
    			admin_log('', _ADDSTRING_, '板块: ' . $name);			
    		}
    		else{
    		    $msg = '添加失败';
    		}

    	}
		 if (!empty($goods_sn)&&strpos($goods_sn, ',')) {//批量添加
             self::batchAddgoods($special_id, $position_id, 999, $goods_sn);
        }

    	Special::createSpecial();    //写专题缓存

        exit($msg);
    }
	/*
	*	批量添加产品
	*/
	private static function batchAddgoods($special_id, $position_id, $sort_order, $goods_sn) {
        global $db;

        $goods_sn     = strtoupper($goods_sn);
        $goods_sn_arr = explode(',', $goods_sn);
        $goods_sn_arr = array_map('trim', $goods_sn_arr);
        $goods_arr    = array();
        $sql          = 'INSERT INTO ' . SPECIAL_GOODS . '(special_id,position_id,goods_id,sort_order) VALUES';

        $db->query('SELECT goods_id,goods_sn FROM ' . GOODS . " WHERE goods_sn IN('" . join("','", $goods_sn_arr) . "')");

        while ($row = $db->fetchArray()) {
            $goods_arr[$row['goods_id']] = strtoupper($row['goods_sn']);
            $sql .= "({$special_id},{$position_id},{$row['goods_id']},{$sort_order}),";
        }

        $diff = array_diff($goods_sn_arr, $goods_arr);

        $diff && exit('商品编码 ' . join(',', $diff) . ' 不存在');

        $added_arr = $db->getCol('SELECT g.goods_sn FROM ' . SPECIAL_GOODS . ' AS sg JOIN ' . GOODS . " AS g ON g.goods_id=sg.goods_id WHERE sg.goods_id IN(" . join(',', array_keys($goods_arr)) . ") AND sg.special_id={$special_id} AND sg.position_id={$position_id}");
        $added_arr && exit('商品编码 ' . join(',', $added_arr) . ' 已经存在该板块中');

        $db->query(substr($sql, 0, -1));

        admin_log('', _ADDSTRING_, '专题商品: ' . join("','", $goods_sn_arr));;

    }//end batchAdd

	/**
     * 删除板块
     *
     */
    static function deletePosition() {
        global $db;

        $special_id  = Param::post('special_id', 'int');
        $position_id = Param::post('position_id', 'int');
        $special_arr = Special::specialList($special_id);

        if (!empty($special_arr[SPECIAL_CHILDREN][$position_id])) {
            $where = 'position_id=' . $position_id;
    		$db->delete(SPECIAL_POSITION, $where); //删除板块
    		$db->delete(SPECIAL_GOODS, $where);    //删除板块商品
            admin_log('', _DELSTRING_, '板块 ：' . $special_arr[SPECIAL_CHILDREN][$position_id]['name']);

            Special::createSpecial();
    		exit;
        }

        exit('删除失败');
    }

    /**
     * 判断专题板块是否存在
     *
     * @param int    $special_id  专题id
     * @param int    $position_id 板块id
     * @param string $name        专题名称
     */
    private static function checkPositionExists($special_id, $position_id, $name) {
        $data = Special::specialList($special_id);

        if (!$data || empty($data[SPECIAL_CHILDREN])) {
            return true;
        }

        foreach ($data[SPECIAL_CHILDREN] as $position) {

            if ((!$position_id || ($position_id && $position_id != $position['position_id'])) && !strcasecmp($name, $position['name'])) {
                return "板块 {$name} 已经存在";
            }
        }

        return true;
    }
}

//专题商品类
class SpecialGoods {

    /**
     * 批量添加商品
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-13 09:42:27
     * @last modify  2012-11-03 09:14:08 by mashanling
     *
     * @param int    $special_id  专题id
     * @param int    $position_id 板块id
     * @param int    $sort_order  排序
     * @param string $goods_sn    商品编码
     *
     * @return void 无返回值
     */
    private static function batchAdd($special_id, $position_id, $sort_order, $goods_sn) {
        global $db;

        $goods_sn     = strtoupper($goods_sn);
        $goods_sn_arr = explode(',', $goods_sn);
        $goods_sn_arr = array_map('trim', $goods_sn_arr);
        $goods_arr    = array();
        $sql          = 'INSERT INTO ' . SPECIAL_GOODS . '(special_id,position_id,goods_id,sort_order) VALUES';

        $db->query('SELECT goods_id,goods_sn FROM ' . GOODS . " WHERE goods_sn IN('" . join("','", $goods_sn_arr) . "')");

        while ($row = $db->fetchArray()) {
            $goods_arr[$row['goods_id']] = strtoupper($row['goods_sn']);
            $sql .= "({$special_id},{$position_id},{$row['goods_id']},{$sort_order}),";
        }

        $diff = array_diff($goods_sn_arr, $goods_arr);

        $diff && exit('商品编码 ' . join(',', $diff) . ' 不存在');

        $added_arr = $db->getCol('SELECT g.goods_sn FROM ' . SPECIAL_GOODS . ' AS sg JOIN ' . GOODS . " AS g ON g.goods_id=sg.goods_id WHERE sg.goods_id IN(" . join(',', array_keys($goods_arr)) . ") AND sg.special_id={$special_id} AND sg.position_id={$position_id}");
        $added_arr && exit('商品编码 ' . join(',', $added_arr) . ' 已经存在该板块中');

        $db->query(substr($sql, 0, -1));

        admin_log('', _ADDSTRING_, '专题商品: ' . join("','", $goods_sn_arr));;

        exit;
    }//end batchAdd

    /**
     * 商品列表
     *
     * @param int   $special_id  专题id
     * @param int   $position_id 板块id
     * @param mixed $auto_id     自增id
     */
    static function goodsList($special_id, $position_id, $auto_id = false) {
        global $Arr, $db;

        $table = ' FROM ' . SPECIAL_GOODS . ' AS a JOIN ' . GOODS . ' AS b on a.goods_id=b.goods_id ';

        if ($auto_id !== false) {    //商品具体信息
            return $db->selectInfo("SELECT a.*,b.goods_sn {$table} WHERE a.auto_id={$auto_id}");
        }

        $where       = '';
        $special_arr = SPECIAL::specialList();
        $Arr['special_arr'] = $special_arr;

        if ($special_id) {
            $where = 'a.special_id=' . $special_id;
            $Arr['special'] = $special_arr[$special_id];

            if ($position_id) {
                $where .= ' AND a.position_id=' . $position_id;
                $Arr['position'] = $Arr['special'][SPECIAL_CHILDREN][$position_id];
            }
        }
		$is_on_sale = (isset($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale']!=='')?intval($_REQUEST['is_on_sale']):2;
		if($is_on_sale !==2){
			$where .= $where?(' AND b.is_on_sale=' . $is_on_sale):(' b.is_on_sale=' . $is_on_sale);
		}
		$Arr['is_on_sale'] = $is_on_sale;
		$is_promote = isset($_REQUEST['is_promote'])?intval($_REQUEST['is_promote']):2;
		if($is_promote !==2){
			$where .= $where?(' AND b.is_promote=' . $is_promote):(' b.is_promote=' . $is_promote);
		}
		$Arr['is_promote'] = $is_promote;
        $keyword   = Param::get('keyword');
	    $column    = Param::get('column');

	    if ($keyword != '') {
	        $where .= $where ? ' AND ' : '';

	        if ($column == 'b.goods_sn') {
	            if (strpos($keyword, ',') === false) {
	                $where .= "b.goods_sn LIKE '" . mysql_like_quote($keyword) . "%'";
	            }
	            else {
	                $k = preg_replace('/\s/','',$keyword);
                	$k = str_replace(',',"','",$keyword);
                	$k = "'{$k}'";
                	$where  .= "b.goods_sn IN({$k})";
	            }
	        }
	        else {
	            $where .= "b.goods_title LIKE '%" . mysql_like_quote($keyword) . "%'";
	        }
	    }
        $filter          = array();
        $where           = $where ? ' WHERE ' . $where : '';
        $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
        $filter['sort_by']          = empty($_REQUEST['sort_by']) ? '' : trim($_REQUEST['sort_by']);
        $filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $record_count    = $record_count > 0 ? $record_count : $db->getOne("SELECT COUNT(a.goods_id) {$table} {$where}");
        if(!empty($filter['sort_by'])){
            $order_by = $filter['sort_by'] . ' ' . $filter['sort_order'];
        }else{
            $order_by = " sort_order,a.auto_id DESC";
        }
        $Arr['position_id'] = $position_id;
        $Arr['special_id']  = $special_id;
        $Arr['keyword']     = $keyword;
        $Arr['column']      = $column;
        $Arr['column_arr']  = array(
    	    'b.goods_title'   => '商品标题',
    	    'b.goods_sn'      => '商品编码'
    	);

        if (!$record_count) {
            return;
        }
        $filter['record_count'] = $record_count;
        $filter          = page_and_size($filter);    //分页信息
        $page            = new page(array('total' => $record_count, 'perpage' => $filter['page_size'], 'url' => "special_mgr.php?act=special_goods&amp;record_cound={$record_count}&amp;special_id={$special_id}&amp;position_id={$position_id}&amp;is_on_sale={$is_on_sale}&amp;sort_by={$filter['sort_by']}&amp;is_promote={$is_promote}&amp;sort_order={$filter['sort_order']}"));
    	$Arr['pagestr']  = $page->show();
        if($filter['sort_order'] == 'DESC'){
            $filter['sort_order'] = 'ASC';
        }else{
             $filter['sort_order'] = 'DESC';
        }
		$title_url = '';

		foreach($_GET as $key => $val) {
			if ($key!='act' && $key!='goods_id') {
				if(is_array($_GET[$key])) {
					foreach($_GET[$key] as $row) {
						$title_url .= '&'.$key.'[]='.$row;
				  
					}
				}else {

					if ($key!='sort_by' && $key!='sort_order')
						$title_url .= '&'.$key.'='.$val;
				}
			}
		}
		$Arr['title_url'] = $title_url;
        $Arr['filter']   = $filter;
        $limit           = $filter['start'] . ',' . $filter['page_size'];    //sql limit

        $sql             = "SELECT a.*,b.goods_thumb,b.url_title,b.goods_number,b.goods_sn,b.goods_title,b.is_on_sale,b.is_promote,b.promote_price,b.point_rate,discount_rate,shop_price,promote_lv,promote_end_date {$table} {$where} ORDER BY {$order_by} LIMIT {$limit}";
        
		$db->query($sql);

        $data            = array();

        while($row = $db->fetchArray()) {
            $row['goods_url']  = get_details_link($row['goods_id'], $row['url_title']);
            $row['goods_thumb'] = get_image_path(false, $row['goods_thumb']);
            if($row['is_promote'] && $row['promote_price']>0){
                $row['is_promote'] = 1;
				$row['shop_price'] = $row['promote_price'];
				$row['promote_end_date'] = date('Y-m-d H:i:s',$row['promote_end_date']);
            }else{
                $row['is_promote'] = 0;
            }
            $data[] = $row;
        }

        $Arr['data']     = $data;
    }//end goodsList

    /**
     * 添加或编辑商品
     *
     */
    static function addGoods() {
        global $Arr;

        $auto_id      = Param::get('auto_id', 'int');
        $special_id   = Param::get('special_id', 'int');
        $position_id  = Param::get('position_id', 'int');
        $special_arr  = Special::specialList();    //专题
        $Arr['action']= '添加专题商品';
        $Arr['special_arr']  = $special_arr;
        $Arr['special_id']   = $special_id;
        $Arr['position_id']  = $position_id;
        $Arr['auto_id']      = $auto_id;

        if (isset($special_arr[$special_id])) {   //所属专题
            $Arr['special'] = $special_arr[$special_id];

            //所属板块
            !empty($Arr['special'][SPECIAL_CHILDREN][$position_id]) && ($Arr['position'] = $Arr['special'][SPECIAL_CHILDREN][$position_id]);
        }

        $data = self::goodsList(0, 0, $auto_id);
        if (!empty($data)) {
            $Arr['data']   = $data;
            $Arr['action'] = '编辑专题商品';
        }
    }
    static function creat_clear_sale_cache(){
        global $db;
       $sql = "SELECT goods_id FROM  eload_special_goods where special_id=50 AND position_id in (366,367,368,369,370,371,372,373,374,375,376,392,393,394)";
       $data = $db->arrQuery($sql);
       $goods_info = ',';
       if(!empty($data)){
           foreach($data as $row){
               $goods_info .= $row['goods_id'].',';
           }
           write_static_cache('clear_sale_goods',array($goods_info),1);
       }
    }
    /**
     * 保存商品
     *
     */
    static function saveGoods() {
        global $db;

        $auto_id    = Param::post('auto_id', 'int');
        $special_id = Param::post('special_id', 'int');
        $position_id= Param::post('position_id', 'int');
        $sort_order = Param::post('sort_order', 'int');
        $goods_sn   = Param::post('goods_sn');

        if (!$auto_id && strpos($goods_sn, ',')) {//批量添加
            return self::batchAdd($special_id, $position_id, $sort_order, $goods_sn);
        }

        $goods_id   = self::getGoodsId($goods_sn);


        !$goods_id  && exit("商品编码 {$goods_sn} 不存在");
        !self::checkGoodsExists($special_id, $position_id, $goods_id, $auto_id) && exit("商品编码 {$goods_sn} 已经存在该板块中");

        $msg  = '';
        $data = array('special_id' => $special_id, 'position_id' => $position_id, 'goods_id' => $goods_id, 'sort_order' => $sort_order);

        if ($auto_id) {    //编辑

    		if ($db->autoExecute(SPECIAL_GOODS, $data, 'UPDATE', 'auto_id=' . $auto_id) !== false){
    			admin_log('', _EDITSTRING_, "专题商品auto_id:  {$auto_id}, 商品编码： {$goods_sn}");
    		}
    		else{
    		    $msg = '修改失败';
    		}

    	}
    	else {    //添加

            if ($db->autoExecute(SPECIAL_GOODS, $data) !== false) {
                admin_log('', _ADDSTRING_, '专题商品: ' . $goods_sn);
                if($special_id == 50) { //清仓类添加产品更新缓存
                    self::creat_clear_sale_cache();
                }
            }
            else {
                $msg = '添加失败';
            }

        }
        
        exit($msg);
    }

	/**
     * 删除商品
     *
     */
    static function deleteGoods($auto_id='',$run=false) {
        global $db;
		if(empty($auto_id)){
			$auto_id = Param::post('auto_id');
			$auto_id = map_int($auto_id);
		}
        if ($auto_id) {
                $special_id = $db->getOne("select special_id from ".SPECIAL_GOODS." where auto_id IN({$auto_id})");
    		$db->delete(SPECIAL_GOODS, "auto_id IN({$auto_id})");    //删除商品
                admin_log('', _DELSTRING_, '专题商品id ：' . $auto_id);
                //删除清仓类产品更新缓存
                if($special_id == 50){
                    self::creat_clear_sale_cache();
                }
			if($run){
				return true;
			}
    		exit;
        }
        
        exit('删除失败');
    }

    /**
     * 判断专题商品是否存在
     *
     * @param int $special_id  专题id
     * @param int $position_id 板块id
     * @param int $goods_id    商品id
     * @param int $auto_id     自增id
     */
    private static function checkGoodsExists($special_id, $position_id, $goods_id, $auto_id) {
        $goods_info = $GLOBALS['db']->selectInfo('SELECT auto_id,goods_id FROM ' . SPECIAL_GOODS . " WHERE goods_id={$goods_id} AND position_id={$position_id}");

        if (!$goods_info) {
            return true;
        }

        return $auto_id && $goods_info['auto_id'] != $auto_id || !$auto_id ? false : true;
    }

    /**
     * 获取商品id
     *
     * @param string $goods_sn 商品编码
     */
    private static function getGoodsId($goods_sn) {
        $goods_id = $GLOBALS['db']->getOne('SELECT goods_id FROM ' . GOODS . " WHERE goods_sn='{$goods_sn}'");
        return $goods_id ? $goods_id : 0;
    }
    /*
     * 保存排序 2013-11-15 by lchen
     */
    static function saveSort(){
        $auto_id = Param::post('id');
        $auto_id = map_int($auto_id);
        $sort_order = Param::post('value');
        if ($auto_id) {
                $sql = "update ".SPECIAL_GOODS." set sort_order = '".$sort_order."' where auto_id = '".$auto_id."'";
    		$GLOBALS['db']->query($sql);    //更新排序
    		exit($sort_order);
        }

        exit('删除失败');
    }
    /*
     * 批量修改
     */
    static function batch() {
        global $db;
        require_once(ROOT_PATH.'lib/lib_goods.php');
        require_once(ROOT_PATH.'lib/syn_public_fun.php');
        $_TYPE = !empty($_POST['type'])?$_TYPE = $_POST['type']:'';
        $auto_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
        $goods_info = $db->arrQuery("select goods_id from ".SPECIAL_GOODS." where auto_id in (".$auto_id.")");
        $goods_ids = array();
        foreach($goods_info as $row){
            $goods_ids[]=$row['goods_id'];
        }
        $goods_id = join(',',$goods_ids);
        unset($goods_ids);
        $GoodsSnArrTemp = array();
	$GoodsSnArr = $db->arrQuery("select goods_sn from ".GOODS." WHERE goods_id " . db_create_in($goods_id));
	foreach($GoodsSnArr as $val){
		$GoodsSnArrTemp[] = $val['goods_sn'];
	}
	$goods_sn = implode('，',$GoodsSnArrTemp);
        if (isset($_TYPE)) {
            /*   批量删除 */
            if($_TYPE =='delete') {
                SpecialGoods::deleteGoods($auto_id,true);
                $BatchStr = '删除商品编号：'.$goods_sn;
            }
            /*设置积分比率*/
            elseif($_TYPE =='jifen_bilv') {
                $setpointrate = $_POST['point_rate'];
                update_goods($goods_id,'point_rate',$setpointrate);
                $BatchStr = '商品编号为：'.$goods_sn.'设置积分比例为'.$setpointrate;
            }
            //批量设置折扣率 fangxin 2013/10/29
            elseif($_TYPE == 'batch_discount_rate') {
                $discount_rate = $_POST['discount_rate'];
                $sql = "SELECT goods_id, shop_price, promote_price FROM ". GOODS ." WHERE goods_id IN(". $goods_id .")";
                $res = $db->arrQuery($sql);
                foreach($res as $key=>$value) {
                    if($value['promote_price'] > 0) {
                        $shop_price = $value['promote_price'];
                    } else {
                        $shop_price = $value['shop_price'];
                    }
                    if($discount_rate > 0 && $shop_price > 0) {
                        $market_price = price_format(($shop_price * 100)/(100-$discount_rate), 2);
                        $db->query("UPDATE ". GOODS ." SET market_price = ". $market_price .", discount_rate = ". $discount_rate ." WHERE goods_id = ". $value['goods_id'] ."");
                    }
                }
                $BatchStr = '商品编号为：'.$goods_sn.'设置折扣率'.$discount_rate;
            }

        /* 批量促销 */
            elseif ($_TYPE == 'batch_promote') {
			/* 记录日志 */
                $promote_rate = $_POST['promote_rate'] ? floatval($_POST['promote_rate']) : 0;
                $beginDate = $_POST['promote_start_date'] ? local_strtotime($_POST['promote_start_date']) : 0;
                $endDate = $_POST['promote_end_date'] ? local_strtotime($_POST['promote_end_date']) : 0;
                $sql = "SELECT * FROM " . GOODS . " WHERE goods_id ".db_create_in($goods_id);
                $goods = $db->arrQuery($sql);
                //规则：1.促销价要小于市场价;2.市场售价高于本店售价;3.商品导入的市场价为0时，设置此促销价后，将市场价更改为本店售价
                $updateManager = array(
                    'is_update'=>true,
                    'max_shop_price'=>0,
                    'errorMsg'=>array(),
                    'successMsg'=>array(),
                    'sql'=>''
                );
                foreach ($goods AS $key => $val) {
                //计算促销价格
                    $shop_price  = round(($val['chuhuo_price']/HUILV),2);//转成美元
                    $updateManager['max_shop_price'] = max($updateManager['max_shop_price'], $shop_price);
                    if($val['is_free_shipping'] == 1) {
                        $shipping_fee   = get_shipping_fee($shop_price, $val['goods_weight']);		//运费
                    }else {
                        $shipping_fee = 0;
                    }
                    $val['promote_price'] = empty($promote_rate) ? 0 : $shop_price*$promote_rate+$shipping_fee;		//商品促销价
                    $val['promote_price']	= round($val['promote_price'],2);
                    $updateFiled = "";
                    //如果设置了市场价则更新，否则按原来的市场价计算
                    $_POST['market_price'] *= 1; //转换数据类型,将0.00|0|null|false等 => 0
                    if (!empty($_POST['market_price'])) {
                        $updateFiled .= ",market_price='".$_POST['market_price']."'";
                        $market_price = $_POST['market_price'];
                    }else {
                        $market_price = $val['market_price'];
                    }
                    //促销价不能大于本店售价
                    if ($val['promote_price'] > $val['shop_price']) {
                        $updateManager['is_update'] = false;
                        $updateManager['errorMsg']["{$val['goods_sn']}"] = 'SKU:'.$val['goods_sn'].'&nbsp;&nbsp;促销价不能大于本店售价(促销价：'.$val['promote_price'].',本店售价：'.$val['shop_price'].')';
                    }
                    //市场价不能小于本店售价
                    if (!empty($_POST['market_price']) && $market_price < $val['shop_price']) {
                        $updateManager['is_update'] = false;
                        $errorStr = '&nbsp;&nbsp;市场价不能小于本店售价(市场价:'.$market_price.',本店售价:'.$val['shop_price'].')';
                        $updateManager['errorMsg']["{$val['goods_sn']}"] = empty($updateManager['errorMsg']["{$val['goods_sn']}"]) ? 'SKU:'.$val['goods_sn'].','.$errorStr : $updateManager['errorMsg']["{$val['goods_sn']}"].$errorStr;
                    }
                    //如果ERP导入产品的市场价为0时,设置此促销价后,将市场价更改为本店售价
                    if (empty($_POST['market_price']) && $market_price*1==0) {
                        $updateFiled .= ",market_price=shop_price";
                    }
                    //如果没有错误，则设置为更新成功的sql
                    if (empty($updateManager['errorMsg'][$val['goods_sn']])) {
                        $updateManager['successMsg']["{$val['goods_sn']}"] = 'SKU:'.$val['goods_sn'].'&nbsp;&nbsp;--可更新(ok)';
                        $promote_price = format_price($val['promote_price']); //修改促销价格 fangxin 2013/10/08
                        $updateManager['sql']["{$val['goods_sn']}"] = "UPDATE ".GOODS." SET is_promote='1',promote_price='".$promote_price."',promote_lv='$promote_rate',promote_start_date='$beginDate',promote_end_date='$endDate'".$updateFiled." WHERE goods_id='".$val['goods_id']."';";
                    }
                }
                //检查是否执行更新
                if ($updateManager['is_update'] == true) {
                    foreach ($updateManager['sql'] AS $key=>$val) {
                        $db->query($val);
                    }
                }else {
                    $msgDetail = '提示：该批商品的本店售价最大值-->'.$updateManager['max_shop_price'].'<br>';
                    foreach ($updateManager['successMsg'] AS $key=>$val) {
                        $msgDetail .= '<font color="#339933">'.$val.'</font><br>';
                    }
                    foreach ($updateManager['errorMsg'] AS $key=>$val) {
                        $msgDetail .= $val.'<br>';
                    }
                    sys_msg($msgDetail, 1, array(), false);
                }
                $BatchStr = '商品编号为：'.$goods_sn.'设置成促销商品,促销利润率为：'.$promote_rate.'，促销时间为：'.$_POST['promote_start_date'].'-->'.$_POST['promote_end_date'];
            }
            /* 记录日志 */
            admin_log('', '批量把', $BatchStr);
        }
        $link[0]["name"] = "返回上一页";
        $link[0]["url"] = $_SERVER["HTTP_REFERER"];
        sys_msg("批量操作成功", 0, $link);
    }
}
?>