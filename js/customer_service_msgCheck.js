// JavaScript Document

// JavaScript Document
function i$(id){
	return document.getElementById(id);
}
var f_name,f_email,f_content,f_verification

f_orderno=0;
f_email=0;
f_paymentID=0;
f_PaymentMethod=0;
f_phone=0;
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
function checkOrder()
{
	var name=i$("orderno");
	var pNode=document.createElement("lable");
	if(!i$("ordernoTips")){
		insertAfter(pNode,name);
		pNode.style.color="red";

		pNode.setAttribute("id","ordernoTips");
	}

	i$("ordernoTips").innerHTML="";
	if(name.value.length==0||name.value.length==""){
		i$("ordernoTips").innerHTML="<img src='../Images/ico_F.gif'><br/>Enter your order number!";
		f_orderno=0;
		return false;
	} 
	else{

		i$("ordernoTips").innerHTML="<img src='../Images/ico_T.gif'>";
	}
	f_orderno=1;
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
	i$("uEmailTips").innerHTML="<img src='../Images/spinner_grey.gif'>"
	var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/ ;
	if(!p.test(uEmail.value)){
			i$("uEmailTips").innerHTML="<img src='../Images/ico_F.gif'><br/>This is not a valid email address";
			f_email=0;
			return false;
	}
	i$("uEmailTips").innerHTML="<img src='../Images/ico_T.gif'>"
	f_email=1;
	return true;
}
function check_paymentID()
{
	
	var paymentID=i$("paymentID");
	var pNode=document.createElement("lable");
	if(!i$("paymentIDTips")){
		insertAfter(pNode,paymentID);
		pNode.style.color="red";

		pNode.setAttribute("id","paymentIDTips");
	}
	i$("paymentIDTips").innerHTML="";
	if(paymentID.value.length==0||paymentID.value.length==""){
		i$("paymentIDTips").innerHTML="<img src='../Images/ico_F.gif'><br/>Enter your payment ID please!";
		f_paymentID=0;
		return false;
	} 
	else{

		i$("paymentIDTips").innerHTML="<img src='../Images/ico_T.gif'>";
	}
	f_paymentID=1;
	return true;

}

function checkPhone()
{
	
	var title=i$("phone");
	var pNode=document.createElement("lable");
	if(!i$("phoneTips")){
		insertAfter(pNode,title);
		pNode.style.color="red";

		pNode.setAttribute("id","phoneTips");
	}
	i$("phoneTips").innerHTML="";
	if(title.value.length==0||title.value.length==""){
		i$("phoneTips").innerHTML="<img src='../Images/ico_F.gif'><br/>Enter the your phone number!";
		f_title=0;
		return false;
	} 
	else{

		i$("phoneTips").innerHTML="<img src='../Images/ico_T.gif'>";
	}
	f_phone=1;
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
function checkcontent(minLen,maxLen){	 
	var content=i$("content")
	i$("content_tips").style.display=""
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

function checkPaymentMethod(){

	var title=i$("PaymentMethod");
	var pNode=document.createElement("lable");

	if(i$("PaymentMethodTips")){
		  i$("PaymentMethodTips").innerHTML="";
	}
	
	insertAfter(pNode,title);	
	pNode.style.color="red";
	pNode.setAttribute("id","PaymentMethodTips");

	i$("PaymentMethodTips").innerHTML="";
	if(i$("PaymentMethod").selectedIndex==0 || i$("PaymentMethod").selectedIndex==""){


		i$("PaymentMethodTips").innerHTML="<img src='../Images/ico_F.gif'><br/>Select your payment method please!";
		f_PaymentMethod=0;
		return false;
	}
	else{
		i$("PaymentMethodTips").innerHTML="<img src='../Images/ico_T.gif'>";
		f_PaymentMethod=1;
	}
	return true;	
}
function checkallInfo()
{


	if(f_orderno==1&&f_email==1&&f_content==1&&f_PaymentMethod==1&&f_paymentID==1&&f_code==1&&f_phone==1)
	{
		return true;
	}
	else
	{
		checkEmail();checkcontent(4,200);checkPaymentMethod();check_paymentID();checkCode();checkOrder();checkPhone();
		if(f_orderno==1&&f_email==1&&f_content==1&&f_paymentID==1&&f_code==1&&f_phone==1)
		{
			return true;
		}
		return false;
	}
}


i$("orderno").onblur=checkOrder;
i$("email").onblur= checkEmail;
i$("paymentID").onblur=check_paymentID;
i$("paymentMethod").onblur=checkPaymentMethod;
i$("code").onblur= checkCode;
i$("content").onblur=checkcontent;
i$("phone").onblur=checkPhone;



