//添加书签
function addbookmark(obj){
	var weburl = $(obj).attr('href');
	ymPrompt.win({message:weburl,width:870,height:450,title:'To bestafford.com Add to Bookmarks',handler:null,maxBtn:true,minBtn:true,iframe:true});									 
};

//订阅邮件
function subscribe(){
	var firstname = $('#sub_firstname').val();					   
	var email     = $('#sub_email').val();	
	if (!firstname){ alert('Please enter your first name!'); $('#sub_firstname').focus(); return false; }
	if (firstname.length > 25){ alert('First name please do not be too long!'); $('#sub_firstname').focus(); return false; }
	if (!email){alert('please enter a Email !');  $('#sub_email').focus();return false;}
	if (!checkmail(email)){alert('Please  a valid e-mail!');  $('#sub_email').focus();return false;}
	
	$.ajax({
		type: "GET",
		url: 'index.php?m=users&a=email_list&job=add&email=' + email +'&firstname='+firstname,
		success: function(msg){ alert(msg); } 
	});
};

function checkmail(Email)
{
    var pattern=/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/;
    flag=pattern.test(Email);
    if (flag){
		return true;
	}else{
        return false;
    }
}







function setTab(m,n){
 var tli=document.getElementById("menu"+m).getElementsByTagName("li");
 var mli=document.getElementById("main"+m).getElementsByTagName("ul");
 for(i=0;i<tli.length;i++){
  //tli[i].className=i==n?str1[n]:str2[n];
  if (i == n ) {
	  tli[i].className = 'pr_'+(n+1)+'_2';
	}else{
	  tli[i].className = 'pr_'+(i+1)+'_1';
	}
  mli[i].style.display=i==n?"block":"none";
 }
};


function setdetails(m,n){
 var tli=document.getElementById("menu"+m).getElementsByTagName("li");
 var mli=$("[ectype='showtable']");
 for(i=0;i<tli.length;i++){
  //tli[i].className=i==n?str1[n]:str2[n];
  if (i == n ) {
	  tli[i].className = 'pr_'+(n+1)+'_2';
	}else{
	  tli[i].className = 'pr_'+(i+1)+'_1';
	}
  mli[i].style.display=i==n?"block":"none";
 }
};




function talkall(st){
	ymPrompt.win({message:'online.html',width:270,height:155,title:'Needs Help?',handler:null,maxBtn:true,minBtn:true,iframe:true}); 
};

//显示自定义框
function show_custom(key,goods_id){
/*	var subprice = $(".spec_"+key+" option:selected").attr('subprice');
	if (subprice != ''){
		$(".plnum").each(function(){
				pk = parseFloat($(this).attr('atrp'));
				thisprice  = $("#pk"+pk).html();
				thisprice   = (parseFloat(subprice)+parseFloat(thisprice)).toFixed(2);
				$("#pk"+pk).html(thisprice);
				$('#input_quantity').keyup();
		});	
	}
	
*/	
    var item_value = $('.spec_'+key+' option:selected').text();
	if (item_value == "custom made"){
	    ymPrompt.win({message:'/m-goods-temp-show.htm?id='+goods_id+'&attr='+key,width:840,height:520,title:'Custom Options',handler:function(){$('.spec_'+key)[0].selectedIndex = 0;},maxBtn:true,minBtn:true,iframe:true}); 
	}else{
		//$("#custom"+key).hide();
	};
	
};





//显示复选自定义框
function show_check_custom(key){
	///alert($(".spec_"+key).val());
	if ($("#customxx"+key).attr("checked")){
		$("#custom"+key).show();
	}else{
		$("#custom"+key).hide();
	};
};



function show_my_shop_price(selectid){
	if (selectid!='')selectid +=' ';
	if ($.cookie('bizhong') == null){
		$.cookie('bizhong', 'USD', {expires: 7, path: '/'});
	} 
	
   $(selectid+".my_shop_price").each(function(i,o) {
		yuanshi = $(this).attr('orgp');
		$(o).html((parseFloat(my_array[$.cookie('bizhong')]) * parseFloat(yuanshi)).toFixed(2)); 
	});
   
$(".bizhong").html($.cookie('bizhong'));
$("#cur_jiancheng").html($.cookie('bizhong'));
};

