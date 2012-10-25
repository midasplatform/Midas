var midas = midas || {};
midas.dicomuploader = midas.dicomuploader || {};

midas.dicomuploader.start = function (email, apikey) {
    'use strict';
    var email_val  = typeof email !== 'undefined' ? email : '';
    var apikey_val  = typeof email !== 'undefined' ? apikey : '';
    var dcm2xml_val  = $(document).find('#dcm2xml').val();
    var storescp_val  = $(document).find('#storescp').val();
    var port_val  = $(document).find('#storescp_port').val();
    var timeout_val  = $(document).find('#storescp_study_timeout').val();
    var incoming_dir_val  = $(document).find('#receptiondir').val();
    var dest_folder_val  = $(document).find('#pydas_dest_folder').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomuploader.start',
        args: 'email=' + email_val +
              'apikey=' + apikey_val +
              'dcm2xml_cmd=' + dcm2xml_val +
              '&storescp_cmd=' + storescp_val +
              '&storescp_port=' + port_val +
              '&storescp_timeout=' + timeout_val +
              '&incoming_dir' + incoming_dir_val +
              '&dest_folder=' + dest_folder_val,
        log: $('<p></p>'),
        success: function (retVal) {
            midas.createNotice(retVal.data.message, 4000);
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 4000, 'error');
        },
        complete: function() {
            midas.dicomuploader.checkStatus();
        }
    });
};

midas.dicomuploader.stop = function () {
    'use strict';
    var storescp_val  = $(document).find('#storescp').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomuploader.stop',
        args: 'storescp_cmd=' + storescp_val,
        log: $('<p></p>'),
        success: function (retVal) {
            midas.createNotice(retVal.data.message, 4000);
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 4000, 'error');
        },
        complete: function() {
            midas.dicomuploader.checkStatus();
        }
    });
};

midas.dicomuploader.checkStatus = function () {
    'use strict';
    var storescp_val  = $(document).find('#storescp').val();
    ajaxWebApi.ajax({
        method: 'midas.dicomuploader.status',
        args: 'storescp_cmd=' + storescp_val,
        log: $('<p></p>'),
        success: function (retVal) {
           if(retVal.data.status === 'running') {
             $('span#not_running_status').hide();
             $('span#running_status').show();
             $('span#span_start_uploader_user').html(retVal.data.user_email);
             $('div#start_uploader_user').show();
             }
           else {
             $('span#running_status').hide();
             $('span#not_running_status').show();
             $('div#start_uploader_user').hide();
             }
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 4000, 'error');
        }
    });
};

midas.dicomuploader.validateConfig = function (formData, jqForm, options) {
}

midas.dicomuploader.successConfig = function (responseText, statusText, xhr, form) {
    try {
        var jsonResponse = jQuery.parseJSON(responseText);
    } catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if(jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if(jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}


$(document).ready(function() {
    $("div#tmpdir").qtip({
          content: 'The file-system location of the DICOM uploader work directory. (required)',
          show: 'mouseover',
          hide: 'mouseout',
          position: {
                target: 'mouse',
                my: 'bottom left',
                viewport: $(window), // Keep the qtip on-screen at all times
                effect: true // Disable positioning animation
             }
         })

    $('#configForm').ajaxForm({
        beforeSubmit: midas.dicomuploader.validateConfig,
        success: midas.dicomuploader.successConfig
    });

    midas.dicomuploader.checkStatus();

    $('div#startUploader').click(function() {
        var html = '';
        html += 'Do you want to use current logged-in user to start DICOM uploader?';
        html += '<br/>';
        html += '<br/>';
        html += '<input style="margin-left:140px;" class="globalButton startUploaderYes" type="button" value="'+json.global.Yes+'"/>';
        html += '<input style="margin-left:50px;" class="globalButton startUploaderNo" type="button" value="'+json.global.No+'"/>';
        midas.showDialogWithContent('Start DICOM uploader', html, false);

        $('input.startUploaderYes').unbind('click').click(function () {
            $( "div.MainDialog" ).dialog('close');
            midas.dicomuploader.start();
        });
        $('input.startUploaderNo').unbind('click').click(function () {
            $( "div.MainDialog" ).dialog('close');
        });
    })

    $('div#stopUploader').click(function() {
        var html = '';
        html += 'Do you really want to stop DICOM uploader?';
        html += '<br/>';
        html += '<br/>';
        html += '<input style="margin-left:140px;" class="globalButton stopUploaderYes" type="button" value="'+json.global.Yes+'"/>';
        html += '<input style="margin-left:50px;" class="globalButton stopUploaderNo" type="button" value="'+json.global.No+'"/>';
        midas.showDialogWithContent('Stop DICOM uploader', html, false);

        $('input.stopUploaderYes').unbind('click').click(function () {
            $( "div.MainDialog" ).dialog('close');
            midas.dicomuploader.stop();
        });
        $('input.stopUploaderNo').unbind('click').click(function () {
            $( "div.MainDialog" ).dialog('close');
        });
    });

});
