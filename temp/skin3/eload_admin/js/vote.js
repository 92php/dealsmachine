/**
 * vote.js              后台投票管理js
 *
 * @author              mashanling(msl-138@163.com)
 * @date                2011-08-18
 * @last modify         2011-08-18 by mashanling
 */
$(function(){
    var msg = $.cookie(L.msg);
    $.cookie(L.msg, null);
    msg && Alert(msg, true); //提示信息
});

//投票管理
var VOTE = {};

//题组
VOTE.subject = {
    //题组-验证数组
    checkArr: [{
        id: 'txt-subject',
        msg: '题组不能为空'
    }],
    
    /**
     * 投票管理首页
     *
     */
    index: function(){
        $('.a-delete').click(function(){
            if (confirm('删除该题组的同时，将会删除其下标题以及选项\n\n您确定要删除?')) {
                $.post('vote.php?act=subject_delete', 'subject_id=' + this.id.substr(2), function(data){
                    C.callback(data, 'vote.php', L.del + L.success);
                });
            }
        });
    },
    
    /**
     * 题组-添加或编辑
     *
     */
    add: function(){
        var _this = this;
        
        $('#a-tpl').click(function(){//选择题组模板
            var tpl = showModalDialog('vote.php?act=get_tpl', '', 'dialogHeight: 250px; dialogWidth: 450px; status: false');
            tpl && $('#txt-tpl').val(tpl);
        });
        
        $('#form-subject').submit(function(){ //提交
            if (checkPost(_this.checkArr)) {
                DoProcess();
                $.post('vote.php?act=subject_update', $(this).serialize(), function(data){
                    var msg = subjectId ? L.edit : L.add;
                    msg += L.success;
                    C.callback(data, 'vote.php', msg);
                });
            }
            return false;
        });
    }
};

//标题
VOTE.title = {
    //标题-验证数组
    checkArr: [{
        id: 'txt-title',
        msg: '标题不能为空'
    }, {
        id: 'select-subjectId',
        msg: '请选择所属题组'
    }],
    
    /**
     * 标题-列表页
     *
     */
    list: function(){
        $('.a-delete').click(function(){
            if (confirm('删除该标题的同时，将会删除其下选项\n\n您确定要删除?')) {
                $.post('vote.php?act=title_delete', 'title_id=' + this.id.substr(2), function(data){
                    C.callback(data, 'vote.php?act=title_list&subject_id=' + subjectId, L.del + L.success);
                });
            }
        });
        $('.a-enable').each(function(){ //开启/关闭
            var _this = $(this);
            _this.attr('title', '点击' + (_this.text() == L.openclose[1] ? L.openclose[0] : L.openclose[1])).click(function(){
                var open = _this.text() == L.openclose[1];
                C.setOne('vote.php?act=enable', {
                    table: 'title',
                    column: 'enable',
                    value: open ? 0 : 1,
                    id: this.className.split(' ')[1].substr(2)
                }, _this, {
                    to: open ? L.openclose[1] : L.openclose[0],
                    now: open ? L.openclose[0] : L.openclose[1]
                });
            });
        });
        $('.a-needed').each(function(){//是否必答
            var _this = $(this);
            _this.attr('title', '点击' + (_this.text() == L.yesno[1] ? L.yesno[0] : L.yesno[1])).click(function(){
                var yes = _this.text() == L.yesno[1];
                C.setOne('vote.php?act=enable', {
                    table: 'title',
                    column: 'needed',
                    value: yes ? 0 : 1,
                    id: this.className.split(' ')[1].substr(2)
                }, _this, {
                    to: yes ? L.yesno[1] : L.yesno[0],
                    now: yes ? L.yesno[0] : L.yesno[1]
                });
            });
        });
        return this;
    },
    
    /**
     * 标题-添加或编辑
     *
     */
    add: function(){
        var _this = this;
        
        var el = $('#txt-title');
        $('#a-insert-br').click(function() {    //插入换行符
            el.insertAtCursor('$br');
        });
        $('#a-remove-br').click(function() {    //清空换行符
            el.val(el.val().replace(/\$br/, ''));
        });
        $('#td-other select').change(function(){
            this.value == 1 ? $('.tr-other').show() : $('.tr-other').hide();
        });
        
        $('#form-title').submit(function(){
            if (checkPost(_this.checkArr)) {
                $.post('vote.php?act=title_update', $(this).serialize(), function(data){
                    var msg = L.edit;
                    if (!titleId) {
                        msg = L.add;
                        subjectId = $('#select-subjectId').val();
                    }
                    msg += L.success;
                    C.callback(data, 'vote.php?act=title_list&subject_id=' + subjectId, msg);
                });
            }
            return false;
        });
    }
};

//选项
VOTE.option = {
    //选项-验证数组
    checkArr: [{
        id: 'txt-name',
        msg: '选项名称不能为空'
    }, {
        id: 'select-subjectId',
        msg: '请选择所属题组'
    }, {
        id: 'select-titleId',
        msg: '请选择所属标题'
    }],
    
    /**
     * 选项-列表页
     *
     */
    list: function(){
        $('.a-delete').click(function(){
            if (confirm('您确定要删除该选项吗?')) {
                $.post('vote.php?act=option_delete', 'option_id=' + this.id.substr(2), function(data){
                    C.callback(data, 'vote.php?act=option_list&subject_id={0}&title_id={1}'.format(subjectId, titleId), L.del + L.success);
                });
            }
        });
        
        $('.a-enable').each(function(){ //开启/关闭
            var _this = $(this);
            _this.attr('title', '点击' + (_this.text() == L.openclose[1] ? L.openclose[0] : L.openclose[1])).click(function(){
                var open = _this.text() == L.openclose[1];
                C.setOne('vote.php?act=enable', {
                    table: 'option',
                    column: 'enable',
                    value: open ? 0 : 1,
                    id: this.className.split(' ')[1].substr(2)
                }, _this, {
                    to: open ? L.openclose[1] : L.openclose[0],
                    now: open ? L.openclose[0] : L.openclose[1]
                });
            });
        });
    },
    
    /**
     * 选项-添加或编辑
     *
     */
    add: function(){
        var _this = this;
        
        $('#form-option').submit(function(){
            if (checkPost(_this.checkArr)) {
                $.post('vote.php?act=option_update', $(this).serialize(), function(data){
                    var msg = optionId ? L.edit : L.add;
                    msg += L.success;
                    C.callback(data, 'vote.php?act=option_list&subject_id={0}&title_id={1}'.format(subjectId != 0 ? subjectId : $('#select-subjectId').val(), titleId != 0 ? titleId : $('#select-titleId').val()), msg);
                });
            }
            return false;
        });
        return this;
    },
    
    /**
     * 选项-加载标题
     *
     */
    initTitle: function(){
        var htmlArr = [], tpl = '<option value="{0}"{1}>{2}</option>';
        title.html(tpl.format('', '', '加载中...'));
        $.get('vote.php?act=getTitles&subject_id=' + subject.val(), function(data){
            data = eval(data);
            $.each(data, function(index, item){
                htmlArr.push(tpl.format(item.title_id, item.title_id == titleId ? ' selected="selected"' : '', item.title));
            });
            title.html(htmlArr.length > 0 ? htmlArr.join('') : tpl.format('', '', '请选择'));
        });
    }
};