//改变货币种类
function change_houbi(obj){ 
	$('.tab_right1').attr('style','POSITION: absolute; TOP: -2400px;');
	var bizhong = obj.value;//$(obj).attr('tref');
	$("#cur_jiancheng").html(bizhong);
	$.cookie('bizhong',bizhong, {expires: 7, path: '/'});          
	$(".bizhong").html(bizhong);
	$(".my_shop_price").each(function(i,o){
			yuanshi = $(this).attr('orgp'); 
			$(o).html((parseFloat(my_array[bizhong]) * parseFloat(yuanshi)).toFixed(2)); 
	   });
};

//更新购物车数量
function cart_items(){
	
   //$("#cart_items").load("/fun/?act=cart_item&noscript=1");
	$.ajax({
		type: "GET",
		url: "/fun/?act=cart_item&noscript=1",
		cache:false,
		success: function(msg){
			$("#cart_items").html(msg);
		}
	});
};

//搜索
function kw_onfocus(obj){
	kw = $.trim($(obj).val());
	if (kw == 'Products keyword'){
		$(obj).val('');
	}
	$(obj).attr('style','color:#000000');
};

function seach_submit(){
	var reg = /\s/g;     
	//var ss = kw.replace(reg, "");
	kw  = encodeURIComponent($.trim($("#k2").val()).replace(reg, "-"));
	kw  = kw.replace("%2F",'-');
	kw  = kw.replace("%5C",'-');
	category = $.trim($("#category").val());
	if ((kw == '') || (kw == 'Products-keyword')){
		alert('Please enter keywords!');
		$("#k2").val('');						   
		$("#k2").focus();
		return false;
	}else{
		kw = kw.replace('%20','-');
		if (category == '0'){
			window.location.href='/wholesale/'+kw+'.html';
		}else{
			window.location.href='/wholesale/'+kw+'/'+category+'.html';
		}
	}
};

//添加商品到购物车
function addcart(obj){  
	var gid = $(obj).attr('gid');
	var num = $(obj).attr('num');
		num = (num == undefined)?1:num;
	var reflash = $(obj).attr('ref');
		reflash = (reflash == undefined)?0:reflash;
		cartval =  '';
	var attrchage = $(obj).attr('attrchage');
		attrchage = (attrchage == undefined)?'':attrchage;
	var geshuxing = '';	
	var error_msg = '';
	var is_kong_msg = '';
	var dijige = '';
	var error_num = 1;
	var temparr = new Array();
	
	if(attrchage.indexOf('|')>0){
		attrchage = attrchage.split('|');
		$.each(attrchage,function(i,v){
			temparr[i] = new Array();
			var type = $('.spec_'+v).attr('type');	
			var lab_name = $('.spec_'+v).attr('lab_name');	
			switch (type){
				   case 'select-one':
					   temparr[i] = $('.spec_'+v).val();
					   var isnes = $('.spec_'+v).attr('isnes');
					   if (isnes == '1'){
						   if (temparr[i] == "") {
							   is_kong_msg += error_num+'. Please select '+lab_name+'!\n';
							   error_num++; 
							   if (dijige==''){dijige = v;}
						   }
					   }
				   case 'checkbox':
						$('.spec_'+v+':checked').each(function(j){
							temparr[i][j] = $(this).val();
							if (temparr[i][j] == "") {is_kong_msg += error_num+'.  Please select '+lab_name+'!\n';error_num++;}
						});
			}
			
		});
	}else{
		if (attrchage!=''){
			dijige = attrchage;
			temparr[1] = new Array();
			var type = $('.spec_'+attrchage).attr('type');
			var lab_name = $('.spec_'+attrchage).attr('lab_name');	
			switch (type){
				   case 'select-one':
					   temparr[1] = $('.spec_'+attrchage).val();
					   var isnes = $('.spec_'+attrchage).attr('isnes');
					   if (isnes == '1'){
						   if (temparr[1] == "") {is_kong_msg += '   Please select '+lab_name+'!\n';}
					   }
				   case 'checkbox':
						$('.spec_'+attrchage+':checked').each(function(j){
							temparr[1][j] = $(this).val();
							if (temparr[1][j] == "") {is_kong_msg += '   Please select '+lab_name+'!\n';}
						});
			}
		}else{
			temparr[1] =  $(this).attr('atrid');
			atrrid = $(this).attr('atrid');
		}
	}
	
	if (is_kong_msg!="") {alert('Can not be submitted for the following reasons:\n\n'+is_kong_msg);$('.spec_'+dijige).focus();return false;}
	cartval = temparr;
	target_div = $(this).attr('atrid')!=undefined?"#add_cart_msg"+gid+atrrid:"#add_cart_msg"+gid;
	if (error_msg !=''){ alert(error_msg);return false;}
	$.ajax({
		type: "POST",
		url: "/m-flow-a-add_to_cart.htm",
		data: "goods_id="+gid+"&number="+num+"&spec="+cartval+"&attrchage="+attrchage,
		dataType:"text",
		beforeSend:function(){$(target_div).html("<img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > Processing ...");}, 
		success: function(msg){
			if (msg.indexOf('Added To Cart')>0){  //当添加成功的时候执行并分解 1||Added To Cart
				var mag_arr = msg.split('||');
					cartnum = parseInt(mag_arr[0]);
				$(".all_red_cart_items").each(function(){$(this).html(cartnum);}); //刷新每一个
				$(target_div).html(mag_arr[1]+'<br><a href="/m-flow-a-cart.htm" class="view_cart"> Cart & Checkout <span class="all_red_cart_items">'+cartnum+'</span>items(s)</a>');
				if (reflash == "1" ) re_load('/m-flow-a-cart.htm'); 
				cart_items();
			}else{
			   $(target_div).html(msg);
			}
		}
	}); 
};


