host = 'http://'+window.location.host;


function signup(){
	
	
	
}




function signin(){
	
}






$(document).ready(function(){
						   
						   
	if ($('.grid_tb').html() != undefined){			   
		$('.grid_tb').tablegrid(); 
		$('.grid_tb').tablegrid({
			oddColor  : '#ffffff', //奇
			evenColor : '#f4f8fb', //偶
			overColor : '#ffffec', //悬
			selColor  : '#FFCC99', //中
			useClick  : false       //是否启用点击选中
		});
	}
	
	var validator = $("#signupform").validate({
		rules: {
			email1: {
				required: true,
				maxlength: 60,
				email: true,
				remote: '/'+js_cur_lang_url + "index.php?m=users&a=check_email"
			},
			password: {
				required: true,
				maxlength: 60,
				minlength: 6
			},
			password_confirm: {
				required: true,
				minlength: 6,
				maxlength: 60,
				equalTo: "#password"
			},
			verifcode:{
				required: true,
				remote: "/fun/?act=chk_ver"
			}
		},
		messages: {
			email1: {
				required: js_email_require_msg,
				minlength: js_email_require_length,
				remote: jQuery.format(js_email_in_user)
			},
			password: {
				required: js_password,
				rangelength: jQuery.format(Enter_at_least)
			},
			password_confirm: {
				required: Repeat_your_password,
				minlength: jQuery.format(Enter_at_least),
				equalTo: Enter_the_same_password_as_above
			},
			verifcode:{
				required: Please_enter_verification_code,
				minlength: Enter_the_characters_on_picture,
				remote: jQuery.format(Verification_code_you_entered_is_incorrect)
			}
		},
		// specifying a submitHandler prevents the default submit, good for the demo
		submitHandler: function() {
			
			email = encodeURIComponent($('#email1').val());
			password = encodeURIComponent($('#password').val());
			flow = $('#flow').val();
			ref = $('#ref').val();
			verifcode = $('#verifcode').val();
			$('#subCreate').attr("disabled",true);
			$.ajax({
				type: "POST",
				url: '/'+js_cur_lang_url + "m-users-a-a_join.htm",
				data: "email1="+email+"&password="+password+"&verifcode="+verifcode,
				dataType:"text",
				beforeSend:function(){
					$("#ajaxmsg1").html("<img src='/temp/skin1/images/990000_bai.gif' id='verify' > Creating ...</div>");	
                    }, 
				success: function(msg){
					
			        //alert(msg);
					$("#ajaxmsg1").html(msg);
					if (msg.indexOf('ok')>=0){
				        msg = Creating_successful;
					    $("#ajaxmsg1").html(msg);
						if(ref != ''){
							window.location.href=ref; //返回
						}else{
							if (flow == 'checkout'){
								window.location.href=DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-checkout.htm'; //返回购物车
							}else{location.href=DOMAIN_USER+'/'+js_cur_lang_url + "m-users.htm";}
						}
					}
					else{
						$('#subCreate').attr("disabled",false);
					//	var timenow = new Date().getTime();
					//	$('#verify').attr({ src:'/fun/verify.php?rand='+timenow});
					}
					
					$('#email1').val('');
					$('#password').val('');
				}
			}); 
		},
		// set this class to error-labels to indicate valid fields
		success: function(label) {
			// set &nbsp; as text for IE
			label.html("&nbsp;").addClass("checked");
		}
	});
						   
	var validator = $("#signinform").validate({
		rules: {
			email: {
				required: true,
				maxlength: 60,
				email: true
			},
			passwordsign: {
				required: true,
				maxlength: 60
			}
		},
		messages: {
			email: {
				required: js_email_require_msg,
				minlength: js_email_require_length
			},
			passwordsign: {
				required: js_password
			}
		},
		submitHandler: function() {
			email = encodeURIComponent($('#email').val());
			password = encodeURIComponent($('#passwordsign').val());
			flow = $('#flow').val();
			ref = $('#ref').val();
			$.ajax({
				   type: "POST",
				   url: '/'+js_cur_lang_url + "m-users-a-act_sign.htm",
				   data: "email="+email+"&password="+password,
				   dataType:"text",
				beforeSend:function(){$("#ajaxmsg").html("<img src='/temp/skin1/images/990000_bai.gif' id='verify' > Signing ...</div>");	},
				success: function(msg){
					$("#ajaxmsg").show();
					$('#passwordsign').val('');
					$("#ajaxmsg").html(msg);
					if (msg.indexOf('Success')>=0){
						msg = js_success_msg;
						$("#ajaxmsg").attr("style","color:#060"); 
						$("#ajaxmsg").html(msg);
						if(ref != ''){
							window.location.href=ref; //返回
						}else{
							if (flow == 'checkout'){
								window.location.href=DOMAIN_CART+'/'+js_cur_lang_url + 'm-flow-a-checkout.htm'; //返回购物车
							}else{location.href=DOMAIN_USER+'/'+js_cur_lang_url + "m-users.htm";}
						}
					}					
					}
				}); 
			},
		success: function(label) {
			label.html("&nbsp;").addClass("checked");
		}
	});
						   
		//检测修改密码是否有填写密码
	var validator = $("#modpassword").validate({
		rules: {
			old_password: {
				required: true,
				maxlength: 60,
				minlength: 6
			},
			new_password: {
				required: true,
				maxlength: 60,
				minlength: 6
			},
			comfirm_password: {
				required: true,
				minlength: 6,
				maxlength: 60,
				equalTo: "#new_password"
			}
		},
		messages: {
			old_password: {
				required: user_js_modpassword_0,
				rangelength: jQuery.format(user_js_modpassword_1)
			},
			new_password: {
				required: user_js_modpassword_0,
				rangelength: jQuery.format(user_js_modpassword_1)
			},
			comfirm_password: {
				required: user_js_modpassword_2,
				minlength: jQuery.format(user_js_modpassword_1),
				equalTo: user_js_modpassword_3
			}
		},
		success: function(label) {
			label.html("&nbsp;").addClass("checked");
		}
	});					   
	
		
	//重载验证码
	$('#flashverify').click(function(){
		var timenow = new Date().getTime();
		$('#verify').attr({ src:'/fun/verify.php?rand='+timenow}); 
	})
	
	//检测密码强度
	//$("#password").keyup(function(){
	//	pwd = $("#password").val();						 
	//	checkIntensity(pwd)							 
	//});
	
	
//邮箱修改
 $("#form-edit_email").validate({
            errorClass: 'invalid',
            rules: {
                email: {
                    required: true,
                    maxlength: 60,
                    email: true
                }
            },
            messages: {
                email: {
                    required: reviews_func_getpassInput_0,
                    maxlength: 'too much char'
                }
            },
            success: function(label) {
                label.html('&nbsp;').addClass('valid');
            },
            submitHandler: function() {
                var _email = $('#txt-email').val(), _tr = $('#tr-confirm');
                $('#strong-email').html() != _email && $('#txt-confirm_code').val('');
                $('#strong-email').html(_email);
                var _confirm_code = $('#txt-confirm_code').val();
                
                $.post('/m-users-a-edit_email.html', {
                    email: _email,
                    confirm_code: _confirm_code
                }, function(data) {
                    try {
                        data = eval('(' + data + ')');
                        data.confirm_code ? _tr.show() : _tr.hide();
                        
                        if (data.success) {
                            var method = data.confirm_code ? '' : 'succeedInfo', msg = 'Edit Successfully';
                        }
                        else {
                            var method = 'errorInfo', msg = data.error;
                        }
                    } 
                    catch (e) {
						
                        log(e);
                        var method = 'errorInfo', msg = 'Edit Failed';
                    }
                    
                    method &&
                    ymPrompt[method]({
                        message: msg,
                        width: 300,
                        height: 160,
                        title: 'Notice information',
                        btn: [['OK', 'yes']]
                    });
                });
                return false;
            }
        });		
	


	//检测邮寄地址是否正确
	$(".theAddressForm").each(function(){
		var validator = $(this).validate({
			rules: {
				firstname: {required: true,maxlength: 35},
				lastname: {required: true,maxlength: 35},
				tel:{required: true,maxlength: 15},
				email: {required: true,maxlength: 60,email: true},
				addressline1: {required: true,maxlength: 35},
				addressline2: {maxlength: 35},
				city: {required: true,maxlength: 35},
				province: {required: true,maxlength: 35},
				country: {required: true,maxlength: 130},
				zipcode: {required: true,maxlength: 10}
			},
			messages: {
				firstname: {required: firstname_msg,maxlength: firstname_maxlength_msg},
				lastname: {required: lastname_msg,maxlength: lastname_maxlength_msg},
				tel: {required: tel_msg,maxlength: tel_maxlength_msg},
				email: {required: email_msg,maxlength: email_maxlength_msg},
				addressline1: {required: addressline1_msg,maxlength: addressline1_maxlength_msg},
				addressline2: {maxlength: addressline1_maxlength_msg},
				city: {required: city_msg,maxlength: city_maxlength_msg},
				province: {required: province_msg,maxlength: province_maxlength_msg},
				country: {required:country_msg},
				zipcode: {required: zipcode_msg,maxlength: zipcode_maxlength_msg}
			},
			success: function(label) {
				// set &nbsp; as text for IE
				label.html("&nbsp;").addClass("checked");
			}
		});
	});

    if ($(".checkprofile").html() != undefined){
		var validator = $(".checkprofile").validate({
			rules: {
				firstname: {required: true,maxlength: 60},
				lastname: {required: true,maxlength: 60}
			},
			messages: {
				firstname: {required: firstname_msg,maxlength: firstname_maxlength_msg},
				lastname: {required: lastname_msg,maxlength: lastname_maxlength_msg}
			},
			success: function(label) {
				// set &nbsp; as text for IE
				label.html("&nbsp;").addClass("checked");
			}
		});
	}



    if ($(".mesform").html() != undefined){
		var validator = $(".mesform").validate({
			rules: {
				msg_title: {required: true,maxlength: 60},
				msg_content: {required: true,maxlength: 500}
			},
			messages: {
				msg_title: {required: msg_title_msg,maxlength: msg_title_maxlength_msg},
				msg_content: {required: msg_content_msg,maxlength: msg_content_maxlength_msg}
			},
			success: function(label) {
				// set &nbsp; as text for IE
				label.html("&nbsp;").addClass("checked");
			}
		});
	}




   // 

	$("#zhuangtai").livequery("click",function(){
		if($(this).attr("checked") == true){ 
			$("#ChangeOrderState").attr("disabled",false);
		}else{
			$("#ChangeOrderState").attr("disabled",true);
		}
	});
	
	//更改订单状态
    $("#ChangeOrderState").livequery("click",function(){
		var zhuangtai = $("#zhuangtai").val();
		var posturl = window.location.href;
		if (zhuangtai == undefined) return false;
			$.ajax({
				type: "POST",
				data:{'status':zhuangtai},
				url: posturl,
				beforeSend:function(){$("#load_ajax_msg").html(" <img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > Processing ...");	}, 
				success: function(msg){
				$("#load_ajax_msg").html('');
					var stext = $(msg).find('#userright').html();
					$('#userright').html(stext);
					//show_my_shop_price('#shipajax');
					//$(".bizhong").html($.cookie('bizhong'));
				} 
			});

	});
	
	
	$('#getpassword').click(function(){
		 ymPrompt.setDefaultCfg({okTxt:' Send ',cancelTxt:' Cancel ',closeTxt:'Close',minTxt:'Minimize',maxTxt:'Maximize'});
         ymPrompt.confirmInfo({icoCls:'',msgCls:'confirm',message:user_js_getpassword_0+"<br><input type='text' id='myInput' onfocus='this.select()' />",title:user_js_getpassword_1,height:150,handler:getpassInput,autoClose:false});	
	});
	
	
});

	function getpassInput(tp){
		if(tp!='ok') return ymPrompt.close();
		var v=$('#myInput').val();
		v = v.replace('-','=');
		if(v=='' || v.indexOf('@')<0 || v.indexOf('.')<0  )
			alert(user_js_func_getpassInput_0);
		else{
			window.location.href = '/'+js_cur_lang_url + 'm-users-a-send_pwd_email_check-e-'+v+'.htm';
			ymPrompt.close();
		}
	};



	  





