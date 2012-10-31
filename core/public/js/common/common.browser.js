var midas = midas || {};
midas.browser = midas.browser || {};

midas.ajaxSelectRequest= '';

/**
 * Callback when a row is selected
 * Pass null if there is no selected row.
 */
midas.genericCallbackSelect = function (node) {
    if(!node || !node.attr('type')) {
        $('div.ajaxInfoElement').html('');
        $('div.viewAction ul').html('');
        return;
    }
    $('img.infoLoading').show();
    $('div.ajaxInfoElement').html('');
    if(midas.ajaxSelectRequest != '') {
        midas.ajaxSelectRequest.abort();
    }
    midas.createAction(node);
    midas.ajaxSelectRequest = $.ajax({
        type: "POST",
        url: json.global.webroot+'/browse/getelementinfo',
        data: {type: node.attr('type'), id: node.attr('element')},
        success: function (jsonContent) {
            midas.createInfo(jsonContent);
            $('img.infoLoading').hide();
        }
    });
};

midas.genericCallbackCheckboxes = function(node) {
    var arraySelected = [];
    arraySelected['folders'] = [];
    arraySelected['items'] = [];
    arraySelected['undeletableFolders'] = [];
    var folders = '';
    var items = '';
    node.find(".treeCheckbox:checked").each(
        function() {
            if($(this).parents('tr').attr('type')!='item') {
                arraySelected['folders'].push($(this).attr('element'));
                folders += $(this).attr('element') + '-';
                if($(this).parents('tr').attr('deletable')==undefined || $(this).parents('tr').attr('deletable')=='false'){
                    arraySelected['undeletableFolders'].push($(this).attr('element'));
                }
            }
            else {
                arraySelected['items'].push($(this).attr('element'));
                items += $(this).attr('element') + '-';
            }
        });
    var nselected = arraySelected['folders'].length + arraySelected['items'].length;
    if(nselected > 0) {
        $('div.viewSelected').show();
        var html = ' (' + nselected;
        html += ' ' + json.browse.element;
        if(nselected != 1) {
            html += 's';
            $('div.sideElementActions').hide();
        }
        html += ')';
        $('div.viewSelected h1 span').html(html);
        var links = '<ul>';
        links += '<li style="background-color: white;">';
        links += '  <img alt="" src="' + json.global.coreWebroot + '/public/images/icons/download.png"/> ';
        links += '  <a class="downloadSelectedLink">' + json.browse.downloadSelected + '</a></li>';
        links += '</li>';

        if(json.global.logged) {
            links += '<li style="background-color: white;">';
            links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> ';
            links += '  <a onclick="midas.deleteSelected(\''+ folders + '\',\'' + items + '\')">' + json.browse.deleteSelected + '</a></li>';
            links += '</li>';
            links += '<li>';
            links +=   '<img alt="" src="'+json.global.coreWebroot+'/public/images/icons/move.png"/> ';
            links +=   '<a onclick="midas.moveSelected(\''+folders+'\',\''+items+'\')" element="'+items+'">Move all selected</a>';
            links += '</li>';
            if(arraySelected['folders'].length == 1 && arraySelected['items'].length == 0) {
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/lock.png"/> ';
                links += '  <a onclick="midas.elementPermissions(\'folder\',\'' + folders + '\');">'+json.browse.share+'</a></li>';
                links += '</li>';
                if(arraySelected['undeletableFolders'] == 0) {
                    links += '<li style="background-color: white;">';
                    links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/edit.png"/> ';
                    links += '  <a onclick="midas.editFolder(\'' + folders + '\');">'+json.browse.editSelected+'</a></li>';
                    links += '</li>';
                }
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/folder_add.png"/> ';
                links += '  <a onclick="midas.createNewFolder(\'' + folders + '\');">'+json.browse.createFolder+'</a></li>';
                links += '</li>';
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/upload.png"/> ';
                links += '  <a rel="'+json.global.webroot+'/upload/simpleupload/?parent='+folders+'" class="uploadInFolder">'+json.browse.uploadIn+'</a></li>';
                links += '</li>';

            }
            if(arraySelected['items'].length > 0) {
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/copy.png"/> ';
                links += '  <a onclick="midas.duplicateSelected(\''+ folders + '\',\'' + items + '\')">' + json.browse.duplicateSelected + '</a></li>';
                links += '</li>';
            }
           if(arraySelected['items'].length == 1 && arraySelected['folders'].length == 0) {
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/edit.png"/> ';
                links += '  <a onclick="midas.editItem(\'' + items + '\');">'+json.browse.editSelected+'</a></li>';
                links += '</li>';
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/lock.png"/> ';
                links += '  <a onclick="midas.elementPermissions(\'item\',\'' + items + '\');">'+json.browse.share+'</a></li>';
                links += '</li>';
                links += '<li style="background-color: white;">';
                links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> ';
                links += '  <a onclick="midas.removeItem(\'' + items + '\');">'+json.browse.removeSelectedItem+'</a></li>';
                links += '</li>';
            }
            if(arraySelected['items'].length > 1 && arraySelected['folders'].length == 0) {
                links += '<li>';
                links +=   '<img alt="" src="'+json.global.coreWebroot+'/public/images/icons/page_white_stack.png"/> ';
                links +=   '<a class="mergeItemsLink" element="'+items+'">Merge items</a>';
                links += '</li>';
            }
        }
        links += '</ul>';
        $('div.viewSelected>span').html(links);
        $('a.mergeItemsLink').click(function () {
            var html ='Select a name: ';
            html+='<input type="text" id="mergeItemName" value=""/><br/><br/>';
            html+='<div id="mergeProgressBar"></div>';
            html+='<div id="mergeProgressMessage"></div>';
            html+='<input id="mergeButton" class="globalButton" type="submit" value="Merge" element="'+$(this).attr('element')+'" />';
            midas.showDialogWithContent('Merge items', html, false, {width: 300});
            $('#mergeButton').click(function () {
                if($('#mergeItemName').val() == '') {
                    midas.createNotice('You must enter an item name first', 3000, 'error');
                    return;
                }
                $(this).attr('disabled', 'disabled');
                midas.ajaxWithProgress(
                  $('#mergeProgressBar'),
                  $('#mergeProgressMessage'),
                  json.global.webroot+'/item/merge',
                  {
                      items: $('#mergeButton').attr('element'),
                      name: $('#mergeItemName').val()
                  },
                  function (data) {
                      midas.createNotice('Successfully merged items', 3000);
                      $('div.MainDialog').dialog('close');
                  }
                );
            });
        });
        $('a.downloadSelectedLink').click(function () {
            $.post(json.global.webroot+'/download/checksize', {
                folderIds: arraySelected['folders'].join(','),
                itemIds: arraySelected['items'].join(',')
            }, function (text) {
                var retVal = $.parseJSON(text);
                if(retVal.action == 'download') {
                    window.location = json.global.webroot+'/download?folders='+folderId;
                }
                else if(retVal.action == 'promptApplet') {
                    midas.promptDownloadApplet(arraySelected['folders'].join(','), 
                      arraySelected['items'].join(','), retVal.sizeStr);
                }
            });
        });

        midas.doCallback('CALLBACK_CORE_RESOURCES_SELECTED', {
            folders: arraySelected.folders,
            items: arraySelected.items,
            selectedActionsList: $('div.viewSelected>span ul')
        });
        $('div.viewSelected li a').hover(
            function(){
                $(this).parents('li').css('background-color','#E5E5E5');
            }, function() {
                $(this).parents('li').css('background-color','white');
            });
        $('div.viewSelected li a').append(' ('+nselected+')');
    }
    else {
        $('div.viewSelected').hide();
        $('div.viewSelected span').html('');
    }
};

