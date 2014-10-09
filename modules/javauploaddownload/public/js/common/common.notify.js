// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.javauploaddownload = midas.javauploaddownload || {};

midas.javauploaddownload.promptApplet = function (args) {
    'use strict';

    var html = 'Warning: You have requested a large download (' + args.sizeString + ') that may take a long time to complete.';
    html += ' Would you like to use the Java download applet in case your connection is interrupted?';
    html += '<div style="margin-top: 20px; float: right">';
    html += '<input type="button" style="margin-left: 0;" class="globalButton useLargeDataApplet" value="Yes, use Java download applet"/>';
    html += '<input type="button" style="margin-left: 10px;" class="globalButton useZipStream" value="No, use normal download"/>';
    html += '</div>';

    midas.showDialogWithContent('Large download requested', html, false, {
        width: 480
    });

    $('input.useLargeDataApplet').unbind('click').click(function () {
        window.location = json.global.webroot + '/javauploaddownload/download?folderIds=' + args.folderIds + '&itemIds=' + args.itemIds;
        $('div.MainDialog').dialog('close');
    });

    $('input.useZipStream').unbind('click').click(function () {
        window.location = json.global.webroot + '/download?folders=' + args.folderIds.split(',').join('-') + '&items=' + args.itemIds.split(',').join('-');
        $('div.MainDialog').dialog('close');
    });
};

midas.registerCallback('CALLBACK_CORE_PROMPT_APPLET', 'core', midas.javauploaddownload.promptApplet);
