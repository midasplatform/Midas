// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global itemSelectionCallback */
/* global json */

var midas = midas || {};
midas.tracker = midas.tracker || {};

itemSelectionCallback = function (name, id) {
    'use strict';
    var html = '<a href="' + json.global.webroot + '/item/' + encodeURIComponent(id) + '">' + name + '</a>';
    $('span.' + midas.tracker.whichSelect + 'DatasetContent').html(html);
    $('input[name=' + midas.tracker.whichSelect + 'ItemId]').val(id);
};

midas.tracker.removeItem = function (which) {
    'use strict';
    $('span.' + which + 'DatasetContent').html('<span class="noItem">none</span>');
    $('input[name=' + which + 'ItemId]').val('');
};

midas.tracker.validateConfig = function () {
    'use strict';
    return true;
};

midas.tracker.successConfig = function (text) {
    'use strict';
    var resp = $.parseJSON(text);
    midas.createNotice(resp.message, 2500, resp.status);
};

$(window).load(function () {
    'use strict';
    $('form.editTrendForm').ajaxForm({
        beforeSubmit: midas.tracker.validateConfig,
        success: midas.tracker.successConfig
    });
    $('#selectConfigItem').click(function () {
        midas.tracker.whichSelect = 'config';
        midas.loadDialog('selectConfigItem', '/browse/selectitem');
        midas.showDialog('Select config item for this trend');
    });
    $('#selectTestDatasetItem').click(function () {
        midas.tracker.whichSelect = 'test';
        midas.loadDialog('selectTestItem', '/browse/selectitem');
        midas.showDialog('Select test dataset for this trend');
    });
    $('#selectTruthDatasetItem').click(function () {
        midas.tracker.whichSelect = 'truth';
        midas.loadDialog('selectTruthItem', '/browse/selectitem');
        midas.showDialog('Select ground truth dataset for this trend');
    });
    $('.removeItem').click(function () {
        midas.tracker.removeItem($(this).attr('element'));
    });
});
