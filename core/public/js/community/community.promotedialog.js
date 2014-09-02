// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.community = midas.community || {};

midas.community.promotedialogBeforeSubmit = function (formData, jqForm, options) {
    'use strict';
    $('#addToGroupsSubmitButton').attr('disabled', 'disabled');
    $('#promoteDialogLoading').show();
    return true;
};

midas.community.promotedialogSuccess = function (responseText, statusText, xhr, form) {
    'use strict';
    $('div.MainDialog').dialog('close');
    $('#addToGroupsSubmitButton').removeAttr('disabled');
    $('#promoteDialogLoading').hide();
    var jsonResponse = $.parseJSON(responseText);

    if (jsonResponse === null) {
        midas.createNotice('Error occurred. Check the logs.', 4000);
        return;
    }
    midas.createNotice(jsonResponse[1], 4000);
    if (jsonResponse[0]) {
        window.location.replace(json.global.webroot +
            '/community/manage?communityId=' + $('#promoteCommunityId').val() + '#tabs-Users');
        window.location.reload();
    }
};

$(document).ready(function () {
    'use strict';
    $('#promoteGroupForm').ajaxForm({
        beforeSubmit: midas.community.promotedialogBeforeSubmit,
        success: midas.community.promotedialogSuccess
    });
});
