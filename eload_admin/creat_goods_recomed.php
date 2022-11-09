<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
set_time_limit(0);
	    /* 权限检查 */
admin_priv('creat_goods_recomed');

$_ACT = 'goods_recomed';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
$url = $_CFG['creat_html_domain'];


if ($_ACT == 'goods_recomed'){
    $Arr['cat_list'] = cat_list();
}


//生成列表
elseif ($_ACT == 'creat_list'){
	
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
          <td width="56%" id="navleft"><a href="?act=main">管理中心</a> ->> 更新推荐商品</td>
          <td width="44%" align="right"></td>
        </tr>
    </table></th>
  </tr>
</table>
<table width="99%" align="center" cellspacing="1"  bgcolor="#FFFFFF" class="borderline" id="stripe_tb">
  <tr>
    <th>更新推荐进度</th>
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
    $cat_id   = !empty($_POST['catids'])   ? $_POST['catids']  : '';
    $listper  = !empty($_POST['listper'])  ? $_POST['listper'] : '20|40|60';
    $gridper  = !empty($_POST['gridper'])  ? $_POST['gridper'] : '24|48|72';
    $listperArr  = explode('|',$listper);
    $gridperArr  = explode('|',$gridper);
	$list_odr_Arr = $_CFG['l'];   //排序方式
	$grid_odr_Arr = $_CFG['g'];
	
   if (!is_array($cat_id)){exit();}
	
	$total = count($cat_id)*2; //总共需要操作的记录数
	$pix = $width / $total; //每条记录的操作所占的进度条单位长度
	$progress = 0;  //当前进度条长度
	
	creat_goods_list($cat_id,$listperArr,$list_odr_Arr);
	creat_goods_list($cat_id,$gridperArr,$grid_odr_Arr,'g');
	?>
	<script language="JavaScript">
	 updateProgress("更新完成！", <?php echo $width; ?>);
	</script>
	<?php
	flush();
	exit();
}



//生成列表
function creat_goods_list($catArr,$listperArr,$list_odr_Arr,$layout='l'){
	    global $progress,$pix,$url,$width,$db;
		foreach ($catArr as $k => $v){
			
			$url_title = $v;
			
			//计算总记录
		$sql   = "SELECT COUNT(*) FROM " .GOODS. " AS g ".
		"WHERE g.is_delete = 0 AND g.is_on_sale = 1  AND g.is_".$v." = 1 ";
		$count = $db->getOne($sql);
		
			foreach ($listperArr as $val){
				
				$max_page = ($count> 0) ? ceil($count / $val) : 1;
				for ($p=1;$p<=$max_page;$p++){
					
					foreach ($list_odr_Arr as $key => $odr){
						$file_name = $url_title.'-'.$key.'-'.$val.'-'.$p.'.htm';
						$re_url  = $url .$file_name.'?m=search&intro='.$v.'&page='.$p.'&page_size='.$val.'&odr='.$key.'&display='.$layout.'&creat=1';
						//echo $re_url.'<br>';
						
						$content = file_get_contents($re_url);
						
						$dir = 'htm/'.$url_title.'/';
						$path_dir = ROOT_PATH .$dir ;
						/* 如果目标目录不存在，则创建它 */
						if (!file_exists($path_dir)){
							if (!make_dir($path_dir)){sys_msg( '目录不能写，请检查读写权限', 1, array(), false);return false;}}
                        
						$file_path = $path_dir.$file_name;
						
						$perlinks = '';
						$style    = '';
						foreach ($listperArr as $vl){
							if ($val == $vl) $style = 'red';
							//href='/htm/$url_title/$url_title-$key-$vl-1.htm'
							$perlinks .= "<a href='#' class='".$style."'  title='Per page ".$vl."'>".$vl."</a>   ";
							$style = '';
						}

						$content  = str_replace('[perlinks]',$perlinks,$content);
						file_put_contents($file_path,$content);
						flush();
						?>
						<script language="JavaScript">
                         updateProgress("正在更新 “<?php echo ' '.$v.' '.$layout. ' pages'  ; ?> ”...", <?php echo min($width, intval($progress)); ?>);
                        </script>
						<?
					}
				}
				
			}
		 flush(); 
		 $progress += $pix;    
		}

}





$_ACT = $_ACT == 'msg'?'msg':'creat_'.$_ACT;
temp_disp();
?>