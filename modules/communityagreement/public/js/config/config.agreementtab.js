/**
 * An ajax based form submission for form 'createAgreementForm'
*/
  $(document).ready(function() {
       $('#createAgreementForm').ajaxForm( {beforeSubmit: validateAgreementChange, success: successAgreementChange} );
  });

  function validateAgreementChange(formData, jqForm, options) {
 
  }

  function successAgreementChange(responseText, statusText, xhr, form) {

    try {
           jsonResponse = $.parseJSON(responseText);
        } catch (e) {
           alert(responseText);
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
          $('#tabsGeneric').tabs('load', $('#tabsGeneric').tabs('option', 'selected')); //reload tab
        }
      else
        {
          createNotive(jsonResponse[1],4000);
        }

    }