$(document).ready(function() 
{ 
  //Bind the migrate submit to start the import
  $('#migratesubmit').click(function(){startMigrate()});

  
  // Form for the new assetstore
  options = {success:assetstoreAddCallback, beforeSubmit:  assetstoreSubmit,  dataType:'json'}; 
  $('#assetstoreForm').ajaxForm(options);
  
  // Load the window for the new assetstore
  $('a.load-newassetstore').cluetip({cluetipClass: 'jtip', 
	                         activation: 'click', 
	  						 local:true, 
	  						 cursor: 'pointer', 
	  						 arrows: true,
	  						 clickOutClose: true
	  						});
});

/** If the button to start the migration has been clicked */
function startMigrate()
{
  formSubmitOptions = {success:migrateCallback,
                       dataType:'json',
                       }; 
  $('#migrateForm').ajaxSubmit(formSubmitOptions);
}

/** On import success */
function migrateCallback(responseText, statusText, xhr, form)  
{ 
  if(responseText.error)
    { 
    $(".viewNotice").html(responseText.error);  
    $(".viewNotice").fadeIn(100).delay(2000).fadeOut(300);
    }
  else if(responseText.message)
    {
    $(".viewNotice").html(responseText.message);    
    $(".viewNotice").fadeIn(100).delay(2000).fadeOut(300);
    }
} 

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
  	  $("#assetstore").append($("<option></option>").
        attr("value",responseText.assetstore_id).
        text(responseText.assetstore_name)
        .attr("selected", "selected"));
  	  
  	  // Add to JSON
  	  var newassetstore = new Object();
  	  newassetstore.assetstore_id = responseText.assetstore_id;
  	  newassetstore.name = responseText.assetstore_name;
  	  newassetstore.type = responseText.assetstore_type;
  	  assetstores.push(newassetstore);
  	  }
  	  
  	$(".viewNotice").html(responseText.msg);  
  	$(".viewNotice").fadeIn(100).delay(2000).fadeOut(100);
    } 
} // end assetstoreAddCallback

/** When the cancel is clicked in the new assetstore window */
function newAssetstoreHide()
{
  $(document).trigger('hideCluetip');
} // end function newAssetstoreHide


