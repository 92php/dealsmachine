$(document).ready(function(){
    adminDelete();
	//添加产品
	$(".goods_cat").livequery('change',function(){
        var item = $(this)
		var cat_id = item.val();
		get_child_list(item,cat_id,'get_goods_child_list');
	});


	function get_child_list(item,cat_id,act){
		act = act?act:'get_child_list';
		$.ajax({
			type: "GET",
			cache:false,
			url: "category.php?act="+act+"&cat_id=" + cat_id,
			//beforeSend:function(){toploadshow();}, 
			success: function(msg){
				//toploadhide();
				item.nextAll().remove('.goods_cat');
				item.after(msg);
			}
		});
	}
	

	//选择扩展分类
	$(".OtherCat").livequery('change',function(){
        var item = $(this)
		var cat_id = item.val();
		get_ext_child_list(item,cat_id);
	});
	
	//取得扩展分类子类
	function get_ext_child_list(item,cat_id){
		yy = item.attr('ectype');
		$.ajax({
			type: "GET",
			cache:false,
			url: "category.php?act=get_target_cat_list&cat_id=" + cat_id+"&n="+yy,
			beforeSend:function(){toploadshow();}, 
			success: function(msg){
				toploadhide();
				item.nextAll().remove('*[ectype="'+yy+'"]');
				item.after(msg);
			}
		});
	}

	$('.del_act').livequery("click",function (){
	   delmsg = ($(this).attr('delmsg')!=undefined)?$(this).attr('delmsg'):'您确实要删除吗？'; //确认信息
	   if(confirm(delmsg)){return true;}else{ return false;}
	});
	
    //分页每页多少条记录
	$("#pageSize").livequery("blur",function (e) {
		ps      = parseInt($(this).val());
		if (!ps) return false;
		page_url = $(this).attr("atr");
		document.cookie = "WEB[page_size]=" + ps + ";";
		window.location.href = page_url;
		return false;
	 })
	
	
	
	//产品搜索
	$("#seach_btn").livequery("click",function () {
		var cat_id     = $("#cat_id").val();
		var intro_type = $("#intro_type").val();
		var keyword = $("#keyword").val();
		var act = $("#actx").val();
		page_url = "goods.php?cat_id="+cat_id+"&intro_type="+intro_type+"&keyword="+keyword+"&act="+act
		list_load(page_url);
	});
	
	
	//评论搜索
	$("#comment_seach_btn").livequery("click",function () {
		var keyword = $("#keyword").val();
		page_url = "comment_manage.php?keywords="+keyword;
		list_load(page_url);
	});
	
	$("#user_btn").livequery("click",function () {
		var user_rank     = $("#user_rank").val();
		var keyword = $("#keyword").val();
		page_url = "users.php?user_rank="+user_rank+"&keyword="+keyword;
		list_load(page_url);
	});
	
	$("#article_search").livequery("click",function () {
		var cat_id     = $("#cat_id").val();
		var keyword = $("#keyword").val();
		page_url = "article.php?cat_id="+cat_id+"&keyword="+keyword;
		list_load(page_url);
	});
	
	$("#abc_search").livequery("click",function () {
		var keyword = $("#keyword").val();
		page_url = "abcindex.php?keyword="+keyword;
		list_load(page_url);
	});
	
	
	
/*	//全选功能
	$("#selAction").attr("disabled",true);
	$("input[id='all_select[]']").livequery("click",function(){
		if($(this).attr("checked") == true){ 
			$("input[class*="+this["className"]+"]").each(function(){
				$(this).attr("checked",true);  
				$("#btnSubmit").attr("disabled",false);
				$("#btnSubmit1").attr("disabled",false);
				$("#btnSubmit2").attr("disabled",false);
				$("#btnSubmit3").attr("disabled",false);
				$("#selAction").attr("disabled",false);
			});
		}else{
			$("input[class*="+this["className"]+"]").each(function(){
				$(this).attr("checked",false);
				$("#btnSubmit").attr("disabled",true);
				$("#btnSubmit1").attr("disabled",true);
				$("#btnSubmit2").attr("disabled",true);
				$("#btnSubmit3").attr("disabled",true);
				$("#selAction").attr("disabled",true);
		    });
		}
	});
	$(".cls").livequery("click",function(){
		if($(this).attr("checked") == true){ 
				$("#btnSubmit").attr("disabled",false);
				$("#btnSubmit1").attr("disabled",false);
				$("#btnSubmit2").attr("disabled",false);
				$("#btnSubmit8").attr("disabled",false);
				$("#btnSubmit3").attr("disabled",false);
				$("#selAction").attr("disabled",false);
		}else{
			if($(".cls:checked").length<=0){
				$("#btnSubmit").attr("disabled",true);
				$("#btnSubmit1").attr("disabled",true);
				$("#btnSubmit2").attr("disabled",true);
				$("#btnSubmit3").attr("disabled",true);
				$("#btnSubmit8").attr("disabled",true);
				$("#selAction").attr("disabled",true);}
		}
	});
*/    
	
	//商品属性选择类型时跳转
	$(".select_type").change(function(){
		page_url  = $(this).attr("atr");
		pageno    = $(this).val();
		if (pageno!=0){
			list_load(page_url+pageno);
		}
	})

	//列表页ajax
/*	$("a").livequery("click",function(){
		page_url = $(this).attr("atr");
		if (page_url!=undefined){
			$.cookie('page_url', page_url, {expires: 7, path: '/'});
			list_load(page_url);
		}
	});

*/	


	$("#PB_Page_Select").livequery("change",function(){
		page_url  = $("#PB_Page_Select").attr("atr");
		pageno    = $("#PB_Page_Select").val();
		list_load(page_url+pageno);
	});
	
	$("#email_batch_send_type").livequery("change",function(){
	    pageno    = $("#PB_Page_Select").val();
		page_url  = window.location.href+'?tid='+pageno
		list_load(page_url);
	});

	$("input[id='pay_checkes[]']").livequery("click",function(){
		page_url = $(this).attr("atr");
		if (page_url!=undefined){
			$.ajax({
				type: "GET",
				cache:false,
				url: page_url,
				beforeSend:function(){toploadshow();}, 
				success: function(msg){
					toploadhide();
				} 
			});
		}
	});
	
	
	
	
	
	//改变订单状态
	$(".change_order_status").livequery("click",function(){
		var order_id     = $(this).attr('order_id');												 
		var order_status = $(this).attr('order_status');
		var Tracking_NO = $('#shipmentNO'+order_id).val() == undefined?'':$('#shipmentNO'+order_id).val();
		var shipping_method = $('#track'+order_id).val() == undefined?'':$('#track'+order_id).val();
		page_url = 'order.php?act=list&order_id='+order_id+'&status='+order_status+'&Tracking_NO='+Tracking_NO+'&shipping_method='+shipping_method;
		
		$.ajax({
			type: "GET",
			url: page_url,
			cache:false,
			beforeSend:function(){toploadshow();}, 
			success: function(msg){
				if(msg.indexOf('success||')>-1)alert('邮件发送成功');
				if(msg.indexOf('fails||')>-1)alert('邮件发送失败，请检查原因！');
				toploadhide();
				//var stext = $(msg).filter('#list').html();
				var stext = $(msg).find('div#list').html();
				$('div#list').html(stext);
				
			} 
		});
		
		
		
	});
	
	
	
	$(".ctrl").livequery("click",function (){
		var cat_id = $(this).attr('cat_id');
		var state = $(this).attr('state');
		var padding = parseInt($(this).attr('padding'))+1;
		if(state == "close"){
			$.ajax({
				type: "GET",
				cache:false,
				url: "category.php?cid="+cat_id+"&padding="+padding,
				beforeSend:function(){toploadshow();}, 
				success: function(msg){
					toploadhide();
					var stext       = $(msg).find('#tbody_id').html();
					
					 $("#cat_tab_tr"+cat_id).after(stext);
					 $("#ctrl"+cat_id).attr('state','open');
					 $("#ctrl"+cat_id).attr('hit','2');
					 $("#ctrl"+cat_id).attr('src','/temp/skin3/eload_admin/images/tv-collapsable.gif');
					  
				}  
			});
		}else{
		   var hit = $("#ctrl"+cat_id).attr('hit');
		   if(hit % 2 == 0){
			   $(".parentid"+cat_id).hide("slow");
			    $("#ctrl"+cat_id).attr('src','/temp/skin3/eload_admin/images/tv-expandable.gif');
		   }else{
			   $(".parentid"+cat_id).show("slow");
			   $("#ctrl"+cat_id).attr('src','/temp/skin3/eload_admin/images/tv-collapsable.gif');
		   }
		   hit++;
		  $("#ctrl"+cat_id).attr('hit',hit);
			   
		}
	});
	
	$(".ctrl_area").livequery("click",function (){
		var cat_id = $(this).attr('cat_id');
		if($(".state_"+cat_id).css('display')=='none'){
			$(".state_"+cat_id).show();
		}else{
			$(".state_"+cat_id).hide();
		}

	});
	
	
	
	

});	

