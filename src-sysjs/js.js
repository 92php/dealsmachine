//货币下拉框选中
host = '/';

$(document).ready(function(){
    var bizhong = $(".bizhong").html();
	alert(bizhong);
    $("#currency option").each(function(){
       if($(this).val() == bizhong){
        $(this).attr("selected","selected");
       }
      });
	  cart_items();
	$('#btn-subscribe').click(function() {
        var el = $('#txt-subscribe');
        var email = el.val();

        if (!email) {
            alert('please enter a Email !');
            el.focus();
            return false;
        }
        else if (!checkmail(email)) {
            alert('Please  a valid e-mail!');
            el.focus();
            return false;
        }
		URL = DOMAIN_CART+'/m-users-a-email_list-job-add-email-' + email + '.htm?jsoncallback=?';
		 $.getJSON(URL, function(data) {
            msg = data.ms;
			alert(msg);
		 })

    });

	$("#live_chat").mouseover(function(){
	
		$(".r1303").show();
		$(this).attr("class","r1302");
		
	});	
	$("#live_chat").mouseleave(function(){
			//alert(111);
			$(".r1303").hide();
			$(this).attr("class","r1301");
	});				

});



//添加书签
function addbookmark(obj){
	var weburl = $(obj).attr('href');
	ymPrompt.win({message:weburl,width:870,height:450,title:js_func_addbookmark_0,handler:null,maxBtn:true,minBtn:true,iframe:true});
};

//订阅邮件
function subscribe(){
	var firstname = $('#sub_firstname').val();
	var email     = $('#sub_email').val();
	if (!firstname){ alert(js_func_subscribe_0); $('#sub_firstname').focus(); return false; }
	if (firstname.length > 25){ alert(js_func_subscribe_1); $('#sub_firstname').focus(); return false; }
	if (!email){alert(js_func_subscribe_2);  $('#sub_email').focus();return false;}
	if (!checkmail(email)){alert(js_func_subscribe_3);  $('#sub_email').focus();return false;}

	$.ajax({
		type: "GET",
		url: DOMAIN_USER+'/'+js_cur_lang_url + 'index.php?m=users&a=email_list&job=add&email=' + email +'&firstname='+firstname,
		success: function(msg){ alert(msg); }
	});
};

function checkmail(Email)
{
    var pattern=/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
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
	ymPrompt.win({message:'online.html',width:270,height:155,title:js_func_talkall_0,handler:null,maxBtn:true,minBtn:true,iframe:true});
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
	    ymPrompt.win({message:DOMAIN+'/'+js_cur_lang_url + 'm-goods-temp-show.htm?id='+goods_id+'&attr='+key,width:840,height:520,title:js_func_show_custom_0,handler:function(){$('.spec_'+key)[0].selectedIndex = 0;},maxBtn:true,minBtn:true,iframe:true});
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
		$.cookie('bizhong', 'USD', {expires: 7, path: '/', domain: COOKIESDIAMON});
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

	//$('.tab_right1').attr('style','POSITION: absolute; TOP: -2400px;');
	var bizhong = obj.value;//$(obj).attr('tref');
	//$("#cur_jiancheng").html(bizhong);
	$.cookie('bizhong',bizhong, {expires: 7, path: '/', domain: COOKIESDIAMON});
	$(".bizhong").html(bizhong);
	$(".my_shop_price").each(function(i,o){
			yuanshi = $(this).attr('orgp');
			$(o).html((parseFloat(my_array[bizhong]) * parseFloat(yuanshi)).toFixed(2));
	   });
};


//根据语言改变货币种类
function change_houbi_curr(curr){
	$("#cur_jiancheng").html(curr);
	$.cookie('bizhong',curr, {expires: 7, path: '/', domain: COOKIESDIAMON});
	$("#currency").val(curr);
	$(".bizhong").html(curr);
	$(".my_shop_price").each(function(i,o){
		yuanshi = $(this).attr('orgp');
		$(o).html((parseFloat(my_array[curr]) * parseFloat(yuanshi)).toFixed(2)); 
	});
};

