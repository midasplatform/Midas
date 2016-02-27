// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};

$(document).ready(function () {
    'use strict';

    $('div.producerManageAggregateMetric').click(function () {
        var producerId = $(event.target).data('producer_id');
        midas.loadDialog('aggregateMetricProducerId' + producerId, '/tracker/producer/aggregatemetric?producerId=' + producerId);
        midas.showDialog('Manage Aggregate Metric Specs', false);
    });
});
