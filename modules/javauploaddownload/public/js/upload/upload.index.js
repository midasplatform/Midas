// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.javauploaddownload = midas.javauploaddownload || {};
midas.javauploaddownload.upload = {};

midas.javauploaddownload.upload.sendParentToJavaSession = function () {
    'use strict';
    $.post(json.global.webroot + '/javauploaddownload/upload', {
        parent: $('#destinationId').val(),
        license: $('select[name=licenseSelect]:last').val()
    });
};

$('.browseMIDASLink').click(function () {
    'use strict';
    midas.loadDialog('select', "/browse/selectfolder?policy=write");
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
midas.javauploaddownload.upload.sendParentToJavaSession();

// Save license change to the session
$('select[name=licenseSelect]:last').change(function () {
    'use strict';
    midas.javauploaddownload.upload.sendParentToJavaSession();
});

// Save parent folder to the session
function folderSelectionCallback() {
    'use strict';
    midas.javauploaddownload.upload.sendParentToJavaSession();
}

midas.doCallback('CALLBACK_CORE_JAVAUPLOAD_LOADED');