//刷新购物车中商品数量
//检查自定义属性是否填写和是否超长。
function check_values(vals,error_msg,duixiang){
	if (vals == ''){
	   error_msg += duixiang + ' can not be blank!\n';
	}else if (vals.length > 120){
	   error_msg += duixiang + ' for far too long!\n';
	}
	 return error_msg;
};


//删除购物车
$('#del_action').live("click",function (){
	delatr = ($(this).attr('delatr')!=undefined)?$(this).attr('delatr'):'';  //删除连接
	gid    = ($(this).attr('gid')!=undefined)?$(this).attr('gid'):'';
	delmsg = ($(this).attr('delmsg')!=undefined)?$(this).attr('delmsg'):'Are you sure that you want to perform this action?'; //确认信息
	if ((confirm(delmsg)) && (delatr!='')){
		list_load(delatr,"#del_ajax_msg"+gid,"#cart"+gid);
		cart_items();
	}
});

//从购物车放入收藏
$('#add_favour_msg').live("click",function (){
	delatr = ($(this).attr('delatr')!=undefined)?$(this).attr('delatr'):'';  //删除连接
	gid    = ($(this).attr('gid')!=undefined)?$(this).attr('gid'):'';
	delmsg = ($(this).attr('delmsg')!=undefined)?$(this).attr('delmsg'):'Are you sure that you want to perform this action?'; //确认信息
	if ((confirm(delmsg)) && (delatr!='')){
			$.ajax({
				type: "GET",
				url: delatr,
				beforeSend:function(){$("#del_ajax_msg"+gid).html("<img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > Processing ...");	}, 
				success: function(msg){
					re_load('/m-flow-a-cart.htm');
					cart_items();
				} 
			});
	}
});


//更新购物车
$(".goods_number").livequery('keyup',function (){
	num = $(this).val();
	if (!num) return false;
	
	//中奖购买数量限制
	lmt_num = $(this).attr('lmt_num');
	if (lmt_num>0 && num < lmt_num){
		alert('Update failed,To buy at least '+lmt_num+'!');
		$(this).val(lmt_num);
		return false;
	}
	
	rid = ($(this).attr('rid')!=undefined)?$(this).attr('rid'):'';
	$.ajax({
		type: "POST",
		url: '/m-flow-a-update_cart.htm',
		data:'rid='+rid+'&goods_number='+num,
		beforeSend:function(){$("#num"+rid).html("<img src='/temp/skin1/images/990000_bai.gif'>");}, 
		success: function(msg){
			if (msg=='Updated'){
				$("#num"+rid).html('');
				re_load('/m-flow-a-cart.htm');
			}else{
				$("#num"+rid).html(msg);
			}
		} 
	});
});


