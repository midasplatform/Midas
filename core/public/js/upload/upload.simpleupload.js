// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global fileDialogComplete */
/* global fileQueued */
/* global fileQueueError */
/* global json */
/* global loadFailed */
/* global preLoad */
/* global queueComplete */
/* global uploadComplete */
/* global uploadError */
/* global uploadProgress */
/* global uploadSuccess */

var midas = midas || {};
midas.upload = midas.upload || {};
midas.upload.simpleupload = {};

// We use shockwave flash uploader for IE (no multi-file upload support)
midas.upload.simpleupload.initSwfupload = function () {
    'use strict';
    // Callback hook for the flash uploader
    midas.upload.simpleupload.uploadPreStart = function (file) {
        midas.upload.simpleupload.swfu.setPostParams({
            'sid': $('.sessionId').val(),
            'parent': $('#destinationId').val(),
            'license': $('select[name=licenseSelect]').val()
        });
    };
    var settings = {
        flash_url: json.global.coreWebroot + "/public/js/swfupload/swfupload_fp10/swfupload.swf",
        flash9_url: json.global.coreWebroot + "/public/js/swfupload/swfupload_fp9/swfupload_fp9.swf",
        upload_url: json.global.webroot + "/upload/saveuploaded",
        post_params: {
            'sid': $('.sessionId').val(),
            'parent': $('#destinationId').val(),
            'license': $('select[name=licenseSelect]').val()
        },
        file_size_limit: $('.maxSizeFile').val() + " MB",
        file_types: "*.*",
        file_types_description: "All Files",
        file_upload_limit: 100,
        file_queue_limit: 0,
        custom_settings: {
            progressTarget: "fsUploadProgress",
            cancelButtonId: "btnCancel",
            pageObj: midas.upload.simpleupload
        },
        debug: false,

        // Button settings
        button_image_url: json.global.coreWebroot + "/public/js/swfupload/images/Button_65x29.png",
        button_width: "65",
        button_height: "20",
        button_placeholder_id: "spanButtonPlaceHolder",
        button_text: '<span class="theFont">' + $('.buttonBrowse').val() + '</span>',
        button_text_style: ".theFont { font-size: 12; }",
        button_text_left_padding: 5,
        button_text_top_padding: 0,

        // The event handler functions are defined in handlers.js
        swfupload_preload_handler: preLoad,
        swfupload_load_failed_handler: loadFailed,
        file_queued_handler: fileQueued,
        file_queue_error_handler: fileQueueError,
        file_dialog_complete_handler: fileDialogComplete,
        upload_start_handler: midas.upload.simpleupload.uploadPreStart,
        upload_progress_handler: uploadProgress,
        upload_error_handler: uploadError,
        upload_success_handler: uploadSuccess,
        upload_complete_handler: uploadComplete,
        queue_complete_handler: queueComplete // Queue plugin event
    };

    $('#swfuploadContent').show();
    midas.upload.simpleupload.swfu = new SWFUpload(settings);

    $('#startUploadLink').click(function () {
        if ($('#destinationId').val() == undefined || $('#destinationId').val().length == 0) {
            midas.createNotice("Please select where you want to upload your files.", 4000, 'warning');
            return false;
        }
        midas.upload.simpleupload.swfu.startUpload();
    });
};

$('img#uploadAFile').show();
$('img#uploadAFileLoading').hide();

