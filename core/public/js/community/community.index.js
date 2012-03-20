$(document).ready(function () {
    'use strict';

    $('a.createCommunity').click(function () {

        if (json.global.logged) {
            loadDialog("createCommunity","/community/create");
            showDialog(json.community.createCommunity,false);
        } else {
            createNotive(json.community.contentCreateLogin,4000)
            $("div.TopDynamicBar").show('blind');
            loadAjaxDynamicBar('login', '/user/login');
        }
    });

    $('.communityBlock').click(function () {
        $(location).attr('href',($('> .communityTitle',this).attr('href')));
    });

    $('span.communityDescription').dotdotdot({height : 30,
                                              wrapper : 'span',
                                              after: 'a.more'});
});
