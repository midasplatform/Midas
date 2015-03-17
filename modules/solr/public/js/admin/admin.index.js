// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.solr = midas.solr || {};

$(document).ready(function () {
    'use strict';
    $('#rebuildIndexButton').click(function () {
        $('#rebuildProgressMessage').html('Rebuilding item index...');
        $(this).attr('disabled', 'disabled');
        midas.ajaxWithProgress(
            $('#rebuildProgressBar'),
            $('#rebuildProgressMessage'),
            json.global.webroot + '/admin/task', {
                task: 'TASK_CORE_RESET_ITEM_INDEXES'
            },
            function (responseText) {
                $('#rebuildIndexButton').removeAttr('disabled');
                try {
                    var resp = $.parseJSON(responseText);
                    midas.createNotice(resp.message, 4000, resp.status);
                }
                catch (e) {
                    midas.createNotice('Error occurred, please check the logs', 4000, 'error');
                }
                $('#rebuildProgressMessage').html('Index rebuild complete.');
            });
    });
});
