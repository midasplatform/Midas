// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.keyfiles = midas.keyfiles || {};

midas.keyfiles.setup = function () {
    'use strict';
    // Add "download key file" actions to bitstream rows
    $('tr.bitstreamRow img.bitstreamInfoIcon').before(function () {
        var bitstream_id = $(this).attr('element');
        return '<img alt="" class="downloadKeyFileIcon" element="' + bitstream_id + '" src="' +
            json.global.coreWebroot + '/public/images/icons/key.png" /> ';
    });
    $('img.downloadKeyFileIcon').qtip({
        content: 'Download key file'
    }).click(function () {
        var bitstream_id = $(this).attr('element');
        window.location.href = json.global.webroot + '/keyfiles/download/bitstream?bitstreamId=' + encodeURIComponent(bitstream_id);
    });
};

$(document).ready(function () {
    'use strict';
    midas.keyfiles.setup();
});
