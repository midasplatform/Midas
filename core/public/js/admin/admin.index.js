  $(document).ready(function() {
    
    tabs=$( "#tabsGeneric" ).tabs({
      });
    $("#tabsGeneric").show();
    $('img.tabsLoading').hide()
      
      
    $('#configForm').ajaxForm( {beforeSubmit: validateConfig, success:       successConfig} );
    
      // Form for the new assetstore
    options = { success:assetstoreAddCallback, beforeSubmit:  assetstoreSubmit,  dataType:'json' }; 
    $('#assetstoreForm').ajaxForm(options);
    
    $('a.load-newassetstore').cluetip({cluetipClass: 'jtip', 
	                         activation: 'click', 
	  						 local:true, 
	  						 cursor: 'pointer', 
	  						 arrows: true,
	  						 clickOutClose: true,
	  						 onShow: newAssetstoreShow
	  						});
                
     $('input.moduleCheckbox').change(function(){
           if($(this).is(':checked'))
             {
             modulevalue='true'; 
             }
           else
             {
             modulevalue='false';
             }
           $.post(json.global.webroot+'/admin/index', {submitModule: true, modulename: $(this).attr('module') , modulevalue:modulevalue},
           function(data) {
               jsonResponse = jQuery.parseJSON(data);
                if(jsonResponse==null)
                  {
                    createNotive('Error',4000);
                    return;
                  }
                createNotive(jsonResponse[1],1500);
           });
     });

  });
  
  var tabs;
  
/** On assetstore add sucess */
function assetstoreAddCallback(responseText, statusText, xhr, $form)  
{ 
  $(".assetstoreLoading").hide();
  if(responseText.error)
  	{
  	$(".viewNotice").html(responseText.error);  
  	$(".viewNotice").fadeIn(100).delay(2000).fadeOut(100);
  	}
  else if(responseText.msg)
  	{
  	$(document).trigger('hideCluetip');
  	    
  	// It worked, we add the assetstore to the list and we select it by default
  	if(responseText.assetstore_id)
  	  {
      var html='';
      html+="<div class='assetstoreElement'>  <span class='assetstoreName'><b>"+responseText.assetstore_name+"</b></span> <br/>";
      html+="Total space: "+responseText.totalSpaceText+"<br/>";
      html+="<b>Free space: "+responseText.freeSpaceText+"</b>";
      html+="</div>";
      $('div.assetstoreElement:last').after(html);
      console.log(html);
  	  }
  	  
    createNotive(responseText.msg,4000);
    } 
} // end assetstoreAddCallback
   
/** On assetstore add submit */
function assetstoreSubmit(formData, jqForm, options)  
{
  // Add the type is the one in the main page (because it's hidden in the assetstore add page)
  var assetstoretype = new Object();
  assetstoretype.name = 'type';
  assetstoretype.value = $("#importassetstoretype").val();
  formData.push(assetstoretype);  
  $(".assetstoreLoading").show();
} // end assetstoreBeforeSubmit

  
/** When the cancel is clicked in the new assetstore window */
function newAssetstoreShow()
{
  var assetstoretype = $('select#importassetstoretype option:selected').val();
  $('#assetstoretype option:selected').removeAttr("selected");
  $('#assetstoretype option[value='+assetstoretype+']').attr("selected", "selected");
} // end function newAssetstoreShow

/** When the cancel is clicked in the new assetstore window */
function newAssetstoreHide()
{
  $(document).trigger('hideCluetip');
} // end function newAssetstoreHide

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