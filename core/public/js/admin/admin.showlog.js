jsonLogs = jQuery.parseJSON($('div#jsonLogs').html());

initLogs();

function initLogs()
{
  $('table#listLogs').hide();
  $('.logsLoading').show();
  $('table#listLogs tr.logSum').remove();
  $('table#listLogs tr.logDetail').remove();
  $.each(jsonLogs, function(index, value) {
  var html='';
  html+='<tr class="logSum">';
  html+=' <td>'+value.datetime+'</td>';
  if(value.priority==2)
    {
      html+=' <td><b>Critical</b></td>';
    }
  if(value.priority==4)
    {
      html+=' <td>Warning</td>';
    }
  if(value.priority==6)
    {
      html+=' <td>Info</td>';
    }
  html+=' <td>'+value.module+'</td>';
  html+=' <td>'+value.shortMessage+'</td>';
  html+='</tr>';
  html+='<tr class="logDetail" style="display:none;">';
  html+=' <td colspan="4"><pre>'+value.message+'</pre></td>';
  html+='</tr>';
  $('table#listLogs').append(html);
  });

  $('table#listLogs').show();
  $('.logsLoading').hide();

  $('table#listLogs tr.logSum').click(function()
  {
    showBigDialogWithContent('Log', $(this).next().html(),true);
  });
}

var dates = $( "#startlog, #endlog" ).datepicker({
			defaultDate: "-1w",
			changeMonth: true,
			numberOfMonths: 1,
			onSelect: function( selectedDate ) {
				var option = this.id == "startlog" ? "minDate" : "maxDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings );
				dates.not( this ).datepicker( "option", option, date );
			}
		});

$('#logSelector').ajaxForm( {beforeSubmit: validateShowlog, success: successShowlog} );

function validateShowlog(formData, jqForm, options) {
 $('table#listLogs').hide();
 $('.logsLoading').show();
}

function successShowlog(responseText, statusText, xhr, form)
{
  $('div#jsonLogs').html(responseText);

  try {
       jsonLogs = jQuery.parseJSON($('div#jsonLogs').html());
    } catch (e) {
      console.log(e);
      alert("An error occured.");
        return false;
    }
  initLogs();
}
