// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
var jsonShare = $.parseJSON($('div.jsonShareContent').html());

$('a#setElementPublicLink').click(function () {
    'use strict';
    $.post(json.global.webroot + '/share/dialog', {
            setPublic: true,
            type: jsonShare.type,
            element: jsonShare.element
        },
        function (data) {
            var jsonResponse = $.parseJSON(data);
            if (jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 1500);
                $('div#elementDirectLink').show();
                $('div#permissionPublic').show();
                $('div#permissionPrivate').hide();
            }
            else {
                midas.createNotice(jsonResponse[1], 4000, 'error');
            }
        }
    );
});

$('a.removeShareLink').click(function () {
    'use strict';
    var removeType = $(this).parents('tr').attr('type');
    var removeId = $(this).parents('tr').attr('element');
    var obj = $(this).parents('tr');
    $.post(json.global.webroot + '/share/dialog', {
            removePolicy: true,
            removeType: removeType,
            removeId: removeId,
            type: jsonShare.type,
            element: jsonShare.element
        },
        function (data) {
            var jsonResponse = $.parseJSON(data);
            if (jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 1500);
                obj.remove();
            }
            else {
                midas.createNotice(jsonResponse[1], 4000, 'error');
            }
        }
    );
});

$('select.changePermissionSelect').change(function () {
    'use strict';
    var changeType = $(this).parents('tr').attr('type');
    var changeId = $(this).parents('tr').attr('element');
    var changeVal = $(this).val();
    var obj = $(this).parents('tr');
    $.post(json.global.webroot + '/share/dialog', {
            changePolicy: true,
            changeVal: changeVal,
            changeType: changeType,
            changeId: changeId,
            type: jsonShare.type,
            element: jsonShare.element
        },
        function (data) {
            var jsonResponse = $.parseJSON(data);
            if (jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 1500);
            }
            else {
                midas.createNotice(jsonResponse[1], 4000);
            }
        }
    );
});

$('a#setElementPrivateLink').click(function () {
    'use strict';
    $.post(json.global.webroot + '/share/dialog', {
            setPrivate: true,
            type: jsonShare.type,
            element: jsonShare.element
        },
        function (data) {
            var jsonResponse = $.parseJSON(data);
            if (jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 1500);
                $('div#elementDirectLink').hide();
                $('div#permissionPublic').hide();
                $('div#permissionPrivate').show();
            }
            else {
                midas.createNotice(jsonResponse[1], 4000, 'error');
            }
        }
    );
});

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

var shareSearchcache = {},
    lastShareXhr;
var itemShareSelected;
$("#live_share_search").catcomplete({
    minLength: 2,
    delay: 10,
    source: function (request, response) {
        'use strict';
        var term = request.term;
        if (term in shareSearchcache) {
            response(shareSearchcache[term]);
            return;
        }

        $("#searchShareLoading").show();

        lastShareXhr = $.getJSON($('.webroot').val() + "/search/live?shareSearch=true", request,
            function (data, status, xhr) {
                $("#searchShareLoading").hide();
                shareSearchcache[term] = data;
                if (xhr === lastShareXhr) {
                    itemShareSelected = false;
                    response(data);
                }
            });
    }, // end source
    select: function (event, ui) {
        'use strict';
        var newPolicyType;
        var newPolicyId;
        if (ui.item.communityid) { // if we have a community
            newPolicyType = 'community';
            newPolicyId = ui.item.communityid;
        }
        else if (ui.item.groupid) { // if we have a folder
            newPolicyType = 'group';
            newPolicyId = ui.item.groupid;
        }
        else if (ui.item.userid) { // if we have a user
            newPolicyType = 'user';
            newPolicyId = ui.item.userid;
        }
        else {
            return;
        }
        $.post(json.global.webroot + '/share/dialog', {
                createPolicy: true,
                newPolicyType: newPolicyType,
                newPolicyId: newPolicyId,
                type: jsonShare.type,
                element: jsonShare.element
            },
            function (data) {
                var jsonResponse = $.parseJSON(data);
                if (jsonResponse[0]) {
                    midas.createNotice(jsonResponse[1], 1500);
                    midas.loadDialog("sharing" + $(this).attr('type') + $(this).attr('element') + newPolicyId, "/share/dialog?type=" + encodeURIComponent(jsonShare.type) + '&element=' + encodeURIComponent(jsonShare.element));
                    midas.showDialog(json.browse.share);
                }
                else {
                    midas.createNotice(jsonResponse[1], 4000, 'error');
                }
            }
        );
        itemShareSelected = true;
    }
});

$('#live_share_search').focus(function () {
    'use strict';
    if ($('#live_share_search_value').val() == 'init') {
        $('#live_share_search_value').val($('#live_share_search').val());
        $('#live_share_search').val('');
    }
});

$('#live_share_search').focusout(function () {
    'use strict';
    if ($('#live_share_search').val() == '') {
        $('#live_share_search').val($('#live_share_search_value').val());
        $('#live_share_search_value').val('init');
    }
});
// end live search

$('input.permissionsDone').click(function () {
    'use strict';
    $('div.MainDialog').dialog('close');
    if (jsonShare.type == "folder") {
        midas.loadDialog("applyRecursive" + jsonShare.element, "/share/applyrecursivedialog?folderId=" + encodeURIComponent(jsonShare.element));
        midas.showDialog(json.browse.share);
    }
});
