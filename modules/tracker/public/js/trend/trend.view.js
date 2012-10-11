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

$(window).load(function () {
    var curveData = midas.tracker.extractCurveData(json.tracker.scalars);
    var yaxisLabel = json.tracker.trend.display_name;
    if(json.tracker.trend.unit) {
        yaxisLabel += ' ('+json.tracker.trend.unit+')';
    }
    $.jqplot('chartDiv', [curveData.points], {
        axes: {
            xaxis: {
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
                pad: 1,
                label: yaxisLabel,
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
        }
    });
    midas.tracker.populateInfo(curveData);
    $('#chartDiv').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
        midas.loadDialog('scalarPoint', '/tracker/scalar/details?scalarId='+json.tracker.scalars[pointIndex].scalar_id);
        midas.showDialog('Scalar details', false);
    });
});
