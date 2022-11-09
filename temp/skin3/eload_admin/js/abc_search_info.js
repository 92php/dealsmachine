/**
 * abc_search_info.js   abc搜索关键字信息设置，包括描述、Hot Searches、Related Tags
 * 
 * @author              mashanling(msl-138@163.com)
 * @date                2012-06-06 14:19:19
 * @last modify         2012-07-03 14:13:37 by mashanling
 */
$(function() {
    ABC_SEARCH.bindEvent();
});

var ABC_SEARCH = {
    url: 'abc_search_info.php',
    form: $('#form'),
    webTitle: $('#web_title'),
    metaKeyword: $('#meta_keyword'),
    metaDescription: $('#meta_description'),
    'description': $('#description'),
    hot: $('#hot'),
    related: $('#related')
};

ABC_SEARCH.bindEvent = function() {//绑定事件
    $('#index').width(50).change(function() {//改变字母
       ABC_SEARCH.reset().loadData($(this).val());
    });
    
    $('#a-rand').click(function() {//随机取描述
        ABC_SEARCH['description'].val(descArr[intval(descArr.length * Math.random())]);
    });
    
    this.save();//保存
}

ABC_SEARCH.reset = function(data) {//设置内容
    !data && setLoading('数据加载中...');
    data = data || {};
    ABC_SEARCH.webTitle.val(data.web_title || '');
    ABC_SEARCH.metaKeyword.val(data.meta_keyword || '');
    ABC_SEARCH.metaDescription.val(data.meta_description || '');
    ABC_SEARCH['description'].val(data['description'] || '');
    ABC_SEARCH.hot.val(data.hot || '');
    ABC_SEARCH.related.val(data.related || '');
    
    return this;
}

ABC_SEARCH.loadData = function(index) {//加载数据
    var me = this.reset();
   $.get(ABC_SEARCH.url + '?act=get_data&index=' + index, function(data) {
       setLoading(false);
       data && me.reset($.parseJSON(data));
   });
   
   return this;
};

ABC_SEARCH.save = function() {//保存
    var me = this;
    
    me.form.submit(function() {
        setLoading();
        $.post(me.url + '?act=save', me.form.serialize(), function(data) {
            setLoading(false);
            
            if (data) {
                me.reset($.parseJSON(data));
                Alert('操作成功', true);
            }
        });
        return false;
    });
    
    return me;
};