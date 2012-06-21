/*global $*/
/*global document*/
/*global json*/

var midas = midas || {};
midas.user = midas.user || {};
midas.user.login = midas.user.login || {};
midas.user.login.valid = false;
midas.user.login.prepare = true;

midas.user.login.validLoginForm = function () {
    'use strict';
    $.ajax({
        url: $('.webroot').val() + "/user/validentry",
        async: false,
        type: "POST",
        data: { entry: $('input[name=email]').val(), password: $('input[name=password]').val(), type: "login"},
        success: function (data) {
            $("form#loginForm div.loginError img").hide();
            if (data.search('true') !== -1) {
                midas.user.login.valid = true;
            } else {
                midas.user.login.valid = false;
            }
            if (midas.user.login.valid === false) {
                midas.createNotice($("form#loginForm div.loginError span").html(), 8000, 'error');
            }
        }
    });
};

$(document).ready(function () {
    'use strict';

    // Deal with login submission
    $('form#loginForm').submit(function () {
        midas.user.login.validLoginForm();
        midas.user.login.prepare = true;
        $('input[name=previousuri]').val(json.global.currentUri);
        return midas.user.login.valid;
    });

    // Deal with password recovery
    $('a#forgotPasswordLink').click(function () {
        midas.loadDialog("forgotpassword", "/user/recoverpassword");
        midas.showDialog("Recover Password");
    });

    $('form#loginForm input[name=submit]').click(function () {
        $("form#loginForm div.loginError img").show();
        $("form#loginForm div.loginError span").hide();
    });

    $("a.registerLink").unbind('click').click(function () {
        midas.showOrHideDynamicBar('register');
        midas.loadAjaxDynamicBar('register', '/user/register');
    });

});
