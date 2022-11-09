<?
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
//usleep(1000000 * .5);

admin_priv('abckeyword');  //检查权限

/* act操作项的初始化 */
$_ACT = 'list_old';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = trim($_GET['id']);



/*------------------------------------------------------ */
//-- 获取所有日志列表
/*------------------------------------------------------ */
if ($_ACT == 'list_old')
{
    $abc_list = get_abc_list();
	$Arr["abc_list"]     = $abc_list['list'];
	$Arr["list"]         = $abc_list['list'];
    $sort_flag           = sort_flag($abc_list['filter']);
	$Arr[$sort_flag['tag']] = $sort_flag['img'];
	$log_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	$Arr["filter"]       = $abc_list['filter'];
	$page=new page(array('total'=>$abc_list['record_count'],'perpage'=>$abc_list['page_size']));
	$Arr["pagestr"]  = $page->show();
	
}


if ($_ACT == 'batch_drop')
{
	$checkboxes = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : array();
	$count = 0;
	foreach ($_POST['checkboxes'] AS $key => $id)
	{
		$sql = "DELETE FROM " .ABCKEYWORD. " WHERE keyword = '$id'";
		$result = $db->query($sql);
		$count++;
	}
	if ($result)
	{
		admin_log('', '', sprintf('批量删除了 %d 个ABC索引记录', $count));
		$link[] = array('name' => '返回ABC索引列表', 'url' => '?');
		sys_msg(sprintf('成功删除了 %d 个ABC索引记录', $count), 0, $link);
	}
}


if ($_ACT == 'xiuggai')
{
	$id = empty($_POST['id'])?'':$_POST['id'];
	$keyword = empty($_POST['keyword'])?'':$_POST['keyword'];
	
	
	$sql = "select count(keyword) from ".ABCKEYWORD." where  keyword = '$keyword' ";
	if($db->getOne($sql)>0)
	   sys_msg("关键字 ".$keyword." 有重复请重新输入！", 1, array(), false);
	
	
	$sql = "update ".ABCKEYWORD." set keyword = '$keyword' where keyword = '$id' ";
	$db->query($sql);
	
    $links[0]["name"] = "返回ABC索引列表";
	$links[0]["url"] = "?";
    sys_msg('修改成功', 0, $links);

}
if ($_ACT == 'import')
{
	flush();
	$fparts = explode('.', $_FILES['excelfile']['name']);
	$file_suffix = strtolower(trim(end($fparts)));
	$mime_type = array('xls'=> array('application/excel','application/vnd.ms-excel','application/msexcel','application/octet-stream','application/kset'));
	if($file_suffix != 'xls' && !in_array($_FILES['excelfile']['type'], $mime_type['xls'])){
		sys_msg("文件格式错误，此处必须要上传EXCEL文件！", 1, array(), false);
	}		
	require_once '../lib/Excel/reader.php';
	$data = new Spreadsheet_Excel_Reader();
	$data->setOutputEncoding('UTF-8');
	$data->read($_FILES['excelfile']['tmp_name']);		
	$y = 0;
	$d = 0;
	foreach($data->sheets[0]['cells'] as $k => $v){
		$keyw =  trim($v[1]);
		if (($keyw!='') && (strlen($keyw)<150) && (strlen($keyw)!=0) && $keyw){
		//$keyw =  addslashes(trim($keyw));
		preg_match_all("/[0-9a-zA-Z.#+]{1,}/",$keyw,$match); 
		$keyw =  implode(' ',$match[0]);
		
		$sql = "select count(*) from ".ABCKEYWORD." where  keyword like '".$keyw."'";
		//echo $sql;//ltrim(rtrim(keyword))
			if($db->getOne($sql)==0){
				$arr = array();
				$arr        = explode(' ', $keyw);
				$operator   = " OR ";
				$keywords    = '';
				foreach ($arr AS $key => $val)
				{
					if ($key > 0 && $key < count($arr) && count($arr) > 1)
						$keywords .= $operator;
						
					$val = mysql_like_quote(trim($val));
					$keywords  .= "concat(goods_sn,goods_title,goods_brief) LIKE '%$val%'";
				}
				
				$sql = "select count(*) from ".GOODS." where  $keywords  order by goods_id desc";
				$goods_num = $db->getOne($sql);
				$data   = array();
				$data['keyword']   = $keyw;
				$data['goods_num'] = $goods_num;
				$db->autoExecute(ABCKEYWORD,$data);
				$y++;
				
				echo '<font color="0000ff">'.$keyw .' 录入成功</font><br>';
			}else{
				echo '<font color="ff0000">'.$keyw .' 数据库中已存在该关键字，录入失败</font><br>';
			$d++;
			}
		}
		flush();
	}
	echo '成功录入'.$y.'个关键字，'.$d.'个关键字重复';
	exit;
   // $links[0]["name"] = "返回ABC索引列表";
	//$links[0]["url"] = "?";
    //sys_msg(, 0, $links);
}
/* 获取管理员操作记录 */
function get_abc_list()
{   global $db;
    $filter = array();
    $filter['sort_by']    = empty($_GET['sort_by']) ? ' keyword ' : trim($_GET['sort_by']);
    $filter['sort_order'] = empty($_GET['sort_order']) ? '' : trim($_GET['sort_order']);
    $filter['keyword']    = empty($_GET['keyword']) ? '' : trim($_GET['keyword']);


    //查询条件
    $where = " 1 ";
	if($filter['keyword']!=''){
		$where .= " AND keyword like '%".$filter['keyword']."%' ";
	}
	
	

    /* 获得总记录数据 */
    $filter['record_count'] = $db->count_info(' ' .ABCKEYWORD. ' ',"*"," $where");
    $filter = page_and_size($filter);
	
    /* 获取管理员日志记录 */
    $list  = array();
    $sql   = 'SELECT * FROM ' .ABCKEYWORD. '  where '.
            $where .' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'] ;
    $list  = $db->selectLimit($sql, $filter['page_size'], $filter['start']);
	
    return array('list' => $list, 'filter' => $filter,'record_count' => $filter['record_count'],'page_size'=> $filter['page_size']);
}
if($_ACT == 'edit') $_ACT = 'edit_old';
$_ACT = 'abckeyword_'.$_ACT;
temp_disp();
?>