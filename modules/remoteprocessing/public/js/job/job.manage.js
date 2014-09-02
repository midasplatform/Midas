// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var selectedJob = false;

$(document).ready(function () {
    'use strict';
    colorLines();
    $('.midasTree tr').click(function () {
        selectedJob = $(this).attr('element');
        createActionMenu($(this));
        colorLines();
    });
});

function createActionMenu(obj) {
    'use strict';
    $('.viewAction').show();
    $('#viewJob').click(function () {
        window.location.replace(json.global.webroot + '/remoteprocessing/job/view/?jobId=' + selectedJob);
    });
}

function colorLines(checkHidden) {
    'use strict';
    var grey = false;
    $('.midasTree tr').each(function (index) {
        $(this).css("border", "none");
        if (index === 0) {
            return;
        }
        if (selectedJob == $(this).attr('element')) {
            $(this).css("background-color", "#C0D1FE");
            $(this).css("border", "1px solid grey");
            grey = !grey;
            $(this).unbind('mouseenter mouseleave');
        }
        else {
            if (grey) {
                $(this).css('background-color', '#f9f9f9');
                $(this).hover(function () {
                    $(this).css('background-color', '#F3F1EC');
                }, function () {
                    $(this).css('background-color', '#f9f9f9');
                });
                grey = false;
            }
            else {
                $(this).css('background-color', 'white');
                $(this).hover(function () {
                    $(this).css('background-color', '#F3F1EC');
                }, function () {
                    $(this).css('background-color', 'white');
                });
                grey = true;
            }
        }
    });
}
