var midas = midas || {};
// Log priority enum
var priorityMap = { 2 : 'critical', 4: 'warning', 6: 'info' };

jsonLogs = jQuery.parseJSON($('div#jsonLogs').html());
initLogs();

// Set up smart date picker widget logic
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
    dates.not(this).datepicker("option", option, date);
    },
  dayNamesMin: ["S", "M", "T", "W", "T", "F", "S"]
  });

// Ajax form for log filtering
$('#logSelector').ajaxForm( {beforeSubmit: validateShowlog, success: successShowlog} );

// Set up sortable table
$('table#listLogs').tablesorter({
  headers: {
    0: {sorter: false}, //checkbox column
    4: {sorter: false}  //log message column
    }
  }).bind('sortEnd', function() {
    $('input.logSelect').enableCheckboxRangeSelection();
  });
// Remove dummy row (it's required to instantiate the tablesorter properly
$('tr#dummy').remove();

// Select/deslect all entries action
$('#selectAllCheckbox').click(function() {
  $('input.logSelect').prop("checked", this.checked);
  });

// Delete selected entries action
$('button#deleteSelected').click(function() {
  var selected = '';
  $('input.logSelect:checked').each(function(index) {
    var id = $(this).attr('name');
    selected += id + ',';
    $('#logRow'+id).remove();
    });
  if(selected != '')
    {
    $('table#listLogs').trigger('update');
    midas.ajaxSelectRequest = $.ajax({
      type: 'POST',
      url: json.global.webroot+'/admin/deletelog',
      dataType: 'json',
      data: {idList: selected},
      success: function(resp) {
        createNotive(resp.message, 3500);
        },
      });
    }
  });

// Fill the log table
function initLogs()
{
  $('table#listLogs').hide();
  $('.logsLoading').show();
  $('table#listLogs tr.logSum').remove();
  $('table#listLogs tr.logDetail').remove();
  $('#fullLogMessages div').remove();
  $('#selectAllCheckbox').removeAttr('checked');

  var i = 0;
  $.each(jsonLogs, function(index, value) {
    i++;
    var stripeClass = i % 2 ? 'odd' : 'even';
    var html='';
    html+='<tr id="logRow'+value.errorlog_id+'" class="logSum '+stripeClass+'">';
    html+=' <td><input class="logSelect" name="'+value.errorlog_id+'" type="checkbox" id="logSelect'+value.errorlog_id+'" /></td>';
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

  if(i > 0)
    {
    $('table#listLogs').trigger('update');
    $('input.logSelect').enableCheckboxRangeSelection();

    $('table#listLogs tr.logSum td.logMessage').click(function() {
      var id = $(this).attr('name');
      showBigDialogWithContent('Log', $('#fullMessage'+id).html(), true);
      });
    }
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
    alert("An error occured: " + reponseText);
    return false;
    }
  initLogs();
}
