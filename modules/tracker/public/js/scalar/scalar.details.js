// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$('#deleteScalar').click(function () {
    'use strict';
    var html = "Are you sure you want to delete this scalar value?";
    html += '<div style="float: right; margin-top: 15px;">';
    html += '<input type="button" style="margin-left: 0;" class="globalButton" id="deleteScalarYes" value="Yes" />';
    html += '<input type="button" style="margin-left: 10px;" class="globalButton" id="deleteScalarNo" value="Cancel" />';
    html += '<input type="hidden" class="scalarIdChild" value="' + $('input.scalarId').val() + '" />';
    html += '</div>';
    midas.showDialogWithContent('Confirm delete scalar', html, false);
    $('#deleteScalarYes').click(function () {
        $.post(json.global.webroot + '/tracker/scalar/delete', {
                scalarId: $('input.scalarIdChild').val()
            },
            function (data) {
                var jsonResponse = $.parseJSON(data);
                if (jsonResponse == null) {
                    midas.createNotice('Error', 2000, 'error');
                }
                else if (jsonResponse.status == 'ok') {
                    window.location.reload();
                }
                else {
                    midas.createNotice(jsonResponse.message, 3000, jsonResponse.status);
                }
            }
        );
    });
    $('#deleteScalarNo').click(function () {
        $('div.MainDialog').dialog('close');
    });
});

$(document).ready(function () {
    'use strict';
    $.fn.qtip.zindex = 16000; // must show qtips on top of the dialog
    $.each($('a.resultItemLink'), function (idx, link) {
        if ($(link).attr('thumbnail')) {
            $(link).qtip({
                content: '<img alt="" src="' + json.global.webroot + '/item/thumbnail?itemId=' + $(link).attr('element') + '" />'
            });
        }
    });
});
