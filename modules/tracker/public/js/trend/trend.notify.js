// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.tracker = midas.tracker || {};

midas.tracker.validateNotificationConfig = function () {
    'use strict';
    return true;
};

midas.tracker.successNotificationConfig = function (text) {
    'use strict';
    var resp = $.parseJSON(text);
    midas.createNotice(resp.message, 2500, resp.status);
};

midas.tracker.toggleForm = function () {
    'use strict';
    if ($('#noNotify').is(':checked')) {
        $('#operatorSelect').attr('disabled', 'disabled');
        $('input.thresholdValue').val('').attr('disabled', 'disabled');
    }
    else {
        $('#operatorSelect').removeAttr('disabled');
        $('input.thresholdValue').removeAttr('disabled');
    }
};

$(document).ready(function () {
    'use strict';
    $('input[name=doNotify]').change(midas.tracker.toggleForm);
    var html = $.trim($('#settingInfo').html());
    if (html == '') {
        $('#noNotify').click();
    }
    else {
        var setting = $.parseJSON(html);
        $('#yesNotify').click();
        $('#operatorSelect').val(setting.operator.replace(/&gt;/, '>').replace(/&lt;/, '<'));
    }
    $('#thresholdNotifyForm').ajaxForm({
        beforeSubmit: midas.tracker.validateNotificationConfig,
        success: midas.tracker.successNotificationConfig
    });
});