midas.genericCallbackDblClick = function(node) {
  // no-op currently
};

midas.createNewFolder = function (id) {
    midas.loadDialog('folderId'+id,'/folder/createfolder?folderId='+id);
    midas.showDialog(json.browse.createFolder,false);
    $('#createFolderForm input[name=name]').val('');
    $('#createFolderForm textarea[name=description]').val('');
    $('#createFolderForm input[name=teaser]').val('');
};

midas.removeNodeFromTree = function (node, recursive) {
    if(!node || node.length == 0) {
        return;
    }
    var ancestorNodes = midas.ancestorsOf(node);
    if(recursive) {
        midas.removeChildren(node);
    }
    node.remove();
    // mark ancestor nodes
    for (var curNode in ancestorNodes) {
        var jCurNode = $(ancestorNodes[curNode]);
        jCurNode.find('span.elementCount').remove();
        jCurNode.find('span.elementSize').after("<img class='folderLoading'  element='"+jCurNode.attr('element')+"' alt='' src='"+json.global.coreWebroot+"/public/images/icons/loading.gif'/>");
        jCurNode.find('span.elementSize').remove();
    }
    // update folder size
    $('#browseTable').ttRenderElementsSize();
};

/**
 * Remove item from folder
 */
midas.removeItem = function (id) {
    var html='';
    html += json.browse['removeItemMessage'];
    html+='<br/>';
    html+='<br/>';
    html+='<br/>';
    html+='<div style="float: right;">';
    html+='<input class="globalButton deleteFolderYes" element="'+id+'" type="button" value="'+json.global.Yes+'"/>';
    html+='<input style="margin-left:15px;" class="globalButton deleteFolderNo" type="button" value="'+json.global.No+'"/>';
    html+='</div>';

    midas.showDialogWithContent(json.browse['delete'],html,false);

    $('input.deleteFolderYes').unbind('click').click(
        function() {
            var node = $('table.treeTable tr[type=item][element='+id+']');
            var folder = midas.parentOf(node);
            var folderId = '';
            // we are in a subfolder view and the parent is the current folder
            if(folder) {
                folderId = folder.attr('element');
            }
            else {
                folderId = json.folder.folder_id;
            }

            $.post(json.global.webroot+'/folder/removeitem',
                   {folderId: folderId, itemId: id},
                   function(data) {
                       jsonResponse = jQuery.parseJSON(data);
                       if(jsonResponse==null) {
                           midas.createNotice('Error',4000);
                           return;
                       }
                       if(jsonResponse[0]) {
                           midas.createNotice(jsonResponse[1],1500);
                           $( "div.MainDialog" ).dialog('close');
                           midas.removeNodeFromTree(node, false);
                           midas.genericCallbackCheckboxes($('#browseTable'));
                           midas.genericCallbackSelect(null);
                       }
                       else {
                           midas.createNotice(jsonResponse[1],4000);
                       }
                   });
        });
    $('input.deleteFolderNo').unbind('click').click(
        function() {
            $( "div.MainDialog" ).dialog('close');
        });
};

