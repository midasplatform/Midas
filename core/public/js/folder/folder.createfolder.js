// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global successCreateFolderCallback */

var midas = midas || {};

$('#createFolderForm').ajaxForm({
    beforeSubmit: validateCreateFolder,
    success: successCreateFolder
});

if (typeof callbackDblClick != 'function') {
    function childrenOf(node) {
        'use strict';
        if (node[0] === undefined) {
            return null;
        }
        return $("table.treeTable tbody tr.child-of-" + node[0].id);
    }
}

function validateCreateFolder(formData, jqForm, options) {
    'use strict';
    var form = jqForm[0];
    if (form.name.value.length < 1) {
        midas.createNotice('Error name', 4000);
        return false;
    }
}

function successCreateFolder(responseText, statusText, xhr, form) {
    'use strict';
    if (typeof successCreateFolderCallback == 'function') {
        successCreateFolderCallback(responseText, statusText, xhr, form);
        return;
    }
    $("div.MainDialog").dialog("close");
    var jsonResponse = $.parseJSON(responseText);
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000);
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
        var node = $('table.treeTable tr[element=' + jsonResponse[2].folder_id + ']');
        if (node.length > 0) {
            node.reload();
        }
        // The new folder is a top level folder
        else {
            var newNodeId = '';
            if ($("#browseTable > tbody > tr:last").length > 0) {
                var lastTopLevelNodeId = $("#browseTable > tbody > tr:last").attr("id").split("-")[2];
                newNodeId = parseInt(eval(lastTopLevelNodeId) + 1);
            }
            else {
                newNodeId = '1';
            }

            var newRow = '';
            // policy: 2 <=> MIDAS_POLICY_ADMIN
            newRow += "<tr id='node--" + newNodeId + "' policy='2' class='parent' privacy='" + jsonResponse[3].privacy_status + "' type='folder' element='" + jsonResponse[3].folder_id + "' ajax='" + jsonResponse[3].folder_id + "'>";
            newRow += "  <td class='treeBrowseElement'><span class='folder'>" + jsonResponse[3].name + "</span></td>";
            newRow += "  <td><img class='folderLoading'  element='" + jsonResponse[3].folder_id + "' alt='' /></td>";
            newRow += "  <td>" + jsonResponse[4] + "</td>";
            newRow += "  <td><input type='checkbox' class='treeCheckbox' type='folder' element='" + jsonResponse[3].folder_id + "' /></td>";
            newRow += "</tr>";

            if ($("#browseTable > tbody > tr:last").length > 0) {
                $(newRow).insertAfter("#browseTable > tbody > tr:last");
            }
            else {
                $(newRow).appendTo("#browseTable > tbody");
            }
            $("#browseTable").treeTable({
                onFirstInit: midas.enableRangeSelect,
                onNodeShow: midas.enableRangeSelect,
                onNodeHide: midas.enableRangeSelect
            });
        }
    }
    else {
        midas.createNotice(jsonResponse[1], 4000);
    }
}
