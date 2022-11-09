// JavaScript Document

function createRequest()
{

	try {
	 request = new XMLHttpRequest();
	} catch (trymicrosoft) {
	 try {
	 request = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (othermicrosoft) {
	 try {
	 request = new ActiveXObject("Microsoft.XMLHTTP");
	 } catch (failed) {
	 request = false;
	 }
	 }
	}
	return request;
}

//获取信息
function getInfo(url,func) {
 request.open("GET", url, true);
 request.onreadystatechange = func;
 request.send(null);
}