// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};

midas.registerCallback('CALLBACK_RATINGS_BEFORE_LOAD', 'ratings', function () {
    'use strict';
    $('#ratingsChart').appendTo('#sideElementRatings');
});
