// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */
/* global sliceFileName */

$(document).ready(function () {
    'use strict';
    json.search.keyword = $("<div/>").html(json.search.keyword).text(); // remove html entity encoding
    $('#live_search_value').val($('#live_search').val());
    $('#live_search').val(json.search.keyword);

    initSearchResults('all', false);

    $('a#allResults').css('font-weight', 'bold');
    $('a#relevanceSort').css('font-weight', 'bold');

    $('a#allResults').click(function () {
        $('#resultTypeLinks').find('a').css('font-weight', 'normal');
        initSearchResults('all', false);
        $(this).css('font-weight', 'bold');
        type = 'all';
    });
    $('a#itemResults').click(function () {
        $('#resultTypeLinks').find('a').css('font-weight', 'normal');
        initSearchResults('item', false);
        $(this).css('font-weight', 'bold');
        type = 'item';
    });
    $('a#folderResults').click(function () {
        $('#resultTypeLinks').find('a').css('font-weight', 'normal');
        initSearchResults('folder', false);
        $(this).css('font-weight', 'bold');
        type = 'folder';
    });
    $('a#communityResults').click(function () {
        $('#resultTypeLinks').find('a').css('font-weight', 'normal');
        initSearchResults('community', false);
        $(this).css('font-weight', 'bold');
        type = 'community';
    });
    $('a#userResults').click(function () {
        $('#resultTypeLinks').find('a').css('font-weight', 'normal');
        initSearchResults('user', false);
        $(this).css('font-weight', 'bold');
        type = 'user';
    });

    $('a#viewSort').click(function () {
        $('#sortTypeLinks').find('a').css('font-weight', 'normal');
        $(this).css('font-weight', 'bold');
        changeSorting('view');
    });
    $('a#relevanceSort').click(function () {
        $('#sortTypeLinks').find('a').css('font-weight', 'normal');
        $(this).css('font-weight', 'bold');
        changeSorting('relevance');
    });
    $('a#nameSort').click(function () {
        $('#sortTypeLinks').find('a').css('font-weight', 'normal');
        $(this).css('font-weight', 'bold');
        changeSorting('name');
    });
    $('a#dateSort').click(function () {
        $('#sortTypeLinks').find('a').css('font-weight', 'normal');
        $(this).css('font-weight', 'bold');
        changeSorting('date');
    });

    type = 'all';
});

var numberOfResults = 20;
var iterator;
var type;

function changeSorting(order) {
    'use strict';
    $('img.searchLoading').show();
    $('ul#searchResults').hide();
    $.post(json.global.webroot + '/search', {
            q: json.search.keyword,
            ajax: true,
            order: order
        },
        function (data) {
            var tmp = $.parseJSON(data);
            json.search.results = tmp.results;
            initSearchResults(type, false);
        });
}

function initSearchResults(type, append) {
    'use strict';
    var i = 0;
    var j = 0;
    if (append) {
        $('#moreResults').remove();
        j = iterator;
    }
    else {
        $('img.searchLoading').show();
        $('ul#searchResults').hide();
        $('ul#searchResults li').remove();
    }
    while (i < numberOfResults) {
        if (json.search.results[j] === undefined) {
            if (i === 0 && !append) {
                $('ul#searchResults').append('<li>' + json.search.noResults + '</li>');
            }
            break;
        }
        if (type == 'all' || json.search.results[j].resultType == type) {
            i++;
            $('ul#searchResults').append(createSearchResults(json.search.results[j]));
        }
        j++;
    }
    if (i == numberOfResults && json.search.results[j] !== undefined) {
        iterator = j;
        $('ul#searchResults').append('<li id="moreResults"><a>' + json.search.moreResults + '</a></li>');
        $('li#moreResults').click(function () {
            initSearchResults(type, true);
        });
    }
    $('img.searchLoading').hide();
    $('ul#searchResults').show();
}

function createSearchResults(element) {
    'use strict';
    var html = '';
    if (element.resultType == 'user') {
        html = "<img class='imageSearchResult' alt='' src='" + json.global.coreWebroot + "/public/images/icons/unknownUser-small.png'/>";
        html += "<a class='nameSearchResult' href='" + json.global.webroot + "/user/" + element.user_id + "'>" + sliceFileName(element.firstname + ' ' + element.lastname, 45) + "</a><br/>";
        html += "<span class='descriptionSearchResult' >" + element.company + "</span>";
        html += "<span class='dateSearchResult' >" + element.formattedDate + "</span>";
    }
    if (element.resultType == 'item') {
        html = "<img class='imageSearchResult' alt='' src='" + json.global.coreWebroot + "/public/images/FileTree/txt.png'/>";
        html += "<a class='nameSearchResult' href='" + json.global.webroot + "/item/" + element.item_id + "'>" + sliceFileName(element.name, 45) + "</a><br/>";
        html += "<span class='descriptionSearchResult' >" + element.description + "</span>";
        html += "<span class='dateSearchResult' >" + element.formattedDate + "</span>";
    }
    if (element.resultType == 'folder') {
        html = "<img class='imageSearchResult' alt='' src='" + json.global.coreWebroot + "/public/images/FileTree/directory.png'/>";
        html += "<a class='nameSearchResult' href='" + json.global.webroot + "/folder/" + element.folder_id + "'>" + sliceFileName(element.name, 45) + "</a><br/>";
        html += "<span class='descriptionSearchResult' >" + element.description + "</span>";
        html += "<span class='dateSearchResult' >" + element.formattedDate + "</span>";
    }
    if (element.resultType == 'community') {
        html = "<img class='imageSearchResult' alt='' src='" + json.global.coreWebroot + "/public/images/icons/community.png'/>";
        html += "<a class='nameSearchResult' href='" + json.global.webroot + "/community/" + element.community_id + "'>" + sliceFileName(element.name, 45) + "</a><br/>";
        html += "<span class='descriptionSearchResult' >" + element.description + "</span>";
        html += "<span class='dateSearchResult' >" + element.formattedDate + "</span>";
    }

    return '<li class="searchElement">' + html + '</li>';
}