/**
 * Delete Item
 */
midas.deleteItem = function (id) {
            $.ajax({
            type: "GET",
            url: json.global.webroot+'/item/checkshared',
            data: {itemId: id},
            success: function (jsonContent) {
                var $itemIsShared = $.parseJSON(jsonContent);
                var html='';
                if ($itemIsShared == true) {
                    html+=json.item.message['sharedItem'];
                }
                html+=json.browse['deleteItemMessage'];
                html+='<br/>';
                html+='<br/>';
                html+='<br/>';
                html+='<div style="float: right;">';
                html+='<input class="globalButton deleteItemYes" element="'+id+'" type="button" value="'+json.global.Yes+'"/>';
                html+='<input style="margin-left:15px;" class="globalButton deleteItemNo" type="button" value="'+json.global.No+'"/>';
                html+='</div>';

                midas.showDialogWithContent(json.browse['delete'], html, false);

                $('input.deleteItemYes').unbind('click').click(function() {
                    location.replace(json.global.webroot+'/item/delete?itemId='+id);
                });
                $('input.deleteItemNo').unbind('click').click(function() {
                    $( "div.MainDialog" ).dialog('close');
                });
            }
        });
};

midas.deleteFolder = function (id) {
    var html = '';
    html += json.browse['deleteFolderMessage'];
    html += '<br/><br/>';
    html += '<div id="deleteFolderProgress"></div>';
    html += '<div id="deleteFolderProgressMessage"></div><br/><br/>';
    html += '<div style="float: right;">';
    html += '<input class="globalButton deleteFolderYes" element="'+id+'" type="button" value="' + json.global.Yes + '"/>';
    html += '<input style="margin-left:15px;" class="globalButton deleteFolderNo" type="button" value="' + json.global.No + '"/>';
    html += '</div>';

    midas.showDialogWithContent(json.browse['delete'], html, false);

    $('input.deleteFolderYes').unbind('click').click(function () {
        $(this).attr('disabled', 'disabled');
        var node = $('table.treeTable tr.parent[element='+id+']');
        midas.ajaxWithProgress(
          $('#deleteFolderProgress'),
          $('#deleteFolderProgressMessage'),
          json.global.webroot+'/folder/delete',
          {folderId: id},
          function(data) {
            $('input.deleteFolderYes').removeAttr('disabled');
            jsonResponse = jQuery.parseJSON(data);
            if(jsonResponse==null) {
                midas.createNotice('Error', 4000, 'error');
                return;
            }
            if(jsonResponse[0]) {
                midas.createNotice(jsonResponse[1], 1500);
                $('div.MainDialog').dialog('close');
                midas.removeNodeFromTree(node, true);
                midas.genericCallbackCheckboxes($('#browseTable'));
                midas.genericCallbackSelect(null);
            }
            else {
                midas.createNotice(jsonResponse[1],4000, 'error');
            }
        });
    });
    $('input.deleteFolderNo').unbind('click').click(function() {
        $('div.MainDialog').dialog('close');
    });
};

