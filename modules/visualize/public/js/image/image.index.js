// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {
    'use strict';
    $("#viewer").iviewer({
        src: $('div#urlImage').html(),
        update_on_resize: false
    });
});
