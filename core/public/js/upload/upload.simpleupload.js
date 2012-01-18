var midas = midas || {};
midas.upload = midas.upload || {};
midas.upload.simpleupload = {};

midas.upload.simpleupload.updateUploadedCount = function()
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

midas.upload.simpleupload.initJqueryFileupload = function()
  {
  midas.upload.simpleupload.updateUploadedCount();
  //see http://aquantum-demo.appspot.com/file-upload
  $('.file_upload:visible').fileUploadUIX({
    beforeSend: function (event, files, index, xhr, handler, callBack) {
      if(index == 0) //only do this once since we have all the files every time
        {
        var retVal = midas.doCallback('CALLBACK_CORE_VALIDATE_UPLOAD', {files: files});
        $.each(retVal, function(module, resp) {
          if(resp.status === false)
            {
            $('div.uploadValidationError b').html(resp.message);
            $('div.uploadValidationError').show();
            }
          });
        }

      handler.uploadRow.find('.file_upload_start').click(function () {
        var path = '';
        $.each(files, function (index, file) {
          path += file.webkitRelativePath+';;';
          });
        handler.formData = {
          parent: $('#destinationId').val(),
          path: path,
          license: $('select[name=licenseSelect]').val()
          };
        callBack();
        });
      $('.file_name').each(function(){
        if($(this).html() == '.' || $(this).html() == '..')
          {
          $(this).parent('tr').find('.file_upload_cancel button').click();
          }
        });

      $('#startUploadLink').css('box-shadow', '0 0 5px blue');
      $('#startUploadLink').css('-webkit-box-shadow', '0 0 5px blue');
      $('#startUploadLink').css('-moz-box-shadow', '0 0 5px blue');
      },
    onComplete:  function (event, files, index, xhr, handler) {
      midas.doCallback('CALLBACK_CORE_RESET_UPLOAD_TOTAL');
      $('.uploadedSimple').val(parseInt($('.uploadedSimple').val())+1);
        midas.upload.simpleupload.updateUploadedCount();
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
      $('#startUploadLink').css('box-shadow', '0 0 0px blue');
      $('#startUploadLink').css('-webkit-box-shadow', '0 0 0px blue');
      $('#startUploadLink').css('-moz-box-shadow', '0 0 0px blue');
      return false;
    });
  }

// Callback hook for the flash uploader
midas.upload.simpleupload.uploadPreStart = function(file)
  {
  midas.upload.simpleupload.swfu.setPostParams({
    'sid': $('.sessionId').val(),
    'parent': $('#destinationId').val(),
    'license': $('select[name=licenseSelect]').val()
    });
  }

// We use shockwave flash uploader for IE (no multi-file upload support)
midas.upload.simpleupload.initSwfupload = function()
  {
  var settings = {
    flash_url: json.global.coreWebroot+"/public/js/swfupload/swfupload_fp10/swfupload.swf",
    flash9_url: json.global.coreWebroot+"/public/js/swfupload/swfupload_fp9/swfupload_fp9.swf",
    upload_url: json.global.webroot+"/upload/saveuploaded",
    post_params: {
      'sid': $('.sessionId').val(),
      'parent': $('#destinationId').val(),
      'license': $('select[name=licenseSelect]').val()
      },
    file_size_limit: $('.maxSizeFile').val()+" MB",
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
    upload_start_handler: midas.upload.simpleupload.uploadPreStart,
    upload_progress_handler: uploadProgress,
    upload_error_handler: uploadError,
    upload_success_handler: uploadSuccess,
    upload_complete_handler: uploadComplete,
    queue_complete_handler: queueComplete  // Queue plugin event
    };

  $('#swfuploadContent').show();
  midas.upload.simpleupload.swfu = new SWFUpload(settings);

  $('#startUploadLink').click(function() {
    if($('#destinationId').val() == undefined || $('#destinationId').val().length == 0)
      {
      createNotice("Please select where you want to upload your files.", 4000);
      return false;
      }
    midas.upload.simpleupload.swfu.startUpload();
    });
  }

$('img#uploadAFile').show();
$('img#uploadAFileLoadiing').hide();

// detect chrom or firefox 7+
try
  {
  var is_chrome = /chrome/.test(navigator.userAgent.toLowerCase());
  var version = jQuery.browser.version.split('.');
  version = parseInt(version[0]);
  var is_firefox = jQuery.browser.mozilla != undefined && version > 6;

  if(is_chrome)
    {
    $('#changeUploadMode').show();
    }
  } catch (e) { }

midas.upload.simpleupload.mode = "file";
midas.upload.simpleupload.htmlFileMode = $('.file_upload_label').html();
midas.upload.simpleupload.htmlFolderMode = $('.file_upload_label_folder').html();

$('#uploadModeFileLink').hide();

$('.uploadModeLink').click(function() {
  if(midas.upload.simpleupload.mode == 'file')
    {
    midas.upload.simpleupload.mode = 'folder';
    $('.fileUploaderInput').attr('webkitdirectory', '');
    $('.fileUploaderInput').attr('directory', '');
    $('.fileUploaderInput').attr('mozdirectory', '');
    $('.file_upload_label').html(midas.upload.simpleupload.htmlFolderMode);
    $('#uploadModeFileLink').show();
    $('#uploadModeFolderLink').hide();
    }
  else
    {
    midas.upload.simpleupload.mode = 'file';
    $('.fileUploaderInput').removeAttr('webkitdirectory');
    $('.fileUploaderInput').removeAttr('directory');
    $('.fileUploaderInput').removeAttr('mozdirectory');
    $('.file_upload_label').html(midas.upload.simpleupload.htmlFileMode);
    $('#uploadModeFileLink').hide();
    $('#uploadModeFolderLink').show();
    }
});

$(".uploadTabs").tabs({
  ajaxOptions: {
    beforeSend: function() {
      $('div.MainDialogLoading').show();
      },
    success: function() {
      $('div.MainDialogLoading').hide();
      $(".uploadTabs").show();
      },
    error: function(xhr, status, index, anchor) {
      $(anchor.hash).html("Couldn't load this tab. ");
      }
    }
  });

$(".uploadTabs").show();
$('#linkForm').ajaxForm(function() {
  $('.uploadedLinks').val(parseInt($('.uploadedLinks').val()) + 1);
  midas.upload.simpleupload.updateUploadedCount();
  });

if($.browser.msie)
  {
  $('#swfuploadContent').show();
  $('#jqueryFileUploadContent').hide();
  midas.upload.simpleupload.initSwfupload();
  }
else
  {
  $('#swfuploadContent').hide();
  $('#jqueryFileUploadContent').show();
  midas.upload.simpleupload.initJqueryFileupload();
  }

$('#startUploadLink').qtip({
  content: {
    attr: 'qtip'
    }
  });

$('.browseMIDASLink').click(function() {
  loadDialog("select", "/browse/selectfolder/?policy=write");
  showDialog('Browse');
  });
midas.doCallback('CALLBACK_CORE_SIMPLEUPLOAD_LOADED');
