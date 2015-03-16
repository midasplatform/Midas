// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {
    'use strict';
    $('div.feedThumbnail img').fadeTo("slow", 0.4);
    $('div.feedThumbnail img').mouseover(function () {
        $(this).fadeTo("fast", 1);
    });

    $('div.feedThumbnail img').mouseout(function () {
        $(this).fadeTo("fast", 0.4);
    });

});
