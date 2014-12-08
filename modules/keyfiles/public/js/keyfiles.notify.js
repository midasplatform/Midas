// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    midas.registerCallback('CALLBACK_CORE_RESOURCES_SELECTED', 'keyfiles', function (params) {
        var html = '<li>';
        html += '<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/key.png"/> ';
        html += '<a href="' + json.global.webroot + '/keyfiles/download/batch?items=' + encodeURIComponent(params.items.join('-')) + '&folders=' + encodeURIComponent(params.folders.join('-')) + '">Download key files</a></li>';
        html += '</li>';

        params.selectedActionsList.append(html);
    });
});
