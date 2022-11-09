<?php
/**
 * 分类关键词管理功能
 * 摘自sammydress by mashanling on 2012-08-31 10:20:44
 * */
define('INI_WEB', true);
require_once('../lib/global.php');//引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('cat_keywords');  //检查权限

$_ACT = 'list';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id']))  $_ID    = intval(trim($_GET['id']));
if (!empty($_GET['cid'])) $_CID   = intval(trim($_GET['cid']));

/*------------------------------------------------------ */
//-- 管理界面
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    $cat_id=-1;
    $keywords='';

   /* if($_POST)
    {
        $cat_id = end($_POST['parent_id']);
        if(empty($cat_id))
           $cat_id=$_POST['parent_id'][count($_POST['parent_id'])-2];
            $keywords = $_POST['keywords'];
    }*/
    
    if($_POST)
    {
        if (!empty($_POST['parent_id']))
        {
            foreach($_POST['parent_id'] as $key => $val){
                if($_POST['parent_id'][$key] == '') unset($_POST['parent_id'][$key]);
            }
        }
        
        $cat_id = empty($_POST['parent_id']) ? 0 : intval(is_array($_POST['parent_id'])?end($_POST['parent_id']):$_POST['parent_id']);
        $keywords = $_POST['keywords'];
    }
    
    if($cat_id!=-1)
    {
       if($cat_id!=0)
        {
            $Arr["cat_select"] = '';
            $parent_id_str = get_parent_id($cat_id);
            $parent_id_Arr = explode(',',$parent_id_str);
            $parent_id_Arr = array_reverse ($parent_id_Arr); //数组逆序
            foreach($parent_id_Arr as $key => $val){
                if ($val!=''){
                    $parent_id = $val;
                    $selectid = isset($parent_id_Arr[$key+1])?$parent_id_Arr[$key+1]:$cat_id;
                    //echo '$parent_id:'.$parent_id.'$selectid'.$selectid.'<br>';
                    $Arr["cat_select"] .=  get_lei_select($parent_id,'parent_id[]','','muli_cat',$selectid);
                }
            }
        }
        else
        {
            $Arr["cat_select"] =   get_lei_select(0,'parent_id[]','','muli_cat',0);
        }
    }
    else
    {
        $Arr["cat_select"] =   get_lei_select(0,'parent_id[]','','muli_cat',-1);
    }


    $cat_keywords_list = cat_keywords_list($cat_id,$keywords);
    $Arr["cat_keywords_list"]  =  $cat_keywords_list['type'];
    $Arr["filter"]  =     $cat_keywords_list['filter'];
    $page=new page(array('total'=>$cat_keywords_list['record_count'],'perpage'=>$cat_keywords_list['page_size']));
    $Arr["pagestr"]  = $page->show();

    $Arr["cat_id"]=$cat_id;
    $Arr["keywords"]=$keywords;
}

