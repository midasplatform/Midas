var midas = midas || {};
midas.mfa = midas.mfa || {};

midas.mfa.validate = function () {
    return true;
};

midas.mfa.saved = function (text) {
    var resp = $.parseJSON(text);
    midas.createNotice(resp.message, 3500, resp.status);
};

$(window).load(function () {
    $('#configForm').ajaxForm({
        beforeSubmit: midas.mfa.validate,
        success: midas.mfa.saved
    });
});
