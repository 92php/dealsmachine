// JavaScript Document

var patt1 = /^chn\_\d+$/;
if(GetCookie("shopzhiwang","username")==null ||GetCookie("shopzhiwang","username")==""|| patt1.test(GetCookie("shopzhiwang","username")) )
{
	document.write("<a href='/reg.asp' class='orge'>Join Free</a> &nbsp;|&nbsp; <a href='/login.asp' class='orge'>Sign In</a>");
}
else
{
	document.write("<a class=Home_smalltitle href='../Logout.asp'>Log out</a>");
} 