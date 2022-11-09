// JavaScript Document

<!--
function i$(id){
	return document.getElementById(id);
}
function numbers(obj){
		obj.value=obj.value.replace(/[^\d]/g,'');
}
function updateCart(obj){
	if(parseInt(obj.className)!=obj.value){
		i$("div_quantity_modify_"+obj.id).style.display="block";
	}
	else{
		i$("div_quantity_modify_"+obj.id).style.display="none";
	}
}
function cancel_modify_quantity(strID){
	document.getElementById("div_quantity_modify_"+strID).style.display="none";
	i$(strID).value=i$(strID).className;
}
function on_update_cart(bookid){

	//if(i$(bookid).value.length<1){
	//	alert('Please enter the quantity you want to buy');
	//	i$(bookid).select();
	//}
	return true;
}