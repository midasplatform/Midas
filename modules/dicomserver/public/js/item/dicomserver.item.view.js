/*global $*/
/*global document*/
/*global ajaxWebApi*/
/*global json*/
/*global window*/
var midas = midas || {};
midas.dicomserver = midas.dicomserver || {};

midas.dicomserver.registerAction = function () {
    'use strict';
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.register',
        args: 'item=' + json.item.item_id,
        success: function (retVal) {
            midas.createNotice('Dicom images registered successfully', 3000);
            window.location.reload();
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 3000, 'error');
        },
        complete: function () {
        },
        log: $('<p></p>')
    });
};

$(document).ready(function () {
    'use strict';
    if (json.item.isModerator === '1') {
        $('#dicomRegisterListItem').show();
        $('#dicomRegisterAction').click(midas.dicomserver.registerAction);
    }
});
