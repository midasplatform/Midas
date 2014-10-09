// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.javauploaddownload = midas.javauploaddownload || {};
midas.javauploaddownload.revision = {};

// When the user sets changes or selects a license, we write the value into
// the session for when the applet upload is complete
midas.javauploaddownload.revision.sendFormToJavaSession = function () {
    'use strict';
    $.post(json.global.webroot + '/upload/javarevisionsession', {
        changes: $('textarea[name=revisionChanges]:last').val(),
        license: $('select[name=licenseSelect]:last').val(),
        itemId: json.item.item_id
    });
};

$(document).ready(function () {
    'use strict';
    midas.javauploaddownload.revision.sendFormToJavaSession();

    // Save license change to the session
    $('select[name=licenseSelect]:last').change(function () {
        midas.javauploaddownload.revision.sendFormToJavaSession();
    });

    // Save changes message to session (if it's not blank)
    $('textarea[name=revisionChanges]:last').blur(function () {
        if ($(this).val() != '') {
            midas.javauploaddownload.revision.sendFormToJavaSession();
        }
    });

    midas.doCallback('CALLBACK_CORE_JAVAREVISIONUPLOAD_LOADED');
});