midas.upload.simpleupload.initHtml5FileUpload = function () {
    'use strict';
    $('.progress-current').progressbar({
        value: 0
    });
    $('.progress-overall').progressbar({
        value: 0
    });

    var files = [],
        totalSize = 0,
        startByte = 0,
        currentIndex = 0,
        overallProgress = 0,
        lastProgress = 0,
        resumeUploadId = null,
        xhr = null;

    var _eatEvent = function (e) {
        e.stopPropagation();
        e.preventDefault();
    };

    /**
     * Helper function that begins the upload of the next file. This calls
     * _streamFileContents internally.
     */
    var _uploadNextFile = function () {
        if (currentIndex >= files.length) {
            // All files have finished
            $('.drop-zone').show();
            window.location.href = json.global.webroot + '/folder/' + $('.destinationId').val();
            return;
        }
        var file = files[currentIndex];
        // Initialize this upload
        $.ajax({
            dataType: 'json',
            type: 'GET',
            url: json.global.webroot + '/rest/system/uploadtoken?useSession=true' +
                '&filename=' + file.name + '&folderid=' + $('#destinationId').val()
        }).success(function (resp) {
            if (file.size > 0) {
                startByte = 0;
                lastProgress = 0;
                _streamFileContents(resp.data.token);
            }
            else {
                // Empty file, so we are done
                currentIndex += 1;
                _uploadNextFile();
            }
        }).error(function (resp) {
            midas.createNotice('Error: ' + resp.error.msg, 4000, 'error');
        });
    };

    /**
     * Handle upload progress events from the XHR
     */
    var _uploadProgress = function (e) {
        if (!e.lengthComputable) {
            return;
        }

        if (startByte + e.loaded < lastProgress) {
            // This would happen from an error case, so we don't want
            // to render it as a progress event.
            return;
        }

        lastProgress = startByte + e.loaded;
        var file = files[currentIndex];

        $('.progress-current').progressbar('option', 'value',
            Math.ceil(100 * lastProgress / file.size));
        $('.progress-overall').progressbar('option', 'value',
            Math.ceil(100 * (overallProgress + e.loaded) / totalSize));
        $('.current-progress-message').html(
            'File ' + (currentIndex + 1) + ' of ' +
            files.length + ' - <b>' + file.name + '</b>: ' +
            midas.formatBytes(lastProgress) + ' / ' +
            midas.formatBytes(file.size));
        $('.overall-progress-message').html('Overall progress: ' +
            midas.formatBytes(overallProgress + e.loaded) + ' / ' +
            midas.formatBytes(totalSize));
    };

    /**
     * Upload errors will call this function to enable resume mode.
     */
    var _enableResumeMode = function () {
        $('div.uploadValidationError b').html('The connection to the server was ' +
            'interrupted, press the Resume Upload button to resume.');
        $('div.uploadValidationError').show();
        $('#startUploadLink').val('Resume Upload').removeClass('disabled');
    };

    /**
     * Stream upload the contents of the current file
     */
    var _streamFileContents = function (uploadToken) {
        var file = files[currentIndex];
        var blob = file.slice(startByte);
        var url = json.global.webroot + '/api/rest?method=midas.upload.perform&uploadtoken=' +
            uploadToken + '&length=' + file.size + '&filename=' + (file.name || file.fileName);

        resumeUploadId = uploadToken;

        xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', _uploadProgress);
        xhr.addEventListener('load', function () {
            if (this.status == 200 || this.status == 201) {
                overallProgress += blob.size;
                currentIndex += 1;
                resumeUploadId = null;
                _uploadNextFile();
            }
            else {
                _enableResumeMode();
            }
        });
        xhr.addEventListener('error', function (e) {
            _enableResumeMode();
        });

        xhr.open('PUT', url, true);
        xhr.setRequestHeader('Content-Type', 'application/octet-stream');
        xhr.setRequestHeader('X-File-Name', file.name || file.fileName);
        xhr.setRequestHeader('X-File-Size', blob.size);
        xhr.setRequestHeader('X-File-Type', file.type);

        xhr.send(blob);
    };

    /**
     * Called when the file selection changes.
     */
    var filesChanged = function () {
        $('div.uploadValidationError').hide();
        var retVal = midas.doCallback('CALLBACK_CORE_VALIDATE_UPLOAD', {
            files: files
        });
        var ok = true;
        $.each(retVal, function (module, resp) {
            if (resp.status === false) {
                $('div.uploadValidationError b').html(resp.message);
                $('div.uploadValidationError').show();
                ok = false;
            }
        });

        $('#startUploadLink').val('Start Upload');

        if (files.length === 0) {
            $('.upload-progress-message').text('No files selected');
            $('#startUploadLink').addClass('disabled');
        }
        else {
            totalSize = 0;
            $.each(files, function (i, file) {
                totalSize += file.size;
            });
            $('.overall-progress-message').html(' Selected ' + files.length +
                ' files (' + midas.formatBytes(totalSize) + ') -- Press start.');
            if (ok) {
                $('#startUploadLink').removeClass('disabled');
            }
            else {
                $('#startUploadLink').addClass('disabled');
            }
            $('.progress-overall,.progress-current').addClass('hide');
            $('.current-progress-message').empty();
        }
    };

    $('.drop-zone').click(function () {
        $('#upload-files').click();
    }).on('dragover', _eatEvent)
        .on('dragenter', _eatEvent)
        .on('drop', function (e) {
            _eatEvent(e);

            files = e.originalEvent.dataTransfer.files;
            filesChanged();
        });

    $('#upload-files').change(function (e) {
        _eatEvent(e);
        files = this.files;
        filesChanged();
    });

    $('#startUploadLink').click(function (e) {
        if ($(this).hasClass('disabled')) {
            return;
        }

        if ($('#destinationId').val() == undefined || $('#destinationId').val().length == 0) {
            midas.createNotice("Please select where you want to upload your files.", 4000, 'warning');
            return false;
        }

        $(this).addClass('disabled');
        $('.drop-zone').hide();

        if (resumeUploadId !== null) {
            return _resumeUpload();
        }

        $('.progress-overall,.progress-current').removeClass('hide');

        currentIndex = 0;
        overallProgress = 0;
        lastProgress = 0;
        resumeUploadId = null;
        _uploadNextFile();
    });

    var _resumeUpload = function () {
        $('div.uploadValidationError').hide();

        // Get the offset from the server
        $.ajax({
            dataType: 'json',
            type: 'GET',
            url: json.global.webroot + '/rest/system/uploadoffset?uploadtoken=' + resumeUploadId
        }).success(function (resp) {
            startByte = resp.data.offset;
            overallProgress += startByte;
            _streamFileContents(resumeUploadId);
        }).error(function (resp) {
            $('div.uploadValidationError b').text('Could not connect to the server to resume upload.');
            $('div.uploadValidationError').show();
            $('#startUploadLink').removeClass('disabled');
            console.log(resp);
        });
    };
};

