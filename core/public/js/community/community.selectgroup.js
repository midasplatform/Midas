// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {
    $('#groupSelectOk').click(function () {
        var url = json.global.webroot + '/community/sendinvitation'
        var params = {
            communityId: json.community.community_id,
            groupId: $('#groupSelect').val()
        };
        if (midas.invite.item.userid) 
          {
          params.userId = midas.invite.item.userid;
          }
        else 
          {
          params[midas.invite.item.key] = midas.invite.item.value;
          }
        
        if(typeof midas.invite.directAdd !== "undefined" && midas.invite.directAdd == 1) 
          {
          url = json.global.webroot + '/community/addusertogroup'
          }
        $.post(url, params, function (data) {
            var jsonResponse = $.parseJSON(data);
            if (jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 3000);
                if(typeof midas.invite.directAdd !== "undefined" && midas.invite.directAdd == 1) 
                  {
                  window.location.href = json.global.webroot + "/community/manage?communityId="+json.community.community_id+"#tabs-Users";
                  window.location.reload();
                  }
                $('div.MainDialog').dialog('close');
            }
            else {
                midas.createNotice(jsonResponse[1], 4000, 'error');
            }
        });
    });
});
