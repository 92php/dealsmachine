<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/lib_order.php');
require_once(ROOT_PATH . 'lib/class.page.php');

if(!IS_LOCAL&&function_exists('get_slave_db')){
	//echo function_exists('get_slave_db');
	$db   = get_slave_db();  //线上使用从服务器
}
//print_r($db);
//exit;
$Arr['now_date'] = date("Y-m-d",strtotime("-1 week"));
$_REQUEST['start_date'] = empty($_REQUEST['start_date'])?'':local_strtotime($_REQUEST['start_date']);
$_REQUEST['end_date'] = empty($_REQUEST['end_date'])?'':local_strtotime($_REQUEST['end_date']);

$_REQUEST['start_date2'] = empty($_REQUEST['start_date2'])?'':local_strtotime($_REQUEST['start_date2']);
$_REQUEST['end_date2'] = empty($_REQUEST['end_date2'])?'':local_strtotime($_REQUEST['end_date2']);

$_REQUEST['sort_by']    = empty($_REQUEST['sort_by'])?'':$_REQUEST['sort_by'];
$_REQUEST['sort_order'] = empty($_REQUEST['sort_order'])?'':$_REQUEST['sort_order'];
$_REQUEST['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);

$_REQUEST['order_num'] = empty($_REQUEST['order_num']) ? '' : intval($_REQUEST['order_num']);
$_REQUEST['intro_type'] = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);

$_REQUEST['price1'] = empty($_REQUEST['price1']) ? '' : floatval($_REQUEST['price1']);
$_REQUEST['price2'] = empty($_REQUEST['price2']) ? '' : floatval($_REQUEST['price2']);
$_REQUEST['price3'] = empty($_REQUEST['price3']) ? '' : floatval($_REQUEST['price3']);
$_REQUEST['price4'] = empty($_REQUEST['price4']) ? '' : floatval($_REQUEST['price4']);


//$_REQUEST['start_date'] = empty($_REQUEST['start_date'])?local_strtotime('-1 months'):local_strtotime($_REQUEST['start_date']);
//$_REQUEST['end_date'] = empty($_REQUEST['end_date'])?local_strtotime('+1 day'):local_strtotime($_REQUEST['end_date']);
//
//
//$_REQUEST['start_date2'] = empty($_REQUEST['start_date2'])?local_strtotime('-1 months'):local_strtotime($_REQUEST['start_date2']);
//$_REQUEST['end_date2'] = empty($_REQUEST['end_date2'])?local_strtotime('+1 day'):local_strtotime($_REQUEST['end_date2']);


