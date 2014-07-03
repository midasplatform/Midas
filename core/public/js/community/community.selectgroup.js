// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {
    $('#groupSelectOk').click(function () {
        var params = {
            communityId: json.community.community_id,
            groupId: $('#groupSelect').val()
        };
        if (midas.invite.item.userid) {
            params.userId = midas.invite.item.userid;
        }
        else {
            params[midas.invite.item.key] = midas.invite.item.value;
        }
        $.post(json.global.webroot + '/community/sendinvitation', params, function (data) {
            var jsonResponse = $.parseJSON(data);
            if (jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 3000);
                $('div.MainDialog').dialog('close');
            }
            else {
                midas.createNotice(jsonResponse[1], 4000, 'error');
            }
        });
    });
});
