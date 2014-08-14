// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.dicomserver = midas.dicomserver || {};

midas.dicomserver.start = function (email, apikey) {
    'use strict';
    var email_val = typeof email !== 'undefined' ? email : '';
    var apikey_val = typeof email !== 'undefined' ? apikey : '';
    var dcm2xml_val = $(document).find('#dcm2xml').val();
    var storescp_val = $(document).find('#storescp').val();
    var port_val = $(document).find('#storescp_port').val();
    var timeout_val = $(document).find('#storescp_study_timeout').val();
    var incoming_dir_val = $(document).find('#receptiondir').val();
    var dest_folder_val = $(document).find('#pydas_dest_folder').val();
    var dcmqrscp_val = $(document).find('#dcmqrscp').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.start',
        args: 'email=' + email_val +
            'apikey=' + apikey_val +
            'dcm2xml_cmd=' + dcm2xml_val +
            '&storescp_cmd=' + storescp_val +
            '&storescp_port=' + port_val +
            '&storescp_timeout=' + timeout_val +
            '&incoming_dir=' + incoming_dir_val +
            '&dest_folder=' + dest_folder_val +
            '&dcmqrscp_cmd=' + dcmqrscp_val,
        log: $('<p></p>'),
        success: function (retVal) {
            midas.createNotice(retVal.data.message, 4000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            midas.createNotice("Failed to start storescp or dcmqrscp!", 3000, 'error');
            $('textarea#apicall_failure_reason').html(XMLHttpRequest.message);
            $('div#apicall_failure').show();
            $('div#hideError').show();
        },
        complete: function () {
            midas.dicomserver.checkStatus();
        }
    });
};

midas.dicomserver.manualstart = function (email, apikey) {
    'use strict';
    var email_val = typeof email !== 'undefined' ? email : '';
    var apikey_val = typeof email !== 'undefined' ? apikey : '';
    var dcm2xml_val = $(document).find('#dcm2xml').val();
    var storescp_val = $(document).find('#storescp').val();
    var port_val = $(document).find('#storescp_port').val();
    var timeout_val = $(document).find('#storescp_study_timeout').val();
    var incoming_dir_val = $(document).find('#receptiondir').val();
    var dest_folder_val = $(document).find('#pydas_dest_folder').val();
    var dcmqrscp_val = $(document).find('#dcmqrscp').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.start',
        args: 'email=' + email_val +
            'apikey=' + apikey_val +
            'dcm2xml_cmd=' + dcm2xml_val +
            '&storescp_cmd=' + storescp_val +
            '&storescp_port=' + port_val +
            '&storescp_timeout=' + timeout_val +
            '&incoming_dir=' + incoming_dir_val +
            '&dest_folder=' + dest_folder_val +
            '&dcmqrscp_cmd=' + dcmqrscp_val +
            '&get_command=' + '',
        log: $('<p></p>'),
        success: function (retVal) {
            $('span#manual_start').html(retVal.data);
        }
    });
};

midas.dicomserver.stop = function () {
    'use strict';
    var storescp_val = $(document).find('#storescp').val();
    var dcmqrscp_val = $(document).find('#dcmqrscp').val();
    var incoming_dir_val = $(document).find('#receptiondir').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.stop',
        args: 'storescp_cmd=' + storescp_val +
            '&dcmqrscp_cmd=' + dcmqrscp_val +
            '&incoming_dir=' + incoming_dir_val,
        log: $('<p></p>'),
        success: function (retVal) {
            midas.createNotice(retVal.data.message, 4000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            midas.createNotice("Execution of storescp stop script failed!", 3000, 'error');
            $('textarea#apicall_failure_reason').html(XMLHttpRequest.message);
            $('div#apicall_failure').show();
            $('div#hideError').show();
        },
        complete: function () {
            midas.dicomserver.checkStatus();
        }
    });
};

midas.dicomserver.manualstop = function () {
    'use strict';
    var storescp_val = $(document).find('#storescp').val();
    var dcmqrscp_val = $(document).find('#dcmqrscp').val();
    var incoming_dir_val = $(document).find('#receptiondir').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.stop',
        args: 'storescp_cmd=' + storescp_val +
            '&dcmqrscp_cmd=' + dcmqrscp_val +
            '&incoming_dir=' + incoming_dir_val +
            '&get_command=' + '',
        log: $('<p></p>'),
        success: function (retVal) {
            $('span#manual_stop').html(retVal.data);
        }
    });
};