var batch_trash_confirm = "您确实要把选中的商品放入回收站吗？";
var batch_no_on_sale = "您确实要将选定的商品下架吗？";
var batch_drop = "您确实要将选定的商品彻底删除吗？";
var batch_add_attr = "您确实要将选定的商品添加属性吗？";
var batch_del_str  = "您确实要删除选定的商品推荐信息吗？";
function changeAction()
{
  var frm = document.forms['listForm'];

  // 切换分类列表的显示
	if (frm.elements['type'].value == 'move_to' || frm.elements['type'].value == 'fenlei_tuijian'){
	  $('#target_cat').show();
	}else{ 
	  $('#target_cat').hide();
	}
  
  
  if(frm.elements['type'].value == 'fenlei_tuijian'){
	  $('#showshuxing').show();
  }else{
	  $('#showshuxing').hide();
  }
  //add set point_rate
  if(frm.elements['type'].value == 'jifen_bilv'){
	  $('#setpoint_rate').show();
  }else{
	  $('#setpoint_rate').hide();
  }
 
  //批量促销
  if(frm.elements['type'].value == 'batch_promote'){
  	  $('#target_promote').show();
  }else{
	 $('#target_promote').hide();
  }
  
  //批量折扣
  if(frm.elements['type'].value == 'batch_discount_rate'){
  	  $('#target_discount_rate').show();
  }else{
	 $('#target_discount_rate').hide();
  }  
  
  frm.elements['other_cat[]'].style.display = frm.elements['type'].value == 'add_to' ? '' : 'none';
  frm.elements['type_id'].style.display = frm.elements['type'].value == 'batch_add_attr' ? '' : 'none';
  
  if (!$('#btnSubmit').disabled && confirmSubmit(frm, false))
  {
	  frm.submit();
  }
}
/**
* @param: bool ext 其他条件：用于转移分类
*/
function confirmSubmit(frm, ext)
{	
  if (frm.elements['type'].value == 'trash')
  {
	  return confirm(batch_trash_confirm);
  }
  
  else if (frm.elements['type'].value == 'del_tuijian')
  {
	  return confirm(batch_del_str);
  }
  else if (frm.elements['type'].value == 'drop')
  {
	  return confirm(batch_drop);
  }
  else if (frm.elements['type'].value == 'not_on_sale')
  {
	  return confirm(batch_no_on_sale);
  }
  else if (frm.elements['type'].value == 'move_to' || frm.elements['type'].value == 'fenlei_tuijian' || frm.elements['type'].value == 'batch_discount_rate') 
  {
	  ext = (ext == undefined) ? true : ext;
	  return ext && frm.elements['target_cat'].value != 0;
  }
  //add judge 
    else if (frm.elements['type'].value == 'jifen_bilv')
  {
	  ext = (ext == undefined) ? true : ext;
	  return ext && frm.elements['setpoint_rate'].value != 0;
  }
  else if (frm.elements['type'].value == 'add_to')
  {
	  ext = (ext == undefined) ? true : ext;
	  return ext && frm.elements['other_cat[]'].value != '';
  }
  else if (frm.elements['type'].value == 'batch_add_attr')
  {
	  ext = (ext == undefined) ? true : ext;
	  return ext && frm.elements['type_id'].value != '0';
  }
  else if(frm.elements['type'].value == 'batch_promote')
  {	  
	  if(ext == undefined){		  	
			var market_price = $('#market_price').val();
			if(market_price !=""){
				if(market_price*1<=0 || isNaN(market_price)){
					alert('抱歉，市场售价必须为大于0的数字！');
					return false;
				}
			}
			
			var promote_value = $('#promote_rate').val();
			if(isNaN(promote_value) || promote_value*1<=0){
				alert('抱歉，促销利润率必须为大于0的数字！');
				return false;
			}
			var pDateBegin = $('#promote_start_date').val();
			var pDateEnd = $('#promote_end_date').val();
			if(pDateBegin == "" || pDateEnd == ""){
				alert('抱歉，促销的起止时间不能为空！');
				return false;
			}
			var dateBegin = pDateBegin.replace(/-/g,'');
			var dateEnd = pDateEnd.replace(/-/g,'');
			if(dateBegin*1 > dateEnd*1){
				alert('抱歉，促销的起始时间不能大于结束时间！');
				return false;
			}
			return true;
	  }
	  return false; 
  }
  else if (frm.elements['type'].value == '')
  {
	  return false;
  }
  else
  {
	  return confirm("确实要进行该操作吗？");;
  }
}




