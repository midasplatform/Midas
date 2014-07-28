// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.community = midas.community || {};
midas.community.manage = {};

midas.community.manage.init = function () {
    var mainDialogContentDiv = $('div.MainDialogContent');
    var createGroupFromDiv = $('div#createGroupFrom');

    $('a#createGroupLink').click(function () {
        $('div.groupUsersSelection').hide();
        $('td.tdUser input').removeAttr('checked');
        mainDialogContentDiv.html('');
        createGroupFromDiv.find('input[name=groupId]').val('0');
        createGroupFromDiv.find('input[name=name]').val('');
        midas.showDialogWithContent(json.community.message.createGroup, createGroupFromDiv.html(), false);
        mainDialogContentDiv.find('form.editGroupForm').ajaxForm({
            beforeSubmit: midas.community.manage.validateGroupChange,
            success: midas.community.manage.successGroupChange
        });
    });
    
    $('a#addUserToGroupLink').click(function () {
        midas.loadDialog("invitationCommunity", "/community/invitation?directadd=true&communityId=" + json.community.community_id);
        midas.showDialog(json.community.sendInvitation, false);
    });


    $('a.editGroupLink').click(function () {
        mainDialogContentDiv.html('');
        var id = $(this).attr('groupid');
        createGroupFromDiv.find('input[name=groupId]').val(id);
        var groupName = $(this).parent('li').find('a:first').html();
        midas.showDialogWithContent(json.community.message.editGroup, createGroupFromDiv.html(), false);
        $('form.editGroupForm input#name').val(groupName);
        mainDialogContentDiv.find('form.editGroupForm').ajaxForm({
            beforeSubmit: midas.community.manage.validateGroupChange,
            success: midas.community.manage.successGroupChange
        });
    });

    // init tree
    $('img.tabsLoading').hide()

    $('table').filter(function () {
        return this.id.match(/browseTable*/);
    }).treeTable({
        disableElementSize: true
    });

    $("img.tableLoading").hide();
    $("table#browseTable").show();

    $('div.userPersonalData').hide();

    midas.community.manage.initDragAndDrop();
    $('td.tdUser input').removeAttr('checked');
};

// dependance: common/browser.js
midas.ajaxSelectRequest = '';

function callbackSelect(node) {
    $('div.genericAction').show();
    $('div.genericCommunities').hide();
    $('div.genericStats').hide();
    $('div.viewInfo').show();
    $('div.viewAction').show();
    midas.genericCallbackSelect(node);
}

function callbackDblClick(node) {}

function callbackCheckboxes(node) {
    midas.genericCallbackCheckboxes(node);
}

function callbackCreateElement(node) {
    midas.community.manage.initDragAndDrop();
}

$('a.deleteGroupLink').click(function () {
    var html = '';
    html += json.community.message['deleteGroupMessage'];
    html += '<br/>';
    html += '<br/>';
    html += '<br/>';
    html += '<input style="margin-left:140px;" class="globalButton deleteGroupYes" element="' + $(this).attr('groupid') + '" type="button" value="' + json.global.Yes + '"/>';
    html += '<input style="margin-left:50px;" class="globalButton deleteGroupNo" type="button" value="' + json.global.No + '"/>';

    midas.showDialogWithContent(json.community.message['delete'], html, false);

    $('input.deleteGroupYes').unbind('click').click(function () {
        var groupid = $(this).attr('element');
        $.post(json.global.webroot + '/community/manage', {
                communityId: json.community.community_id,
                deleteGroup: 'true',
                groupId: groupid
            },
            function (data) {
                var jsonResponse = jQuery.parseJSON(data);
                if (jsonResponse == null) {
                    midas.createNotice('Error', 4000, 'error');
                    return;
                }
                if (jsonResponse[0]) {
                    $("div.MainDialog").dialog("close");
                    $('a.groupLink[groupid=' + groupid + ']').parent('li').remove();
                    midas.createNotice(jsonResponse[1], 4000);
                    midas.community.manage.init();
                    window.location.replace(json.global.webroot + '/community/manage?communityId=' + json.community['community_id'] + '#tabs-Users');
                    window.location.reload();
                }
                else {
                    midas.createNotice(jsonResponse[1], 4000, 'error');
                }
            }
        );
    });
    $('input.deleteGroupNo').unbind('click').click(function () {
        $("div.MainDialog").dialog('close');
    });
});

