// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.archive = midas.archive || {};

midas.archive.validateForm = function () {
    'use strict';
    return true;
};

midas.archive.submitSuccess = function (responseText) {
    'use strict';
    var retVal = $.parseJSON(responseText);
    if (!retVal) {
        midas.createNotice('An error occurred, check the admin logs', 2500, 'error');
    }
    else {
        midas.createNotice(retVal.message, 3000, retVal.status);
    }
};

$(document).ready(function () {
    'use strict';
    $('#unzipCommand').val(json.archive.unzipCommand);

    $('#configForm').ajaxForm({
        beforeSubmit: midas.archive.validateForm,
        success: midas.archive.submitSuccess
    });
});
