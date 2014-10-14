// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.upload = midas.upload || {};
midas.upload.revision = {};

midas.upload.revision.updateUploadedCount = function () {
    'use strict';
    var count = parseInt($('.uploadedSimple').val()) +
        parseInt($('.uploadedLinks').val());
    $('.globalUploadedCount').html(count);
};

// Use jquery file upload for real browsers (no flash required)
midas.upload.revision.initJqueryFileupload = function () {
    'use strict';
    midas.upload.revision.updateUploadedCount();
    // see http:// aquantum-demo.appspot.com/file-upload
    $('.file_upload:visible').fileUploadUIX({
        beforeSend: function (event, files, index, xhr, handler, callBack) {
            if (index === 0) { // only do this once since we have all the files every time
                var retVal = midas.doCallback('CALLBACK_CORE_VALIDATE_UPLOAD', {
                    files: files,
                    revision: true
                });
                $.each(retVal, function (module, resp) {
                    if (resp.status === false) {
                        $('div.uploadValidationError b').html(resp.message);
                        $('div.uploadValidationError').show();
                    }
                });
            }

            handler.uploadRow.find('.file_upload_start').click(function () {
                handler.formData = {
                    parent: $('#destinationId').val(),
                    license: $('select[name=licenseSelect]').val(),
                    changes: $('textarea[name=revisionChanges]').val()
                };
                callBack();
            });
        },
        onComplete: function (event, files, index, xhr, handler) {
            $('.uploadedSimple').val(parseInt($('.uploadedSimple').val()) + 1);
            midas.upload.revision.updateUploadedCount();
            if (index == files.length - 1) { // after all files are done, redirect to parent folder
                window.location.reload();
            }
        },
        sequentialUploads: true
    });

    $('#startUploadLink').click(function () {
        if ($('#destinationId').val() === undefined || $('#destinationId').val().length === 0) {
            midas.createNotice("Please select where you want to upload your files.", 4000, 'warning');
            return false;
        }
        $('.file_upload_start button').click();
        return false;
    });
};

$(".uploadTabs").tabs({
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
$(".uploadTabs").show();

$('#linkForm').ajaxForm(function () {
    'use strict';
    $('.uploadedLinks').val(parseInt($('.uploadedLinks').val()) + 1);
    midas.upload.revision.updateUploadedCount();
});

$('#jqueryFileUploadContent').show();
midas.upload.revision.initJqueryFileupload();

$('#browseMIDASLink').click(function () {
    'use strict';
    midas.loadDialog("select", "/browse/movecopy/?selectElement=true");
    midas.showDialog('Browse');
});

$(document).ready(function () {
    'use strict';
    midas.doCallback('CALLBACK_CORE_REVISIONUPLOAD_LOADED');
});
