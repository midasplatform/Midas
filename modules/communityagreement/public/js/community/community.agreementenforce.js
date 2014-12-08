// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

/**
 * If the community has an agreement, we must not submit the join form.
 * Instead render the license dialog for them when they hit Join Community.
 */
$(document).ready(function () {
    'use strict';
    var hasAgreement = $('#hasAgreement').html();

    if (hasAgreement == 'true') {
        $('form#joinCommunityForm').unbind('submit').submit(function () {
            midas.loadDialog('agreement', '/communityagreement/community/agreementdialog?communityId=' + encodeURIComponent(json.community.community_id));
            midas.showDialog('Community Agreement', false, {
                width: 500
            });
            return false;
        });
    }
});
