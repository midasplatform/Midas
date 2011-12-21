// We have four stages: validate, initialize, upload
var stage = 'validate';
var formSubmitOptions;
var importSubmitButtonValue;
var uploadid;

$(document).ready(function() 
{ 
  
  // Bind the import submit to start the import
  $('#importsubmit').click(function(){startImport()});
  
  // Bind the input directory 
  $('#inputdirectory').change(function(){inputdirectoryChanged()});
	
  // Form for the new assetstore
  options = {success:assetstoreAddCallback, beforeSubmit:  assetstoreSubmit,  dataType:'json'}; 
  $('#assetstoreForm').ajaxForm(options);
  
  // Load the window for the new assetstore
  $('a.load-newassetstore').cluetip({cluetipClass: 'jtip', 
	                         activation: 'click', 
	  						 local:true, 
	  						 cursor: 'pointer', 
	  						 arrows: true,
	  						 clickOutClose: true,
	  						 onShow: newAssetstoreShow
	  						});

  $("#progress").progressbar();
  
  // Not possible to change the type of an assetstore. This is based on previous choice by the user
  $("#assetstoretype").attr('disabled', 'disabled');

  importSubmitButtonValue = $("#importsubmit").html();
  
  //Init Browser
  $('input[name=importFolder]').val('');
  $('input[name=importFolder]').attr('id','destinationId');
  $('input[name=importFolder]').hide();
  $('input[name=importFolder]').before('<input style="margin-left:0px;" id="browseMIDASLink" class="globalButton" type="button" value="Select location" />');
  $('input[name=importFolder]').before('<span style="margin-left:5px;" id="destinationUpload"/>');
  $('#browseMIDASLink').click(function()
    {
    loadDialog("select","/browse/movecopy/?selectElement=true");
    showDialog('Browse');
    });
 });


/** On import serialize */
function importSerialize(form, options) 
{
  if(stage == 'validate')
    {
    options.data = {validate: '1'};
    }
}

/** On import submit */
function importSubmit(formData, jqForm, options)  
{ 	
  uploadid = formData[0].value;	
  if(stage == 'upload')
    {
    checkProgress(uploadid);
    }
} 

/** On import success */
function importCallback(responseText, statusText, xhr, form)  
{ 
  if(responseText.stage == 'validate')
  	{
  	stage = 'initialize';
  	$("#progress_status").html('Counting files (this could take some time)');  
  	formSubmitOptions.data = {initialize: '1'};
  	$("#importsubmit").html($("#importstop").val());
  	$('#importForm').ajaxSubmit(formSubmitOptions);
  	}
  else if(responseText.stage == 'initialize')
  	{
  	stage = 'upload';
  	formSubmitOptions.data = {totalfiles: responseText.totalfiles};
  	$('#importForm').ajaxSubmit(formSubmitOptions);
  	}
  else if(responseText.error)
  	{
  	stage = 'validate';  // goes back to the validate stage  
  	$("#importsubmit").html(importSubmitButtonValue);	  
  	$(".viewNotice").html(responseText.error);  
  	$(".viewNotice").fadeIn(100).delay(2000).fadeOut(300);
  	}
  else if(responseText.message)
  	{
  	stage = 'validate';	// goes back to the validate stage	
  	$("#progress_status").html('Import done');
  	$("#progress").progressbar("value",100);
  	$(".viewNotice").html(responseText.message);    
  	$(".viewNotice").fadeIn(100).delay(2000).fadeOut(300);
  	$('#importsubmit').html(importSubmitButtonValue);
  	}
} 

/** If the button to start/stop the import has been clicked */
function startImport()
{
  if(stage == 'validate')
  	{
  	formSubmitOptions = {success:importCallback, 
  		                   beforeSerialize: importSerialize,
  			                 beforeSubmit: importSubmit,
  			                 dataType:'json',
  	                     }; 
  	$('#importForm').ajaxSubmit(formSubmitOptions);
  	}
  else // stop the import
  	{
    stage = 'validate'; // goes back to the validate stage  
    $.get($('.webroot').val()+'/import/stop?id='+uploadid, function(data) 
      {
  	  $(".viewNotice").html('Import has been stopped.');    
        $(".viewNotice").fadeIn(100).delay(2000).fadeOut(300); 
  	  });
  	}
}

/** On assetstore add submit */
function assetstoreSubmit(formData, jqForm, options)  
{
  // Add the type is the one in the main page (because it's hidden in the assetstore add page)
  var assetstoretype = new Object();
  assetstoretype.name = 'assetstoretype';
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

/** When the input directory is changed */
function inputdirectoryChanged()
{
  // Set the assetstore name as the basename
  var basename = $('#inputdirectory').val().replace(/^.*[\/\\]/g, '')
  if(basename.length == 0) // if the last char is / or \
    {
    basename = $('#inputdirectory').val().substr(0,$('#inputdirectory').val().length-1).replace(/^.*[\/\\]/g, '')  
    }
  $("#assetstorename").val(basename);
  
  // set the input directory as the same
  $("#assetstoreinputdirectory").val($('#inputdirectory').val());
} // end function inputdirectoryChanged

/** When the assetstore type list is changed */
function assetstoretypeChanged()
{
  var assetstoretype = $('select#importassetstoretype option:selected').val();

  // Set the same assetstore type for the new assetstore
  $('#assetstoretype option:selected').removeAttr("selected");
  $('#assetstoretype option[value='+assetstoretype+']').attr("selected", "selected");
 
  // Clean the assetstore list
  $("select#assetstore").find('option:not(:first)').remove();
  
  for(var i=0;i<assetstores.length;i++)
	  {
	  if(assetstores[i].type == assetstoretype)
      {
	    $("select#assetstore").append($("<option></option>").
	      attr("value",assetstores[i].assetstore_id).
	      text(assetstores[i].name)); 
      }
	  }  
}  // end function assetstoretypeChanged()

/** Check the progress of the import */
function checkProgress(id)
{
  if(stage == 'validate')
    {
    return false;
    }
  
  $.ajax({
    type: "GET",
    url: $('.webroot').val()+'/import/getprogress?id='+id,
    dataType: 'json',
    timeout: 10000000000,
    success: function(html){
      if(html)
        {   
    	if(html.percent != 'NA')
          {
    	  $("#progress").show();
    	  $("#progress_status").show();
    				
          $("#progress_status").html('Importing files '+html.current+'/'+html.max+' ('+html.percent+'%)');
          $("#progress").progressbar("value",html.percent);
    	  }	
    	}   
      window.setTimeout("checkProgress("+id+")",3000); // every 3s should be enough
      },
    error: function(XMLHttpRequest, textStatus, errorThrown){
      alert(textStatus);
      alert(errorThrown);
    }
  });
}  // end function checkProgress  


