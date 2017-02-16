// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.browser = midas.browser || {};

midas.ajaxSelectRequest = '';

/**
 * Callback when a row is selected
 * Pass null if there is no selected row.
 */
midas.genericCallbackSelect = function (node) {
    'use strict';
    if (!node || !node.attr('type')) {
        $('div.ajaxInfoElement').html('');
        $('div.viewAction ul').html('');
        return;
    }
    $('img.infoLoading').show();
    $('div.ajaxInfoElement').html('');
    if (midas.ajaxSelectRequest != '') {
        midas.ajaxSelectRequest.abort();
    }
    $('div.viewAction').show();

    midas.createAction(node);
    midas.ajaxSelectRequest = $.ajax({
        type: 'POST',
        url: json.global.webroot + '/browse/getelementinfo',
        data: {
            type: node.attr('type'),
            id: node.attr('element')
        },
        success: function (jsonContent) {
            midas.createInfo(jsonContent);
            $('img.infoLoading').hide();
        }
    });
};

midas.genericCallbackCheckboxes = function (node) {
    'use strict';
    var arraySelected = [];
    arraySelected['folders'] = [];
    arraySelected['items'] = [];
    var folders = '';
    var items = '';
    node.find('.treeCheckbox:checked').each(
        function () {
            if ($(this).parents('tr').attr('type') != 'item') {
                arraySelected['folders'].push($(this).attr('element'));
                folders += $(this).attr('element') + '-';
            }
            else {
                arraySelected['items'].push($(this).attr('element'));
                items += $(this).attr('element') + '-';
            }
        });
    var nselected = arraySelected['folders'].length + arraySelected['items'].length;
    if (nselected > 0) {
        $('div.viewSelected').show();
        $('div.sideElementActions').hide();
        var html = ' (' + nselected;
        html += ' Resource';
        if (nselected != 1) {
            html += 's';
        }
        html += ')';
        $('div.viewSelected h1 span').html(html);
        var links = '<ul>';
        links += '<li style="background-color: white;">';
        links += '  <img alt="" src="' + json.global.coreWebroot + '/public/images/icons/download.png"/> ';
        links += '  <a class="downloadSelectedLink">Download all selected</a></li>';
        links += '</li>';

        if (json.global.logged) {
            links += '<li style="background-color: white;">';
            links += '  <img alt="" src="' + json.global.coreWebroot + '/public/images/icons/close.png"/> ';
            links += '  <a onclick="midas.deleteSelected(\'' + folders + '\',\'' + items + '\')">' + json.browse.deleteSelected + '</a></li>';
            links += '</li>';
            links += '<li>';
            links += '<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/move.png"/> ';
            links += '<a onclick="midas.moveSelected(\'' + folders + '\',\'' + items + '\')" element="' + items + '">Move all selected</a>';
            links += '</li>';
            if (arraySelected['items'].length > 0) {
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="' + json.global.coreWebroot + '/public/images/icons/copy.png"/> ';
                links += '  <a onclick="midas.duplicateSelected(\'' + folders + '\',\'' + items + '\')">' + json.browse.duplicateSelected + '</a></li>';
                links += '</li>';
            }
            if (arraySelected['items'].length > 1 && arraySelected['folders'].length === 0) {
                links += '<li>';
                links += '<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/page_white_stack.png"/> ';
                links += '<a class="mergeItemsLink" element="' + items + '">Merge items</a>';
                links += '</li>';
            }
        }
        links += '</ul>';
        $('div.viewSelected>span').html(links);
        $('a.mergeItemsLink').click(function () {
            var html = 'Select a name: ';
            html += '<input type="text" id="mergeItemName" value=""/><br/><br/>';
            html += '<div id="mergeProgressBar"></div>';
            html += '<div id="mergeProgressMessage"></div>';
            html += '<input id="mergeButton" class="globalButton" type="submit" value="Merge" element="' + $(this).attr('element') + '" />';
            midas.showDialogWithContent('Merge items', html, false, {
                width: 300
            });
            $('#mergeButton').click(function () {
                if ($('#mergeItemName').val() == '') {
                    midas.createNotice('You must enter an item name first', 3000, 'error');
                    return;
                }
                $(this).attr('disabled', 'disabled');
                midas.ajaxWithProgress(
                    $('#mergeProgressBar'),
                    $('#mergeProgressMessage'),
                    json.global.webroot + '/item/merge', {
                        items: $('#mergeButton').attr('element'),
                        name: $('#mergeItemName').val()
                    },
                    function (data) {
                        var resp = $.parseJSON(data);
                        if (!resp || !resp.redirect) {
                            midas.createNotice('An error occurred. Please check the logs', 3000, 'error');
                        }
                        else {
                            window.location = resp.redirect;
                        }
                    }
                );
            });
        });
        $('a.downloadSelectedLink').click(function () {
            $.post(json.global.webroot + '/download/checksize', {
                folderIds: arraySelected['folders'].join(','),
                itemIds: arraySelected['items'].join(',')
            }, function (text) {
                var retVal = $.parseJSON(text);
                if (retVal.action == 'download') {
                    window.location = json.global.webroot + '/download?folders=' + encodeURIComponent(folders) + '&items=' + encodeURIComponent(items);
                }
                else if (retVal.action == 'promptApplet') {
                    midas.doCallback('CALLBACK_CORE_PROMPT_APPLET', {
                        folderIds: arraySelected['folders'].join(','),
                        itemIds: arraySelected['items'].join(','),
                        sizeString: retVal.sizeStr
                    });
                }
            });
        });

        midas.doCallback('CALLBACK_CORE_RESOURCES_SELECTED', {
            folders: arraySelected.folders,
            items: arraySelected.items,
            selectedActionsList: $('div.viewSelected>span ul')
        });
        $('div.viewSelected li a').hover(
            function () {
                $(this).parents('li').css('background-color', '#E5E5E5');
            }, function () {
                $(this).parents('li').css('background-color', 'white');
            });
        $('div.viewSelected li a').append(' (' + nselected + ')');
    }
    else {
        $('div.viewSelected').hide();
        $('div.viewSelected span').html('');
    }
};