/*------------------------------------------------------ */
//-- 添加分类关键词
/*------------------------------------------------------ */
elseif ($_ACT == 'add')
{
    $cat_info["parent_id"] = 0;
    $parent_id = 0;
    if ($_ID!=0){
        $tag_msg = "修改";
        $sql = "select * from ".CAT_KEYWORDS." where id = $_ID";
        $cat_keywords = $db -> selectInfo($sql);

        $Arr["cat_select"] = '';
        if(isset($_CID))
        {
            $parent_id_str = get_parent_id($_CID);
            $parent_id_Arr = explode(',',$parent_id_str);
            $parent_id_Arr = array_reverse ($parent_id_Arr); //数组逆序
            foreach($parent_id_Arr as $key => $val){
                if ($val!=''){
                    $parent_id = $val;
                    $selectid = isset($parent_id_Arr[$key+1])?$parent_id_Arr[$key+1]:$_CID;
                    //echo '$parent_id:'.$parent_id.'$selectid'.$selectid.'<br>';
                    $Arr["cat_select"] .=  get_lei_select($parent_id,'parent_id[]','','muli_cat',$selectid);
                }
            }
        }
        else
        {
            $Arr["cat_select"] =   get_lei_select($parent_id,'parent_id[]','','muli_cat');
        }
    }
    else
    {
        $Arr["cat_info"]  =  array('is_show' => 1);
        $Arr["cat_select"] =   get_lei_select($parent_id,'parent_id[]','','muli_cat');
        $tag_msg = "添加";
    }


    $url = "?act=insert&id=$_ID";
    if(isset($cat_keywords)){
        $Arr["cat_keywords"]   = $cat_keywords;
    }
    $Arr["tag_msg"] = $tag_msg;
    $Arr["url"] = $url;
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////批量处理分类关键词推荐到索引关键词
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif ($_ACT == 'batch')
{
        // 操作权限检测
        admin_priv('cat_keywords');
        //获取所选分类关键词
        $category_ids = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
        $cat_keywordsArr['recommend']  = 1;   //是否分类关键词推荐
        $cat_keywordsArr['identity']           = !empty($_POST['type'])  ? $_POST['type'] : ''; //关键词推荐到索引词分类
        if(!empty($category_ids))
        {
                $category_ids_arr = explode(',',$category_ids);
                foreach($category_ids_arr as $key=>$val)
                {
                    $db->autoExecute(CAT_KEYWORDS, $cat_keywordsArr,'UPDATE'," id = $val") ;        
                }
        }
        $link[0]["name"] = "返回上一页";
        $link[0]["url"] = $_SERVER["HTTP_REFERER"];
        $link[1]["name"] = "返回分类关键词";
        $link[1]["url"] = "cat_keywords.php?act=list";
        sys_msg("批量操作成功", 0, $link);      //记录日志信息
}

/*------------------------------------------------------ */
//-- 保存分类关键词信息
/*------------------------------------------------------ */
elseif ($_ACT == 'insert')
{
    $parent_id_temp=end($_POST['parent_id']);
    if($parent_id_temp=='')
    {
       $parent_id_temp=$_POST['parent_id'][count($_POST['parent_id'])-2];
    }

    $cat_keywordsArr['cat_id'] = $parent_id_temp;
    $cat_keywordsArr['keywords'] = $_POST['keywords'];
    $cat_keywordsArr['url'] = $_POST['url'];
    $cat_keywordsArr['num'] = empty($_POST['order'])?0:$_POST['order'];
    $cat_keywordsArr['status'] = empty($_POST['status'])?0:$_POST['status'];
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///增加对是否推荐和索引关键词分类的关联
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $cat_keywordsArr['recommend'] = empty($_POST['recommend'])?0:$_POST['recommend'];   //关键词是否推荐
    $cat_keywordsArr['identity']         = empty($_POST['type'])?0:$_POST['type'];   //关键词推荐到索引词分类

    
    if ($_ID!=''){
        if ($db->autoExecute(CAT_KEYWORDS, $cat_keywordsArr,'UPDATE'," id = $_ID") !== false){
            $msg="修改成功";
            admin_log($sn = '', _ADDSTRING_, '分类关键词'.$cat_keywordsArr['keywords']);
        }else{$msg="添加失败";}
        $links = array('0'=> array('url'=>'?act=list','name'=>'返回分类关键词列表'),
                       '1'=> array('url'=>'?act=add','name'=>'返回添加分类关键词'));
    }else{
        if ($db->autoExecute(CAT_KEYWORDS, $cat_keywordsArr) !== false){
            $msg="添加成功";
            admin_log($sn = '', _ADDSTRING_, '分类关键词'.$cat_keywordsArr['keywords']);
        }else{$msg="添加失败";}
        $links = array('0'=> array('url'=>'?act=list','name'=>'返回分类关键词列表'),
                       '1'=> array('url'=>'?act=add','name'=>'返回添加分类关键词'));
    }
    $_ACT = 'msg';
    $Arr["msg"] = $msg;
    $Arr["links"] = $links;
}

/*------------------------------------------------------ */
//-- 删除分类关键词
/*------------------------------------------------------ */

elseif ($_ACT == 'remove')
{
       if ($_ID!=''){
        admin_log($sn='', _DELSTRING_, '分类关键词列表ID为 '.$_ID);
        $db -> delete(CAT_KEYWORDS," id = $_ID ");
        $msg = "删除成功！";
        $links[] = array('url'=>"?act=list",'name'=>'返回分类关键词列表');
        $_ACT = 'msg';
        $Arr["msg"]   = $msg;
        $Arr["links"] = $links;
    }
}
/*------------------------------------------------------ */
//-- ajax修改分类关键词名称
/*------------------------------------------------------
elseif ($_ACT == 'editinplace')
{
    $id  = intval($_POST['id']);
    $val = trim($_POST['value']);
    $db->update(PCODE," code = '$val' ", " id = '$id'");
    admin_log('', _EDITSTRING_,'分类关键词 '.$val);
    echo $val;
    exit();
}
*/

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
//-- ajax修改商品分类
/*------------------------------------------------------ */
elseif ($_ACT == 'editinplace')
{

    $dataArr = explode('||',$_POST['id']);
    $id    = intval($dataArr[0]);
    $field = trim($dataArr[1]);
    $val   = trim($_POST['value']);

    $db->query("UPDATE ".CAT_KEYWORDS." SET $field = '$val' WHERE id=$id");
    echo $val;
    exit();
}
/*------------------------------------------------------ */
//-- ajax修改商品状态
/*------------------------------------------------------ */
elseif ($_ACT == 'cat_keywords_show')
{
    $id = intval($_POST['id']);
    $status = intval($_POST['status']);
    if($db->query("UPDATE ".CAT_KEYWORDS." SET status = '$status' WHERE id=$id"))
        echo "1";
    else
        echo "-1";

    exit();
}


$_ACT = $_ACT == 'msg'?'msg':'cat_keywords_'.$_ACT;
temp_disp();

/**
 * 获取分类关键词
 * @return  array
 */
function cat_keywords_list($cid=0,$keywords)
{
    global $db;
    $ext = ' WHERE 1 ';
    /* 记录总数以及页数 */
    $filter['record_count'] = 0 ;
    $filter['record_count'] = $db->count_info(CAT_KEYWORDS,"*","");
    $filter = page_and_size($filter);
    /* 查询记录 */
    if($cid >=0)
    {
        $cid=intval($cid);
        $ext.=" AND a.cat_id=$cid ";
    }
    
    if($keywords)
    {
         $ext.=" AND a.keywords LIKE '%$keywords%' ";
    }

    $sql = "SELECT a.*,b.cat_name FROM ".CAT_KEYWORDS." a LEFT JOIN eload_category b ON a.cat_id=b.cat_id $ext ORDER BY a.num DESC,a.id DESC limit $filter[start],$filter[page_size]";
    $all = $db->arrQuery($sql);

    return array('type' => $all, 'filter' => $filter, 'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
}
?>