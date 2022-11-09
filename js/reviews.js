// JavaScript Document
jQuery.extend({
getCookie : function(sName) {
    var aCookie = document.cookie.split("; ");
    for (var i=0; i < aCookie.length; i++){
      var aCrumb = aCookie[i].split("=");
      if (sName == aCrumb[0]) return decodeURIComponent(aCrumb[1]);
    }
    return '';
},
setCookie : function(sName, sValue, sExpires) {
    var sCookie = sName + "=" + encodeURIComponent(sValue);
    if (sExpires != null) sCookie += "; expires=" + sExpires;
    document.cookie = sCookie;
},
removeCookie : function(sName) {
    document.cookie = sName + "=; expires=Fri, 31 Dec 1999 23:59:59 GMT;";
}
}); 
	function check_btn_signin(){
		var username=$("#username").val();
		var password=$("#password").val();
		if(username!=''){
			var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
			if(!p.test(username)){
				$("#rw_sign_msg").html("<img src='/Images/ico_F.gif'><span style='color:red'>This is not a valid email address</span>");
				return false;
			}
		}
		else{
			$("#rw_sign_msg").html("<img src='/Images/ico_F.gif'><span style='color:red'>Please Enter your email address.</span>");
		}
		if(password ==''){
			$("#rw_sign_msg").html("<img src='/Images/ico_F.gif'><span style='color:red'>Please Enter the password.</span>");
		}else{
			$.ajax({
			  type:"post",
			  data:"mode=ajax&username="+username+"&password="+password,
			  url: "/checklogin.asp",
			  cache: false,
			  timeout: 5000,
              error: function (xmlHttpRequest, errors) {
				  			alert(errors)
                            $("#rw_sign_msg").html("<img src='/Images/ico_F.gif'><span style='color:red'>"+errors+"</span>");
                        },
			  beforeSend :function(){
				  $("#rw_sign_msg").html("<span style='color:green'>Processing...</span>");
				  },
			  success: function(data){
				if(data!='signedin'){
					$("#rw_sign_msg").html("<img src='/Images/ico_F.gif'><span style='color:red'>"+data+"</span>");
				}else{
					nickname = $.getCookie("fullname");
					$("#re_nickname").val(nickname);
					$("#sp_nickname").show();
					$(".login_tips").html('(1000 characters)');
					$(".review_sigin").hide();
				}}
			}); }}
	function is_signin(){
		if(GetCookie("shopzhiwang","username")==null ||GetCookie("shopzhiwang","username")==""|| patt1.test(GetCookie("shopzhiwang","username")) )
			return false;
		return true;
	}
	
	$('.td_reply').click(function(){
		var rid=$(this).attr("rid");
		$('.reply_post').each(function(){$(this).remove()});
		if($(this).hasClass('al')){
			$('.td_reply').each(function(){$(this).removeClass('al')});
		}else{
			var patt1 = /^chn\_\d+$/;
			var str=$('#submit_in').html();
			nickname = $.getCookie("fullname");
			$("#re_nickname_temp").val(nickname);
			if(!is_signin()){
				var replyNode="<tr class='reply_post'>"+str.replace('(1000 characters)','<a class=blue_link href=\'javascript:void(0)\' onclick=\'$(".review_sigin").toggle();\' >sign in</a> or <a class=blue_link href=\'/reg.asp\' >register</a>')+"</tr>";	

			}
			else{
				$("#sp_nickname_temp").show();
				var replyNode="<tr class='reply_post'>"+$('#submit_in').html()+"</tr>";	
			}
			re = /_temp/g; // 创建正则表达式模式。 
    		replyNode = replyNode.replace(re, ""); // 交换每一对单词。
			$('.td_reply').each(function(){$(this).removeClass('al')});
			$(this).toggleClass('al').parent().after(replyNode);
			$("#re_content").attr("rid",rid);
		}
	})
	function post_reply(){
		if(!is_signin()){
			alert("Please sign in");
			$(".review_sigin").show();
			$("#username").focus();
			return false;
		}else{
			var re_content=$(".re_content").val();
			re_content=jQuery.trim(re_content);
			var re_nickname=$("#re_nickname").val();
			re_nickname=jQuery.trim(re_nickname);
			var rid = $("#re_content").attr("rid");
			msg=""
			if(rid==""||isNaN(rid)){
				alert("review did not found.");
				return false;
			}
			if(re_content==""){
				msg+="Please enter the content\r\n";
				$(".re_content").focus();
			}
			if(re_nickname==""){
				msg+="Please enter your nickname\r\n";
				if(msg=="") $("#re_nickname").focus();
			}
			if(msg!=""){alert(msg);return false;}
			bt_img=$("#bt_post_reply").attr("src");
			$.ajax({
			  type:"post",
			  data:{'act':'save_reply','re_content':re_content,'re_nickname':re_nickname,'rid':rid},
			  url: "/submitreview.asp",
			  cache: false,
			  timeout: 5000,
              error: function (xmlHttpRequest, error) {
				  $("#bt_post_reply").attr("src",bt_img);},
			  beforeSend :function(){
				  $("#bt_post_reply").attr("src","/images/1-0.gif");
				  $("#bt_post_reply").attr("disable",'true');
			   },
			  success: function(data){
				   $("#bt_post_reply").attr("src",bt_img);
				   $("#bt_post_reply").attr("disable",'false');
				   $(".re_content").val("");
				if(data=='success'){
					alert("Your review was successfully submitted. Please note that  it may take up to 72 hours for your review to appear.");
				}else{
					nickname = $.getCookie("fullname");
					$("#re_nickname").val(nickname);
					$("#sp_nickname").show();
					$(".login_tips").html('(1000 characters)');
					$(".review_sigin").hide();
					alert(data);
				}
				
			  }
			}); 			
		}}