// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};

$('input.deleteFolderYes').unbind('click').click(function () {
    $(this).attr('disabled', 'disabled');
    var id = $('#deleteFolderId').val();
    midas.ajaxWithProgress(
        $('#deleteFolderProgress'),
        $('#deleteFolderProgressMessage'),
        json.global.webroot + '/folder/delete', {
            folderId: id
        },
        function (data) {
            $('input.deleteFolderYes').removeAttr('disabled');
            jsonResponse = jQuery.parseJSON(data);
            if (jsonResponse == null) {
                midas.createNotice('Error', 4000, 'error');
                return;
            }
            if (jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 1500);
                $('div.MainDialog').dialog('close');
                var node = $('table.treeTable tr.parent[element=' + id + ']');
                midas.removeNodeFromTree(node, true);
                midas.genericCallbackCheckboxes($('#browseTable'));
                midas.genericCallbackSelect(null);
            }
            else {
                midas.createNotice(jsonResponse[1], 4000, 'error');
            }
        });
});

$('input.deleteFolderNo').unbind('click').click(function () {
    $('div.MainDialog').dialog('close');
});
