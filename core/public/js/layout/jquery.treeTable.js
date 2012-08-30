/**
 * Midas tree table object
 */
(function($) {
    var defaultPaddingLeft;

    $.fn.treeTable = function(opts) {
        var table = $(this);
        var options = $.extend({}, $.fn.treeTable.defaults, opts);

        // default to global callbacks in none were passed
        if(options.callbackSelect == null && typeof callbackSelect == 'function') {
            options.callbackSelect = callbackSelect;
        }
        if(options.callbackCheckboxes == null && typeof callbackCheckboxes == 'function') {
            options.callbackCheckboxes = callbackCheckboxes;
        }
        if(options.callbackDblClick == null && typeof callbackDblClick == 'function') {
            options.callbackDblClick = callbackDblClick;
        }
        if(options.callbackCreateElement == null && typeof callbackCreateElement == 'function') {
            options.callbackCreateElement = callbackCreateElement;
        }
        if(options.callbackReloadNode == null && typeof callbackReloadNode == 'function') {
            options.callbackReloadNode = callbackReloadNode;
        }
        if(options.callbackCustomElements == null && typeof callbackCustomElements == 'function') {
            options.callbackCustomElements = callbackCustomElements;
        }

        options.sort = 'name';
        options.sortdir = 'asc';

        // Store the options in the dom (on the table element) for later lookup
        $.data(table[0], 'options', options);

        var tmp = this.each(function() {
            $(this).addClass('treeTable').find('tbody tr').each(function() {
                if(!options.expandable || $(this)[0].className.search(options.childPrefix) == -1) {
                    if (isNaN(defaultPaddingLeft)) {
                        defaultPaddingLeft = parseInt($($(this).children("td:first")[options.treeColumn]).css('padding-left'), 10);
                    }
                    $(this).ttInitNode();
                }
                else if(options.initialState == 'collapsed') {
                    $(this).hide();
                }
            });
            $(this).ttRenderElementsSize();
        });
        $(this).ttInitTable();
        if($.isFunction(options.onFirstInit)) {
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
        indent: 7,
        initialState: "collapsed",
        treeColumn: 0,
        pageLength: 70,
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
    $.fn.ttSortClicked = function(fieldname) {
        var table = $(this).ttTable();
        table.find('th.thData,th.thSize,th.thDate').ttShowNoSort();
        var options = table.ttOptions();
        var sortdir = 'asc';
        if(options.sort == fieldname && options.sortdir == 'asc') {
            sortdir = 'desc';
        }
        table.ttSetOption('sort', fieldname);
        table.ttSetOption('sortdir', sortdir);
        $(this).ttShowSort(sortdir);
        table.ttReloadTree();
    };

    $.fn.ttShowNoSort = function() {
        $(this).removeClass('sortasc sortdesc');
        $(this).addClass('sortnone');
    };

    $.fn.ttShowSort = function(sortdir) {
        $(this).removeClass('sortnone sortasc sortdesc');
        $(this).addClass('sort'+sortdir);
    };

    // Collapse a branch
    $.fn.collapse = function() {
        var node = $(this);
        var id = node.attr('id');
        var table = node.ttTable();
        table.find('tr[id*="'+id+'"]').removeClass('expanded').addClass('collapsed').hide();
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
        if(!node.attr('fetched')) {
            node.ttFetchChildren();
            table.ttInitTable();
            node.ttInitNode();
        } else {
            node.ttChildren().each(function () {
                $(this).ttInitNode();
                $(this).show();
            });
            if($.isFunction(options.onNodeShow)) {
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
    $.fn.ttToggleLoading = function(loading) {
      var node = $(this);
      if(loading && node.find('img.fetchingChildren').length > 0) {
          return; //image is already displayed
      }
      if(loading) {
          node.find('td:first').append(' <img class="fetchingChildren" alt="" src="'
                                       +json.global.coreWebroot+'/public/images/icons/loading.gif"/>');
      }
      else {
          node.find('img.fetchingChildren').remove();
      }
    }

    /**
     * Use ajax to fetch the children of a node.  The child data will be stored
     * as data on the node's dom element
     */
    $.fn.ttFetchChildren = function () {
        var node = $(this);
        if(node == undefined) {
            return;
        }
        var table = node.ttTable();
        var options = table.ttOptions();

        // Mark the node as fetched so we don't try to fetch it multiple times
        node.attr('fetched', 'true');
        node.ttToggleLoading(true);

        $.post(json.global.webroot+'/browse/getfolderscontent', {
            folders: node.attr('element'),
            sort: options.sort,
            sortdir: options.sortdir
          } , function (data) {
            var children = jQuery.parseJSON(data);
            // Store the children in the dom node
            for(var key in children) {
                $.data(node[0], 'children', children[key]);
                break; //just fetching one folder at a time for now.
            }
            // Render the children (one chunk at a time)
            node.ttRenderChildren(0);
            table.ttRenderElementsSize();

            // Table state has changed so we call init table
            table.ttInitTable();
            node.ttToggleLoading(false);
            if($.isFunction(options.onNodeShow)) {
                options.onNodeShow.call(node);
            }
        });
    }

    /**
     * Once the data has been loaded onto a node, call this to actually
     * render the data in the tree.
     * @param offset The page offset
     */
    $.fn.ttRenderChildren = function(offset) {
        var node = $(this);
        var table = node.ttTable();
        var options = table.ttOptions();
        var elements = $.data(node[0], 'children');
        var lastchild = $.data(node[0], 'lastchild');
        var html = '';

        if(typeof options.callbackCustomElements == 'function') {
            html = options.callbackCustomElements(node, elements);
            node.after(html);
        }
        else {
            var i = offset;
            var id = node.attr('id');

            // Calculate how many characters we should truncate to based on node depth
            var sliceValue = 55 - (id.split('-').length - 1) * 3;
            var drag_option = '';

            // Render the child folders
            $.each(elements.folders, function(index, value) {
                if(index < offset || i >= options.pageLength + offset) { // only render a max of pageLength children at a time
                    return;
                }
                i++;
                if(table.find('#'+id+"-"+i).length > 0) {
                    return;
                }
                if(value.policy == 0) {
                    drag_option = ' notdraggable';
                }
                else {
                    drag_option = '';
                }
                var privacyClass;
                if(value.privacy_status == 0) { //public
                    privacyClass = 'Public';
                }
                else {
                    privacyClass = 'Private';
                }

                html += "<tr id='"+id+"-"+i+"' deletable='"+value.deletable+"' privacy='"+value.privacy_status+"' class='parent child-of-"+id+"' type='folder' policy='"+value.policy+"' element='"+value.folder_id+"'>";
                html += "  <td><span class='folder"+privacyClass+drag_option+"'>"+sliceFileName(value.name, sliceValue)+"</span></td>";
                html += "  <td><img class='folderLoading' element='"+value.folder_id+"' alt='' src='"+json.global.coreWebroot+"/public/images/icons/loading.gif'/></td>";
                html += "  <td>"+value.date_update+"</td>";
                html += "  <td><input type='checkbox' class='treeCheckbox' type='folder' element='"+value.folder_id+"'/></td>";
                html += "</tr>";
                $.data(node[0], 'lastchild', id+"-"+i);
            });

            // Render the child items
            $.each(elements.items, function(index, value) {
                if(index < offset || i >= options.pageLength + offset) { //only render a max of pageLength children at a time
                    return;
                }
                i++;
                if(table.find('#'+id+"-"+i).length > 0) {
                    return;
                }
                if(value.policy == 0) {
                    drag_option = ' notdraggable';
                }
                else {
                    drag_option = '';
                }
                var privacyClass;
                if(value.privacy_status == 0) { //public
                    privacyClass = 'Public';
                }
                else {
                    privacyClass = 'Private';
                }
                html +=  "<tr id='"+id+"-"+i+"' class='child-of-"+id+"' privacy='"+value.privacy_status+"' type='item' policy='"+value.policy+"' element='"+value.item_id+"'>";
                html +=  "  <td><span class='file"+privacyClass+drag_option+"'>"+sliceFileName(value.name, sliceValue)+"</span></td>";
                html +=  "  <td>"+value.size+"</td>";
                html +=  "  <td>"+value.date_update+"</td>";
                html +=  "  <td><input type='checkbox' class='treeCheckbox' type='item' element='"+value.item_id+"'/></td>";
                html +=  "</tr>";
                $.data(node[0], 'lastchild', id+"-"+i);
            });

            if(i >= options.pageLength + offset) {
                html += "<tr class='child-of-"+id+"' id='"+id+"-10000000' element='"+id+"'>"+
                        "<td colspan='1' align='right'><a offset='"+i+"' class='treeBrowserShowMore'>Show more</a></td><td></td><td></td><td></td></tr>";
            }
            if(lastchild == undefined) {
                // We are rendering the first page
                node.after(html);
            }
            else {
                // We are rendering a subsequent page
                table.find('#' + lastchild).after(html);
            }
        }

        // Bind "Show more" action
        table.find('a.treeBrowserShowMore:visible').click(function () {
            table.find('tr#'+$(this).parents('tr').attr('element')).ttRenderChildren(parseInt($(this).attr('offset')));
            table.ttInitTable();
            $(this).parents('tr').ttInitNode();
            $(this).parents('tr').remove();
        });

        var cell = $(node.children('td')[options.treeColumn]);
        var padding = cell.ttPaddingLeft() + options.indent;
        var arrayCell = node.ttChildren();
        if(arrayCell == null) {
            return;
        }
        arrayCell.each(function() {
            $(this).children("td:first")[options.treeColumn].style.paddingLeft = padding + "px";
            if(node.hasClass('expanded')) {
                $(this).ttInitNode();
                $(this).show();
            }
            else {
                $(this).hide();
            }
            if(typeof options.callbackCreateElement == 'function') {
                options.callbackCreateElement($(this));
            }
        });
    }

    /**
     * Reloads the entire tree (should be called on the table)
     */
    $.fn.ttReloadTree = function () {
        var table = $(this);
        var options = table.ttOptions();
        table.find('tbody tr').remove();
        table.after('<img class="reloadTableIndicator" alt=""  src="'+json.global.coreWebroot+'/public/images/icons/loading.gif" />');

        $.post(json.global.webroot+'/browse/getfolderscontent', {
            folders: table.attr('root'),
            sort: options.sort,
            sortdir: options.sortdir
          } , function (data) {
            var children = $.parseJSON(data);
            table.find('tbody tr').remove(); // in case concurrent requests were made, clear tree
            for(var key in children) {
                var index = 1;
                for(var folderIndex in children[key].folders) {
                    var folder = children[key].folders[folderIndex];
                    var privacyClass = folder.privacy_status == 0 ? 'Public' : 'Private';
                    var row = '<tr id="node--'+index+'" policy="'+folder.policy+'" deletable="false" class="parent" privacy="'+
                              folder.privacy_status+'" type="folder" element="'+folder.folder_id+'">';
                    row += '<td class="treeBrowseElement"><span class="folder'+privacyClass+'">'+sliceFileName(folder.name,43)+'</span></td>';
                    row += '<td><img class="folderLoading" element="'+folder.folder_id+'" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/></td>';
                    row += '<td>'+folder.date_update+'</td>';
                    row += '<td><input type="checkbox" class="treeCheckbox" type="folder" element="'+folder.folder_id+'" id="folderCheckbox'+folder.folder_id+'"/></td>';
                    row += '</tr>';
                    table.find('tbody').append(row);
                    index++;
                }
                for(var itemIndex in children[key].items) {
                    var item = children[key].items[itemIndex];
                    var privacyClass = item.privacy_status == 0 ? 'Public' : 'Private';
                    var row = '<tr id="node--'+index+'" policy="'+item.policy+'" privacy="'+
                              item.privacy_status+'" type="item" element="'+item.item_id+'">';
                    row += '<td class="treeBrowseElement"><span class="file'+privacyClass+'">'+sliceFileName(item.name,43)+'</span></td>';
                    row += '<td>'+item.size+'</td>';
                    row += '<td>'+item.date_update+'</td>';
                    row += '<td><input type="checkbox" class="treeCheckbox" type="folder" element="'+item.item_id+'" id="itemCheckbox'+item.item_id+'"/></td>';
                    row += '</tr>';
                    table.find('tbody').append(row);
                    index++;
                }

            }
            $('img.reloadTableIndicator').hide();
            table.find('tbody tr').each(function() {
                if(!options.expandable || $(this)[0].className.search(options.childPrefix) == -1) {
                    if (isNaN(defaultPaddingLeft)) {
                        defaultPaddingLeft = parseInt($($(this).children('td:first')[options.treeColumn]).css('padding-left'), 10);
                    }
                    $(this).ttInitNode();
                }
            });
            table.ttInitTable();
            table.ttRenderElementsSize();
        });
    }

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

        if(typeof options.callbackReloadNode == 'function') {
            options.callbackReloadNode(node);
        }
    }

    // Add an entire branch to +destination+
    $.fn.appendBranchTo = function (destination) {
        var node = $(this);
        var parent = node.ttParent();
        var options = node.ttOptions();

        var ancestorNames = $.map($(destination).ttAncestors(), function(a) {return a.id;});

        // Conditions:
        // 1: +node+ should not be inserted in a location in a branch if this would
        //    result in +node+ being an ancestor of itself.
        // 2: +node+ should not have a parent OR the destination should not be the
        //    same as +node+'s current parent (this last condition prevents +node+
        //    from being moved to the same location where it already is).
        // 3: +node+ should not be inserted as a child of +node+ itself.

        if($.inArray(node[0].id, ancestorNames) == -1 && (!parent || (destination.id != parent[0].id)) && destination.id != node[0].id) {
            $.ttIndent(node, node.ttAncestors().length * options.indent * -1); // Remove indentation

            if(parent) {
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
        if($(this).hasClass("collapsed")) {
            $(this).expand();
        }
        else {
            $(this).removeClass("expanded").collapse();
        }

        return this;
    };

    $.fn.ttAncestors = function () {
        var node = $(this);
        var ancestors = [];
        while(node = node.ttParent()) {
            ancestors[ancestors.length] = node[0];
        }
        return ancestors;
    };

    /**
     * Get all *rendered* children of a node
     */
    $.fn.ttChildren = function () {
        var node = $(this);
        if(node[0] == undefined) {
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
        if(node[0] == undefined) {
            return defaultPaddingLeft;
        }
        var paddingLeft = parseInt(node[0].style.paddingLeft, 10);
        return (isNaN(paddingLeft)) ? defaultPaddingLeft : paddingLeft;
    }

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
        table.find('tbody tr').mousedown(function() {
            $('tr.selected').removeClass('selected'); // Deselect currently selected rows
            $(this).addClass('selected');
            if(typeof options.callbackSelect == 'function') {
                options.callbackSelect($(this));
            }
        });

        table.find('tbody tr').unbind('dblclick');
        table.find('tbody tr').dblclick(function () {
            if(typeof options.callbackDblClick == 'function') {
                options.callbackDblClick($(this));
            }
        });
        table.ttColorLines();

        table.find('.treeCheckbox').unbind('change');
        table.find('.treeCheckbox').change(function () {
            if(typeof options.callbackCheckboxes == 'function') {
                options.callbackCheckboxes(table);
            }
        });

        // Bind arrow keys and spacebar key to tree navigation
        $(document).unbind('keydown').keydown(function (event) {
            if($('div.MainDialog').is(':visible')) {
                return;
            }
            if(event.which == 38 && document.activeElement.tagName != 'TEXTAREA') { //up arrow - select previous visible element
                var selected = table.find('tbody tr.selected');
                table.find('tbody tr.selected').prevAll(':visible').first().mousedown();
                event.preventDefault();
            } else if(event.which == 40 && document.activeElement.tagName != 'TEXTAREA') { //down arrow - select next visible row
                table.find('tbody tr.selected').nextAll(':visible').first().mousedown();
                event.preventDefault();
            } else if(event.which == 32) { //space bar - toggle checkbox
                var checkbox = table.find('tbody tr.selected input.treeCheckbox');
                if(checkbox.is(':checked')) {
                    checkbox.removeAttr('checked');
                } else {
                    checkbox.attr('checked', 'checked');
                }
                checkbox.change();
            } else if(event.which == 13) { //enter - toggle folder expanded state
                table.find('tbody tr.selected .expander').click();
            }
        });
    }

    /**
     * Applies row striping style to the table
     */
    $.fn.ttColorLines = function () {
        var table = $(this);
        var grey = false;
        table.find('tr').each(function (index) {
            if(index == 0) {
                return;
            }
            if(!$(this).is(':hidden')) {
                if(grey) {
                    $(this).css('background-color','#f9f9f9');
                    $(this).hover(function(){$(this).css('background-color','#F3F1EC')}, function(){$(this).css('background-color','#f9f9f9')});
                    grey = false;
                }
                else {
                    $(this).css('background-color','white');
                    $(this).hover(function(){$(this).css('background-color','#F3F1EC')}, function(){$(this).css('background-color','white')});
                    grey = true;
                }
            }
        });
    }

    /**
     * Call this to initialize a single node. Sets its indentation and binds
     * the expand/collapse toggle action to clicking the node.
     */
    $.fn.ttInitNode = function () {
        var node = $(this);
        if(node == undefined) {
          return;
        }
        var options = node.ttOptions();
        if(!node.hasClass('initialized')) {
            node.addClass("initialized");

            var childNodes = node.ttChildren();
            if(!node.hasClass("parent") && childNodes.length > 0) {
                node.addClass("parent");
            }
            if(node.hasClass("parent")) {
                var cell = $(node.children("td:first")[options.treeColumn]);
                var padding = cell.ttPaddingLeft() + options.indent;

                childNodes.each(function () {
                    $(this).children("td:first")[options.treeColumn].style.paddingLeft = padding + "px";
                });

                if(options.expandable) {
                    cell.prepend('<span style="margin-left: -' + (options.indent+12) + 'px; padding-left: ' + (options.indent+12) + 'px" class="expander"></span>');
                    $(cell[0].firstChild).click(function () {
                        node.toggleBranch();
                    });

                    if(options.clickableNodeNames) {
                        cell[0].style.cursor = "pointer";
                        $(cell).click(function(e) {
                            // Don't double-toggle if the click is on the existing expander icon
                            if(e.target.className != 'expander') {
                                node.toggleBranch();
                            }
                        });
                    }

                    // Check for a class set explicitly by the user, otherwise set the default class
                    if(!(node.hasClass("expanded") || node.hasClass("collapsed"))) {
                        node.addClass(options.initialState);
                    }

                    if(node.hasClass("expanded")) {
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

        for(key in classNames) {
            if(classNames[key].match(options.childPrefix)) {
                return $(this).ttTable().find("#" + classNames[key].substring(9));
            }
        }
    }

    /**
     * Get the containing table for a node
     */
    $.fn.ttTable = function () {
        if($(this).is('table')) {
            return $(this);
        }
        return $($(this).parents('table')[0]);
      }

    /**
     * Get the options from the node or table
     */
    $.fn.ttOptions = function () {
        var table = $(this).ttTable();
        if(table.length == 0) {
            return $.fn.treeTable.defaults;
        }
        var options = $.data(table[0], 'options');
        return options ? options : $.fn.treeTable.defaults;
    }

    /**
     * Call this on the table object to set an option on it
     */
    $.fn.ttSetOption = function(name, value) {
        var options = $.data($(this)[0], 'options');
        options[name] = value;
        $.data($(this)[0], 'options', options);
    }

    $.fn.ttRenderElementsSize = function () {
        var table = $(this);
        var options = table.ttOptions();
        if(options.disableElementSize) {
            return;
        }
        var elements = '';
        var i = 0;
        table.find('img.folderLoading').each(function () {
            i++;
            if(i > 10) {
                return;
            }
            if($(this).attr('process') == undefined) {
                elements += $(this).attr('element') + '-';
                $(this).attr('process', 'true');
            }
        });
        if(elements != '') {
            $.post(json.global.webroot+'/browse/getfolderssize', {folders: elements} , function(data) {
                var arrayElement = jQuery.parseJSON(data);
                $.each(arrayElement, function(index, value) {
                    var img = table.find('img.folderLoading[element='+value.id+']');
                    img.after('<span class="elementSize">'+value.size+'</span>');
                    img.parents('tr').find('td:first span:last').after('<span style="padding-left:0px;" class="elementCount">'+' ('+value.count+')'+'</span>');
                    img.remove();
                });
                table.ttRenderElementsSize();
            });
        }
    }
})(jQuery);
