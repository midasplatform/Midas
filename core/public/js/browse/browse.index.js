// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
$(document).ready(
    function () {
        'use strict';
        $("#browseTable").treeTable();
        $("img.tableLoading").hide();
        $("table#browseTable").show();

        $('div.feedThumbnail img').fadeTo("slow", 0.4);
        $('div.feedThumbnail img').mouseover(
            function () {
                $(this).fadeTo("fast", 1);
            });

        $('div.feedThumbnail img').mouseout(
            function () {
                $(this).fadeTo("fast", 0.4);
            });

        $('a.createCommunity').click(
            function () {
                if (json.global.logged) {
                    midas.loadDialog("createCommunity", "/community/create");
                    midas.showDialog(json.community.createCommunity, false);
                }
                else {
                    midas.createNotice(json.community.contentCreateLogin, 4000);
                    $("div.TopDynamicBar").show('blind');
                    midas.loadAjaxDynamicBar('login', '/user/login');
                }
            });

        $('.itemBlock').click(
            function () {
                $(location).attr('href', ($('> .itemTitle', this).attr('href')));
            });

    });

// dependence: common/browser.js
// Tree table depends on some global functions. This is terrible. Our javascript
// is absolutely shameful. That's why I didn't namespace these functions.
midas.ajaxSelectRequest = '';
var callbackSelect = function (node) {
    'use strict';
    $('div.defaultSide').hide();
    $('div.viewAction').show();
    $('div.viewInfo').show();
    $('div.ajaxInfoElement').show();
    midas.genericCallbackSelect(node);
};

var callbackDblClick = function (node) {
    'use strict';
    midas.genericCallbackDblClick(node);
};

var callbackCheckboxes = function (node) {
    'use strict';
    midas.genericCallbackCheckboxes(node);
};
