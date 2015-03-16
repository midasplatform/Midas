// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global ajaxWebApi */
/* global document */
/* global json */
/* global window*/

var midas = midas || {};
midas.dicomextractor = midas.dicomextractor || {};

midas.dicomextractor.extractAction = function () {
    'use strict';
    ajaxWebApi.ajax({
        method: 'midas.dicomextractor.extract',
        args: 'item=' + json.item.item_id,
        success: function (retVal) {
            midas.createNotice('Metadata extracted successfully', 3000);
            window.location.reload();
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 3000, 'error');
        },
        complete: function () {},
        log: $('<p></p>')
    });
};

$(document).ready(function () {
    'use strict';
    if (json.item.isModerator === '1') {
        $('#dicomExtractListItem').show();
        $('#dicomExtractAction').click(midas.dicomextractor.extractAction);
    }
});
