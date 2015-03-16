// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    $('div.feedElement').hover(function () {
        $(this).find('div.feedDelete img').show();
    }, function () {
        $(this).find('div.feedDelete img').hide();
    });
    $('img.feedDeleteLink').click(function () {
        var html = '';
        html += $(this).parents('div.feedElement').find('.feedInfo').html();
        html += '<br/>';
        html += '<br/>';
        html += '<br/>';
        html += '<input style="margin-left:140px;" class="globalButton deleteFeedYes" element="' + $(this).attr('element') + '" type="button" value="' + json.global.Yes + '"/>';
        html += '<input style="margin-left:50px;" class="globalButton deleteFeedNo" type="button" value="' + json.global.No + '"/>';

        midas.showDialogWithContent(json.feed.deleteFeed, html, false);

        $('input.deleteFeedYes').unbind('click').click(function () {
            var elementId = $(this).attr('element');
            $.post(json.global.webroot + '/feed/deleteajax', {
                    feed: $(this).attr('element')
                },
                function (data) {});
            $('div.feedElement[element=' + elementId + ']').remove();
            $("div.MainDialog").dialog('close');
        });
        $('input.deleteFeedNo').unbind('click').click(function () {
            $("div.MainDialog").dialog('close');
        });
    });
});
