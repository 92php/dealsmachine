$(function(){function hoverCategory(){var _bland;$(".js_litem").hover(function(){var me=$(this);if(!$('body').data('__loaded__')){_bland=setTimeout(function(){$('body').data('__loaded__',true);me.find(".js_litem_list").addClass("hover_litem_list");me.children(".subitem").css({"height":"350px","background":"#fff url(http://www.ah05.com/temp/skin3/images/styleimg/indicator.gif) no-repeat center center"}).show();$.get(DOMAIN+'/data-cache/index_category.htm?'+new Date().getTime(),function(data){$(data).appendTo($('body')).hide();me.children(".subitem").css({"height":"auto","background":"#fff"}).append($('#nav-'+me.attr('data-cat')).children())})},500)}else if($(this).find('.subitem_list').length<1){me.find(".js_litem_list").addClass("hover_litem_list");me.children(".subitem").append($('#nav-'+me.attr('data-cat')).children()).show()}else{me.find(".js_litem_list").addClass("hover_litem_list");me.children(".subitem").show()}},function(){clearTimeout(_bland);$(this).find(".js_litem_list").removeClass("hover_litem_list");$(this).children(".subitem").hide()});$(".cat_js_litem").hover(function(){var me=$(this);if(!$('body').data('__loaded__')){$('body').data('__loaded__',true);$.get(DOMAIN+'/data-cache/cat_category_'+js_cur_lang+'.htm?'+new Date().getTime(),function(data){$(data).appendTo($('body')).hide();me.children(".cat_sub_item").append($('#nav-'+me.attr('data-cat')).children());checkSubMenuAddImg(me)})}else if($(this).find('.subitem_list').length<1){me.children(".cat_sub_item").append($('#nav-'+me.attr('data-cat')).children())}else{}},function(){})};function checkSubMenuAddImg(elemt,fistMenu){var innerRightImg='<img src="/temp/skin3/images/styleimg/leftmenu_icon.gif" class="rightarrowclass"/>',$elemt=$(elemt);if(fistMenu){$(elemt).children("a").append(innerRightImg);return false};$elemt.find("ul").each(function(){var that=$(this);var $thatParent=that.parent("li");if($thatParent.find("img").length<1){$thatParent.children("a").append(innerRightImg)}})};hoverCategory();checkSubMenuAddImg($("#smoothmenu").find("li"),1);$("#smoothmenu").on("mouseover","li",function(){var that=$(this);if(that.children("ul").length>0){that.attr("id","chcss");that.children("a").addClass("selected");that.children("ul").css({"visibility":"visible","z-index":"1000","right":"-231px"}).show()}});$("#smoothmenu").on("mouseout","li",function(){var that=$(this);if(that.children("ul").length>0){that.attr("id","");that.children("a").removeClass("selected");that.children("ul").css({"visibility":"hidden"}).hide()}})});