$(".jian_num").livequery("click",function (){
	var rec_id = $(this).attr("atrid");
	var item = $('#goods_number_' + rec_id);
	var orig = Number(item.val());
	if(orig > 1){
		item.val(orig - 1);
		item.keyup();
	}
 });

$(".jia_num").livequery("click",function (){
	var rec_id = $(this).attr("atrid");
	var item = $('#goods_number_' + rec_id);
	var orig = Number(item.val());
	item.val(orig + 1);
	item.keyup();
 });



//根据国家查看运费
$("#selcountries").livequery('change',function (){   
		var country = $("#selcountries").val();
		if (country == '') return false;
		$.ajax({
			type: "POST",
			url: 'm-flow-a-cart.htm?country='+country,
			beforeSend:function(){$("#load_ajax_msg").html(" <img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > Processing ...");	}, 
			success: function(msg){
			    $("#load_ajax_msg").html('');
				var stext = $(msg).find('#shipajax').html();
				$('#shipajax').html(stext); 
				show_my_shop_price('#shipajax');
				$(".bizhong").html($.cookie('bizhong'));
			} 
		});
 });


function re_load(page_url){
	$.ajax({
		type: "GET",
		url: page_url,
		cache:false,
		success: function(msg){
<<<<<<< .mine           // ss = msg.split('<div id="cart_list">');
            txt = ss[1].split('<script type="text/javascript">setAlsoBuoght();</script>');
            cart_str=txt[0]+'<script type="text/javascript">setAlsoBuoght();</script>';
			//var stext = $(msg).find('#cart_list').html();
			$('#cart_list').html(cart_str);
=======			var stext = $(msg).find('#cart_list').html();
			$('#cart_list').html(stext);
>>>>>>> .theirs			show_my_shop_price('');
			$(".bizhong").html($.cookie('bizhong'));
		}
	});
};

function list_load(page_url,tobj,obj){
	$.ajax({
		type: "GET",
		url: page_url,
		beforeSend:function(){$(tobj).html("<img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > Processing ...");	}, 
		success: function(msg){
			if (msg=='Deleted'){
				$(obj).hide("slow");
				//$(obj).animate({opacity:"toggle"},"slow");
				//$(obj).remove("slow");
				re_load('/m-flow-a-cart.htm');
			}
			 $(tobj).html(msg);
		} 
	});
};


//详细页面商品数量 减 每次减1
function jian(){
    price= parseFloat($("#unit_price2").html());
	var item = $('#input_quantity');
	var orig = Number(item.val());
	if(orig > 1){
        n=orig - 1;
		item.val(n);
		item.keyup();
        var str='<font color="red">USD '+price+'</font> x '+n+' = <font color="red">USD '+price * n+'</font>';
        $('#items_total').html(str);
	}
	
};

//详细页面商品数量 加 每次加1
function jia(){ 
    price= parseFloat($("#unit_price2").html());
	var item = $('#input_quantity');
	var orig = Number(item.val());
    var n=orig + 1;
	item.val(n);
	item.keyup();
    var str='<font color="red">USD '+price+'</font> x '+n+' = <font color="red">USD '+price * n+'</font>';
    $('#items_total').html(str);
};

//详细页面商品数量 直接填写
function input_quantity(obj){
	shur_num   = parseInt($(obj).val());
	uprice     = parseFloat($("#unit_price").html());
	if (shur_num <= 9999){
		$(".plnum").each(function(){
			cur_num = $(this).html();
			cur_num = parseInt(cur_num.replace(/[\u4e00-\u9fa5]/g,""));
			if (shur_num >= cur_num ){
				pk     = parseFloat($(this).attr('atrp'));
				uprice = $("#pk"+pk).html();
				$("#unit_price").html(uprice); //附值新一级数量价格
				$("#unit_price2").html(uprice); //附值新一级数量价格
			}
		});
		
		$("#input_quantity").val(shur_num);
		$("#span_quantity").html(shur_num);
		$("#total_sub_price").html((shur_num*uprice).toFixed(2));
		$(".add_cart_button").attr('num',shur_num);
	}
};


function checkorder(){
	 ymPrompt.setDefaultCfg({okTxt:' OK ',cancelTxt:' Cancel ',closeTxt:'Close',minTxt:'Minimize',maxTxt:'Maximize'});
	 ymPrompt.confirmInfo({icoCls:'',msgCls:'confirm',message:"Please enter your order<br><input type='text' id='myInput' onfocus='this.select()' />",title:'Query Order',height:150,handler:getInput,autoClose:false});	
};


