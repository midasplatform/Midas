var midas = midas || {};
midas.upload = midas.upload || {};
midas.upload.javaupload = {};

midas.upload.javaupload.sendParentToJavaSession = function()
  {
  $.post(json.global.webroot+'/upload/javaupload', {
    parent: $('#destinationId').val(),
    license: $('select[name=licenseSelect]:last').val()
    });
  }

$('.browseMIDASLink').click(function() {
  loadDialog("select","/browse/selectfolder/?policy=write");
  showDialog('Browse', null, {
    close: function() {
      $('applet').show();
      }
    });
  $('applet').hide();
  });

$('.destinationId').val($('#destinationId').val());
$('.destinationUpload').html($('#destinationUpload').html());

// Save initial state to the session
midas.upload.javaupload.sendParentToJavaSession();

// Save license change to the session
$('select[name=licenseSelect]:last').change(function() {
  midas.upload.javaupload.sendParentToJavaSession();
  });

// Save parent folder to the session
function folderSelectionCallback()
  {
  midas.upload.javaupload.sendParentToJavaSession();
  }

midas.doCallback('CALLBACK_CORE_JAVAUPLOAD_LOADED');
