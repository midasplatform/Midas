var midas = midas || {};
midas.share = midas.share || {};
midas.share.applyrecursive = {};

midas.share.applyrecursive.submitClicked = function(formData, jqForm, options)
{
  $('input#acceptApplyRecursive').attr('disabled', 'disabled');
  $('input#declineApplyRecursive').attr('disabled', 'disabled');
  $('img#applyPoliciesRecursiveLoadingGif').show();
}

midas.share.applyrecursive.success = function(responseText, statusText, xhr, form)
{
  $('div.MainDialog').dialog('close');
  $('input#acceptApplyRecursive').removeAttr('disabled');
  $('input#declineApplyRecursive').removeAttr('disabled');
  $('img#applyPoliciesRecursiveLoadingGif').hide();
  jsonResponse = $.parseJSON(responseText);
  
  if(jsonResponse == null)
    {
    createNotice('Error', 4000);
    return;
    }
  if(jsonResponse[0])
    {
    var success = jsonResponse[1].success;
    var failure = jsonResponse[1].failure;
    if(success > 0)
      {
      createNotice('Successfully set policies on ' + success + ' resources', 4000);
      }
    if(failure > 0)
      {
      createNotice('Failed to set policies on ' + failure + ' resources', 5000);
      }
    }
  else
    {
    createNotice(jsonResponse[1], 4000);
    }
}

$('form#applyPoliciesRecursiveForm').ajaxForm({
  beforeSubmit: midas.share.applyrecursive.submitClicked,
  success: midas.share.applyrecursive.success
  });

$('input#declineApplyRecursive').click(function() {
  $('div.MainDialog').dialog('close');
  });
