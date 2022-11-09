<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/lib_goods.php');
require_once('lib/common.fun.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
include_once(ROOT_PATH.'config/language_cfg.php');
include(ROOT_PATH . 'languages/en/shopping_flow.php');
$_ACT = 'list';
$_ID  = '';
if (!empty($_REQUEST['act'])) $_ACT   = trim($_REQUEST['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
//多语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;
//商品分类列表
if ($_ACT == 'list')
{
    /* 获取分类列表 */
	    /* 权限检查 */
    admin_priv('catalog');
	$tree=array();
	$cid     = !empty($_GET['cid'])?intval($_GET['cid']):0;
	$padding = !empty($_GET['padding'])?intval($_GET['padding'])+ 10:3;
	$catArr = read_static_cache('category_c_key',2);//var_export(getChilds($catArr, 0));exit;
	$cat_priv= $_SESSION['WebUserInfo']['cat_priv'];//拥有的分类管理权限
	if(empty($cat_priv))$cat_priv='100000';
    $allow_cat_id='';
    if(!empty($cat_priv)){  //获取拥有的所有权限的所有字符串
        $priv_cat_big_arr = explode(',',$cat_priv);
		$category_children = read_static_cache('category_children', 2);    //顶级分类
        foreach ($priv_cat_big_arr as $k=>$v){
    		$allow_cat_id.=$v.",";//echo("<BR/>".$v.">>>>>");
    		if(!empty($category_children[$v]['children'])){
    			array_push($category_children[$v]['children'],$v);
    			$allow_cat_id.=implode(',',$category_children[$v]['children']).",";
    		}
        }
        $allow_cat_id.="0";
    }
    $allow_cat_id.='0';
	if(!empty($cat_priv)){  //过滤没有权限的分类
		$allow_cat_id_arr = explode(',',$allow_cat_id);
		foreach($catArr as $k=>$v){
			if(!in_array($v['cat_id'],$allow_cat_id_arr)){
				unset($catArr[$k]);
			}
		}
	}
	$cat_tree = array();
	foreach($catArr as $k=>$v)
	{
		if($v['parent_id'] == $cid){
			$cat_tree[$k] = $v;
			$cat_tree[$k]['_child'] = count(array_keys(cat_list($k,true)));
		}
	}
    unset($catArr);
	$Arr["catArr"] = $cat_tree;
	$Arr["padding"] = $padding;
}

/*------------------------------------------------------ */
//-- 添加商品分类
/*------------------------------------------------------ */
if ($_ACT == 'add')
{
    /* 权限检查 */
    //admin_priv('catalog_add');
	$tag_msg = "添加";
	$url = "?act=insert&id=$_ID";
    $cat_info["parent_id"] = 0;
	$parent_id = 0;
	$Arr['shipping_method'] = json_encode(read_static_cache('shipping_method', ADMIN_STATIC_CACHE_PATH));
    if($_ID!=''){
		$Arr["cat_select"] = '';
		$parent_id_str = get_parent_id($_ID);
		$parent_id_Arr = explode(',',$parent_id_str);
		$parent_id_Arr = array_reverse ($parent_id_Arr); //数组逆序
		foreach($parent_id_Arr as $key => $val){
			if ($val!=''){
				$parent_id = $val;
				$selectid  = isset($parent_id_Arr[$key+1])?$parent_id_Arr[$key+1]:'';
				$Arr["cat_select"] .=  get_lei_select($parent_id,'parent_id[]','','muli_cat',$selectid);
			}
		}

		$muti_lang_info =  $db->arrQuery("select * from ".CATALOG_LANG." where cat_id='$_ID'");
		$muti_lang_info = fetch_id($muti_lang_info,'lang');
		$Arr["muti_lang_info"] = $muti_lang_info;
		$tag_msg   = "修改";
		$sql       = "select * from ".CATALOG." where cat_id = $_ID";
		$cat_info  = $db->selectinfo($sql);
		$Arr["cat_info"]   =  $cat_info;
	}else{
		$Arr["cat_info"]   =  array('is_show' => 1, 'is_show_seo' => 1);
        $Arr["cat_select"] =   get_lei_select($parent_id,'parent_id[]','','muli_cat');
	}
	//属性查找模板
	$template_info_arr = read_static_cache('search_attr_template',2);
	$Arr["template_info_arr"] =  $template_info_arr;
    $Arr["url"] =  $url;
	$Arr["tag_msg"] =  $tag_msg;
	$Arr["langcfg"]= $langArr; //传递语言设置数组给模板变量
}

/*------------------------------------------------------ */
//-- 商品分类添加时的处理
/*------------------------------------------------------ */
if ($_ACT == 'insert')
{
    /* 权限检查 */
    admin_priv('catalog_add');
	require_once('../lib/cls_image.php');
	$image = new cls_image();
	if(!empty($_POST['shipping_method']))$cat['shipping_method'] = implode(',',$_POST['shipping_method']);    //配送方式
    //处理pid
	foreach($_POST['parent_id'] as $key => $val){
		if($_POST['parent_id'][$key] == '') unset($_POST['parent_id'][$key]);
	}
	$cat['parent_id'] = end($_POST['parent_id']);
    /* 初始化变量 */
    $cat['cat_name']        = !empty($_POST['cat_name'])     ? trim($_POST['cat_name'])     : '';
    $cat['cat_title']       = !empty($_POST['cat_title'])     ? trim($_POST['cat_title'])     : '';
    $cat['add_word']        = !empty($_POST['add_word'])   ? $_POST['add_word']: '';
    $cat['sort_order']      = !empty($_POST['sort_order'])   ? intval($_POST['sort_order']) : 0;
    $cat['hot_search']      = !empty($_POST['hot_search'])   ? trim($_POST['hot_search'])   : '';
    $cat['keywords']        = !empty($_POST['keywords'])     ? trim($_POST['keywords'])     : '';
    $cat['cat_desc']        = !empty($_POST['cat_desc'])     ? $_POST['cat_desc']           : '';
    $cat['cat_cont']        = !empty($_POST['cat_cont'])     ? $_POST['cat_cont']           : '';
    $cat['measure_unit']    = !empty($_POST['measure_unit']) ? trim($_POST['measure_unit']) : '';
    $cat['template_file']   = !empty($_POST['template_file'])? trim($_POST['template_file']): '';
    $cat['template_id']		= !empty($_POST['template_id'])     ? intval($_POST['template_id'])     : 0;			//属性查找模板ID
	if(!empty($cat['cat_cont'])) {
		$cat['cat_cont'] = format_html($cat['cat_cont']);
	}
    $cat['is_show']      = !empty($_POST['is_show'])      ? intval($_POST['is_show'])    : 0;
    $cat['is_show_seo']      = !empty($_POST['is_show_seo'])      ? intval($_POST['is_show_seo'])    : 0;
	$cat['is_home']      = !empty($_POST['is_home'])      ? intval($_POST['is_home'])    : 0;
    $cat['is_home_under'] = !empty($_POST['is_home_under'])      ? intval($_POST['is_home_under'])    : 0;
    $cat['is_dalei']      = !empty($_POST['is_dalei'])      ? intval($_POST['is_dalei'])    : 0;
    $cat['grade']        = !empty($_POST['grade'])        ? trim($_POST['grade'])      : 1;
    $cat['zhekou']       = !empty($_POST['zhekou'])       ? trim($_POST['zhekou'])       : 0;
    if(empty($cat['cat_name'])||empty($cat['zhekou'])){
    	sys_msg('分类信息不完整，请重新操作 ');
    }
	if(!empty($_ID))

   	/*更新以该目录id为父目录id下的所有相关商品的语言设置信息*/
	$clang_Arr = array();
	$clang_Arr = empty($_POST['langs']) ? array() : $_POST['langs'];
	$clang     = implode(",", $clang_Arr); // 形成语言集字符串
	$children  = get_children($_ID);
	$sql = "update  ".GOODS." as g  set `clang`='$clang'  where ".$children ;
	$db->query($sql);  //
	$sql2 = "update  ".CATALOG." as g  set `clang`='$clang'  where ".$children ;
	$db->query($sql2);
    $cat['is_show']       = !empty($_POST['is_show'])       ? intval($_POST['is_show'])    : 0;
    $cat['is_home']       = !empty($_POST['is_home'])       ? intval($_POST['is_home'])    : 0;
    $cat['is_home_under'] = !empty($_POST['is_home_under'])      ? intval($_POST['is_home_under'])    : 0;
    $cat['is_dalei']      = !empty($_POST['is_dalei'])      ? intval($_POST['is_dalei'])   : 0;
    $cat['grade']         = !empty($_POST['grade'])         ? trim($_POST['grade'])        : 1;
    $cat['zhekou']        = !empty($_POST['zhekou'])        ? trim($_POST['zhekou'])       : 0;
	$url_title = title_to_url($_POST['cat_name']);
	$url_title_temp = $url_title;
	$cat['cat_pic']       = !empty($_POST['cat_pic'])       ? trim($_POST['cat_pic'])      : '';
	$cat['cat_pic_small'] = !empty($_POST['cat_pic_small']) ? trim($_POST['cat_pic_small']): '';
    if ($cat['parent_id'] == '') $cat['parent_id'] == '0';
    if ($_ID == $cat['parent_id']){
       $links[] = array('name' => "返回修改", 'url' => 'javascript:history.back(-1)');
       $msg = "修改失败，你不能把自己指定为自己的子类！";
    }
    if(strlen($cat['grade']) > 80){
        /* 价格区间数超过范围 */
       $links[] = array('text' => "返回修改", 'href' => 'javascript:history.back(-1)');
       $msg = "添加失败，价格区间数超过范围";
    }

    /* 入库的操作 */
	$catArr_parr = read_static_cache('category_c_key',2);
	if ($cat['parent_id'] == 0) {    //顶级分类
	    $cat['level'] = 1;
	    $cat['node']  = '';
	}
	else {
	    $cat['level'] = count($_POST['parent_id']) + 1;
	    $cat['node']  = $catArr_parr[$cat['parent_id']]['node'] . ',';
	}
	if ($_ID!=''){
	    $_cat_arr     = $catArr_parr[$_ID];
        $children = isset($children) ? $children : get_children($_ID);
        if (isset($cat['is_show_seo']) && $cat['is_show_seo'] != $_cat_arr['is_show_seo']) {//前台不显示可访问继承 by mashanling on 2013-03-18 15:50:12
            $db->update(CATALOG . ' AS g', 'is_show_seo=' . $cat['is_show_seo'], $children);
        }
        if ($cat['shipping_method'] != $_cat_arr['shipping_method']) {//运输方式继承 by mashanling on 2013-03-18 15:55:18
            $db->update(CATALOG . ' AS g', "shipping_method='{$cat['shipping_method']}'", $children);
        }
	    $cat['node'] .= $_ID;
	    /*
	     * 所属分类不相同，修改其下子类节点及层级
	     * 如将
	     * cat_id level node
	     * 1      1     1
	     * 2      2     1,2
	     * 10     2     1,10
	     * 11     3     1,10,11
	     *
	     * cat_id=10移到cat_id=2下，新level,node关系为
	     *
	     * cat_id level node
	     * 1      1     1
	     * 2      2     1,2
	     * 10     3     1,2,10
	     * 11     4     1,2,10,11
	     */
	    if ($cat['parent_id'] != $_cat_arr['parent_id']) {
	        $sql = 'UPDATE ' . CATALOG . " SET level=level+{$cat['level']}-{$_cat_arr['level']}, node=replace(node, '{$_cat_arr['node']},', '{$cat['node']},') WHERE node LIKE '{$_cat_arr['node']}%'";
	        $db->query($sql);
	    }
	}
	if ($_ID!=''){
		$db->autoExecute(CATALOG, $cat,'UPDATE'," cat_id = $_ID");
		$msg = "修改成功！";
        admin_log('', _EDITSTRING_, '商品分类 '.$_POST['cat_name']);   // 记录管理员操作
        /*添加链接*/
		$pid = $catArr_parr[$_ID]['parent_id'];
        $links[0]['name'] = "返回同级数分类列表";
        $links[0]['url'] = 'category.php?cid='.$pid;
        $links[1]['name'] = "返回分类列表";
        $links[1]['url'] = 'category.php?act=list';
        $links[2]['name'] = "还需要修改";
        $links[2]['url'] = 'category.php?act=add&id='.$_ID;
	}else{
		$db->autoExecute(CATALOG, $cat);
		$new_cat_id=$db->insertId();
		if($new_cat_id){
			$c_arr['url_title'] =$url_title."-c-$new_cat_id/";
			$db->autoExecute(CATALOG,$c_arr,'update',"cat_id=$new_cat_id");
		}
		$msg = "添加成功！";
        /*添加链接*/
		$_ID = $db->insertId();
		$pid = empty($catArr_parr[$_ID]['parent_id'])?0:$catArr_parr[$_ID]['parent_id'];
        admin_log('', _ADDSTRING_, '商品分类 '.$_POST['cat_name']);   // 记录管理员操作
        $links[0]['name'] = "返回继续添加";
        $links[0]['url'] = 'category.php?act=add';
        $links[1]['name'] = "返回同级数分类列表";
        $links[1]['url'] = 'category.php?cid='.$pid;
        $links[2]['name'] = "返回分类列表";
        $links[2]['url'] = 'category.php?act=list';
    }

    if ($_FILES['cat_pic']['tmp_name'] != '' && $_FILES['cat_pic']['tmp_name'] != 'none')
    {
		$cat_pic = reformat_image_name('goods_mid', $_ID, $cat['cat_pic'], 'cat-img');
		if ($cat_pic !== false)
		{
			$db->query("UPDATE " . CATALOG . " SET cat_pic = '$cat_pic' WHERE cat_id='$_ID'");
		}
	}

    if ($_FILES['cat_pic_small']['tmp_name'] != '' && $_FILES['cat_pic_small']['tmp_name'] != 'none')
    {
		$cat_pic_small = reformat_image_name('goods_thumb', $_ID, $cat['cat_pic_small'], 'cat-img');
		if ($cat_pic_small !== false)
		{
			$db->query("UPDATE " . CATALOG . " SET cat_pic_small = '$cat_pic_small' WHERE cat_id='$_ID'");
		}
	}

	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
	$tree = array();//生成分类树，不可少
	creat_category();
	createDynamicTreeSub(0,'en');
	createDynamicTreeSub(0,'fr');
	createDynamicTreeSub(0,'ru');
	createDynamicTreeSub(0,'es');
	createDynamicGoodsCategory(0,'en');
	createDynamicGoodsCategory(0,'fr');
	createDynamicGoodsCategory(0,'ru');
	createDynamicGoodsCategory(0,'es');
}


/*------------------------------------------------------ */
//-- 批量转移商品分类页面
/*------------------------------------------------------ */
if ($_ACT == 'move')
{
    /* 权限检查 */
    admin_priv('catalog_add');
    $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
    $Arr['cat_select'] = cat_list($cat_id);
}

/*------------------------------------------------------ */
//-- 处理批量转移商品分类的处理程序
/*------------------------------------------------------ */
if ($_ACT == 'move_cat')
{
    /* 权限检查 */
    admin_priv('catalog_add');
    $cat_id        = !empty($_POST['cat_id'])        ? intval($_POST['cat_id'])        : 0;
    $target_cat_id = !empty($_POST['target_cat_id']) ? intval($_POST['target_cat_id']) : 0;

    /* 商品分类不允许为空 */
    if ($cat_id == 0 || $target_cat_id == 0)
    {
        $link[] = array('name' => "返回转移商品分类", 'url' => 'category.php?act=move');
        sys_msg('商品分类不允许为空！', 0, $link);
    }

    /* 更新商品分类 */
    $sql = "UPDATE " .GOODS. " SET cat_id = '$target_cat_id' ".
           "WHERE cat_id = '$cat_id'";
    if ($db->query($sql))
    {
        /* 提示信息 */
        $link[] = array('name' => "返回商品分类", 'url' => 'category.php?act=list');
        sys_msg("转移成功！", 0, $link);
    }
}


/*------------------------------------------------------ */
//-- 批量依据分类修改商品价格
/*------------------------------------------------------ */
if ($_ACT == 'up_cat_price')
{
    /* 权限检查 */
    admin_priv('catalog_add');
	$val = array();
	$page     = !empty($_GET['page']) ? intval($_GET['page']) : 1;
	$val['model']     = !empty($_GET['model']) ? intval($_GET['model']) : 0;
	$val['baifenshu'] = !empty($_GET['baifenshu']) ? floatval($_GET['baifenshu']) : 0;
	$val['cat_id']    = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
	$val['leixing']    = !empty($_GET['leixing']) ? intval($_GET['leixing']) : 0;
	$children = get_children($val['cat_id']);
	$pernum = 10;
	$total_record = $db->getOne("SELECT count(*) FROM " . GOODS ." as g where ".$children);
	$total_page   = ceil($total_record/$pernum);                                    //zong ye shu
	$start        = ($page - 1) * $pernum;
	if($page>$total_page){
		echo "$total_record 全部完成 ";
		exit;
	}else{
		echo "总计：".$total_record." 当前第 $page 页，已经处理了$start 个产品。 <br>";
	}
	$sql = "select g.goods_id,g.shop_price,g.market_price from ".GOODS." as g where ".$children ." LIMIT $start ,$pernum ";
	$goods_arr = $db->arrQuery($sql);
	foreach($goods_arr as $gv){
		if($val['model'] == '1' ){
			if($val['leixing']==1){
				$vv = "(volume_price * (".(1 + $val['baifenshu']*0.01)."))";
			}elseif($val['leixing']==2){
				$vv = "(volume_price  + ".$val['baifenshu']." )";
			}
			$sql = "update ".VPRICE." set volume_price = $vv where goods_id = '".$gv['goods_id']."' ";
			$db->query($sql);

			//修改阶段价格 fangxin 2013/10/08
			$sql_vprice = "SELECT * FROM ". VPRICE ." WHERE goods_id = ". $gv['goods_id'] ." AND volume_number = 1 LIMIT 1";
			if($res = $db->arrQuery($sql_vprice)) {
				$volume_price = format_price($res[0]['volume_price']);
				$sql_p = "UPDATE ".VPRICE." SET volume_price = $volume_price WHERE goods_id = '".$gv['goods_id']."' AND volume_number = 1";
				$db->query($sql_p);
			}
		}elseif($val['model'] == '2' ){
			if($val['leixing']==1){
				$vv = "(volume_price * (".(1 - $val['baifenshu']*0.01)."))";
			}elseif($val['leixing']==2){
				$vv = "(volume_price  - ".$val['baifenshu']." )";
			}
			$sql = "update ".VPRICE." set volume_price = $vv  where goods_id = '".$gv['goods_id']."' ";
			$db->query($sql);

			//修改阶段价格 fangxin 2013/10/08
			$sql_vprice = "SELECT * FROM ". VPRICE ." WHERE goods_id = ". $gv['goods_id'] ." AND volume_number = 1 LIMIT 1";
			if($res = $db->arrQuery($sql_vprice)) {
				$volume_price = format_price($res[0]['volume_price']);
				$sql_p = "UPDATE ".VPRICE." SET volume_price = $volume_price WHERE goods_id = '".$gv['goods_id']."' AND volume_number = 1";
				$db->query($sql_p);
			}
		}

		if($val['model'] == '1' ){
			if($val['leixing']==1){
				$vv = " g.shop_price = (g.shop_price * (".(1 + $val['baifenshu']*0.01)."))";
			}elseif($val['leixing']==2){
				$vv = " g.shop_price = (g.shop_price + ".$val['baifenshu'].")";
			}
			$sql = "update ".GOODS." as g  set $vv where g.goods_id = '".$gv['goods_id']."' ";
			$db->query($sql);

			//修改阶段价格 fangxin 2013/10/08
			$sql_shop_price = "SELECT goods_id, shop_price, promote_price FROM ". GOODS ." WHERE goods_id = ". $gv['goods_id'] ." LIMIT 1";
			if($res = $db->arrQuery($sql_shop_price)) {
				$shop_price = format_price($res[0]['shop_price']);
				$promote_price = format_price($res[0]['promote_price']);
				$sql_p = "UPDATE ".GOODS." SET shop_price = $shop_price, promote_price = $promote_price WHERE goods_id = '".$gv['goods_id']."'";
				$db->query($sql_p);
			}
		}else{
			if($val['leixing']==1){
				$vv = " g.shop_price = (g.shop_price * (".(1 - $val['baifenshu']*0.01)."))";
			}elseif($val['leixing']==2){
				$vv = " g.shop_price = (g.shop_price - ".$val['baifenshu'].")";
			}
			$sql = "update ".GOODS." as g  set $vv where g.goods_id = '".$gv['goods_id']."' ";
			$db->query($sql);

			//修改阶段价格 fangxin 2013/10/08
			$sql_shop_price = "SELECT goods_id, shop_price, promote_price FROM ". GOODS ." WHERE goods_id = ". $gv['goods_id'] ." LIMIT 1";
			if($res = $db->arrQuery($sql_shop_price)) {
				$shop_price = format_price($res[0]['shop_price']);
				$promote_price = format_price($res[0]['promote_price']);
				$sql_p = "UPDATE ".GOODS." SET shop_price = $shop_price, promote_price = $promote_price WHERE goods_id = '".$gv['goods_id']."'";
				$db->query($sql_p);
			}
		}
	}
	//if(){
		/* 提示信息 */
		//$typeArray =  read_static_cache('category_c_key',2);
		//$fangshi = ($val['model'] == '1' )?'增加':'减少';
        //admin_log('', _EDITSTRING_,'商品分类'.$typeArray[$val['cat_id']]['cat_name'].'下的商品价格，修改成'.$fangshi.$val['baifenshu'].'%' );  //操作记录
		//$link[] = array('name' => "返回商品分类", 'url' => 'category.php?act=list');
		$page++;
		echo "<META HTTP-EQUIV='Refresh' Content='1;URL=category.php?act=up_cat_price&cat_id=$val[cat_id]&model=$val[model]&leixing=$val[leixing]&baifenshu=$val[baifenshu]&page=$page'>";
		exit;
		//sys_msg("价格修改成功！", 0, $link);
	//}
}

/*------------------------------------------------------ */
//-- 批量依据分类修改商品价格
/*------------------------------------------------------ */
if ($_ACT == 'modify_cat_price')
{
    /* 权限检查 */
    admin_priv('catalog_add');
    $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
    $Arr['cat_select'] = cat_list($cat_id);
}


/*------------------------------------------------------ */
//-- 产品列表转移类
/*------------------------------------------------------ */
if ($_ACT == 'get_target_cat_list')
{
    $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : '';
	if($cat_id!=''){
		echo get_lei_select($cat_id,'target_cat[]','cat_id','OtherCat','','','请选择分类');
	}
	exit;
}


/*------------------------------------------------------ */
//-- ajax取得主分类
/*------------------------------------------------------ */
if ($_ACT == 'get_child_list')
{
    $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : '';
	if($cat_id!=''){
		echo get_lei_select($cat_id,'parent_id[]','','muli_cat');
	}
	exit;
}


/*------------------------------------------------------ */
//-- 添加产品ajax取得主分类
/*------------------------------------------------------ */
if ($_ACT == 'get_goods_child_list')
{
    $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : '';
	if($cat_id!=''){
		echo get_lei_select($cat_id,'cat_id[]','','goods_cat');
	}
	exit;
}




/*------------------------------------------------------ */
//-- 添加产品时取得扩展分类的子类
/*------------------------------------------------------ */
if ($_ACT == 'get_ext_child_list')
{
    $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : '';
    $n = isset($_GET['n']) ? intval($_GET['n']) : '';
	if($cat_id!=''){
		echo get_lei_select($cat_id,'other_cat['.$n.'][]','','OtherCat','',$n);
	}
	exit;
}

/*------------------------------------------------------ */
//-- 添加产品时点击增加扩展顶级类
/*------------------------------------------------------ */
if ($_ACT == 'get_ext_parent_list')
{
    $n = isset($_GET['n']) ? intval($_GET['n']) : '';
	echo get_lei_select('0','other_cat['.$n.'][]','','OtherCat','',$n);
	exit;
}




/*------------------------------------------------------ */
//-- ajax修改商品分类
/*------------------------------------------------------ */
elseif ($_ACT == 'editinplace')
{
    admin_priv('catalog');
	$dataArr = explode('||',$_POST['id']);
    $cat_id    = intval($dataArr[0]);
    $cat_field = trim($dataArr[1]);
    $val       = trim($_POST['value']);

    if(strlen($val)<1 ||empty($cat_field) ||empty($cat_id)){
    	sys_msg('分类信息不完整，请重新操作 ');
    }

	$whrstr = '';
	if ($cat_field == 'sort_order'){//修改排序值的时候，不改子类排序
		$whrstr = " cat_id = '".$cat_id."'";
	}else{
		$whrstr =  get_children($cat_id,'');
	}
	//修改商品价格
	//$sql = "select g.goods_id,g.shop_price,g.market_price from ".GOODS." as g where ".$whrstr;
	//$goods_arr = $db->arrQuery($sql);
    $db->update(CATALOG," $cat_field = '$val'", "  $whrstr ");
    admin_log('', _EDITSTRING_,'商品分类ID为 '.$cat_id );
	creat_category();
    echo $val;
	exit();
}

/*------------------------------------------------------ */
//-- ajax修改商品分类
/*------------------------------------------------------ */
elseif ($_ACT == 'edit_zhekou')
{
    admin_priv('catalog');
	$dataArr    = explode('||',$_POST['id']);
    $cat_id     = intval($dataArr[0]);
    $cat_field  = trim($dataArr[1]);
    $cat_name_y = '';
    $val        = trim($_POST['value']);
	$catArr = read_static_cache('category_c_key',2);
    if(strlen($val)<1 ||empty($cat_field) ||empty($cat_id)){
    	sys_msg('分类信息不完整，请重新操作 ');
    }

	if ($val != $catArr[$cat_id]['zhekou']){
		$val   = str_replace("\n",'<BR>',$val);
		$g_arr =  explode('|',$val);
		$cat_name_y = $catArr[$cat_id]['cat_name'];
		$yuan_grade = $catArr[$cat_id]['zhekou'];
		$children = get_children($cat_id,'');
		//修改商品价格

		$db->update(CATALOG," $cat_field = '$val'", "  $children ");
		admin_log('', _EDITSTRING_,$cat_name_y.' 原：'.$yuan_grade.',改为'.$val.'，分类ID为 '.$cat_id );
		creat_category();
	}
	echo $val;
	exit();

}



elseif ($_ACT == 'replace_html'){
	$dataArr    = explode('||',$_REQUEST['id']);
    $cat_id     = intval($dataArr[0]);
    $cat_field  = trim($dataArr[1]);
    $cat_name_y = '';
   // $val        = trim($_REQUEST['value']);
	//print_r($_REQUEST);
	//echo $_POST['id'];
	if($cat_id){
		$val = $db->getOne('select '.$cat_field.' from '.CATALOG." where cat_id = '".$cat_id."'");
	}
	echo str_replace('<BR>',chr(13),trim($val));
	//echo $val;
	exit;


}

elseif ($_ACT == 'update_qujian_price'){


	echo '<style>html{font-size:12px;}</style><script>function Ok(){window.parent.JqueryDialog.SubmitCompleted("", true,false);}</script><body onload="scroll(0,document.body.scrollHeight) ">';
    flush();
    $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
	$catArr = read_static_cache('category_c_key',2);

	$zhekouArr        = explode('<BR>',strtoupper($catArr[$cat_id]['zhekou']));
	$chuhuo_qujianArr = explode('<BR>',strtoupper($catArr[$cat_id]['chuhuo_qujian']));
  	$zhuijia_priceArr = explode('<BR>',strtoupper($catArr[$cat_id]['zhuijia_price']));

	//echo count($zhekouArr).'<br>';
	//echo count($chuhuo_qujianArr).'<br>';
	//echo count($zhuijia_priceArr).'<br>';

	if((count($zhekouArr) != count($chuhuo_qujianArr)) || (count($chuhuo_qujianArr) != count($zhuijia_priceArr))){
		echo '分级价格配置异常，程序终止。请检查配置再执行程序。';
		exit;
	}


    $fenji            = $catArr[$cat_id]['grade'];
    $children         = get_children($cat_id,'');


	foreach($chuhuo_qujianArr as $key => $val){
		$ynum = 0;
		$wnum = 0;
		$priceArr = explode('-',$val);
		$rate     = explode('|',$zhekouArr[$key]);
		$sql = "select chuhuo_price,shop_price,goods_weight,goods_sn,goods_id,is_free_shipping from ".GOODS." WHERE chuhuo_price >= '".$priceArr[0]."' and chuhuo_price <= '".$priceArr[1]."' and shop_price <> '0.01'  and  $children ";
		$goodsArr = $db->arrQuery($sql);
		foreach($goodsArr as $grow){
            $is_free_shipping = $grow['is_free_shipping'];
			$goods_id   = $grow['goods_id'];
			$shop_price = $grow['shop_price'];
			$chuhuo_price  = round(($grow['chuhuo_price']/HUILV),2);
			$zhui_price    = round(($zhuijia_priceArr[$key]/HUILV),2);
			$shipping_fee = get_shipping_fee($chuhuo_price,$grow['goods_weight']);

			//出货价×价格比例+价格追加[+运费]
			$xin_price = $chuhuo_price*$rate[0];
			$xin_price = round($xin_price,2);
            if($is_free_shipping==1)//如果是免运费产品 ，则加默认的平邮运费
                $xiaoshou_price = $xin_price  + $shipping_fee  + $zhui_price;
            else{
			    $xiaoshou_price = $xin_price + $zhui_price;
			    $shipping_fee = 0;
            }
			//if($shop_price.'' == $xiaoshou_price.''){
			//	$wnum++;
			//	echo  '&nbsp;&nbsp;'.$grow['goods_sn'] .'更新前后价格相同，<font color="#ff0000">产品销售价未更新</font><br>';
			//}else{
				$market_price = get_market_price($xiaoshou_price);
				$xiaoshou_price = format_price($xiaoshou_price); //修改销售价 fangxin 2013/10/08
				$sql = " update ".GOODS." set  shop_price = '$xiaoshou_price',shipping_fee = '$shipping_fee' where goods_id = '".$grow['goods_id']."' ";
				$db->query($sql);
				$_POST = array();
				$fenjiArr = explode('|',$fenji);
				$_POST['volume_number'][] = $fenjiArr[0];
				$_POST['volume_number'][] = $fenjiArr[1];
				$_POST['volume_number'][] = $fenjiArr[2];
				$_POST['volume_number'][] = $fenjiArr[3];
				$_POST['volume_price'][]  = format_price($xin_price + $shipping_fee  + $zhui_price); //修改优惠价格，只修改价梯为1价格 fangxin 2013/10/08
				$_POST['volume_price'][]  = round(($xin_price*$rate[1])/$rate[0],2) + $shipping_fee  + $zhui_price;
				$_POST['volume_price'][]  = round(($xin_price*$rate[2])/$rate[0],2) + $shipping_fee  + $zhui_price;
				$_POST['volume_price'][]  = round(($xin_price*$rate[3])/$rate[0],2) + $shipping_fee  + $zhui_price;
				if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
				{
					$temp_num = array_count_values($_POST['volume_number']);
					foreach($temp_num as $v)
					{
						if ($v > 1)
						{
							sys_msg("优惠数量重复！", 1, array(), false);
							break;
						}
					}
					handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
				}
				$ynum++;
				echo '&nbsp;&nbsp;'. $grow['goods_sn'] .'<font color="#7FA139">产品销售价更新成功</font><br>';
			//}
			flush();
			///echo $shop_price.' '.$chuhuo_price.' '.$grow['goods_weight'].' '.$shipping_fee.' '.$xiaoshou_price.'<br>';
		}
		$goodsNum = count($goodsArr);
		if ($goodsNum>0){
			$ymsg = '';
			$wmsg = '';
			if ($ynum>0) $ymsg = '共更新了'.$ynum.'个产品。';
			if ($wnum>0) $wmsg = ''.$wnum.'个产品未更新。';
		    echo '出货价格段：'.$val.'，分类'.$catArr[$cat_id]['cat_name'].','.$ymsg.$wmsg.'<br>';
		}else{
			echo '分类'.$catArr[$cat_id]['cat_name'].'出货价格段：'.$val.'无产品<br>';
		}
		flush();
	}
	exit;
}

/*		$sql = "select g.goods_id,g.shop_price,g.market_price,g.cat_id from ".GOODS." as g where ".$children;
		$goods_arr = $db->arrQuery($sql);
		foreach($goods_arr as $gv){
			$_POST = array();
			$yuan_g     = array();
			$cat_id     = $gv['cat_id'];
			$goods_id   = $gv['goods_id'];
			$yuan_grade = $catArr[$cat_id]['zhekou'];
			$yuan_g     =  explode('|',$yuan_grade);

			$first_price = ($g_arr[0] * $gv['shop_price'] )  / $yuan_g[0];

			$first_price = round($first_price,2);
			$market_price = round(($first_price * 1.3 + (rand(0,10) * $first_price * 0.1 )/2) ,2);
			$sql = " update ".GOODS." set market_price = '$market_price',shop_price = '$first_price'  where goods_id = '".$goods_id ."' ";
			$db->query($sql);


			$_POST['volume_number'][] = '1';
			$_POST['volume_number'][] = '2 ---- 9';
			$_POST['volume_number'][] = '10 ---- 49';
			$_POST['volume_number'][] = '50 ----- max';

			$_POST['volume_price'][]  = $first_price;
			$_POST['volume_price'][]  = round(($first_price*$g_arr[1])/$g_arr[0],2);
			$_POST['volume_price'][]  = round(($first_price*$g_arr[2])/$g_arr[0],2);
			$_POST['volume_price'][]  = round(($first_price*$g_arr[3])/$g_arr[0],2);

			if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
			{
				$temp_num = array_count_values($_POST['volume_number']);
				foreach($temp_num as $v)
				{
					if ($v > 1){
					sys_msg("优惠数量重复！", 1, array(), false);
					break;
					}
				}
				handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
			}
*/


/*------------------------------------------------------ */
//-- 保存分类多语言信息
/*------------------------------------------------------ */
if ($_ACT == 'mutil_lang_save'){
	$lang = empty($_POST['lang'])?'':$_POST['lang'];
	$cat_id = empty($_POST['cat_id'])?0:$_POST['cat_id'];
	if(empty($lang)){
		sys_msg('没有找到当前语种');
	}
	if(empty($cat_id)){
		sys_msg('没有找到当前分类');
	}
	$_update = $_POST;
	unset($_update['cat_id']);
	unset($_update['act']);
	if(!empty($_POST['cat_cont'])) {
		$_update['cat_cont'] = format_html($_POST['cat_cont']);
	}
	$db->autoReplace(CATALOG_LANG, $_POST,$_update);
	creat_category();
	sys_msg("保存成功");
}


/*------------------------------------------------------ */
//-- 删除商品分类
/*------------------------------------------------------ */
if ($_ACT == 'remove')
{
    if ($_ID!=''){
    $cat_name = $db->selectinfo(" SELECT cat_name FROM ".CATALOG." WHERE cat_id='$_ID'");
    $cat_name = $cat_name["cat_name"];
    /* 当前分类下是否有子分类 */
    $cat_count = $db->count_info(CATALOG," * ","  parent_id='$_ID'");

    /* 当前分类下是否存在商品 */
    $goods_count = $db->count_info(GOODS," * "," cat_id='$_ID'");
	}
    /* 如果不存在下级子分类和商品，则删除之 */


    if ($cat_count == 0 && $goods_count == 0){
        /* 删除分类 */
        $sql = 'DELETE FROM ' .CATALOG. " WHERE cat_id = '$_ID'";
        if ($db->query($sql)){
            admin_log("", _DELSTRING_, '商品分类列表：名称号为'.$cat_name);
			$msg = "已经删除".$cat_name;
        }
    }else{
		$msg = $cat_name." 下存在子分类或商品，请删除子分类或商品再删除分类！";
	}

//	echo $_SERVER['HTTP_REFERER'];


	$links[0]['name'] = "返回分类列表";
	$links[0]['url'] = $_SERVER['HTTP_REFERER'];//'category.php?act=list';
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
	$tree = array();//生成分类树，不可少
	creat_category();

}




/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */
//
///**
// * 检查分类是否已经存在
// *
// * @param   string      $cat_name       分类名称
// * @param   integer     $parent_cat     上级分类
// * @param   integer     $exclude        排除的分类ID
// *
// * @return  boolean
// */
//function cat_exists($cat_name, $parent_cat, $exclude = 0)
//{
//    $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('category').
//           " WHERE parent_id = '$parent_cat' AND cat_name = '$cat_name' AND cat_id<>'$exclude'";
//    return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
//}

/**
 * 获得商品分类的所有信息
 *
 * @param   integer     $cat_id     指定的分类ID
 *
 * @return  mix
 */
function get_cat_info($cat_id)
{
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('category'). " WHERE cat_id='$cat_id' LIMIT 1";
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 添加商品分类
 *
 * @param   integer $cat_id
 * @param   array   $args
 *
 * @return  mix
 */
function cat_update($cat_id, $args)
{
    if (empty($args) || empty($cat_id))
    {
        return false;
    }

    return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('category'), $args, 'update', "cat_id='$cat_id'");
}


/**
 * 添加商品时，选择分类返回价格数和折扣
 */
if ($_ACT == 'goods_jiage')
{
	if ($_ID!=""){

		$goods_id = $_REQUEST['goods_id'];
		$chuhuo_price = $db->getOne("select chuhuo_price from ".GOODS." WHERE goods_id = '".$goods_id."'");

		$fenleiArr = get_zhuijia_price_and_fenlei_bili($_ID,$chuhuo_price);
		$catArr["zhekou"] = $fenleiArr['bili'];
		$catArr["grade"]  = $fenleiArr["grade"];

		$grade_str = '';
		$firstgrad = '';
		$grade_arr = explode('|',$catArr["grade"]);
		if (is_array($grade_arr)){
			$firstgrad = $grade_arr[0];
			for($i = 1 ;$i < count($grade_arr);$i++){
				$grade_str .= '<tr id="'.($i+1).'"><td height="23"> <a href="javascript:;" onclick=deltr("'.($i+1).'")>[- ]</a> 数量 <input type="text" name="volume_number[]" size="8" value="'.$grade_arr[$i].'"/> 价格 <input type="text" name="volume_price[]"  id="count_volume_price'.($i+1).'" size="8" value=""/></td> </tr>';
			}
		}

		echo "var catArr=new Array();";
		echo "catArr[0] = '$grade_str';";
		echo "catArr[1] = '$catArr[zhekou]';";
		echo "catArr[2] = '$firstgrad';";
	}
exit;
}

 $_ACT = $_ACT == 'msg'?'msg':'category_'.$_ACT;
temp_disp();
?>