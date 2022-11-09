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
		i$('cart_price').setAttribute('USD',price);}
	catch(e){
		i$('cart_price').setAttribute('usd',price);
		}
	var currency=getCookie('currency');
	if(!currency) currency="USD" ;
	if(f==1){
		i$("cart_price").innerHTML="VIP Price:<br/>"+price.toFixed(2)+" "+currency;
	}
	else{
		i$("cart_price").innerHTML="US $"+price.toFixed(2);
	}
	i$("cart_price").setAttribute("USD",price);
	i$("items_total").innerHTML="<span t_type='price' class='price' USD='"+price.toFixed(2)+"'>US $"+price.toFixed(2)+"</span>"+" x "+qty+" = "+"<span class='price'  t_type='price' USD='"+(price*qty).toFixed(2)+"'>US $"+(price*qty).toFixed(2)+"</span>";
	change_currency();
}
$("#cart_add").click(function(){
	var c=$("#quantity").val();
	if(isNaN(c)){
		cart.quantity.value=1
		return;
	}
	c=parseInt(c);
	c=c+1;
	cart.quantity.value=c;
	on_change_quantity(c);
 });
i$("quantity").onkeyup=(function(){
	on_change_quantity(i$("quantity").value);
});
i$("cart_reduce").onclick=(function(){
	var c=cart.quantity.value;
	if(isNaN(c)){
		cart.quantity.value=1
		return;
	}
	c=parseInt(c);
	if(c<1) c=1;
	c=c-1;
	cart.quantity.value=c;
	on_change_quantity(c);
 });
function numbers(obj){
		obj.value=obj.value.replace(/[^\d]/g,'');
}
function set_img_border_color(qty){
	for(var i=0;i<qty;i++){
		i$("view_small_pic").getElementsByTagName("img").item(i).style.borderColor='#CCCCCC';
	}
}
on_change_quantity(1);
var qty=i$("view_small_pic").getElementsByTagName("img").length;
for(var i=0;i<qty;i++){
	i$("view_small_pic").getElementsByTagName("img").item(i).onmouseover=(function() { 
	var img=new Image();
	img.src= this.getAttribute("mid_pic")+"?t="+Math.round(Math.random()*10000);
	i$("mid_pic").src= this.getAttribute("src");
	
	for(var a=0;a<document.links.length;a++){
		if(document.links[a].name=='a_mid_pic'){
			document.links[a].href=this.getAttribute("big_pic")+"?t="+Math.round(Math.random()*10000);
		}
	}
	  img.onload = function(){
		 i$("mid_pic").src=img.src;	
		  }			
	  });  
	  
  }
	$(function() {
		$('.gallery a').lightBox();
	});
	var img3=new Image();
	img3.src='/images/imageNavRightHover.gif';
	var img4=new Image();
	img4.src='/images/imageNavLeftHover.gif';
//---------------picture view------------
//var p = /(*\.gif)|(*\.jpg)/;

