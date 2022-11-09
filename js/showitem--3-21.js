$('#add_to_cart_submit').click(function(){
										
										var f=1;
										var a=0;
										var msg='* Indicates required fields\n\r\n\r';
										$(':select[is_must=1]').each(function(i){if($(this).val()==''){a++;msg=msg+'  '+a+'. please select the '+$(this).attr('attr')+'\n\r';$(this).focus();f=0}
																	}
														)
										if(f==0){alert(msg);return false;}
										
										});

var para_money=0;
	function accessory_select(){
		 para_money=0;
		var arr =new Array();
		$("select[name='para_id']").each(function(){	
		  if(!isNaN($(this).children("option:selected").attr("fee"))) para_money=para_money+Number($(this).children("option:selected").attr("fee"));
		});
		$("input:checkbox[name='para_id']:checked").each(function(){
			if(!isNaN($(this).attr("fee"))) para_money=para_money+Number($(this).attr("fee"));
		})
		on_change_quantity($('#quantity').val());
	}	
	$("select[name='para_id']").change(function(){accessory_select();})

	$("input:checkbox[name='para_id']").click(function(){accessory_select();});
   	
   function review_page(bid,rpage){
		$.get("/review_show.asp", { id: bid, page: rpage },
		  function(data){
			$('#review').html(data);
			window.location.href="#customer_review";
		  }); 
	}
	function on_change_quantity(qty){
		if (isNaN(qty)){
			qty=1;
		}
		var price;
		if(qty<3){
			price=price1;
		}
		else if(qty <7 ){
			price=price2;
		}
		else if(qty<20){
			price=price3;
		}
		else if(qty<50){
			price=price4;
		}		
		else{
			price=price5;
		}	
		price=price+para_money;
		var f=0;
		try{
			if(GetCookie("shopzhiwang","reglx")>1){
				price=price5;
				f=1;
			};
		}
		catch(err){};
		try{
			$('#cart_price').attr('USD',price);}
		catch(e){
			$('#cart_price').attr('USD',price);
			}
		var currency=getCookie('currency');
		if(!currency) currency="USD" ;
		if(f==1){
			
			$("#cart_price").html("VIP Price:<br/>"+"US $"+price.toFixed(2));
		}
		else{
			//alert("US $"+price.toFixed(2));
			$("#cart_price").html("US $"+price.toFixed(2));
			//alert("22");
		}
		
		$("#cart_price").attr("USD",price);
		$("#items_total").html("<span t_type='price' class='price' USD='"+price.toFixed(2)+"'>US $"+price.toFixed(2)+"</span>"+" x "+qty+" = "+"<span class='price'  t_type='price' USD='"+(price*qty).toFixed(2)+"'>US $"+(price*qty).toFixed(2)+"</span>");
		change_currency();
	}
	$("#cart_add").click(function(){
								 // 
		var c=$("#quantity").val();
		if(isNaN(c)){
			//alert('11');
			cart.quantity.value=1
			return;
		}
		c=parseInt(c);
		c=c+1;
		$("input[name=quantity]").val(c);
		//$("input[name=quantity]").val(c);;
		on_change_quantity(c);
	 });
	$("#cart_reduce").click(function(){
		var c=$("#quantity").val();
		if(isNaN(c)){
			cart.quantity.value=1
			return;
		}
		c=parseInt(c);
		c=c-1;
		if(c<1)c=1;
		$("input[name=quantity]").val(c);;
		on_change_quantity(c);
	 });
	$("#quantity").keyup(function(){
		on_change_quantity(document.getElementById("quantity").value);
	});

	function numbers(obj){
			obj.value=obj.value.replace(/[^\d]/g,'');
	}
	function set_img_border_color(qty){
		for(var i=0;i<qty;i++){
			document.getElementById("view_small_pic").getElementsByTagName("img").item(i).style.borderColor='#CCCCCC';
		}
	}
	
	//on_change_quantity(1);
	accessory_select();
	try{
	var qty=document.getElementById("view_small_pic").getElementsByTagName("img").length;
	for(var i=0;i<qty;i++){
		document.getElementById("view_small_pic").getElementsByTagName("img").item(i).onmouseover=(function() { 
		var img=new Image();
		img.src= this.getAttribute("mid_pic")+"?t="+Math.round(Math.random()*10000);
		document.getElementById("mid_pic").src= this.getAttribute("src");
		
		for(var a=0;a<document.links.length;a++){
			if(document.links[a].name=='a_mid_pic'){
				document.links[a].href=this.getAttribute("big_pic")+"?t="+Math.round(Math.random()*10000);
			}
		}
		  img.onload = function(){
			  document.getElementById("mid_pic").src=img.src;	
			  }			
		  });  
		  
	  }
	}
	catch(err){};
	$(function() {
		$('.gallery a').lightBox();
	});

	var img3=new Image();
	img3.src='/images/imageNavRightHover.gif';
	var img4=new Image();
	img4.src='/images/imageNavLeftHover.gif';
		