midas.genericCallbackDblClick = function (node) {
    'use strict';
    // no-op currently
};

midas.createNewFolder = function (id) {
    'use strict';
    midas.loadDialog('folderId' + id, '/folder/createfolder?folderId=' + encodeURIComponent(id));
    midas.showDialog(json.browse.createFolder, false);
    $('#createFolderForm').find('input[name=name]').val('');
    $('#createFolderForm').find('textarea[name=description]').val('');
    $('#createFolderForm').find('input[name=teaser]').val('');
};

midas.removeNodeFromTree = function (node, recursive) {
    'use strict';
    if (!node || node.length === 0) {
        return;
    }
    var ancestorNodes = midas.ancestorsOf(node);
    if (recursive) {
        midas.removeChildren(node);
    }
    node.remove();
    // mark ancestor nodes
    for (var curNode in ancestorNodes) {
        var jCurNode = $(ancestorNodes[curNode]);
        jCurNode.find('span.elementCount').remove();
        jCurNode.find('span.elementSize').after('<img class="folderLoading"  element="' + jCurNode.attr('element') + '" alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif"/>');
        jCurNode.find('span.elementSize').remove();
    }
    // update folder size
    $('#browseTable').ttRenderElementsSize();
};

midas.removeItem = function (id) {
    'use strict';
    var node = $('table.treeTable tr[type=item][element=' + id + ']');
    var itemName = node.find('td:first span').html();

    var html = 'Are you sure you want to remove the item <b>' + itemName + '</b>?';
    html += '<div style="float: right;margin-top:30px;">';
    html += '<input class="globalButton removeItemYes" element="' + id + '" type="button" value="' + json.global.Yes + '"/>';
    html += '<input style="margin-left:15px;" class="globalButton removeItemNo" type="button" value="' + json.global.No + '"/>';
    html += '</div>';

    midas.showDialogWithContent('Confirm Delete Item', html, false);

    $('input.removeItemYes').unbind('click').click(function () {
        $(this).attr('disabled', 'disabled');
        var folder = midas.parentOf(node);
        var folderId = '';
        // we are in a subfolder view and the parent is the current folder
        if (folder) {
            folderId = folder.attr('element');
        }
        else {
            folderId = json.folder.folder_id;
        }

        $.post(json.global.webroot + '/folder/removeitem', {
            folderId: folderId,
            itemId: id
        },
            function (data) {
                var jsonResponse = $.parseJSON(data);
                if (!jsonResponse) {
                    $('div.MainDialog').dialog('close');
                    midas.createNotice('An error occurred, check the error logs', 4000);
                    return;
                }
                if (jsonResponse[0]) {
                    midas.createNotice(jsonResponse[1], 1500);
                    $('div.MainDialog').dialog('close');
                    midas.removeNodeFromTree(node, false);
                    midas.genericCallbackCheckboxes($('#browseTable'));
                    midas.genericCallbackSelect(null);
                }
                else {
                    midas.createNotice(jsonResponse[1], 4000);
                }
            });
    });
    $('input.removeItemNo').unbind('click').click(function () {
        $('div.MainDialog').dialog('close');
    });
};

