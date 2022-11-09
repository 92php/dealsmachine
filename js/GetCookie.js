// JavaScript Document

function GetCookie(sMainName, sSubName)  //equal to asp request.cookie(MainName)(sSubName) ,but it is work on client computer,don't need IIS Server support
{
var sCookieName = sMainName + "=";
var sSubCookieName = (sSubName) ? sSubName + "=" : null;
var sCookie;
var sWholeCookie = document.cookie;				
var nValueBegin = sWholeCookie.indexOf(sCookieName);
if(nValueBegin != -1)
{
var nValueEnd = sWholeCookie.indexOf(";", nValueBegin);
if (nValueEnd == -1)
nValueEnd = sWholeCookie.length;				
var sValue = sWholeCookie.substring(nValueBegin + sCookieName.length, nValueEnd);				
if(sSubCookieName)
{
var nSubValueBegin = sValue.indexOf(sSubCookieName);
if(nSubValueBegin != -1)
{
var nSubValueEnd = sValue.indexOf("&", nSubValueBegin);
if(nSubValueEnd == -1)
nSubValueEnd = sValue.length;
var sSubValue = sValue.substring(nSubValueBegin + sSubCookieName.length, nSubValueEnd);
return unescape(sSubValue);
}
}
if(!sSubCookieName)
return unescape(sValue);
}
return null;
}
				
		  
