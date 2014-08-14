// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    $('a.editProducerInfo').click(function () {
        midas.loadDialog('editProducer', '/tracker/producer/edit?producerId=' + json.tracker.producer.producer_id);
        midas.showDialog('Edit Producer Information', false, {
            width: 495
        });
    });
    $('a.deleteProducer').click(function () {
        var html = 'Are you sure you want to delete this producer and all of its trend data?';
        html += '<div style="float: right; margin-top: 20px;">';
        html += '<img class="deletingProducer" style="display: none;" alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif" />';
        html += '<input type="button" style="margin-left: 10px;" class="globalButton deleteProducerYes" value="Yes" />';
        html += '<input type="button" style="margin-left: 10px;" class="globalButton deleteProducerNo" value="No" />';
        html += '</div>';
        midas.showDialogWithContent('Confirm delete producer', html, false);
        $('input.deleteProducerNo').click(function () {
            $('div.MainDialog').dialog('close');
        });
        $('input.deleteProducerYes').click(function () {
            $(this).attr('disabled', 'disabled');
            $('input.deleteProducerNo').attr('disabled', 'disabled');
            $('img.deletingProducer').show();
            $.post(json.global.webroot + '/tracker/producer/delete', {
                producerId: json.tracker.producer.producer_id
            }, function (retVal) {
                // Use location.replace so we remove this page from back button history
                window.location.replace(json.global.webroot + '/community/' + json.tracker.producer.community_id + '#Trackers');
            });
        });
    });

    $('input.selectTrend').click(function () {
        var checked = $('input.selectTrend:checked');
        if (checked.length == 2) {
            $('a.visualizeDualAxis').show().unbind('click').click(function () {
                window.location = json.global.webroot + '/tracker/trend/view?trendId=' + $(checked[0]).attr('element') + '&rightTrendId=' + $(checked[1]).attr('element');
            });
        }
        else {
            $('a.visualizeDualAxis').unbind('click').hide();
        }
        if (checked.length >= 1) {
            $('a.visualizeSelected').show().unbind('click').click(function () {
                var trends = '';
                $.each(checked, function (idx, checkbox) {
                    trends += $(checkbox).attr('element') + ',';
                });
                window.location = json.global.webroot + '/tracker/trend/view?trendId=' + trends;
            });
            $('span.selectedTrendCount').html(checked.length);
        }
        else {
            $('a.visualizeSelected').unbind('click').hide();
        }
    });
});
