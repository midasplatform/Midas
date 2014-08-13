// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.user = midas.user || {};

/**
 * Displays the create new user
 */
midas.user.doCreate = function () {
    var content = $('#registerFormTemplate').clone();
    content.find('form.registerForm').attr('id', 'registerForm');
    content.find('div.registerError').attr('id', 'registerError');
    midas.showDialogWithContent('Register', content.html(), false, {
        width: 380
    });

    $('.registerForm input[name=nopassword]').unbind('change').change(function () {
        var disabled = $(this).is(':checked');

        if (disabled) {
            $('#registerForm').find('input[type="password"]').val('').attr('disabled', 'disabled');
        }
        else {
            $('#registerForm').find('input[type="password"]').removeAttr('disabled');
        }
    });

    $('#registerForm').ajaxForm({
        success: function (responseText, statusText, xhr, form) {
            var resp = $.parseJSON(responseText);
            if (resp.status == 'ok') {
                window.location.reload();
            }
            else {
                var errorText = '<ul>';
                if (resp.alreadyRegistered) {
                    $('#registerForm').find('input[type=text],input[type=password]')
                        .removeClass('invalidField').addClass('validField');
                    $('#registerForm').find('input[name=email]').removeClass('validField').addClass('invalidField');
                    errorText += '<li>' + resp.message + '</li>';
                }
                else {
                    $('#registerForm').find('input[type=text],input[type=password]')
                        .removeClass('validField').addClass('invalidField');

                    $.each(resp.validValues, function (field, value) {
                        $('#registerForm').find('input[name=' + field + ']')
                            .removeClass('invalidField').addClass('validField');
                    });
                    if (!resp.validValues.email) {
                        errorText += '<li>Invalid email</li>';
                    }
                    if (!resp.validValues.firstname) {
                        errorText += '<li>Invalid first name</li>';
                    }
                    if (!resp.validValues.lastname) {
                        errorText += '<li>Invalid last name</li>';
                    }
                    if (!resp.validValues.password1) {
                        errorText += '<li>Invalid password</li>';
                    }
                    if (!resp.validValues.password2) {
                        errorText += '<li>Passwords must match</li>';
                    }
                }
                errorText += '</ul>';
                $('#registerError').html(errorText);
            }
        }
    });
}

$(document).ready(function () {
    $('.userBlock').click(function () {
        $(location).attr('href', ($('> .userTitle', this).attr('href')));
    });
    $('a.createUserLink').unbind('click').click(midas.user.doCreate);
});
