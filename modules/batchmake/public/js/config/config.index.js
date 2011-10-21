// TODO put all strings into translation 
// TODO clean up this file and make it more rational


global_config_error_msg = "The overall configuration is in error";
global_config_correct_msg = "The overall configuration is correct";





info_class = 'info';
error_class = 'error';

application_entry = 'Application';
php_entry = 'PHP';
application_div = 'apps_config_div';
php_div = 'php_config_div';



  $(document).ready(function() {
    
      
    $('#configForm').ajaxForm( {beforeSubmit: validateConfig, success:       successConfig} );

    $('#configForm input').each(function()
      {
      // add a span after each input for displaying any errors related to that input
      inputID = $(this).attr("id")       
      $(this).after('<span id="'+inputID+'Status'+'"></span>');
      })
    $('#configForm').focusout(function()
      {
        checkConfig($(this));
      });
      
    $('#configForm input').keyup(function()
      {
      $(document).find('#submitConfig').attr('disabled','disabled');
  //      var obj=$(this);
        //checkAll(obj);
      });

    $(document).find('#submitConfig').attr('disabled','disabled');

    checkConfig($(this));
  });


function checkConfig(obj)
  {
//  obj=obj.parents('form');
  $(document).find('#testLoading').show();
  $(document).find('#testOk').hide();
  $(document).find('#testNok').hide();
  $(document).find('#testError').html('');

tmp_dir_val  = $(document).find('#tmp_dir').val()
bin_dir_val  = $(document).find('#bin_dir').val()
script_dir_val  = $(document).find('#script_dir').val()
app_dir_val  = $(document).find('#app_dir').val()
data_dir_val  = $(document).find('#data_dir').val()
condor_bin_dir_val  = $(document).find('#condor_bin_dir').val()


  $.ajax({
          type: "POST",
          url: json.global.webroot+'/batchmake/config/testconfig',
          data: {tmp_dir: tmp_dir_val, bin_dir: bin_dir_val, script_dir: script_dir_val,
            app_dir: app_dir_val,data_dir: data_dir_val,condor_bin_dir: condor_bin_dir_val},
          cache:false,
          success: function(jsonContent){ handleValidationResponse(jsonContent) }
         }); 
  return;
  }  


function handleValidationResponse(jsonContent)
  {
  $(document).find('#testLoading').hide();
            
  var testConfig=jQuery.parseJSON(jsonContent);
  // testConfig should be
  // [0] = 1 if the global config is correct, 0 otherwise
  // [1] = an array of individual config properties and statuses            
            
  global_config_correct = testConfig[0];
  config_properties = testConfig[1];

  // handle global config value
  if(global_config_correct == true)
    {
    $(document).find('#testOk').show();
    $(document).find('#testError').html(global_config_correct_msg).removeClass().addClass(info_class);       
    $(document).find('#submitConfig').removeAttr('disabled');
    }
  else
    {
    $(document).find('#testNok').show();
    $(document).find('#testError').html(global_config_error_msg).removeClass().addClass(error_class);
    $(document).find('#submitConfig').attr('disabled','disabled');
    }

  $(document).find('div #'+application_div).children().remove();
  $(document).find('div #'+php_div).children().remove();

  // now look at all of the individual config values, print out statuses
  for (configVarInd in config_properties)
    {
    property = config_properties[configVarInd]['property'];
    status = config_properties[configVarInd]['status'];
    type = config_properties[configVarInd]['type'];
    if(property.search(application_entry) > -1)
      {
      spanString = '<div class="'+type+'">'+property+' '+status+'</div>';
      $(document).find('div #'+application_div).append(spanString);
      }
    else if(property.search(php_entry) > -1)
      {
      spanString = '<div class="'+type+'">'+property+' '+status+'</div>';
      $(document).find('div #'+php_div).append(spanString);
      }
    else
      {
      configVarStatusSpan_selector = '#' + property + 'Status';
      $(document).find(configVarStatusSpan_selector).html(status).removeClass().addClass(type);
      }
    }               
  }



  
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