midas.deleteFolder = function (id) {
    'use strict';
    midas.loadDialog('deleteFolder' + id, '/folder/deletedialog?folderId=' + encodeURIComponent(id));
    midas.showDialog('Confirm Delete Folder', false);
};

/**
 * Deletes the set of folders and items selected with the checkboxes.
 * The folders and items params should be strings of ids separated by - (empty
 * ids will be ignored)
 */
midas.deleteSelected = function (folders, items) {
    'use strict';
    var html = '';
    html += json.browse['deleteSelectedMessage'];
    html += '<br/><br/><br/>';
    html += '<form class="genericForm"><div class="dialogButtons">';
    html += '  <input class="globalButton deleteSelectedYes" type="button" value="' + json.global.Yes + '"/>';
    html += '  <input class="globalButton deleteSelectedNo" type="button" value="' + json.global.No + '"/>';
    html += '</div></form>';
    html += '<img id="deleteSelectedLoadingGif" alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif"/>';

    midas.showDialogWithContent(json.browse['deleteSelected'], html, false);
    $('input.deleteSelectedYes').unbind('click').click(function () {
        $('input.deleteSelectedYes').attr('disabled', 'disabled');
        $('input.deleteSelectedNo').attr('disabled', 'disabled');
        $('#deleteSelectedLoadingGif').show();
        $.post(json.global.webroot + '/browse/delete', {
            folders: folders,
            items: items
        }, function (data) {
            $('input.deleteSelectedYes').removeAttr('disabled');
            $('input.deleteSelectedNo').removeAttr('disabled');
            $('#deleteSelectedLoadingGif').hide();
            var resp = $.parseJSON(data);
            if (resp === null) {
                midas.createNotice('Error during folder delete. Check the log.', 4000);
                return;
            }
            if (resp.success) {
                var message = 'Deleted ' + resp.success.folders.length + ' folders and ';
                message += resp.success.items.length + ' items.';
                if (resp.failure.folders.length || resp.failure.items.length) {
                    message += ' Invalid delete permissions on ';
                    message += resp.failure.folders.length + ' folders and ';
                    message += resp.failure.items.length + ' items.';
                }
                midas.createNotice(message, 5000);
                $('div.MainDialog').dialog('close');
                for (var curFolder in resp.success.folders) {
                    midas.removeNodeFromTree($('table.treeTable tr.parent[element=' + resp.success.folders[curFolder] + ']'), true);
                }
                for (var curItem in resp.success.items) {
                    midas.removeNodeFromTree($('table.treeTable tr[type=item][element=' + resp.success.items[curItem] + ']'), false);
                }
                midas.genericCallbackCheckboxes($('#browseTable'));
                midas.genericCallbackSelect(null);
            }
        });
    });
    $('input.deleteSelectedNo').unbind('click').click(function () {
        $('div.MainDialog').dialog('close');
    });
};

/**
 * Duplicates the set of items selected with the checkboxes.
 * This action does not support folder type, selected folder will be ignored.
 * The items param should be strings of ids separated by - (empty
 * ids will be ignored)
 */
midas.duplicateSelected = function (folders, items) {
    'use strict';
    midas.loadDialog('duplicateItem', '/browse/movecopy/?duplicate=true&items=' + encodeURIComponent(items));
    var title = 'Copy selected items';
    if (folders != '') {
        title += ' ' + json.browse.ignoreSelectedFolders;
    }
    midas.showDialog(title);
};

/**
 * Copy or symlink a single item
 */
midas.duplicateItem = function (item) {
    'use strict';
    midas.loadDialog('duplicateItem', '/browse/movecopy/?duplicate=true&items=' + encodeURIComponent(item));
    midas.showDialog('Copy item');
};

/**
 * Prompt the user with a dialog to move the selected items and folders.
 * @param folders The list of folders to move (separated by -)
 * @param items The list of items to move (separated by -)
 */
