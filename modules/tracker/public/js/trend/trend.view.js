// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.tracker = midas.tracker || {};

midas.tracker.OFFICIAL_COLOR_KEY = null;
midas.tracker.UNOFFICIAL_COLOR_KEY = 'red';
midas.tracker.unofficialVisible = true;

/**
 * In modern browsers that support window.history.replaceState,
 * this updates the currently displayed URL in the browser to make
 * permalinking easy.
 */
midas.tracker.updateUrlBar = function () {
    'use strict';
    if (typeof window.history.replaceState == 'function') {
        var params = '?trendId=' + json.tracker.trendIds;
        params += '&startDate=' + $('#startdate').val();
        params += '&endDate=' + $('#enddate').val();

        if (json.tracker.rightTrend) {
            params += '&rightTrendId=' + json.tracker.rightTrend.trend_id;
            if (typeof json.tracker.y2Min != 'undefined' && typeof json.tracker.y2Max != 'undefined') {
                params += '&y2Min=' + json.tracker.y2Min + '&y2Max=' + json.tracker.y2Max;
            }
        }
        if (typeof json.tracker.yMin != 'undefined' && typeof json.tracker.yMax != 'undefined') {
            params += '&yMin=' + json.tracker.yMin + '&yMax=' + json.tracker.yMax;
        }
        window.history.replaceState({}, '', params);
    }
};

/**
 * Extract the jqplot curve data from the scalar daos passed to us
 */
midas.tracker.extractCurveData = function (curves) {
    'use strict';
    // TODO remove duplicates from branchFilters
    if (!midas.tracker.branchFilters) {
        midas.tracker.branchFilters = [''];
    }
    midas.tracker.scalarIdMap = {};

    var allPoints = [],
        allColors = [],
        seriesIndex = 0,
        minVal, maxVal;
    $.each(curves, function (curveIdx, scalars) {
        if (!scalars) {
            return;
        }

        $.each(midas.tracker.branchFilters, function (idx, branchFilter) {
            var points = [];
            var colors = [];
            midas.tracker.scalarIdMap[seriesIndex] = [];

            $.each(scalars, function (idx, scalar) {
                if (!midas.tracker.unofficialVisible && scalar.official === 0) {
                    return;
                }

                if (!branchFilter || branchFilter === scalar.branch) {
                    var value = parseFloat(scalar.value);

                    points.push([scalar.submit_time, value]);
                    midas.tracker.scalarIdMap[seriesIndex].push(scalar.scalar_id);

                    if (scalar.official == 1) {
                        colors.push(midas.tracker.OFFICIAL_COLOR_KEY);
                    }
                    else {
                        colors.push(midas.tracker.UNOFFICIAL_COLOR_KEY);
                    }

                    if (typeof minVal == 'undefined' || value < minVal) {
                        minVal = value;
                    }
                    if (typeof maxVal == 'undefined' || value > maxVal) {
                        maxVal = value;
                    }
                }
            });
            allPoints.push(points);
            allColors.push(colors);
            seriesIndex += 1;
        });
    });
    return {
        points: allPoints,
        colors: allColors,
        minVal: minVal,
        maxVal: maxVal
    };
};

/**
 * Fill in the "info" sidebar section based on the curve data
 */
midas.tracker.populateInfo = function (curveData) {
    'use strict';
    var count = curveData.points[0].length;
    if (json.tracker.rightTrend) {
        count += curveData.points[1].length;
    }
    $('#pointCount').html(count);
    $('#minVal').html(curveData.minVal);
    $('#maxVal').html(curveData.maxVal);
};

midas.tracker.bindPlotEvents = function () {
    'use strict';
    $('#chartDiv').unbind('jqplotDataClick').bind('jqplotClick', function (ev, gridpos, datapos, dataPoint, plot) {
        if (dataPoint === null || typeof dataPoint.seriesIndex == 'undefined') {
            return;
        }
        var scalarId;
        if (!json.tracker.rightTrend || dataPoint.seriesIndex === 0) {
            scalarId = midas.tracker.scalarIdMap[dataPoint.seriesIndex][dataPoint.pointIndex];
        }
        else {
            scalarId = json.tracker.rightScalars[dataPoint.pointIndex].scalar_id;
        }
        $('.webroot').val(json.global.webroot);
        midas.loadDialog('scalarPoint' + scalarId, '/tracker/scalar/details?scalarId=' + encodeURIComponent(scalarId));
        midas.showDialog('Scalar details', false, {
            width: 500
        });
    });
};

