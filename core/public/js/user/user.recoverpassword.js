// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.user = midas.user || {};

midas.user.validateRecoverPassword = function (formData, jqForm, options) {
    'use strict';
    var form = jqForm[0];
    if (form.email.value.length < 1) {
        midas.createNotice('Error email', 4000, 'error');
        return false;
    }
};

midas.user.successRecoverPassword = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse = $.parseJSON(responseText);
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
        $("div.MainDialog").dialog("close");
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

$(document).ready(function () {
    'use strict';
    $('#recoverPasswordForm').ajaxForm({
        beforeSubmit: midas.user.validateRecoverPassword,
        success: midas.user.successRecoverPassword
    });
});
