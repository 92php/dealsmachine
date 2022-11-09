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
	return true;
}
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
//var insurance,pro_total,shipping,tracking_number_fee;
var promo_min_money=0;
var promo_cut=0;
var pro_total_after_dis=pro_total;

function changeShip(p){
	var total=0;
	total=pro_total+p-cheapen-point_money;
	if (total<0) total=0.01;
	shipping=p;
	//if (i$("insurance_checked").checked){
	//	total=total+insurance;
	//	if (total<0) total=0.01;
	//}
	
	//if (i$("Traking_number_checked").checked){
	//	total=total+tracking_number_fee;
	//	if (total<0) total=0.01;
	//}
	
	//i$("total").innerHTML="US $"+total.toFixed(2);
	//i$("total").setAttribute("USD",total);

	if(shipping!=0){
		i$("shipping").innerHTML="US $"+shipping.toFixed(2);
		i$("shipping").setAttribute("USD",shipping);
	}
	else{
		i$("shipping").innerHTML="<font color=red>Free Shipping</font>";
		i$("shipping").setAttribute("USD", 0);
	};
	try{
		//alert(document.getElementById("flat").checked);
		if(document.getElementById("flat").checked){
			document.getElementById("flat_ship_tips").style.display="";	
			change_tracking_check(0);
			
		}
		else{
			i$("Traking_number_checked").checked="false";
			document.getElementById("flat_ship_tips").style.display="none";	
			change_tracking_check(0);	
		}
	}
	catch(e){};
	caculate_total();
	change_currency();
	
}
function change_insurance_check(b){

	if(b){
		insurance = Number(i$("insurance_checked").value);

	}
	else{
		insurance=0;
		
		
	}


	i$("show_insurance").innerHTML="US $"+insurance.toFixed(2);
	
	i$("show_insurance").setAttribute("USD", insurance);
	caculate_total();
	change_currency();
}
function change_tracking_check(b){
	if(document.getElementById("flat").checked){ 
		if(b){
			tracking_number_fee = Number(i$("Traking_number_checked").value);
			
	
		}
		else{
			tracking_number_fee = 0;
			
			
		}

	}
	else
	{
		i$("Traking_number_checked").checked =  false;
		i$("tracking_number_title").style.display = "none";
		i$("tracking_number").style.display = "none";
		tracking_number_fee = 0 ;
		
	}
	
	if(tracking_number_fee>0)
		i$("tracking_number_title").style.display = "";
	else
		i$("tracking_number_title").style.display = "none";

	if(tracking_number_fee>0)
		i$("tracking_number").style.display = "";
	else
		i$("tracking_number").style.display = "none";
		
	
	i$("show_tracking_number").innerHTML = "US $"+tracking_number_fee.toFixed(2);
	
	i$("show_tracking_number").setAttribute("USD", tracking_number_fee);
	caculate_total();
	change_currency();
}
function changeCoupon(p){
	
	cheapen=p;
	var total=0
	pro_total_after_dis=pro_total-cheapen;
	//total=pro_total+shipping-cheapen-point_money;
	//if (i$("insurance_checked").checked){
	//	total=pro_total+shipping+insurance-cheapen-point_money;
	//}
	//i$("total").innerHTML="US $"+total.toFixed(2);
	//i$("total").setAttribute("USD", total);
	
	i$("cheapen").innerHTML="US $"+pro_total_after_dis.toFixed(2);
	i$("cheapen").setAttribute("USD", pro_total_after_dis);
	caculate_total();
	change_currency();
}
function addPromo(p){
	
	cheapen=p;
	//var total=0
	pro_total_after_dis=pro_total-cheapen;
	//total=pro_total+shipping-cheapen-point_money;
	//if (i$("insurance_checked").checked){
	//	total=pro_total+shipping+insurance-cheapen-point_money;
	//	if (total<0) total=0.01;
	///}
	//i$("total").innerHTML="US $"+total.toFixed(2);
	//i$("total").setAttribute("USD", total);
	
	i$("cheapen").innerHTML="US $"+pro_total_after_dis.toFixed(2);
	i$("cheapen").setAttribute("USD", pro_total_after_dis);
	i$("td_cheapen").style.display="";
	i$("td_cheapen_price").style.display="";
	caculate_total();
	change_currency();
}
function add_point_money(cash){
	
	point_money=cash;
	var total=0
	//pro_total_after_dis=pro_total-cheapen;
	total=pro_total+shipping-cheapen-point_money;
	//alert(String(pro_total)+"+"&String(shipping)+"-"&String(cheapen));
	//if (i$("insurance_checked").checked){
	//	total=pro_total+shipping+insurance-cheapen-point_money;
		
	//}
	//if (total<0) total=0.01;
	if(cash>=0){
		//cash=
		//i$("total").innerHTML="US $"+total.toFixed(2);
		//i$("total").setAttribute("USD", total);
	
		i$("show_point_money").innerHTML="US $"+cash;
		i$("show_point_money").setAttribute("USD", cash);
		i$("td_show_point").style.display="";
		i$("td_show_point_value").style.display="";
		
		caculate_total();
		change_currency();
	}
}
function caculate_total(){
	var total = 0;
	total = pro_total+shipping-cheapen-point_money +insurance + tracking_number_fee;
	//if(i$("insurance_checked").checked){
	//	total = total+	insurance;
	//}
	//if(i$("Traking_number_checked").checked){
	//	total = total+Traking_number_checked;
	//}
	i$("total").innerHTML="US $" + total.toFixed(2);
	i$("total").setAttribute("USD", total);	
	return total;

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
		i$("payDescript"+String(i)).style.display="none";
	}

	if (id !=""){
		i$(id).style.display="block";
	}
	if(e==1||e=="1"){
		i$("tb_show_input_bottom").style.display='';
		//i$('tb_promo').style.display='none';
		//i$('bt_apple_code').style.display='';
	}
	else{
		try{
			i$("tb_show_input_bottom").style.display='none';
			i$('tb_promo').style.display='none';
		}
		catch(err){
		}
	}
}

