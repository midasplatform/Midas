var midas = midas || {};
midas.upload = midas.upload || {};
midas.upload.revision = {};

midas.upload.revision.updateUploadedCount = function()
  {
  var count = parseInt($('.uploadedSimple').val()) +
                       parseInt($('.uploadedLinks').val()) +
                       parseInt($('.uploadedJava').val());
  $('.globalUploadedCount').html(count);
  if(count > 0)
    {
    $('.reviewUploaded').show();
    }
  else
    {
    $('.reviewUploaded').hide();
    }
  }

// Use jquery file upload for real browsers (no flash required)
midas.upload.revision.initJqueryFileupload = function()
  {
  midas.upload.revision.updateUploadedCount();
  //see http://aquantum-demo.appspot.com/file-upload
  $('.file_upload:visible').fileUploadUIX({
    beforeSend: function (event, files, index, xhr, handler, callBack) {
      if(index == 0) //only do this once since we have all the files every time
        {
        var retVal = midas.doCallback('CALLBACK_CORE_VALIDATE_UPLOAD', {files: files, revision: true});
        $.each(retVal, function(module, resp) {
          if(resp.status === false)
            {
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
      $('.uploadedSimple').val(parseInt($('.uploadedSimple').val())+1);
      midas.upload.revision.updateUploadedCount();
      },
    sequentialUploads: true
    });

  $('#startUploadLink').click(function () {
    if($('#destinationId').val() == undefined || $('#destinationId').val().length == 0)
      {
      createNotice("Please select where you want to upload your files.", 4000);
      return false;
      }
    $('.file_upload_start button').click();
    return false;
    });
  }

// Callback hook for the flash uploader
midas.upload.revision.uploadPreStart = function(file)
  {
  midas.upload.revision.swfu.setPostParams({
    'sid': $('.sessionId').val(),
    'parent': $('#destinationId').val(),
    'license': $('select[name=licenseSelect]').val(),
    'changes': $('textarea[name=revisionChanges]').val()
    });
  }

// We use shockwave flash uploader for IE (no multi-file upload support)
midas.upload.revision.initSwfupload = function()
  {
  var settings = {
    flash_url: json.global.coreWebroot+"/public/js/swfupload/swfupload_fp10/swfupload.swf",
    flash9_url: json.global.coreWebroot+"/public/js/swfupload/swfupload_fp9/swfupload_fp9.swf",
    upload_url: json.global.webroot+"/upload/saveuploaded",
    post_params: {
      'sid': $('.sessionId').val(),
      'parent': $('#destinationId').val(),
      'license': $('select[name=licenseSelect]').val(),
      'changes': $('textarea[name=revisionChanges]').val()
      },
    file_size_limit: $('.maxSizeFile').val()+" MB",
    file_types: "*.*",
    file_types_description: "All Files",
    file_upload_limit: 100,
    file_queue_limit: 0,
    custom_settings: {
      progressTarget: "fsUploadProgress",
      cancelButtonId: "btnCancel",
      pageObj: midas.upload.revision
      },
    debug: false,

    // Button settings
    button_image_url: json.global.coreWebroot+"/public/js/swfupload/images/Button_65x29.png",
    button_width: "65",
    button_height: "20",
    button_placeholder_id: "spanButtonPlaceHolder",
    button_text: '<span class="theFont">'+$('.buttonBrowse').val()+'</span>',
    button_text_style: ".theFont { font-size: 12; }",
    button_text_left_padding: 5,
    button_text_top_padding: 0,

    // The event handler functions are defined in handlers.js
    swfupload_preload_handler: preLoad,
    swfupload_load_failed_handler: loadFailed,
    file_queued_handler: fileQueued,
    file_queue_error_handler: fileQueueError,
    file_dialog_complete_handler: fileDialogComplete,
    upload_start_handler: midas.upload.revision.uploadPreStart,
    upload_progress_handler: uploadProgress,
    upload_error_handler: uploadError,
    upload_success_handler: uploadSuccess,
    upload_complete_handler: uploadComplete,
    queue_complete_handler: queueComplete  // Queue plugin event
    };
  $('#swfuploadContent').show();
  midas.upload.revision.swfu = new SWFUpload(settings);

  $('#startUploadLink').click(function() {
    if($('#destinationId').val() == undefined || $('#destinationId').val().length == 0)
      {
      createNotice("Please select where you want to upload your files.", 4000);
      return false;
      }
    midas.upload.revision.swfu.startUpload();
    });
  }

$(".uploadTabs").tabs({
  ajaxOptions: {
    beforeSend: function() {
      $('div.MainDialogLoading').show();
      },
    success: function() {
      $('div.MainDialogLoading').hide();
      $( ".uploadTabs" ).show();
      },
    error: function(xhr, status, index, anchor) {
      $(anchor.hash).html("Couldn't load this tab. ");
      }
    }
  });
$(".uploadTabs").show();

$('#linkForm').ajaxForm(function() {
  $('.uploadedLinks').val(parseInt($('.uploadedLinks').val()) + 1);
  midas.upload.revision.updateUploadedCount();
  });

if($.browser.msie)
  {
  $('#swfuploadContent').show();
  $('#jqueryFileUploadContent').hide();
  midas.upload.revision.initSwfupload();
  }
else
  {
  $('#swfuploadContent').hide();
  $('#jqueryFileUploadContent').show();
  midas.upload.revision.initJqueryFileupload();
  }

$('#browseMIDASLink').click(function() {
  loadDialog("select", "/browse/movecopy/?selectElement=true");
  showDialog('Browse');
  });

midas.doCallback('CALLBACK_CORE_REVISIONUPLOAD_LOADED');