midas.moveSelected = function (folders, items) {
    'use strict';
    midas.loadDialog('moveItem', '/browse/movecopy/?move=true&items=' + encodeURIComponent(items) + '&folders=' + encodeURIComponent(folders));
    midas.showDialog('Move all selected resources');
};

/**
 * Helper method to remove all of a node's subtree from the treeTable view.
 * Expects a jQuerified node object.
 */
midas.removeChildren = function (node) {
    'use strict';
    node.each(
        function () {
            var children = $('table.treeTable tbody tr.child-of-' + this.id);
            $(children).each(
                function () {
                    midas.removeChildren($(this));
                    $(this).remove();
                });
        });
};

midas.editFolder = function (id) {
    'use strict';
    midas.loadDialog('editFolder' + id, '/folder/edit?folderId=' + encodeURIComponent(id));
    midas.showDialog(json.browse.edit, false);
};

midas.moveFolder = function (id) {
    'use strict';
    midas.loadDialog('moveFolder' + id, '/browse/movecopy?move=true&folders=' + encodeURIComponent(id));
    midas.showDialog(json.browse.move);
};

midas.moveItem = function (itemId, fromFolderId) {
    'use strict';
    midas.loadDialog('moveItem' + itemId, '/browse/movecopy?move=true&items=' + encodeURIComponent(itemId) + '&from=' + encodeURIComponent(fromFolderId));
    midas.showDialog(json.browse.move);
};

midas.parentOf = function (node) {
    'use strict';
    var classNames = node[0].className.split(' ');

    for (var key in classNames) {
        if (classNames[key].match('child-of-')) {
            return $('#' + classNames[key].substring(9));
        }
    }
};

midas.ancestorsOf = function (node) {
    'use strict';
    var ancestors = [];
    while ((node = midas.parentOf(node))) {
        ancestors[ancestors.length] = node[0];
    }
    return ancestors;
};