/* *
 * 检测密码强度
 * @param       string     pwd     密码
 */
function checkIntensity(pwd)
{
  var Mcolor = "#FFF",Lcolor = "#FFF",Hcolor = "#FFF";
  var m=0;

  var Modes = 0;
  for (i=0; i<pwd.length; i++)
  {
    var charType = 0;
    var t = pwd.charCodeAt(i);
    if (t>=48 && t <=57)
    {
      charType = 1;
    }
    else if (t>=65 && t <=90)
    {
      charType = 2;
    }
    else if (t>=97 && t <=122)
      charType = 4;
    else
      charType = 4;
    Modes |= charType;
  }

  for (i=0;i<4;i++)
  {
    if (Modes & 1) m++;
      Modes>>>=1;
  }

  if (pwd.length<=4)
  {
    m = 1;
  }

  switch(m)
  {
    case 1 :
      Lcolor = "2px solid red";
      Mcolor = Hcolor = "2px solid #DADADA";
    break;
    case 2 :
      Mcolor = "2px solid #f90";
      Lcolor = Hcolor = "2px solid #DADADA";
    break;
    case 3 :
      Hcolor = "2px solid #3c0";
      Lcolor = Mcolor = "2px solid #DADADA";
    break;
    case 4 :
      Hcolor = "2px solid #3c0";
      Lcolor = Mcolor = "2px solid #DADADA";
    break;
    default :
      Hcolor = Mcolor = Lcolor = "";
    break;
  }
  document.getElementById("pwd_lower").style.borderBottom  = Lcolor;
  document.getElementById("pwd_middle").style.borderBottom = Mcolor;
  document.getElementById("pwd_high").style.borderBottom   = Hcolor;

}



