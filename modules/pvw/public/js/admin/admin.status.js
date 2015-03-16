// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    $('img.killInstance').qtip({
        content: 'Stop and delete this instance'
    }).click(function () {
        var id = $(this).attr('key');
        $.ajax({
            type: 'DELETE',
            url: json.global.webroot + '/pvw/paraview/instance/' + encodeURIComponent(id),
            dataType: 'json',
            data: {},
            success: function (resp) {
                midas.createNotice(resp.message, 3000, resp.status);
                if (resp.status == 'ok') {
                    $('table.instances tr[key=' + id + ']').remove();
                }
            }
        });
    });
});