$('.uploadTabs').tabs({
    ajaxOptions: {
        beforeSend: function () {
            'use strict';
            $('div.MainDialogLoading').show();
        },
        success: function () {
            'use strict';
            $('div.MainDialogLoading').hide();
            $(".uploadTabs").show();
        },
        error: function (xhr, status, index, anchor) {
            'use strict';
            $(anchor.hash).html("Couldn't load this tab. ");
        }
    }
});

$('.uploadTabs').show();
$('#linkForm').ajaxForm(function () {
    'use strict';
    $('.uploadedLinks').val(parseInt($('.uploadedLinks').val()) + 1);
});

if ($.browser.msie) {
    $('#swfuploadContent').show();
    $('#jqueryFileUploadContent').hide();
    midas.upload.simpleupload.initSwfupload();
}
else {
    $('#swfuploadContent').hide();
    $('#jqueryFileUploadContent').show();
    midas.upload.simpleupload.initHtml5FileUpload();
}

$('.browseMIDASLink').click(function () {
    'use strict';
    midas.loadDialog("select", "/browse/selectfolder/?policy=write");
    midas.showDialog('Select upload destination');
});

if ($('#destinationId').val()) {
    $('#startUploadLink').removeClass('disabled');
}

midas.registerCallback('CALLBACK_CORE_UPLOAD_FOLDER_CHANGED', 'core', function () {
    'use strict';
    $('#startUploadLink').removeClass('disabled');
});

midas.doCallback('CALLBACK_CORE_SIMPLEUPLOAD_LOADED');
