// JavaScript Document

// JavaScript Document
function i$(id){
	return document.getElementById(id);
}
var f_name,f_email,f_content,f_verification

f_name=0;
f_email=0;
f_title=0;
f_content=0;
f_code=0;

function insertAfter(newElement,targetElement){
	var parent=targetElement.parentNode;
	if(parent.lastChild==targetElement){
	parent.appendChild(newElement);
	}else{
	parent.insertBefore(newElement,targetElement.nextSibling);
	}
}





function checkName()
{
	
	var name=i$("name");
	var pNode=document.createElement("lable");
	if(!i$("nameTips")){
		insertAfter(pNode,name);
		pNode.style.color="red";

		pNode.setAttribute("id","nameTips");
	}
	i$("nameTips").innerHTML="";
	if(name.value.length==0||name.value.length==""){
		i$("nameTips").innerHTML="<img src='/Images/ico_F.gif'><br/>Enter your Nickname!";
		f_name=0;
		return false;
	} 
	else{

		i$("nameTips").innerHTML="<img src='/Images/ico_T.gif'>";
	}
	f_name=1;
	return true;
}

function checkEmail()
{
	
	var uEmail=i$("email");
	var pNode=document.createElement("lable");
	if(!i$("uEmailTips")){
		insertAfter(pNode,uEmail);
		pNode.style.color="red";
		pNode.setAttribute("id","uEmailTips");
	}
	i$("uEmailTips").innerHTML="<img src='/Images/spinner_grey.gif'>"
	var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
	if(!p.test(uEmail.value)){
			i$("uEmailTips").innerHTML="<img src='/Images/ico_F.gif'><br/>This is not a valid email address";
			f_email=0;
			return false;
	}
	i$("uEmailTips").innerHTML="<img src='/Images/ico_T.gif'>"
	f_email=1;
	return true;
}
function checkTitle()
{
	
	var title=i$("title");
	var pNode=document.createElement("lable");
	if(!i$("titleTips")){
		insertAfter(pNode,title);
		pNode.style.color="red";

		pNode.setAttribute("id","titleTips");
	}
	i$("titleTips").innerHTML="";
	if(title.value.length==0||title.value.length==""){
		i$("titleTips").innerHTML="<img src='/Images/ico_F.gif'><br/>Enter  the topic!";
		f_title=0;
		return false;
	} 
	else{

		i$("titleTips").innerHTML="<img src='/Images/ico_T.gif'>";
	}
	f_title=1;
	return true;

}

function checkCode()
{
	var code=i$("code");
	var pNode=document.createElement("lable");
	if(!i$("codeTips")){
		insertAfter(pNode,code);
		pNode.style.color="red";

		pNode.setAttribute("id","codeTips");
	}
	i$("codeTips").innerHTML="";
	
	if(code.value.length==0||code.value==""){
		i$("codeTips").innerHTML="<img src='/Images/ico_F.gif'>Enter the verify code at the left .";
		f_code=0;
		return false;
	} 
	if(code.value.length!=4){
		i$("codeTips").innerHTML="<img src='/Images/ico_F.gif'>Use 4 numbers .";
		f_code=0;
		return false;	}
	
	i$("codeTips").innerHTML="<img src='/Images/ico_T.gif'>";
	
	f_code=1;
	return true;
}
function checkcontent(minLen,maxLen){
	
	 
	var content=i$("content")
	i$("content_tips").style.display=""
	if(content.value.length<4 ||content.value.length>500){
		i$("content_tips").innerHTML="<img src='/Images/ico_F.gif'>Use 4 to 500 characters";	
		f_content=0;
		return false;
	}
	if(content.value==""||content.value==null){
			i$("content_tips").innerHTML="Review content is required!";
			f_content=0;
			
			return false;
			
	}
	else{
		if(content.value.length<minLen||content.value.length>maxLen){
			i$("content_tips").innerHTML="Review content Use "+minLen.toString()+" to "+maxLen.toString()+" characters";
			f_content=0;
		
			return false;
		}	
	}
	i$("content_tips").innerHTML="";
	i$("content_tips").style.display="none"

	f_content=1;
	return true;
}

function checkMaxInput(obj,maxLen) {
	if (obj.value.length > maxLen){ 
		document.getElementById("summarytip").innerHTML =0;
		return false;
	}
	else {
			document.getElementById("summarytip").innerHTML = maxLen - obj.value.length;
			return true;
	}
}
function checkallInfo()
{


	if(f_name==1&&f_email==1&&f_content==1&&f_title==1&&f_code==1)
	{
		return true;
	}
	else
	{
		checkName();checkEmail();checkTitle();checkcontent(4,200);checkCode();
		return false;
	}
}


i$("name").onblur=checkName;
i$("email").onblur= checkEmail;
i$("title").onblur=checkTitle;
i$("code").onblur= checkCode;
i$("content").onblur=checkcontent;