midas.community.manage.initDragAndDrop = function () {
    $("#browseTable .file, #browseTable .filePublic, #browseTable .filePrivate," +
        "#browseTable .folderPublic:not(.notdraggable), #browseTable .folderPrivate:not(.notdraggable)").draggable({
        helper: "clone",
        cursor: "move",
        opacity: .75,
        refreshPositions: true, // Performance?
        revert: "invalid",
        revertDuration: 300,
        scroll: true,
        start: function () {
            $('div.userPersonalData').show();
        }
    });

    // Configure droppable rows
    $("#browseTable .folder, #browseTable .folderPublic, #browseTable .folderPrivate").each(function () {
        $(this).parents("tr").droppable({
            accept: ".file, .filePublic, .filePrivate, .folder, .folderPublic, .folderPrivate",
            drop: function (e, ui) {
                // Call jQuery treeTable plugin to move the branch
                var elements = '';
                if ($(ui.draggable).parents("tr").attr('type') == 'folder') {
                    elements = $(ui.draggable).parents("tr").attr('element') + ';';
                }
                else {
                    elements = ';' + $(ui.draggable).parents("tr").attr('element');
                }
                var from_ojbect;
                var classNames = $(ui.draggable).parents("tr").attr('class').split(' ');
                for (key in classNames) {
                    if (classNames[key].match('child-of-')) {
                        from_obj = "#" + classNames[key].substring(9);
                    }
                }
                var destination_obj = this;

                // do nothing if drop item(s) to its current folder
                if ($(this).attr('id') != $(from_obj).attr('id')) {
                    $.post(json.global.webroot + '/browse/movecopy', {
                            moveElement: true,
                            elements: elements,
                            destination: $(this).attr('element'),
                            from: $(from_obj).attr('element'),
                            ajax: true
                        },
                        function (data) {
                            var jsonResponse = jQuery.parseJSON(data);
                            if (jsonResponse == null) {
                                midas.createNotice('Error', 4000, 'error');
                                return;
                            }
                            if (jsonResponse[0]) {
                                midas.createNotice(jsonResponse[1], 1500);
                                $($(ui.draggable).parents("tr")).appendBranchTo(destination_obj);
                            }
                            else {
                                midas.createNotice(jsonResponse[1], 4000, 'error');
                            }
                        }
                    );
                }
            },
            hoverClass: "accept",
            over: function (e, ui) {
                // Make the droppable branch expand when a draggable node is moved over it.
                if (this.id != $(ui.draggable.parents("tr")[0]).id && !$(this).is(".expanded")) {
                    $(this).expand();
                }
            }
        });
    });
}

midas.community.manage.validateGroupChange = function (formData, jqForm, options) {
    var form = jqForm[0];
    if (form.name.value.length < 1) {
        midas.createNotice(json.community.message.infoErrorName, 4000);
        return false;
    }
}

