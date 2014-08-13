// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

jsonRegister = jQuery.parseJSON($('div.jsonRegister').html());

$('label.termLabel').after($('div.termDiv').html());
$('a.termOfService').click(function () {
    midas.loadDialog("terms", "/user/termofservice");
    midas.showBigDialog("Terms of Service");
});

$('#registerForm').find('input').each(function () {
    $(this).after('<span></span>');
})

var email = false;
var password = false;
var firstname = false;
var lastname = false;

$('#registerForm').find('input').focusout(function () {
    var obj = $(this);
    checkAll(obj);
});

function checkAll(obj) {
    if (obj.attr('name') == 'email') {
        if (!checkEmail(obj.val())) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> ' + jsonRegister.MessageNotValid);
            email = false;
        }
        else {
            $.post(json.global.webroot + "/user/userexists", {
                entry: obj.val()
            }, function (data) {
                if (data.search('true') != -1) {
                    obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/>' + jsonRegister.MessageNotAvailable);
                    email = false;
                }
                else {
                    obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
                    email = true;
                }
            });
        }
    }
    if (obj.attr('name') == 'firstname') {
        if (obj.val().length < 1) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> ' + jsonRegister.MessageFirstname);
            firstname = true;
        }
        else {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
            firstname = false;
        }
    }
    if (obj.attr('name') == 'lastname') {
        if (obj.val().length < 1) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> ' + jsonRegister.MessageLastname);
            lastname = false;
        }
        else {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
            lastname = true;
        }
    }
    if (obj.attr('name') == 'password1') {
        if (obj.val().length < 3) {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> ' + jsonRegister.MessagePassword);
            password = false;
        }
        else {
            obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
        }
    }
    if (obj.attr('name') == 'password2') {
        if (obj.val().length < 3) {
            password = false;
        }
        else {
            if ($('input[name=password1]').val() != obj.val()) {
                obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> ' + jsonRegister.MessagePasswords);
                password = false;
            }
            else {
                obj.parent('div').find('span').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
                password = true;
            }
        }
    }
}

$('#registerForm').find('input').focusin(function () {
    var obj = $(this);
    obj.parent('div').find('span').html('');
});

$('form#registerForm').submit(function () {
    var valid = validRegisterForm();
    if (valid) {
        $('#registerWaiting').show();
        $('#registerForm').find('input[type=submit]').attr('disabled', 'disabled');
    }
    return valid;
});

function checkEmail(mailteste) {
    var reg = /^[a-zA-Z0-9.+_-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]+$/;

    if (reg.test(mailteste)) {
        return true;
    }
    else {
        return (false);
    }
}

function validRegisterForm() {
    firstname = $('input[name=firstname]').val().length > 0;
    lastname = $('input[name=lastname]').val().length > 0;
    var terms = $('input[name=conditions]').is(':checked');
    password = ($('input[name=password1]').val().length > 2 && ($('input[name=password1]').val() == $('input[name=password2]').val()));
    if (terms && lastname && firstname && email && password) {
        return true;
    }
    else {
        $('form#registerForm div.registerError span').show();
        $('#registerForm').find('input').each(function () {
            checkAll($(this));
            if ($(this).attr('name') == 'conditions') {
                if (!$(this).is(':checked')) {
                    $(this).parent('div').find('span:last').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/nok.png"/> ' + jsonRegister.MessageTerms);
                }
                else {
                    $(this).parent('div').find('span:last').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/ok.png"/>');
                }
            }
        });
        return false;
    }
}
