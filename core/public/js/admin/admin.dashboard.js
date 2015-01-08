// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.admin = midas.admin || {};
midas.admin.integrityComputed = false;

midas.admin.computeDbIntegrity = function (event, ui) {
    'use strict';
    if (midas.admin.integrityComputed) {
        return;
    }
    midas.admin.integrityComputed = true;
    $.post(json.global.webroot + '/admin/integritycheck', {}, function (resp) {
        $('div.integrityLoading').hide();
        $('div.integrityList').show();
        var json = $.parseJSON(resp);
        $('span.nFolder').html(json.nOrphanedFolders);
        $('span.nItem').html(json.nOrphanedItems);
        $('span.nItemRevision').html(json.nOrphanedRevisions);
        $('span.nBitstream').html(json.nOrphanedBitstreams);
    });
};

$(document).ready(function () {
    'use strict';
    $('.databaseIntegrityWrapper').accordion({
        clearStyle: true,
        collapsible: true,
        active: false,
        autoHeight: false,
        change: midas.admin.computeDbIntegrity
    }).show();
    $('button.removeOrphans').click(function () {
        var html = '<div id="cleanupProgress"></div>';
        html += '<div id="cleanupProgressMessage"></div>';
        midas.showDialogWithContent('Cleaning orphaned resources', html, false, {
            width: 400
        });
        var model = $(this).attr('element');

        midas.ajaxWithProgress($('#cleanupProgress'),
            $('#cleanupProgressMessage'),
            json.global.webroot + '/admin/removeorphans', {
                model: model
            },
            function (text) {
                var retVal = $.parseJSON(text);
                if (retVal === null) {
                    midas.createNotice('Error occurred, check the logs', 2500, 'error');
                }
                else {
                    midas.createNotice(retVal.message, 3000, retVal.status);
                }
                $('div.MainDialog').dialog('close');
                $('td.n' + model).html('0');
            });
    });
});