midas.community.manage.successGroupChange = function (responseText, statusText, xhr, form) {
    $("div.MainDialog").dialog("close");
    var jsonResponse = jQuery.parseJSON(responseText);
    if (jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
        var obj = $('a.groupLink[groupId=' + jsonResponse[2].group_id + ']');
        if (obj.length > 0) {
            obj.html(jsonResponse[2].name);
        }

        midas.community.manage.init();
        window.location.replace(json.global.webroot + '/community/manage?communityId=' + json.community['community_id'] + '#tabs-Users');
        window.location.reload();
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}

midas.community.manage.validateInfoChange = function (formData, jqForm, options) {
    var form = jqForm[0];
    if (form.name.value.length < 1) {
        midas.createNotice(json.community.message.infoErrorName, 4000, 'error');
        return false;
    }
}

midas.community.manage.successInfoChange = function (responseText, statusText, xhr, form) {
    var jsonResponse = jQuery.parseJSON(responseText);
    if (jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        $('div.genericName').html(jsonResponse[2]);
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}

midas.community.manage.successPrivacyChange = function (responseText, statusText, xhr, form) {
    var jsonResponse = jQuery.parseJSON(responseText);
    if (jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}

midas.community.manage.promoteMember = function (userId) {
    midas.loadDialog('promoteId' + userId + '.' +
        json.community.community_id +
        new Date().getTime(),
        '/community/promotedialog?user=' + userId + '&community=' + json.community.community_id);
    midas.showDialog('Add user to groups', false);
}

midas.community.manage.removeFromGroup = function (userId, groupId) {
    $.post(json.global.webroot + '/community/removeuserfromgroup', {
            groupId: groupId,
            userId: userId
        },
        function (data) {
            var jsonResponse = jQuery.parseJSON(data);
            if (jsonResponse == null) {
                midas.createNotice('Error', 4000);
                return;
            }
            midas.createNotice(jsonResponse[1], 4000);
            if (jsonResponse[0]) {
                window.location.replace(json.global.webroot + '/community/manage?communityId=' +
                    json.community.community_id + '#tabs-Users');
                window.location.reload();
            }
        }
    );
}

/** Used to remove a user from the members group, and thus all other groups */
midas.community.manage.removeMember = function (userId, groupId) {
    var html = '';
    html += 'Are you sure you want to remove the user from this community? They will be removed from all groups.';
    html += '<br/>';
    html += '<br/>';
    html += '<br/>';
    html += '<span style="float: right">';
    html += '<input class="globalButton removeUserYes" type="button" value="' + json.global.Yes + '"/>';
    html += '<input style="margin-left:15px;" class="globalButton removeUserNo" type="button" value="' + json.global.No + '"/>';

    midas.showDialogWithContent('Remove user from community', html, false);
    $('input.removeUserYes').unbind('click').click(function () {
        $('div.MainDialog').dialog('close');
        midas.community.manage.removeFromGroup(userId, groupId);
    });
    $('input.removeUserNo').unbind('click').click(function () {
        $('div.MainDialog').dialog('close');
    });
}

midas.community.manage.initCommunityPrivacy = function () {
    var inputCanJoin = $('input[name=canJoin]');
    var inputPrivacy = $('input[name=privacy]');
    var canJoinDiv = $('div#canJoinDiv');

    if (inputPrivacy.filter(':checked').val() == 1) { // private
        inputCanJoin.attr('disabled', 'disabled');
        inputCanJoin.removeAttr('checked');
        inputCanJoin.filter('[value=0]').attr('checked', true); // invitation
        canJoinDiv.hide();
    }
    else {
        inputCanJoin.removeAttr('disabled');
        canJoinDiv.show();
    }
    inputPrivacy.change(function () {
        midas.community.manage.initCommunityPrivacy();
    });
}

$(document).ready(function () {
    midas.community.manage.initCommunityPrivacy();

    $("#tabsGeneric").tabs({
        select: function (event, ui) {
            $('div.genericAction').show();
            $('div.genericCommunities').show();
            $('div.genericStats').show();
            $('div.viewInfo').hide();
            $('div.memberSelection').hide();
            $('div.groupUsersSelection').hide();
            $('div.viewAction').hide();
            $('td.tdUser input').removeAttr('checked');
        }
    });
    $("#tabsGeneric").show();
    $('img.tabsLoading').hide();

    $('a#communityDeleteLink').click(function () {
        var html = '';
        html += json.community.message['deleteMessage'];
        html += '<br/>';
        html += '<br/>';
        html += '<br/>';
        html += '<input style="margin-left:140px;" class="globalButton deleteCommunityYes" element="' + $(this).attr('element') + '" type="button" value="' + json.global.Yes + '"/>';
        html += '<input style="margin-left:50px;" class="globalButton deleteCommunityNo" type="button" value="' + json.global.No + '"/>';

        midas.showDialogWithContent(json.community.message['delete'], html, false);

        $('input.deleteCommunityYes').unbind('click').click(function () {
            location.replace(json.global.webroot + '/community/delete?communityId=' + json.community.community_id);
        });
        $('input.deleteCommunityNo').unbind('click').click(function () {
            $("div.MainDialog").dialog('close');
        });
    });

    $('#editCommunityInfoForm').ajaxForm({
        beforeSubmit: midas.community.manage.validateInfoChange,
        success: midas.community.manage.successInfoChange
    });

    $('#editCommunityPrivacyForm').ajaxForm({
        success: midas.community.manage.successPrivacyChange
    });

    // init group tab
    midas.community.manage.init();
    $('table.tablesorter').tablesorter({
        widgets: ['zebra'],
        headers: {
            1: {
                sorter: false
            } // Actions column not sortable
        }
    });

    // init tree
    $('img.tabsLoading').hide();

    $('table').filter(function () {
        return this.id.match(/browseTable*/);
    }).treeTable();
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    $('div.userPersonalData').hide();

    midas.community.manage.initDragAndDrop();
    $('td.tdUser input').removeAttr('checked');
    $('#description').autogrow();
});
