// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.communityagreement = midas.communityagreement || {};

midas.communityagreement.validateAgreementChange = function (formData, jqForm, options) {};

midas.communityagreement.successAgreementChange = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice(responseText, 4000, 'error');
        return false;
    }
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
        $('#tabsGeneric').tabs('load', $('#tabsGeneric').tabs('option', 'selected')); // reload tab
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

/**
 * An ajax based form submission for form 'createAgreementForm'
 */
$(document).ready(function () {
    'use strict';
    $('#createAgreementForm').ajaxForm({
        beforeSubmit: midas.communityagreement.validateAgreementChange,
        success: midas.communityagreement.successAgreementChange
    });
});
