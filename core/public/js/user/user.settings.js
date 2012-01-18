$( "#tabsSettings" ).tabs();

$( "#tabsSettings" ).css('display','block');
$( "#tabsSettings" ).show();

$('#modifyPassword').ajaxForm( { beforeSubmit: validatePasswordChange, success: successPasswordChange  } );

$('#modifyAccount').ajaxForm( { beforeSubmit: validateAccountChange, success: successAccountChange  } );

$('#modifyPicture').ajaxForm( { beforeSubmit: validatePictureChange, success: successPictureChange  } );

jsonSettings = jQuery.parseJSON($('div.jsonSettingsContent').html());

$('textarea#biography').attr('onkeyup', 'this.value = this.value.slice(0, 255)');
$('textarea#biography').attr('onchange', 'this.value = this.value.slice(0, 255)');


function validatePasswordChange(formData, jqForm, options)
{
  var form = jqForm[0]; 
  if(form.newPassword.value.length < 2)
    {
    createNotice(jsonSettings.passwordErrorShort, 4000);
    return false;
    }
  if(form.newPassword.value.length < 2 || form.newPassword.value != form.newPasswordConfirmation.value)
    { 
    createNotice(jsonSettings.passwordErrorMatch, 4000);
    return false;
    } 
}

function validatePictureChange(formData, jqForm, options)
{  
  var form = jqForm[0]; 
}

function validateAccountChange(formData, jqForm, options)
{  
  var form = jqForm[0]; 
  if(form.firstname.value.length < 1)
    {
    createNotice(jsonSettings.accountErrorFirstname, 4000);
    return false;
    }
  if(form.lastname.value.length < 1)
    {
    createNotice(jsonSettings.accountErrorLastname, 4000);
    return false;
    }
}

function successPasswordChange(responseText, statusText, xhr, form) 
{
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse == null)
    {
    createNotice('Error', 4000);
    return;
    }
  if(jsonResponse[0])
    {
    createNotice(jsonResponse[1], 4000);
    }
  else
    {
    $('#modifyPassword input[type=password]').val(''); 
    createNotice(jsonResponse[1], 4000);
    }
}

function successAccountChange(responseText, statusText, xhr, form) 
{
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse == null)
    {
    createNotice('Error', 4000);
    return;
    }
  if(jsonResponse[0])
    {
    createNotice(jsonResponse[1], 4000);
    }
  else
    {
    createNotice(jsonResponse[1], 4000);
    }
}

function successPictureChange(responseText, statusText, xhr, form) 
{
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse == null)
    {
    createNotice('Error', 4000);
    return;
    }
  if(jsonResponse[0])
    {
    $('img#userTopThumbnail').attr('src', jsonResponse[2]);
    createNotice(jsonResponse[1], 4000);
    }
  else
    {
    createNotice(jsonResponse[1], 4000);
    }
}