midas.createAction = function (node) {
    'use strict';
    $('div.viewAction ul').html('<img alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif" />');
    var type = node.attr('type');
    var element = node.attr('element');

    if (typeof node.attr('policy') == 'undefined') {
        var params = {
            type: type,
            id: element
        };
        $.post(json.global.webroot + '/browse/getmaxpolicy', params, function (retVal) {
            var resp = $.parseJSON(retVal);
            node.attr('policy', resp.policy);
            midas.createAction(node);
        });
        return;
    }

    var policy = node.attr('policy');
    var header = '<h1>Selected ' + type + '</h1>';
    $('div.viewAction ul').html(header);
    var html = '';
    if (type == 'community') {
        html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/view.png"/> <a href="' + json.global.webroot + '/community/' + encodeURIComponent(element) + '">' + json.browse.view + '</a></li>';
    }
    if (type == 'folder') {
        html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/view.png"/> <a href="' + json.global.webroot + '/folder/' + encodeURIComponent(element) + '">' + json.browse.view + '</a></li>';
        html += '<li class="downloadObject"><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/download.png"/> <a element="' + element + '" class="downloadFolderLink">' + json.browse.download + '</a></li>';
        html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/link.png"/> <a type="folder" element="' + element + '" href="javascript:;" class="getResourceLinks">Share</a></li>';
        if (policy >= 1) {
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/folder_add.png"/> <a onclick="midas.createNewFolder(' + element + ');">' + json.browse.createFolder + '</a></li>';
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/upload.png"/> <a rel="' + json.global.webroot + '/upload/simpleupload/?parent=' + element + '" class="uploadInFolder">' + json.browse.uploadIn + '</a></li>';
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/edit.png"/> <a onclick="midas.editFolder(' + element + ');">' + json.browse.edit + '</a></li>';
        }
        if (policy >= 2) {
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/move.png"/> <a onclick="midas.moveFolder(' + element + ');">' + json.browse.move + '</a></li>';
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/lock.png"/> <a type="folder" element="' + element + '" class="sharingLink">' + json.browse.share + '</a></li>';
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/close.png"/> <a onclick="midas.deleteFolder(' + element + ');">' + json.browse['delete'] + '</a></li>';
        }
    }
    if (type == 'item') {
        var from = midas.parentOf(node);
        var fromFolder;
        if (from) {
            fromFolder = from.attr('element');
        }
        else { // we are in a subfolder view and the parent is the current folder
            fromFolder = json.folder.folder_id;
        }
        html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/view.png"/> <a href="' + json.global.webroot + '/item/' + encodeURIComponent(element) + '">' + json.browse.view + '</a></li>';
        html += '<li class="downloadObject"><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/download.png"/> <a element="' + element + '" class="downloadItemLink">' + json.browse.download + '</a></li>';
        html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/link.png"/> <a type="item" element="' + element + '" href="javascript:;" class="getResourceLinks">Share</a></li>';
        if (json.global.logged) {
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/copy.png"/> <a onclick="midas.duplicateItem("' + element + '");">Copy</a></li>';
        }
        if (policy >= 2) {
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/lock.png"/> <a  type="item" element="' + element + '" class="sharingLink">' + json.browse.share + '</a></li>';
            html += '<li><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/move.png"/> <a onclick="midas.moveItem("' + element + '","' + fromFolder + '");">' + json.browse.move + '</a></li>';
            html += '<li class="removeItemLi"><img alt="" src="' + json.global.coreWebroot + '/public/images/icons/close.png"/> <a onclick="midas.removeItem(' + element + ');">' + json.browse['removeItem'] + '</a></li>';
        }
    }
    $('div.viewAction ul').append(html);

    midas.doCallback('CALLBACK_CORE_RESOURCE_HIGHLIGHTED', {
        type: type,
        id: element,
        actionsList: $('div.viewAction ul')
    });

    $('div.viewAction li a').hover(
        function () {
            $(this).parents('li').css('background-color', '#E5E5E5');
        },
        function () {
            $(this).parents('li').css('background-color', 'white');
        });

    $('a.uploadInFolder').click(function () {
        var button = $('li.uploadFile');
        button.attr('rel', $(this).attr('rel'));
        midas.resetUploadButton();
        button.click();
    });
    $('a.sharingLink').click(function () {
        midas.loadDialog('sharing' + $(this).attr('type') + $(this).attr('element'), '/share/dialog?type=' + encodeURIComponent($(this).attr('type')) + '&element=' + encodeURIComponent($(this).attr('element')));
        midas.showDialog(json.browse.share);
    });
    $('a.getResourceLinks').click(function () {
        midas.loadDialog('links' + $(this).attr('type') + $(this).attr('element'), '/share/links?type=' + encodeURIComponent($(this).attr('type')) + '&id=' + encodeURIComponent($(this).attr('element')));
        midas.showDialog('Link to this item');
    });
    $('a.downloadFolderLink').click(function () {
        midas.createNotice("Folder download is disabled. Contact kitware[at]kitware[dot]com if you have questions.", 4000, 'warning');
        /*
        var folderId = $(this).attr('element');
        $.post(json.global.webroot + '/download/checksize', {
            folderIds: folderId
        }, function (text) {
            var retVal = $.parseJSON(text);
            if (retVal.action == 'download') {
                window.location = json.global.webroot + '/download/folder/' + encodeURIComponent(folderId);
            }
            else if (retVal.action == 'promptApplet') {
                midas.doCallback('CALLBACK_CORE_PROMPT_APPLET', {
                    folderIds: folderId,
                    itemIds: '',
                    sizeString: retVal.sizeStr
                });
            }
        });
        */
    });
    $('a.downloadItemLink').click(function () {
        var itemId = $(this).attr('element');
        $.post(json.global.webroot + '/download/checksize', {
            itemIds: itemId
        }, function (text) {
            var retVal = $.parseJSON(text);
            if (retVal.action == 'download') {
                window.location = json.global.webroot + '/download/item/' + encodeURIComponent(itemId);
            }
            else if (retVal.action == 'promptApplet') {
                midas.doCallback('CALLBACK_CORE_PROMPT_APPLET', {
                    folderIds: '',
                    itemIds: itemId,
                    sizeString: retVal.sizeStr
                });
            }
        });
    });
    $('div.viewAction ul').show();
};

midas.escape = function (text) {
    'use strict';
    return $('<div/>').text(text).html();
};

