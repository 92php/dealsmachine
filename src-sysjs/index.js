/**
 * index.js            通用库
 *
 * @author              mashanling(msl-138@163.com)
 * @date                2012-04-10 15:04:28
 * @last modify         2012-04-10 15:04:28 by mashanling
 */
var imgAutoScrollTime = 4000; //图片轮转时间间隔 ，毫秒
var imgCount = 0; //轮转图片数量
var imgIndex = 0; //当前图片索引
var imgAutoScroll;

(function() {
    setImgAutoScroll();
    initImgAutoScroll();
}());

/**
 * 轮转图片鼠标移进移出事件
 *
 * @return {void} 无返回值
 */
function initImgAutoScroll() {

    var el = $('#ul-auto_scroll_item li').hover(function() {
        var index = el.index($(this));
        clearImgAuto(index);
        showImg(index);
    }, function() {
        setImgAutoScroll();
    });
    
    imgCount = el.length;
    
    var li = $('#ul-auto_scroll > li').hover(function() {
        var index = li.index($(this));
        clearImgAuto(index);
    }, function() {
        setImgAutoScroll();
    });
}

/**
 * 显示轮转图片
 *
 * @param {int} index 图片索引
 *
 * @return {void} 无返回值
 */
function showImg(index) {
    var el = $('#ul-auto_scroll_item li').removeClass('overpic');
    $(el[index]).addClass('overpic');
    el = $('#ul-auto_scroll > li').hide();
    $(el[index]).fadeIn(800);
}

/**
 * 设置自动图片轮转
 *
 * @return {void} 无返回值
 */
function setImgAutoScroll() {
    imgAutoScroll = setInterval(setImgAuto, imgAutoScrollTime);
}

/**
 * 设置轮转图片
 *
 * @return {void} 无返回值
 */
function setImgAuto() {
    imgIndex++;
    imgIndex = imgIndex >= imgCount ? 0 : imgIndex;
    showImg(imgIndex);
}

/**
 * 清除图片轮转
 *
 * @return {void} 无返回值
 */
function clearImgAuto(index) {
    imgIndex = index >= imgCount ? 0 : index;
    clearInterval(imgAutoScroll);
}
