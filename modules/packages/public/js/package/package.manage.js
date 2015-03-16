// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.packages = midas.packages || {};

midas.packages.successConfig = function (responseText, statusText, xhr, form) {
    'use strict';
    var resp = $.parseJSON(responseText);
    midas.createNotice(resp.message, 3500, resp.status);
};

midas.packages.validateConfig = function (formData, jqForm, options) {
    'use strict';
    return true;
};

$(document).ready(function () {
    'use strict';
    $('form.packageEdit').ajaxForm({
        beforeSubmit: midas.packages.validateConfig,
        success: midas.packages.successConfig
    });
});
