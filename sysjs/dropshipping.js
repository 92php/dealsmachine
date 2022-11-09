/**
 * dropshipping.js      dropshipping
 *
 * @author              mashanling(msl-138@163.com)
 * @date                2011-10-18
 * @last modify         2011-10-19 by mashanling
 */
$(function() {
    ymPrompt.setDefaultCfg({
        closeTxt: 'Close'
    });
    
    $('.txt-quantity').live('keyup', function() {
        this.value = this.value.replace(/[^\d]/g, '');
    });
    $('#select-category').change(function() { //跳转至指定分类产品列表
        var v = $(this).val(), v = v == '0' ? '' : v;
        location.href = '/dropshipping-products' + v + '.html';
    });
    
    $('.a-favorite').click(function() { //加入收藏
        var goodsId = this.id.substr(2);
        Dropshipping.favorite(goodsId);
        return false;
    });
    
    $('#btn-add-sku').live('click', function() { //添加SKU
        Dropshipping.addSKU();
    });
    
    Dropshipping.del().showTooltip();
    
    switchTab(['#switch-1', '#switch-2']);
});

var Dropshipping = {};

/**
 * 加入收藏
 *
 * @param {int} goodsId 商品id
 */
Dropshipping.favorite = function(goodsId) {
    $.post('/dropshipping-favorite.html', 'goodsId=' + goodsId, function(data) {
        switch (data) {
            case '1': //未登陆
                location.replace('/m-users-a-sign.htm?ref=' + encodeURIComponent(location.href));
                break;
                
            case '2': //不合法商品id
                break;
            //case '3': //已经收藏
                
            default:
                ymPrompt.succeedInfo({
                    message: 'Add Successfully',
                    width: 300,
                    height: 160,
                    title: 'Notice information',
                    btn: [['OK', 'yes']]
                });
                data != 3 && $('#div-favorite').html(data);
                break;
        }
    });
};

/**
 * 添加SKU
 *
 */
Dropshipping.addSKU = function() {
    $('#tr-clone').clone().insertBefore($('#tr-do-clone')).removeAttr('id').find('.span-del-sku').show().click(function() {
        $(this).parents('tr:first').remove();
    }).end().find(':text:first').val('').end().find(':text:last').val(1);
};

/**
 * 订单
 *
 */
Dropshipping.del = function() {
    $('.a-delete').click(function() {
        confirm('Are you sure that you want to perform this action?') && $.post('/m-dropshipping-a-delete.html', 'sn=' + this.id.substr(2), location.reload);
    });
    return this;
};

Dropshipping.checkout = function(data) {
    $.post('/m-dropshipping-a-check_info.html', data, function(data) {
        if (data == '1') {
            location.replace('/m-users-a-sign.htm?ref=' + encodeURIComponent(location.href));
        }
        else if (data) {
            ymPrompt.errorInfo({
                message: data,
                width: 350,
                height: 170,
                title: 'Notice information',
                btn: [['OK', 'yes']]
            });
        }
        else {
            location.href = '/m-flow-a-checkout.html';
        }
    });
};

Dropshipping.showTooltip = function() {
    $('.td-tooltip').each(function(index, item) {
        $(item).hover(function(e) {
            var el = $(item).find('.div-content');
            !el.is(':visible') && el.show();
        }, function() {
            $(item).find('.div-content').hide();
        });
    });
};

Dropshipping.checkAddress = function() {
    var el = $('#form-address');//$('#form-address').find(':text').each(function() {this.value=this.name;});
    el.submit(function() {        
        el.valid() && Dropshipping.checkout(el.serialize());
        return false;
    });//.find(':text').each(function() {this.value=this.name;});
    el.validate({
        rules: {
            firstname: {
                required: true,
                maxlength: 60
            },
            lastname: {
                required: true,
                maxlength: 60
            },
            tel: {
                required: true,
                maxlength: 60
            },
            email: {
                required: true,
                maxlength: 60,
                email: true
            },
            addressline1: {
                required: true,
                maxlength: 120
            },
            city: {
                required: true,
                maxlength: 80
            },
            province: {
                required: true,
                maxlength: 80
            },
            country: {
                required: true
            },
            zipcode: {
                required: true,
                maxlength: 20
            }
        },
        messages: {
            firstname: {
                required: firstname_msg,
                maxlength: firstname_maxlength_msg
            },
            lastname: {
                required: lastname_msg,
                maxlength: lastname_maxlength_msg
            },
            tel: {
                required: tel_msg,
                maxlength: tel_maxlength_msg
            },
            email: {
                required: email_msg,
                maxlength: email_maxlength_msg
            },
            addressline1: {
                required: addressline1_msg,
                maxlength: addressline1_maxlength_msg
            },
            city: {
                required: city_msg,
                maxlength: city_maxlength_msg
            },
            province: {
                required: province_msg,
                maxlength: province_maxlength_msg
            },
            country: {
                required: country_msg
            },
            postalcode: {
                required: zipcode_msg,
                maxlength: zipcode_maxlength_msg
            }
        },
        success: function(label) {
            label.html('&nbsp;').addClass('checked');
        }
    });
}

Dropshipping.alertUS = function(orderSN) {
    ymPrompt.alert({
        message: 'There is us only product in your order <span style="color:red;">' + orderSN + '</span>. Please remove it from your cart',
        width: 350,
        height: 200,
        title: 'System Message',
        btn: [['ok']]
    });
}
