$(document).ready(function(){
	$('#showyiwen').live('mouseover',function(){$('#yiwen').show();});
	$('#showyiwen').live('mouseout',function(){$('#yiwen').hide();});
	
		$('#showyiwen1').live('mouseover',function(){$('#gifts_txt').show();});
	$('#showyiwen1').live('mouseout',function(){$('#gifts_txt').hide();});
	
	$('#choose_gift').live('click',function(){
		//alert(pcode);
		if(pcode !=''){
			//if(confirm('Sorry,you have used this coupon code already,do you want to cancel the coupon code and choose a gift instead ?'))
			
			if(confirm(flow_shipping_js_choose_gift))
				re_load('/'+js_cur_lang_url + 'm-flow-a-clearCoupon.htm',function(){$('#choose_gift').attr('checked',true);});
			else
				return false;
		}
	});

	$('#choose_coupon').live('click',function(){
		//alert(pcode);
		if(is_include_gifts ==1){
			//if(confirm('Sorry,there is a gift in your cart,it cannot be used with a coupon at the same time,do you want to cancel the gift and use coupon instead ?'))
			if(confirm(flow_shipping_js_choose_coupon))
				re_load('/'+js_cur_lang_url + 'm-flow-a-cleargift.htm',function(){$('#choose_coupon').attr('checked',true);});
			else
				return false;
		}
	});	
	
	
})


	
	function numbers(obj){obj.value=obj.value.replace(/[^\d]/g,'');}	
    
	/*$("#point_ipt").live('keyup', function(){ 
		//积分
		numbers(this);
        $('#hidden-point').val(this.value);
		//caculate_point();	
		jisuan_total();
	});*/
    		
	function caculate_point(){
		var el = $('#point_ipt'), point_money = 0, msg, point;    
		if (typeof(min_use_point) == 'number') {
			point = el.val();
            
			if (point == ''){
				$('#point_tips').html('');
				return point_money;		
			}		
			
			point = parseInt(point); 
			isNaN(point) && (point = 0);
			el.val(point);
			
			var use_point_max = parseInt($('#use_point_max').text());
			
			if (point > use_point_max) {
                point = use_point_max;
				$('#point_ipt, #hidden-point').val(use_point_max);
			}
            
			point_money = (parseFloat(point) * point_rate).toFixed(2);
            msg = ' - '+ point_money + ' USD';
            
            $('#point_tips').html(msg);
		}
        
        return point_money;
	}
			
	jisuan_total();
	
	
	
	$(".shipping_method").live('click', function(){
		shipping_id = $(this).val(); 
		//yunfei      = $("#sm"+shipping_id).html();
		//$("#shipping_sub_total").html(yunfei);
		//$("#free_shipping_sub_total").html(freeyunfei);
		freeyunfei  = parseFloat($("#freesm"+shipping_id).html());
		//shipping_id=$('input[name=shipping][@checked]').val();
		//alert(shipping_id);
		switch (shipping_id){
			case "1":
			   $("#Need_Traking_number_button1").show('slow');
               $("#china_post_desc").hide('slow');
               //var _v = $('#freesm1').html($(".Need_Traking_number").val());
               //$('.Need_Traking_number').attr('checked') && $('#freesm1').html($(".Need_Traking_number").val());
			   //$('#freesm1').html($(".Need_Traking_number").val());
			   //$(".Need_Traking_number").attr("checked",true);
			break;
			
			case "2":
			   $("#Need_Traking_number_button1").hide('slow');
               $("#china_post_desc").hide('slow');
			   //$(".Need_Traking_number").attr("checked",false);
			   //clk_tracking_number();
			   //$('#freesm1').html('0.00');
			break;
			
			case "3":
			   $("#Need_Traking_number_button1").hide('slow');
               $("#china_post_desc").hide('slow');
			   //$(".Need_Traking_number").attr("checked",false);
			   //clk_tracking_number();
			   //$('#freesm1').html('0.00');
			break;
            case "4":
			   $("#china_post_desc").show('slow');
               $("#Need_Traking_number_button1").hide('slow');
               //$(".Need_Traking_number").attr("checked",false);
			   //clk_tracking_number();
			break;
			
			default:
			
			break;
		}
        
        $("#shipping_sub_total").html(freeyunfei);
        $("#shipping_sub_total").attr("orgp",freeyunfei);
		//$("#free_shipping_sub_total").html(freeyunfei);
		jisuan_total();	
	 });
	 function clk_tracking_number(){
		shipid = $(this).attr('shipid');
		thisprice = parseFloat($('#Need_Traking_number_fee'+shipid).html());
		freesmprice = parseFloat($('#freesm'+shipid).html());
        var el = $("#shipping_sub_total"), free_sub_total = el.html();
		if($(this).attr("checked")){
			freesmprice = freesmprice + thisprice
			$('#freesm'+shipid).html(freesmprice.toFixed(2));
			$('#freesm'+shipid).attr("orgp",freesmprice.toFixed(2));
		}else{
			freesmprice = freesmprice - thisprice;
			$('#freesm'+shipid).html(freesmprice.toFixed(2));
			$('#freesm'+shipid).attr("orgp",freesmprice.toFixed(2) );
		}
        el.html(freesmprice.toFixed(2));	 
	 
	 }
     
	$('.Need_Traking_number').live('click', function(){
		shipid = $(this).attr('shipid');
		thisprice = parseFloat($('#Need_Traking_number_fee'+shipid).html());
		freesmprice = parseFloat($('#freesm'+shipid).html());
        var el = $("#shipping_sub_total"), free_sub_total = el.html();
		if($(this).attr("checked")){
			freesmprice = freesmprice + thisprice
			$('#freesm'+shipid).html(freesmprice.toFixed(2));
			$('#freesm'+shipid).attr("orgp",freesmprice.toFixed(2));
		}else{
			freesmprice = freesmprice - thisprice;
			$('#freesm'+shipid).html(freesmprice.toFixed(2));
			$('#freesm'+shipid).attr("orgp",freesmprice.toFixed(2) );
		}
        el.html(freesmprice.toFixed(2));
		jisuan_total();

	});
	
	
    
    $("#bizhong").val($.cookie('bizhong'));

    //保费
	$(".baofei").live('click', function(){
		yunfei  = $("#baofei").html();
		if($(this).attr("checked") == true){ 
			$("#insurance").html(yunfei);
		}else{
			$("#insurance").html(0);
		}
		jisuan_total();
    });
	
	
	function jisuan_total(){
		
		var point_money = 0;
        point_money=$("#hidden-point-money").val(); 
		//point_money=caculate_point();
		//alert(point_money);
		$("#point_money").html(point_money);
		$("#point_money").attr("orgp",point_money);
		point_money = (point_money == undefined)?0:parseFloat(point_money);//积分
		 
		//free_sub_total = $("#free_shipping_sub_total").html();
		//free_sub_total = (free_sub_total == undefined)?0:parseFloat(free_sub_total);//alert(free_sub_total);
		sub_total = $("#shipping_sub_total").html();
        sub_total = (sub_total == undefined)?0:parseFloat(sub_total);//运费
		cheknum = ($('.Need_Traking_number').attr("checked"))?parseFloat($('.Need_Traking_number').val()):0;
        cheknum = 0;
		insurance    = parseFloat($("#insurance").html());//保险费
		xx_sub_total = parseFloat($("#items_sub_total").html());//单价

        price_total=(sub_total - point_money +insurance + xx_sub_total+cheknum).toFixed(2);
		$("#price_total").html(price_total);
        $("#price_total").attr("orgp",price_total);
	}
	
	$(".paymentselect").live('click', function(){
			id = $(this).val();
			if (id == 'PayPal') {
				$("#bt_apple_code").show("slow");
			//	$("#showapp").show("slow");
			}else{
				$("#bt_apple_code").hide("slow");
				$("#showapp").hide("slow");
			}
			
		$(".paymentselect").each(function(){
			sid = $(this).val();
			if (sid==id){
				$("#subpaymentlist"+sid).show("slow");
			}else{
				$("#subpaymentlist"+sid).hide("slow");
			}
			 
	    });
    });
	
	
