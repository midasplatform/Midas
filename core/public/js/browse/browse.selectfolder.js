// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global callbackSelect */
/* global folderSelectionCallback */
/* global json */
/* global sliceFileName */
/* global trimName */

var midas = midas || {};
midas.browse = midas.browse || {};

midas.browse.checkSelectedDestinationValid = function (node, policy) {
    'use strict';
    if (policy >= $('div.MainDialogContent #defaultPolicy').val()) {
        midas.browse.selectFolderToggleButton(true);
    }
};

midas.browse.selectFolderToggleButton = function (on) {
    'use strict';
    if (on) {
        $('#selectElements').removeAttr('disabled');
    }
    else {
        $('#selectElements').attr('disabled', 'disabled');
    }
};

$("#moveTable").treeTable({
    callbackSelect: selectFolderCallbackSelect,
    callbackDblClick: selectFolderCallbackDblClick,
    callbackReloadNode: selectFolderCallbackReloadNode,
    callbackCheckboxes: selectFolderCallbackCheckboxes,
    callbackCustomElements: selectFolderCallbackCustomElements,
    pageLength: 99999 // do not page this table (preserves old functionality)
});
$("div.MainDialogContent img.tableLoading").hide();
$("table#moveTable").show();

if ($('div.MainDialogContent #selectElements') !== undefined) {
    $('div.MainDialogContent #selectElements').click(function () {
        'use strict';
        var folderName = $('#selectedDestination').html();
        var folderId = $('#selectedDestinationHidden').val();
        midas.doCallback('CALLBACK_CORE_UPLOAD_FOLDER_CHANGED', {
            folderName: folderName,
            folderId: folderId
        });

        $('#destinationUpload').html(folderName);
        $('#destinationId').val(folderId);
        $('.destinationUpload').html(folderName);
        $('.destinationId').val(folderId);
        $("div.MainDialog").dialog('close');

        if (typeof folderSelectionCallback == 'function') {
            folderSelectionCallback(folderName, folderId);
        }
        return false;
    });
}

// dependence: common/browser.js
var ajaxSelectRequest = '';

function selectFolderCallbackSelect(node) {
    'use strict';
    var selectedElement = node.find('span:eq(1)').html();
    var parent = true;
    var current = node;

    while (parent !== null) {
        parent = null;
        var classNames = current[0].className.split(' ');
        for (var key in classNames) {
            if (classNames[key].match("child-of-")) {
                parent = $("div.MainDialogContent #" + classNames[key].substring(9));
            }
        }
        if (parent !== null) {
            selectedElement = parent.find('span:eq(1)').html() + '/' + selectedElement;
            current = parent;
        }
    }

    $('div.MainDialogContent #createFolderContent').hide();
    midas.browse.selectFolderToggleButton(false);
    if (node.hasClass('userTopLevel') || node.hasClass('community')) {
        $('div.MainDialogContent #selectElements').attr('disabled', 'disabled');
        $('div.MainDialogContent #createFolderButton').hide();
    }
    else {
        $('div.MainDialogContent #selectedDestinationHidden').val(node.attr('element'));
        $('div.MainDialogContent #selectedDestination').html(sliceFileName(selectedElement, 40));

        if ($('div.MainDialogContent #defaultPolicy').val() !== 0) {
            $('div.MainDialogContent #createFolderButton').show();
        }
        if (typeof node.attr('policy') == 'undefined') {
            var params = {
                type: node.attr('type'),
                id: node.attr('element')
            };
            $.post(json.global.webroot + '/browse/getmaxpolicy', params, function (retVal) {
                var resp = $.parseJSON(retVal);
                node.attr('policy', resp.policy);
                midas.browse.checkSelectedDestinationValid(node, resp.policy);
            });
        }
        else {
            midas.browse.checkSelectedDestinationValid(node, node.attr('policy'));
        }
    }
}

$('#moveTable').find('img.infoLoading').show();
$('div.MainDialogContent div.ajaxInfoElement').html('');

$('div.MainDialogContent #createFolderButton').click(function () {
    'use strict';
    if ($('div.MainDialogContent #createFolderContent').is(':hidden')) {
        $('div.MainDialogContent #createFolderContent').html('<img  src="' + json.global.webroot + '/core/public/images/icons/loading.gif" alt="Loading..." />').show();
        var url = json.global.webroot + '/folder/createfolder?folderId=' + encodeURIComponent($('#selectedDestinationHidden').val());
        $('div.MainDialogContent #createFolderContent').load(url);
    }
    else {
        $('div.MainDialogContent #createFolderContent').hide();
    }
});

var newFolder = false;

function successCreateFolderCallback(responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse = $.parseJSON(responseText);
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000);
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
        var node = $('#moveTable').find('tr[element=' + jsonResponse[2].folder_id + ']');
        node.reload();

        $('div.MainDialogContent #createFolderContent').hide();

        newFolder = jsonResponse[3].folder_id;
    }
    else {
        midas.createNotice(jsonResponse[1], 4000);
    }
}

function selectFolderCallbackReloadNode(mainNode) {
    'use strict';
    if (newFolder !== false) {
        callbackSelect($('#moveTable').find('tr[element=' + newFolder + ']'));
    }
}

function selectFolderCallbackDblClick(node) {}

function selectFolderCallbackCheckboxes(node) {}

function selectFolderCallbackCustomElements(node, elements) {
    'use strict';
    var i = 1;
    var id = node.attr('id');

    var padding = parseInt(node.find('td:first').css('padding-left').slice(0, -2));
    var html = '';
    $.each(elements.folders, function (index, value) {
        html += "<tr id='" + id + "-" + i + "' class='parent child-of-" + id + "' ajax='" + value.folder_id + "'type='folder' element='" + value.folder_id + "'>";
        html += "  <td><span class='folder'>" + trimName(value.name, padding) + "</span></td>";
        html += "</tr>";
        i++;
    });
    return html;
}
