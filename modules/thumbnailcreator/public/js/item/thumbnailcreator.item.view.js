// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.thumbnailcreator = midas.thumbnailcreator || {};

midas.thumbnailcreator.setup = function () {
    'use strict';
    // If the user lacks the permissions to make a thumbnail, don't display the
    // icon
    if (json.item.isModerator !== '1') {
        return;
    }

    // Add "make into thumbnail" actions to bitstream rows
    $('tr.bitstreamRow img.bitstreamInfoIcon').before(function () {
        var bitstream_id = $(this).attr('element');
        return '<img alt="" class="makeThumbnailIcon" element="' + bitstream_id + '" src="' +
            json.global.webroot + '/modules/thumbnailcreator/public/images/photo.png" /> ';
    });
    $('img.makeThumbnailIcon').qtip({
        content: 'Use this bitstream as main image'
    }).click(function () {
        var bitstream_id = $(this).attr('element');
        $.post(json.global.webroot + '/thumbnailcreator/thumbnail/create', {
            bitstreamId: bitstream_id,
            itemId: json.item.item_id
        }, function (data) {
            var resp = $.parseJSON(data);
            midas.createNotice(resp.message, 3500, resp.status);
            if (resp.itemthumbnail && resp.status == 'ok') {
                midas.thumbnailcreator.displayThumbnail(resp.itemthumbnail);
            }
        });
    });
};

/**
 * Call this to display the thumbnail set on the item
 */
midas.thumbnailcreator.displayThumbnail = function (itemthumbnail) {
    'use strict';
    $('#thumbnailcreatorLargeImageSection').show()
        .find('img.largeImage')
        .attr('src', json.global.webroot + '/thumbnailcreator/thumbnail/item?itemthumbnail=' + encodeURIComponent(itemthumbnail.itemthumbnail_id));
};

$(document).ready(function () {
    'use strict';
    midas.thumbnailcreator.setup();
    if (json.modules.thumbnailcreator && json.modules.thumbnailcreator.itemthumbnail) {
        midas.thumbnailcreator.displayThumbnail(json.modules.thumbnailcreator.itemthumbnail);
    }
});