//});

function chenkpayment(){
	try{
		var obj=document.getElementsByName("payment");
	}
	catch(err)
	{
		return false;
	}
	var l=obj.length;
	
	for(var i=0;i<l;i++)
	{
		if(obj[i].checked==true){
			//document.getElementById("payment_title").className="";
			return true;
		}
	}

	return false;
}

function chenkship(){
	 
	try{
		var obj=document.getElementsByName("shipping");
	}
	catch(err)
	{
		return false;
	}
	var l=obj.length;
	
	for(var i=0;i<l;i++)
	{
		if(obj[i].checked==true){
			//document.getElementById("payment_title").className="";
			return true;
		}
	}

	return false;
}
function checkInfo(){

	if(chenkship()!=true){		
		//alert("Please choose shipping method");
		alert(flow_shipping_js_func_checkInfo_0);
		window.location.hash="ship";
		try{
			document.getElementsByName("shipping")[0].focus();
		}
		catch(err){
			
		}
		return false;
	}	
	
	if(chenkpayment()!=true){
			
		//alert("Please choose payment method");
		alert(flow_shipping_js_func_checkInfo_1);
		window.location.hash="pay";
		try{
			document.getElementsByName("payment")[0].focus();
		}
		catch(err){
			
		}
		return false;
	}

	
	try{
		//alert(typeof(document.getElementById('tel1')));
		//return false;
		if(typeof(document.getElementById('tel1')) =='object' &&$("#tel1").val() == ''){
			alert(users_inc_tel_msg);
			window.location.hash="tel";
			document.getElementById('tel1').focus();
			return false;
		};
		
	}
	catch(err){
			
	}

	
	//if ($("#postscript").val().length>500){
	//	$("#postscript").focus();
	//	alert("The review must be less than 500 chars.");
	//	return false;
	//}
    
    var goods_ids=$("#us_warehouse").val();
    if(goods_ids)
    {
        var bol=check_us_warehouse(goods_ids);
		//alert(bol);
        if(bol)
		{
			//alert(bol);
           return true;
		}
		else
           return false;
    }
    
	return true;
}
//美国仓产品
function check_us_warehouse(goods_ids){
    var bol=0;
    $.ajax({
       type: "POST",
       async:false,
       url: '/'+js_cur_lang_url + "m-flow-a-check_us_warehouse.htm",
       data: 'goods_ids_str='+goods_ids,
       success: function(msg){
                var flag=0;
                //var res_ids='';
                //var del_goods='';
                var del_str='';
                var seach_link='';
                var myarray1 = msg.split("@@");
                
                //检查购物车中是否有美国仓的产品
                for (i = 0; i < myarray1.length; i++) {
                   var myarray2 = myarray1[i].split("|");
                   if(myarray2[5] =="1")
                   {
                     flag=1;//有美国仓的产品
                     //res_ids+=myarray2[0]+",";
                     //del_goods+=myarray2[1]+",";
                     del_str+=myarray2[3]+", ";
                     seach_link+=myarray2[4];
                   }
                }
              
                var country=$("#country_name").val();//alert(country);
                
                //如果购物车中有美国仓产品，但运输国家不是美国的，提示删除该产品
                if(flag==1 && country!='UNITED STATES' && country!='USA')
                {
                    del_str=del_str.substr(0, del_str.length-2);  
                    /*
                    ymPrompt.confirmInfo({message:'" '+del_str+'" is US Only. It will remove from your shopping cart. <br/><br/>'+seach_link,width:400,height:200,title:"System Message",okTxt:'ok',cancelTxt:'cancel',handler:function(tp)
                    {
                        if(tp=='ok')
                        {
                                del_goods=del_goods.substr(0, del_goods.length-1);
                                var myarray3 = res_ids.split(",");
                                for (j = 0; j < myarray3.length; j++)
                                {
                                    //删除该产品
                                    if(myarray3[j])
                                    {
                                        del_link="/m-flow-a-drop_goods-id-"+myarray3[j]+".htm";
                                        gid=myarray3[j];      //alert(del_link);
                                        list_load(del_link,"#del_ajax_msg"+gid,"#cart"+gid);
                                        cart_items();
                                        bol=0;
                                    }
                                }
                        }
                        else
                        {bol=1;}
                            
                    }
                    
                    });//end ymPrompt
                    */ 
                    //ymPrompt.alert({message:'<span style="color:red;">"'+del_str+'" is shipped to US only. For none US address, please remove it from your shopping cart.</span><br/><br/>'+seach_link,width:400,height:230,title:"System Message",handler: function() {window.scrollTo(0,0);},btn:[['ok','ok']]});
                    ymPrompt.alert({message:'<span style="color:red;">"'+del_str+'"'+flow_shipping_js_func_check_us_warehouse_0+'</span><br/><br/>'+seach_link,width:400,height:230,title:"System Message",handler: function() {window.scrollTo(0,0);},btn:[['ok','ok']]});
                    bol=0;
                }
                else
                {bol=1;}

       }
    }); 

    return bol;
 }


