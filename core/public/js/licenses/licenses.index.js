// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.licenses = midas.licenses || {};

midas.licenses.newValidate = function (formData, jqForm, options) {};

midas.licenses.newSuccess = function (responseText, statusText, xhr, form) {
    'use strict';
    var resp = $.parseJSON(responseText);
    midas.createNotice(resp[1], 3000);
    window.location.replace(json.global.webroot + '/admin#ui-tabs-1');
    window.location.reload();
};

midas.licenses.existingValidate = function (formData, jqForm, options) {};

midas.licenses.existingSuccess = function (responseText, statusText, xhr, form) {
    'use strict';
    var resp = $.parseJSON(responseText);
    midas.createNotice(resp[1], 3000);
};

$(document).ready(function () {
    'use strict';
    $('form.existingLicense').ajaxForm({
        beforeSubmit: midas.licenses.existingValidate,
        success: midas.licenses.existingSuccess
    });
    $('form.newLicense').ajaxForm({
        beforeSubmit: midas.licenses.newValidate,
        success: midas.licenses.newSuccess
    });

    $('input.deleteLicense').click(function () {
        var id = $(this).attr('element');
        var html = '';
        html += 'Do you really want to delete this license?';
        html += '<br/>';
        html += '<br/>';
        html += '<span style="float: right;">';
        html += '<input class="globalButton deleteLicenseYes" type="button" value="' + json.global.Yes + '"/>';
        html += '<input style="margin-left:15px;" class="globalButton deleteLicenseNo" type="button" value="' + json.global.No + '"/>';
        html += '</span>';
        midas.showDialogWithContent('Delete License', html, false);

        $('input.deleteLicenseYes').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
            midas.ajaxSelectRequest = $.ajax({
                type: 'POST',
                url: json.global.webroot + '/licenses/delete',
                data: {
                    licenseId: id
                },
                success: function (jsonContent) {
                    var jsonResponse = $.parseJSON(jsonContent);
                    midas.createNotice(jsonResponse[1], 3000);
                    if (jsonResponse[0]) {
                        $('div.licenseContainer[element=' + id + ']').remove();
                    }
                }
            });
        });
        $('input.deleteLicenseNo').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });
});
