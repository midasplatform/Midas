// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global ajaxWebApi */
/* global document */
/* global json */
/* global window */

var midas = midas || {};
midas.dicomserver = midas.dicomserver || {};

midas.dicomserver.showLoadingImage = function () {
    'use strict';
    $('li#dicomRegisterListItem').append('<div id="registering-image"><img src="' + json.global.webroot + '/modules/dicomserver/public/images/registering.gif" alt=""/> Registering images ...</div>');
};

midas.dicomserver.hideLoadingImage = function () {
    'use strict';
    $('#registering-image').remove();
};

midas.dicomserver.registerAction = function () {
    'use strict';
    midas.dicomserver.showLoadingImage();
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.register',
        args: 'item=' + json.item.item_id,
        success: function (retVal) {
            midas.createNotice('Dicom images registered successfully', 3000);
            $("div#sideElementDicomRegistration").show();
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 3000, 'error');
        },
        complete: function () {
            midas.dicomserver.hideLoadingImage();
        },
        log: $('<p></p>')
    });
};

midas.dicomserver.registrationStatus = function () {
    'use strict';
    ajaxWebApi.ajax({
        method: 'midas.dicomserver.registration.status',
        args: 'item=' + json.item.item_id,
        success: function (retVal) {
            if (retVal.data.status) {
                $("div#sideElementDicomRegistration").show();
            }
            else {
                $("div#sideElementDicomRegistration").hide();
            }
        },
        log: $('<p></p>')
    });
};

$(document).ready(function () {
    'use strict';
    if (json.item.isModerator === '1') {
        $('#dicomRegisterListItem').show();
        midas.dicomserver.registrationStatus();
        $('#dicomRegisterAction').click(midas.dicomserver.registerAction);
    }
});