function checkcode(obj){
	var obj = $(obj);
	var objvalue = encodeURIComponent(obj.val());
	var huance   = obj.attr('huance');  //huan chun zong jia 
	var isApply  = obj.attr('isApply');  //huan chun shi fou yingyong 
	var huancode = obj.attr('huancode');  //huan chun cu xiao ma , yongyu panduan cuxiaoma sfou you gaidong 
	var total_obj = $('span[entry="all_total_price"]');
	var total_p = parseFloat(huance).toFixed(2);
	
	if (isApply == "1") {
		if (objvalue!=huancode){
			total_obj.html(total_p);
			total_obj.attr('orgp',total_p);
			obj.attr('isApply','0')
			obj.attr('huance','0')
			alert(flow_shipping_js_func_checkcode_0);
		}
	}
	
}

var hitnum = 1;
function showappdiv(showdiv){
	if (hitnum%2 == 0 ){
	    $("#"+showdiv).hide();
		$("#bt_apple_code").attr('class','bt_apple1');
	}else{
	    $("#"+showdiv).show();
		$("#bt_apple_code").attr('class','bt_apple2');
	}
	hitnum++;
}

function code_apply(obj_str){
	if(is_include_gifts>0){
		//alert('Sorry,This offer cannot be used at the same time with the gift');
		alert(flow_shipping_js_func_code_apply_0);
		return false;
	}
	var obj = $("#"+obj_str);	
	var objvalue = encodeURIComponent(obj.val());
	if  (objvalue.length == 0) return false;
	if (objvalue.length > 40){
    	//alert('Promotion code Please do not enter too many characters');
    	alert(flow_shipping_js_func_code_apply_1);
		return false;
	}
	hitnum = 1;
	$('#apply_msg').html('Loading...');
	//document.write('/'+js_cur_lang_url + 'm-flow-a-cart.htm?pcode='+objvalue);
	re_load('/'+js_cur_lang_url + 'm-flow-a-cart.htm?pcode='+objvalue);
	
	
	//alert(222);
}

