<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
set_time_limit(0);
	    /* 权限检查 */
admin_priv('creat_goods_content');
$_ID = 0;
$_ACT = 'goods_content';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
$url = $_CFG['creat_html_domain'];

if ($_ACT == 'goods_content'){
    $Arr['nowtime'] = local_date($_CFG['date_format'], time());
    $Arr['cat_list'] = cat_list();
}


//生成列表
elseif ($_ACT == 'creat_content'){
	
	$width = 500;   //显示的进度条长度，单位 px

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "' target=_blank>http://www.w3.org/TR/xhtml1/DTD/transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<title></title>
<link href='/temp/skin1/eload_admin/images/admin_css.css' rel='stylesheet' type='text/css'/>
<script language="javascript" src="/sysjs/eload13pack.js"></script>
<script language="javascript" src="/sysjs/validator.js"></script>
<script language="javascript" src="/sysjs/jlivequery.js"></script>
<script language="javascript" src="/sysjs/jcookie.js"></script>
<script language="javascript" src="/temp/skin1/eload_admin/js/tablegrid.js"></script>
<script language="javascript" src="/temp/skin1/eload_admin/js/admin_add.js"></script>

 <style>
 body, div input { font-family: Tahoma; font-size: 12px; }
 </style>
 <script language="JavaScript">
 <!--
 function updateProgress(sMsg, iWidth)
 { 
     document.getElementById("status").innerHTML = sMsg;
     document.getElementById("progress").style.width = iWidth + "px";
     document.getElementById("percent").innerHTML = parseInt(iWidth / <?php echo $width; ?> * 100) + "%";
  }
 //-->
 </script>    
</head>
<body>
<table width="99%" align="center" cellspacing="1"  bgcolor="#FFFFFF" class="borderline">
  <tr>
    <th><table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="56%" id="navleft"><a href="?act=main">管理中心</a> ->> 更新商品</td>
          <td width="44%" align="right"></td>
        </tr>
    </table></th>
  </tr>
</table>
<table width="99%" align="center" cellspacing="1"  bgcolor="#FFFFFF" class="borderline" id="stripe_tb">
  <tr>
    <th>更新进度</th>
  </tr>
  <tr><td>
<div style="margin: auto; padding: 8px; width: <?php echo $width+8; ?>px">
 <div><font color="gray">以下是更新进度：</font></div>
 <div style="padding: 1px; background-color: white; border: 1px solid #FF8604; width: <?php echo $width; ?>px">
     <div id="progress" style="padding: 0; background: url(/temp/skin1/images/8-122.gif); border: 0; width: 0px; text-align: center;  height: 14px"></div>            
 </div>
 <div id="status">&nbsp;</div>
 <div id="percent" style="position: relative; top: -30px; text-align: center; font-weight: bold; font-size: 8pt">0%</div>
</div>
    </td></tr></table>    

<?  flush();
    $sql = "";
	$limitstr = '';
    $cat_t[0] = 0;
    $cat_id    = !empty($_POST['catids'])? $_POST['catids']:$cat_t;
	$creat_num = !empty($_POST['number'])?intval($_POST['number']):0;
	$fromid    = !empty($_POST['fromid'])?intval($_POST['fromid']):0;
	$toid      = !empty($_POST['toid'])?intval($_POST['toid']):0;

	$fromdate    = !empty($_POST['fromdate'])?strtotime($_POST['fromdate']):'';
	$todate      = !empty($_POST['todate'])?strtotime($_POST['todate']):'';
	
	$creat      = !empty($_POST['creat'])?trim($_POST['creat']):'';

	$msg = '';
	
	switch ($creat){
		   case 'all':
				//按分类生成
				if ($cat_id[0] != 0){
					foreach ($cat_id as $v){
						$children = get_children($v);
						$sql .= " AND ($children) ";
					}
				}
		   break;
		   
		   case 'numcreat':
				//按条数生成
				if ($creat_num!=0)  {
						$limitstr = ' ORDER BY g.last_update DESC LIMIT '.$creat_num;
					}else{
						sys_msg('更新最新发布信息的条数不能为0且不能为空！', 1, array(), false);
					}

		   break;
		
		   case 'goodsidcreat':
				//指定ID号生成
				if ($toid!=0 && ($fromid <= $toid)) {
					$sql .= " AND g.goods_id >= '$fromid' And  g.goods_id <= '$toid' ";
				}else{
						sys_msg('商品ID设置不正确！', 1, array(), false);
					}
		   break;
		   
		   case 'updatecreat':
				//按更新时间
				if ($todate!='' && $fromdate!='' && ($fromdate <= $todate)) {
					$sql .= " AND g.last_update >= '$fromdate' And  g.last_update <= '$todate' ";
				}else{
						sys_msg('日期设置不正确！', 1, array(), false);
				}
		   break;
	}
	
	$sql = "select g.url_title,g.goods_id,g.cat_id,g.goods_name from ".GOODS." AS g  WHERE g.is_delete = 0 and g.is_on_sale = 1 and g.is_alone_sale = 1  $sql $limitstr";
	
	$listArr = $db -> arrQuery($sql);
	
	$total = count($listArr); //总共需要操作的记录数
	if ($total != 0){
		$pix = $width / $total; //每条记录的操作所占的进度条单位长度
		$progress = 0;  //当前进度条长度
		creat_goods_content($listArr);
		?>
		<script language="JavaScript">
		 updateProgress("更新完成！", <?php echo $width; ?>);
		</script>
	<?php
	}else{
		?>
		<script language="JavaScript">
		 updateProgress("更新失败，查询记录数为0！", <?php echo 0; ?>);
		</script>
	<?php
	}
	flush();
	exit();
}

//生成列表
function creat_goods_content($listArr){
	    global $progress,$pix,$url,$width;
		$cat_static_Arr  = read_static_cache('category_c_key',2);
		foreach ($listArr as $k => $v){
			$url_title = $v['url_title'];
			$goods_id = $v['goods_id'];
			$cat_id = $cat_static_Arr[$v['cat_id']]['url_title'];
			$file_name = 'm-goods.htm';
			$re_url  = $url .$file_name.'?id='.$goods_id;
			$content = file_get_contents($re_url);
						
			$dir = GOODS_DIR.$cat_id.'/';
			$path_dir = ROOT_PATH .$dir ;
			/* 如果目标目录不存在，则创建它 */
			if (!file_exists($path_dir)){
				if (!make_dir($path_dir)){sys_msg( '目录不能写，请检查读写权限', 1, array(), false);return false;}}
			$file_path = $path_dir.$url_title;
			file_put_contents($file_path,$content);
			flush();
			?>
			<script language="JavaScript">
			 updateProgress(" 正在更新<?=$cat_id?> ... ", <?php echo min($width, intval($progress)); ?>);
			</script>
			<?
				
		 flush(); 
		 $progress += $pix;    
		}

}





$_ACT = $_ACT == 'msg'?'msg':'creat_'.$_ACT;
temp_disp();
?>