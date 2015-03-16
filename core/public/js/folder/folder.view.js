// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    $("#browseTable").treeTable({
        onFirstInit: midas.enableRangeSelect,
        onNodeShow: midas.enableRangeSelect,
        onNodeHide: midas.enableRangeSelect
    });
    /**
     * Non-ajax'd pages (ones with only items in the view) do not get their
     * init callback made, so we manually initialize range selection
     * before the items are made visible asynchronously
     */
    $('input.treeCheckbox').enableCheckboxRangeSelection({
        onRangeSelect: function () {
            midas.genericCallbackCheckboxes($('#browseTable'));
        }
    });

    $('a.sharingLink').click(function () {
        midas.loadDialog("sharing" + $(this).attr('type') + $(this).attr('element'), "/share/dialog?type=" + encodeURIComponent($(this).attr('type')) + '&element=' + encodeURIComponent($(this).attr('element')));
        midas.showDialog(json.browse.share);
    });
    $('a.getResourceLinks').click(function () {
        midas.loadDialog("links" + $(this).attr('type') + $(this).attr('element'), '/share/links?type=' + encodeURIComponent($(this).attr('type')) + '&id=' + encodeURIComponent($(this).attr('element')));
        midas.showDialog('Link to this item');
    });
    $('a.uploadInFolder').click(function () {
        var button = $('li.uploadFile');
        button.attr('rel', $(this).attr('rel'));
        midas.resetUploadButton();
        button.click();
    });
    $('a.downloadFolderLink').click(function () {
        var folderId = $(this).attr('element');
        $.post(json.global.webroot + '/download/checksize', {
            folderIds: folderId
        }, function (text) {
            var retVal = $.parseJSON(text);
            if (retVal.action == 'download') {
                window.location = json.global.webroot + '/download?folders=' + encodeURIComponent(folderId);
            }
            else if (retVal.action == 'promptApplet') {
                midas.doCallback('CALLBACK_CORE_PROMPT_APPLET', {
                    folderIds: folderId,
                    itemIds: '',
                    sizeString: retVal.sizeStr
                });
            }
        });
    });

    /**
     * Select/deselect all rows. If we are doing deselect all, we include hidden
     * ones
     */
    midas.browser.enableSelectAll();

    $("img.tableLoading").hide();
    $("table#browseTable").show();

    $("#tabsGeneric").tabs();
    $("#tabsGeneric").show();

    if ($('.pathBrowser li').length > 5) {
        while ($('.pathBrowser li').length > 5) {
            $('.pathBrowser li:first').remove();
        }
        $('.pathBrowser li:first').before('<li><span>...</span></li>');
    }
    $('.pathBrowser').show();
});

// dependence: common/browser.js
midas.ajaxSelectRequest = '';

function callbackSelect(node) {
    'use strict';
    midas.genericCallbackSelect(node);
}

function callbackDblClick(node) {
    'use strict';
    midas.genericCallbackDblClick(node);
}

function callbackCheckboxes(node) {
    'use strict';
    midas.genericCallbackCheckboxes(node);
}

$(document).ready(function () {
    'use strict';
    $('div.viewAction').show();
});

$(window).load(function () {
    'use strict';
    $.ajax({
        type: 'POST',
        url: json.global.webroot + '/browse/getelementinfo',
        data: {
            type: 'folder',
            id: json.folder.folder_id
        },
        success: function (jsonContent) {
            midas.createInfo(jsonContent);
            $('img.infoLoading').hide();
        }
    });
});
