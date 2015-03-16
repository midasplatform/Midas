// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.oauth = midas.oauth || {};

midas.oauth.newClientDialog = function () {
    'use strict';
    midas.showDialogWithContent('Register New OAuth Client', $('#template-createClientDialog').html());
    var container = $('div.MainDialog');
    container.find('.createClientButton').click(function () {
        var name = container.find('input.newClientName').attr('disabled', 'disabled').val();
        $(this).attr('disabled', 'disabled');
        $.post(json.global.webroot + '/oauth/client/create', {
                name: name,
                userId: $('.userIdValue').html()
            },
            function (text) {
                var resp = $.parseJSON(text);
                $('div.MainDialog').dialog('close');
                if (resp.status == 'ok' && resp.client) {
                    midas.createNotice(resp.message, 3000, resp.status);
                    midas.oauth.addClientToList(resp.client);
                }
                else {
                    midas.createNotice(resp.message, 3000, resp.status);
                }
            }
        );
    });
};

midas.oauth.confirmDeleteClient = function () {
    'use strict';
    var parentRow = $(this).parents('tr');
    var clientId = $(this).attr('element');
    midas.showDialogWithContent('Delete OAuth Client', $('#template-deleteClientDialog').html());
    var container = $('div.MainDialog');
    container.find('.deleteClientYes').click(function () {
        container.find('input').attr('disabled', 'disabled');

        $.post(json.global.webroot + '/oauth/client/delete', {
                clientId: clientId
            },
            function (text) {
                var resp = $.parseJSON(text);
                $('div.MainDialog').dialog('close');
                if (resp.status == 'ok') {
                    midas.createNotice(resp.message, 3000, resp.status);
                    $(parentRow).remove();
                }
                else {
                    midas.createNotice(resp.message, 3000, resp.status);
                }
            }
        );
    });
    container.find('.deleteClientNo').click(function () {
        $('div.MainDialog').dialog('close');
    });
};

midas.oauth.confirmDeleteToken = function () {
    'use strict';
    var parentRow = $(this).parents('tr');
    var tokenId = $(this).attr('element');
    midas.showDialogWithContent('Deauthorize Token', $('#template-deleteTokenDialog').html());
    var container = $('div.MainDialog');
    container.find('.deleteTokenYes').click(function () {
        container.find('input').attr('disabled', 'disabled');

        $.post(json.global.webroot + '/oauth/token/delete', {
                tokenId: tokenId
            },
            function (text) {
                var resp = $.parseJSON(text);
                $('div.MainDialog').dialog('close');
                if (resp.status == 'ok') {
                    midas.createNotice(resp.message, 3000, resp.status);
                    $(parentRow).remove();
                }
                else {
                    midas.createNotice(resp.message, 3000, resp.status);
                }
            }
        );
    });
    container.find('.deleteTokenNo').click(function () {
        $('div.MainDialog').dialog('close');
    });
};

midas.oauth.addClientToList = function (client) {
    'use strict';
    var html = '<tr><td>' + client.name + '</td><td>' + client.client_id + '</td><td>' + client.secret +
        '</td><td><a class="deleteClientLink" element="' + client.client_id + '">Delete</a></td></tr>';
    $('.myClientsTable tbody').append(html);
    $('a.deleteClientLink').unbind('click').click(midas.oauth.confirmDeleteClient);
    $('.noClientsMessage').hide();
};

$(document).ready(function () {
    'use strict';
    $('.newClientButton').qtip({
        content: {
            text: $('.newClientButton').attr('qtip')
        }
    }).click(midas.oauth.newClientDialog);

    $('a.deleteClientLink').click(midas.oauth.confirmDeleteClient);
    $('a.deauthorizeTokenLink').click(midas.oauth.confirmDeleteToken);
});