//更新购物车数量
function cart_items(){

	URL=DOMAIN_CART+'/fun/?act=cart_item&noscript=1&jsoncallback=?';
	$.getJSON(URL, function(data) {
		msg = data.ms;
		$("#cart_items,.cart_items").html(msg);
	})

};

//搜索
function kw_onfocus(obj){
	tips_word=$(obj).attr('tips');
	kw = $.trim($(obj).val());
	if (kw==tips_word||kw == 'Products keyword' || kw == 'New Arrival'){
		$(obj).val('');
	}
	$(obj).attr('style','color:#000000');
};

function seach_submit(){
	var reg = /[^\w\.]/g;
	//var ss = kw.replace(reg, "");
	kw  = encodeURIComponent($.trim($("#k2").val()).replace(reg, "-"));
	kw  = kw.replace("%2F",'-');
	kw  = kw.replace("%5C",'-');
	category = $.trim($("#category").val());
	if ((kw == '') || (kw == 'Products-keyword')){
		alert(js_func_seach_submit_0);
		$("#k2").val('');
		$("#k2").focus();
		return false;
	}else{
		kw = kw.replace('%20','-');
		if (category == '0'){
			window.location.href=DOMAIN+'/'+js_cur_lang_url + 'wholesale/'+kw+'.html';
		}else{
			window.location.href=DOMAIN+'/'+js_cur_lang_url + 'wholesale/'+kw+'/'+category+'.html';
		}
	}
};

