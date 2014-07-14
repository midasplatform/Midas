// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.errorlog = midas.errorlog || {};

midas.errorlog.pageOffset = 0;
midas.errorlog.PAGE_LIMIT = 100;

// Fill the log table
midas.errorlog.initLogs = function () {
    $('table#listLogs').hide();
    $('.logsLoading').show();
    $('table#listLogs tr.logSum').remove();
    $('table#listLogs tr.logDetail').remove();
    $('#fullLogMessages div').remove();
    $('#selectAllCheckbox').removeAttr('checked');

    var i = 0;
    $.each(midas.errorlog.jsonLogs.logs, function (index, value) {
        i++;
        var stripeClass = i % 2 ? 'odd' : 'even';
        var html = '';
        html += '<tr id="logRow' + value.errorlog_id + '" class="logSum ' + stripeClass + '">';
        html += ' <td><input class="logSelect" name="' + value.errorlog_id + '" type="checkbox" id="logSelect' + value.errorlog_id + '" /></td>';
        html += ' <td>' + value.datetime + '</td>';
        html += ' <td>' + midas.errorlog.priorityMap[value.priority] + '</td>';
        html += ' <td>' + value.module + '</td>';
        html += ' <td class="logMessage" name="' + value.errorlog_id + '">' + value.shortMessage + '</td>';
        html += '</tr>';
        html += '<tr class="logDetail" style="display:none;">';
        html += ' <td colspan="4"><pre>' + value.message + '</pre></td>';
        html += '</tr>';
        var messageHtml = '<div id="fullMessage' + value.errorlog_id + '"><pre>' + value.message + '</pre></div>';
        $('table#listLogs').append(html);
        $('#fullLogMessages').append(messageHtml);
    });
    $('table#listLogs').show();
    $('.logsLoading').hide();
    if (midas.errorlog.jsonLogs.total == 0) {
        $('#paginationMessage').html('No results');
    }
    else {
        $('#paginationMessage').html('Showing entries ' + (midas.errorlog.pageOffset + 1) + ' - ' +
            (midas.errorlog.pageOffset + midas.errorlog.jsonLogs.logs.length) +
            ' of ' + midas.errorlog.jsonLogs.total);
    }
    if (midas.errorlog.pageOffset + midas.errorlog.PAGE_LIMIT < midas.errorlog.jsonLogs.total) {
        $('#errorlogNextPage').removeAttr('disabled');
    }
    else {
        $('#errorlogNextPage').attr('disabled', 'disabled');
    }
    if (midas.errorlog.pageOffset >= midas.errorlog.PAGE_LIMIT) {
        $('#errorlogPreviousPage').removeAttr('disabled');
    }
    else {
        $('#errorlogPreviousPage').attr('disabled', 'disabled');
    }

    if (i > 0) {
        $('table#listLogs').trigger('update');
        $('input.logSelect').enableCheckboxRangeSelection();

        $('table#listLogs tr.logSum td.logMessage').click(function () {
            var id = $(this).attr('name');
            midas.showDialogWithContent('Log', $('#fullMessage' + id).html(), true, {
                width: 630,
                height: 500
            });
        });
    }
}

midas.errorlog.validateShowlog = function (formData, jqForm, options) {
    $('table#listLogs').hide();
    $('.logsLoading').show();
}

midas.errorlog.successShowlog = function (responseText, statusText, xhr, form) {
    try {
        var resp = $.parseJSON(responseText);
        midas.errorlog.jsonLogs = resp;
        $('#currentFilterStart').html(resp.currentFilter.start);
        $('#currentFilterEnd').html(resp.currentFilter.end);
        $('#currentFilterModule').html(resp.currentFilter.module);
        $('#currentFilterPriority').html(midas.errorlog.operationMap[resp.currentFilter.priorityOperator] + ' ' + midas.errorlog.priorityMap[resp.currentFilter.priority]);
    }
    catch (e) {
        alert("An error occured: " + responseText);
        return false;
    }
    midas.errorlog.initLogs();
}

midas.errorlog.fetchNextPage = function () {
    if (midas.errorlog.pageOffset + midas.errorlog.PAGE_LIMIT < midas.errorlog.jsonLogs.total) {
        midas.errorlog.pageOffset += midas.errorlog.PAGE_LIMIT;
        $('#errorlogOffset').val(midas.errorlog.pageOffset);
        $('#logSelector').submit();
    }
}

midas.errorlog.fetchPreviousPage = function () {
    if (midas.errorlog.pageOffset >= midas.errorlog.PAGE_LIMIT) {
        midas.errorlog.pageOffset -= midas.errorlog.PAGE_LIMIT;
        $('#errorlogOffset').val(midas.errorlog.pageOffset);
        $('#logSelector').submit();
    }
}

midas.errorlog.applyFilter = function () {
    // reset offset to 0, as user has clicked apply and
    // desires a new result set, which should start at page 0
    midas.errorlog.pageOffset = 0;
    $('#errorlogOffset').val(0);
}

$(document).ready(function () {
    $('#errorlogOffset').val(midas.errorlog.pageOffset);
    $('#errorlogPageLimit').val(midas.errorlog.PAGE_LIMIT);
    $('#errorlogPreviousPage').click(midas.errorlog.fetchPreviousPage);
    $('#errorlogNextPage').click(midas.errorlog.fetchNextPage);
    $('#applyFilter').click(midas.errorlog.applyFilter);

    // Log priority enum
    midas.errorlog.operationMap = {
        '<=': '>=',
        '=': '=='
    };
    midas.errorlog.priorityMap = {
        2: 'critical',
        4: 'warning',
        6: 'info'
    };

    midas.errorlog.jsonLogs = $.parseJSON($('div#jsonLogs').html());
    midas.errorlog.initLogs();

    // Set up smart date picker widget logic
    var dates = $("#startlog, #endlog").datepicker({
        defaultDate: "-1w",
        changeMonth: true,
        numberOfMonths: 1,
        onSelect: function (selectedDate) {
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
    $('#logSelector').ajaxForm({
        beforeSubmit: midas.errorlog.validateShowlog,
        success: midas.errorlog.successShowlog
    });

    // Set up sortable table
    $('table#listLogs').tablesorter({
        headers: {
            0: {
                sorter: false
            }, // checkbox column
            4: {
                sorter: false
            } // log message column
        }
    }).bind('sortEnd', function () {
        $('input.logSelect').enableCheckboxRangeSelection();
    });

    // Select/deslect all entries action
    $('#selectAllCheckbox').click(function () {
        $('input.logSelect').prop("checked", this.checked);
    });

    // Delete selected entries action
    $('button#deleteSelected').click(function () {
        var selected = '';
        $('input.logSelect:checked').each(function (index) {
            var id = $(this).attr('name');
            selected += id + ',';
            $('#logRow' + id).remove();
        });
        if (selected != '') {
            $('table#listLogs').trigger('update');
            midas.ajaxSelectRequest = $.ajax({
                type: 'POST',
                url: json.global.webroot + '/admin/deletelog',
                dataType: 'json',
                data: {
                    idList: selected
                },
                success: function (resp) {
                    midas.createNotice(resp.message, 3500);
                    midas.errorlog.pageOffset = 0;
                    $('#logSelector').submit();
                }
            });
        }
    });
});
