$(function() {
	function hoverCategory () {
		 /*首页分类导航*/	
		 var _bland;
		 $(".js_litem").hover(function() {
			var me = $(this);
			if (!$('body').data('__loaded__')) {
				_bland = setTimeout(function(){
					$('body').data('__loaded__', true);
					me.find(".js_litem_list").addClass("hover_litem_list");
					me.children(".subitem").css({"height":"350px","background":"#fff url(http://www.ah05.com/temp/skin3/images/styleimg/indicator.gif) no-repeat center center"}).show();
					$.get(DOMAIN +'/data-cache/index_category.htm?' + new Date().getTime(), function(data) {
						$(data).appendTo($('body')).hide();
						me.children(".subitem").css({"height":"auto","background":"#fff"}).append($('#nav-' + me.attr('data-cat')).children());
					});
				},500);
				
			}
			else if($(this).find('.subitem_list').length<1) {
				me.find(".js_litem_list").addClass("hover_litem_list");
				me.children(".subitem").append($('#nav-' + me.attr('data-cat')).children()).show();
			}
			else {
				me.find(".js_litem_list").addClass("hover_litem_list");
				me.children(".subitem").show();
			}
		}, function() {
			clearTimeout(_bland);
			$(this).find(".js_litem_list").removeClass("hover_litem_list");
			$(this).children(".subitem").hide();
		}); 

		/*类目页分类导航*/
		$(".cat_js_litem").hover(function() {
			var me = $(this);
			if (!$('body').data('__loaded__')) {
				$('body').data('__loaded__', true);
				$.get(DOMAIN + '/data-cache/cat_category_'+ js_cur_lang +'.htm?' + new Date().getTime(), function(data) {
					$(data).appendTo($('body')).hide();
					me.children(".cat_sub_item").append($('#nav-' + me.attr('data-cat')).children());
					checkSubMenuAddImg(me);
				});
			}
			else if($(this).find('.subitem_list').length<1) {
				me.children(".cat_sub_item").append($('#nav-' + me.attr('data-cat')).children());
			}
			else {
				//me.children(".cat_sub_item").show();
			}
			
		}, function() {
		   // $(this).removeClass("hoverClass");
			//$(this).children(".cat_sub_item").hide();
		});    
	};

	/**
	   *判断分类是否有子分类，如果有，则添加小图标表示
	   *@param{elemt} elemt为分类的li dom对象
	   *@param{fistMenu},是否为第一级分类，1为是，0为否
	*/
	function checkSubMenuAddImg(elemt,fistMenu){
		var innerRightImg = '<img src="/temp/skin3/images/styleimg/leftmenu_icon.gif" class="rightarrowclass"/>',
			  $elemt = $(elemt);
		
		//如果分类为一级分类不用判断是否有子分类，直接添加一个指示图标
		if(fistMenu){
			$(elemt).children("a").append(innerRightImg);
			return false;
		}
		
		//如果分类有子类，则添加一个指示图标
		$elemt.find("ul").each(function(){
			var that = $(this);
			var $thatParent = that.parent("li");
			if($thatParent.find("img").length<1){
				$thatParent.children("a").append(innerRightImg);
			}
		})
	}
	
	hoverCategory();
	//给一级分类添加有子分类的指示图标
	checkSubMenuAddImg($("#smoothmenu").find("li"),1);
	
	$("#smoothmenu").on("mouseover","li",function(){
		var that = $(this);
		if(that.children("ul").length>0){
			that.attr("id","chcss");
			that.children("a").addClass("selected");
			that.children("ul").css({"visibility":"visible","z-index":"1000","right":"-231px"}).show();
		}
	});
	$("#smoothmenu").on("mouseout","li",function(){
		var that = $(this);
		if(that.children("ul").length>0){
			that.attr("id","");
			that.children("a").removeClass("selected");
			that.children("ul").css({"visibility":"hidden"}).hide();
		}
	});
	
	
	

});

