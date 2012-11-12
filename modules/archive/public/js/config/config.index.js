var midas = midas || {};
midas.archive = midas.archive || {};

midas.archive.validateForm = function () {
    return true;
};

midas.archive.submitSuccess = function (responseText) {
    var retVal = $.parseJSON(responseText);
    if(!retVal) {
        midas.createNotice('An error occurred, check the admin logs', 2500, 'error');
    }
    else {
        midas.createNotice(retVal.message, 3000, retVal.status);
    }
};

$(document).ready(function () {
    $('#unzipCommand').val(json.archive.unzipCommand);

    $('#configForm').ajaxForm({
        beforeSubmit: midas.archive.validateForm,
        success: midas.archive.submitSuccess
    });
});