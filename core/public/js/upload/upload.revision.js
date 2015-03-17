// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global console */
/* global json */

var midas = midas || {};
midas.upload = midas.upload || {};
midas.upload.revision = {};

midas.upload.revision.initHtml5FileUpload = function () {
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
        if (currentIndex > 0) {
            // All files have finished
            $('.drop-zone').show();
            window.location.href = json.global.webroot + '/item/' + encodeURIComponent($('#destinationId').val());
            return;
        }
        var file = files[currentIndex];
        // Initialize this upload
        $.ajax({
            dataType: 'json',
            type: 'GET',
            url: json.global.webroot + '/rest/system/uploadtoken?useSession=true' +
            '&filename=' + encodeURIComponent(file.name) +
            '&itemid=' + encodeURIComponent($('#destinationId').val())
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
        var url = json.global.webroot + '/api/json?method=midas.upload.perform&uploadtoken=' +
            encodeURIComponent(uploadToken) + '&length=' + encodeURIComponent(file.size) + '&filename=' + encodeURIComponent(file.name);
        var changes = $('#revisionChanges').val();
        if(changes) {
            url += '&changes=' + encodeURIComponent(changes);
        }
        console.log(url);

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
        xhr.setRequestHeader('X-File-Name', file.name);
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

        if (files.length === 1) {
            totalSize = files[0].size;
            $('.overall-progress-message').html(' Selected 1 file (' +
                midas.formatBytes(totalSize) + ') -- Press start.');
            if (ok) {
                $('#startUploadLink').removeClass('disabled');
            }
            else {
                $('#startUploadLink').addClass('disabled');
            }
            $('.progress-overall,.progress-current').addClass('hide');
            $('.current-progress-message').empty();
        }
        else {
            $('.upload-progress-message').text('No files selected.');
            $('#startUploadLink').addClass('disabled');
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
            url: json.global.webroot + '/rest/system/uploadoffset?uploadtoken=' + encodeURIComponent(resumeUploadId)
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


$('.uploadTabs').tabs({
    ajaxOptions: {
        beforeSend: function () {
            'use strict';
            $('.MainDialogLoading').show();
        },
        success: function () {
            'use strict';
            $('.MainDialogLoading').hide();
            $('.uploadTabs').show();
        },
        error: function (xhr, status, index, anchor) {
            'use strict';
            $(anchor.hash).html('Could not load this tab.');
        }
    }
});

$('.uploadTabs').show();
$('#jqueryFileUploadContent').show();
midas.upload.revision.initHtml5FileUpload();
midas.doCallback('CALLBACK_CORE_REVISIONUPLOAD_LOADED');

