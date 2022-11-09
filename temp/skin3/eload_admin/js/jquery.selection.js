/**
 * jquery.selection.js      jquery文本输入框插件，支持获取（设置）光标位置、在光标处插入文字、选择指定范围文字
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2011-08-23
 * @last modify             2011-08-23 by mashanling
 */

$.fn.extend({
    /**
     * 获取光标位置
     * 
     */
    getCursorPosition: function() {
        var position = 0, field = this[0];
        if (document.selection) {   //ie
            field.focus();
            var range = document.selection.createRange();
            range.moveStart('character', -field.value.length);
            position = range.text.length;
        }
        else if (field.selectionStart || field.selectionStart == 0) {   //firefox
            position = field.selectionStart;
        }
        return position;
    },
    
    /**
     * 设置光标位置
     * 
     * @param {int} position 光标位置
     */
    setCursorPosition: function(position) {
        return this.each(function() {
            if (this.setSelectionRange) {   //firefox
                this.focus();
                this.setSelectionRange(position, position);
            }
            else if (this.createTextRange) {    //ie
                var range = this.createTextRange();
                range.collapse(true);
                range.moveEnd('character', position);
                range.moveStart('character', position);
                range.select();
            }
        });
    },
    
    /**
     * 在光标处插入文字
     * 
     * @param {string} text 待插入文字
     */
    insertAtCursor: function(text) {
        return this.each(function() {
            var position = $(this).getCursorPosition();
            this.value = this.value.substr(0, position) + text + this.value.substr(position);
        });
    },
    
    /**
     * 选择指定范围文字
     * 
     * @param {int} start 开始位置
     * @param {int} end   结束位置
     */
    selection: function(start, end) {
        if (start == undefined || end == undefined || start == end) {
            $(this[0]).setCursorPosition(start);
        }
        return this.each(function(){
            if (this.createTextRange) { //ie
                var selRange = this.createTextRange();
                if (end === undefined || start == end) {
                    selRange.move("character", start);
                    selRange.select();
                }
                else {
                    selRange.collapse(true);
                    selRange.moveStart("character", start);
                    selRange.moveEnd("character", end);
                    selRange.select();
                }
            }
            else if (this.setSelectionRange) {  //firefox
                this.setSelectionRange(start, end);
            }
            else if (this.selectionStart) {
                this.selectionStart = start;
                this.selectionEnd = end;
            }
        });
    }
});