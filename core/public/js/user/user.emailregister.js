// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.register = midas.register || {};
midas.register.password = false;
midas.register.firstname = false;
midas.register.lastname = false;

$(document).ready(function () {
    $('label.termLabel').after($('div.termDiv').html());
    $('a.termOfService').click(function () {
        midas.loadDialog("terms", "/user/termofservice");
        midas.showBigDialog("Terms of Service");
    });

    $('#registerForm').find('input').each(function () {
        $(this).after('<span></span>');
    });

    $('#registerForm').find('input').focusout(function () {
        var obj = $(this);
        midas.register.checkAll(obj);
    });

    $('#registerForm').find('input').focusin(function () {
        var obj = $(this);
        obj.parent('div').find('span').html('');
    });

    $('#registerForm').ajaxForm({
        beforeSubmit: function () {
            var valid = midas.register.validRegisterForm();
            if (valid) {
                $('#registerWaiting').show();
                $('#registerForm').find('input[type=submit]').attr('disabled', 'disabled');
            }
            return valid;
        },
        success: function (responseText) {
            var resp = $.parseJSON(responseText);
            if (!resp) {
                midas.createNotice('Registration failed. Contact an administrator');
                return;
            }
            if (resp.status == 'ok' && resp.redirect) {
                // using location.replace intentionally to remove this from history
                window.location.replace(resp.redirect);
            }
            else {
                midas.createNotice(resp.message, 3000, resp.status);
            }
        }
    });
});

midas.register.checkAll = function (obj) {
    if (obj.attr('name') == 'firstName') {
        if (obj.val().length < 1) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> You must enter a first name');
            midas.register.firstname = true;
        }
        else {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
            midas.register.firstname = false;
        }
    }
    if (obj.attr('name') == 'lastName') {
        if (obj.val().length < 1) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> You must enter a last name');
            midas.register.lastname = false;
        }
        else {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
            midas.register.lastname = true;
        }
    }
    if (obj.attr('name') == 'password1') {
        if (obj.val().length < 3) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> Your password is too short');
            midas.register.password = false;
        }
        else {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
        }
    }
    if (obj.attr('name') == 'password2') {
        if (obj.val().length < 3) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> Passwords do not match');
            midas.register.password = false;
        }
        else {
            if ($('input[name=password1]').val() != obj.val()) {
                obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> Passwords do not match');
                midas.register.password = false;
            }
            else {
                obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
                midas.register.password = true;
            }
        }
    }
};

midas.register.validRegisterForm = function () {
    midas.register.firstname = $('input[name=firstName]').val().length > 0;
    midas.register.lastname = $('input[name=lastName]').val().length > 0;
    midas.register.terms = $('input.tosAccept').is(':checked');
    midas.register.password = ($('input[name=password1]').val().length > 2 && ($('input[name=password1]').val() == $('input[name=password2]').val()));
    if (midas.register.terms && midas.register.lastname && midas.register.firstname && midas.register.password) {
        return true;
    }
    else {
        $('form#registerForm div.registerError span').show();
        $('#registerForm').find('input').each(function () {
            midas.register.checkAll($(this));
            if ($(this).attr('name') == 'conditions') {
                if (!$(this).is(':checked')) {
                    $(this).parent('div').find('span:last').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> You must agree to the terms of service');
                }
                else {
                    $(this).parent('div').find('span:last').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
                }
            }
        });
        return false;
    }
};