if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' ||  $_REQUEST['act'] == 'download'))
{
    admin_priv('sales_report');

    /* 下载报表 */
    if ($_REQUEST['act'] == 'download')
    {
        $goods_order_data = get_sales_order(false);
		$tj = $goods_order_data['tj'];
        $goods_order_data = $goods_order_data['sales_order_data'];

        $filename = local_date('Y-m-d', $_REQUEST['start_date']). '_' . local_date('Y-m-d', $_REQUEST['end_date']).'sale_order';

        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$filename.xls");



$data  = '<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=utf-8">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content="Microsoft Excel 11">
<link rel=File-List href="111.files/filelist.xml">
<link rel=Edit-Time-Data href="111.files/editdata.mso">
<link rel=OLE-Object-Data href="111.files/oledata.mso">
<!--[if !mso]>
<style>
v\:* {behavior:url(#default#VML);}
o\:* {behavior:url(#default#VML);}
x\:* {behavior:url(#default#VML);}
.shape {behavior:url(#default#VML);}
</style>
<![endif]--><!--[if gte mso 9]><xml>
 <o:DocumentProperties>
  <o:Created>1996-12-17T01:32:42Z</o:Created>
  <o:LastSaved>2010-04-24T03:20:39Z</o:LastSaved>
  <o:Version>11.9999</o:Version>
 </o:DocumentProperties>
 <o:OfficeDocumentSettings>
  <o:RemovePersonalInformation/>
 </o:OfficeDocumentSettings>
</xml><![endif]-->
<style>
<!--table
	{mso-displayed-decimal-separator:"\.";
	mso-displayed-thousand-separator:"\,";}
@page
	{margin:1.0in .75in 1.0in .75in;
	mso-header-margin:.5in;
	mso-footer-margin:.5in;}
tr
	{mso-height-source:auto;
	mso-ruby-visibility:none;}
col
	{mso-width-source:auto;
	mso-ruby-visibility:none;}
br
	{mso-data-placement:same-cell;}
.style0
	{mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	white-space:nowrap;
	mso-rotate:0;
	mso-background-source:auto;
	mso-pattern:auto;
	color:windowtext;
	font-size:12.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:宋体;
	mso-generic-font-family:auto;
	mso-font-charset:134;
	border:none;
	mso-protection:locked visible;
	mso-style-name:常规;
	mso-style-id:0;}
td
	{mso-style-parent:style0;
	padding:0px;
	mso-ignore:padding;
	color:windowtext;
	font-size:12.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:宋体;
	mso-generic-font-family:auto;
	mso-font-charset:134;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border:none;
	mso-background-source:auto;
	mso-pattern:auto;
	mso-protection:locked visible;
	white-space:nowrap;
	mso-rotate:0;}
.xl24
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"Arial Unicode MS", sans-serif;
	mso-font-charset:0;
	text-align:center;
	vertical-align:middle;
	border:.5pt solid windowtext;
	white-space:normal;}
.xl25
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-weight:700;
	font-family:"Arial Unicode MS", sans-serif;
	mso-font-charset:0;
	text-align:center;
	vertical-align:middle;
	border:.5pt solid windowtext;
	white-space:normal;}
.xl26
	{mso-style-parent:style0;
	vertical-align:middle;
	border:.5pt solid windowtext;
	white-space:normal;}
.xl27
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"Arial Unicode MS", sans-serif;
	mso-font-charset:0;
	vertical-align:middle;
	border:.5pt solid windowtext;
	white-space:normal;}
.xl28
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"Arial Unicode MS", sans-serif;
	mso-font-charset:0;
	mso-number-format:"Short Date";
	vertical-align:middle;
	border:.5pt solid windowtext;
	white-space:normal;}
.xl29
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"Arial Unicode MS", sans-serif;
	mso-font-charset:0;
	text-align:center;
	vertical-align:middle;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:.5pt solid windowtext;
	border-left:none;
	white-space:normal;}
.xl30
	{mso-style-parent:style0;
	font-size:10.0pt;
	font-family:"Arial Unicode MS", sans-serif;
	mso-font-charset:0;
	mso-number-format:"Short Date";
	vertical-align:middle;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:.5pt solid windowtext;
	border-left:none;
	white-space:normal;}
.xl31
	{mso-style-parent:style0;
	font-size:10.0pt;
	mso-number-format:"Short Date";
	text-align:right;
	vertical-align:middle;
	border-top:.5pt solid windowtext;
	border-right:none;
	border-bottom:.5pt solid windowtext;
	border-left:.5pt solid windowtext;
	white-space:normal;}
.xl32
	{mso-style-parent:style0;
	text-align:right;
	vertical-align:middle;
	border-top:.5pt solid windowtext;
	border-right:none;
	border-bottom:.5pt solid windowtext;
	border-left:none;
	white-space:normal;}
.xl33
	{mso-style-parent:style0;
	text-align:right;
	vertical-align:middle;
	border-top:.5pt solid windowtext;
	border-right:.5pt solid windowtext;
	border-bottom:.5pt solid windowtext;
	border-left:none;
	white-space:normal;}
ruby
	{ruby-align:left;}
rt
	{color:windowtext;
	font-size:9.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:宋体;
	mso-generic-font-family:auto;
	mso-font-charset:134;
	mso-char-type:none;
	display:none;}
-->
</style>
<!--[if gte mso 9]><xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Sheet1</x:Name>
    <x:WorksheetOptions>
     <x:DefaultRowHeight>285</x:DefaultRowHeight>
     <x:CodeName>Sheet1</x:CodeName>
     <x:Selected/>
     <x:Panes>
      <x:Pane>
       <x:Number>3</x:Number>
       <x:ActiveRow>13</x:ActiveRow>
       <x:ActiveCol>8</x:ActiveCol>
      </x:Pane>
     </x:Panes>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
   <x:ExcelWorksheet>
    <x:Name>Sheet2</x:Name>
    <x:WorksheetOptions>
     <x:DefaultRowHeight>285</x:DefaultRowHeight>
     <x:CodeName>Sheet2</x:CodeName>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
   <x:ExcelWorksheet>
    <x:Name>Sheet3</x:Name>
    <x:WorksheetOptions>
     <x:DefaultRowHeight>285</x:DefaultRowHeight>
     <x:CodeName>Sheet3</x:CodeName>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
  <x:WindowHeight>4530</x:WindowHeight>
  <x:WindowWidth>8505</x:WindowWidth>
  <x:WindowTopX>480</x:WindowTopX>
  <x:WindowTopY>120</x:WindowTopY>
  <x:AcceptLabelsInFormulas/>
  <x:ProtectStructure>False</x:ProtectStructure>
  <x:ProtectWindows>False</x:ProtectWindows>
 </x:ExcelWorkbook>
</xml><![endif]--><!--[if gte mso 9]><xml>
 <o:shapedefaults v:ext="edit" spidmax="2049"/>
</xml><![endif]--><!--[if gte mso 9]><xml>
 <o:shapelayout v:ext="edit">
  <o:idmap v:ext="edit" data="1"/>
 </o:shapelayout></xml><![endif]-->
</head>

<body link=blue vlink=purple>

<table x:str border=0 cellpadding=0 cellspacing=0 width=929 style="border-collapse:
 collapse;table-layout:fixed;width:699pt">
 <col width=72 style="width:54pt">
 <col width=54 style="mso-width-source:userset;mso-width-alt:1728;width:41pt">
 <col width=262 style="mso-width-source:userset;mso-width-alt:8384;width:197pt">
 <col width=72 style="width:54pt">
 <col width=142 style="mso-width-source:userset;mso-width-alt:4544;width:107pt">
 <col width=67 style="mso-width-source:userset;mso-width-alt:2144;width:50pt">
 <col width=75 style="mso-width-source:userset;mso-width-alt:2400;width:56pt">
 <col width=49 style="mso-width-source:userset;mso-width-alt:1568;width:37pt">
 <col width=70 style="mso-width-source:userset;mso-width-alt:2240;width:53pt">
 <col width=66 style="mso-width-source:userset;mso-width-alt:2112;width:50pt">
 <tr height=25 style="mso-height-source:userset;height:18.75pt">
  <td height=25 class=xl25 width=72 style="height:18.75pt;width:54pt">排名</td>
  <td class=xl25 width=54 style="border-left:none;width:41pt">图片</td>
  <td class=xl25 width=262 style="border-left:none;width:197pt">产品名称</td>
  <td class=xl25 width=72 style="border-left:none;width:54pt">产品编号</td>
  <td class=xl25 width=142 style="border-left:none;width:107pt">产品类别</td>
  <td class=xl25 width=67 style="border-left:none;width:50pt">上架时间</td>
  <td class=xl25 width=75 style="border-left:none;width:56pt">均价</td>
  <td class=xl25 width=49 style="border-left:none;width:37pt">数量</td>
  <td class=xl25 width=70 style="border-left:none;width:53pt">总销售额</td>
  <td class=xl25 width=66 style="border-left:none;width:50pt">开发员</td>
 </tr>';

        foreach ($goods_order_data AS $k => $row)
        {
            $order_by = $k + 1;


 $data .=   " <tr height=54 style='mso-height-source:userset;height:40.5pt'>
  <td height=54 class=xl24 width=72 style='height:40.5pt;border-top:none;
  width:54pt' x:num> $order_by </td>
  <td height=54 class=xl26 width=54 style='height:40.5pt;border-top:none;
  border-left:none;width:41pt'><!--[if gte vml 1]><v:shapetype id='_x0000_t75'
   coordsize='21600,21600' o:spt='75' o:preferrelative='t' path='m@4@5l@4@11@9@11@9@5xe'
   filled='f' stroked='f'>
   <v:stroke joinstyle='miter'/>
   <v:formulas>
    <v:f eqn='if lineDrawn pixelLineWidth 0'/>
    <v:f eqn='sum @0 1 0'/>
    <v:f eqn='sum 0 0 @1'/>
    <v:f eqn='prod @2 1 2'/>
    <v:f eqn='prod @3 21600 pixelWidth'/>
    <v:f eqn='prod @3 21600 pixelHeight'/>
    <v:f eqn='sum @0 0 1'/>
    <v:f eqn='prod @6 1 2'/>
    <v:f eqn='prod @7 21600 pixelWidth'/>
    <v:f eqn='sum @8 21600 0'/>
    <v:f eqn='prod @7 21600 pixelHeight'/>
    <v:f eqn='sum @10 21600 0'/>
   </v:formulas>
   <v:path o:extrusionok='f' gradientshapeok='t' o:connecttype='rect'/>
   <o:lock v:ext='edit' aspectratio='t'/>
  </v:shapetype><v:shape id='_x0000_s1025' type='#_x0000_t75' alt='' style='position:absolute;
   margin-left:2.25pt;margin-top:2.25pt;width:37.5pt;height:37.5pt;z-index:1'>
   <v:imagedata src='http://www.bestafford.com/$row[goods_thumb]'/>
   <x:ClientData ObjectType='Pict'>
    <x:SizeWithCells/>
    <x:CF>Bitmap</x:CF>
   </x:ClientData>
  </v:shape><![endif]--><![if !vml]><span style='mso-ignore:vglayout'>
  <table cellpadding=0 cellspacing=0>
   <tr>
    <td width=3 height=3></td>
   </tr>
   <tr>
    <td></td>
    <td><img width=50 height=50
    src='http://www.bestafford.com/$row[goods_thumb]'
    v:shapes='_x0000_s1025'></td>
    <td width=1></td>
   </tr>
   <tr>
    <td height=1></td>
   </tr>
  </table>
  </span><![endif]><!--[if !mso & vml]><span style='width:40.5pt;height:40.5pt'></span><![endif]--></td>
  <td class=xl27 width=262 style='border-top:none;border-left:none;width:197pt'>$row[goods_name]</td>
  <td class=xl24 width=72 style='border-top:none;border-left:none;width:54pt'>$row[goods_sn]</td>
  <td class=xl27 width=142 style='border-top:none;border-left:none;width:107pt'>$row[cat_id]</td>
  <td class=xl28 align=right width=67 style='border-top:none;border-left:none; width:50pt' x:num='40073'>$row[add_time]</td>
  <td class=xl27 align=right width=75 style='border-top:none;border-left:none; width:56pt' x:num>$row[wvera_price]</td>
  <td class=xl27 align=right width=49 style='border-top:none;border-left:none; width:37pt' x:num>$row[goods_num]</td>
  <td class=xl27 align=right width=70 style='border-top:none;border-left:none; width:53pt' x:num>$row[turnover]</td>
  <td class=xl24 width=66 style='border-top:none;border-left:none;width:50pt'>$row[add_user]</td>
 </tr>";

            //$data .= "$order_by\t$row[goods_thumb]\t$row[goods_name]\t$row[goods_sn]\t".$row['cat_id']."\t$row[add_time]\t$row[wvera_price]\t$row[goods_num]\t$row[turnover]\t$row[add_user]\n";
        }

/*        if (EC_CHARSET == 'utf-8')
        {
            echo ecs_iconv(EC_CHARSET, 'GB2312', $data);
        }
        else
        {
*/




 $data .=   " <tr height=54 style='mso-height-source:userset;height:40.5pt'>
  <td colspan=7 height=54 width=744 style='border-right:.5pt solid black;
  height:40.5pt;width:559pt' align=left valign=top><!--[if gte vml 1]><v:shape
   id='_x0000_s1026' type='#_x0000_t75' alt='' style='position:absolute;
   margin-left:56.25pt;margin-top:2.25pt;width:37.5pt;height:37.5pt;z-index:7'>
   <x:ClientData ObjectType='Pict'>
    <x:SizeWithCells/>
    <x:CF>Bitmap</x:CF>
   </x:ClientData>
  </v:shape><![endif]--><![if !vml]><span style='mso-ignore:vglayout;
  position:absolute;z-index:7;margin-left:75px;margin-top:3px;width:50px;
  height:50px'><img width=50 height=50 src='www.files/image001.gif' v:shapes='_x0000_s1026'></span><![endif]><span
  style='mso-ignore:vglayout2'>
  <table cellpadding=0 cellspacing=0>
   <tr>
    <td colspan=7 height=54 class=xl31 width=744 style='border-right:.5pt solid black;
    height:40.5pt;width:559pt'>总计：</td>
   </tr>
  </table>
  </span></td>
  <td class=xl27 align=right width=49 style='width:37pt' x:num>".$tj['goods_num']."</td>
  <td class=xl27 align=right width=70 style='width:53pt' x:num>".$tj['turnover']."</td>
  <td class=xl29 width=66 style='width:50pt'>　</td>
 </tr> ";



echo $data." <![if supportMisalignedColumns]>
 <tr height=0 style='display:none'>
  <td width=72 style='width:54pt'></td>
  <td width=54 style='width:41pt'></td>
  <td width=262 style='width:197pt'></td>
  <td width=72 style='width:54pt'></td>
  <td width=142 style='width:107pt'></td>
  <td width=67 style='width:50pt'></td>
  <td width=75 style='width:56pt'></td>
  <td width=49 style='width:37pt'></td>
  <td width=70 style='width:53pt'></td>
  <td width=66 style='width:50pt'></td>
 </tr>
 <![endif]>".'</table>';
      //  }


        exit;
    }

    $goods_order_data = get_sales_order();
    $Arr['goods_order_data'] = $goods_order_data['sales_order_data'];
    $Arr['filter'] =       $goods_order_data['filter'];
    $Arr['record_count'] = $goods_order_data['record_count'];
    $Arr['page_count'] =   $goods_order_data['page_count'];

    $Arr['start_date'] =       local_date('Y-m-d', $_REQUEST['start_date']);
    $Arr['end_date'] =         local_date('Y-m-d', $_REQUEST['end_date']);

    $sort_flag  = sort_flag($goods_order_data['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];

}
else
{
    /* 权限检查 */
    admin_priv('sales_report');
    $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
    $fangshi = empty($_REQUEST['fangshi']) ? 1 : intval($_REQUEST['fangshi']);
	$cat_priv= $_SESSION['WebUserInfo']['cat_priv'];//拥有的分类管理权限
	if(empty($cat_priv))$cat_priv='100000';//如果没如就设一个不存在的分类
    $Arr['cat_list'] = cat_list_is_show($cat_id,false,false,$cat_priv);
    //echo $Arr['cat_list'];
    $Arr['fangshi'] = $fangshi;
	$Arr['goods_grade_arr'] = read_static_cache('goods_grade_arr', 1);//产品等级


	if(!empty($_GET)){
		$goods_order_data = get_sales_order();
		//print_r($goods_order_data);
		$Arr['goods_order_data'] = $goods_order_data['sales_order_data'];
		$Arr['tj']               = $goods_order_data['tj'];

		$sort_flag           = sort_flag($goods_order_data['filter']);
		$Arr[$sort_flag['tag']] = $sort_flag['img'];
		$goods_order_data['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
		$Arr['filter'] = $goods_order_data['filter'];
    //print_r($Arr['filter']);
		$page=new page(array('total'=>$goods_order_data['record_count'],'perpage'=>$goods_order_data['page_size']));

		$Arr["pagestr"]  = $page->show();
	}
	$sql = 'select add_user from '.GOODS.' where add_user <> "" group by add_user ORDER BY binary add_user asc ';
	$Arr['users'] = $db->arrQuery($sql);
    $Arr['start_date'] =       local_date('Y-m-d', $_REQUEST['start_date']);
    $Arr['end_date'] =         local_date('Y-m-d', $_REQUEST['end_date']);
    $Arr['start_date2'] =      local_date('Y-m-d', $_REQUEST['start_date2']);
    $Arr['end_date2'] =        local_date('Y-m-d', $_REQUEST['end_date2']);
    $Arr['goods_sn'] =         $_REQUEST['goods_sn'];
    $Arr['order_num'] =        $_REQUEST['order_num'];
    $Arr['order_num'] =        $_REQUEST['order_num'];
    $Arr['price1']    =        $_REQUEST['price1'];
    $Arr['price2']    =        $_REQUEST['price2'];
    $Arr['price3']    =        $_REQUEST['price3'];
    $Arr['price4']    =        $_REQUEST['price4'];

	$Arr["search_url"] = get_url_parameters($_GET,array('sort_order','sort_by'));
	$search_url2 = '?act=download&sort_by='.$_REQUEST['sort_by'].'&sort_order='.$_REQUEST['sort_order'].$Arr["search_url"];

    $Arr['action_link'] =      array('text' => '销售排行报表下载', 'href' => $search_url2 );

    //活动
    $sql = 'select * from eload_activity where type=1 ORDER BY id DESC';
	$Arr['activity_putong'] = $db->arrQuery($sql);
    $sql = 'select * from eload_activity where type=2 ORDER BY id DESC';
	$Arr['activity_guding'] = $db->arrQuery($sql);

}

/*------------------------------------------------------ */
//--排行统计需要的函数
/*------------------------------------------------------ */
/**
 * 取得销售排行数据信息
 * @param   bool  $is_pagination  是否分页
 * @return  array   销售排行数据
 */
function get_sales_order($is_pagination = true)
{
  global $db;
  $isping = '';

  if($_SESSION["WebUserInfo"]["sa_user"] == 'haoren'){
       $isping = ' and oi.is_ping = 0 ';
  }else{
       $isping = ' and oi.is_dao = 0 ';
  }
  $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
  $filter['end_date']   = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
  $filter['start_date2'] = empty($_REQUEST['start_date2']) ? '' : $_REQUEST['start_date2'];
  $filter['end_date2']   = empty($_REQUEST['end_date2']) ? '' : $_REQUEST['end_date2'];
  $ORDER_BY=$filter['sort_by']    = empty($_GET['sort_by']) ? 'add_time' : trim($_GET['sort_by']);
  $updown=$filter['sort_order'] = empty($_REQUEST['sort_order']) ? '' : trim($_REQUEST['sort_order']);
  $filter['cat_id']     = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
  $filter['add_user']   = empty($_REQUEST['add_user'])?'':$_REQUEST['add_user'];
  $filter['intro_type'] = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);
  $filter['payment_status'] = empty($_REQUEST['payment_status']) ? '' : trim($_REQUEST['payment_status']);
  $filter['activity'] = empty($_REQUEST['activity']) ? '' : trim($_REQUEST['activity']);
  $filter['orderby'] = empty($_REQUEST['orderby']) ? 'adddate' : trim($_REQUEST['orderby']);
  $filter['updown'] = empty($_REQUEST['updown']) ? '' : trim($_REQUEST['updown']);
  $filter['cat_ext'] = empty($_REQUEST['cat_ext']) ? 0 : $_REQUEST['cat_ext']; //统计拓展产品
  $Arr['cat_ext'] = $filter['cat_ext'];
  $filter['price1'] = $_REQUEST['price1'];
  $filter['price2'] = $_REQUEST['price2'];
  $cat_priv= $_SESSION['WebUserInfo']['cat_priv'];//拥有的分类管理权限
  $allow_cat_id='';
  if(!empty($cat_priv)){
     $priv_cat_big_arr = explode(',',$cat_priv);
     $category_children = read_static_cache('category_children', 2);    //顶级分类
     foreach ($priv_cat_big_arr as $k=>$v){
     		if($v&&!empty($category_children[$v]['children'])){
      		$allow_cat_id.=$v.",";
      		array_push($category_children[$v]['children'],$v);
      		$allow_cat_id.=implode(',',$category_children[$v]['children']).",";
      	}}
      	$allow_cat_id.="0";
  }
  $allow_cat_id.='0';
  $filter['allow_cat_id'] = $allow_cat_id;
  $where = " WHERE og.order_id = oi.order_id  and g.goods_id = og.goods_id $isping ";
  if($filter['orderby'] == 'conversion_rate') $where .=" and c.goods_id = g.goods_id ";
  //订单状态
  if ($filter['payment_status']=="no_payment")
  {
      $where .= " AND oi.order_status = 0 ";
  }
  elseif ($filter['payment_status']=="yes_payment")
  {
      $where .= " AND oi.order_status > 0 AND oi.order_status < 9 ";
  }
  else
  {
      $where .= " AND oi.order_status >= 0 AND oi.order_status < 9 ";
  }


  //活动
  if ($filter['activity'])
  {
    $sql = 'SELECT * FROM eload_activity WHERE id='.$filter['activity'];
    $activity_info = $db->selectinfo($sql);
		if($activity_info['type'] != 2){

   			if(!empty($activity_info['act_goods_list'])){
	        	$goods_list_sn = "'".str_replace(',',"','",$activity_info['act_goods_list'])."'";
	            $where .= " AND g.goods_sn in($goods_list_sn)  ";
   			}else{
   				$where .= " AND activity_list LIKE '%,".$filter['activity'].",%'  ";
   			}
		} else{

         		 $where .= " AND activity_list LIKE '%,".$filter['activity'].",%'  ";
		}
  }
  //分类权限
	if($filter['intro_type'] != 'with_main_goods_id') $where .= " AND g.cat_id in(".$filter['allow_cat_id'].")";
  $join_tuijian = null;
  /* 推荐类型 */
  switch ($filter['intro_type'])
  {
      case 'is_free_shipping':
          $where .= " AND g.is_free_shipping=1";
           break;
      case 'is_best':
          $where .= " AND g.is_best=1";
           break;
     case 'on_sale':
          $where .= " AND g.is_on_sale=1";
          break;
      case 'not_on_sale':
          $where .= " AND g.is_on_sale=0";
          break;
      case 'is_hot':
          $where .= ' AND g.is_hot=1';
      case 'is_bighot':
          $where .= ' AND g.is_bighot=1';
          break;
      case 'is_new':
          $where .= ' AND g.is_new=1';
          break;
      case 'is_promote':
          $where .= " AND g.is_promote = 1 ";
          break;
      case 'is_gift':
          $where .= " AND g.gifts_id >0 and goods_price=0";
          break;
      case 'multi_points':
          $where .= " AND g.point_rate >1";
          break;
      case 'all_type';
          $where .= " AND (g.is_best=1 OR g.is_hot=1 OR g.is_new=1 OR g.is_promote = 1)";
      case 'with_main_goods_id';

      	if(empty($filter['cat_id'])){
      		 $where .= " AND og.main_goods_id>0  ";
      	}else {
      		 $where .= " AND og.main_goods_id in (select goods_id from eload_goods where cat_id in(".new_get_children($filter['cat_id']).")) ";
      	}


          break;
      case 'is_super_star'://super star by mashanling on 2013-02-27 13:50:01
          $where .= ' AND t.goods_id=g.goods_id AND t.is_super_star=1';
          $join_tuijian = ',' . GOODSTUIJIAN . ' AS t ';
          break;
    }

    $subwhere = ' ';

    if ($filter['start_date'])
    {
        $where .= " AND oi.add_time >= '" . $filter['start_date'] . "'";
        $subwhere .= " AND daytime >= '" . $filter['start_date'] . "'";
    }
    if ($filter['end_date'])
    {
        $where .= " AND oi.add_time <= '" . $filter['end_date'] . "'";
        $subwhere .= " AND daytime <= '" . $filter['end_date'] . "'";
    }

	if ($_REQUEST['goods_sn'])
	{

		$goods_sn=str_replace(' ','',$_REQUEST['goods_sn']);
		if(!empty($goods_sn)){
			$goods_sn=str_replace(',',"','",$goods_sn);
			$goods_sn ="'".$goods_sn."'";
			//die($goods_sn);
			//$goods_sn=explode(',',$goods_sn);
	        $where .= " AND g.goods_sn in($goods_sn)";
		}
	}
	if ($_REQUEST['keywords'])
	{
	        $where .= " AND g.goods_title like '%$_REQUEST[keywords]%'";

	}

	if ($_REQUEST['price1'])
	{
        $where .= " AND g.shop_price >= '" .$_REQUEST['price1'] . "'";
	}
	if ($_REQUEST['price2'])
	{
        $where .= " AND g.shop_price <= '" .$_REQUEST['price2'] . "'";
	}


	if ($_REQUEST['price3'])
	{
        $where .= " AND og.goods_price  >= '" .$_REQUEST['price3'] . "'";
	}
	if ($_REQUEST['price4'])
	{
        $where .= " AND og.goods_price <= '" .$_REQUEST['price4'] . "'";
	}

	if ($_REQUEST['order_num'] > 0)
	{
		$tempArr = array();
		$sql = " select user_id from eload_order_info where order_status >0 AND order_status <9  group by user_id HAVING count(order_id) >= '".$_REQUEST['order_num']."' ";
		$gArr = $db->arrQuery($sql);
		if (!empty($gArr)){
			foreach($gArr as $val){
				$tempArr[] = $val['user_id'];
			}
			$where .= " and user_id ".db_create_in($tempArr);
		}else{
			$where .= " and user_id  in ('0')";
		}
	}
	//add_time >= '" . $filter['start_date'] . "' and add_time <= '" . $filter['end_date'] . "' exists
    if ($filter['start_date2'])
    {
        $where .= " AND g.add_time >= '" . $filter['start_date2'] . "'";
    }
    if ($filter['end_date2'])
    {
        $where .= " AND g.add_time <= '" . $filter['end_date2'] . "'";
    }
    $ext_good_str = '';
    if ($filter['cat_id'] && $filter['intro_type'] != 'with_main_goods_id')
    {
		    $children = get_children($filter['cat_id']);        
        if($filter['cat_ext']){
            $ext_sql = 'select goods_id from '.GOODSCAT.' where cat_id = '.$filter['cat_id'];
            $ext_goods = $db->getCol($ext_sql);
            $ext_good_str = implode(',', $ext_goods);
        }        
        //统计拓展产品
        if ($filter['cat_ext'] && $ext_good_str)
        {
          $where .= " AND ($children  OR og.goods_id in ($ext_good_str)) AND og.main_goods_id =0";
        } else {
          $where .= " AND $children ";          
        }      
    }

    if ($filter['add_user'])
    {
        $where .= " AND g.add_user = '" . $filter['add_user'] . "'";
    }

    //排序
    if ($filter['orderby'])
    {
        switch ($filter['orderby'])
        {
            case 'add_time'://上架时间
                $ORDER_BY = " g.add_time ";
                break;
            case 'click_count':
                $ORDER_BY = " click_count ";
                break;
            case 'adduser':
                $ORDER_BY = " g.add_user ";
                break;
            case 'anclassid':
                $ORDER_BY = " g.cat_id ";
                break;
            case 'totalqty':
                $ORDER_BY = " goods_num ";
                break;
            case 'totalmoney':
                $ORDER_BY = " turnover ";
                break;
            case 'conversion_rate':
                $ORDER_BY = " conversion_rate ";
                break;

        }
    }
    if ($filter['updown']=="desc")
    {
        $updown = " DESC ";
    }
    else
    {
        $updown = " ASC ";
    }

    $sql = "SELECT COUNT(distinct(og.goods_id)) FROM " .
           ORDERINFO . ' AS oi,'.
           GOODS . ' AS g,'.
           ODRGOODS . ' AS og  ';
    if($filter['orderby']=='conversion_rate')$sql .= ",eload_goods_conversion_rate as c "     ;
    $sql .= isset($join_tuijian) ? $join_tuijian : '';
    $sql .=  $where;
	  //echo $sql; exit;
    $filter['record_count'] = $db->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "SELECT og.goods_id, g.goods_sn, g.goods_title, g.goods_thumb,g.goods_number as stock,(select sum(hitnum) from ".GOODS_HITS." where goods_id = og.goods_id $subwhere ) as click_count , count(oi.order_id) as order_num,  g.cat_id,g.add_time,g.add_user, oi.order_status, " .
           "SUM(og.goods_number) AS goods_num, SUM(og.goods_number * og.goods_price) AS turnover ".
           "FROM ".ODRGOODS." AS og, ".GOODS." AS g " ;

    if($filter['orderby']=='conversion_rate')$sql .= ",eload_goods_conversion_rate as c "     ;
    $sql .= isset($join_tuijian) ? $join_tuijian : '';
    $sql .= ',' . ORDERINFO." AS oi  " .$where .
           " GROUP BY og.goods_sn ".
           ' ORDER BY ' . $ORDER_BY . ' ' . $updown ;
           //echo $ORDER_BY;
          // exit();

    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
	if(!empty($_GET['test']))echo $sql;

    $sales_order_data = $db->getAll($sql);
   // print_r($GLOBALS['db']);
    $catArr = read_static_cache('category_c_key',2);
    $top_20_shiji = $num = 0;
    foreach ($sales_order_data as $key => $item)
    {
        $num++;
        $sales_order_data[$key]['wvera_price'] = price_format($item['goods_num'] ? $item['turnover'] / $item['goods_num'] : 0);
        $sales_order_data[$key]['short_name']  = sub_str($item['goods_title'], 30, true);
        $sales_order_data[$key]['goods_name']  = $item['goods_title'];
        $sales_order_data[$key]['add_time']    =  local_date('Y-m-d', $item['add_time']);
        $sales_order_data[$key]['turnover']    = price_format($item['turnover']);
        $sales_order_data[$key]['cat_id']      = empty($catArr[$item['cat_id']]['cat_name'])?'':$catArr[$item['cat_id']]['cat_name'];
        $sales_order_data[$key]['taxis']       = $key + 1;
         $sales_order_data[$key]['goods_thumb']       = get_image_path( $sales_order_data[$key]['goods_id'],$sales_order_data[$key]['goods_thumb']);
		//echo $sales_order_data[$key]['click_count'].'<br>';

        $sales_order_data[$key]['con_lv'] = !empty($sales_order_data[$key]['click_count'])?round($item['goods_num']/$sales_order_data[$key]['click_count'],4)*100 :0;
        $sales_order_data[$key]['shiji_con_lv'] = !empty($sales_order_data[$key]['click_count'])?round($item['order_num']/$sales_order_data[$key]['click_count'],4)*100 :0;

        $sales_order_data[$key]['click_count'] = $item['click_count'];
        $top_20_shiji += $sales_order_data[$key]['shiji_con_lv'];    //前20位实际转换率总和
    }
	unset($catArr);

	$sql = "select SUM(og.goods_number) as goods_num,SUM(og.goods_number*og.goods_price) as goods_price  ".
           "FROM ".ODRGOODS." AS og, ".GOODS." AS g ";
	if($filter['orderby']=='conversion_rate')$sql .= ",eload_goods_conversion_rate as c "     ;
    $sql .= isset($join_tuijian) ? $join_tuijian : '';
        $sql .= ',' . ORDERINFO." AS oi  " .$where ;
	$tj = $db->selectinfo($sql);

	$tj['wvera_price'] = $tj['goods_num']?number_format($tj['goods_price']/$tj['goods_num'],2):0;

	$is_pagination && $num > 0 && ($tj['top_20_shiji_con_lv'] = empty($top_20_shiji) ? 0: round($top_20_shiji / $num, 2));


	$tempArr = array();
	$sql = " SELECT og.goods_id FROM ".ODRGOODS." AS og, ".GOODS." AS g " ;
	if($filter['orderby']=='conversion_rate')$sql .= ",eload_goods_conversion_rate as c " ;
    $sql .= isset($join_tuijian) ? $join_tuijian : '';
	$sql .=  ',' . ORDERINFO." AS oi  " .$where ." GROUP BY og.goods_sn ";
	$gArr = $db->arrQuery($sql);
	if (!empty($gArr)){
		foreach($gArr as $val){
			$tempArr[] = $val['goods_id'];
		}
		$yywhr = " goods_id ".db_create_in($tempArr);
	}else{
		$yywhr = ' 1 ';
	}

	$sql = "select sum(hitnum) from eload_goods_hits where $yywhr $subwhere ;";
	$tj['goodshits'] = $GLOBALS['db']->getOne($sql);


	//print_r($tj);
/*	$sql = "select  oi.order_amount  ".
           "FROM ".ODRGOODS." AS og, ".GOODS." AS g, ".
           ORDERINFO." AS oi  " .$where .'  group by oi.order_id ';
		  // echo $sql;
	$tjArr = $GLOBALS['db']->arrQuery($sql);
	$tj['turnover'] = 0;
	foreach($tjArr as $val){
		$tj['turnover'] = $tj['turnover'] + $val['order_amount'];
	}
	$tj['turnover'] = round($tj['turnover'],2);
*/
    $arr = array('sales_order_data' => $sales_order_data, 'filter' => $filter,'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count'],'tj'=>$tj);
    return $arr;
}
$_ACT = 'sales_report';
temp_disp();//

?>