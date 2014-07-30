// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.ldap = midas.ldap || {};
midas.ldap.config = midas.ldap.config || {};

midas.ldap.config.validateConfig = function (formData, jqForm, options) {};

midas.ldap.config.successConfig = function (responseText,
    statusText,
    xhr,
    form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = jQuery.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000, 'error');
        return false;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
        return true;
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
        return true;
    }
};

$(document).ready(function () {
    'use strict';
    $('#configForm').ajaxForm({
        beforeSubmit: midas.ldap.config.validateConfig,
        success: midas.ldap.config.successConfig
    });
});
