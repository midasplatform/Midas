// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.assetstore = midas.assetstore || {};

$('#moveBitstreamsConfirm').click(function () {
    'use strict';
    $(this).attr('disabled', 'disabled');
    var params = {
        srcAssetstoreId: $('#srcAssetstoreId').val(),
        dstAssetstoreId: $('#dstAssetstoreId').val()
    };
    midas.ajaxWithProgress(
        $('#moveBitstreamsProgressBar'),
        $('#moveBitstreamsProgressMessage'),
        json.global.webroot + '/assetstore/movecontents',
        params,
        function (text) {
            $('div.MainDialog').dialog('close');
            $('#moveBitstreamsConfirm').removeAttr('disabled');
            var resp = $.parseJSON(text);
            midas.createNotice(resp.message, 3000, resp.status);
        });
});
