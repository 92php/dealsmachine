<?
define('INI_WEB', true);
require("../lib/global.php");

$cat_id     = empty($_GET['cat_id'])?0:intval($_GET['cat_id']);         //fenlei
$type_id    = empty($_GET['type_id'])?0:intval($_GET['type_id']);       //leixing
$pernum     = empty($_GET['pernum'])?20:intval($_GET['pernum']);        //meiye                       
$page       = empty($_GET['page'])?1:intval($_GET['page']);
	
if (!empty($_GET['act'])){
	
    //指定属性的子项，
/*   $zhiding_shuxing = array('Female　S',
							 'Female　M',
							 'Female　L',
							 'Female　XL',
							 'Female　2XL',
							 'Male　S',
							 'Male　M',
							 'Male　L',
							 'Male  XL',
							 'Male  2XL',								
							 'custom made');
*/
     //指定属性ID
   // $zd_attr        = array('23','36');
  //  $zd_attr_money  = array('23'=>array('1GB'=>'4.5','2GB'=>'5.1','4GB'=>'8','8GB'=>'16.3'),'36'=>array('CAR CHARGER'=>'2','LEATHER CASE'=>'2','Srceen protector'=>'1','Emergency Charger'=>'2.95'));
	

	$children   =  get_children($cat_id);
	$where      = ' where '.$children;
	
	$total_record = $db->getOne("SELECT count(goods_id) FROM " . GOODS ." as g $where ");
	$total_page   = ceil($total_record/$pernum);                            //zong ye shu
	$start        = ($page - 1) * $pernum;
	if($page>$total_page) {echo '全部完成!';exit;}
	
	if($page == 1) $db->query(" update ".GOODS." as g set goods_type = '$type_id' $where ");
	
	$type_attr = gettypeattr($type_id);
	$sql = "select goods_id from ".GOODS." as g $where LIMIT   $start ,$pernum";
	$goodsArr = $db->arrQuery($sql);
	foreach ($goodsArr as $row){
		//goods_id  attr_id  attr_value 
		$goods_id = $row['goods_id'];
	foreach($type_attr as $key => $tw){
			$attr_id = $tw['attr_id'];
		//if (in_array($attr_id,$zd_attr)){
			
		$sql = "DELETE FROM " .GATTR. " WHERE goods_id = '$goods_id' and attr_id = '$attr_id' ";
		$res = $db->query($sql);
		    $attr_values = explode("\n", $tw['attr_values']);
            foreach ($attr_values AS $opt)
            {
				$opt    = trim(htmlspecialchars($opt));
	            $price  = '';//$zd_attr_money[$attr_id][$opt];
				//if (in_array($opt,$zhiding_shuxing)){
					$sql = "INSERT INTO " .GATTR. " (attr_id, goods_id, attr_value,attr_price)".
							"VALUES ('$attr_id', '$goods_id', '$opt','$price')";
					if($db->query($sql)){
						//echo $goods_id.'添加完成。<br>';
					//}else{
						//echo $goods_id.'添加失败。<br>';
					}
			   //}
			}
		//}
	}
}
	
	
	
	
	
	//print_r($goodsArr);
	echo '第'.$page.'页,记录数从'.$start.' 到 '.($start + $pernum). ' 处理完成。 ';
	$page++;
	echo "<br> <script language='javascript'>doajax('addattr.php?act=add&cat_id=$cat_id&page=$page&pernum=$pernum&type_id=$type_id');
</script>";
	
	
}else{
	
	  
	
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>分类下的产品增加属性</title>
<link href='{$jscache_url}temp/skin3/eload_admin/images/admin_css.css' rel='stylesheet' type='text/css'/>
<script language="javascript" src="/sysjs/eload13pack.js" type="text/javascript"></script>
<script language="javascript" src="/sysjs/validator.js"></script>
<script language="javascript">
function doajax(surl){
	$.ajax({
		type: "GET",
		url: surl,
		//beforeSend:function(){toploadshow();toploadhide();},
		success: function(msg){$("div").append(msg);} 
	});
}

function checkform(){
		var cat_id = $('#cat_id').val();
		var type_id = $('#type_id').val();
		doajax('addattr.php?act=add&cat_id='+cat_id+'&type_id='+type_id);
}

function toploadshow(){
	$("#load-div").show();
}
function toploadhide(){
	$("#load-div").hide();
}
	
//doajax('addattr.php?act=add&cat_id=<?=$cat_id?>&page=<?=$page?>&pernum=<?=$pernum?>&type_id=<?=$type_id?>');
</script>
</head>

<body>
<form  name="myform" >
<table width="99%" align="center" cellspacing="1"  bgcolor="#FFFFFF" class="borderline"  id="stripe_tb">
  <tr>
    <th colspan="2">批量加属性</th>
  </tr>
  <tr>
    <td width="30%" align="right">请选择分类：</td>
    <td width="70%">
    <select name="cat_id" id="cat_id" dataType="Require" msg="请选择分类！" class="input_style" >
      <option>请选择...</option>
    <?=cat_list();?>
    </select>
    </td>
  </tr>
  <tr>
    <td align="right">请选择属性类型：</td>
    <td>
    <select name="type_id" id="type_id"  dataType="Require" msg="请选择属性类型！" class="input_style">
       <option>请选择...</option>
	   <?=goods_type_list(); ?>
    </select>
</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
        <td><input type="button" value="修改" class="sub_but" onclick="checkform();"/></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><span id="load-div" style="padding: 5px 10px 0 0; color: #FF9900; display:none;"><img src="/temp/skin3/images/top_loader.gif" style="vertical-align: middle" />   正在处理，请稍等......</span></td>
  </tr>
</table>
</form>


<div style="line-height:20px; font-size:12px; padding-left:20px;">

</div>
</body>
</html>
<?
}

function gettypeattr($type_id){
	$sql = "SELECT attr_id,attr_values FROM " .ATTR. "  WHERE cat_id='$type_id'";
	return $GLOBALS['db']->arrQuery($sql);
}
?>