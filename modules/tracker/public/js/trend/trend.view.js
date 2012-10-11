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
        if(minVal == 'undefined' || value < minVal) {
            minVal = value;
        }
        if(maxVal == 'undefined' || value > maxVal) {
            maxVal = value;
        }
    });
    return {
        points: points,
        minVal: minVal,
        maxVal: maxVal
    };
};

$(window).load(function () {
    var curveData = midas.tracker.extractCurveData(json.tracker.scalars);
    $.jqplot('chartDiv', [curveData.points], {
        axes: {
            xaxis: {
                renderer: $.jqplot.DateAxisRenderer,
                tickOptions: {
                    formatString: '%b'
                }
            },
            yaxis: {
                pad: 1.2
            }
        }
    });
});