//----------------rating-----------------
var rating=5;
function rate_mouseover(i){
	for(var a=1;a<=5;a++)
	{
		document.getElementById("rate"+a.toString()).src="../images/icon_star_1.gif";
	}
	for(var a=1;a<=i;a++)
	{
		document.getElementById("rate"+a.toString()).src="../images/icon_star_2.gif";
	}
	if(i>1)
		document.getElementById("rating_tips").innerHTML=i.toString()+" Stars";
	else
		document.getElementById("rating_tips").innerHTML=i.toString()+" Star";
		


}
function rate_mouseout(i){
	for(var a=1;a<=5;a++)
	{
		document.getElementById("rate"+a.toString()).src="../images/icon_star_1.gif";
	}
	for(var a=1;a<=rating;a++)
	{
		document.getElementById("rate"+a.toString()).src="../images/icon_star_2.gif";
	}
	if(rating>1)
		document.getElementById("rating_tips").innerHTML=rating.toString()+" Stars";
	else
		document.getElementById("rating_tips").innerHTML=rating.toString()+" Star";


	
}
function rate_chick(i){
	rating=i;
	document.getElementById("rates").value=i.toString();

}		
//----------------rating end -----------------
//----------------review---------------------

function i$(id){
	return document.getElementById(id);
}
var f_name,f_email,f_content,f_verification

f_name=0;
f_email=0;
f_title=0;
f_content=0;

function insertAfter(newElement,targetElement){
	var parent=targetElement.parentNode;
	if(parent.lastChild==targetElement){
	parent.appendChild(newElement);
	}else{
	parent.insertBefore(newElement,targetElement.nextSibling);
	}
}



function checkName()
{
	
	var name=i$("name");
	var pNode=document.createElement("span");
	if(!i$("nameTips")){
		insertAfter(pNode,name);
		pNode.style.color="red";

		pNode.setAttribute("id","nameTips");
	}
	i$("nameTips").innerHTML="";
	if(name.value.length==0||name.value.length==""){
		i$("nameTips").innerHTML="<img src='../Images/ico_F.gif'><div>Enter your Nickname!</div>";
		f_name=0;
		return false;
	} 
	else{

		i$("nameTips").innerHTML="<img src='../Images/ico_T.gif'>";
	}
	f_name=1;
	return true;
}

function checkEmail()
{
	
	var uEmail=i$("email");
	var pNode=document.createElement("span");
	if(!i$("uEmailTips")){
		insertAfter(pNode,uEmail);
		pNode.style.color="red";
		pNode.setAttribute("id","uEmailTips");
	}
	i$("uEmailTips").innerHTML="<img src='../Images/spinner_grey.gif'>"
	var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
	if(!p.test(uEmail.value)){
			i$("uEmailTips").innerHTML="<img src='../Images/ico_F.gif'><div style='display:block'>This is not a valid email address.</div>";
			f_email=0;
			return false;
	}
	i$("uEmailTips").innerHTML="<img src='../Images/ico_T.gif'>"
	f_email=1;
	return true;
}
function checkTitle()
{
	
	var title=i$("title");
	var pNode=document.createElement("span");
	if(!i$("titleTips")){
		insertAfter(pNode,title);
		pNode.style.color="red";

		pNode.setAttribute("id","titleTips");
	}
	i$("titleTips").innerHTML="";
	if(title.value.length==0||title.value.length==""){
		i$("titleTips").innerHTML="<img src='../Images/ico_F.gif'><div>Enter  the review title!</div>";
		f_title=0;
		return false;
	} 
	else{

		i$("titleTips").innerHTML="<img src='../Images/ico_T.gif'>";
	}
	f_title=1;
	return true;

}

