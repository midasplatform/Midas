// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.example = midas.example || {};

midas.example.sampleView = function (displayValue) {
    'use strict';
    $('.viewWrapper').append(displayValue);
};

$(document).ready(function () {
    'use strict';
    if (json.json_sample) {
        midas.example.sampleView(json.json_sample);
    }
});
