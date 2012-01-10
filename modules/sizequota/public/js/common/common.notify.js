midas.sizequota = midas.sizequota || {};
midas.sizequota.totalSize = 0;

/**
 * Called when a user selects a set of files in the simple uploader
 * @return Json object {status: bool, message: string}.
 * If status is false, caller should render the error message.
 */
midas.sizequota.validateUpload = function(args)
{
  $.each(args.files, function(index, file) {
    midas.sizequota.totalSize += file.size;
    });
  var freeSpace = $('#sizequotaFreeSpace').html();

  if(freeSpace != '' && midas.sizequota.totalSize > parseInt(freeSpace))
    {
    return {
      status: false,
      message: 'Uploading these files would exceed the maximum quota for the selected folder.  Please choose a different folder or remove some of the files.'
      };
    }
  else
    {
    return {
      status: true
      };
    }
}

/**
 * Called when the upload is complete
 */
midas.sizequota.resetTotal = function()
{
  midas.sizequota.totalSize = 0;
}

/**
 * Called when a different upload location is selected
 */
midas.sizequota.folderChanged = function(args)
{
  $.ajax({
    type: "POST",
    url: json.global.webroot+'/sizequota/config/getfreespace',
    data: {folderId: args.folderId},
    success: function(jsonContent) {
      var jsonResponse = jQuery.parseJSON(jsonContent);
      $('#sizequotaFreeSpace').html(jsonResponse.freeSpace);

      if(midas.sizequota.totalSize == 0)
        {
        return;
        }
      if(jsonResponse.freeSpace == '') //unlimited space
        {
        $('div.uploadValidationError').hide();
        return;
        }
      var freeSpace = parseInt(jsonResponse.freeSpace);
      if(midas.sizequota.totalSize > freeSpace)
        {
        $('div.uploadValidationError').show();
        }
      else
        {
        $('div.uploadValidationError').hide();
        }
      }
    });
}

midas.registerCallback('CALLBACK_CORE_VALIDATE_UPLOAD', 'sizequota', midas.sizequota.validateUpload);
midas.registerCallback('CALLBACK_CORE_RESET_UPLOAD_TOTAL', 'sizequota', midas.sizequota.resetTotal);
midas.registerCallback('CALLBACK_CORE_UPLOAD_FOLDER_CHANGED', 'sizequota', midas.sizequota.folderChanged);