/**
 * checkout页面事件绑定
 * 
 */
function checkoutBinds() {
    showConsignee();
    saveConsignee();
    checkConsignee();
}

/**
 * checkout页面显示编辑收货地址
 * 
 */
function showConsignee() {
    $('#a-show-consignee').click(function() {
        $('#tb-consignee').show();
        $('#div-consignee').hide();
    });
}

/**
 * cart页面购买了其它产品
 * 
 */
function setAlsoBuoght() {
    $('#div-also-bought').jCarouselLite({
        btnNext: "#detailsrightbar",
        btnPrev: "#detailsleftbar",
        vertical: false,
        play:true,
        auto: 3000,
        visible: 8,
        scroll: 2,
        //mouseWheel: true,
        speed: 800
    });
}

/**
 * checkout页面保存收货地址
 * 
 */
function saveConsignee() {
    $('#btn-save-consignee').click(function() {
        if ($('#form-consignee').valid()) {
            this.disabled = true;
            $.post('/'+js_cur_lang_url + 'm-flow-a-consignee.htm', $('#form-consignee').serialize() + '&from_checkout=1', function() {
				//window.location.href ='';
				window.location.href =window.location.href;
                //re_load('/'+js_cur_lang_url + 'm-flow-a-checkout.htm?ajax=true');
            });
        }
    });
    $('#btn-cancel-consignee').click(function() {
        $('#tb-consignee').hide();
        $('#div-consignee').show();
    });
}

/**
 * checkout页面验证收货地址
 * 
 */
function checkConsignee() {
    $('#form-consignee').validate({
        rules: {
            firstname: {
                required: true,
                maxlength: 60
            },
            lastname: {
                required: true,
                maxlength: 60
            },
            tel: {
                required: true,
                maxlength: 60
            },
            email: {
                required: true,
                maxlength: 60,
                email: true
            },
            addressline1: {
                required: true,
                maxlength: 120
            },
            city: {
                required: true,
                maxlength: 80
            },
            province: {
                required: true,
                maxlength: 80
            },
            country: {
                required: true
            },
            zipcode: {
                required: true,
                maxlength: 20
            }
        },
        messages: {
            firstname: {
                required: firstname_msg,
                maxlength: firstname_maxlength_msg
            },
            lastname: {
                required: lastname_msg,
                maxlength: lastname_maxlength_msg
            },
            tel: {
                required: tel_msg,
                maxlength: tel_maxlength_msg
            },
            email: {
                required: email_msg,
                maxlength: email_maxlength_msg
            },
            addressline1: {
                required: addressline1_msg,
                maxlength: addressline1_maxlength_msg
            },
            city: {
                required: city_msg,
                maxlength: city_maxlength_msg
            },
            province: {
                required: province_msg,
                maxlength: province_maxlength_msg
            },
            country: {
                required: country_msg
            },
            zipcode: {
                required: zipcode_msg,
                maxlength: zipcode_maxlength_msg
            }
        },
        success: function(label) {
            // set &nbsp; as text for IE
            label.html("&nbsp;").addClass("checked");
        }
    });
}


function point_selected(val){
    point_arr=val.split(","); 
    point_arr[0];//积分对应的金额
    point_arr[1];//积分
    $('#hidden-point').val(point_arr[1]);
    $('#point_ipt').val(point_arr[1]);
    $("#hidden-point-money").val(point_arr[0]);
    jisuan_total();
}


