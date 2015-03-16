// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    $("#tabsGeneric").tabs({
        select: function (event, ui) {
            $('div.genericAction').show();
            $('div.genericCommunities').show();
            $('div.genericStats').show();
            $('div.viewInfo').hide();
            $('div.viewAction').hide();
        }
    });
    $("#tabsGeneric").show();
    $('img.tabsLoading').hide();

    $("#browseTable").treeTable({
        onFirstInit: midas.enableRangeSelect,
        onNodeShow: midas.enableRangeSelect,
        onNodeHide: midas.enableRangeSelect
    });
    // Select/deselect all rows. If we are doing deselect all, we include hidden rows

    midas.browser.enableSelectAll();

    $("img.tableLoading").hide();
    $("table#browseTable").show();

    $('a#sendInvitationLink').click(function () {
        midas.loadDialog("invitationCommunity", "/community/invitation?communityId=" + encodeURIComponent(json.community.community_id));
        midas.showDialog(json.community.sendInvitation, false);
    });

    $('#communitySubtitle').dotdotdot({
        height: 20,
        after: 'a.more'
    });
    $('a.more').click(function () {
        $('#tabInfoLink').click();
    });
});

// dependency: common.browser.js
var ajaxSelectRequest = '';

function callbackSelect(node) {
    'use strict';
    $('div.genericAction').show();
    $('div.genericCommunities').hide();
    $('div.genericStats').hide();
    $('div.viewInfo').show();
    $('div.viewAction').show();
    midas.genericCallbackSelect(node);
}

function callbackDblClick(node) {
    'use strict';
    midas.genericCallbackDblClick(node);
}

function callbackCheckboxes(node) {
    'use strict';
    midas.genericCallbackCheckboxes(node);
}
