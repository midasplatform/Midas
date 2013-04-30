var midas = midas || {};
midas.oauth = midas.oauth || {};

midas.oauth.validateLogin = function() {
    'use strict';
    $('.form-login input').attr('disabled', 'disabled');
    $('.loginErrorMessage').html('').hide();
    return true;
};

midas.oauth.loginCallback = function (response) {
    'use strict';

    try {
        var ret = $.parseJSON(response);
    } catch(e) {
        $('.form-login input').removeAttr('disabled');
        $('.loginErrorMessage').html('An internal error occurred. Please contact an administrator.').show();
        return;
    }

    if(ret.status == 'ok' && ret.redirect) {
        window.location = ret.redirect;
    }
    else {
        $('.form-login input').removeAttr('disabled');
        $('.loginErrorMessage').html(ret.message).show();
    }
};

$(document).ready(function () {
    'use strict';
    $('.form-login').ajaxForm({
        beforeSubmit: midas.oauth.validateLogin,
        success: midas.oauth.loginCallback
    });
});