/**
 * Deletes the set of folders and items selected with the checkboxes.
 * The folders and items params should be strings of ids separated by - (empty
 * ids will be ignored)
 */
midas.deleteSelected = function (folders, items) {
    var html='';
    html+=json.browse['deleteSelectedMessage'];
    html+='<br/><br/><br/>';
    html+='<form class="genericForm"><div class="dialogButtons">';
    html+='  <input class="globalButton deleteSelectedYes" type="button" value="' + json.global.Yes + '"/>';
    html+='  <input class="globalButton deleteSelectedNo" type="button" value="' + json.global.No + '"/>';
    html+='</div></form>';
    html+='<img id="deleteSelectedLoadingGif" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>';

    midas.showDialogWithContent(json.browse['deleteSelected'],html,false);
    $('input.deleteSelectedYes').unbind('click').click(function() {
        $('input.deleteSelectedYes').attr('disabled', 'disabled');
        $('input.deleteSelectedNo').attr('disabled', 'disabled');
        $('#deleteSelectedLoadingGif').show();
        $.post(json.global.webroot+'/browse/delete', {folders: folders, items: items}, function(data) {
            $('input.deleteSelectedYes').removeAttr('disabled');
            $('input.deleteSelectedNo').removeAttr('disabled');
            $('#deleteSelectedLoadingGif').hide();
            var resp = jQuery.parseJSON(data);
            if(resp == null) {
                midas.createNotice('Error during folder delete. Check the log.', 4000);
                return;
            }
            if(resp.success) {
                var message = 'Deleted ' + resp.success.folders.length + ' folders and ';
                message += resp.success.items.length + ' items.';
                if(resp.failure.folders.length || resp.failure.items.length) {
                    message += ' Invalid delete permissions on ';
                    message += resp.failure.folders.length + ' folders and ';
                    message += resp.failure.items.length + ' items.';
                }
                midas.createNotice(message, 5000);
                $('div.MainDialog').dialog('close');
                for (var curFolder in resp.success.folders) {
                    midas.removeNodeFromTree($('table.treeTable tr.parent[element='+resp.success.folders[curFolder]+']'), true);
                }
                for (var curItem in resp.success.items) {
                    midas.removeNodeFromTree($('table.treeTable tr[type=item][element='+resp.success.items[curItem]+']'), false);
                }
                midas.genericCallbackCheckboxes($('#browseTable'));
                midas.genericCallbackSelect(null);
            }
        });
    });
    $('input.deleteSelectedNo').unbind('click').click(function() {
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
    midas.loadDialog("duplicateItem", "/browse/movecopy/?duplicate=true&items="+items);
    var title = 'Copy selected items';
    if(folders != '') {
        title += ' ' + json.browse.ignoreSelectedFolders;
    }
    midas.showDialog(title);
};

/**
 * Copy or symlink a single item
 */
midas.duplicateItem = function (item) {
    midas.loadDialog("duplicateItem", "/browse/movecopy/?duplicate=true&items="+item);
    midas.showDialog('Copy item');
};

/**
 * Prompt the user with a dialog to move the selected items and folders.
 * @param folders The list of folders to move (separated by -)
 * @param items The list of items to move (separated by -)
 */
midas.moveSelected = function (folders, items) {
    midas.loadDialog("moveItem", "/browse/movecopy/?move=true&items="+items+"&folders="+folders);
    midas.showDialog('Move all selected resources');
};

/**
 * Helper method to remove all of a node's subtree from the treeTable view.
 * Expects a jquerified node object.
 */
midas.removeChildren = function (node) {
    node.each(
        function() {
            var children = $("table.treeTable tbody tr.child-of-" + this.id);
            $(children).each(
                function(){
                    midas.removeChildren($(this));
                    $(this).remove();
                });
        });
};

midas.editFolder = function (id) {
    midas.loadDialog("editFolder" + id,"/folder/edit?folderId=" + id);
    midas.showDialog(json.browse.edit, false);
};

midas.moveFolder = function (id) {
    midas.loadDialog("moveFolder"+id,"/browse/movecopy?move=true&folders="+id);
    midas.showDialog(json.browse.move);
};

midas.moveItem = function (itemId, fromFolderId) {
    midas.loadDialog("moveItem"+itemId,"/browse/movecopy?move=true&items="+itemId+"&from="+fromFolderId);
    midas.showDialog(json.browse.move);
};

midas.shareItem = function (itemId) {
        midas.loadDialog("shareItem"+itemId,"/browse/movecopy/?share=true&items="+itemId);
        midas.showDialog(json.browse.shareitem);
};

midas.duplicateItem = function (itemId) {
        midas.loadDialog("duplicateItem"+itemId,"/browse/movecopy/?duplicate=true&items="+itemId);
        midas.showDialog(json.browse.duplicate);
};

midas.editItem = function (itemId) {
        midas.loadDialog("editItem"+itemId,"/item/edit?itemId="+itemId);
        midas.showDialog(json.browse.edit, false, {
            width: 545
        });
};

midas.elementPermissions = function (elementType, elementId) {
        midas.loadDialog("sharing"+elementType+elementId,"/share/dialog?type="+elementType+'&element='+elementId);
        midas.showDialog(json.browse.share);
};

midas.parentOf = function (node) {
    var classNames = node[0].className.split(' ');

    for(key in classNames) {
        if(classNames[key].match("child-of-")) {
            return $("#" + classNames[key].substring(9));
        }
    }
};

midas.ancestorsOf = function (node) {
    var ancestors = [];
    while((node = midas.parentOf(node))) {
        ancestors[ancestors.length] = node[0];
    }
    return ancestors;
};

midas.createAction = function (node) {
    var type = node.attr('type');
    var element = node.attr('element');
    var policy = node.attr('policy');
    var deletable = node.attr('deletable');
    var header = '<h1>Selected '+type+'</h1>';
    $('div.viewAction ul').fadeOut('fast', function() {
        $('div.viewAction ul').html(header);
        var html = '';
        if(type=='community') {
            html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/view.png"/> <a href="'+json.global.webroot+'/community/'+element+'">'+json.browse.view+'</a></li>';
        }
        if(type=='folder') {
            html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/view.png"/> <a href="'+json.global.webroot+'/folder/'+element+'">'+json.browse.view+'</a></li>';
            html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/download.png"/> <a element="'+element+'" class="downloadFolderLink">'+json.browse.download+'</a></li>';
            html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/link.png"/> <a type="folder" element="'+element+'" href="javascript:;" class="getResourceLinks">Share</a></li>';
            if(policy>=1) {
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/folder_add.png"/> <a onclick="midas.createNewFolder('+element+');">'+json.browse.createFolder+'</a></li>';
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/upload.png"/> <a rel="'+json.global.webroot+'/upload/simpleupload/?parent='+element+'" class="uploadInFolder">'+json.browse.uploadIn+'</a></li>';
                if(node.attr('deletable')!=undefined && node.attr('deletable')=='true') {
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/edit.png"/> <a onclick="midas.editFolder('+element+');">'+json.browse.edit+'</a></li>';
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/move.png"/> <a onclick="midas.moveFolder('+element+');">'+json.browse.move+'</a></li>';
                }
            }
            if(policy>=2) {
                if(deletable!=undefined && deletable=='true') {
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> <a onclick="midas.deleteFolder('+element+');">'+json.browse['delete']+'</a></li>';
                }              
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/lock.png"/> <a onclick="midas.elementPermissions(\''+ type + '\',\'' + element + '\');">'+json.browse.share+'</a></li>';
            }
            if(policy>=1) {
                if(deletable!=undefined && deletable=='true') {
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/move.png"/> <a onclick="midas.moveFolder('+element+');">'+json.browse.move+'</a></li>';
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/edit.png"/> <a onclick="midas.editFolder('+element+');">'+json.browse.edit+'</a></li>';
                }
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/folder_add.png"/> <a onclick="midas.createNewFolder('+element+');">'+json.browse.createFolder+'</a></li>';
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/upload.png"/> <a rel="'+json.global.webroot+'/upload/simpleupload/?parent='+element+'" class="uploadInFolder">'+json.browse.uploadIn+'</a></li>';
            }

        }
        if(type == 'item') {
            var from = midas.parentOf(node);
            if(from) {
                var fromFolder = from.attr('element');
            }
            else { // we are in a subfolder view and the parent is the current folder
                var fromFolder = json.folder.folder_id;
            }
            html += '<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/view.png"/> <a href="'+json.global.webroot+'/item/'+element+'">'+json.browse.view+'</a></li>';
            html += '<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/download.png"/> <a href="'+json.global.webroot+'/download?items='+element+'">'+json.browse.download+'</a></li>';
            html += '<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/copy.png"/> <a onclick="midas.duplicateItem(\''+element+'\');">Copy</a></li>';
            html += '<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/link.png"/> <a type="item" element="'+element+'" href="javascript:;" class="getResourceLinks">Share</a></li>';
            if (policy>=2) {
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> <a onclick="midas.deleteItem(\'' + element + '\');">'+json.browse.deleteItem+'</a></li>';
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/lock.png"/> <a onclick="midas.elementPermissions(\''+ type + '\',\'' + element + '\');">'+json.browse.share+'</a></li>';
            }
            if (policy>=1) {
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/move.png"/> <a onclick="midas.moveItem(\''+ element + '\',\'' + fromFolder + '\');">'+json.browse.move+'</a></li>';
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/item-share.png"/> <a onclick="midas.shareItem(\''+ element + '\');">'+json.browse.shareitem+'</a></li>';
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/copy.png"/> <a onclick="midas.duplicateItem(\''+ element + '\');">'+json.browse.duplicate+'</a></li>';
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/edit.png"/> <a onclick="midas.editItem(\''+ element + '\');">'+json.browse.edit+'</a></li>';
                html+='<li class="removeItemLi"><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> <a onclick="midas.removeItem('+element+');">'+json.browse['removeItem']+'</a></li>';
            }
        }
        $('div.viewAction ul').append(html);

        midas.doCallback('CALLBACK_CORE_RESOURCE_HIGHLIGHTED', {
            type: type,
            id: element,
            actionsList: $('div.viewAction ul')
        });

        $('div.viewAction li a').hover(
            function() {
                $(this).parents('li').css('background-color','#E5E5E5');
            },
            function() {
                $(this).parents('li').css('background-color','white');
            });

        $('a.uploadInFolder').click(function () {
            var button = $('li.uploadFile');
            button.attr('rel', $(this).attr('rel'));
            midas.resetUploadButton();
            button.click();
        });

        $('a.sharingLink').click(function () {
            midas.loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
            midas.showDialog(json.browse.share);
        });
        $('a.getResourceLinks').click(function () {
            midas.loadDialog("links"+$(this).attr('type')+$(this).attr('element'),'/share/links?type='+$(this).attr('type')+'&id='+$(this).attr('element'));
            midas.showDialog('Link to this item');
        });
        $('a.downloadFolderLink').click(function () {
            var folderId = $(this).attr('element');
            $.post(json.global.webroot+'/download/checksize', {
                folderIds: folderId
            }, function (text) {
                var retVal = $.parseJSON(text);
                if(retVal.action == 'download') {
                    window.location = json.global.webroot+'/download?folders='+folderId;
                }
                else if(retVal.action == 'promptApplet') {
                    midas.promptDownloadApplet(folderId, '', retVal.sizeStr);
                }
            });
        });
        $('div.viewAction ul').fadeIn('fast');
    });
};

/**
 * Prompts the user to choose between normal zipstream download and applet downloader.
 * Called if the requested download is suitably large.
 */
midas.promptDownloadApplet = function(folderIds, itemIds, sizeString) {
    var html = 'Warning: you have requested a large download ('+sizeString+') that might take a very long time to complete.';
    html += ' It is recommended that you use the large data download applet in case your connection is interrupted. '+
    'Would you like to use the applet?';
    
    html += '<div style="margin-top: 20px; float: right">';
    html += '<input type="button" style="margin-left: 0px;" class="globalButton useLargeDataApplet" value="Yes, use large downloader"/>';
    html += '<input type="button" style="margin-left: 10px;" class="globalButton useZipStream" value="No, use normal download"/>';
    html += '</div>';
    midas.showDialogWithContent('Large download requested', html, false, {width: 480});

    $('input.useLargeDataApplet').unbind('click').click(function () {
        window.location = json.global.webroot+'/download/applet?folderIds='+folderIds+'&itemIds='+itemIds;
        $('div.MainDialog').dialog('close');
    });
    $('input.useZipStream').unbind('click').click(function () {
        window.location = json.global.webroot+'/download?folders='+folderIds.split(',').join('-')
                        + '&items='+itemIds.split(',').join('-');
        $('div.MainDialog').dialog('close');
    });
};

midas.createInfo = function (jsonContent) {
    var arrayElement = jQuery.parseJSON(jsonContent);
    var html='';
    if(arrayElement['type']=='community') {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/community-big.png" />';
    }
    else if(arrayElement['type']=='folder') {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/folder-big.png" />';
    }
    else {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/document-big.png" />';
    }
    html+='<span class="infoTitle" >'+arrayElement['name']+'</span>';
    html+='<table>';
    html+='  <tr>';
    html+='    <td>'+arrayElement.translation.Created+'</td>';
    html+='    <td>'+arrayElement.creation+'</td>';
    html+='  </tr>';
    if(arrayElement['type']=='community') {
        html+='  <tr>';
        html+='    <td>Members';
        html += '</td>';
        html+='    <td>'+arrayElement['members']+'</td>';
        html+='  </tr>';
    }
    if(arrayElement['type']=='item') {
        if(arrayElement['norevisions'] == true) {
            html+='  <tr>';
            html+='    <td>No Revisions</td>';
            html+='  </tr>';
            html+='  <tr>';
        }
        else {
            html+='  <tr>';
            html+='    <td>'+arrayElement.translation.Uploaded+'</td>';
            html+='    <td><a href="'+json.global.webroot+'/user/'+arrayElement['uploaded']['user_id']+'">'+arrayElement['uploaded']['firstname']+' '+arrayElement['uploaded']['lastname']+'</a></td>';
            html+='  </tr>';
            html+='  <tr>';
            html+='    <td>Revisions</td>';
            html+='    <td>'+arrayElement['revision']['revision']+'</td>';
            html+='  </tr>';
            html+='  <tr>';
            html+='    <td>Files</td>';
            html+='    <td>'+arrayElement['nbitstream']+'</td>';
            html+='  </tr>';
            html+='  </tr>';
            html+='    <td>Size</td>';
            html+='    <td>'+arrayElement['sizebytes']+' B</td>';
            html+='  </tr>';
        }
    }

    if(arrayElement['type']=='folder') {
        html+='  <tr>';
        html+='    <td>Last Updated</td>';
        html+='    <td>'+arrayElement['updated']+'</td>';
        html+='  </tr>';
        html+='  <tr>';
        html+='    <td>Size</td>';
        html+='    <td>'+arrayElement['sizebytes']+' B</td>';
        html+='  </tr>';
    }
    html+='</table>';
    if(arrayElement['type']=='community'&&arrayElement['privacy']==2) {
        html+='<h4>'+arrayElement.translation.Private+'</h4>';
    }

  if(arrayElement['thumbnail_id']!=undefined&&arrayElement['thumbnail_id']!='')
    {
    html+='<h1>'+json.browse.preview+'</h1><a href="'+json.global.webroot+'/item/'+arrayElement['item_id']+'"><img class="infoLogo" alt="" src="'+json.global.webroot+'/item/thumbnail?itemId='+arrayElement['item_id']+'" /></a>';
    }

    $('div.ajaxInfoElement').html(html);
};

midas.cutName = function(name, nchar) {

  if(name.length>nchar)
      {
      name=name.substring(0,nchar)+'...';
      return name;
      }
  return name;
};

/**
 * Enable selecting all of the elements in a treeview browser
 * @param opts an object with an optional callback
 */
midas.browser.enableSelectAll = function (opts) {
    var default_args = { callback: midas.genericCallbackCheckboxes };
    var options = $.extend({}, default_args, opts);

    // Select/deslect all rows. If we are doing deselect all, we include
    // hidden rows
    $('#browseTableHeaderCheckbox').click(
        function() {
            var selector = this.checked ? '.treeCheckbox:visible' : '.treeCheckbox';
            $('#browseTable').find(selector).prop("checked", this.checked);
            options.callback($('#browseTable'));
        });
};

midas.enableRangeSelect = function (node) {
    $('input.treeCheckbox:visible').enableCheckboxRangeSelection(
        {
            onRangeSelect: function() {
                midas.genericCallbackCheckboxes($('#browseTable'));
            }
        });
};

$(document).ready(function () {
    $('#browseTableHeaderCheckbox').qtip({
        content: 'Check/Uncheck All'
    });
});