function getInput(tp){
	if(tp!='ok') return ymPrompt.close();
	var v=$('#myInput').val();
	if(v=='' || v.length < 17 || v.length > 18)
		alert('Please enter your correct order number!');
	else{
		window.open('/m-users-a-queryorder-n-'+v+'.htm');
		ymPrompt.close();
	}
};

function islogin(){	
	$.ajax({
		type: "GET",
		//cache:false,
		url: '/fun/index.php?act=chk_sign',
		success: function(msg){
			if (msg){				
				$("#islogin").html('Hi '+ msg +'  <a href="/m-users-a-logout.htm">Logout</a>');
			}else{
				$("#islogin").html('Welcome! <a href="/m-users-a-sign.htm" rel=nofollow>Sign in</a> or <a href="/m-users-a-join.htm"  style="color:#FF6600">Register</a>');
			}
		} 
	});
};



function aiqi(s){
   $("#mianji").attr("mianji",s);
};


function tj(obj,goods_id){
	var local_url = $(obj).attr('href');
	if (goods_id != ''){
		$.ajax({type: "GET",url: '/tj.php?goods_id='+goods_id});
	}
	window.location.href = local_url;
}

function change_same_goods(obj){
	var goods_id = parseInt(obj.value);
	if(goods_id > 0)
	{
		window.location.href = '/product' + goods_id + '.html';
	}
}

$(document).ready(function(){
$("#k2").autocomplete("/m-autosearch.htm", {
		width: 358,
		scroll:false,
		autoFill: false,
		selectFirst: false,
		formatItem: function(row, i, max) {
			return "<span style='float:left; margin-left:3px; width:358px;'>" + row[0] + "</span>";
		}
	}).result(function(event, item) {
   location.href = '/wholesale/'+item[1]+'.html';
});

})

;

$("#accessory_select").livequery('change',function (){   
    var select_prev=$("#select_prev").val();
    var select_prev=parseFloat(select_prev);
    var val=$(this).find("option:selected").attr("subprice");  
    if(val)
    {
         val=parseFloat(val); 
  
         var unit_price=$("#unit_price2").html();
         unit_price=parseFloat(unit_price);
         var n=$("#input_quantity").val();
          
         if(select_prev==0)
           unit_price=unit_price+val;
         else
           unit_price=unit_price+val-select_prev;

         unit_price=unit_price.toFixed(2);
         $("#unit_price2").html(unit_price);
         var str='<font color="red">USD '+unit_price+'</font> x '+n+' = <font color="red">USD '+unit_price * n+'</font>';
         $('#items_total').html(str); 
    }
    $("#select_prev").val(val);
 });

$("#para_accessory").find("input").livequery('click',function (){   
     val=$(this).attr("val");
     val=parseFloat(val);

     unit_price=$("#unit_price2").html();
     unit_price=parseFloat(unit_price);
     n=$("#input_quantity").val();
     
     checked=$(this).attr("checked");
     if(checked)
       unit_price=unit_price+val;
     else
       unit_price=unit_price-val;

     unit_price=unit_price.toFixed(2);
     $("#unit_price2").html(unit_price);
     str='<font color="red">USD '+unit_price+'</font> x '+n+' = <font color="red">USD '+unit_price * n+'</font>';
     $('#items_total').html(str);
 });
/*

$(document).ready(function(){
	$("#mianji").click(function(){
		var mianji = $("#mianji").attr("mianji");
		var lv = $("#mianji").attr("lv");
		if ( mianji == undefined){
			alert('You do not scratch card!');
		}else{
			//var price_store = $("#price_store").attr("orgp");
			//var target_price = $("#target_price").attr("orgp");
			//var price = (price_store*((100-lv)*0.01)).toFixed(2);
			
			//$("#target_price").html(price);
			//$("#target_price").attr("orgp",price);
			//$("#unit_price2").html(price);
			//
			$.ajax({
				type: "GET",
				url: '/m-flow-a-Is_Apply.htm',
				success: function(msg){
					if (msg=='ok'){
						alert("Successfully applied.\n\n The application will appear in your shopping cart");
					}else{
						alert('Error,Application failure.');
					}
				} 
			});
		}
	});
});*/