//订单提交时将订单号拼成以逗号隔开的

function check()
{
  var snArray = new Array();
  var eles = document.forms['listForm'].elements;
  for (var i=0; i<eles.length; i++)
  {
	if (eles[i].tagName == 'INPUT' && eles[i].type == 'checkbox' && eles[i].checked && eles[i].value != 'on')
	{
	  snArray.push(eles[i].value);
	}
  }
  if (snArray.length == 0)
  {
	return false;
  }
  else
  {
	eles['order_id'].value = snArray.toString();
	return true;
  }
}


 //为防止数据多的情况下导致客户端死机的问题，$("a").livequery("click",function()函数改写成
 function loadAjax(page_url){
	if (page_url!=undefined){
		$.cookie('page_url', page_url, {expires: 7, path: '/'});
		list_load(page_url);
	}
 }
 
 function list_load(page_url){
	$.cookie('page_url', page_url, {expires: 7, path: '/'});
	$.ajax({
		type: "GET",
		url: page_url,
		cache:false,
		beforeSend:function(){toploadshow();}, 
		success: function(msg){
			//alert(msg);
			toploadhide();
			//var stext = $(msg).filter('#list').html();
			var stext = $(msg).find('div#list').html();
			$('div#list').html(stext);
			
		} 
	});
}

