var midas = midas || {};
  $(document).ready(function() {

    tabs=$( "#tabsGeneric" ).tabs({
      });
    $("#tabsGeneric").show();
    $('img.tabsLoading').hide()

    $('a.defaultAssetstoreLink').click(function(){
      $.post(json.global.webroot+'/assetstore/defaultassetstore', {submitDefaultAssetstore: true, element: $(this).attr('element')},
         function(data) {
             jsonResponse = jQuery.parseJSON(data);
              if(jsonResponse==null)
                {
                  createNotive('Error',4000);
                  return;
                }
              createNotive(jsonResponse[1],1500);
              window.location.replace(json.global.webroot+'/admin#tabs-assetstore');
              window.location.reload();
         });
    });   

    $('a.removeAssetstoreLink').click(function()
      {
      var element = $(this).attr('element');
      var html = '';
      html += 'Do you really want to remove the assetstore? All the items located in it will be deleted. (Can take a while)';
      html += '<br/>';
      html += '<br/>';
      html += '<input style="margin-left:140px;" class="globalButton deleteAssetstoreYes" element="'+element+'" type="button" value="'+json.global.Yes+'"/>';
      html += '<input style="margin-left:50px;" class="globalButton deleteAssetstoreNo" type="button" value="'+json.global.No+'"/>';
      showDialogWithContent('Remove Assetstore', html,false);

      $('input.deleteAssetstoreYes').unbind('click').click(function()
         {
          $( "div.MainDialog" ).dialog('close');
          midas.ajaxSelectRequest = $.ajax({
            type: "POST",
            url: json.global.webroot+'/assetstore/delete',
            data: {assetstoreId: element},
            success: function(jsonContent){
              jsonResponse = jQuery.parseJSON(jsonContent);
              createNotive(jsonResponse[1],1500);
              if(jsonResponse[0])
                {
                window.location.replace(json.global.webroot+'/admin#tabs-assetstore');
                window.location.reload();
                }
            }
          });
         });
        $('input.deleteAssetstoreNo').unbind('click').click(function()
          {
          $( "div.MainDialog" ).dialog('close');
          });
      });

    $('a.editAssetstoreLink').click(function()
      {
      var element = $(this).attr('element');
      var html = '';
      html += '<form class="genericForm" onsubmit="false;">';
      html += '<label>Name:</label> <input type="text" id="assetstoreName" value="'+$(this).parents('div').find('span.assetstoreName').html()+'"/><br/><br/>';
      html += '<label>Path:</label> <input type="text" id="assetstorePath" value="'+$(this).parents('div').find('span.assetstorePath').html()+'"/>';
      html += '<br/>';
      html += '<br/>';
      html += '<input type="submit" id="assetstoreSubmit" value="Save"/>';
      html += '</form>';
      html += '<br/>';
      showDialogWithContent('Edit Assetstore', html,false);

      $('input#assetstoreSubmit').unbind('click').click(function()
         {
           midas.ajaxSelectRequest = $.ajax({
            type: "POST",
            url: json.global.webroot+'/assetstore/edit',
            data: {assetstoreId: element, assetstoreName: $('input#assetstoreName').val(), assetstorePath: $('input#assetstorePath').val()},
            success: function(jsonContent){
              jsonResponse = jQuery.parseJSON(jsonContent);
              if(jsonResponse[0])
                {
                createNotive(jsonResponse[1],1500);
                window.location.replace(json.global.webroot+'/admin#tabs-assetstore');
                window.location.reload();
                }
              else
                {
                createNotive(jsonResponse[1],4000);
                }
            }
          });
         });
        $('input.deleteAssetstoreNo').unbind('click').click(function()
          {
          $( "div.MainDialog" ).dialog('close');
          });
      });

    $('#configForm').ajaxForm( {beforeSubmit: validateConfig, success: successConfig} );

      // Form for the new assetstore
    options = {success:assetstoreAddCallback, beforeSubmit:  assetstoreSubmit,  dataType:'json'};
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
             var dependencies = $(this).attr('dependencies');
             dependencies=dependencies.split(',');
             $.each(dependencies, function(i, l){
                if(l != '')
                 {
                 if(!$('input[module='+l+']').is(':checked'))
                   {
                    $.post(json.global.webroot+'/admin/index', {submitModule: true, modulename: l , modulevalue:modulevalue});
                    createNotive("Dependancy: Enabling module "+l,1500);
                   }
                 $('input[module='+l+']').attr('checked',true);
                 }
               });
             }
           else
             {
             modulevalue='false';
             var moduleDependencies = new Array();
             $.each($('input[dependencies='+$(this).attr('module')+']:checked'),function(){
               moduleDependencies.push($(this).attr('module'));
             });
             $.each($('input[dependencies*=",'+$(this).attr('module')+'"]:checked'),function(){
               moduleDependencies.push($(this).attr('module'));
             });
             $.each($('input[dependencies*="'+$(this).attr('module')+',"]:checked'),function(){
               moduleDependencies.push($(this).attr('module'));
             });
             var found = false;

             var mainModule = $(this).attr('module');

             $.each(moduleDependencies, function(i, l){
                var module = l;
                if(module != '')
                 {
                 found =true;
                 createNotive("Dependancy: The module "+module+" requires "+mainModule+". Please, disable it first.",3500);
                 }
               });
             if(found)
               {
               $(this).attr('checked',true);
               return;
               }
             }


           $.post(json.global.webroot+'/admin/index', {submitModule: true, modulename: $(this).attr('module') , modulevalue:modulevalue},
           function(data) {
               jsonResponse = jQuery.parseJSON(data);
                if(jsonResponse==null)
                  {
                    createNotive('Error',4000);
                    return;
                  }
                createNotive(jsonResponse[1],3500);
                initModulesConfigLinks();
           });
     });

     $('a.moduleVisibleCategoryLink').click(function(){
       if($(this).prev('span').html() == '&gt;')
         {
         $(this).prev('span').html('v');
         $('.'+$(this).html()+'VisibleElement').show();
         }
       else
         {
         $(this).prev('span').html('>');
         $('.'+$(this).html()+'VisibleElement').hide();
         }
     });

     $('a.moduleHiddenCategoryLink').click(function(){
       if($(this).prev('span').html() == '&gt;')
         {
         $(this).prev('span').html('v');
         $('.'+$(this).html()+'HiddenElement').show();
         }
       else
         {
         $(this).prev('span').html('>');
         $('.'+$(this).html()+'HiddenElement').hide();
         }
     });
   initModulesConfigLinks();
  });

  var tabs;

function initModulesConfigLinks()
{
  $('input.moduleCheckbox').each(function(){
    if($(this).is(':checked'))
      {
      $(this).parents('tr').find('td.configLink').show();
      }
    else
      {
      $(this).parents('tr').find('td.configLink').hide();
      }
  })

}

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
      window.location.replace(json.global.webroot+'/admin#tabs-assetstore');
      window.location.reload();
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