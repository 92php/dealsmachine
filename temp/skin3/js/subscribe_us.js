$(function() {
	//首页初次访问弹出订阅邮件框
	open_subscribe_index();
	//首页初次访问订阅邮件
	$('#btn-subscribe-index').click(function() {
		var el = $('#txt-subscribe-index');
		var email = el.val().trim();

		if (!email) {
			$('#subscribe-index-info').show();
			$("#subscribe-index-info").attr("class","info_w");
			$('#subscribe-index-info').html(jsLang.email_notValid);
			el.focus();
			return false;
		}
		else if (!checkmail(email)) {
			$('#subscribe-index-info').show();
			$("#subscribe-index-info").attr("class","info_w");
			$('#subscribe-index-info').html(jsLang.email_notValid);
			el.focus();
			return false;
		}
		var postData = 'source=1';
		$.getJSON(DOMAIN_USER+'/'+cur_lang+'/m-users-a-email_list-job-add-email-' + email + '.htm?jsoncallback=?',postData, function(data) {

			$('#subscribe-index-info').show();
			if(data.type==1)
			{
				$("#subscribe-index-info").attr("class","info_w");
			}
			else
			{
				$("#subscribe-index-info").attr("class","info");
			}
			$('#subscribe-index-info').html(data.info);
			el.focus();
			return false;
		});
	});
});


//首页初次访问弹出订阅邮件框
function open_subscribe_index(){
	if ($.cookie('first_access') == null){
		$('#maskLevel').show();
		$('#subscribe-index-div').show();
		$.cookie('first_access', 'yes', {expires: 365, path: '/', domain: COOKIESDIAMON});
	}
}

//关闭首页初次访问弹出订阅邮件框
function close_subscribe_index(){
	$('#subscribe-index-info').html('');
	$('#subscribe-index-info').hide();
	$('#subscribe-index-info').html('Ingrese su correo electronico,por favor!');
	$('#subscribe-index-div').hide();
	$('#maskLevel').hide();
}

/**
 * 输入框获、失焦点处理
 *
 * @param {object} obj   html元素
 * @param {string} def   默认内容，默认为元素初始值
 * @param {string} color 获得焦点时，输入框字体颜色
 *
 * @return {object} html元素
 */
function setFocusNew(obj, def, color) {
	def = def || obj.defaultValue;
	obj.value.trim() == def ? obj.value = '' : '';
	obj.value.trim() == def ? obj.style.fontStyle = 'italic' : obj.style.fontStyle = 'normal';

	obj.onblur = function() {
		obj.value.trim() == '' ? obj.value = def : '';
		obj.value.trim() == def ? obj.style.fontStyle = 'italic' : obj.style.fontStyle = 'normal';
		obj.value.trim() == def ? obj.style.color = '#999' : obj.style.color = '#333';
	};
	color ? obj.style.color = color : '';

	return obj;
}

