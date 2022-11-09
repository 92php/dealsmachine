// JavaScript Document

function i$(id){
	return document.getElementById(id);
}
function numbers(obj){
	obj.value=obj.value.replace(/[^\d\.]/g,'');
}

function chenkpayment(){
	 
	try{
		var obj=document.getElementsByName("paymentid");
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
		var obj=document.getElementsByName("shipid");
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
	
	
	if(chenkpayment()!=true){
			
		alert("Please choose payment method");
		window.location.hash="pay";
		try{
			document.getElementsByName("paymentid")[0].focus();
		}
		catch(err){
			
		}
		return false;
	}
	
	if(chenkship()!=true){		
		alert("Please choose shipping method");
		window.location.hash="ship";
		try{
			document.getElementsByName("shipid")[0].focus();
		}
		catch(err){
			
		}
		return false;
	}	
	
	if (i$("review").value.length>500){
		i$("review").select();
		alert("The review must be less than 500 chars.");
		return false;
	}
	//i$("img_loading_tips").style.display="";
	//i$("btn_sumbit_order").disabled="true";
	return true;
}
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
var insurance,pro_total,shipping;


var promo_min_money=0;
var promo_cut=0;
var pro_total_after_dis=pro_total;


function changeShip(p){
	var total=0;
	total=pro_total+p-cheapen;
	shipping=p;
	if (i$("insurance_checked").checked){
		total=pro_total+p+insurance-cheapen;
	}
	i$("total").innerHTML=total.toFixed(2);
	i$("total").setAttribute("USD",total);

	if(shipping!=0){
		i$("shipping").innerHTML=shipping.toFixed(2);
		i$("shipping").setAttribute("USD",shipping);
	}
	else{
		i$("shipping").innerHTML="<font color=red>Free Shipping</font>";
		i$("shipping").setAttribute("USD", 0);
	}
	change_currency();
	
}
function change_insurance_check(b){
	if(b){
		var t=insurance+pro_total+shipping-cheapen;
		i$("total").innerHTML=t.toFixed(2);
		i$("total").setAttribute("USD", t);
		if(shipping!=0){
			i$("shipping").innerHTML=shipping.toFixed(2);
			i$("shipping").setAttribute("USD", shipping);
		}
		else{
			i$("shipping").innerHTML="<font color=red>Free Shipping</font>";
			i$("shipping").setAttribute("USD", 0);
		}	
		i$("show_insurance").innerHTML=insurance.toFixed(2);
		i$("show_insurance").setAttribute("USD", insurance);
	}
	else{
		i$("total").innerHTML=(pro_total+shipping-cheapen).toFixed(2);
		i$("total").setAttribute("USD", (pro_total+shipping-cheapen));	
		i$("show_insurance").innerHTML="0.00";
		if(shipping==0){
			i$("shipping").innerHTML="<font color=red>Free Shipping</font>";
			i$("shipping").setAttribute("USD", 0);
		}
		else{
			i$("shipping").innerHTML=(shipping).toFixed(2);
			i$("shipping").setAttribute("USD", shipping);
			
		}
	
		i$("show_insurance").setAttribute("USD", 0);
	}
	change_currency();
}
function changeCoupon(p){
	
	cheapen=p;
	var total=0
	pro_total_after_dis=pro_total-cheapen;
	total=pro_total+shipping-cheapen;
	if (i$("insurance_checked").checked){
		total=pro_total+shipping+insurance-cheapen;
	}
	i$("total").innerHTML=total.toFixed(2);
	i$("total").setAttribute("USD", total);
	
	i$("cheapen").innerHTML=pro_total_after_dis.toFixed(2);
	i$("cheapen").setAttribute("USD", pro_total_after_dis);
	change_currency();
}
function addPromo(p){
	
	cheapen=p;
	var total=0
	pro_total_after_dis=pro_total-cheapen;
	total=pro_total+shipping-cheapen;
	if (i$("insurance_checked").checked){
		total=pro_total+shipping+insurance-cheapen;
	}
	i$("total").innerHTML=total.toFixed(2);
	i$("total").setAttribute("USD", total);
	
	i$("cheapen").innerHTML=pro_total_after_dis.toFixed(2);
	i$("cheapen").setAttribute("USD", pro_total_after_dis);
	i$("td_cheapen").style.display="";
	i$("td_cheapen_price").style.display="";
	change_currency();
}

function updateCart(obj){
	if(parseInt(obj.id)!=obj.value){
		i$("div_quantity_modify_"+obj.id).style.display="block";
	}
	else{
		i$("div_quantity_modify_"+obj.id).style.display="none";
	}
}
function cancel_modify_quantity(strID){  
	i$("div_quantity_modify_"+strID).style.display="none";
	i$(strID).value=strID;
}
function showExplain(id,e){   

	for (i=1;i<= payment_count;i++){
		//alert("payDescript"+String(i));
		i$("payDescript"+String(i)).style.display="none";
	}

	if (id !=""){
		i$(id).style.display="block";
	}
	
	if(e==1||e=="1"){
		i$("tb_promo").style.display='';
	}
	else{
		try{
			i$("tb_promo").style.display='none';
		}
		catch(err){
		}

	}
		
	
}
