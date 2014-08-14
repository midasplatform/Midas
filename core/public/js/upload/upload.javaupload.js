// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.upload = midas.upload || {};
midas.upload.javaupload = {};

midas.upload.javaupload.sendParentToJavaSession = function () {
    'use strict';
    $.post(json.global.webroot + '/upload/javaupload', {
        parent: $('#destinationId').val(),
        license: $('select[name=licenseSelect]:last').val()
    });
};

$('.browseMIDASLink').click(function () {
    'use strict';
    midas.loadDialog("select", "/browse/selectfolder/?policy=write");
    midas.showDialog('Browse', null, {
        close: function () {
            $('.uploadApplet').show();
        }
    });
    $('.uploadApplet').hide();
});

$('.destinationId').val($('#destinationId').val());
$('.destinationUpload').html($('#destinationUpload').html());

// Save initial state to the session
midas.upload.javaupload.sendParentToJavaSession();

// Save license change to the session
$('select[name=licenseSelect]:last').change(function () {
    'use strict';
    midas.upload.javaupload.sendParentToJavaSession();
});

// Save parent folder to the session
function folderSelectionCallback() {
    'use strict';
    midas.upload.javaupload.sendParentToJavaSession();
}

midas.doCallback('CALLBACK_CORE_JAVAUPLOAD_LOADED');
