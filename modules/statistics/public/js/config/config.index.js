  $(document).ready(function() {
    
      
    $('#configForm').ajaxForm( {beforeSubmit: validateConfig, success:       successConfig} );
  });
  
  
  function validateConfig(formData, jqForm, options) { 
 
}

function successConfig(responseText, statusText, xhr, form) 
{
  try {
        jsonResponse = jQuery.parseJSON(responseText);
    } catch (e) {
      alert("An error occured. Please check the logs.");
        return false;
    }
  if(jsonResponse==null)
    {
      createNotive('Error',4000);
      return;
    }
  if(jsonResponse[0])
    {
      createNotive(jsonResponse[1],4000);
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}