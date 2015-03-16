// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.mfa = midas.mfa || {};

midas.mfa.validateConfig = function (formData, jqForm, options) {};

midas.mfa.successConfig = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if (jsonResponse === null) {
        midas.createNotice('An internal error occurred, please contact an administrator', 4000, 'error');
        return;
    }
    midas.createNotice(jsonResponse.message, 4000, jsonResponse.status);
};

/**
 * Enable/disable the form elements based on the top checkbox
 */
midas.mfa.setEnabledState = function () {
    'use strict';
    if ($('#useOtpCheckbox').is(':checked')) {
        $('#otpSecret').removeAttr('disabled');
        $('#otpAlgorithmSelect').removeAttr('disabled');
        $('#otpLength').removeAttr('disabled');
    }
    else {
        $('#otpSecret').attr('disabled', 'disabled');
        $('#otpAlgorithmSelect').attr('disabled', 'disabled');
        $('#otpLength').attr('disabled', 'disabled');
    }
};

$(document).ready(function () {
    'use strict';
    $('#mfaConfigForm').ajaxForm({
        beforeSubmit: midas.mfa.validateConfig,
        success: midas.mfa.successConfig
    });
    $('#useOtpCheckbox').click(function () {
        midas.mfa.setEnabledState();
    });
    midas.mfa.setEnabledState();
});
