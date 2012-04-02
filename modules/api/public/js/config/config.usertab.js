var midas = midas || {};
midas.api = midas.api || {};

midas.api.validateApiConfig = function (formData, jqForm, options) {
}

midas.api.successApiConfig = function (responseText, statusText, xhr, form) {
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
      $('#tabsSettings').tabs('load', $('#tabsSettings').tabs('option', 'selected')); //reload tab
  }
  else {
      midas.createNotice(jsonResponse[1], 4000, 'error');
  }
}

$(document).ready(function() {
    $('#generateKeyForm').ajaxForm({
        beforeSubmit: midas.api.validateApiConfig,
        success: midas.api.successApiConfig} );

    $('a.deleteApiKeyLink').click(function () {
        var obj = $(this);
        $.post(json.global.webroot+'/api/config/usertab', {deleteAPIKey: true, element: $(this).attr('element')},
            function(data) {
                var jsonResponse = jQuery.parseJSON(data);
                if(jsonResponse == null) {
                    midas.createNotice('Error', 4000, 'error');
                    return;
                }
                if(jsonResponse[0]) {
                    midas.createNotice(jsonResponse[1], 2000);
                    obj.parents('tr').remove();
                }
                else {
                    midas.createNotice(jsonResponse[1], 4000, 'error');
                }
            }
        );
   });
});
