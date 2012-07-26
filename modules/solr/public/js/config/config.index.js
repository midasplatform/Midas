var midas = midas || {};
midas.solr = midas.solr || {};

midas.solr.validateConfig = function (formData, jqForm, options) {
};

midas.solr.successConfig = function (responseText, statusText, xhr, form) {
  try {
      var jsonResponse = $.parseJSON(responseText);
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
      $('div.notSavedWarning').remove();
  }
  else {
      midas.createNotice(jsonResponse[1], 4000, 'error');
  }
}

$(document).ready(function () {
    $('#configForm').ajaxForm({
        beforeSubmit: midas.solr.validateConfig,
        success: midas.solr.successConfig
    });

    $('#rebuildIndexButton').click(function() {
        $('#rebuildProgressMessage').html('Rebuilding item index...');
        $(this).attr('disabled', 'disabled');
        midas.ajaxWithProgress(
          $('#rebuildProgressBar'),
          $('#rebuildProgressMessage'),
          json.global.webroot+'/admin/task',
          {task: 'TASK_CORE_RESET_ITEM_INDEXES'},
          function (responseText) {
              $('#rebuildIndexButton').removeAttr('disabled');
              try {
                  var resp = $.parseJSON(responseText);
                  midas.createNotice(resp.message, 4000, resp.status);
              } catch (e) {
                  midas.createNotice('Error occurred, please check the logs', 4000, 'error');
              }
              $('#rebuildProgressMessage').html('Index rebuild finished');
          });
    });
});
