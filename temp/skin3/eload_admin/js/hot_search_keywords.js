/**
 * hot_search_keywords.js   后台热门搜索关键字设置js
 * 
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-04-28 15:13:59
 * @last modify             2012-04-28 15:13:59 by mashanling
 */

$(function() {
    var msg = $.cookie(L.msg);
    $.cookie(L.msg, null);
    msg && Alert(msg, true); //提示信息
    
    hot_search_keywords_action();
});

function hot_search_keywords_action() {
    $('#btn-save').click(function() {
        $.post('?act=save', {
            'default': $('#txt-default').val(),
            keywords: $('#txt-keywords').val() 
        }, function(data){
            C.callback(data, location.href, L.edit + L.success);
        });
    });
}