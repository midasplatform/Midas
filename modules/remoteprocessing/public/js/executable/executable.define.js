  var jsonMetadata;
  $(document).ready(function() {
    jsonMetadata = jQuery.parseJSON($('div#jsonMetadataContent').html());

    jQuery.each(jsonMetadata.options.option, function(i, val) {
        var parameters = new Array()
        parameters['name'] = this.name;
        parameters['tag'] = this.tag;
        parameters['required'] = this.required;
        if(this.channel == 'ouput')
          {
          parameters['type'] = 'ouputFile';
          }
        else if(this.field.external == 1)
          {
          parameters['type'] = 'inputFile';
          }
        else
          {
          parameters['type'] = 'inputParam';
          parameters['typeParam'] = this.field.type;
          }
        parameters['tag']
        addElement(parameters)

       });
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
             url: "",
             data: req ,
             success: function(x){
               window.location.replace($('.webroot').val()+'/item/'+json.item.item_id)
             }
           });
    });
  });

function initMetadataForm()
  {

  }

function addElement(paramaters)
  {
  var html = '<div class="portlet">';
  if(paramaters['name'].length == 0)
    {
    html += '<div class="portlet-header">New Variable</div>';
    }
  else
    {
    html += '<div class="portlet-header">'+paramaters['name']+'</div>';
    }
  html += '<div class="portlet-content">';
  html += ' Name: <input type="text" class="definitionName" value="'+paramaters['name']+'"/>';
  html += ' Tag (Optional): <input type="text" class="definitionTag" />';

  html += ' <br/>Type: <select class="definitionType">';
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
    $(this).parents('div.portlet').find('.portlet-header').html($(this).val());
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
			connectWith: ".column"
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
  }
