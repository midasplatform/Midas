// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.statistics = midas.statistics || {};

midas.statistics.validateConfig = function (formData, jqForm, options) {};

midas.statistics.successConfig = function (responseText, statusText, xhr, form) {
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
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

$(document).ready(function () {
    'use strict';
    $('#configForm').ajaxForm({
        beforeSubmit: midas.statistics.validateConfig,
        success: midas.statistics.successConfig
    });
});
