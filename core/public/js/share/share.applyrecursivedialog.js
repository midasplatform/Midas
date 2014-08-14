// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.share = midas.share || {};
midas.share.applyrecursive = {};

midas.share.applyrecursive.submitClicked = function () {
    'use strict';
    $('input#acceptApplyRecursive').attr('disabled', 'disabled');
    $('input#declineApplyRecursive').attr('disabled', 'disabled');
};

midas.share.applyrecursive.success = function (responseText) {
    'use strict';
    $('div.MainDialog').dialog('close');
    $('input#acceptApplyRecursive').removeAttr('disabled');
    $('input#declineApplyRecursive').removeAttr('disabled');
    var jsonResponse = $.parseJSON(responseText);

    if (jsonResponse == null) {
        midas.createNotice('Error', 4000);
        return;
    }
    if (jsonResponse[0]) {
        var success = jsonResponse[1].success;
        var failure = jsonResponse[1].failure;
        if (success > 0) {
            midas.createNotice('Successfully set policies on ' + success + ' resources', 4000);
        }
        if (failure > 0) {
            midas.createNotice('Failed to set policies on ' + failure + ' resources', 5000, 'error');
        }
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

$('#acceptApplyRecursive').click(function () {
    'use strict';
    midas.share.applyrecursive.submitClicked();
    midas.ajaxWithProgress($('#applyPoliciesRecursiveProgressBar'),
        $('#applyPoliciesRecursiveMessage'),
        json.global.webroot + '/share/applyrecursivedialog', {
            folderId: $('#folderId').val()
        },
        midas.share.applyrecursive.success
    );
});

$('input#declineApplyRecursive').click(function () {
    'use strict';
    $('div.MainDialog').dialog('close');
});
