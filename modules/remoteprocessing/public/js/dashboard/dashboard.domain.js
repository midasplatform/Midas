var plotArray = new Array();

$(document).ready(function(){
  $( "#tabsGeneric" ).tabs({
      });
  $("#tabsGeneric").show();

  $('#shareDiv').load($('#shareDiv').attr('src'));

  $('#workflowDomainForm').ajaxForm( {beforeSubmit: validateInfoChange, success:       successInfoChange} );

});

function validateInfoChange(formData, jqForm, options) {

    var form = jqForm[0];
    form = $('#'+form.getAttribute('id'));
    if (form.find('#workflowDomainName').val().length<1)
      {
        createNotive("Please set a name.",4000);
        return false;
      }
}

function successInfoChange(responseText, statusText, xhr, form)
{
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse==null)
    {
      createNotive('Error',4000);
      return;
    }
  if(jsonResponse[0])
    {
      $('div.viewHeader').html("Domain: "+jsonResponse[2]);
      createNotive(jsonResponse[1],4000);
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}
