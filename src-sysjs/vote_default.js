/**
 * vote_default.js      投票默认模板js
 *
 * @author              mashanling(msl-138@163.com)
 * @date                2011-08-17
 * @last modify         2011-08-23 by mashanling
 */
var li, p;
$(function(){
    li = $('li.vote-title');    //标题元素
    p = $('#p-vote-tips');      //提示元素
    checkVote();    //验证投票
});

/**
 * 提交投票
 * 
 */
function postVote(){
    var optionIdArr = [], data = {};
    $.each(li, function(){
        var item = $(this).data('value');
        if (item) {
            optionId = item.optionId, id = item.id;
            if (optionId != '' && optionId != undefined) { //单选或多选
                optionIdArr.push(optionId);
                if (optionId == 0 || optionId.indexOf(',0') > 0) { //其它选项
                    data[id] = item.text; //添加其它值
                }
            }
            else {
                data[item.id] = item.text; //输入框值
            }
        }
    });
    data.id = optionIdArr.join();   //option id
    data.sid = $('#hidden-sid').val();
    //var btn = $('#btn-vote');
    //btn.val('Submitting');
    $.post('/'+js_cur_lang_url + 'index.php?m=vote&a=vote', data, function(data) {    //提交
        ymPrompt.setDefaultCfg({closeTxt:'Close'});
        var fun = data ? ymPrompt.errorInfo : ymPrompt.succeedInfo;
        fun({
            message: data || vote_default_func_postVote_0,
            width: 300,
            height: 160,
            title: vote_default_func_checkVote_1
        });
        //btn.val('Submit');
    });
}

/**
 * 验证投票
 * 
 */
function checkVote(){
    var btn = $('#btn-vote').click(function(){
        //this.disabled = true;
        var result = checked = true;
        li.find('span:first-child').css('color', '#000');
        p.hide();
        var time = Date.parse(new Date());
        $.each(li, function(){
            var _this = $(this);
            if (_this.hasClass('needed')) { //必答
                if (_this.hasClass('box')) { //单选或多选
                    checked = checkBox(_this);
                }
                else if (_this.hasClass('text')) {//文本
                    checked = checkText(_this);
                }
                if (!checked) {
                    result = false;
                    var title = _this.find('span:first').css('color', 'red').text();
                    $('html').animate({
                        scrollTop: _this.offset().top
                    }, 300);
                    ymPrompt.setDefaultCfg({closeTxt:'Close'});
                    ymPrompt.alert({
                        message: vote_default_func_checkVote_0,
                        width: 300,
                        height: 160,
                        title: vote_default_func_checkVote_1,
                        btn: [['OK', 'yes']]
                    });
                    return false;
                }
            }
            else {  //非必答，设置选项值
                var isBox = _this.hasClass('box');  //单选或多选
                var optionId = getVoteId(_this);
                if (!isBox || optionId != '') {
                    _this.data('value', {
                        id: this.id,
                        optionId: optionId,
                        text: getVoteText(_this)
                    });
                }
            }
        });
        result ? postVote() : this.disabled = false;
    });
}

/**
 * 验证单选或多选
 * 
 * @param {object} element jquery对象
 */
function checkBox(element){
    var value = getVoteId(element), text = getVoteText(element);
    var needed = element.find('.span-needed').length > 0;
    if (value == '' || ((value == 0 || value.indexOf(',0') > 0) && needed && text == '')) {
        needed && $(':text,textarea', element).focus();
        return false;
    }
    element.data('value', {
        id: element.attr('id'),
        optionId: value,
        text: text
    });
    return true;
}

/**
 * 验证输入框
 * 
 * @param {object} element jquery对象
 */
function checkText(element){
    var text = getVoteText(element);
    if (text == '') {
        $(':text,textarea', element).focus();
        return false;
    }
    element.data('value', {
        id: element.attr('id'),
        text: text
    });
    return true;
}

/**
 * 获取单选或多选值
 * 
 * @param {object} element jquery对象
 */
function getVoteId(element){
   var value = $(':checked[checked]', element).map(function() {
        return this.value;
    }).get().join();
    return value;
}

/**
 * 获取输入框值
 * 
 * @param {object} element jquery对象
 */
function getVoteText(element){
    return $.trim($(':text,textarea', element).val());
}