midas.dicomserver.checkStatus = function () {
    'use strict';
    var storescp_val = $(document).find('#storescp').val();
    var dcmqrscp_val = $(document).find('#dcmqrscp').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.status',
        args: 'storescp_cmd=' + storescp_val +
            '&dcmqrscp_cmd=' + dcmqrscp_val,
        log: $('<p></p>'),
        success: function (retVal) {
            if (retVal.data.status === 3 || retVal.data.status === "3") {
                $('span#not_running_status').hide();
                $('span#only_storescp_running_status').hide();
                $('span#only_dcmqrscp_running_status').hide();
                $('span#running_status').show();
                $('span#span_start_server_user').html(retVal.data.user_email);
                $('div#start_server_user').show();
            }
            else if (retVal.data.status === 2 || retVal.data.status === "2") {
                $('span#not_running_status').hide();
                $('span#only_storescp_running_status').hide();
                $('span#only_dcmqrscp_running_status').show();
                $('span#running_status').hide();
                $('div#start_server_user').hide();
            }
            else if (retVal.data.status === 1 || retVal.data.status === "1") {
                $('span#not_running_status').hide();
                $('span#only_storescp_running_status').show();
                $('span#only_dcmqrscp_running_status').hide();
                $('span#span_start_server_user').html(retVal.data.user_email);
                $('div#start_server_user').show();
            }
            else if (retVal.data.status === 0 || retVal.data.status === "0") {
                $('span#not_running_status').show();
                $('span#only_storescp_running_status').hide();
                $('span#only_dcmqrscp_running_status').hide();
                $('span#running_status').hide();
                $('div#start_server_user').hide();
            }
            else { // this module is not supported
                $('span#only_storescp_running_status').hide();
                $('span#only_dcmqrscp_running_status').hide();
                $('span#not_running_status').hide();
                $('span#not_supported_status').show();
                $('div#startServer').hide();
                $('div#stopServer').hide();
                $('div#start_server_user').hide();
                $('div#manualCommandsWrapper').hide();
            }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            midas.createNotice(XMLHttpRequest.message, 3000, 'error');
        }
    });
};

midas.dicomserver.validateConfig = function (formData, jqForm, options) {};

midas.dicomserver.successConfig = function (responseText, statusText, xhr, form) {
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
        window.location.reload();
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

$(document).ready(function () {
    'use strict';
    $("div#receptiondir").qtip({
        content: 'The file-system location of the DICOM server work directory. (required)',
        show: 'mouseover',
        hide: 'mouseout',
        position: {
            target: 'mouse',
            my: 'bottom left',
            viewport: $(window), // Keep the qtip on-screen at all times
            effect: true // Disable positioning animation
        }
    });

    $("div#peer_aes").qtip({
        content: 'Please follow the above instructions to define your Peer AE list and it cannot be empty. (required)',
        show: 'mouseover',
        hide: 'mouseout',
        position: {
            target: 'mouse',
            my: 'bottom left',
            viewport: $(window), // Keep the qtip on-screen at all times
            effect: true // Disable positioning animation
        }
    });

    $('#configForm').ajaxForm({
        beforeSubmit: midas.dicomserver.validateConfig,
        success: midas.dicomserver.successConfig
    });

    midas.dicomserver.checkStatus();

    $('div#startServer').click(function () {
        var html = '';
        html += 'Do you want to use current logged-in user to start DICOM server?';
        html += '<br/>';
        html += '<br/>';
        html += '<input style="margin-left:140px;" class="globalButton startServerYes" type="button" value="' + json.global.Yes + '"/>';
        html += '<input style="margin-left:50px;" class="globalButton startServerNo" type="button" value="' + json.global.No + '"/>';
        midas.showDialogWithContent('Start DICOM server', html, false);

        $('input.startServerYes').unbind('click').click(function () {
            $("div.MainDialog").dialog('close');
            midas.dicomserver.start();
        });
        $('input.startServerNo').unbind('click').click(function () {
            $("div.MainDialog").dialog('close');
        });
    });

    $('div#stopServer').click(function () {
        var html = '';
        html += 'Do you really want to stop DICOM server?';
        html += '<br/>';
        html += '<br/>';
        html += '<input style="margin-left:140px;" class="globalButton stopServerYes" type="button" value="' + json.global.Yes + '"/>';
        html += '<input style="margin-left:50px;" class="globalButton stopServerNo" type="button" value="' + json.global.No + '"/>';
        midas.showDialogWithContent('Stop DICOM server', html, false);

        $('input.stopServerYes').unbind('click').click(function () {
            $("div.MainDialog").dialog('close');
            midas.dicomserver.stop();
        });
        $('input.stopServerNo').unbind('click').click(function () {
            $("div.MainDialog").dialog('close');
        });
    });

    $('div#hideError').click(function () {
        $('div#hideError').hide();
        $('div#apicall_failure').hide();
    });

    $('.manualCommandsWrapper').accordion({
        clearStyle: true,
        collapsible: true,
        active: false,
        autoHeight: false,
        change: function () {
            var dcm2xml_val = $(document).find('#dcm2xml').val();
            var storescp_val = $(document).find('#storescp').val();
            var dcmqrscp_val = $(document).find('#dcmqrscp').val();
            var dcmqridx_val = $(document).find('#dcmqridx').val();
            var incoming_dir_val = $(document).find('#receptiondir').val();
            $('span#dcm2xml_command').html(dcm2xml_val);
            $('span#storescp_command').html(storescp_val);
            $('span#dcmqrscp_command').html(dcmqrscp_val);
            $('span#dcmqridx_command').html(dcmqridx_val);
            $('span#reception_dir').html(incoming_dir_val);
            midas.dicomserver.manualstart();
            midas.dicomserver.manualstop();
        }
    }).show();

});
