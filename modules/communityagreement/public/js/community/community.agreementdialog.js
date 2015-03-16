// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

$(document).ready(function () {
    'use strict';
    $('#agreementAccepted').click(function () {
        if ($(this).is(':checked')) {
            $('#confirmAgreement').removeAttr('disabled');
        }
        else {
            $('#confirmAgreement').attr('disabled', 'disabled');
        }
    });

    $('#declineAgreement').click(function () {
        $('div.MainDialog').dialog('close');
    });

    $('#confirmAgreement').click(function () {
        $('div.MainDialog').dialog('close');
        window.location.assign(json.global.webroot + '/community/' + encodeURIComponent(json.community.community_id) + '?joinCommunity=true');
    });
});
