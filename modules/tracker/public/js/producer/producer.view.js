// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    $('a.editProducerInfo').click(function () {
        midas.loadDialog('editProducer', '/tracker/producer/edit?producerId=' + encodeURIComponent(json.tracker.producer.producer_id));
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
            }, function (_) {
                // Use location.replace so we remove this page from back button history
                window.location.replace(json.global.webroot + '/community/' + encodeURIComponent(json.tracker.producer.community_id) + '#Trackers');
            });
        });
    });

    $('a.producerManageAggregateMetric').click(function () {
        var producerId = $(event.target).data('producer_id');
        midas.loadDialog('aggregateMetricProducerId' + producerId, '/tracker/producer/aggregatemetric?producerId=' + producerId);
        midas.showDialog('Manage Aggregate Metric Specs', false);
    });

    $('input.selectTrend').click(function () {
        var checked = $('input.selectTrend:checked');
        if (checked.length == 2) {
            $('a.visualizeDualAxis').show().unbind('click').click(function () {
                window.location = json.global.webroot + '/tracker/trend/view?trendId=' + encodeURIComponent($(checked[0]).attr('element')) + '&rightTrendId=' + encodeURIComponent($(checked[1]).attr('element'));
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
                window.location = json.global.webroot + '/tracker/trend/view?trendId=' + encodeURIComponent(trends);
            });
            $('span.selectedTrendCount').html(checked.length);
        }
        else {
            $('a.visualizeSelected').unbind('click').hide();
        }


        // Toggle key metric state.
        if (checked.length >= 1) {
            $('span.keyMetricTogglePlural').html(checked.length > 1 ? 's' : '');
            var isKey = $(checked[0]).attr('iskey') === '1';
            var verb = isKey ? 'Unset' : 'Set';
            $('span.keyMetricToggleVerb').html(verb);
            $('a.toggleKeyMetric').show().unbind('click').click(function () {
                $.each(checked, function (idx, checkbox) {
                    var keySpan = $(checkbox).parent().parent().find('.keyMetric');
                    if (isKey) {
                        keySpan.hide();
                        $(checked[0]).attr('iskey', '0');
                    } else {
                        $(checked[0]).attr('iskey', '1');
                        keySpan.show();
                    }
                    $.post(json.global.webroot + '/tracker/trend/setkeymetric', {
                        trendId: $(checkbox).attr('element'),
                        state: !isKey
                    }, function () {
                        verb = isKey ? 'Unset' : 'Set';
                        $('span.keyMetricToggleVerb').html(verb);
                    });
                });
                isKey = !isKey;
            });
        } else {
            $('a.toggleKeyMetric').unbind('click').hide();
        }
    });

    // Set tooltip for key metric icon.
    $('.keyMetric').qtip({
        'content': 'This is a key metric'
    });
});
