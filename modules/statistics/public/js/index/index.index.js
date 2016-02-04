// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var tabs;

$(document).ready(function () {
    'use strict';
    tabs = $('#tabsGeneric').tabs({
        select: function (event, ui) {}
    });
    $('#tabsGeneric').show();
    $('img.tabsLoading').hide();
    var plotAssetStores = [];

    $.each(json.stats.assetstores, function (i, val) {
        $('#chartAssetstore').append('<div style="height:200px;width:300px";  id="charAssetstore' + i + '"></div');
        var data = val[1];
        $.each(data, function (i, val) {
            data[i][1] = parseFloat(data[i][1]);
        });
        var plot = $.jqplot('charAssetstore' + i, [data], {
            title: val[0],
            seriesDefaults: {
                // Make this a pie chart.
                renderer: jQuery.jqplot.PieRenderer,
                rendererOptions: {
                    // Put data labels on the pie slices.
                    // By default, labels show the percentage of the slice.
                    showDataLabels: true
                }
            },
            legend: {
                show: true,
                location: 'e'
            }
        });
        plotAssetStores.push(plot);
    });

    $('#tabsGeneric').bind('tabsshow', function (event, ui) {
        $.each(plotAssetStores, function (i, val) {
            if (val._drawCount === 0) {
                val.replot();
            }
        });
    });
});
