$('#add_to_cart_submit').click(function(){
										var f=1;var a=0;
										var msg='* Indicates required fields\n\r\n\r';
										$(':select[is_must=1]').each(function(i){if($(this).val()==''){a++;msg=msg+'  '+a+'. please select the '+$(this).attr('attr')+'\n\r';$(this).focus();f=0}})
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
			$('#review').html(data);window.location.href="#customer_review";}); 
	}
	function on_change_quantity(qty){
		try{
		if(qty_min>1&&qty_min>document.getElementById("quantity").value){
			alert("The Minimum Order Quantity of this product is :"+qty_min+" PCS");
			$("#quantity").val(qty_min);
			qty=qty_min;
	
		}	}
		catch(e){};
		try{
		if(qty_max>1&&qty_max<document.getElementById("quantity").value){
			alert("The Max Order Quantity of this product is :"+qty_max+" PCS");
			$("#quantity").val(qty_max);
			qty=qty_max;
	
		}	}
		catch(e){};
		if (isNaN(qty)||qty<1){
			return;
			$("#quantity").val(1);
			qty=1;
		}
		var price;
		
		if(qty<3){price=price1;}
		else if(qty <7 ){price=price2;}
		else if(qty<20){price=price3;}
		else if(qty<50){price=price4;}		
		else{price=price5;}	
		price=price+para_money;
		var f=0;
		try{
			if(GetCookie("shopzhiwang","reglx")>1){
				price=price5;f=1;};}
		catch(err){};
		
		try{$('#cart_price').attr('USD',price);}
		catch(e){$('#cart_price').attr('USD',price);}
		var currency=getCookie('currency');
		if(!currency) currency="USD" ;
		
		if(pro_type ==1){
			$("#cart_price").html("Group Deal Price:<br/>"+"US $"+price.toFixed(2));
		}else{
			
			if(f==1){$("#cart_price").html("VIP Price:<br/>"+"US $"+price.toFixed(2));}
			else{$("#cart_price").html("US $"+price.toFixed(2));}
		}
		$("#cart_price").attr("USD",price);
		$("#items_total").html("<span t_type='price' class='price' USD='"+price.toFixed(2)+"'>US $"+price.toFixed(2)+"</span>"+" x "+qty+" = "+"<span class='price'  t_type='price' USD='"+(price*qty).toFixed(2)+"'>US $"+(price*qty).toFixed(2)+"</span>");
		change_currency();
	}
	$("#cart_add").click(function(){
								 // 
		var c=$("#quantity").val();
		if(isNaN(c)){
			cart.quantity.value=1
			return;
		}
		c=parseInt(c);
		c=c+1;
		$("input[name=quantity]").val(c);
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
	$("#quantity").keyup(function(){on_change_quantity(document.getElementById("quantity").value);});
	function numbers(obj){obj.value=obj.value.replace(/[^\d]/g,'');}
	function set_img_border_color(qty){
		for(var i=0;i<qty;i++){document.getElementById("view_small_pic").getElementsByTagName("img").item(i).style.borderColor='#CCCCCC';}
	}
	accessory_select();
	try{
	var qty=document.getElementById("view_small_pic").getElementsByTagName("img").length;
	for(var i=0;i<qty;i++){
		document.getElementById("view_small_pic").getElementsByTagName("img").item(i).onmouseover=(function() { 
		var img=new Image();
		img.src= this.getAttribute("mid_pic")+"?t="+Math.round(Math.random()*10000);
		try{
		document.getElementById("mid_pic").src= this.getAttribute("src");
		
		for(var a=0;a<document.links.length;a++){
			if(document.links[a].name=='a_mid_pic'){document.links[a].href=this.getAttribute("big_pic")+"?t="+Math.round(Math.random()*10000);}
		}
		  img.onload = function(){document.getElementById("mid_pic").src=img.src;}	
		 }catch(e){};		
		  });  
		 
	  }
	}
	catch(err){};
	$(function() {$('.gallery a').lightBox();});
	var img3=new Image();
	img3.src='/images/imageNavRightHover.gif';
	var img4=new Image();
	img4.src='/images/imageNavLeftHover.gif';


	