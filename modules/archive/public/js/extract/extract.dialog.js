// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.archive = midas.archive || {};
midas.archive.extract = {};

midas.archive.extract.submitClicked = function () {
    'use strict';
    $('input#beginArchiveExtract').attr('disabled', 'disabled');
    $('input#declineArchiveExtract').attr('disabled', 'disabled');
    $('input#deleteArchiveWhenDone').attr('disabled', 'disabled');
};

midas.archive.extract.success = function (responseText) {
    'use strict';
    $('div.MainDialog').dialog('close');
    $('input#beginArchiveExtract').removeAttr('disabled');
    $('input#declineArchiveExtract').removeAttr('disabled');
    $('input#deleteArchiveWhenDone').removeAttr('disabled');
    var jsonResponse = $.parseJSON(responseText);

    if (jsonResponse === null) {
        midas.createNotice('An error occurred, please contact an administrator', 4000, 'error');
        return;
    }
    if (jsonResponse.status == 'ok') {
        window.location.replace(jsonResponse.redirect);
    }
    else {
        midas.createNotice(jsonResponse.message, 4000, jsonResponse.status);
    }
};

$('#beginArchiveExtract').click(function () {
    'use strict';
    var params = {
        itemId: $('#itemId').val(),
        deleteArchive: $('#deleteArchiveWhenDone').is(':checked')
    };
    midas.archive.extract.submitClicked();
    midas.ajaxWithProgress($('#extractArchiveProgressBar'),
        $('#extractArchiveProgressMessage'),
        json.global.webroot + '/archive/extract/perform',
        params,
        midas.archive.extract.success
    );
});

$('input#declineArchiveExtract').click(function () {
    'use strict';
    $('div.MainDialog').dialog('close');
});
