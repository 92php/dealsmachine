// JavaScript Document
var rating=5;
function rate_mouseover(i){
	for(var a=1;a<=5;a++)
		document.getElementById("rate"+a.toString()).src="../images/icon_star_1.gif";
	for(var a=1;a<=i;a++)
		document.getElementById("rate"+a.toString()).src="../images/icon_star_2.gif";
	if(i>1)
		document.getElementById("rating_tips").innerHTML=i.toString()+" Stars";
	else
		document.getElementById("rating_tips").innerHTML=i.toString()+" Star";
}
function rate_mouseout(i){
	for(var a=1;a<=5;a++)
		document.getElementById("rate"+a.toString()).src="../images/icon_star_1.gif";
	for(var a=1;a<=rating;a++)
		document.getElementById("rate"+a.toString()).src="../images/icon_star_2.gif";
	if(rating>1)
		document.getElementById("rating_tips").innerHTML=rating.toString()+" Stars";
	else
		document.getElementById("rating_tips").innerHTML=rating.toString()+" Star";
}
function rate_chick(i){
	rating=i;
	document.getElementById("rates").value=i.toString();
}