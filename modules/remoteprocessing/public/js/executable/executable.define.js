  var jsonMetadata;
  $(document).ready(function() {
    jsonMetadata = jQuery.parseJSON($('div#jsonMetadataContent').html());

    if(jsonMetadata.options != undefined)
      {
      if(jsonMetadata.options.option.field == undefined)
        {
        jQuery.each(jsonMetadata.options.option, function(i, val) {
           initOption(this)
           });
        }
      else
        {
        initOption(jsonMetadata.options.option)
        }
      }
    updateSortableElement();

    $('a#addDefinitionLink').click(function()
    {
      var parameters = new Array();
      parameters['name'] = '';
      addElement(parameters);

      $('.portlet:last .definitionName').focus();
    });

    $('a#saveDefinitionLink').click(function(){
      var results = new Array;
      $('.portlet-content').each(function(){
        var tmp = '';
        if($(this).find('.definitionName').val() != '')
          {
          tmp+= $(this).find('.definitionName').val()+';';
          tmp+= $(this).find('.definitionType').val()+';';
          tmp+= $(this).find('.definitionTypeParam').val()+';';
          tmp+= $(this).find('.definitionName').val()+';';
          if( $(this).find('.definitionRequired').is(':checked'))
            {
            tmp+= 'True;';
            }
          else
            {
            tmp+= 'False;';
            }
          tmp+= $(this).find('.definitionTag').val();
          results.push(tmp);
          }
      });
      req = { 'results[]' : results};
      $(this).after('<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Saving..." />')
      $(this).remove();
      $.ajax({
             type: "POST",
             url: json.global.webroot+"/remoteprocessing/executable/define?itemId="+$('#itemIdExecutable').val(),
             data: req ,
             success: function(x){
               if(typeof(isDefineAjax) === 'undefined' || !isDefineAjax)
                 {
                  window.location.replace($('.webroot').val()+'/item/'+json.item.item_id)
                 }
               else
                 {
                 $( "div.MainDialog" ).dialog("close");
                 $('#metaWrapper').hide();
                 $('#metaPageBlock').html('');
                 isExecutableMeta = true;
                 }
             }
           });
    });
  });


function initOption(option)
  {
  var parameters = new Array()
  parameters['name'] = option.name;
  parameters['tag'] = option.tag;
  parameters['required'] = option.required;
  if(option.channel == 'ouput')
    {
    parameters['type'] = 'ouputFile';
    }
  else if(option.field.external == 1)
    {
    parameters['type'] = 'inputFile';
    }
  else
    {
    parameters['type'] = 'inputParam';
    parameters['typeParam'] = option.field.type;
    }
  addElement(parameters)
  }

function addElement(paramaters)
  {
  var html = '<div class="portlet">';
  if(paramaters['name'].length == 0)
    {
    html += '<div class="portlet-header" qtip="Drag the element to change the order."><span class="optionName">New Variable</span><img class="deleteOptionLink" qtip="Remove the option."    src="'+json.global.webroot+'/core/public/images/icons/close.png" alt="Delete" /></div>';
    }
  else
    {
    html += '<div class="portlet-header" qtip="Drag the element to change the order."><span class="optionName">'+paramaters['name']+'</span><img  qtip="Remove the option."  class="deleteOptionLink"  src="'+json.global.webroot+'/core/public/images/icons/close.png" alt="Delete" /></div>';
    }
  html += '<div class="portlet-content">';
  html += ' Name: <input type="text" class="definitionName" value="'+paramaters['name']+'"/>';
  html += ' Tag (Optional): <input type="text" class="definitionTag" qtip="Example: --help" />';

  html += ' <br/>Type: <select class="definitionType" >';
  html += ' <option value="inputFile">Input File</option>';
  html += ' <option value="inputParam">Input Parameter</option>';
  html += ' <option value="ouputFile">Ouput File</option>';
  html += ' </select>';

  html += ' Required: <input type="checkbox" class="definitionRequired" checked />';

  html += ' <div class="inputParameterForm">';
  html += ' Type Parameter: <select class="definitionTypeParam">';
  html += ' <option value="string">String</option>';
  html += ' <option value="int">Integer</option>';
  html += ' <option value="float">Float</option>';
  html += ' <option value="tag">Tag only</option>';
  html += ' </select>';
  html += ' </div>';

  html += '</div>';
  html += '</div>';

  $('#tableContainer .column').append(html);
  $('.portlet:last .inputParameterForm').hide();

  $('.portlet:last .definitionName').keyup(function(){
    $(this).parents('div.portlet').find('.portlet-header .optionName').html($(this).val());
    });
  $('.portlet:last .definitionType').change(function(){
    if($(this).val() == 'inputParam')
      {
      $(this).parents('div.portlet').find('.inputParameterForm').show()
      }
    else
      {
      $(this).parents('div.portlet').find('.inputParameterForm').hide()
      }
    });

  $('.deleteOptionLink').click(function(){
    $(this).parents('div.portlet').remove();
    updateSortableElement();
  });

  if(paramaters['type'] != undefined)
    {
    $('.portlet:last .definitionType').val(paramaters['type']);
    }
  if(paramaters['typeParam'] != undefined)
    {
    $('.portlet:last .definitionTypeParam').val(paramaters['typeParam']);
    $('.portlet:last .inputParameterForm').show();
    }

  if(paramaters['required'] != undefined)
    {
    if(paramaters['required'] == 1)
      {
      $('.portlet:last .definitionRequired').attr('checked', 'checked');
      }
    else
      {
      $('.portlet:last .definitionRequired').removeAttr('checked');
      }
    }
  if(paramaters['tag'] != undefined)
    {
    $('.portlet:last .definitionTag').val(paramaters['tag']);
    }

  updateSortableElement();
  }

function updateSortableElement()
  {
		$( ".column" ).sortable({
			connectWith: ".column",
       handle: '.portlet-header'
		});

		$( ".portlet" ).addClass( "ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" )
			.find( ".portlet-header" )
				.addClass( "ui-widget-header ui-corner-all" )

				.end()
			.find( ".portlet-content" );

		$( ".portlet-header .ui-icon" ).click(function() {
			$( this ).toggleClass( "ui-icon-minusthick" ).toggleClass( "ui-icon-plusthick" );
			$( this ).parents( ".portlet:first" ).find( ".portlet-content" ).toggle();
		});

		$( ".column" ).disableSelection();


  $('[qtip]').qtip({
   content: {
      attr: 'qtip'
   }
   });
  }