function toploadshow(){
	//top.frames['header-frame'].document.getElementById("load-div").style.display = "block";
}

function toploadhide(){
//	top.frames['header-frame'].document.getElementById("load-div").style.display = "none";
}

//上架，下架Ajax
function cimage(obj,url){
	state = $(obj).attr('state');
	$.ajax({
		type: "GET",
		url: url+"&state="+state,
		cache:false,
		dataType:"text",
		beforeSend:function(){$(obj).attr("src","/temp/skin3/images/admin/990000.gif");}, 
		success: function(msg){
			if ($.trim(msg) == '1' ){
				$(obj).attr({src:"/temp/skin3/images/admin/yes.gif",state:"1"});
			}
            else if ($.trim(msg) == '2' ){
				alert("图片不完整");
                $(obj).attr({src:"/temp/skin3/images/admin/no.gif",state:"0"});
			}
            else{
				$(obj).attr({src:"/temp/skin3/images/admin/no.gif",state:"0"});
			}
		}
	}); 
}

//单个删除ajax程序
function del_signle(delatr,msg,obj){
	if (confirm(msg)){
		$.ajax({
			type: "GET",
			url: delatr,
			beforeSend:function(){toploadshow();}, 
			success: function(msg){
				toploadhide();
				$("tr[id='"+obj+"']").remove();
			} 
		});
	}
}	
	
//全选
function checkAll(e, itemName)
{
	//alert(1111);
	if (e.checked){
		try{
	   document.getElementById('btnSubmit').disabled = false;
	   document.getElementById('selAction').disabled = false;
		}
		catch(err){};
   }else{
	   try{
	   document.getElementById('btnSubmit').disabled = true;
	   document.getElementById('selAction').disabled = true;
	   }
		catch(err){};
   }
   
	var aa = document.getElementsByName(itemName);
	for (var i=0; i<aa.length; i++)
	{
	   aa[i].checked = e.checked;
	}
	
}

function checkone(itemName){
	
	var aa = document.getElementsByName(itemName);
	var count = 0;
	for (var i=0;i<aa.length;i++ )
	{
	  if (aa[i].checked){
		  count++;
	  }
	}
	try{
	if (count > 0){
		
	   document.getElementById('btnSubmit').disabled = false;
	   document.getElementById('selAction').disabled = false;
	}else{
	   document.getElementById('btnSubmit').disabled = true;
	   document.getElementById('selAction').disabled = true;
	}
	}catch(e){};
}
function del_acttion(obj){
	delatr = ($(obj).attr('delatr')!=undefined)?$(obj).attr('delatr'):'';  //删除连接
	page_url = ($("#PB_Page_Select").attr('atr')!=undefined)?$("#PB_Page_Select").attr('atr'):document.location.href; //返回连接
	pageno = parseInt($(".red").html()); //当前页数
	if (!isNaN(pageno)) {page_url= page_url+pageno;}
	delmsg = ($(obj).attr('delmsg')!=undefined)?$(obj).attr('delmsg'):'您确实要删除吗？'; //确认信息
	if ((confirm(delmsg)) && (delatr!='') && (page_url!=undefined)){
		$.ajax({
			type: "GET",
			url: delatr,
			beforeSend:function(){toploadshow();}, 
			success: function(msg){
				toploadhide();
			}
		});
		list_load(page_url);
	}else{
		return false;
	}
};

	function Ok(){
		//alert('uuu');
		window.parent.JqueryDialog.SubmitCompleted("", true,false); //回调JqueryDialog函数
	}
    

