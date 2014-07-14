// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

midas.registerCallback('CALLBACK_RATINGS_BEFORE_LOAD', 'ratings', function () {
    $('#ratingsChart').appendTo('#sideElementRatings');
});
