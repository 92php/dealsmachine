// JavaScript Document
if((GetCookie("shopzhiwang","username")==null ||GetCookie("shopzhiwang","username")=="" )&&(GetCookie("shopzhiwang","shjianame")==""||GetCookie("shopzhiwang","shjianame")==null))
{
	document.write("<a href='/reg.asp' class='orge'>Join Free</a> &nbsp;|&nbsp; <a href='/login.asp' class='orge'>Sign In</a>");
}
else
{
	document.write("<a class=Home_smalltitle href='../Logout.asp'>Log out</a>");
}