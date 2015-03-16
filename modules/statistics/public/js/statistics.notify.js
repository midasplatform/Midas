// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    midas.registerCallback('CALLBACK_CORE_RESOURCES_SELECTED', 'statistics', function (params) {
        if (params.items.length > 0) {
            var html = '<li>';
            html += '<img alt="" src="' + json.global.webroot + '/modules/statistics/public/images/chart_curve.png"/> ';
            html += '<a href="' + json.global.webroot + '/statistics/item?id=' + params.items.join(',') + '">Aggregate statistics</a></li>';
            html += '</li>';

            params.selectedActionsList.append(html);
        }
    });
});
