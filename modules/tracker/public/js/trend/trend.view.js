var midas = midas || {};
midas.tracker = midas.tracker || {};

/**
 * Extract the jqplot curve data from the scalar daos passed to us
 */
midas.tracker.extractCurveData = function (scalars) {
    var points = [], minVal, maxVal;
    $.each(scalars, function(idx, scalar) {
        var value = parseFloat(scalar.value);
        points.push([scalar.submit_time, value]);
        if(typeof minVal == 'undefined' || value < minVal) {
            minVal = value;
        }
        if(typeof maxVal == 'undefined' || value > maxVal) {
            maxVal = value;
        }
    });
    return {
        points: points,
        minVal: minVal,
        maxVal: maxVal
    };
};

/**
 * Fill in the "info" sidebar section based on the curve data
 */
midas.tracker.populateInfo = function (curveData) {
    $('#pointCount').html(curveData.points.length);
    $('#minVal').html(curveData.minVal);
    $('#maxVal').html(curveData.maxVal);
};

midas.tracker.bindPlotEvents = function () {
    $('#chartDiv').unbind('jqplotDataClick').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
        var scalarId = json.tracker.scalars[pointIndex].scalar_id;
        midas.loadDialog('scalarPoint'+scalarId, '/tracker/scalar/details?scalarId='+scalarId);
        midas.showDialog('Scalar details', false);
    });
};

midas.tracker.renderChartArea = function (curveData, first) {
    if(midas.tracker.plot) {
        midas.tracker.plot.destroy();
    }
    if(curveData.points.length > 0) {
        $('#chartDiv').html('');
        midas.tracker.plot = $.jqplot('chartDiv', [curveData.points], {
            axes: {
                xaxis: {
                    pad: 1.05,
                    renderer: $.jqplot.DateAxisRenderer,
                    tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                    tickOptions: {
                        formatString: "%Y-%m-%d",
                        angle: 270,
                        fontSize: '11px',
                        labelPosition: 'middle'
                    }

                },
                yaxis: {
                    pad: 1.05,
                    label: midas.tracker.yaxisLabel,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    labelOptions: {
                        angle: 270,
                        fontSize: '12px'
                    }
                }
            },
            highlighter: {
                show: true,
                showTooltip: true
            },
            cursor: {
                show: true,
                zoom: true,
                showTooltip: false
            }
        });
        midas.tracker.bindPlotEvents();

        $('a.resetZoomAction').unbind('click').click(function () {
            midas.tracker.plot.resetZoom();
        });
    }
    else {
        $('#chartDiv').html('<span class="noPoints">There are no values for this trend in the specified date range.</span>');
    }
    if(first) {
        $.jqplot.postDrawHooks.push(midas.tracker.bindPlotEvents); //must re-bind data click each time we redraw
    }
    midas.tracker.populateInfo(curveData);
};

$(window).load(function () {
    var curveData = midas.tracker.extractCurveData(json.tracker.scalars);
    midas.tracker.yaxisLabel = json.tracker.trend.display_name;
    if(json.tracker.trend.unit) {
        midas.tracker.yaxisLabel += ' ('+json.tracker.trend.unit+')';
    }

    var dates = $("#startdate, #enddate").datepicker({
        defaultDate: "today",
        changeMonth: true,
        numberOfMonths: 1,
        onSelect: function(selectedDate) {
          var option = this.id == "startdate" ? "minDate" : "maxDate";
          var instance = $(this).data("datepicker");
          var date = $.datepicker.parseDate(
            instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
            selectedDate, instance.settings);
          dates.not(this).datepicker("option", option, date);
          },
        dayNamesMin: ["S", "M", "T", "W", "T", "F", "S"]
    });
    $('#startdate').val(json.tracker.initialStartDate);
    $('#enddate').val(json.tracker.initialEndDate);
    $('#filterButton').click(function () {
        $(this).attr('disabled', 'disabled');
        $('#dateRangeUpdating').show();
        $.post(json.global.webroot+'/tracker/trend/scalars', {
            trendId: json.tracker.trend.trend_id,
            startDate: $('#startdate').val(),
            endDate: $('#enddate').val()
        }, function (retVal) {
            var resp = $.parseJSON(retVal);
            json.tracker.scalars = resp.scalars;
            midas.tracker.renderChartArea(midas.tracker.extractCurveData(json.tracker.scalars), false);
            $('#filterButton').removeAttr('disabled');
            $('#dateRangeUpdating').hide();
        });
    });

    midas.tracker.renderChartArea(curveData, true);

    $('a.thresholdAction').click(function () {
        midas.loadDialog('thresholdNotification', '/tracker/trend/notify?trendId='+json.tracker.trend.trend_id);
        midas.showDialog('Email notification settings', false);
    });
});
