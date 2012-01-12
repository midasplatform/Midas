/**
 * NOTICE: This has been forked by the MIDAS team and contains a great deal of code
 * that is not in the original plugin.  Do not overwrite this file with a newer
 * version of the plugin.
 */

/*
 * jQuery treeTable Plugin 2.3.0
 * http://ludo.cubicphuse.nl/jquery-plugins/treeTable/
 *
 * Copyright 2010, Ludo van den Boom
 * Dual licensed under the MIT or GPL Version 2 licenses.
 */
(function($) {
  // Helps to make options available to all functions
  // TODO: This gives problems when there are both expandable and non-expandable
  // trees on a page. The options shouldn't be global to all these instances!
  var defaultPaddingLeft;

  $.fn.treeTable = function(opts) {
    var options = $.extend({}, $.fn.treeTable.defaults, opts);

    // default to global callbacks in none were passed
    if(options.callbackSelect == null && typeof callbackSelect == 'function')
      {
      options.callbackSelect = callbackSelect;
      }
    if(options.callbackCheckboxes == null && typeof callbackCheckboxes == 'function')
      {
      options.callbackCheckboxes = callbackCheckboxes;
      }
    if(options.callbackDblClick == null && typeof callbackDblClick == 'function')
      {
      options.callbackDblClick = callbackDblClick;
      }
    if(options.callbackCreateElement == null && typeof callbackCreateElement == 'function')
      {
      options.callbackCreateElement = callbackCreateElement;
      }
    if(options.callbackReloadNode == null && typeof callbackReloadNode == 'function')
      {
      options.callbackReloadNode = callbackReloadNode;
      }
    if(options.callbackCustomElements == null && typeof callbackCustomElements == 'function')
      {
      options.callbackCustomElements = callbackCustomElements;
      }

    $.data($(this)[0], 'options', options);
    tree[$(this).attr('id')] = new Array();
    colorLines($(this), false);

    var tmp = this.each(function() {

      $(this).addClass("treeTable").find("tbody tr").each(function() {
        // Initialize root nodes only if possible
        if(!options.expandable || $(this)[0].className.search(options.childPrefix) == -1) {
          // To optimize performance of indentation, I retrieve the padding-left
          // value of the first root node. This way I only have to call +css+
          // once.
          if (isNaN(defaultPaddingLeft)) {
            defaultPaddingLeft = parseInt($($(this).children("td:first")[options.treeColumn]).css('padding-left'), 10);
            }
          initialize($(this));
          }
        else if(options.initialState == "collapsed") {
          this.style.display = "none"; // Performance! $(this).hide() is slow...
          }
        });
    initializeAjax($(this), true, false);
    });
  initEvent($(this));

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

    callbackSelect: null,
    callbackCheckboxes: null,
    callbackDblClick: null,
    callbackCreateElement: null,
    callbackReloadNode: null,
    callbackCustomElements: null
  };

  // Recursively hide all node's children in a tree
  $.fn.collapse = function() {
    var id = $(this).attr('id');
    var table = getContainer($(this));
    table.find('tr[id*="'+id+'"]').addClass("collapsed").hide();
    $(this).show();
    colorLines(table, true);
    return this;
  };

  var tree = new Object();

  function initializeAjax(node, first, expandNode)
    {
    var table = getContainer(node);
    var options = getOptions(table);
    
    if(node == undefined)
      {
      return ;
      }
    var folders = '';
    var children;
    if(first)
      {
      children = node.find('.parent');
      }
    else
      {
      children = childrenOf(node);
      }

    children.each(function()
      {
      if($(this).attr('ajax') != undefined)
        {
        folders += $(this).attr('ajax') + '-';
        $(this).attr('proccessing', true);
        }
      });
    if(folders != '')
      {
      $.post(json.global.webroot+'/browse/getfolderscontent',{folders: folders} , function(data) {
        arrayElement = jQuery.parseJSON(data);
        $.each(arrayElement, function(index, value) {
          tree[getContainer(node).attr('id')][index] = value;
          });

        if(expandNode != false)
          {
          expandNode.expand();
          getElementsSize(getContainer(node));
          }

        initEvent(getContainer(node));
        if(first && $.isFunction(options.onFirstInit))
          {
          options.onFirstInit.call();
          }
        });
      }
    getElementsSize(getContainer(node));
    }

  $.fn.reload = function ()
    {
    var table = getContainer($(this));
    var options = getOptions(table);
    $(this).each(function() {
        childrenOf($(this)).remove();
      });
    tree[table.attr('id')][$(this).attr('element')] = null;
    var obj = $(this);
    $(this).removeAttr('proccessing');
    $(this).attr('ajax', $(this).attr('element'));
    $(this).expand();

    var mainNode = $(this);
    $.post(json.global.webroot+'/browse/getfolderscontent',{folders: $(this).attr('element')} , function(data) {
      arrayElement = jQuery.parseJSON(data);
      $.each(arrayElement, function(index, value) {
        tree[table.attr('id')][index] = value;
        });
     // createElementsAjax(obj,tree[obj.attr('element')],true);
      initEvent(mainNode);
      getElementsSize(mainNode);

      var treeArray = new Array();
      table.find('tr').each(function() {
        var id = $(this).attr('element');
        if(treeArray[id] != undefined)
          {
          $(this).remove();
          }
        else
          {
          treeArray[id] = true;
          }
        });

      if(typeof options.callbackReloadNode == 'function')
        {
        options.callbackReloadNode(mainNode);
        }
      });
    }

  // Recursively show all node's children in a tree
  $.fn.expand = function()
    {
    var table = getContainer($(this));
    var options = getOptions(table);
    if ($(this).attr('ajax') != undefined && tree[table.attr('id')][$(this).attr('ajax')] != undefined)
      {
      createElementsAjax($(this), tree[table.attr('id')][$(this).attr('ajax')], true);
      $(this).removeAttr('ajax');
      $(this).attr('proccessing',false);
      $(this).find('td:first img.tableLoading').hide();
      initEvent(table);
      initialize($(this));
      }
    else if($(this).attr('ajax') != undefined && tree[table.attr('id')][$(this).attr('ajax')] == undefined)
      {
      var parent = parentOf($(this));
      if(!parent)
        {
        parent = $(this);
        }
      initializeAjax(parent, false, $(this));
      return;
      }

    if($(this).attr('proccessing') == 'true')
      {
      //$(this).find('td:first').prepend('<img class="tableLoading" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>');
      }

    var id = $(this).attr('id');

    $(this).removeClass("collapsed").addClass("expanded");
    childrenOf($(this)).each(function() {
      initialize($(this));

      if($(this).is(".expanded.parent")) {
        $(this).expand();
        }

      // this.style.display = "table-row"; // Unfortunately this is not possible with IE :-(
      $(this).show();

      if($.isFunction(options.onNodeShow)) {
        options.onNodeShow.call(this);
        }
      });

    initializeAjax($(this), false, false);
    colorLines(table, true);
    return this;
    };

  // Reveal a node by expanding all ancestors
  $.fn.reveal = function()
    {
    $(ancestorsOf($(this)).reverse()).each(function() {
      initialize($(this));
      $(this).expand().show();
      });

    return this;
    };

  // Add an entire branch to +destination+
  $.fn.appendBranchTo = function(destination)
    {
    var node = $(this);
    var parent = parentOf(node);
    var options = getOptions(node);

    var ancestorNames = $.map(ancestorsOf($(destination)), function(a) {return a.id;});

    // Conditions:
    // 1: +node+ should not be inserted in a location in a branch if this would
    //    result in +node+ being an ancestor of itself.
    // 2: +node+ should not have a parent OR the destination should not be the
    //    same as +node+'s current parent (this last condition prevents +node+
    //    from being moved to the same location where it already is).
    // 3: +node+ should not be inserted as a child of +node+ itself.

    if($.inArray(node[0].id, ancestorNames) == -1 && (!parent || (destination.id != parent[0].id)) && destination.id != node[0].id) {
      indent(node, ancestorsOf(node).length * options.indent * -1); // Remove indentation

      if(parent) {
        node.removeClass(options.childPrefix + parent[0].id);
        }

      node.addClass(options.childPrefix + destination.id);
      move(node, destination); // Recursively move nodes to new location
      indent(node, ancestorsOf(node).length * options.indent);
      }

    return this;
    };

  // Add reverse() function from JS Arrays
  $.fn.reverse = function()
    {
    return this.pushStack(this.get().reverse(), arguments);
    };

  // Toggle an entire branch
  $.fn.toggleBranch = function()
    {
    if($(this).hasClass("collapsed"))
      {
      $(this).expand();
      }
    else
      {
      $(this).removeClass("expanded").collapse();
      }

    return this;
    };

  // === Private functions

  function ancestorsOf(node)
    {
    var ancestors = [];
    while(node = parentOf(node)) {
      ancestors[ancestors.length] = node[0];
      }
    return ancestors;
    };

  function childrenOf(node)
    {
    if(node[0] == undefined)
      {
      return null;
      }
    var table = getContainer(node);
    var options = getOptions(table);

    return table.find("tbody tr." + options.childPrefix + node[0].id);
    };

  function getPaddingLeft(node)
    {
    if(node[0]==undefined)
      {
      return defaultPaddingLeft;
      }
    var paddingLeft = parseInt(node[0].style.paddingLeft, 10);
    return (isNaN(paddingLeft)) ? defaultPaddingLeft : paddingLeft;
    }

  function indent(node, value)
    {
    var options = getOptions(node);
    var cell = $(node.children("td")[options.treeColumn]);
    cell[0].style.paddingLeft = getPaddingLeft(cell) + value + "px";

    childrenOf(node).each(function() {
      indent($(this), value);
      });
    };

  function initEvent(table)
    {
    var options = getOptions(table);
          // Make visible that a row is clicked
    table.find("tbody tr").unbind('mousedown');
    table.find("tbody tr").mousedown(function() {
      $("tr.selected").removeClass("selected"); // Deselect currently selected rows
      $(this).addClass("selected");
      if(typeof options.callbackSelect == 'function') {
          options.callbackSelect($(this));
          }
    });

    table.find("tbody tr").unbind('dblclick');
    table.find("tbody tr").dblclick(function () {
      if(typeof options.callbackDblClick == 'function') {
        options.callbackDblClick($(this));
        }
      });
    colorLines(table, true);

    table.find(".treeCheckbox").unbind('change');
    table.find(".treeCheckbox").change(function(){
      if(typeof options.callbackCheckboxes == 'function') {
        options.callbackCheckboxes(table);
        }
      });
    }

  function colorLines(table, checkHidden)
    {
    var grey = false;
    table.find('tr').each(function(index) {
      if(index == 0)
        {
        return;
        }
      if(!checkHidden || !$(this).is(':hidden'))
        {
        if(grey)
          {
          $(this).css('background-color','#f9f9f9');
          $(this).hover(function(){$(this).css('background-color','#F3F1EC')}, function(){$(this).css('background-color','#f9f9f9')});
          grey = false;
          }
        else
          {
          $(this).css('background-color','white');
          $(this).hover(function(){$(this).css('background-color','#F3F1EC')}, function(){$(this).css('background-color','white')});
          grey = true;
          }
        }
      });
    }

  function createElementsAjax(node, elementsRaw, first)
    {
    var lastElement;
    var table = getContainer(node);
    var options = getOptions(table);
    if(typeof options.callbackCustomElements == 'function')
      {
      html = options.callbackCustomElements(node, elementsRaw, first);
      node.after(html);
      }
    else
      {
      var html = '';
      var i = 1;
      var id = node.attr('id');
      var elements = elementsRaw;
      elements['folders'] = jQuery.makeArray(elementsRaw['folders']);
      elements['items'] = jQuery.makeArray(elementsRaw['items']);
    //  var padding=parseInt(node.find('td:first').css('padding-left').slice(0,-2));

      var j = 1;
      var sliceValue = 42 - (id.split('-').length - 1) * 3;

      var drag_option = "";

       $.each(elements['folders'], function(index, value) {
        if(j > 70)
          {
          return;
          }
        i++;
        if(table.find('#'+id+"-"+i).length > 0)
          {
          return;
          }
        if(value['policy'] == 0)
          {
          drag_option = " notdraggable"
          }
        else
          {
          drag_option = ""
          }
        html+= "<tr id='"+id+"-"+i+"' deletable='"+value['deletable']+"' privacy='"+value['privacy_status']+"'  class='parent child-of-"+id+"' ajax='"+value['folder_id']+"'type='folder'  policy='"+value['policy']+"' element='"+value['folder_id']+"'>";
        html+=     "  <td><span class='folder"+drag_option+"'>"+sliceFileName(value['name'],sliceValue)+"</span></td>";
        html+=     "  <td>"+'<img class="folderLoading"  element="'+value['folder_id']+'" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>'+"</td>";
        html+=     "  <td>"+value['date_update']+"</td>";
        html+=     "  <td><input type='checkbox' class='treeCheckbox' type='folder' element='"+value['folder_id']+"'/></td>";
        html+=     "</tr>";
        lastElement = id+"-"+i;
        j++;
        });

      $.each(elements['items'], function(index, value) {
        i++;
        if(j > 70)
          {
          return;
          }
        if(table.find('#'+id+"-"+i).length > 0)
          {
          return;
          }
        if (value['policy'] == 0)
          {
          drag_option = " notdraggable"
          }
        else
          {
          drag_option = ""
          }
        html+=  "<tr id='"+id+"-"+i+"' class='child-of-"+id+"' privacy='"+value['privacy_status']+"'  type='item' policy='"+value['policy']+"' element='"+value['item_id']+"'>";
        html+=     "  <td><span class='file"+drag_option+"'>"+sliceFileName(value['name'],sliceValue)+"</span></td>";
        html+=     "  <td>"+value['size']+"</td>";
        html+=     "  <td>"+value['date_update']+"</td>";
        html+=     "  <td><input type='checkbox' class='treeCheckbox' type='item' element='"+value['item_id']+"'/></td>";
        html+=     "</tr>";
        j++;
        lastElement = id+"-"+i;
        });

      if(j > 70)
        {
        html+="<tr id='"+id+"-10000000' element='"+id+"'><td colspan = 1 align=right><a class='treeBrowserShowMore'>Show more</a></td><td></td><td></td><td></td></tr>";
        }
      if(elementsRaw['last'] != undefined)
        {
        table.find('#' + elementsRaw['last']).after(html);
        }
      else
        {
        node.after(html);
        }
      }

    table.find('a.treeBrowserShowMore').click(function() {
      elementsRaw['last'] = lastElement;
      createElementsAjax(table.find('tr#'+$(this).parents('tr').attr('element')),elementsRaw,false);
      initEvent(table);
      initialize($(this).parents('tr'));
      $(this).parents('tr').remove();
      }
    );

    var cell = $(node.children("td")[options.treeColumn]);
    var padding = getPaddingLeft(cell) + options.indent;
    var arrayCell = childrenOf(node);
    if(arrayCell == null)return;
    arrayCell.each(function() {
      if(first)
        {
        $(this).children("td:first")[options.treeColumn].style.paddingLeft = padding + "px";
        }
      else
        {
        $(this).children("td:first")[options.treeColumn].style.paddingLeft = padding + "px";
        }
       if(node.hasClass('expanded'))
         {
         initialize($(this));
         $(this).show();
         }
       else
         {
         $(this).hide();
         }
       });
     if(typeof options.callbackCreateElement == 'function') {
      options.callbackCreateElement($(this));
      }

  }

  function initialize(node)
    {
    var options = getOptions(node);
    if(!node.hasClass("initialized")) {
      node.addClass("initialized");

      var privacy = '';
      if(node.attr('privacy') == undefined || node.attr('privacy') == 0)
        {
        privacy = json.browse['public'];
        }
      else if(node.attr('privacy') == 1)
        {
        privacy = json.browse['shared'];
        }
      else if(node.attr('privacy') == 2)
        {
        privacy = json.browse['private'];
        }

      node.find('td:first span').after('<span class="browserPrivacyInformation">'+privacy+'</span>');

      var childNodes = childrenOf(node);
      if(!node.hasClass("parent") && childNodes.length > 0) {
        node.addClass("parent");
        }
      if(node.hasClass("parent")) {
        var cell = $(node.children("td:first")[options.treeColumn]);
        var padding = getPaddingLeft(cell) + options.indent;

        childNodes.each(function() {
          $(this).children("td:first")[options.treeColumn].style.paddingLeft =  padding + "px";
          });

        if(options.expandable) {
          cell.prepend('<span style="margin-left: -' + (options.indent+12) + 'px; padding-left: ' + (options.indent+12) + 'px" class="expander"></span>');
          $(cell[0].firstChild).click(function() {node.toggleBranch();});

          if(options.clickableNodeNames) {
            cell[0].style.cursor = "pointer";
            $(cell).click(function(e) {
              // Don't double-toggle if the click is on the existing expander icon
              if (e.target.className != 'expander') {
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

  function move(node, destination)
    {
    node.insertAfter(destination);
    childrenOf(node).reverse().each(function() {
      move($(this), node[0]);
      });
    };

  function parentOf(node)
    {
    var classNames = node[0].className.split(' ');
    var options = getOptions(node);

    for(key in classNames)
      {
      if(classNames[key].match(options.childPrefix))
        {
        return getContainer(node).find("#" + classNames[key].substring(9));
        }
      }
    }

  function getContainer(node)
    {
    if(node.is('table'))
      {
      return node;
      }
    return $(node.parents('table')[0]);
    }

  function getOptions(node)
    {
    var table = getContainer(node);
    var options = $.data(table[0], 'options');
    return options ? options : $.fn.treeTable.defaults;
    }
})(jQuery);

var ajaxSizeRequest='';
function getElementsSize(table)
  {
  if(typeof(disableElementSize) !== 'undefined')
    {
    return;
    }
  var elements='';
  var i = 0;
  table.find('img.folderLoading').each(function() {
    i++;
    if(i > 10)
      {
      return ;
      }
    if($(this).attr('process') == undefined)
      {
      elements += $(this).attr('element') + '-';
      $(this).attr('process', 'true');
      }
    });
  if(elements != '')
    {
    ajaxSizeRequest=$.post(json.global.webroot+'/browse/getfolderssize',{folders: elements} , function(data) {
      arrayElement=jQuery.parseJSON(data);
      $.each(arrayElement, function(index, value) {
        var img = table.find('img.folderLoading[element='+value.id+']');
        img.after('<span class="elementSize">'+value.size+'</span>');
        img.parents('tr').find('td:first span:last').before('<span style="padding-left:0px;" class="elementCount">'+' ('+value.count+')'+'</span>');
        img.remove();
        });
      getElementsSize(table);
      });
    }
  }
