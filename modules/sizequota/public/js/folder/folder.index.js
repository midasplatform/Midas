// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.sizequota = midas.sizequota || {};
midas.sizequota.folder = midas.sizequota.folder || {};
midas.sizequota.constant = {
    MIDAS_SIZEQUOTA_USE_DEFAULT_QUOTA: '0',
    MIDAS_SIZEQUOTA_USE_SPECIFIC_QUOTA: '1'
};

midas.sizequota.folder.validateConfig = function (formData, jqForm, options) {};

midas.sizequota.folder.successConfig = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice('An error occured. Please check the logs.', 4000, 'error');
        return false;
    }
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        location.reload();
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

midas.sizequota.folder.useDefaultFolderQuotaChanged = function () {
    'use strict';
    var selected = $('input[id="use_default_folder_quota"]:checked');

    if (selected.val() == 1) {
        $('input#folder_quota_value').attr('disabled', 'disabled');
        $('select#folder_quota_unit').attr('disabled', 'disabled');
    }
    else {
        $('input#folder_quota_value').removeAttr('disabled');
        $('select#folder_quota_unit').removeAttr('disabled');
    }
};

$(document).ready(function () {
    'use strict';
    $('#sizequota_folder').ajaxForm({
        beforeSubmit: midas.sizequota.folder.validateConfig,
        success: midas.sizequota.folder.successConfig
    });

    $('input[id="use_default_folder_quota"]').change(midas.sizequota.folder.useDefaultFolderQuotaChanged);
    midas.sizequota.folder.useDefaultFolderQuotaChanged();

    var content = $('#quotaValue').html();
    if (content != '' && content !== 0) {
        var quota = parseInt($('#quotaValue').html());
        var used = parseInt($('#usedSpaceValue').html());

        if (used <= quota) {
            var free = quota - used;
            var hUsed = $('#hUsedSpaceValue').html();
            var hFree = $('#hFreeSpaceValue').html();
            var data = [
                ['Used space (' + hUsed + ')', used],
                ['Free space (' + hFree + ')', free]
            ];
            $('#quotaChart').show();
            $.jqplot('quotaChart', [data], {
                seriesDefaults: {
                    renderer: $.jqplot.PieRenderer,
                    rendererOptions: {
                        showDataLabels: true
                    }
                },
                legend: {
                    show: true,
                    location: 'e'
                }
            });
        }
    }
});
