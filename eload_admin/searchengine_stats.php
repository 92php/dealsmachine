<?php

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('lang/statistic.php');
require_once('../lib/param.class.php');
require_once('../lib/class.page.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'view';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

if ($_REQUEST['act'] == 'view')
{
	admin_priv('client_flow_stats');

    /* 时间参数 */
    /* TODO: 时间需要改 */
    if (isset($_GET) && !empty($_GET))
    {
        $start_date = $_GET['start_date'];
        $end_date   = $_GET['end_date'];
    }
    else
    {
        $start_date = local_date('Y-m-d', strtotime('-1 day'));
        $end_date   = local_date('Y-m-d');
        
        $_GET['start_date'] =$start_date;
        $_GET['end_date'] = $end_date;
    }
	
    $searchengines = get_keywords();
    if($Arr['filter']['sort_order']=='DESC')
    	$Arr['filter']['new_sort_order']='ASC';
    else 
    	$Arr['filter']['new_sort_order']='DESC';
 // print_r($Arr['filter']);
  
    
    $Arr['searchengines'] = $searchengines;
    
    /* 显示日期 */
    $Arr['start_date']=   $start_date;
    $Arr['end_date']=     $end_date;

    /*$filename = local_date('Ymd', $start_date) . '_' . local_date('Ymd', $end_date);
    $Arr['action_link'] =  array('text' => $_LANG['down_search_stats'], 'href' => 'searchengine_stats.php?act=download&start_date=' . $start_date . '&end_date=' . $end_date . '&filename=' . $filename);*/

    $Arr['lang']= $_LANG;
    $_ACT = 'searchengine_stats';
    
    
    
 	$title_url = '';
	$back_url = '';
	foreach($_GET as $key => $val){
		if ($key!='act' && $key!='goods_id'){
			if(is_array($_GET[$key])){
				foreach($_GET[$key] as $row){
					$title_url .= '&'.$key.'[]='.$row;
					$back_url .= '&'.$key.'[]='.$row;
				}
			}else{
				$back_url .= '&'.$key.'='.$val;

				if ($key!='sort_by' && $key!='sort_order')
				      $title_url .= '&'.$key.'='.$val;
			}
		}
	}
	   
    $Arr['title_url'] = $title_url;
    
}
elseif ($_REQUEST['act'] == 'download')
{
    $start_date =  $_REQUEST['start_date'];
    $end_date   =  $_REQUEST['end_date'];

    $filename = $start_date . '_' . $end_date;
    $sql = "SELECT keyword, count,searchengine ".
            " FROM " .KEYWORDS.
            " WHERE date >= '$start_date' AND date <= '$end_date'";
    $res = $db->query($sql);

    $searchengine = array();
    $keyword = array();

    while ($val = $db->fetchRow($res))
    {
        $keyword[$val['keyword']] = 1;
        $searchengine[$val['searchengine']][$val['keyword']] = $val['count'];
    }
    header("Content-type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename.xls");
    $data = "\t";
    foreach ($searchengine AS $k => $v)
    {
        $data .= "$k\t";
    }
    foreach ($keyword AS $kw => $val)
    {
        $data .= "\n$kw\t";
        foreach ($searchengine AS $k => $v)
        {
            if (isset($searchengine[$k][$kw]))
            {
                $data .= $searchengine[$k][$kw] . "\t";
            }
            else
            {
                $data .= "0" . "\t";
            }
        }
    }
    echo ecs_iconv(EC_CHARSET, 'GB2312', $data) . "\t";
	exit;
}


/**
 * 取得图表颜色
 *
 * @access  public
 * @param   integer $n  颜色顺序
 * @return  void
 */
function chart_color($n)
{
    /* 随机显示颜色代码 */
    $arr = array('33FF66', 'FF6600', '3399FF', '009966', 'CC3399', 'FFCC33', '6699CC', 'CC3366', '33FF66', 'FF6600', '3399FF');

    if ($n > 8)
    {
        $n = $n % 8;
    }

    return $arr[$n];
}

/**
 * 统计关键词
 *
 */
function get_keywords() {
    global $Arr, $db;


	$filter    = page_and_size(array())	;
	$size      = $filter['page_size'];
	$keyword   = Param::get('keyword');
	$status    = Param::get('status');
	$start_date= Param::get('start_date');
	$end_date  = Param::get('end_date');
	$column    = Param::get('column');
	$filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'count' : trim($_REQUEST['sort_by']);
    $filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	//$exclude_self = Param::get('exclude_self', 'int');//排除自已人提前写的评论 by mashanling on 17:42 2012-07-20
	//$where     = $exclude_self ? ' WHERE r.adddate<' . gmtime(): 'WHERE 1';
	$where =' where 1=1';
	if (!empty($_REQUEST['start_date'])||!empty($_REQUEST['end_date']))
    {
        $start_date = $_REQUEST['start_date'];
        $end_date   = $_REQUEST['end_date'];
    }
    else
    {
        $start_date = local_date('Y-m-d', strtotime('-1 day'));
        $end_date   = local_date('Y-m-d');
    }
	
	if ($keyword != '') {    //关键字
	  //  $where .= in_array($column, array('g.goods_id', 'u.user_id')) ? " AND {$column}=" . intval($keyword) : " AND {$column} LIKE '%{$keyword}%'";
	}

	if ($status !== '') {    //状态

	}

    if (isset($_REQUEST['filter']))
    {
        $sql .= ' AND '. db_create_in($_POST['filter'], 'searchengine');
    }
	$start_date != '' && ($where .= " AND date>='" . $start_date."'");
	$end_date != '' && ($where .= " AND date<='" . $end_date."'");

    //echo (strtotime('2011-09-07'));exit;
    $Arr['keyword']  = $keyword;
    $Arr['column']   = $column;
    $Arr['status']   = $status;
    $Arr['start_date'] = $start_date;
    $Arr['end_date'] = $end_date;
   // $Arr['exclude_self'] = $exclude_self;
 	$groupby = ' GROUP BY keyword';
	$fields          = 'keyword,sum(count) as `count`,sum(not_found) as not_found,sum(not_found_by_user) as not_found_by_user,sum(found_by_user) as found_by_user';
	$table           = KEYWORDS;

    $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
    $sql             = 'SELECT COUNT(distinct(keyword)) FROM ' . $table . $where;
    $record_count    = $record_count > 0 ? $record_count : $db->getOne($sql);
	//echo $sql;
    if (!$record_count) {
    	
        return;
    }
    $filter['record_count']          = $record_count;
    //echo $record_count;
    $filter          = page_and_size($filter);    //分页信息
    $page            = new page(array(
        'total'   => $record_count,
        'perpage' => $filter['page_size'],
        'url'     => "?record_cound={$record_count}&amp;sort_by={$filter['sort_by']}&amp;sort_order={$filter['sort_order']}&amp;keyword={$keyword}&amp;start_date={$start_date}&amp;end_date={$end_date}"
        )
    );
	$Arr['pagestr']  = $page->show();
    $Arr['filter']   = $filter;

   // print_r($filter);
    $limit           =  ' LIMIT ' . $filter['start'] . ',' . $filter['page_size'];    //sql limit
    $sql             = "SELECT {$fields} FROM " . $table . $where .$groupby. ' ORDER BY '.$filter['sort_by'].' '.$filter['sort_order'] . $limit;
    $data            = $db->arrQuery($sql);
	//print_r($data);
    return  $data;
}//end new_get_reviews




temp_disp();
?>