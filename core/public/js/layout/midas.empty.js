// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/**
 * This is the main javascript file for the empty layout.  It should
 * not contain references to any DOM elements from other layouts.
 */
var json = json || {};
var midas = midas || {};

// Prevent error if console.log is called
if (typeof console != "object") {
    var console = {
        'log': function () {}
    };
}

$(function () {
    'use strict';
    // Parse json content
    // jQuery 1.8 has weird bugs when using .html() here, use the old-style innerHTML here
    json = $.parseJSON($('div.jsonContent')[0].innerHTML);
});
