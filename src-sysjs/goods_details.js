$(document).ready(function(){
    //getDiggs();
	goodsStat();
    $('.a-digg').each(function() {
        var me = $(this);
        me.click(function() {
            digg(me);
            return false;
        });
    });
    $("#detailstopsalefudong").jCarouselLite({
		btnNext: "#detailsrightbar",
		btnPrev: "#detailsleftbar",
		vertical: false,
		play:false,
		auto: 3000,
		visible: 6,
        scroll: 2,
		//mouseWheel: true,
		speed: 800
    });
	   
    if ($(".jqzoom img").attr('jqimg'))
    {
        $(".jqzoom").jqueryzoom({ xzoom: 400, yzoom: 270 });
    }  
	
	
	
		var validator = $(".theAddressForm").validate({
			rules: {
				email: {required: true,maxlength: 60,email: true},
				content: {required: true,maxlength: 300},
				rating: {required: true},
				nickname: {required: true,maxlength: 30}
			},
			messages: {
				email: {required: email_msg,maxlength: email_maxlength_msg},
				content: {required:content_msg,maxlength: content_maxlength_msg},
				rating: {required:rating_msg},
				nickname: {required:nickname_msg,maxlength: nickname_maxlength_msg}
			},
			submitHandler: function() {
				var content = $("#msg_content").val();
				var gid     = $("#gid").val();
				var nickname = $("#nickname").val();
				var email = $("#email").val();
				var rating = $("input[name='rating']:checked").val();
				if (nickname == undefined) return false;
				if (email == undefined) return false;
				if (rating == undefined) return false;
				if (content == undefined) return false;
				$.ajax({
					type: "POST",
					data:{'rank':rating,'email':email,'content':content,'nickname':nickname,'id':gid},
					url: '/'+js_cur_lang_url + 'm-comment.htm',
					beforeSend:function(){$("#ajaxmsg").html(" <img src='/temp/skin1/images/990000_bai.gif' id='verify' style='vertical-align: middle' > "+reviews_func_check_btn_signin_3);	}, 
					success: function(msg){
						$("#msg_content").val('');
					//	ymPrompt.succeedInfo({message:msg,width:300,height:160,title:'System Message',handler:null});
						$("#ajaxmsgre").html(msg);						
					} 
				});
				},
			success: function(label) {
				// set &nbsp; as text for IE
				label.html("&nbsp;").addClass("checked");
			}
		});
	
		$('#content').inputlimitor({
			limit: 300,
			boxId: 'limitingtext',
			boxAttach: false
		});
	
	
/*   if ($.cookie('WEBF-email') != null && $.cookie('WEBF-firstname')!= null){
	   var zifu = $.cookie('WEBF-email');
	   var niname = $.cookie('WEBF-firstname');
	   $("#review_w #email").val(zifu);
	   $("#review_w #nickname").val(niname);
	}
*/	
	
	
	
	
	
	
	
	//var comm_url = document.location.href;
	//comm_load(comm_url);
	
	$("#review a").livequery("click",function(){
		page_url = $(this).attr("atr");
		if (page_url!=undefined)
		comm_load(page_url);
	});
	
	
	$("#PB_Page_Select").livequery("change",function(){
		page_url  = $("#PB_Page_Select").attr("atr");
		pageno    = $("#PB_Page_Select").val();
		comm_load(page_url+pageno);
	});
	
	
	function comm_load(page_url){
		$.ajax({
			type: "GET",
			url: page_url,
			success: function(msg){
				var stext = $(msg).find('#review').html();
				if (stext.indexOf('review_title')>=0){
					$('#review').html(stext);
				}else{
					$('#review').remove('');
				}
			}
		});
	}
	
	
	
	
});


ddsmoothmenu.init({
	mainmenuid: "smoothmenu1", //menu DIV id
	orientation: 'h', //Horizontal or vertical menu: Set to "h" or "v"
	classname: 'ddsmoothmenu', //class added to menu's outer DIV
	//customtheme: ["#1c5a80", "#18374a"],
	contentsource: "markup" //"markup" or ["container_id", "path_to_menu_file"]
})

/**
 * 设置digg
 * 
 * @param {object} me     点击元素
 * @param {object} obj    显示digg数元素
 * @param {object} itemId 商品id
 */
function digg(me, obj, itemId) {
    itemId = itemId || $('#hidden-goodsId').val();
    obj = obj || $('.a-digg');
    var str = 'digg_' + itemId, num = me.text();
    
    if ($.cookie(str)) {
        alert(goods_details_func_digg_0);
        return;
    }
    
    $.post('/fun/?act=digg', 'set=1&itemId=' + itemId, function(data) {
        
        if (data) {
            $.cookie(str, true, {
                expires: 1 / 24
            });
            obj.text(parseInt(num) + 1);
        }
        
    });
}

/**
 * 获取digg数
 * 
 * @param {object} obj    显示digg元素
 * @param {object} itemId 商品id
 */
function getDiggs(obj, itemId) {
    obj = obj || $('.a-digg');
    itemId = itemId || $('#hidden-goodsId').val();
    
    $.post('/fun/?act=digg', 'itemId=' + itemId, function(data) {
        data && obj.text(data);
    });
}

function goodsStat(obj, itemId) {
	
    obj = obj || $('.a-digg');
    itemId = itemId || $('#hidden-goodsId').val();
    
	$.getJSON('http://cart.happy.com:6/hits/hits.php?act=goods_stat&jsoncallback=?&itemId=' + itemId, function(data) {
		//alert(data.diggs);
        obj.find('span').text(data.diggs);
    });
}