// JavaScript Document
//添加商品到购物车
function addcart(obj){
	//alert(DOMAIN_CART);
    var act_sign = $(obj).attr('act_sign');
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
	var is_combo = $(obj).attr('is_combo') == undefined?0:1;
	var msg_id = $(obj).attr('msg_id') == undefined?'':$(obj).attr('msg_id');
	//alert($(obj).attr('msg_id'));
	//alert(gid);
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
							   is_kong_msg += error_num+js_func_addcart_0+lab_name+'!\n';
							   error_num++;
							   if (dijige==''){dijige = v;}
						   }
					   }
				   case 'checkbox':
						$('.spec_'+v+':checked').each(function(j){
							temparr[i][j] = $(this).val();
							if (temparr[i][j] == "") {is_kong_msg += error_num+js_func_addcart_0+lab_name+'!\n';error_num++;}
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
						   if (temparr[1] == "") {is_kong_msg += js_func_addcart_0+lab_name+'!\n';}
					   }
				   case 'checkbox':
						$('.spec_'+attrchage+':checked').each(function(j){
							temparr[1][j] = $(this).val();
							if (temparr[1][j] == "") {is_kong_msg += js_func_addcart_0+lab_name+'!\n';}
						});
			}
		}else{
			temparr[1] =  $(this).attr('atrid');
			atrrid = $(this).attr('atrid');
		}
	}
	if(typeof(atrrid)=='undefined')atrrid='';
	if (is_kong_msg!="") {alert(js_func_addcart_1 + is_kong_msg);$('.spec_'+dijige).focus();return false;}
	cartval = temparr;

	target_div = msg_id ==''?"#add_cart_msg"+gid+atrrid:"#"+msg_id;
	//alert(target_div);
	if (error_msg !=''){ alert(error_msg);return false;}


	if(is_combo){
		var peijianId = '', el = $('#ul-peijian');

		if (el.length > 0) {//配件
			peijianId = el.find(':checked').map(function() {
				return $(this).val();
			}).get().join();
		}
	}else{

		peijianId ='';
	}
	URL=DOMAIN_CART+'/'+js_cur_lang_url + "m-flow-a-add_to_cart.htm?jsoncallback=?&"+"goods_id="+gid+"&number="+num+"&spec="+cartval+"&attrchage="+attrchage+"&act_sign="+act_sign+'&peijian_id=' + peijianId;
    $.getJSON(URL, function(data) {
		msg = data.ms;
		if (msg.indexOf('Added To Cart')>0){  //当添加成功的时候执行并分解 1||Added To Cart
				var mag_arr = msg.split('||');
					cartnum = parseInt(mag_arr[0]);

				$(".all_red_cart_items").each(function(){$(this).html(cartnum);}); //刷新每一个
				$(target_div).html(mag_arr[1]+'<br><a href="'+DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-cart.htm" class="view_cart"> '+js_func_addcart_2+' <span class="all_red_cart_items">'+cartnum+'</span>items(s)</a>');

				if (reflash == "1" ) re_load(DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-cart.htm');
				cart_items();
		}else{
			   $(target_div).html(msg);
		}

    });
}




//刷新购物车中商品数量
//检查自定义属性是否填写和是否超长。
function check_values(vals,error_msg,duixiang){
	if (vals == ''){
	   error_msg += duixiang + js_func_check_values_0;
	}else if (vals.length > 120){
	   error_msg += duixiang + js_func_check_values_1;
	}
	 return error_msg;
};


//删除购物车
$('#del_action').live("click",function (){
	delatr = ($(this).attr('delatr')!=undefined)?$(this).attr('delatr'):'';  //删除连接
	gid    = ($(this).attr('gid')!=undefined)?$(this).attr('gid'):'';
	gifts_id    = ($(this).attr('gifts_id')!=undefined)?$(this).attr('gifts_id'):0;
	if(is_include_gifts>0 && gifts_id == 0)
		delmsg = js_del_action_0;
	else
		delmsg = ($(this).attr('delmsg')!=undefined)?$(this).attr('delmsg'):js_del_action_1; //确认信息
	if ((confirm(delmsg)) && (delatr!='')){
		list_load(delatr,"#del_ajax_msg"+gid,"#cart"+gid);
		cart_items();
	}
});

//从购物车放入收藏
$('#add_favour_msg').live("click",function (){
	delatr = ($(this).attr('delatr')!=undefined)?$(this).attr('delatr'):'';  //删除连接
	gid    = ($(this).attr('gid')!=undefined)?$(this).attr('gid'):'';
	delmsg = ($(this).attr('delmsg')!=undefined)?$(this).attr('delmsg'):js_del_action_1; //确认信息
	if ((confirm(delmsg)) && (delatr!='')){
			$.ajax({
				type: "GET",
				url: delatr,
				beforeSend:function(){$("#del_ajax_msg"+gid).html("<img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > " + reviews_func_check_btn_signin_3);	},
				success: function(msg){
					re_load(DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-cart.htm');
					cart_items();
				}
			});
	}
});


//更新购物车
$(".goods_number").livequery('keyup',function (){
	var el = $(this);
	num = $(this).val();
	o_goods_number = $(this).attr('o_goods_number');
	if(o_goods_number=='undefined')o_goods_number=0;
	if (!num) return false;
	//alert(o_goods_number);
	if(is_include_gifts>0 && o_goods_number>num){
		msg = js_goods_number_0;
		if(!confirm(msg)){
			$(this).val(o_goods_number);
			return false;
		}
	}

	//中奖购买数量限制
	lmt_num = $(this).attr('lmt_num');
	if (lmt_num>0 && num < lmt_num){
		alert(js_goods_number_1 + lmt_num+'!');
		$(this).val(lmt_num);
		return false;
	}

    var mainGoodsId = parseInt(el.attr('main_goods_id'));//主商品id
    var max = parseInt($('.hidden-' + mainGoodsId).val());
    //alert(mainGoodsId);
    if (mainGoodsId && num > max) {
        alert(js_goods_number_2);
        el.val(max);
        return false;
    }




	rid = ($(this).attr('rid')!=undefined)?$(this).attr('rid'):'';
	$.ajax({
		type: "POST",
		url: DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-update_cart.htm',
		data:'rid='+rid+'&goods_number='+num,
		beforeSend:function(){$("#num"+rid).html("<img src='/temp/skin1/images/990000_bai.gif'>");},
		success: function(msg){
            if (msg=='Updated'){
				$("#num"+rid).html('');
				re_load(DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-' + (typeof inCheckout == 'undefined' ? 'cart' : 'checkout') + '.htm?ajax=true');
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
			url: DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-cart.htm?country='+country,
			beforeSend:function(){$("#load_ajax_msg").html(" <img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > " + reviews_func_check_btn_signin_3);	},
			success: function(msg){
			    $("#load_ajax_msg").html('');
				var stext = $(msg).find('#shipajax').html();
				$('#shipajax').html(stext);
				show_my_shop_price('#shipajax');
				$(".bizhong").html($.cookie('bizhong'));
			}
		});
 });


function re_load(page_url, callback){
	//return;
	//alert(page_url);
	$.ajax({
		type: "GET",
		url: page_url,
		cache:false,
		success: function(msg){
			//window.location.reload();

			var stext = $(msg).find('#cart_list').html();
			//alert(stext);
			$('#cart_list').html(stext);
			//alert(stext);
			show_my_shop_price('');
			$(".bizhong").html($.cookie('bizhong'));
            typeof(callback) == 'function' && callback();
		}
	});
};

function list_load(page_url,tobj,obj, callback){
	$.ajax({
		type: "GET",
		url: page_url,
		beforeSend:function(){$(tobj).html("<img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > " + reviews_func_check_btn_signin_3);	},
		success: function(msg){
			if (msg=='Deleted'){
				$(obj).hide("slow");
				//$(obj).animate({opacity:"toggle"},"slow");
				//$(obj).remove("slow");
				re_load(DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-' + (typeof inCheckout == 'undefined' ? 'cart' : 'checkout') + '.htm?ajax=true', callback);
			}
			 $(tobj).html(msg);
		}
	});
};


//详细页面商品数量 减 每次减1
function jian(){
    price= parseFloat($("#unit_price").html());
	var item = $('#input_quantity');
	var orig = Number(item.val());
    var bizhong=$(".bizhong").html();
	if(orig > 1){
        n=orig - 1;
		item.val(n);
		item.keyup();
        //var str='<span class="bizhong" style="color:red;">'+bizhong+'</span><font color="red"> '+price+'</font> x '+n+' = <span class="bizhong" style="color:red;">'+bizhong+'</span> <font color="red">'+(price * n).toFixed(2)+'</font>';
        //$('#items_total').html(str);
	}

};


/**
 * 配件复选框
 *
 * @return {void} 无返回值
 */
function pajianCheckbox() {

    var el = $('#ul-peijian');

    if (el.length > 0) {
        el.find(':checkbox').attr('checked', false).click(function() {
            var el = $(this), elParent = el.parents('li:first');
            elParent.toggleClass('choose');

            input_quantity($('#input_quantity')[0]);//重新计算总价
        });
    }
}


//详细页面商品数量 加 每次加1
function jia(){

    price= parseFloat($("#unit_price").html());
	var item = $('#input_quantity');
	var orig = Number(item.val());
    var bizhong=$(".bizhong").html();
    var n=orig + 1;
	item.val(n);
	item.keyup();
   // var str='<span class="bizhong" style="color:red;">'+bizhong+'</span><font color="red"> '+price+'</font> x '+n+' = <span class="bizhong" style="color:red;">'+bizhong+'</span> <font color="red">'+(price * n).toFixed(2)+'</font>';
    //$('#items_total').html(str);
};

//详细页面商品数量 直接填写
function input_quantity(obj){

    var bizhong=$(".bizhong").html();
	var shur_num   = parseInt($(obj).val());
	var uprice     = parseFloat($("#unit_price").html());

	if(isNaN(shur_num))shur_num=1;

    var accessory_price=0;
    var accessory_price1=0;
    var select_accessory_price=0;
    //复选框配件
    $("#para_accessory").find("input:checked").each(function(){
     accessory_price=$(this).attr("val");
     accessory_price=parseInt((parseFloat(accessory_price)*100).toFixed(2));
     accessory_price1=accessory_price1+accessory_price;
    });
    //accessory_price1=parseFloat(accessory_price1);
    //下拉配件
    select_accessory_price=$("#accessory_select").find("option:selected").attr("subprice");
    select_accessory_price=parseInt((parseFloat(select_accessory_price)*100).toFixed(2));
	//

	if (shur_num <= 9999){
		$(".plnum").each(function(){
			//

			cur_num = $(this).html();
			//alert(cur_num);
			cur_num = parseInt(cur_num.replace(/[\u4e00-\u9fa5]/g,""));

			if (shur_num >= cur_num ){
				//alert(promote_price);
				if(promote_price>0){
					uprice=promote_price*100;//alert(uprice);
				}else{
					pk     = parseFloat($(this).attr('atrp'));
					uprice = $("#pk"+pk).html();
                	uprice=parseInt((parseFloat(uprice)*100).toFixed(2));//alert(uprice);
				}

				if(accessory_price1||select_accessory_price)
                   uprice=((uprice+accessory_price1+select_accessory_price)/100).toFixed(2);
                else
                   uprice=(uprice/100).toFixed(2);



				$("#unit_price").html(uprice); //附值新一级数量价格
                $("#unit_price").attr('orgp',uprice);//附值新一级数量价格
			}
		});

		$("#input_quantity").val(shur_num);
		//$("#span_quantity").html(shur_num);
		//$("#total_sub_price").html((shur_num*uprice).toFixed(2));
		$(".add_cart_button").attr('num',shur_num);
		amt=(((uprice *100)* shur_num)/100).toFixed(2);
		$('.total_price').attr('orgp',amt);
		$('.total_price').html(amt);
        var str='<span class="bizhong" style="color:red;">'+bizhong+'</span><font color="red"> '+uprice+'</font> x '+shur_num+' = <span class="bizhong" style="color:red;">'+bizhong+'</span> <font color="red">'+amt+'</font>';
        $('#items_total').html(str);
	}
	addPeijianPrice();
};


/**
 * 增加配件价格
 *
 * @return {void} 无返回值
 */
function addPeijianPrice() {
	///alert(11);
    var el = $('#ul-peijian');

    if (el.length > 0) {//存在配件
        var elPrice = $('.total_price');
		//alert(elPrice.attr('orgp'));
        var orgp = parseFloat(elPrice.attr('orgp'));
        var price = parseFloat($(elPrice[0]).text());
        var num = parseInt($('#input_quantity').val());
        var peijian_price = 0;
        var peijian_shop_price = 0;
        var _el = $('#span-market_price');

        if (_el.length) {//市场价
            var total_price = parseFloat($('#span-market_price').text());
            var total_orgp = _el.attr('orgp');
        }
        else {

            _el = $('#unit_price');//第一个阶梯价
            var total_price = _el.length ? parseFloat(_el.text()) : 0;
            var total_orgp = _el.length ? _el.attr('orgp') : 0;
        }
        total_price =0;
		total_orgp  =0;
        el.find(':checked').each(function() {
            _el = $(this).nextAll('.my_shop_price');
            orgp += parseFloat(_el.attr('orgp')) * num;
            var _price = parseFloat(_el.text());
            peijian_price += _price;

            var _shop_price = $(this).parent().prev().find('.my_shop_price');//配件销售价
             //total_price += _shop_price.length > 0 ? parseFloat(_shop_price.text()) : _price;
			 total_price += _shop_price.length > 0 ? parseFloat(_shop_price.text()) : _price;
        });
		//alert(peijian_price * num);

        peijian_price = (peijian_price * num).toFixed(2);

		//alert(peijian_price);
		//alert(total_price);
        var total_shop_price = (price + parseFloat(peijian_price)).toFixed(2);
        var saving = (total_price * num - parseFloat(total_shop_price)).toFixed(2);
		saving = total_price * num  -peijian_price;
		//alert(saving);
        elPrice.attr('orgp', orgp).text(total_shop_price);
        $('.total_peijian_price').text(peijian_price);
        $('.total_saving').text(saving.toFixed(2));



    }
}

function checkorder(){
	 ymPrompt.setDefaultCfg({okTxt:' OK ',cancelTxt:' Cancel ',closeTxt:'Close',minTxt:'Minimize',maxTxt:'Maximize'});
	 ymPrompt.confirmInfo({icoCls:'',msgCls:'confirm',message:js_func_checkorder_0+"<br><input type='text' id='myInput' onfocus='this.select()' />",title:js_func_checkorder_1,height:150,handler:getInput,autoClose:false});
};


function getInput(tp){
	if(tp!='ok') return ymPrompt.close();
	var v=$('#myInput').val();
	if(v=='' || v.length < 17 || v.length > 18)
		alert(js_func_getInput_0);
	else{
		window.open(DOMAIN_USER+'/'+js_cur_lang_url + 'm-users-a-queryorder-n-'+v+'.htm');
		ymPrompt.close();
	}
};
//alert(host+"fun/index.php?act=chk_sign");
function islogin(){
	URL=DOMAIN_USER+'/fun/index.php?act=chk_sign&jsoncallback=?';
	//document.write(URL);
	$.getJSON(URL, function(data) {
		msg = data.ms;
		if (msg){
			$("#islogin").html(js_func_islogin_0+' '+ msg +'  <a rel=nofollow href="'+DOMAIN_USER+'/'+js_cur_lang_url + 'm-users-a-logout.htm">' + js_func_islogin_1 + '</a>');
		}else{
				$("#islogin").html(js_func_islogin_2+'! <a href="'+DOMAIN_USER+'/'+js_cur_lang_url + 'm-users-a-sign.htm" rel=nofollow>'+js_func_islogin_3+'</a> or <a href="'+DOMAIN_USER+'/'+js_cur_lang_url + 'm-users-a-join.htm" rel=nofollow  style="color:#FF6600">'+js_func_islogin_4+'</a>');
		}
	})
}




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
		window.location.href = DOMAIN+'/'+js_cur_lang_url + 'product-' + goods_id + '.html';
	}
}

$(document).ready(function(){
$("#k2").autocomplete("/m-autosearch.htm", {
		width: 358,
		scroll:false,
		autoFill: false,
		selectFirst: false,
		formatItem: function(row, i, max) {
			return "<span style='float:left; margin-left:3px; width:358px;text-align:left;'>" + row[0] + "</span>";
		}
	}).result(function(event, item) {
   location.href = DOMAIN+'/'+js_cur_lang_url + 'wholesale/'+item[1]+'.html';
});

});

$("#accessory_select").livequery('change',function (){
    var bizhong=$(".bizhong").html();
    var select_prev=$("#select_prev").val();
    var select_prev=parseFloat(select_prev);
    var val=$(this).find("option:selected").attr("subprice"); //alert(val);
    val=(parseFloat(my_array[bizhong]) * parseFloat(val));
    //if(val)
    //{
         val=parseFloat(val.toFixed(2));
         //alert(val);

         var unit_price=$("#unit_price").html();
         unit_price=parseFloat(unit_price);
         var n=$("#input_quantity").val();

         if(select_prev==0)
           unit_price=unit_price+val;
         else
           unit_price=unit_price+val-select_prev;

         unit_price=unit_price.toFixed(2);
         $("#unit_price").html(unit_price);
         var str='<span class="bizhong" style="color:red;">'+bizhong+'</span><font color="red"> '+unit_price+'</font> x '+n+' = <span class="bizhong" style="color:red;">'+bizhong+'</span><font color="red"> '+unit_price * n+'</font>';
         $('#items_total').html(str);
    //}
    $("#select_prev").val(val);
 });

$("#para_accessory").find("input").livequery('click',function (){
     var bizhong=$(".bizhong").html();
     var val=$(this).attr("val");
     var unit_price=$("#unit_price").html();
     var n=$("#input_quantity").val();

     val=(parseFloat(my_array[bizhong]) * parseFloat(val)); //alert(val);
     unit_price=parseFloat(unit_price);

     checked=$(this).attr("checked");
     if(checked)
       unit_price=unit_price+val;
     else
       unit_price=unit_price-val;

     unit_price=unit_price.toFixed(2);
     $("#unit_price").html(unit_price);
     str='<span class="bizhong" style="color:red;">'+bizhong+'</span><font color="red"> '+unit_price+'</font> x '+n+' = <span class="bizhong" style="color:red;">'+bizhong+'</span><font color="red"> '+unit_price * n+'</font>';
     $('#items_total').html(str);
 });


$(document).ready(function(){
	pajianCheckbox();
	$('#fb_yes').click(function(){vote('y')});
	$('#fb_no').click(function(){vote('n')});

});
function vote(v){

	$.ajax({
		type: "get",
		url: "?",
		data: "a=vote"+'&v=' + v,
		dataType:"text",
		success: function(msg){
			$('#p_vote').html('<img src=/temp/skin3/images/yes.gif>Thank you for your feedback.');
		}
	}
)
}

function setFocus(obj, def, color) {
    def = def || obj.defaultValue;

    obj.value.trim() == def ? obj.value = '' : '';

    obj.onblur = function() {
        obj.value.trim() == '' ? obj.value = def : '';
    };
    color ? obj.style.color = color : '';

    return obj;
}

String.prototype.ltrim = function() {
    return this.replace(/^\s+/, '');
};
String.prototype.rtrim = function() {
    return this.replace(/\s+$/, '');
};
String.prototype.trim = function() {
    return this.ltrim().rtrim();
};

/**
 * 全选，全不选
 *
 * @author      mashanling<msl-138@163.com>
 * @date        2013-02-25 10:43:17
 *
 * @param {object} cb  checkbox
 * @param {string} cls class
 *
 * @return {void} 无返回值
 */
function checkAll(cb, cls) {
    cls = cls || 'checkbox';
    $('input.' + cls).attr('checked', cb.checked);
}

/**
 * 获取选中内容
 *
 * @author      mashanling<msl-138@163.com>
 * @date        2013-02-25 10:43:17
 *
 * @param {string} cls class
 * @param {bool} noCheckedMsg 未有选择提示语。默认Please selected
 *
 * @return {string} 选中内容
 */
function getCheckedAll(cls, noCheckedMsg) {
    cls = cls || 'checkbox';
    var values = $('input.' + cls + ':checked').map(function() {
        return $(this).val();
    }).get().join(',');

    if (!values && false !== noCheckedMsg) {
        alert(noCheckedMsg || 'Please select!');
    }

    return values;
}

//记录用户评论好评差评数
function review_helpful_num(review_id,help_type)
{
	$.ajax({
	    type: "GET",
	    url: "/index.php?m=review&a=review_helpful_num&review_id="+review_id+"&help_type="+help_type,
	    success: function(msg){
	    	if(msg == 'no_login')
	    	{
	    		var ref_url = $("#hidden-goodsUrl").val();
	    		window.location.href = DOMAIN_USER + "/m-users-a-sign.htm?ref="+ref_url+"&Anchor=review_helpful_div_"+review_id;
	    	}
	    	else if(msg >0)
	    	{
		        if(help_type == 0)
		        {
		        	var c_id = "#review_helpful_yes_"+review_id;
		        }
		        else
		        {
		        	var c_id = "#review_helpful_no_"+review_id;
		        }
		        $(c_id).html('('+msg+')');
	        }
	        else if(msg < 0)
	        {
	        	alert('You\'ve told us.');
	        }
	    }
	});
}

