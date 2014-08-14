// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    $('a.createCommunity').click(function () {
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

    $('.communityBlock').click(function () {
        $(location).attr('href', ($('> .communityTitle', this).attr('href')));
    });

    $('span.communityDescription').dotdotdot({
        height: 30,
        wrapper: 'span',
        after: 'a.more'
    });
});
