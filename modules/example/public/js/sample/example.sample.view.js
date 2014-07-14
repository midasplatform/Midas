// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.example = midas.example || {};

midas.example.sampleView = function (displayValue) {
    $('.viewWrapper').append(displayValue);
};

$(document).ready(function () {
    if (json.json_sample) {
        midas.example.sampleView(json.json_sample);
    }
});
