$(document).ready(function(){
	var validator = $("#signupform").validate({
		rules: {
			email: {
				required: true,
				maxlength: 60,
				email: true,
				remote: '/'+js_cur_lang_url + "index.php?m=users&a=check_email"
			},
			password: {
				required: true,
				maxlength: 60,
				minlength: 5
			},
			password_confirm: {
				required: true,
				minlength: 5,
				maxlength: 60,
				equalTo: "#password"
			},
			verifcode:{
				required: true,
				remote: "/fun/?act=chk_ver"
			}
		},
		messages: {
			email: {
				required: reviews_func_getpassInput_0,
				minlength: reviews_func_getpassInput_0,
				remote: jQuery.format(flow_sign_js_signupform_0)
			},
			password: {
				required: user_js_modpassword_0,
				rangelength: jQuery.format(user_js_modpassword_1)
			},
			password_confirm: {
				required: user_js_modpassword_2,
				minlength: jQuery.format(user_js_modpassword_1),
				equalTo: user_js_modpassword_3
			},
			verifcode:{
				required: flow_sign_js_signupform_1,
				minlength: flow_sign_js_signupform_2,
				remote: jQuery.format(flow_sign_js_signupform_3)
			}
		},
		success: function(label) {
			// set &nbsp; as text for IE
			label.html("&nbsp;").addClass("checked");
		}
	});
	
		
	//重载验证码
	$('#flashverify').click(function(){
		var timenow = new Date().getTime();
		$('#verify').attr({ src:'/fun/verify.php?rand='+timenow}); 
	})
	
	//检测密码强度
	$("#password").keyup(function(){
		pwd = $("#password").val();						 
		checkIntensity(pwd)							 
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
				maxlength: 60,
				minlength: 5
			}
		},
		messages: {
			email: {
				required: reviews_func_getpassInput_0,
				minlength: reviews_func_getpassInput_0
			},
			passwordsign: {
				required: user_js_modpassword_0,
				rangelength: jQuery.format(user_js_modpassword_1)
			}
		},
		success: function(label) {
			label.html("&nbsp;").addClass("checked");
		}
	});
	
	
	$('#getpassword').click(function(){
		 ymPrompt.setDefaultCfg({okTxt:' Send ',cancelTxt:' Cancel ',closeTxt:'Close',minTxt:'Minimize',maxTxt:'Maximize'});
         ymPrompt.confirmInfo({icoCls:'',msgCls:'confirm',message:user_js_getpassword_0+"<br><input type='text' id='myInput' onfocus='this.select()' />",title:reviews_func_forgetpsw_1,height:150,handler:getpassInput,autoClose:false});	
	});
	
});

	  
	function getpassInput(tp){
		if(tp!='ok') return ymPrompt.close();
		var v=$('#myInput').val();
		
		if(v=='' || v.indexOf('@')<0 || v.indexOf('.')<0  )
			alert(reviews_func_getpassInput_0);
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



