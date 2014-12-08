// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.packages = midas.packages || {};

midas.packages.showPlatform = function (event, ui) {
    'use strict';
    if (!ui.newContent.hasClass('packagesFetched')) {
        ui.newContent.addClass('packagesFetched');
        ui.newContent.html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif" />');
        var os = ui.newContent.attr('os');
        var arch = ui.newContent.attr('arch');
        $.post(json.global.webroot + '/packages/package/latest', {
            os: os,
            arch: arch,
            applicationId: json.applicationId
        }, function (data) {
            midas.packages.renderPackages(os, arch, $.parseJSON(data));
        });
    }
};

/**
 * Render the packages under the appropriate section using the package widget template
 */
midas.packages.renderPackages = function (os, arch, packages) {
    'use strict';
    var container = $('div.platformContainer[os="' + os + '"][arch="' + arch + '"]');
    container.html('<div class="platformContainerTitle">Available package types:</div>');
    var table = $('#packageListTemplate').clone();
    table.attr('id', 'packageList' + os + arch);
    table.show();

    var index = 0;
    $.each(packages, function (k, val) {
        var trClass = index % 2 ? 'odd' : 'even';
        var html = '<tr class="' + trClass + '">';
        html += '<td><a href="' + json.global.webroot + '/download?items=' + encodeURIComponent(val.item_id) + '">' +
            '<img alt="" src="' + json.global.webroot + '/modules/packages/public/images/package_go.png" /> ' +
            'Download ' + val.packagetype + '</a> - ';
        html += val.size_formatted + ' (' + val.checkoutdate + ')</td>';
        html += '</tr>';
        table.find('tbody').append(html);
        index++;
    });
    table.appendTo(container);
};

/**
 * Call with a simple os string; returns the html that should be rendered for that os
 */
midas.packages.transformOs = function (os) {
    'use strict';
    // todo transform os
    return os;
};

midas.packages.transformArch = function (arch) {
    'use strict';
    // todo transform arch
    return arch;
};

$(document).ready(function () {
    'use strict';
    $('#platformList').find('h3').each(function () {
        var el = $(this);
        el.find('a').html(midas.packages.transformOs(el.attr('os')) + ' ' +
            midas.packages.transformArch(el.attr('arch')));
    });
    // TODO platform detection and figure out which tab to open by default
    $('#platformList').accordion({
        clearStyle: true,
        collapsible: true,
        autoHeight: false,
        active: false
    }).bind('accordionchange', midas.packages.showPlatform);
    $('#platformList').show();
});