function checkCode()
{
	var code=i$("code");
	var pNode=document.createElement("span");
	if(!i$("codeTips")){
		insertAfter(pNode,code);
		pNode.style.color="red";

		pNode.setAttribute("id","codeTips");
	}
	i$("codeTips").innerHTML="";
	
	if(code.value.length==0||code.value==""){
		i$("codeTips").innerHTML="<img src='../Images/ico_F.gif'>Enter the verify code at the left .";
		f_code=0;
		return false;
	} 
	if(code.value.length!=4){
		i$("codeTips").innerHTML="<img src='../Images/ico_F.gif'>Use 4 numbers .";
		f_code=0;
		return false;	}
	
	i$("codeTips").innerHTML="<img src='../Images/ico_T.gif'>";
	
	f_code=1;
	return true;
}
function checkcontent(){
	var minLen=4;
	var maxLen=500;
	
	 
	var content=i$("reviewcontent");
	
	i$("content_tips").style.display="";
	
	
	if(content.value.length<4 ||content.value.length>500){
		
		i$("content_tips").innerHTML="<img src='../Images/ico_F.gif'>Use 4 to 500 characters";	
		f_content=0;
		return false;
	}
	if(content.value==""||content.value==null){
			i$("content_tips").innerHTML="Review content is required!";
			f_content=0;
			
			return false;
			
	}
	else{
		if(content.value.length<minLen||content.value.length>maxLen){
			i$("content_tips").innerHTML="Review content Use "+minLen.toString()+" to "+maxLen.toString()+" characters";
			f_content=0;
		
			return false;
		}	
	}
	i$("content_tips").innerHTML="";
	i$("content_tips").style.display="none"

	f_content=1;
	return true;
}

function checkMaxInput(obj,maxLen) {
	if (obj.value.length > maxLen){ 
		document.getElementById("summarytip").innerHTML =0;
		return false;
	}
	else {
			document.getElementById("summarytip").innerHTML = obj.value.length;
			return true;
	}
}
function checkallInfo()
{

	//$('#review_msg').hidden();
	if(f_name==1&&f_email==1&&f_content==1&&f_title==1)
	{
		sendreview();
	}
	else
	{
		checkName();checkEmail();checkTitle();checkcontent(4,200);
		if(f_name==1&&f_email==1&&f_content==1&&f_title==1)
		{
			sendreview();
		}		
		return false;
	}
}
function sendreview(){
	
	var id=$('#id').val();
	var rates=$('#rates').val();
	var name=$('#name').val();
	var email=$('#email').val();
	var title=$('#title').val();
	$('#review_submit').attr("disabled",true);
	var reviewcontent=$('#reviewcontent').val();
	$.post("/ReviewsSave.asp", { id: id, rates:rates,name:name,email:email,title:title,reviewcontent:reviewcontent},
  	function(data){
		
	  if (data=='ok'){
		  $('#review_msg').show();
		  //document.getElementById("review_form").reset();
		  review_form.reset(); 
		  f_name=0;
		  $('#name_tips').val('');
		  $('#email_tips').val('');
		  $('#rating_tips').val('');
		  $('#summarytip').html(0);
		  $('#review_submit').attr("disabled",false);
		  window.location="#write_review";
	}
	else{
		alert(data);
	}
  }); 
}


i$("name").onblur=checkName;
i$("email").onblur= checkEmail;
i$("title").onblur=checkTitle;
i$("reviewcontent").onblur=checkcontent;



$(document).ready(function(){
  $('a[href*=#]').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'')
    && location.hostname == this.hostname) {
      var $target = $(this.hash);
      $target = $target.length && $target
      || $('[name=' + this.hash.slice(1) +']');
      if ($target.length) {
        var targetOffset = $target.offset().top;
        $('html,body')
        .animate({scrollTop: targetOffset}, 500);
       return false;
      }
    }
  });
});




//----------------review end -----------------