midas.createInfo = function (jsonContent) {
    'use strict';
    var arrayElement = $.parseJSON(jsonContent);
    var html = '';
    if (arrayElement['type'] == 'community') {
        html += '<img class="infoLogo" alt="Data Type" src="' + json.global.coreWebroot + '/public/images/icons/community-big.png" />';
    }
    else if (arrayElement['type'] == 'folder') {
        html += '<img class="infoLogo" alt="Data Type" src="' + json.global.coreWebroot + '/public/images/icons/folder-big.png" />';
    }
    else {
        html += '<img class="infoLogo" alt="Data Type" src="' + json.global.coreWebroot + '/public/images/icons/document-big.png" />';
    }

    html += '<span class="infoTitle" >' + midas.escape(arrayElement['name']) + '</span>';
    html += '<table>';
    html += '  <tr>';
    html += '    <td>' + arrayElement.translation.Created + '</td>';
    html += '    <td>' + arrayElement.creation + '</td>';
    html += '  </tr>';
    if (arrayElement['type'] == 'community') {
        html += '  <tr>';
        html += '    <td>Members';
        html += '</td>';
        html += '    <td>' + midas.escape(arrayElement['members']) + '</td>';
        html += '  </tr>';
    }
    if (arrayElement['type'] == 'item') {
        if (arrayElement['norevisions'] === true) {
            html += '  <tr>';
            html += '    <td>No Revisions</td>';
            html += '  </tr>';
            html += '  <tr>';
        }
        else {
            html += '  <tr>';
            html += '    <td>' + arrayElement.translation.Uploaded + '</td>';
            html += '    <td><a href="' + json.global.webroot + '/user/' + midas.escape(encodeURIComponent(arrayElement['uploaded']['user_id'])) + '">' + midas.escape(arrayElement['uploaded']['firstname']) + ' ' + midas.escape(arrayElement['uploaded']['lastname']) + '</a></td>';
            html += '  </tr>';
            html += '  <tr>';
            html += '    <td>Revisions</td>';
            html += '    <td>' + midas.escape(arrayElement['revision']['revision']) + '</td>';
            html += '  </tr>';
            html += '  <tr>';
            html += '    <td>Files</td>';
            html += '    <td>' + midas.escape(arrayElement['nbitstream']) + '</td>';
            html += '  </tr>';
            html += '  </tr>';
            html += '    <td>Size</td>';
            html += '    <td>' + midas.escape(arrayElement['size']) + '</td>';
            html += '  </tr>';
        }
    }

    if (arrayElement['type'] == 'folder') {
        html += '  <tr>';
        html += '    <td>Last Updated</td>';
        html += '    <td>' + midas.escape(arrayElement['updated']) + '</td>';
        html += '  </tr>';
        html += '  <tr>';
        html += '    <td>Size</td>';
        html += '    <td>' + midas.escape(arrayElement['size']) + '</td>';
        html += '  </tr>';
    }
    html += '</table>';
    if (arrayElement['type'] == 'community' && arrayElement['privacy'] == 2) {
        html += '<h4>' + arrayElement.translation.Private + '</h4>';
    }

    if (arrayElement['thumbnail_id'] !== undefined && arrayElement['thumbnail_id'] != '') {
        html += '<h1>' + json.browse.preview + '</h1><a href="' + json.global.webroot + '/item/' + midas.escape(encodeURIComponent(arrayElement['item_id'])) + '"><img class="infoLogo" alt="" src="' + json.global.webroot + '/item/thumbnail?itemId=' + midas.escape(encodeURIComponent(arrayElement['item_id'])) + '" /></a>';
    }

    $('div.ajaxInfoElement').html(html);
};

/**
 * Enable selecting all of the elements in a tree view browser
 * @param opts an object with an optional callback
 */
midas.browser.enableSelectAll = function (opts) {
    'use strict';
    var default_args = {
        callback: midas.genericCallbackCheckboxes
    };
    var options = $.extend({}, default_args, opts);

    // Select/deselect all rows. If we are doing deselect all, we include
    // hidden rows
    $('#browseTableHeaderCheckbox').click(
        function () {
            var selector = this.checked ? '.treeCheckbox:visible' : '.treeCheckbox';
            $('#browseTable').find(selector).prop('checked', this.checked);
            options.callback($('#browseTable'));
        });
};

midas.enableRangeSelect = function (node) {
    'use strict';
    $('input.treeCheckbox:visible').enableCheckboxRangeSelection({
        onRangeSelect: function () {
            midas.genericCallbackCheckboxes($('#browseTable'));
        }
    });
};

$(document).ready(function () {
    'use strict';
    $('#browseTableHeaderCheckbox').qtip({
        content: 'Check/Uncheck All'
    });
});

midas.cutName = function (name, nchar) {
    'use strict';
    if (name.length > nchar) {
        name = name.substring(0, nchar) + '...';
        return name;
    }
    return name;
};
