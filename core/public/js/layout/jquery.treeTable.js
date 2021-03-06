// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global callbackCheckboxes */
/* global callbackCreateElement */
/* global callbackCustomElements */
/* global callbackDblClick */
/* global callbackReloadNode */
/* global callbackSelect */
/* global json */
/* global sliceFileName */

(function ($) {
    'use strict';
    var defaultPaddingLeft;

    $.fn.treeTable = function (opts) {
        var table = $(this);
        var options = $.extend({}, $.fn.treeTable.defaults, opts);

        // default to global callbacks in none were passed
        if (options.callbackSelect === null && typeof callbackSelect == 'function') {
            options.callbackSelect = callbackSelect;
        }
        if (options.callbackCheckboxes === null && typeof callbackCheckboxes == 'function') {
            options.callbackCheckboxes = callbackCheckboxes;
        }
        if (options.callbackDblClick === null && typeof callbackDblClick == 'function') {
            options.callbackDblClick = callbackDblClick;
        }
        if (options.callbackCreateElement === null && typeof callbackCreateElement == 'function') {
            options.callbackCreateElement = callbackCreateElement;
        }
        if (options.callbackReloadNode === null && typeof callbackReloadNode == 'function') {
            options.callbackReloadNode = callbackReloadNode;
        }
        if (options.callbackCustomElements === null && typeof callbackCustomElements == 'function') {
            options.callbackCustomElements = callbackCustomElements;
        }

        options.sort = 'name';
        options.sortdir = 'asc';

        // Store the options in the dom (on the table element) for later lookup
        $.data(table[0], 'options', options);

        var tmp = this.each(function () {
            $(this).addClass('treeTable').find('tbody tr').each(function () {
                if (!options.expandable || $(this)[0].className.search(options.childPrefix) == -1) {
                    if (isNaN(defaultPaddingLeft)) {
                        defaultPaddingLeft = parseInt($($(this).children("td:first")[options.treeColumn]).css('padding-left'), 10);
                    }
                    $(this).ttInitNode();
                } else if (options.initialState == 'collapsed') {
                    $(this).hide();
                }
            });
            $(this).ttRenderElementsSize();
        });
        $(this).ttInitTable();
        if ($.isFunction(options.onFirstInit)) {
            options.onFirstInit.call();
        }

        table.find('th.thData').click(function () {
            $(this).ttSortClicked('name');
        });
        table.find('th.thSize').click(function () {
            $(this).ttSortClicked('size');
        });
        table.find('th.thDate').click(function () {
            $(this).ttSortClicked('date');
        });

        return tmp;
    };

    $.fn.treeTable.defaults = {
        childPrefix: "child-of-",
        clickableNodeNames: true,
        expandable: true,
        onFirstInit: null,
        onNodeShow: null,
        onNodeHide: null,
        indent: 9,
        initialState: "collapsed",
        treeColumn: 0,
        pageLength: 100,
        disableElementSize: false,

        callbackSelect: null,
        callbackCheckboxes: null,
        callbackDblClick: null,
        callbackCreateElement: null,
        callbackReloadNode: null,
        callbackCustomElements: null
    };

    /**
     * When a header field is clicked, we want to sort by that header field
     */
    $.fn.ttSortClicked = function (fieldname) {
        var table = $(this).ttTable();
        table.find('th.thData,th.thSize,th.thDate').ttShowNoSort();
        var options = table.ttOptions();
        var sortdir = 'asc';
        if (options.sort == fieldname && options.sortdir == 'asc') {
            sortdir = 'desc';
        }
        table.ttSetOption('sort', fieldname);
        table.ttSetOption('sortdir', sortdir);
        $(this).ttShowSort(sortdir);
        table.ttReloadTree();
    };

    $.fn.ttShowNoSort = function () {
        $(this).removeClass('sortasc sortdesc');
        $(this).addClass('sortnone');
    };

    $.fn.ttShowSort = function (sortdir) {
        $(this).removeClass('sortnone sortasc sortdesc');
        $(this).addClass('sort' + sortdir);
    };

    // Collapse a branch
    $.fn.collapse = function () {
        var node = $(this);
        var id = node.attr('id');
        var table = node.ttTable();
        table.find('tr[id*="' + id + '"]').removeClass('expanded').addClass('collapsed').hide();
        $(this).show();
        table.ttColorLines();
        return this;
    };

    // Expand a node of the tree
    $.fn.expand = function () {
        var node = $(this);
        var table = node.ttTable();
        var options = node.ttOptions();

        // If the node has not been fetched yet, we fetch it with ajax
        if (!node.attr('fetched')) {
            node.ttFetchChildren();
            table.ttInitTable();
            node.ttInitNode();
        } else {
            node.ttChildren().each(function () {
                $(this).ttInitNode();
                $(this).show();
            });
            if ($.isFunction(options.onNodeShow)) {
                options.onNodeShow.call(this);
            }
        }
        node.removeClass('collapsed').addClass('expanded');
        return this;
    };

    /**
     * Display or hide the loading indicator on a row
     * @param loading Boolean value for loading state
     */
    $.fn.ttToggleLoading = function (loading) {
        var node = $(this);
        if (loading && node.find('img.fetchingChildren').length > 0) {
            return; //image is already displayed
        }
        if (loading) {
            node.find('td:first').append(' <img class="fetchingChildren" alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif"/>');
        } else {
            node.find('img.fetchingChildren').remove();
        }
    };

    /**
     * Use ajax to fetch the children of a node.  The child data will be stored
     * as data on the node's dom element
     */
    $.fn.ttFetchChildren = function () {
        var node = $(this);
        if (node === undefined) {
            return;
        }
        var table = node.ttTable();
        var options = table.ttOptions();

        var data = $.data(node[0], 'children');
        var itemOffset = 0,
            folderOffset = 0;
        if (data && data.itemOffset) {
            itemOffset = data.itemOffset;
        }
        if (data && data.folderOffset) {
            folderOffset = data.folderOffset;
        }

        // Mark the node as fetched so we don't try to fetch it multiple times
        node.attr('fetched', 'true');
        node.ttToggleLoading(true);

        $.post(json.global.webroot + '/browse/getfolderscontent', {
            folders: node.attr('element'),
            sort: options.sort,
            sortdir: options.sortdir,
            limit: options.pageLength,
            itemOffset: itemOffset,
            folderOffset: folderOffset
        }, function (data) {
            var children = jQuery.parseJSON(data);
            // Store the children in the dom node
            for (var key in children) {
                if (children.hasOwnProperty(key)) {
                    $.data(node[0], 'children', children[key]);
                    break; //just fetching one folder at a time for now.
                }
            }
            // Render the children (one chunk at a time)
            node.ttRenderChildren();
            table.ttRenderElementsSize();

            // Table state has changed so we call init table
            table.ttInitTable();
            node.ttToggleLoading(false);
            if ($.isFunction(options.onNodeShow)) {
                options.onNodeShow.call(node);
            }
        });
    };

    /**
     * Once the data has been loaded onto a node, call this to actually
     * render the data in the tree.
     */
    $.fn.ttRenderChildren = function () {
        var node = $(this);
        var table = node.ttTable();
        var options = table.ttOptions();
        var elements = $.data(node[0], 'children');
        var lastchild = $.data(node[0], 'lastchild');
        var html = '';
        var offset = $.data(node[0], 'offset');
        if (!offset) {
            offset = 0;
        }

        if (typeof options.callbackCustomElements == 'function') {
            html = options.callbackCustomElements(node, elements);
            node.after(html);
        } else {
            var id = node.attr('id');
            var i = offset;

            // Calculate how many characters we should truncate to based on node depth
            var sliceValue = 55 - (id.split('-').length - 1) * 3;

            // Render the child folders
            $.each(elements.folders, function (index, value) {
                i++;
                var privacyClass;
                if (value.privacy_status === 0) { //public
                    privacyClass = 'Public';
                } else {
                    privacyClass = 'Private';
                }

                html += "<tr id='" + id + "-" + i + "' privacy='" + value.privacy_status + "' class='parent child-of-" + id + "' type='folder' element='" + value.folder_id + "'>";
                html += "  <td><span class='folder" + privacyClass + "'>" + sliceFileName(value.name, sliceValue) + "</span></td>";
                html += "  <td><img class='folderLoading' element='" + value.folder_id + "' alt='' src='" + json.global.coreWebroot + "/public/images/icons/loading.gif'/></td>";
                html += "  <td>" + value.date_update + "</td>";
                html += "  <td><input type='checkbox' class='treeCheckbox' type='folder' element='" + value.folder_id + "'/></td>";
                html += "</tr>";
                $.data(node[0], 'lastchild', id + "-" + i);
            });

            // Render the child items
            $.each(elements.items, function (index, value) {
                i++;
                var privacyClass;
                if (value.privacy_status === 0) { //public
                    privacyClass = 'Public';
                } else {
                    privacyClass = 'Private';
                }
                html += "<tr id='" + id + "-" + i + "' class='child-of-" + id + "' privacy='" + value.privacy_status + "' type='item' element='" + value.item_id + "'>";
                html += "  <td><span class='file" + privacyClass + "'>" + sliceFileName(value.name, sliceValue) + "</span></td>";
                html += "  <td>" + value.size + "</td>";
                html += "  <td>" + value.date_update + "</td>";
                html += "  <td><input type='checkbox' class='treeCheckbox' type='item' element='" + value.item_id + "'/></td>";
                html += "</tr>";
                $.data(node[0], 'lastchild', id + "-" + i);
            });
            $.data(node[0], 'offset', i);

            if (elements.showMoreLink) {
                html += "<tr class='child-of-" + id + "' id='" + id + "-10000000' element='" + id + "'>" +
                    "<td colspan='1' align='right'><a class='treeBrowserShowMore'>Show more</a></td><td></td><td></td><td></td></tr>";
            }
            if (lastchild === undefined) {
                // We are rendering the first page
                node.after(html);
            } else {
                // We are rendering a subsequent page
                table.find('#' + lastchild).after(html);
            }
        }

        // Bind "Show more" action
        table.find('a.treeBrowserShowMore:visible').unbind('click').click(function () {
            var showMoreRow = $(this).parents('tr');
            showMoreRow.hide();
            table.find('tr#' + $(this).parents('tr').attr('element')).ttFetchChildren();
            table.ttInitTable();
            showMoreRow.ttInitNode();
            showMoreRow.remove();
        });

        var cell = $(node.children('td')[options.treeColumn]);
        var padding = cell.ttPaddingLeft() + options.indent;
        var arrayCell = node.ttChildren();
        if (arrayCell === null) {
            return;
        }
        arrayCell.each(function () {
            $(this).children("td:first")[options.treeColumn].style.paddingLeft = padding + "px";
            if (node.hasClass('expanded')) {
                $(this).ttInitNode();
                $(this).show();
            } else {
                $(this).hide();
            }
            if (typeof options.callbackCreateElement == 'function') {
                options.callbackCreateElement($(this));
            }
        });
    };

    /**
     * Reloads the entire tree (should be called on the table)
     */
    $.fn.ttReloadTree = function () {
        var table = $(this);
        var options = table.ttOptions();
        table.find('tbody tr').remove();
        table.after('<img class="reloadTableIndicator" alt=""  src="' + json.global.coreWebroot + '/public/images/icons/loading.gif" />');

        $.post(json.global.webroot + '/browse/getfolderscontent', {
            folders: table.attr('root'),
            sort: options.sort,
            sortdir: options.sortdir
        }, function (data) {
            var children = $.parseJSON(data);
            table.find('tbody tr').remove(); // in case concurrent requests were made, clear tree
            for (var key in children) {
                if (children.hasOwnProperty(key)) {
                    var index = 1;
                    for (var folderIndex in children[key].folders) {
                        if (children[key].folders.hasOwnProperty(folderIndex)) {
                            var folder = children[key].folders[folderIndex];
                            var folderPrivacyClass = folder.privacy_status === 0 ? 'Public' : 'Private';
                            var folderRow = '<tr id="node--' + index + '" class="parent" privacy="' +
                                folder.privacy_status + '" type="folder" element="' + folder.folder_id + '">';
                            folderRow += '<td class="treeBrowseElement"><span class="folder' + folderPrivacyClass + '">' + sliceFileName(folder.name, 43) + '</span></td>';
                            folderRow += '<td><img class="folderLoading" element="' + folder.folder_id + '" alt="" src="' + json.global.coreWebroot + '/public/images/icons/loading.gif"/></td>';
                            folderRow += '<td>' + folder.date_update + '</td>';
                            folderRow += '<td><input type="checkbox" class="treeCheckbox" type="folder" element="' + folder.folder_id + '" id="folderCheckbox' + folder.folder_id + '"/></td>';
                            folderRow += '</tr>';
                            table.find('tbody').append(folderRow);
                            index++;
                        }
                    }
                    for (var itemIndex in children[key].items) {
                        if (children[key].items.hasOwnProperty(itemIndex)) {
                            var item = children[key].items[itemIndex];
                            var itemPrivacyClass = item.privacy_status === 0 ? 'Public' : 'Private';
                            var itemRow = '<tr id="node--' + index + '" privacy="' + item.privacy_status + '" type="item" element="' + item.item_id + '">';
                            itemRow += '<td class="treeBrowseElement"><span class="file' + itemPrivacyClass + '">' + sliceFileName(item.name, 43) + '</span></td>';
                            itemRow += '<td>' + item.size + '</td>';
                            itemRow += '<td>' + item.date_update + '</td>';
                            itemRow += '<td><input type="checkbox" class="treeCheckbox" type="folder" element="' + item.item_id + '" id="itemCheckbox' + item.item_id + '"/></td>';
                            itemRow += '</tr>';
                            table.find('tbody').append(itemRow);
                            index++;
                        }
                    }
                }
            }
            $('img.reloadTableIndicator').hide();
            table.find('tbody tr').each(function () {
                if (!options.expandable || $(this)[0].className.search(options.childPrefix) == -1) {
                    if (isNaN(defaultPaddingLeft)) {
                        defaultPaddingLeft = parseInt($($(this).children('td:first')[options.treeColumn]).css('padding-left'), 10);
                    }
                    $(this).ttInitNode();
                }
            });
            table.ttInitTable();
            table.ttRenderElementsSize();
        });
    };

    /**
     * Reload a node by clearing and re-fetching its children
     */
    $.fn.reload = function () {
        var node = $(this);
        var options = node.ttOptions();
        $.removeData(node[0], 'lastchild');
        $.removeData(node[0], 'children');
        node.ttRemoveSubtree();
        node.removeAttr('fetched');
        node.expand();

        if (typeof options.callbackReloadNode == 'function') {
            options.callbackReloadNode(node);
        }
    };

    // Add an entire branch to +destination+
    $.fn.appendBranchTo = function (destination) {
        var node = $(this);
        var parent = node.ttParent();
        var options = node.ttOptions();

        var ancestorNames = $.map($(destination).ttAncestors(), function (a) {
            return a.id;
        });

        // Conditions:
        // 1: +node+ should not be inserted in a location in a branch if this would
        //    result in +node+ being an ancestor of itself.
        // 2: +node+ should not have a parent OR the destination should not be the
        //    same as +node+'s current parent (this last condition prevents +node+
        //    from being moved to the same location where it already is).
        // 3: +node+ should not be inserted as a child of +node+ itself.

        if ($.inArray(node[0].id, ancestorNames) == -1 && (!parent || (destination.id != parent[0].id)) && destination.id != node[0].id) {
            $.ttIndent(node, node.ttAncestors().length * options.indent * -1); // Remove indentation

            if (parent) {
                node.removeClass(options.childPrefix + parent[0].id);
            }

            node.addClass(options.childPrefix + destination.id);
            $.ttMove(node, destination); // Recursively move nodes to new location
            $.ttIndent(node, node.ttAncestors().length * options.indent);
        }

        return this;
    };

    // Add reverse() function from JS Arrays
    $.fn.reverse = function () {
        return this.pushStack(this.get().reverse(), arguments);
    };

    // Toggle an entire branch
    $.fn.toggleBranch = function () {
        if ($(this).hasClass("collapsed")) {
            $(this).expand();
        } else {
            $(this).removeClass("expanded").collapse();
        }

        return this;
    };

    $.fn.ttAncestors = function () {
        var node = $(this);
        var ancestors = [];
        while (node = node.ttParent()) {
            ancestors[ancestors.length] = node[0];
        }
        return ancestors;
    };

    /**
     * Get all *rendered* children of a node
     */
    $.fn.ttChildren = function () {
        var node = $(this);
        if (node[0] === undefined) {
            return null;
        }
        var table = node.ttTable();
        var options = table.ttOptions();

        return table.find("tbody tr." + options.childPrefix + node[0].id);
    };

    /**
     * Remove the entire subtree of a node
     */
    $.fn.ttRemoveSubtree = function () {
        var node = $(this);
        node.ttChildren().each(function () {
            $(this).ttRemoveSubtree();
            $(this).remove();
        });
    };

    $.fn.ttPaddingLeft = function () {
        var node = $(this);
        if (node[0] === undefined) {
            return defaultPaddingLeft;
        }
        var paddingLeft = parseInt(node[0].style.paddingLeft, 10);
        return (isNaN(paddingLeft)) ? defaultPaddingLeft : paddingLeft;
    };

    /**
     * Indent a node by the given value.  Will apply recursively to children
     */
    $.ttIndent = function (node, value) {
        var options = node.ttOptions();
        var cell = $(node.children('td')[options.treeColumn]);
        cell[0].style.paddingLeft = cell.ttPaddingLeft() + value + 'px';

        node.ttChildren().each(function () {
            $.ttIndent($(this), value);
        });
    };

    /**
     * Call this function on the table element whenever the table state has changed.
     * This will bind all of the click, checkbox, and keyboard actions to the table
     */
    $.fn.ttInitTable = function () {
        var table = $(this);
        var options = table.ttOptions();
        // Make visible that a row is clicked
        table.find('tbody tr').unbind('mousedown');
        table.find('tbody tr').mousedown(function (event) {
            // If the user is clicking on the row's checkbox, don't perform select behavior
            if ($(event.toElement).is('input.treeCheckbox')) {
                return;
            }
            $('tr.selected').removeClass('selected'); // Deselect currently selected rows
            $(this).addClass('selected');
            if (typeof options.callbackSelect == 'function') {
                options.callbackSelect($(this));
            }
        });

        table.find('tbody tr').unbind('dblclick');
        table.find('tbody tr').dblclick(function () {
            if (typeof options.callbackDblClick == 'function') {
                options.callbackDblClick($(this));
            }
        });
        table.ttColorLines();

        table.find('.treeCheckbox').unbind('change');
        table.find('.treeCheckbox').change(function () {
            if (typeof options.callbackCheckboxes == 'function') {
                options.callbackCheckboxes(table);
            }
        });

        // Bind arrow keys and space bar key to tree navigation
        $(document).unbind('keydown').keydown(function (event) {
            if ($('div.MainDialog').is(':visible')) {
                return;
            }
            if (event.which == 38 && document.activeElement.tagName != 'TEXTAREA') { //up arrow - select previous visible element
                var selected = table.find('tbody tr.selected');
                table.find('tbody tr.selected').prevAll(':visible').first().mousedown();
                event.preventDefault();
            } else if (event.which == 40 && document.activeElement.tagName != 'TEXTAREA') { //down arrow - select next visible row
                table.find('tbody tr.selected').nextAll(':visible').first().mousedown();
                event.preventDefault();
            } else if (event.which == 32) { //space bar - toggle checkbox
                var checkbox = table.find('tbody tr.selected input.treeCheckbox');
                if (checkbox.is(':checked')) {
                    checkbox.removeAttr('checked');
                } else {
                    checkbox.attr('checked', 'checked');
                }
                checkbox.change();
            } else if (event.which == 13) { //enter - toggle folder expanded state
                table.find('tbody tr.selected .expander').click();
            }
        });
    };

    /**
     * Applies row striping style to the table
     */
    $.fn.ttColorLines = function () {
        var table = $(this);
        var grey = false;
        table.find('tr').each(function (index) {
            if (index === 0) {
                return;
            }
            if (!$(this).is(':hidden')) {
                if (grey) {
                    $(this).css('background-color', '#f9f9f9');
                    $(this).hover(function () {
                        $(this).css('background-color', '#F3F1EC');
                    }, function () {
                        $(this).css('background-color', '#f9f9f9');
                    });
                    grey = false;
                } else {
                    $(this).css('background-color', 'white');
                    $(this).hover(function () {
                        $(this).css('background-color', '#F3F1EC');
                    }, function () {
                        $(this).css('background-color', 'white');
                    });
                    grey = true;
                }
            }
        });
    };

    /**
     * Call this to initialize a single node. Sets its indentation and binds
     * the expand/collapse toggle action to clicking the node.
     */
    $.fn.ttInitNode = function () {
        var node = $(this);
        if (node === undefined) {
            return;
        }
        var options = node.ttOptions();
        if (!node.hasClass('initialized')) {
            node.addClass("initialized");

            var childNodes = node.ttChildren();
            if (!node.hasClass("parent") && childNodes.length > 0) {
                node.addClass("parent");
            }
            if (node.hasClass("parent")) {
                var cell = $(node.children("td:first")[options.treeColumn]);
                var padding = cell.ttPaddingLeft() + options.indent;

                childNodes.each(function () {
                    $(this).children("td:first")[options.treeColumn].style.paddingLeft = padding + "px";
                });

                if (options.expandable) {
                    cell.prepend('<span style="margin-left: ' + (-(options.indent + 12)) + 'px; padding-left: ' + (options.indent + 12) + 'px" class="expander"></span>');
                    $(cell[0].firstChild).click(function () {
                        node.toggleBranch();
                    });

                    if (options.clickableNodeNames) {
                        cell[0].style.cursor = "pointer";
                        $(cell).click(function (e) {
                            // Don't double-toggle if the click is on the existing expander icon
                            if (e.target.className != 'expander') {
                                node.toggleBranch();
                            }
                        });
                    }

                    // Check for a class set explicitly by the user, otherwise set the default class
                    if (!(node.hasClass("expanded") || node.hasClass("collapsed"))) {
                        node.addClass(options.initialState);
                    }

                    if (node.hasClass("expanded")) {
                        node.expand();
                    }
                }
            }
        }
    };

    /**
     * Move a node
     * @param node The node to move
     * @param destination The destination parent to move under
     */
    $.ttMove = function (node, destination) {
        node.insertAfter(destination);
        node.ttChildren().reverse().each(function () {
            $.ttMove($(this), node[0]);
        });
    };

    /**
     * Get the immediate parent of a node or null if none exists
     */
    $.fn.ttParent = function () {
        var classNames = $(this)[0].className.split(' ');
        var options = $(this).ttOptions();

        for (var key in classNames) {
            if (classNames.hasOwnProperty(key) && classNames[key].match(options.childPrefix)) {
                return $(this).ttTable().find("#" + classNames[key].substring(9));
            }
        }
        return null;
    };

    /**
     * Get the containing table for a node
     */
    $.fn.ttTable = function () {
        if ($(this).is('table')) {
            return $(this);
        }
        return $($(this).parents('table')[0]);
    };

    /**
     * Get the options from the node or table
     */
    $.fn.ttOptions = function () {
        var table = $(this).ttTable();
        if (table.length === 0) {
            return $.fn.treeTable.defaults;
        }
        var options = $.data(table[0], 'options');
        return options ? options : $.fn.treeTable.defaults;
    };

    /**
     * Call this on the table object to set an option on it
     */
    $.fn.ttSetOption = function (name, value) {
        var options = $.data($(this)[0], 'options');
        options[name] = value;
        $.data($(this)[0], 'options', options);
    };

    $.fn.ttRenderElementsSize = function () {
        // For performance reasons, don't fetch folder sizes anymore.
        $(this).find('img.folderLoading').replaceWith('--');
    };
})(jQuery);
