// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
    // init tree
    $('img.tabsLoading').hide();

    $('div.sideElementFirst').show();
    $('div.viewAction').hide();

    $('table')
        .filter(function () {
            return this.id.match(/browseTable*/);
        })
        .treeTable({
            disableElementSize: true
        });

    $('img.tableLoading').hide();
    $('table#browseTable').show();

    $('div.communityList').hide();

    initDragAndDrop();

});

// dependence: common/browser.js
var ajaxSelectRequest = '';

function callbackSelect(node) {
    'use strict';
    $('div.genericAction').hide();
    $('div.genericCommunities').hide();
    $('div.genericStats').hide();

    // user need to have at least written permission to see specific Actions
    //(edit, delete, etc...)
    if (node.attr('type') !== 0) {
        $('div.viewInfo').show();
        $('div.viewAction').show();
    }

    midas.genericCallbackSelect(node);
}

function callbackDblClick(node) {}

function callbackCheckboxes(node) {
    'use strict';
    midas.genericCallbackCheckboxes(node);
}

function callbackCreateElement(node) {
    'use strict';
    initDragAndDrop();
}

function initDragAndDrop() {
    'use strict';
    $('#browseTable .file, #browseTable .filePublic, #browseTable .filePrivate,' +
        '#browseTable .folderPublic:not(.notdraggable), #browseTable .folderPrivate:not(.notdraggable)').draggable({
            helper: 'clone',
            cursor: 'move',
            opacity: 0.75,
            refreshPositions: true, // Performance?
            revert: 'invalid',
            revertDuration: 300,
            scroll: true,
        // Show communities when user starts to drag items
            start: function () {
                $('div.communityList').show();
            }
        });

    $('#browseTable .folder, #browseTable .folderPrivate, #browseTable .folderPublic').each(function () {
        // Configure droppable folders/items
        $(this).parents('tr:[policy!=0]').droppable({
            accept: '.file, .filePublic, .filePrivate, .folder, .folderPublic, .folderPrivate',
            drop: function (e, ui) {
                // Call jQuery treeTable plugin to move the branch
                var elements = '';
                if ($(ui.draggable).parents('tr').attr('type') == 'folder') {
                    elements = $(ui.draggable).parents('tr').attr('element') + ';';
                }
                else {
                    elements = ';' + $(ui.draggable).parents('tr').attr('element');
                }
                var from_obj;
                var classNames = $(ui.draggable).parents('tr').attr('class').split(' ');
                for (var key in classNames) {
                    if (classNames[key].match('child-of-')) {
                        from_obj = '#' + classNames[key].substring(9);
                    }
                }
                var destination_obj = this;

                // do nothing if drop item(s) to its current folder, otherwise move item(s)
                if ($(this).attr('id') != $(from_obj).attr('id')) {
                    $.post(json.global.webroot + '/browse/movecopy', {
                        moveElement: true,
                        elements: elements,
                        destination: $(destination_obj).attr('element'),
                        from: $(from_obj).attr('element'),
                        ajax: true
                    },
                        function (data) {
                            var jsonResponse = $.parseJSON(data);
                            if (jsonResponse === null) {
                                midas.createNotice('Error', 4000);
                                return;
                            }
                            if (jsonResponse[0]) {
                                midas.createNotice(jsonResponse[1], 1500);
                                $($(ui.draggable).parents('tr')[0]).appendBranchTo(destination_obj);
                            }
                            else {
                                midas.createNotice(jsonResponse[1], 4000);
                            }
                        });
                }

            },
            hoverClass: 'accept',
            over: function (e, ui) {
                // Make the droppable branch expand when a draggable node is moved over it.
                if (this.id != $(ui.draggable.parents('tr')[0]).id && !$(this).is('.expanded')) {
                    $(this).expand();
                }
            }
        });

        // Configure non-drappable folders/items
        $(this).parents('tr:[policy=0]').droppable({
            revert: true,
            // Make the droppable branch expand when a draggable node is moved over it.
            over: function (e, ui) {
                if (!$(this).is('.expanded')) {
                    $(this).expand();
                }
            }
        });

        // qtip pop-up for folders with only read permission
        $(this).parents('tr:[policy=0]').qtip({
            content: 'You do not have write permission on this folder and cannot drop item(s) to it !',
            show: 'mouseover',
            hide: 'mouseout',
            position: {
                target: 'mouse',
                my: 'bottom left',
                viewport: $(window), // Keep the qtip on-screen at all times
                effect: true // Disable positioning animation
            }
        });

    });
}
