// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.demo = midas.demo || {};

midas.demo.success = function (responseText, statusText, xhr, form) {
    'use strict';
    try {
        var jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice('An error occured. Please check the logs.', 4000, 'error');
        return false;
    }
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000, 'error');
        return false;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
        return false;
    }
};

$(document).ready(function () {
    'use strict';
    $('#configForm').ajaxForm({
        success: midas.demo.success
    });
});
