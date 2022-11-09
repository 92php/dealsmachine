/**
 * newsletter.js        邮件期刊管理js
 * 
 * @author              mashanling(msl-138@163.com)
 * @date                2012-07-11 15:39:40
 * @last modify         2012-08-03 14:16:40 by mashanling
 */

var NEWSLETTER = {//邮件期刊管理
    url: 'newsletter.php'
};

NEWSLETTER.list = {//列表
    /**
     * 绑定事件
     * 
     * @return {object} 邮件期刊对象
     */
    bindEvents: function() {
        var me = this;
        
        $('.a-delete').click(function() {//删除
            me['delete'](this.id.split('-').pop(), '此');
            return false;
        });
        
        $('#btn-delete').click(function() {//删除按钮 by mashanling on 2012-08-03 14:16:34
            var ids = getCheckedAll();
            ids && me['delete'](ids)
        });
        
        return this;
    },//end bindEvents
    
    /**
     * 删除
     * 
     * @param {string} ids        id串
     * @param {string} confirmMsg 确认信息
     * 
     * @return {void} 无返回值
     */
    'delete': function(ids, confirmMsg) {
        
        if (confirm('您确定要删除' + (confirmMsg || '选中') + '记录？')) {
            setLoading();
            
            $.post(NEWSLETTER.url + '?act=delete', 'ids=' + ids, function(data) {
                C.callback(data, location.href, L.del + L.success);
            });
        }
    }
};

NEWSLETTER.checkArr = [{//提交表单验证数组
    selector: '#txt-title',
    msg: '请输入标题'
}, {
    selector: '#txt-description',
    msg: '请输入描述'
}, {
    selector: '#txt-date',
    msg: '请选择日期'
}, {
    selector: '#txt-body',
    msg: '请输入html代码'
}];

NEWSLETTER.add = function() {//添加邮件期刊
    var me = this;
    var form = $('#form').submit(function () {
        
        if (!checkPost(me.checkArr)) {
            return false;   
        }
        
        setLoading();
    });
    
    $('#btn-preview').click(function () {//生成预览 by mashanling on 2012-07-31 11:42:42
        
        if (!checkPost(me.checkArr)) {
            return false;   
        }
        
        setLoading();
        
        $.post(NEWSLETTER.url + '?act=preview', form.serialize(), function(data) {//保存
            setLoading(false);
             $('#span-preview').html(data);
        });
       
        return false;
    });
    
    return this;
};