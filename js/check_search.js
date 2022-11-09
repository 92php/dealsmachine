// JavaScript Document
function search_on_focus(o)
{
	if(o.value=='Enter your keyword') o.value='';
	
}
function search_on_blur(o)
{
	if(o.value=='') o.value='Enter your keyword';
	
}
function on_search_submit(){
	if(searchform.key.value=='' || searchform.key.value=='Enter your keyword'){
		alert("Please Enter your keyword");
		searchform.key.select();
		return false;
	}
}
