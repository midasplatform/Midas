// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.ratings = midas.ratings || {};

/**
 * Create the jqPlot of ratings distribution
 */
midas.ratings.createChart = function (distribution) {
    'use strict';
    midas.ratings.chart = $.jqplot('ratingsChart', [distribution], {
        seriesDefaults: {
            renderer: $.jqplot.BarRenderer,
            pointLabels: {
                show: true,
                location: 'e',
                edgeTolerance: -15,
                formatString: '%d'
            },
            shadowAngle: 135,
            shadowOffset: 0,
            shadowDepth: 3,
            rendererOptions: {
                barDirection: 'horizontal',
                varyBarColor: true,
                barMargin: 3,
                highlightMouseOver: false,
                highlightMouseDown: false
            }
        },
        seriesColors: ['#FF6F31', '#FF9F02', '#FFCF02', '#A4CC02', '#88B131'],
        axes: {
            yaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: ['1 star', '2 star', '3 star', '4 star', '5 star'],
                borderWidth: 1,
                borderColor: 'black',
                tickOptions: {
                    markSize: 0
                }
            },
            xaxis: {
                min: 0,
                numberTicks: 1
            }
        },
        grid: {
            drawGridlines: false,
            shadow: false,
            drawBorder: true,
            borderColor: 'white',
            background: 'white'
        }
    });
};

/**
 * Display the aggregate rating information including star visualization
 */
midas.ratings.renderAggregate = function (average, total, distribution) {
    'use strict';
    if (average !== null && average != '') {
        average = Math.round(average * 100) / 100;
        $('#averageValue').html(average);
        var starSelect = Math.round(average * 2) - 1;
        $('#ratingsAverage').stars('selectID', starSelect);
    }
    else {
        $('#averageValue').html('0');
        $('#ratingsAverage').stars('selectID', -1);
    }
    for (var key in distribution) {
        distribution[key] = parseInt(distribution[key]);
    }
    midas.ratings.createChart(distribution);
    midas.ratings.chart.replot();
    $('#voteTotal').html(total);
};

/**
 * Set the rating of the current item for the currently logged user
 * to the given value, which should be a number 0-5. Passing 0 means
 * the existing rating should be removed for the user.
 */
midas.ratings.setRating = function (value) {
    'use strict';
    $.post(json.global.webroot + '/ratings/rating/rateitem', {
        itemId: json.item.item_id,
        rating: value
    }, function (data) {
        var resp = $.parseJSON(data);
        midas.createNotice(resp.message, 3000, resp.status);
        midas.ratings.renderAggregate(resp.average, resp.total, resp.distribution);
    });
};

$(window).load(function () {
    'use strict';
    midas.doCallback('CALLBACK_RATINGS_BEFORE_LOAD');
    $('#ratingsAverage').stars({
        disabled: true,
        split: 2
    });

    midas.ratings.renderAggregate(json.modules.ratings.average,
        json.modules.ratings.total,
        json.modules.ratings.distribution);
    $('#ratingsAverage').show();

    if (json.global.logged == '1') {
        $('#ratingsUser').stars({
            disabled: false,
            callback: function (ui, type, value) {
                midas.ratings.setRating(value);
            }
        });
        $('#ratingsUser').stars('selectID', json.modules.ratings.userRating - 1);
        $('#ratingsUser').show();
    }
    else {
        $('div.loginToRate').show();
        $('#loginToRate').click(function () {
            midas.showOrHideDynamicBar('login');
            midas.loadAjaxDynamicBar('login', '/user/login');
        });
        $('#registerToRate').click(function () {
            midas.showOrHideDynamicBar('register');
            midas.loadAjaxDynamicBar('register', '/user/register');
        });
    }
    midas.doCallback('CALLBACK_RATINGS_AFTER_LOAD');
});