midas.tracker.renderChartArea = function (curveData, first) {
    'use strict';
    if (midas.tracker.plot) {
        midas.tracker.plot.destroy();
    }
    if (curveData.points[0].length > 0) {
        $('#chartDiv').html('');
        var opts = {
            axes: {
                xaxis: {
                    pad: 1.05,
                    renderer: $.jqplot.DateAxisRenderer,
                    tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                    tickOptions: {
                        formatString: '%Y-%m-%d',
                        angle: 270,
                        fontSize: '11px',
                        labelPosition: 'middle',
                        showGridline: false
                    }
                },
                yaxis: {
                    pad: 1.05,
                    label: midas.tracker.yaxisLabel,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    labelOptions: {
                        fontSize: '12px',
                        angle: 270
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
            },
            grid: {
                backgroundColor: 'white',
                borderWidth: 0,
                shadow: false
            },
            series: []
        };
        // Now assign official/unofficial color to each marker
        $.each(curveData.colors, function (idx, trendColors) {
            opts.series[idx] = {
                renderer: $.jqplot.DifferentColorMarkerLineRenderer,
                shadow: false,
                rendererOptions: {
                    markerColors: curveData.colors[idx],
                    shapeRenderer: $.jqplot.ShapeRenderer
                }
            };
        });
        if (json.tracker.rightTrend) {
            opts.legend = {
                show: true,
                labels: [json.tracker.trends[0].display_name, json.tracker.rightTrend.display_name],
                location: 's',
                placement: 'outsideGrid'
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
            opts.series[0].yaxis = 'yaxis';
            opts.series[1].yaxis = 'y2axis';

            if (typeof json.tracker.y2Min != 'undefined' && typeof json.tracker.y2Max != 'undefined') {
                opts.axes.y2axis.min = parseFloat(json.tracker.y2Min);
                opts.axes.y2axis.max = parseFloat(json.tracker.y2Max);
                opts.axes.y2axis.pad = 1.0;
            }
        }
        else if (json.tracker.trends.length > 1 || midas.tracker.branchFilters.length > 1) {
            var labels = [];
            $.each(json.tracker.trends, function (key, trend) {
                var label = trend.display_name;
                if (trend.unit != '') {
                    label += ' (' + trend.unit + ')';
                }
                $.each(midas.tracker.branchFilters, function (idx, branchFilter) {
                    if (!branchFilter) {
                        branchFilter = '[all branches]';
                    }
                    var branchLabel = label + ': ' + branchFilter;
                    labels.push(branchLabel);
                });
            });
            opts.legend = {
                show: true,
                location: 's' ,
                placement : 'outsideGrid',
                labels: labels
            };
        }

        if (typeof json.tracker.yMin != 'undefined' && typeof json.tracker.yMax != 'undefined') {
            opts.axes.yaxis.min = parseFloat(json.tracker.yMin);
            opts.axes.yaxis.max = parseFloat(json.tracker.yMax);
            opts.axes.yaxis.pad = 1.0;
        }

        midas.tracker.plot = $.jqplot('chartDiv', curveData.points, opts);
        midas.tracker.bindPlotEvents();

        $('a.resetZoomAction').unbind('click').click(function () {
            if (midas.tracker.plot.plugins.cursor._zoom.isZoomed) {
                midas.tracker.plot.resetZoom();
            }
        });
    }
    else {
        $('#chartDiv').html('<span class="noPoints">There are no values for this trend in the specified date range.</span>');
    }
    if (first) {
        $.jqplot.postDrawHooks.push(midas.tracker.bindPlotEvents); // must re-bind data click each time we redraw
    }
    midas.tracker.populateInfo(curveData);
};

midas.tracker.resizePlotContainer = function () {
    var filterHeight = $('.branchFilterContainer').height();
    $('.SubMainContent').css('height', $(window).height()-150 + 'px');
    $('.viewMain').css('height', $(window).height()-(filterHeight+300) + 'px');
};

$(window).load(function () {
    'use strict';

    var inputCurves = json.tracker.scalars;
    if (json.tracker.rightTrend) {
        inputCurves.push(json.tracker.rightScalars);
    }
    var curveData = midas.tracker.extractCurveData(inputCurves);
    midas.tracker.resizePlotContainer();
    $(window).resize(function () {
        midas.tracker.resizePlotContainer();
        midas.tracker.updateBranchFilters();
    });

    if (json.tracker.trends.length == 1) {
        midas.tracker.yaxisLabel = json.tracker.trends[0].display_name;
        if (json.tracker.trends[0].unit) {
            midas.tracker.yaxisLabel += ' (' + json.tracker.trends[0].unit + ')';
        }
    }
    else {
        midas.tracker.yaxisLabel = '';
    }
    if (json.tracker.rightTrend) {
        midas.tracker.yaxis2Label = json.tracker.rightTrend.display_name;
        if (json.tracker.rightTrend.unit) {
            midas.tracker.yaxis2Label += ' (' + json.tracker.rightTrend.unit + ')';
        }
    }

    var dates = $('#startdate, #enddate').datepicker({
        defaultDate: 'today',
        changeMonth: true,
        numberOfMonths: 1,
        onSelect: function (selectedDate) {
            var option = this.id == 'startdate' ? 'minDate' : 'maxDate';
            var instance = $(this).data('datepicker');
            var date = $.datepicker.parseDate(
                instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
                selectedDate, instance.settings);
            dates.not(this).datepicker('option', option, date);
        },
        dayNamesMin: ['S', 'M', 'T', 'W', 'T', 'F', 'S']
    });
    $('#startdate').val(json.tracker.initialStartDate);
    $('#enddate').val(json.tracker.initialEndDate);
    $('#filterButton').click(function () {
        $(this).attr('disabled', 'disabled');
        $('#dateRangeUpdating').show();
        var params = {
            trendId: json.tracker.trendIds,
            startDate: $('#startdate').val(),
            endDate: $('#enddate').val()
        };
        if (json.tracker.rightTrend) {
            params.rightTrendId = json.tracker.rightTrend.trend_id;
        }
        $.post(json.global.webroot + '/tracker/trend/scalars', params, function (retVal) {
            var resp = $.parseJSON(retVal);
            json.tracker.scalars = resp.scalars;
            json.tracker.rightScalars = resp.rightScalars;
            var inputCurves = json.tracker.scalars;
            if (json.tracker.rightTrend) {
                inputCurves.push(json.tracker.rightScalars);
            }
            midas.tracker.updateUrlBar();
            midas.tracker.renderChartArea(midas.tracker.extractCurveData(inputCurves), false);
            $('#filterButton').removeAttr('disabled');
            $('#dateRangeUpdating').hide();
        });
    });

    $('.branchFilterContainer').on('change', '.branchfilter', null, function () {
        midas.tracker.updateBranchFilters();
    });

    $('.add-branchfilter').click(function () {
        var div = $('<div class="otherBranchFilter">Other Trend: </div>');
        var removeLink = $('<a class="removeBranchFilter">Remove</a>').click(function () {
            div.remove();
            midas.tracker.updateBranchFilters();
        });
        div.append($($('.branchfilter')[0]).clone()).append(removeLink);

        $('.branchFilterContainer').append(div);
        midas.tracker.resizePlotContainer();
        midas.tracker.renderChartArea(curveData, false);
    });

    midas.tracker.renderChartArea(curveData, true);

    $('a.thresholdAction').click(function () {
        midas.loadDialog('thresholdNotification', '/tracker/trend/notify?trendId=' + encodeURIComponent(json.tracker.trends[0].trend_id));
        midas.showDialog('Email notification settings', false);
    });
    $('a.axesControl').click(function () {
        midas.showDialogWithContent('Axes Controls', $('#axesControlTemplate').html(), false, {
            width: 380
        });
        var container = $('div.MainDialog');
        container.find('input.yMin').val(json.tracker.yMin);
        container.find('input.yMax').val(json.tracker.yMax);
        container.find('input.y2Min').val(json.tracker.y2Min);
        container.find('input.y2Max').val(json.tracker.y2Max);
        container.find('input.updateAxes').unbind('click').click(function () {
            json.tracker.yMin = container.find('input.yMin').val();
            json.tracker.yMax = container.find('input.yMax').val();
            if (json.tracker.rightTrend) {
                json.tracker.y2Min = container.find('input.y2Min').val();
                json.tracker.y2Max = container.find('input.y2Max').val();
            }
            var curveData = midas.tracker.extractCurveData(inputCurves);
            midas.tracker.renderChartArea(curveData, false);
            midas.tracker.updateUrlBar();
            container.dialog('close');
        });
    });
    $('a.deleteTrend').click(function () {
        midas.showDialogWithContent('Confirm Delete Trend', $('#deleteTrendTemplate').html(), false, {
            width: 420
        });
        var container = $('div.MainDialog');
        container.find('input.deleteYes').unbind('click').click(function () {
            $(this).attr('disabled', 'disabled');
            container.find('input.deleteNo').attr('disabled', 'disabled');

            midas.ajaxWithProgress(container.find('div.deleteProgressBar'),
                container.find('div.deleteProgressMessage'),
                json.global.webroot + '/tracker/trend/delete', {
                    trendId: json.tracker.trendIds
                },
                midas.tracker.trendDeleted
            );
        });
        container.find('input.deleteNo').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });

    $('a.toggleUnofficialVisibility').click(function () {
        if (midas.tracker.unofficialVisible) {
            $(this).find('.linkText').text('Show unofficial submissions');
            $(this).find('.toggleUnofficialIcon').removeClass('toHide').addClass('toShow');
        }
        else {
            $(this).find('.linkText').text('Hide unofficial submissions');
            $(this).find('.toggleUnofficialIcon').removeClass('toShow').addClass('toHide');
        }
        midas.tracker.unofficialVisible = !midas.tracker.unofficialVisible;
        var curveData = midas.tracker.extractCurveData(inputCurves);
        midas.tracker.renderChartArea(curveData, false);
    });

});

midas.tracker.trendDeleted = function (resp) {
    'use strict';
    window.location = json.global.webroot + '/tracker/producer/view?producerId=' + encodeURIComponent(json.tracker.producerId);
};

midas.tracker.updateBranchFilters = function () {
    'use strict';
    midas.tracker.branchFilters = [];
    $.each($('.branchfilter'), function () {
        midas.tracker.branchFilters.push($(this).val());
    });
    midas.tracker.renderChartArea(midas.tracker.extractCurveData(json.tracker.scalars), false);
};
