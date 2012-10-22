var midas = midas || {};
midas.tracker = midas.tracker || {};

/**
 * Extract the jqplot curve data from the scalar daos passed to us
 */
midas.tracker.extractCurveData = function (curves) {
    var allPoints = [], minVal, maxVal;
    $.each(curves, function(idx, scalars) {
        if(!scalars) {
            return;
        }
        var points = [];
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
        allPoints.push(points);
    });
    return {
        points: allPoints,
        minVal: minVal,
        maxVal: maxVal
    };
};

/**
 * Fill in the "info" sidebar section based on the curve data
 */
midas.tracker.populateInfo = function (curveData) {
    var count = curveData.points[0].length;
    if(json.tracker.rightTrend) {
        count += curveData.points[1].length;
    }
    $('#pointCount').html(count);
    $('#minVal').html(curveData.minVal);
    $('#maxVal').html(curveData.maxVal);
};

midas.tracker.bindPlotEvents = function () {
    $('#chartDiv').unbind('jqplotDataClick').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
        var scalarId;
        if(seriesIndex == 0) {
            scalarId = json.tracker.scalars[pointIndex].scalar_id;
        } else {
            scalarId = json.tracker.rightScalars[pointIndex].scalar_id;
        }
        midas.loadDialog('scalarPoint'+scalarId, '/tracker/scalar/details?scalarId='+scalarId);
        midas.showDialog('Scalar details', false);
    });
};

midas.tracker.renderChartArea = function (curveData, first) {
    if(midas.tracker.plot) {
        midas.tracker.plot.destroy();
    }
    if(curveData.points[0].length > 0) {
        $('#chartDiv').html('');
        var opts = {
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
        };
        if(json.tracker.rightTrend) {
            opts.legend = {
                show: true,
                labels: [json.tracker.trend.display_name, json.tracker.rightTrend.display_name],
                location: 'se'
            };
            opts.axes.y2axis = {
                show: true,
                pad: 1.05,
                label: midas.tracker.yaxis2Label,
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                labelOptions: {
                    angle: 270,
                    fontSize: '12px'
                },
                showLabel: true
            };
            opts.series = [{yaxis: 'yaxis'}, {yaxis: 'y2axis'}];
        }

        midas.tracker.plot = $.jqplot('chartDiv', curveData.points, opts);
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
    var curveData = midas.tracker.extractCurveData([json.tracker.scalars, json.tracker.rightScalars]);

    midas.tracker.yaxisLabel = json.tracker.trend.display_name;
    if(json.tracker.trend.unit) {
        midas.tracker.yaxisLabel += ' ('+json.tracker.trend.unit+')';
    }
    if(json.tracker.rightTrend) {
        midas.tracker.yaxis2Label = json.tracker.rightTrend.display_name;
        if(json.tracker.rightTrend.unit) {
            midas.tracker.yaxis2Label += ' ('+json.tracker.rightTrend.unit+')';
        }
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
        var params = {
            trendId: json.tracker.trend.trend_id,
            startDate: $('#startdate').val(),
            endDate: $('#enddate').val()
        };
        if(json.tracker.rightTrend) {
          params.rightTrendId = json.tracker.rightTrend.trend_id;
        }
        $.post(json.global.webroot+'/tracker/trend/scalars', params, function (retVal) {
            var resp = $.parseJSON(retVal);
            json.tracker.scalars = resp.scalars;
            json.tracker.rightScalars = resp.rightScalars;
            midas.tracker.renderChartArea(midas.tracker.extractCurveData([json.tracker.scalars, json.tracker.rightScalars]), false);
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
