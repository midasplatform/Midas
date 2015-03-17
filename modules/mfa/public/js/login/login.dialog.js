// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.mfa = midas.mfa || {};

midas.mfa.validateSubmit = function (formData, jqForm, options) {};

midas.mfa.successSubmit = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if (jsonResponse.status == 'ok') {
        window.location.reload();
    }
    else {
        midas.createNotice(jsonResponse.message, 3000, jsonResponse.status);
    }
};

$(document).ready(function () {
    'use strict';
    $('#mfaLoginForm').ajaxForm({
        beforeSubmit: midas.mfa.validateSubmit,
        success: midas.mfa.successSubmit
    });
    $('#otpToken').focus();
});
