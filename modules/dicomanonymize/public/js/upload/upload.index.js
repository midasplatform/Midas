// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.dcma = midas.dcma || {};

midas.dcma.sendParentToJavaSession = function () {
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
midas.dcma.sendParentToJavaSession();

// Save license change to the session
$('select[name=licenseSelect]:last').change(function () {
    'use strict';
    midas.dcma.sendParentToJavaSession();
});

// Save parent folder to the session
function folderSelectionCallback() {
    'use strict';
    midas.dcma.sendParentToJavaSession();
}

// Makes the same callback as the large upload applet
midas.doCallback('CALLBACK_CORE_JAVAUPLOAD_LOADED');
