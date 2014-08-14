// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.packages = midas.packages || {};

midas.packages.showRelease = function (event, ui) {
    'use strict';
    if (!ui.newContent.hasClass('packagesFetched')) {
        ui.newContent.addClass('packagesFetched');
        ui.newContent.html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif" />');
        var release = ui.newContent.attr('element');
        $.post(json.global.webroot + '/packages/application/getpackages', {
            release: release,
            applicationId: json.applicationId
        }, function (data) {
            midas.packages.renderPackages(release, $.parseJSON(data));
        });
    }
};

/**
 * Render the packages under the appropriate release section using the package widget template
 */
midas.packages.renderPackages = function (release, packages) {
    'use strict';
    var container = $('div.releaseEntry[element="' + release + '"]');
    container.html('');
    var table = $('#packageListTemplate').clone();
    table.attr('id', 'packageList' + release);
    table.show();

    $.each(packages, function (k, val) {
        var html = '<tr>';
        html += '<td>' + midas.packages.transformOs(val.os) + ' ' + val.arch + '</td>';
        html += '<td>' + val.packagetype + '</td>';
        html += '<td><a href="' + json.global.webroot + '/download?items=' + val.item_id + '">' +
            '<img alt="" src="' + json.global.webroot + '/modules/packages/public/images/package.png"/> Download</a> / ';
        html += '<a href="' + json.global.webroot + '/item/' + val.item_id + '">' +
            '<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/page_white_go.png"/> View</a> / ';
        html += '<a href="' + json.global.webroot + '/statistics/item?id=' + val.item_id + '">' +
            '<img alt="" src="' + json.global.webroot + '/modules/statistics/public/images/chart_bar.png"/> Stats</a></td>';
        html += '</tr>';
        table.find('tbody').append(html);
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

midas.packages.successConfig = function (responseText, statusText, xhr, form) {
    'use strict';
    var resp = $.parseJSON(responseText);
    midas.createNotice(resp.message, 3500, resp.status);
    if (resp.status == 'ok') {
        $('input[name="name"]').attr('value', resp.name);
        $('div.applicationName').html(resp.name);
        $('textarea[name="description"]').html(resp.description);
        $('div.applicationDescription').html(resp.description);
    }
};

midas.packages.validateConfig = function (formData, jqForm, options) {
    'use strict';
    $('div.MainDialog').dialog('close');
    return true;
};

$(document).ready(function () {
    'use strict';
    if (json.openRelease) {
        midas.packages.openRelease = json.openRelease;
        $('div.releaseEntry[element="' + json.openRelease + '"]').addClass('packagesFetched');
        midas.packages.renderPackages(json.openRelease, json.latestReleasePackages);
    }

    $('#packageList').accordion({
        clearStyle: true,
        collapsible: true,
        autoHeight: false
    }).bind('accordionchange', midas.packages.showRelease);
    $('#packageList').show();

    $('a.editApplication').click(function () {
        midas.showDialogWithContent('Edit Application', $('#applicationEditDialog').html(), false);
        $('textarea.expanding').autogrow();
        $('form.editApplication').ajaxForm({
            beforeSubmit: midas.packages.validateConfig,
            success: midas.packages.successConfig
        });
    });

    $('a.deleteApplication').click(function () {
        midas.showDialogWithContent('Delete Application', $('#applicationDeleteDialog').html(), false);
        $('input.cancelDelete').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });

});
