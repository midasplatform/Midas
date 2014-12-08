// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.invite = midas.invite || {};

midas.invite.directAdd = $("#directAdd").val();

/**
 * Render group selection dialog for the community
 * @param item The ui.item from the selected user or email node
 */
midas.invite.showGroupSelect = function (item) {
    'use strict';
    var dialogTitle = 'groupSelect';
    var dialogUrl = '/community/selectgroup?communityId=' + encodeURIComponent(json.community.community_id);

    if (item.userid) {
        dialogUrl += '&userId=' + encodeURIComponent(item.userid);
        dialogTitle += item.userid;
    }
    else { // email address
        dialogUrl += '&' + encodeURIComponent(item.key) + '=' + encodeURIComponent(item.value);
        dialogTitle += item.value;
    }

    midas.invite.item = item;
    $('div.MainDialog').dialog('close');
    midas.loadDialog(dialogTitle, dialogUrl);
    midas.showDialog('Select community group', false, {
        width: 400
    });
};

// Live search
$.widget("custom.catcomplete", $.ui.autocomplete, {
    _renderMenu: function (ul, items) {
        'use strict';
        var self = this,
            currentCategory = "";
        $.each(items, function (index, item) {
            if (item.category != currentCategory) {
                ul.append('<li class="search-category">' + item.category + "</li>");
                currentCategory = item.category;
            }
            self._renderItemData(ul, item);
        });
    }
});

var invitationSearchcache = {},
    lastShareXhr;

$("#live_invitation_search").catcomplete({
    minLength: 2,
    delay: 10,
    source: function (request, response) {
        'use strict';
        var term = request.term;
        if (term in invitationSearchcache) {
            response(invitationSearchcache[term]);
            return;
        }
        $("#searchInvitationLoading").show();

        lastShareXhr = $.getJSON($('.webroot').val() + "/search/live?userSearch=true&allowEmail",
            request, function (data, status, xhr) {
                $("#searchInvitationLoading").hide();
                invitationSearchcache[term] = data;
                if (xhr === lastShareXhr) {
                    response(data);
                }
            });
    }, // end source
    select: function (event, ui) {
        'use strict';
        midas.invite.showGroupSelect(ui.item);
    } // end select
});

$('#live_invitation_search').focus(function () {
    'use strict';
    if ($('#live_invitation_search_value').val() == 'init') {
        $('#live_invitation_search_value').val($(this).val());
        $(this).val('');
    }
}).focusout(function () {
    'use strict';
    if ($(this).val() == '') {
        $(this).val($('#live_invitation_search_value').val());
        $('#live_invitation_search_value').val('init');
    }
});
