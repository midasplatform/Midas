var midas = midas || {};
midas.packages = midas.packages || {};


midas.packages.successConfig = function (responseText, statusText, xhr, form) {
    var resp = $.parseJSON(responseText);
    midas.createNotice(resp.message, 3500, resp.status);
};

midas.packages.validateConfig = function (formData, jqForm, options) {
    return true;
};

$(document).ready(function () {
    $('form.packageEdit').ajaxForm({
        beforeSubmit: midas.packages.validateConfig,
        success: midas.packages.successConfig
    });
});