(function($){$.fn.lightBox=function(settings){settings=jQuery.extend({overlayBgColor:'#000',overlayOpacity:0.8,fixedNavigation:false,imageLoading:'/images/lightbox-ico-loading.gif',imageBtnPrev:'/images/lightbox-btn-prev.gif',imageBtnNext:'/images/lightbox-btn-next.gif',imageBtnClose:'/images/lightbox-btn-close.gif',imageBlank:'/images/lightbox-blank.gif',containerBorderSize:10,containerResizeSpeed:400,txtImage:'Image',txtOf:'of',keyToClose:'c',keyToPrev:'p',keyToNext:'n',imageArray:[],activeImage:0},settings);var jQueryMatchedObj=this;function _initialize(){_start(this,jQueryMatchedObj);return false;}
function _start(objClicked,jQueryMatchedObj){$('embed, object, select').css({'visibility':'hidden'});_set_interface();settings.imageArray.length=0;settings.activeImage=0;if(jQueryMatchedObj.length==1){settings.imageArray.push(new Array(objClicked.getAttribute('href'),objClicked.getAttribute('title')));}else{for(var i=0;i<jQueryMatchedObj.length;i++){if(jQueryMatchedObj[i].getAttribute('ref')!="0" && (jQueryMatchedObj[i].getAttribute('href').indexOf(".jpg")!=-1 ||jQueryMatchedObj[i].getAttribute('href').indexOf(".JPG")!=-1 || jQueryMatchedObj[i].getAttribute('href').indexOf(".gif")!=-1 ) )settings.imageArray.push(new Array(jQueryMatchedObj[i].getAttribute('href'),jQueryMatchedObj[i].getAttribute('title')));}}
while(settings.imageArray[settings.activeImage][0]!=objClicked.getAttribute('href')){settings.activeImage++;}
_set_image_to_view();}
function _set_interface(){$('body').append('<div id="jquery-overlay"></div><div id="jquery-lightbox"><div id="lightbox-container-image-box"><div id="lightbox-container-image"><img id="lightbox-image"><div style="" id="lightbox-nav" ><a href="#" title="Preview" id="lightbox-nav-btnPrev" style="cursor:url("+img2.src+"),url(/images/pre.cur),pointer;"></a><a href="#" title="Next" id="lightbox-nav-btnNext" style="cursor:url("+img1.src+"),url(/images/next.cur),pointer;"></a></div><div id="lightbox-loading"><a href="#" id="lightbox-loading-link" ><img src="'+settings.imageLoading+'"></a></div></div></div><div id="lightbox-container-image-data-box"><div id="lightbox-container-image-data"><div id="lightbox-image-details"><span id="lightbox-image-details-caption"></span><span id="lightbox-image-details-currentNumber"></span></div><div id="lightbox-secNav"><a href="#" id="lightbox-secNav-btnClose"><img src="'+settings.imageBtnClose+'"></a></div></div></div></div>');var arrPageSizes=___getPageSize();$('#jquery-overlay').css({backgroundColor:settings.overlayBgColor,opacity:settings.overlayOpacity,width:arrPageSizes[0],height:arrPageSizes[1]}).fadeIn();var arrPageScroll=___getPageScroll();$('#jquery-lightbox').css({top:arrPageScroll[1]+(arrPageSizes[3]/10),left:arrPageScroll[0]}).show();$('#jquery-overlay,#jquery-lightbox').click(function(){_finish();});$('#lightbox-loading-link,#lightbox-secNav-btnClose').click(function(){_finish();return false;});$(window).resize(function(){var arrPageSizes=___getPageSize();$('#jquery-overlay').css({width:arrPageSizes[0],height:arrPageSizes[1]});var arrPageScroll=___getPageScroll();$('#jquery-lightbox').css({top:arrPageScroll[1]+(arrPageSizes[3]/10),left:arrPageScroll[0]});});}
function _set_image_to_view(){$('#lightbox-loading').show();if(settings.fixedNavigation){$('#lightbox-image,#lightbox-container-image-data-box,#lightbox-image-details-currentNumber').hide();}else{$('#lightbox-image,#lightbox-nav,#lightbox-nav-btnPrev,#lightbox-nav-btnNext,#lightbox-container-image-data-box,#lightbox-image-details-currentNumber').hide();}
var objImagePreloader=new Image();objImagePreloader.onload=function(){$('#lightbox-image').attr('src',settings.imageArray[settings.activeImage][0]);_resize_container_image_box(objImagePreloader.width,objImagePreloader.height);objImagePreloader.onload=function(){};};objImagePreloader.src=settings.imageArray[settings.activeImage][0];};function _resize_container_image_box(intImageWidth,intImageHeight){var intCurrentWidth=$('#lightbox-container-image-box').width();var intCurrentHeight=$('#lightbox-container-image-box').height();var intWidth=(intImageWidth+(settings.containerBorderSize*2));var intHeight=(intImageHeight+(settings.containerBorderSize*2));var intDiffW=intCurrentWidth-intWidth;var intDiffH=intCurrentHeight-intHeight;$('#lightbox-container-image-box').animate({width:intWidth,height:intHeight},settings.containerResizeSpeed,function(){_show_image();});if((intDiffW==0)&&(intDiffH==0)){if($.browser.msie){___pause(250);}else{___pause(100);}}
$('#lightbox-container-image-data-box').css({width:intImageWidth});$('#lightbox-nav-btnPrev,#lightbox-nav-btnNext').css({height:intImageHeight+(settings.containerBorderSize*2)});};function _show_image(){$('#lightbox-loading').hide();$('#lightbox-image').fadeIn(function(){_show_image_data();_set_navigation();});_preload_neighbor_images();};function _show_image_data(){$('#lightbox-container-image-data-box').slideDown('fast');$('#lightbox-image-details-caption').hide();if(settings.imageArray[settings.activeImage][1]){$('#lightbox-image-details-caption').html(settings.imageArray[settings.activeImage][1]).show();}
if(settings.imageArray.length>1){$('#lightbox-image-details-currentNumber').html(settings.txtImage+' '+(settings.activeImage+1)+' '+settings.txtOf+' '+settings.imageArray.length).show();}}
function _set_navigation(){$('#lightbox-nav').show();$('#lightbox-nav-btnPrev,#lightbox-nav-btnNext').css({'background':'transparent url('+settings.imageBlank+') no-repeat'});if(settings.activeImage!=0){if(settings.fixedNavigation){$('#lightbox-nav-btnPrev').css({'background':'url('+settings.imageBtnPrev+') left 15% no-repeat'}).unbind().bind('click',function(){settings.activeImage=settings.activeImage-1;_set_image_to_view();return false;});}else{$('#lightbox-nav-btnPrev').unbind().hover(function(){$(this).css({'background':'url('+settings.imageBtnPrev+') left 15% no-repeat'});},function(){$(this).css({'background':'transparent url('+settings.imageBlank+') no-repeat'});}).show().bind('click',function(){settings.activeImage=settings.activeImage-1;_set_image_to_view();return false;});}}
if(settings.activeImage!=(settings.imageArray.length-1)){if(settings.fixedNavigation){$('#lightbox-nav-btnNext').css({'background':'url('+settings.imageBtnNext+') right 15% no-repeat'}).unbind().bind('click',function(){settings.activeImage=settings.activeImage+1;_set_image_to_view();return false;});}else{$('#lightbox-nav-btnNext').unbind().hover(function(){$(this).css({'background':'url('+settings.imageBtnNext+') right 15% no-repeat'});},function(){$(this).css({'background':'transparent url('+settings.imageBlank+') no-repeat'});}).show().bind('click',function(){settings.activeImage=settings.activeImage+1;_set_image_to_view();return false;});}}
_enable_keyboard_navigation();}
function _enable_keyboard_navigation(){$(document).keydown(function(objEvent){_keyboard_action(objEvent);});}
function _disable_keyboard_navigation(){$(document).unbind();}
function _keyboard_action(objEvent){if(objEvent==null){keycode=event.keyCode;escapeKey=27;}else{keycode=objEvent.keyCode;escapeKey=objEvent.DOM_VK_ESCAPE;}
key=String.fromCharCode(keycode).toLowerCase();if((key==settings.keyToClose)||(key=='x')||(keycode==escapeKey)){_finish();}
if((key==settings.keyToPrev)||(keycode==37)){if(settings.activeImage!=0){settings.activeImage=settings.activeImage-1;_set_image_to_view();_disable_keyboard_navigation();}}
if((key==settings.keyToNext)||(keycode==39)){if(settings.activeImage!=(settings.imageArray.length-1)){settings.activeImage=settings.activeImage+1;_set_image_to_view();_disable_keyboard_navigation();}}}
function _preload_neighbor_images(){if((settings.imageArray.length-1)>settings.activeImage){objNext=new Image();objNext.src=settings.imageArray[settings.activeImage+1][0];}
if(settings.activeImage>0){objPrev=new Image();objPrev.src=settings.imageArray[settings.activeImage-1][0];}}
function _finish(){$('#jquery-lightbox').remove();$('#jquery-overlay').fadeOut(function(){$('#jquery-overlay').remove();});$('embed, object, select').css({'visibility':'visible'});}
function ___getPageSize(){var xScroll,yScroll;if(window.innerHeight&&window.scrollMaxY){xScroll=window.innerWidth+window.scrollMaxX;yScroll=window.innerHeight+window.scrollMaxY;}else if(document.body.scrollHeight>document.body.offsetHeight){xScroll=document.body.scrollWidth;yScroll=document.body.scrollHeight;}else{xScroll=document.body.offsetWidth;yScroll=document.body.offsetHeight;}
var windowWidth,windowHeight;if(self.innerHeight){if(document.documentElement.clientWidth){windowWidth=document.documentElement.clientWidth;}else{windowWidth=self.innerWidth;}
windowHeight=self.innerHeight;}else if(document.documentElement&&document.documentElement.clientHeight){windowWidth=document.documentElement.clientWidth;windowHeight=document.documentElement.clientHeight;}else if(document.body){windowWidth=document.body.clientWidth;windowHeight=document.body.clientHeight;}
if(yScroll<windowHeight){pageHeight=windowHeight;}else{pageHeight=yScroll;}
if(xScroll<windowWidth){pageWidth=xScroll;}else{pageWidth=windowWidth;}
arrayPageSize=new Array(pageWidth,pageHeight,windowWidth,windowHeight);return arrayPageSize;};function ___getPageScroll(){var xScroll,yScroll;if(self.pageYOffset){yScroll=self.pageYOffset;xScroll=self.pageXOffset;}else if(document.documentElement&&document.documentElement.scrollTop){yScroll=document.documentElement.scrollTop;xScroll=document.documentElement.scrollLeft;}else if(document.body){yScroll=document.body.scrollTop;xScroll=document.body.scrollLeft;}
arrayPageScroll=new Array(xScroll,yScroll);return arrayPageScroll;};function ___pause(ms){var date=new Date();curDate=null;do{var curDate=new Date();}
while(curDate-date<ms);};return this.unbind('click').click(_initialize);};})(jQuery);
//---------------picture view end----------	
//------------review-----------------
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
		i$("summarytip").innerHTML =0;
		return false;
	}
	else {
			i$("summarytip").innerHTML = obj.value.length;
			return true;
	}
}
function checkallInfo()
{
	if(f_name==1&&f_email==1&&f_content==1&&f_title==1){
		sendreview();
	}
	else{
		checkName();checkEmail();checkTitle();checkcontent(4,200);
		if(f_name==1&&f_email==1&&f_content==1&&f_title==1){
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
//-----------review end----------------

//---------rating------------
// JavaScript Document
var rating=5;
function rate_mouseover(i){
	for(var a=1;a<=5;a++)
		i$("rate"+a.toString()).src="../images/icon_star_1.gif";
	for(var a=1;a<=i;a++)
		i$("rate"+a.toString()).src="../images/icon_star_2.gif";
	if(i>1)
		$("#rating_tips").html(i.toString()+" Stars");
	else
		i$("rating_tips").innerHTML=i.toString()+" Star";
}
function rate_mouseout(i){
	for(var a=1;a<=5;a++)
		i$("rate"+a.toString()).src="../images/icon_star_1.gif";
	for(var a=1;a<=rating;a++)
		i$("rate"+a.toString()).src="../images/icon_star_2.gif";
	if(rating>1)
		i$("rating_tips").innerHTML=rating.toString()+" Stars";
	else
		i$("rating_tips").innerHTML=rating.toString()+" Star";
}
function rate_chick(i){
	rating=i;
	i$("rates").value=i.toString();
}
//---------rating end--------
//---------get price---------
function get_price(price){
	var currency=getCookie('currency');
	if (currency)
		p=arr_rate[currency]* price;
	else 
		p=price;
	document.write(p.toFixed(2)+" "+currency);
}
//---------get price end---------	