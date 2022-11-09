// JavaScript Document

function setCookie(name,value)
{
    var Days = 30;
    var exp  = new Date();    //new Date("December 31, 9998");
        exp.setTime(exp.getTime() + Days*24*60*60*365);
        document.cookie = name + "="+ escape (value) + ";path=/;expires=" + exp.toGMTString();
}
function getCookie(name)
{
    var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
        if(arr=document.cookie.match(reg)) return unescape(arr[2]);
        else return null;
}
function change_currency(){
	var arr_obj=document.getElementsByTagName("span");
	var len=arr_obj.length;
	var currency=getCookie('currency');
	if(currency){
		for(var i=0;i<len;i++){
			try{
				if(arr_obj.item(i).getAttribute('t_type')=='price'){
					p=arr_obj.innerHTML;

					if(arr_obj.item(i).getAttribute("USD")!='0')arr_obj.item(i).innerHTML=(arr_obj.item(i).getAttribute("USD")*arr_rate[currency]).toFixed(2)+" "+currency;
				}
			}catch(e){};
		}
	}
}

change_currency();
