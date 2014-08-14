// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.batchmake = midas.batchmake || {};

midas.batchmake.global_config_error_msg = "The overall configuration is in error";
midas.batchmake.global_config_correct_msg = "The overall configuration is correct";

midas.batchmake.info_class = 'info';
midas.batchmake.error_class = 'error';

midas.batchmake.application_entry = 'Application';
midas.batchmake.php_entry = 'PHP';
midas.batchmake.application_div = 'apps_config_div';
midas.batchmake.php_div = 'php_config_div';

midas.batchmake.checkConfig = function (obj) {
    'use strict';
    $('#testLoading').show();
    $('#testOk').hide();
    $('#testNok').hide();
    $('#testError').html('');

    var tmp_dir_val = $(document).find('#tmp_dir').val();
    var bin_dir_val = $(document).find('#bin_dir').val();
    var script_dir_val = $(document).find('#script_dir').val();
    var app_dir_val = $(document).find('#app_dir').val();
    var data_dir_val = $(document).find('#data_dir').val();
    var condor_bin_dir_val = $(document).find('#condor_bin_dir').val();

    ajaxWebApi.ajax({
        method: 'midas.batchmake.testconfig',
        args: 'tmp_dir=' + tmp_dir_val +
            '&bin_dir=' + bin_dir_val +
            '&script_dir=' + script_dir_val +
            '&app_dir=' + app_dir_val +
            '&data_dir=' + data_dir_val +
            '&condor_bin_dir=' + condor_bin_dir_val,
        log: $('#testError'),
        success: function (retVal) {
            midas.batchmake.handleValidationResponse(retVal);
        },
        error: function (retVal) {
            alert(retVal.responseText);
        },
        complete: function () {
            $('#testLoading').hide();
        }
    });
};

midas.batchmake.handleValidationResponse = function (retVal) {
    'use strict';
    var testConfig = retVal.data;
    // testConfig should be
    // [0] = 1 if the global config is correct, 0 otherwise
    // [1] = an array of individual config properties and statuses

    var global_config_correct = testConfig[0];
    var config_properties = testConfig[1];

    // handle global config value
    if (global_config_correct == true) {
        $(document).find('#testOk').show();
        $(document).find('#testError').html(midas.batchmake.global_config_correct_msg).removeClass().addClass(midas.batchmake.info_class);
    }
    else {
        $(document).find('#testNok').show();
        $(document).find('#testError').html(midas.batchmake.global_config_error_msg).removeClass().addClass(midas.batchmake.error_class);
    }

    $(document).find('div #' + midas.batchmake.application_div).children().remove();
    $(document).find('div #' + midas.batchmake.php_div).children().remove();

    // now look at all of the individual config values, print out statuses
    for (var configVarInd in config_properties) {
        var property = config_properties[configVarInd]['property'];
        var status = config_properties[configVarInd]['status'];
        var type = config_properties[configVarInd]['type'];
        var spanString;
        if (property.search(midas.batchmake.application_entry) > -1) {
            spanString = '<div class="' + type + '">' + property + ' ' + status + '</div>';
            $(document).find('div #' + midas.batchmake.application_div).append(spanString);
        }
        else if (property.search(midas.batchmake.php_entry) > -1) {
            spanString = '<div class="' + type + '">' + property + ' ' + status + '</div>';
            $(document).find('div #' + midas.batchmake.php_div).append(spanString);
        }
        else {
            var configVarStatusSpan_selector = '#' + property + 'Status';
            $(document).find(configVarStatusSpan_selector).html(status).removeClass().addClass(type);
        }
    }
};

midas.batchmake.validateConfig = function (formData, jqForm, options) {};

midas.batchmake.successConfig = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if (jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

$(document).ready(function () {
    'use strict';
    $('#configForm').ajaxForm({
        beforeSubmit: midas.batchmake.validateConfig,
        success: midas.batchmake.successConfig
    });

    $('#configForm').find('input').each(function () {
        // add a span after each input for displaying any errors related to that input
        var inputID = $(this).attr("id")
        $(this).after('<span id="' + inputID + 'Status' + '"></span>');
    });

    $('#configForm').focusout(function () {
        midas.batchmake.checkConfig($(this));
    });

    midas.batchmake.checkConfig($(this));
});
