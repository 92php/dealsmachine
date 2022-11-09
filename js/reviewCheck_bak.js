
function i$(id){
	return document.getElementById(id);
}
var f_name,f_email,f_content,f_verification

f_name=0;
f_email=0;
f_title=0;
f_content=0;

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
	var pNode=document.createElement("span");
	if(!i$("nameTips")){
		insertAfter(pNode,name);
		pNode.style.color="red";

		pNode.setAttribute("id","nameTips");
	}
	i$("nameTips").innerHTML="";
	if(name.value.length==0||name.value.length==""){
		i$("nameTips").innerHTML="<img src='../Images/ico_F.gif'><div>Enter your Nickname!</div>";
		f_name=0;
		return false;
	} 
	else{

		i$("nameTips").innerHTML="<img src='../Images/ico_T.gif'>";
	}
	f_name=1;
	return true;
}

function checkEmail()
{
	
	var uEmail=i$("email");
	var pNode=document.createElement("span");
	if(!i$("uEmailTips")){
		insertAfter(pNode,uEmail);
		pNode.style.color="red";
		pNode.setAttribute("id","uEmailTips");
	}
	i$("uEmailTips").innerHTML="<img src='../Images/spinner_grey.gif'>"
	var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
	if(!p.test(uEmail.value)){
			i$("uEmailTips").innerHTML="<img src='../Images/ico_F.gif'><div style='display:block'>This is not a valid email address.</div>";
			f_email=0;
			return false;
	}
	i$("uEmailTips").innerHTML="<img src='../Images/ico_T.gif'>"
	f_email=1;
	return true;
}
function checkTitle()
{
	
	var title=i$("title");
	var pNode=document.createElement("span");
	if(!i$("titleTips")){
		insertAfter(pNode,title);
		pNode.style.color="red";

		pNode.setAttribute("id","titleTips");
	}
	i$("titleTips").innerHTML="";
	if(title.value.length==0||title.value.length==""){
		i$("titleTips").innerHTML="<img src='../Images/ico_F.gif'><div>Enter  the review title!</div>";
		f_title=0;
		return false;
	} 
	else{

		i$("titleTips").innerHTML="<img src='../Images/ico_T.gif'>";
	}
	f_title=1;
	return true;

}

function checkCode()
{
	var code=i$("code");
	var pNode=document.createElement("span");
	if(!i$("codeTips")){
		insertAfter(pNode,code);
		pNode.style.color="red";

		pNode.setAttribute("id","codeTips");
	}
	i$("codeTips").innerHTML="";
	
	if(code.value.length==0||code.value==""){
		i$("codeTips").innerHTML="<img src='../Images/ico_F.gif'>Enter the verify code at the left .";
		f_code=0;
		return false;
	} 
	if(code.value.length!=4){
		i$("codeTips").innerHTML="<img src='../Images/ico_F.gif'>Use 4 numbers .";
		f_code=0;
		return false;	}
	
	i$("codeTips").innerHTML="<img src='../Images/ico_T.gif'>";
	
	f_code=1;
	return true;
}
function checkcontent(){
	var minLen=4;
	var maxLen=500;
	
	 
	var content=i$("reviewcontent");
	
	i$("content_tips").style.display="";
	
	
	if(content.value.length<4 ||content.value.length>500){
		
		i$("content_tips").innerHTML="<img src='../Images/ico_F.gif'>Use 4 to 500 characters";	
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
			document.getElementById("summarytip").innerHTML = obj.value.length;
			return true;
	}
}
function checkallInfo()
{

	//$('#review_msg').hidden();
	if(f_name==1&&f_email==1&&f_content==1&&f_title==1)
	{
		sendreview();
	}
	else
	{
		checkName();checkEmail();checkTitle();checkcontent(4,200);
		if(f_name==1&&f_email==1&&f_content==1&&f_title==1)
		{
			sendreview();
		}		
		return false;
	}
}
function sendreview(){
	
	var id=$('#id').val();
	var rates=$('#rates').val();
	var name=$('#name').val();
	var email=$('#email').val();
	var title=$('#title').val();
	$('#review_submit').attr("disabled",true);
	var reviewcontent=$('#reviewcontent').val();
	$.post("/ReviewsSave.asp", { id: id, rates:rates,name:name,email:email,title:title,reviewcontent:reviewcontent},
  	function(data){
		
	  if (data=='ok'){
		  $('#review_msg').show();
		  //document.getElementById("review_form").reset();
		  review_form.reset(); 
		  f_name=0;
		  $('#name_tips').val('');
		  $('#email_tips').val('');
		  $('#rating_tips').val('');
		  $('#summarytip').html(0);
		  $('#review_submit').attr("disabled",false);
		  window.location="#write_review";
	}
	else{
		alert(data);
	}
  }); 
}


i$("name").onblur=checkName;
i$("email").onblur= checkEmail;
i$("title").onblur=checkTitle;
i$("reviewcontent").onblur=checkcontent;

$(document).ready(function(){
  $('a[href*=#]').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'')
    && location.hostname == this.hostname) {
      var $target = $(this.hash);
      $target = $target.length && $target
      || $('[name=' + this.hash.slice(1) +']');
      if ($target.length) {
        var targetOffset = $target.offset().top;
        $('html,body')
        .animate({scrollTop: targetOffset}, 500);
       return false;
      }
    }
  });
});



