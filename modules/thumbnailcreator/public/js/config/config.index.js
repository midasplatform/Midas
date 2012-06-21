var midas = midas || {};
midas.thumbnailcreator = midas.thumbnailcreator || {};
midas.thumbnailcreator.config = midas.thumbnailcreator.config || {};

midas.thumbnailcreator.config.validateConfig = function (formData, jqForm, options) {
}

midas.thumbnailcreator.config.successConfig = function (responseText, statusText, xhr, form) {
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

midas.thumbnailcreator.config.initUseThumbnailer = function () {
    var inputThumbnailer = $('input[name=thumbnailer]');
    var inputUseThumbnailer = $('input[name=useThumbnailer]');
    var thumbnailerDiv = $('div#thumbnailerDiv');

    if(inputUseThumbnailer.filter(':checked').val() == 0) { //private
        inputThumbnailer.attr('disabled', 'disabled');
        inputThumbnailer.removeAttr('checked');
        inputThumbnailer.filter('[value=0]').attr('checked', true); //invitation
        thumbnailerDiv.hide();
    }
    else {
        inputThumbnailer.removeAttr('disabled');
        thumbnailerDiv.show();
    }
    inputUseThumbnailer.change(function () {
        midas.thumbnailcreator.config.initUseThumbnailer();
    });
}


$(document).ready(function() {
    midas.thumbnailcreator.config.initUseThumbnailer();
  
    $('#configForm').ajaxForm({
        beforeSubmit: midas.thumbnailcreator.config.validateConfig,
        success: midas.thumbnailcreator.config.successConfig
    });
    $('#thumbnailerForm').ajaxForm({
        beforeSubmit: midas.thumbnailcreator.config.validateConfig,
        success: midas.thumbnailcreator.config.successConfig
    });
});
