
    $('#generateKeyForm').ajaxForm( {beforeSubmit: validateApiConfig, success:       successApiConfig} );
    
    $('a.deleteApiKeyLink').click(function(){
        var obj = $(this);
         $.post(json.global.webroot+'/api/config/usertab', {deleteAPIKey: true, element: $(this).attr('element')},
         function(data) {
           jsonResponse = jQuery.parseJSON(data);
            if(jsonResponse==null)
              {
                createNotive('Error',4000);
                return;
              }
            if(jsonResponse[0])
              {
                createNotive(jsonResponse[1],1500);
                obj.parents('tr').remove();
              }
            else
              {
                createNotive(jsonResponse[1],4000);
              }
         });
    });

  
  function validateApiConfig(formData, jqForm, options) { 
 
}

function successApiConfig(responseText, statusText, xhr, form) 
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
      $('#tabsSettings').tabs('load', $('#tabsSettings').tabs('option', 'selected')); //reload tab
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}