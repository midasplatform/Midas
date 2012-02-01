/**
 * If the community has an agreement, we must not submit the join form.
 * Instead render the license dialog for them when they hit Join Community.
 */
$(document).ready(function() {
    var hasAgreement = $('#hasAgreement').html();

    if(hasAgreement == 'true') {
        $('form#joinCommunityForm').unbind('submit').submit(function() {
            loadDialog('agreement', '/communityagreement/config/agreementdialog?communityId='+json.community.community_id);
            showDialog('Community Agreement', false, {
                width: 500
            });
            return false;
        });
    }

});
