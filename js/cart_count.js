// JavaScript Document

var count=GetCookie("shopzhiwang","jianshu");
if(count==null || count=="0"|| count==0 )
{document.write("<font color=yellow>0</font>");}
else
	document.write("<font color=#990000>"+count+"</font>");
