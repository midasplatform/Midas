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
sendParentToJavaSession();

// Save license change to the session
$('select[name=licenseSelect]:last').change(function() {
  sendParentToJavaSession();
  });

// Save parent folder to the session
function folderSelectionCallback()
  {
  sendParentToJavaSession();
  }

midas.doCallback('CALLBACK_CORE_JAVAUPLOAD_LOADED');
