// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.oauth = midas.oauth || {};

midas.oauth.validateLogin = function () {
    'use strict';
    $('.form-login input').attr('disabled', 'disabled');
    $('.loginErrorMessage').html('').hide();
    return true;
};

midas.oauth.loginCallback = function (response) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(response);
    }
    catch (e) {
        $('.form-login input').removeAttr('disabled');
        $('.loginErrorMessage').html('An internal error occurred. Please contact an administrator.').show();
        return;
    }

    if (jsonResponse.status == 'ok' && jsonResponse.redirect) {
        window.location = jsonResponse.redirect;
    }
    else {
        $('.form-login input').removeAttr('disabled');
        $('.loginErrorMessage').html(jsonResponse.message).show();
    }
};

$(document).ready(function () {
    'use strict';
    $('.form-login').ajaxForm({
        beforeSubmit: midas.oauth.validateLogin,
        success: midas.oauth.loginCallback
    });
});