/**
 * 删除订单
 * 
 */
function deleteOrder() {
   // ymPrompt.setDefaultCfg({
  //      closeTxt: 'Close'
 //   });
    
    $('.a-delete_order').click(function() {
        
        if (confirm(user_js_func_deleteOrder_0)) {
            var el = this;
            var idArr = this.id.split('-');
            var id    = idArr[1], pid = idArr[2];
                // alert((pid == undefined ? 'bid' : 'id') + '=' + id + '&pid=' + pid);
            $.post('/m-users-a-delete_order.html', (pid == undefined ? 'bid' : 'id') + '=' + id + '&pid=' + pid, function(data) {
                var msg = user_js_func_deleteOrder_1, fun = 'errorInfo';
                //alert(data);
                if (data) {
                    fun = 'succeedInfo';
                    msg = data;
					
                    $(el).parents('tr:first').remove();
					ymPrompt[fun]({
                    message: data,
					okTxt:'OK',
                    width: 350,
                    height: 170,
                    title: user_js_func_deleteOrder_2,
                    btn: [['OK']]
                	});
                }
                

            });
        };
        
        return false;
    });
}
function log() {
    var len = arguments.length;
    
    if (typeof(console) != 'undefined') {
        var i = 0;
        
        for (i = 0; i < len; i++) {
            console.log(arguments[i]);
        }
    }
}


