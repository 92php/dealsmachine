// JavaScript Document
var img1=new Image();
var img2=new Image();
var img3=new Image();
img1.src='Images/ico_T.gif';
img2.src='Images/ico_F.gif';
img3.src='Images/spinner_grey.gif';			
function i$(id){
	return document.getElementById(id);
}
var request = false;
function createXMLHttpRequest()
{        
    try{
        xmlHttp = new XMLHttpRequest();
        return xmlHttp;
    }
    catch(trymicrosoft){
        try{
            xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");    
            return xmlHttp;
        }
        catch(othermicrosoft){
            try{
                xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
                return xmlHttp;
            }
            catch(failed){
                return xmlHttp;
            }
        }
    }
   if (!xmlHttp){
    	return false;
   }
}
request=createXMLHttpRequest();
req2=createXMLHttpRequest();
function getInfo(url,func,obj) {
	 obj.open("GET", url, true);
	 obj.setRequestHeader("If-Modified-Since","0"); 
	 obj.onreadystatechange = func;
	 obj.send(null);
}
function insertAfter(newElement,targetElement){
	var parent=targetElement.parentNode;
	if(parent.lastChild==targetElement){
	parent.appendChild(newElement);
	}else{
	parent.insertBefore(newElement,targetElement.nextSibling);
	}
}


f_pwd=0
f_repwd=0
f_email=0


function confirmPWD()
{
	var pwd=i$("userpassword")
	var pNode=document.createElement("lable");
	if(!i$("pwdTips")){
		insertAfter(pNode,pwd);
		pNode.style.color="red";
		pNode.setAttribute("id","pwdTips");
	}
	i$("pwdTips").innerHTML=""
	if(pwd.value==""||pwd.value==null){
			i$("pwdTips").innerHTML="Please Enter password";
			f_repwd=0;
			return false;
	}
	else{
		if(pwd.value.length<6||pwd.value.length>16){
			i$("pwdTips").innerHTML="Use 6 to 16 characters";
			f_repwd=0;
			return false;
		}
	}
	f_repwd=1;
	return true;
}

function confirmPassword()
{
	var RePassword=i$("userpassword1")
	
	if(document.all("RePasswordTips")==null){
		var pNode=document.createElement("lable");
		insertAfter(pNode,RePassword);
		pNode.style.color="red";
		pNode.setAttribute("id","RePasswordTips");
	}
	i$("RePasswordTips").style.display="";
	i$("RePasswordTips").innerHTML=""
	if(RePassword.value!=i$("userpassword").value){
			i$("RePasswordTips").innerHTML="<img src='Images/ico_F.gif'><br/>Your entries must match. Please check both.";
			f_repwd=0;
			return false;
	}
	else{
		if(RePassword.value.length<6||RePassword.value.length>16){
			i$("RePasswordTips").innerHTML="<img src='Images/ico_F.gif'><br/>Use 6 to 16 chars";
			f_repwd=0;
			return false;
			}
	}
	i$("RePasswordTips").innerHTML="<img src='Images/ico_T.gif'>"
	f_repwd=1;
	return true;
}
String.prototype.trim   =   function()
{
         //   用正则表达式将前后空格
         //   用空字符串替代。
         return   this.replace(/(^\s*)|(\s*$)/g,   "");
}
function email()
{
	var uEmail=i$("useremail");
	uEmail.value=uEmail.value.trim();
	
	if(document.all("uEmailTips")==null){
		var pNode=document.createElement("lable");
		insertAfter(pNode,uEmail);
		pNode.style.color="red";
		pNode.setAttribute("id","uEmailTips");
	}
	i$("uEmailTips").style.display="";
	i$("uEmailTips").innerHTML="<img src='Images/spinner_grey.gif'>"
	var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
	if(!p.test(uEmail.value)){
			i$("uEmailTips").innerHTML="<img src='Images/ico_F.gif'><br/>This is not a valid email address";
			f_email=0;
			return false;
	}
	var url = "check_email.asp?email="+i$("useremail").value;
	getInfo(url,checkEmail2,req2);
}
function checkEmail2() {
	 if (req2.readyState == 4) {
		 if (req2.status == 200) {
		 	var response2 = eval("("+req2.responseText+")");
			//alert(request.responseText);
			if(response2=="1"){
			i$("uEmailTips").innerHTML="<img src='Images/ico_T.gif'>"
				f_email=1;
				return true;
		}
		else {
			var str="<img src='Images/ico_F.gif'><br/>This email is in use .<br/>If you have Rregistered in Davismicro.com and <br>forget your password.,<a target='_blank' href='GetPwd.asp?email="+i$("useremail").value+"'>click here</a>.";
			//document.write(str);
			i$("uEmailTips").innerHTML=str;
			f_email=0;
			return false;
			}
		}
	}
}

function password()
{	

	
	var pwd=i$("userpassword")
	
	if(document.all("pwdTips")==null){
		var pNode=document.createElement("lable");
		insertAfter(pNode,pwd);
		pNode.style.color="red";
		pNode.setAttribute("id","pwdTips");
	}
	i$("pwdTips").style.display="";
	i$("pwdTips").innerHTML=""
	if(pwd.value==""||pwd.value==null){
			i$("pwdTips").innerHTML="<img src='Images/ico_F.gif'><br/>Enter the password";
			f_pwd=0;
			return false;
	}
	else{
		if(pwd.value.length<6||pwd.value.length>16){
			i$("pwdTips").innerHTML="<img src='Images/ico_F.gif'><br/>Use 6 to 16 characters";
			f_pwd=0;
			return false;
		}	
	}
	i$("pwdTips").innerHTML="<img src='Images/ico_T.gif'>";
	f_pwd=1;
	if (i$("userpassword1").value.length>0) confirmPassword();
	return true;
}


function checkallInfo(){
	  if(f_pwd==1&&f_repwd==1&&f_email==1)
	  {
		  return true;
	  }
	  else{
	  	  password();confirmPassword();email();
		  if(f_pwd==1&&f_repwd==1&&f_email==1)
		  {
			  return true;
		  }
	  }
	  return false;
}
function email_onfocus(){
	if(document.all("uEmailTips")!=null){
		i$("uEmailTips").style.display="none";
	}
}
function password_onfocus(){
	if(document.all("pwdTips")!=null){
		i$("pwdTips").style.display="none";
	}
}
function confirmPassword_onfocus(){
	if(document.all("RePasswordTips")!=null){
		i$("RePasswordTips").style.display="none";
	}
}
	

	i$("useremail").onblur=email;
	i$("useremail").onfocus=email_onfocus;
	i$("userpassword").onblur= password;
	i$("userpassword1").onblur= confirmPasswor
	i$("userpassword").onfocus= password_onfocus;
	i$("userpassword1").onfocus= confirmPassword_onfocus;	

