var plotArray = new Array();

$(document).ready(function(){
  $( "#tabsGeneric" ).tabs({
      });
    $("#tabsGeneric").show();
    $("#tabsGeneric").bind('tabsshow', function(event, ui) {
      $.each(plotArray, function(index, value) {
          if (value._drawCount == 0) {
          value.replot();
          }
      });
    });

    $.each(json.workflow.metrics, function(index, values) {
          jQuery.each(values, function(i, val) {
            values[i][1] = parseFloat(values[i][1]);
          });

       var plotErrors = $.jqplot('chart-'+index, [values], {
        title: index,
        gridPadding:{right:35},
        axes:{
          xaxis:{
            renderer:$.jqplot.DateAxisRenderer,
            tickOptions:{formatString:'%b %#d'},
            tickInterval:'1 day'
          }
        },
        series:[{lineWidth:4, markerOptions:{style:'square'}}]
      });

      plotArray.push(plotErrors);
  });
  $('#workflowForm').ajaxForm( {beforeSubmit: validateInfoChange, success:       successInfoChange} );

});

function validateInfoChange(formData, jqForm, options) {

    var form = jqForm[0];
    form = $('#'+form.getAttribute('id'));
    if (form.find('#workflowName').val().length<1)
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
      $('div.viewHeader').html("Workflow: "+jsonResponse[2]);
      createNotive(jsonResponse[1],4000);
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}


function jdotClickHandler(w, s)
  {
  if(w.isNode)console.log(w.name);
	};
