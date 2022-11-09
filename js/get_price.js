// JavaScript Document

function get_price(price){
	var currency=getCookie('currency');
	if (currency)
		p=arr_rate[currency]* price;
	else 
		p=price;
	document.write(p.toFixed(2)+" "+currency);
}