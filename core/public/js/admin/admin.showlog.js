var priorityMap = { 2 : 'critical', 4: 'warning', 6: 'info' };

jsonLogs = jQuery.parseJSON($('div#jsonLogs').html());

initLogs();
$('table#listLogs').tablesorter({widgets: ['zebra']});

function initLogs()
{
  $('table#listLogs').hide();
  $('.logsLoading').show();
  $('table#listLogs tr.logSum').remove();
  $('table#listLogs tr.logDetail').remove();
  var i = 1;
  $.each(jsonLogs, function(index, value) {
    var stripeClass = i % 2 ? 'odd' : 'even';
    i++;
    var html='';
    html+='<tr class="logSum '+stripeClass+'">';
    html+=' <td>'+value.datetime+'</td>';
    html+=' <td>'+priorityMap[value.priority]+'</td>';
    html+=' <td>'+value.module+'</td>';
    html+=' <td>'+value.shortMessage+'<div style="display:none;"><pre>'+value.message+'</pre></div></td>';
    html+='</tr>';
    html+='<tr class="logDetail" style="display:none;">';
    html+=' <td colspan="4"><pre>'+value.message+'</pre></td>';
    html+='</tr>';
    $('table#listLogs').append(html);
    });
  $('table#listLogs').show();
  $('table#listLogs').trigger('update');
  
  $('.logsLoading').hide();

  $('table#listLogs tr.logSum').click(function() {
    showBigDialogWithContent('Log', $(this).find('div').html(), true);
    });
}

var dates = $("#startlog, #endlog").datepicker({
  defaultDate: "-1w",
  changeMonth: true,
  numberOfMonths: 1,
  onSelect: function(selectedDate) {
    var option = this.id == "startlog" ? "minDate" : "maxDate";
    var instance = $(this).data("datepicker");
    var date = $.datepicker.parseDate(
      instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
      selectedDate, instance.settings);
    dates.not( this ).datepicker("option", option, date);
    },
  dayNamesMin: ["S", "M", "T", "W", "T", "F", "S"]
  });

$('#logSelector').ajaxForm( {beforeSubmit: validateShowlog, success: successShowlog} );

function validateShowlog(formData, jqForm, options)
{
  $('table#listLogs').hide();
  $('.logsLoading').show();
}

function successShowlog(responseText, statusText, xhr, form)
{
  try
    {
    var resp = jQuery.parseJSON(responseText);
    jsonLogs = resp.logs;
    $('#currentFilterStart').html(resp.currentFilter.start);
    $('#currentFilterEnd').html(resp.currentFilter.end);
    $('#currentFilterModule').html(resp.currentFilter.module);
    $('#currentFilterPriority').html(priorityMap[resp.currentFilter.priority]);
    }
  catch(e)
    {
    console.log(e);
    alert("An error occured.");
    return false;
    }
  initLogs();
}
