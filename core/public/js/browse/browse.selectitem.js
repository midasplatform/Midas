// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global itemSelectionCallback */
/* global sliceFileName */
/* global trimName */

$('#moveTable').treeTable({
    pageLength: 99999
});
$('img.tableLoading').hide();
$('table#moveTable').show();

$('.uploadApplet').hide();

if ($('#selectElements') !== undefined) {
    $('#selectElements').click(function () {
        'use strict';
        $('#destinationUpload').html($('#selectedDestination').html());
        $('#destinationId').val($('#selectedDestinationHidden').val());
        $('.destinationUpload').html($('#selectedDestination').html());
        $('.destinationId').val($('#selectedDestinationHidden').val());
        $('div.MainDialog').dialog('close');
        $('.uploadApplet').show();

        if (typeof itemSelectionCallback == 'function') {
            itemSelectionCallback($('#selectedDestination').html(), $('#selectedDestinationHidden').val());
        }
        return false;
    });
}

// dependence: common/browser.js
var ajaxSelectRequest = '';

function callbackSelect(node) {
    'use strict';
    if (node.attr('type') == 'item') {
        var selectedElement = node.find('span:eq(0)').html();

        $('#selectedDestinationHidden').val(node.attr('element'));
        $('#selectedDestination').html(sliceFileName(selectedElement, 40));
        $('#selectElements').removeAttr('disabled');
    }
}

$('img.infoLoading').show();
$('div.ajaxInfoElement').html('');

function callbackDblClick(node) {
    'use strict';
    //  genericCallbackDblClick(node);
}

function callbackCheckboxes(node) {
    'use strict';
    //  genericCallbackCheckboxes(node);
}

function callbackCustomElements(node, elements, first) {
    'use strict';
    var i = 1;
    var id = node.attr('id');
    elements['folders'] = $.makeArray(elements['folders']);

    var padding = parseInt(node.find('td:first').css('padding-left').slice(0, -2));
    var html = '';

    $.each(elements['folders'], function (index, value) {
        html += '<tr id="' + id + '-' + i + '" class="parent child-of-' + id + '" ajax="' + value['folder_id'] + '"type="folder"  policy="' + value['policy'] + '" element="' + value['folder_id'] + '">';
        html += '  <td><span class="folder">' + trimName(value['name'], padding) + '</span></td>';
        html += '</tr>';
        i++;
    });

    $.each(elements['items'], function (index, value) {

        html += '<tr id="' + id + '-' + i + '" class="child-of-' + id + '" privacy="' + value['privacy_status'] + '"  type="item" policy="' + value['policy'] + '" element="' + value['item_id'] + '">';
        html += '  <td><span class="file">' + trimName(value['name'], padding) + '</span></td>';
        html += '</tr>';
        i++;
    });
    return html;

}

// Live search
$.widget('custom.catcomplete', $.ui.autocomplete, {
    _renderMenu: function (ul, items) {
        'use strict';
        var self = this,
            currentCategory = '';
        $.each(items, function (index, item) {
            if (item.category != currentCategory) {
                ul.append('<li class="search-category">' + item.category + '</li>');
                currentCategory = item.category;
            }
            self._renderItemData(ul, item);
        });
    }
});

var cacheSearchSelectItem = {},
    lastXhr;
$('#live_search_item').catcomplete({
    minLength: 2,
    delay: 10,
    source: function (request, response) {
        'use strict';
        var term = request.term;
        if (term in cacheSearchSelectItem) {
            response(cacheSearchSelectItem[term]);
            return;
        }

        $('#searchloadingSelectItem').show();

        lastXhr = $.getJSON($('.webroot').val() + '/search/live?itemSearch=true', request, function (data, status, xhr) {
            $('#searchloadingSelectItem').hide();
            cacheSearchSelectItem[term] = data;
            if (xhr === lastXhr) {
                itemselected = false;
                response(data);
            }
        });
    }, // end source
    select: function (event, ui) {
        'use strict';
        itemselected = true;
        $('#selectedDestinationHidden').val(ui.item.itemid);
        $('#selectedDestination').html(ui.item.value);
        $('#selectElements').removeAttr('disabled');
    }
});

$('#live_search_item').focus(function () {
    'use strict';
    if ($('#live_search_item_value').val() == 'init') {
        $('#live_search_item_value').val($('#live_search_item').val());
        $('#live_search_item').val('');
    }
});

$('#live_search_item').focusout(function () {
    'use strict';
    if ($('#live_search_item').val() == '') {
        $('#live_search_item').val($('#live_search_item_value').val());
        $('#live_search_item_value').val('init');
    }
});

$('#live_search_item').keyup(function (e) {
    'use strict';
    if (e.keyCode == 13 && !itemselected) // enter key has been pressed
    {
        window.location = $('.webroot').val() + '/search/' + encodeURIComponent($('#live_search_item').val());
    }
});
