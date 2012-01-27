var midas = midas || {};
midas.community = midas.community || {};

midas.community.promotedialogBeforeSubmit = function(formData, jqForm, options)
{
  $('#addToGroupsSubmitButton').attr('disabled', 'disabled');
  $('#promoteDialogLoading').show();
  return true;
}

midas.community.promotedialogSuccess = function(responseText, statusText, xhr, form)
{
  $('div.MainDialog').dialog('close');
  $('#addToGroupsSubmitButton').removeAttr('disabled');
  $('#promoteDialogLoading').hide();
  var jsonResponse = $.parseJSON(responseText);

  if(jsonResponse == null)
    {
    createNotice('Error occurred. Check the logs.', 4000);
    return;
    }
  createNotice(jsonResponse[1], 4000);
  if(jsonResponse[0])
    {
    window.location.replace(json.global.webroot+
      '/community/manage?communityId='+$('#promoteCommunityId').val()+'#tabs-2');
    window.location.reload();
    }
}

$(document).ready(function() {
  $('#promoteGroupForm').ajaxForm({
    beforeSubmit: midas.community.promotedialogBeforeSubmit,
    success: midas.community.promotedialogSuccess
    });
});
