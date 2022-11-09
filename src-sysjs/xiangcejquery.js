// JavaScript Document
jQuery.fn.loadthumb = function(options) {
	options = $.extend({
		 src : ""
	},options);
	var _self = this;
	_self.hide();
	var img = new Image();
	$(img).load(function(){
		_self.attr("src", options.src);
		_self.show();//_self.fadeIn("slow");
	}).attr("src", options.src);  //.atte("src",options.src)要放在load后面，
	return _self;
}

$(function(){
  var i = 5;  //已知显示的<a>元素的个数
  var m = 5;  //用于计算的变量
  var $content = $(".scrollableDiv");
  var count = $content.find("a").length;//总共的<a>元素的个数
  //下一张
  $(".xiang_next").live("click",function(){
		var $scrollableDiv = $(this).siblings(".items").find(".scrollableDiv");
		if( !$scrollableDiv.is(":animated")){  //判断元素是否正处于动画，如果不处于动画状态，则追加动画。
			if(m<count){  //判断 i 是否小于总的个数
				m++;
				$scrollableDiv.animate({left: "-=50px"}, 600);
			}
		}
		return false;
  });
   //上一张
  $(".xiang_prev").live("click",function(){
		var $scrollableDiv = $(this).siblings(".items").find(".scrollableDiv");
		if( !$scrollableDiv.is(":animated")){
			if(m>i){ //判断 i 是否小于总的个数
				m--;
				$scrollableDiv.animate({left: "+=50px"}, 600);
			}
		}
		return false;
  });

  $(".scrollableDiv a").live("mouseover",function(){
		var src = $(this).find("img").attr("imgb");
		var bigimgSrc = $(this).find("img").attr("bigimg");//alert(bigimgSrc);
		//$(".myImagesSlideBox").find(".myImgs").loadthumb({src:src}).attr("bigimg",bigimgSrc);
		$(".myImages img").attr("jqimg",bigimgSrc);
        $(".myImages img").attr("src",src);
		$("#gallery_url").attr("relurl",($("#gallery_url").attr("url")+bigimgSrc)); //点击放大
		$(this).addClass("active").siblings().removeClass("active");
		return false;
  });
  $(".scrollableDiv a:nth-child(1)").trigger("mouseover");
  
  $("#gallery_url").click(function(){
	  url = $(this).attr("relurl");
	 // alert(url);
	  window.opener=null;
	  window.open(url,'_blank','');					   
	});
})
