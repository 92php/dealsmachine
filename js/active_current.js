// JavaScript Document
try{
	document.getElementById("currency").onchange=(function(){												   
  setCookie('currency',document.getElementById("currency").value);
  change_currency();
	})

	document.getElementById("currency").remove(0)
	var opt_c=document.getElementById("currency").getElementsByTagName("option");
	var opt_len=opt_c.length;
	var currency=getCookie('currency');
	for(var i=0;i<opt_len;i++){
		if(opt_c.item(i).value==currency) opt_c.item(i).selected=true;
	}
	document.getElementById("currency").disabled=false;
}catch(e){};