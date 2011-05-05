$('#recoverPasswordForm').ajaxForm( {beforeSubmit: validateRecoverPassword, success:       successRecoverPassword} );


function validateRecoverPassword(formData, jqForm, options) { 
 
    var form = jqForm[0]; 
    if (form.email.value.length<1)
      {
        createNotive('Error email',4000);
        return false;
      }
}

function successRecoverPassword(responseText, statusText, xhr, form) 
{
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse==null)
    {
      createNotive('Error',4000);
      return;
    }
  if(jsonResponse[0])
    {
      createNotive(jsonResponse[1],4000);
      $( "div.MainDialog" ).dialog("close");
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}
