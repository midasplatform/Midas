// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.mfa = midas.mfa || {};

midas.mfa.validate = function () {
    'use strict';
    return true;
};

midas.mfa.saved = function (text) {
    'use strict';
    var resp = $.parseJSON(text);
    midas.createNotice(resp.message, 3500, resp.status);
};

$(window).load(function () {
    'use strict';
    $('#configForm').ajaxForm({
        beforeSubmit: midas.mfa.validate,
        success: midas.mfa.saved
    });
});
