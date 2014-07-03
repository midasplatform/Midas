// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/**
 * If the community has an agreement, we must not submit the join form.
 * Instead render the license dialog for them when they hit Join Community.
 */
$(document).ready(function () {
    var hasAgreement = $('#hasAgreement').html();

    if (hasAgreement == 'true') {
        $('form#joinCommunityForm').unbind('submit').submit(function () {
            midas.loadDialog('agreement', '/communityagreement/config/agreementdialog?communityId=' + json.community.community_id);
            midas.showDialog('Community Agreement', false, {
                width: 500
            });
            return false;
        });
    }
});