/**
 * 删除字符串左右两边空白
 * 
 */
String.prototype.trim = function(){
    return this.replace(/^\s+|\s+$/g, '');
};

/**
 * 格式化输入字符串
 * 用法: "hello {0}".format('world')；返回'hello world'
 *
 */
String.prototype.format = function() {
    var args = arguments;
    return this.replace(/\{(\d+)\}/g, function(m, i) {
        return args[i];
    });
}

/**
 * 
 * @param {array} json 待判断元素数组，数组值为json，形式为id: value; msg: msg
 */
function checkPost(array) {
    var len = array.length, i, item, el, val;
    for (i = 0; i < len; i++) {
        item = array[i];
        el = $('#' + item.id);
        value = el.val().trim();
        if (value == '' || value == item.value) {
            el.focus();
            item.alert ? alert(item.msg) : Alert(item.msg);
            return false;
        }
    };
    return true;
}

/**
 * 友好提示
 * 
 * @param {string} msg      提示内容
 * @param {bool}   success  是否成功提示
 * @param {bool}   cancel   是否清除提示
 * @param {int}    timeout  提示停留时间
 */
function Alert(msg, success, cancel, timeout) {
    var div = $('#div-alert');
    if (!cancel) {
        var background = success ? '#16960e' : '#d90000';
        if (div.length == 0) {
            $('<div/>').html(msg).attr('id', 'div-alert').css({
                //'font-weight': 'bold',
                'text-align': 'center',
                'background-color': background,
                'margin-left': '-60px',
                //border: '1px solid #ECECEC',
                color: '#fff',
                position: 'absolute',
                width: '120px',
                height: 'auto',
                padding: '5px',
                left: '50%',
                top: '5px'
            }).appendTo('body');
        }
        else {
            div.html(msg).show().css('background-color', background);
        }
        setTimeout(function() {
            Alert(false, false, true);
        }, timeout || 1500);
    }
    else {
        div.hide();
    } 
}

/**
 * 确认删除
 * 
 */
function adminDelete() {
    $('.a-delete').click(function() {
        var msg = $(this).attr('delmsg');
        if (msg && confirm($(this).attr('delmsg'))) {
            return true;
        }
        else {
            return false;
        }
    });
}

//常用语言
var L = {
    add: '添加',
    edit: '编辑',
    del: '删除',
    success: '成功',
    failure: '失败',
    
    yesno: ['否', '是'],
    openclose: ['关闭', '开启'],
    
    msg: '__msg__' //cookie __msg__
};

//常用函数
var C = {
    /**
     * ajax回调函数
     * 
     * @param {string}      responseText    返回信息
     * @param {string}      url             跳转地址
     * @param {string}      msg             跳转后将提示信息
     */
    callback: function(responseText, url, msg){
        if (responseText) {
            alert(responseText);
            return;
        }
        $.cookie(L.msg, msg);
        location.href = url;
    },
    
    /**
     * ajax设置某一字段为0或1
     * 
     * @param {string}  url     url地址
     * @param {mixed}   data    数据
     * @param {object}  element 点击元素，jquery
     * @param {object}  text    切换文字
     */
    setOne: function(url, data, element, text) {
        $.post(url, data, function(data) {
            if (data) {
                Alert(L.edit + L.failure);
            }
            else {
                Alert(L.edit + L.success, true);
                element.attr('title', '点击' + text.to).text(text.now);
            }
        });
    }
};

function changeActionPeijian()
{
	var frm = document.forms['listForm'];
	// 切换分类列表的显示
	if (frm.elements['type'].value == 'order'){
	  $('#peijian_order').show();
	  return false;
	}else{ 
	  $('#peijian_order').hide();
	}   
	if (!$('#btnSubmit').disabled && confirmSubmitPeijian(frm, false))
	{
	  frm.submit();
	}
}
/**
* @param: bool ext 其他条件：用于转移分类
*/
function confirmSubmitPeijian(frm, ext)
{	
  if (frm.elements['type'].value == 'del')
  {
	  return confirm("你确定要删除？");
  }
  else if (frm.elements['type'].value == '')
  {
	  return false;
  }
  else
  {
	  return confirm("确实要进行该操作吗？");;
  }
}

