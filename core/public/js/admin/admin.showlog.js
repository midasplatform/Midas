var priorityMap = { 2 : 'critical', 4: 'warning', 6: 'info' };

jsonLogs = jQuery.parseJSON($('div#jsonLogs').html());

initLogs();

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

$('table#listLogs').tablesorter({
  headers: {
    0: {sorter: false}, //checkbox column
    4: {sorter: false}  //log message column
    } 
  }).bind('sortEnd', function() {
    $('input.logSelect').enableCheckboxRangeSelection();
  });

$('#selectAllCheckbox').click(function() {
  $('input.logSelect').prop("checked", this.checked);
  });

function initLogs()
{
  $('table#listLogs').hide();
  $('.logsLoading').show();
  $('table#listLogs tr.logSum').remove();
  $('table#listLogs tr.logDetail').remove();
  $('#fullLogMessages div').remove();

  var i = 1;
  $.each(jsonLogs, function(index, value) {
    var stripeClass = i % 2 ? 'odd' : 'even';
    i++;
    var html='';
    html+='<tr class="logSum '+stripeClass+'">';
    html+=' <td><input class="logSelect" type="checkbox" id="logSelect'+value.errorlog_id+'" /></td>';
    html+=' <td>'+value.datetime+'</td>';
    html+=' <td>'+priorityMap[value.priority]+'</td>';
    html+=' <td>'+value.module+'</td>';
    html+=' <td class="logMessage" name="'+value.errorlog_id+'">'+value.shortMessage+'</td>';
    html+='</tr>';
    html+='<tr class="logDetail" style="display:none;">';
    html+=' <td colspan="4"><pre>'+value.message+'</pre></td>';
    html+='</tr>';
    var messageHtml = '<div id="fullMessage'+value.errorlog_id+'"><pre>'+value.message+'</pre></div>';
    $('table#listLogs').append(html);
    $('#fullLogMessages').append(messageHtml);
    });
  $('table#listLogs').show();
  $('.logsLoading').hide();
  $('table#listLogs').trigger('update');
  $('input.logSelect').enableCheckboxRangeSelection();

  $('table#listLogs tr.logSum td.logMessage').click(function() {
    var id = $(this).attr('name');
    showBigDialogWithContent('Log', $('#fullMessage'+id).html(), true);
    });
}

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
