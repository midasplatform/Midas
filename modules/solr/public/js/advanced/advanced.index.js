// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global ajaxWebApi */
/* global json */

var midas = midas || {};
midas.solr = midas.solr || {};

midas.solr.searchQuery = '';
midas.solr.solrOffset = 0;
midas.solr.displayOffset = 0;
midas.solr.total = 0;
midas.solr.PAGE_LIMIT = 10;

/**
 * Renders the item result list and counters in the DOM, as well as the counters and prev/next buttons if needed
 */
midas.solr.displayResults = function (items) {
    'use strict';
    for (var idx in items) {
        var item = items[idx];
        var result = $('#itemResultTemplate').clone().show().removeAttr('id');
        result.find('a.itemLink')
            .attr('href', json.global.webroot + '/item/' + encodeURIComponent(item.id))
            .html(item.name);
        result.appendTo('#resultsArea');
    }
};

/**
 * Fetch a page of search results based on the current shared variable state
 */
midas.solr.fetchPage = function () {
    'use strict';
    $('.nextPageSearch').hide();
    $('#resultsArea').html('');
    $('img.resultsLoading').show();
    var params = {
        query: midas.solr.searchQuery,
        displayOffset: midas.solr.displayOffset,
        solrOffset: midas.solr.solrOffset,
        limit: midas.solr.PAGE_LIMIT
    };
    $.post(json.global.webroot + '/solr/advanced/submit', params, function (responseText) {
        $('img.resultsLoading').hide();
        var jsonResponse;
        try {
            jsonResponse = $.parseJSON(responseText);
        }
        catch (e) {
            midas.createNotice('Internal error occurred, contact an administrator');
            return;
        }
        if (jsonResponse.status == 'error') {
            midas.createNotice(jsonResponse.message, 3000, jsonResponse.status);
        }
        else {
            midas.solr.displayOffset = jsonResponse.displayOffset;
            midas.solr.total = jsonResponse.totalResults;
            midas.solr.displayResults(jsonResponse.items);
            midas.solr.solrOffset = jsonResponse.solrOffset; // iterate

            if (midas.solr.solrOffset < midas.solr.total) {
                $('.nextPageSearch').show();
            }
        }
    });
};

midas.solr.fetchTypes = function () {
    'use strict';
    ajaxWebApi.ajax({
        method: 'midas.metadata.types.list',
        success: function (retVal) {
            var typeCombo = $("#typeCombo");
            var typeArray = retVal.data;
            var curType;
            var i;
            for (i = 0; i < typeArray.length; ++i) {
                curType = typeArray[i];
                typeCombo.append($('<option></option>').attr("value", curType).text(curType));
            }
            typeCombo.change(function () {
                midas.solr.fetchElements($(this).val());
            });
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 3000, 'error');
        },
        complete: function () {},
        log: $('<p></p>')
    });
};

midas.solr.fetchElements = function (type) {
    'use strict';
    if (type === 'type') {
        $('#elementCombo').empty()
            .append($('<option></option>').attr("value", "element").text("Element"));
        $('#qualifierCombo').empty()
            .append($('<option></option>').attr("value", "qualifier").text("Qualifier"));
        return;
    }
    ajaxWebApi.ajax({
        method: 'midas.metadata.elements.list',
        args: 'typename=' + type,
        success: function (retVal) {
            var elementCombo = $("#elementCombo");
            var elementArray = retVal.data;
            var curElement;
            var i;
            elementCombo.empty();
            elementCombo.append($('<option></option>').attr("value", "element").text("Element"));
            for (i = 0; i < elementArray.length; ++i) {
                curElement = elementArray[i];
                elementCombo.append($('<option></option>').attr("value", curElement).text(curElement));
            }
            elementCombo.change(function () {
                midas.solr.fetchQualifiers(type, $(this).val());
            });
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 3000, 'error');
        },
        complete: function () {},
        log: $('<p></p>')
    });
};

midas.solr.fetchQualifiers = function (type, element) {
    'use strict';
    if (type === 'type' || element === 'element') {
        $('#elementCombo').empty()
            .append($('<option></option>').attr("value", "element").text("Element"));
        $('#qualifierCombo').empty()
            .append($('<option></option>').attr("value", "qualifier").text("Qualifier"));
        return;
    }
    ajaxWebApi.ajax({
        method: 'midas.metadata.qualifiers.list',
        args: 'typename=' + type + '&element=' + element,
        success: function (retVal) {
            var qualifierCombo = $("#qualifierCombo");
            var qualifierArray = retVal.data;
            var curQualifier;
            var i;
            qualifierCombo.empty();
            qualifierCombo.append($('<option></option>').attr("value", "qualifier").text("Qualifier"));
            for (i = 0; i < qualifierArray.length; ++i) {
                curQualifier = qualifierArray[i];
                qualifierCombo.append($('<option></option>').attr("value", curQualifier).text(curQualifier));
            }
        },
        error: function (retVal) {
            midas.createNotice(retVal.message, 3000, 'error');
        },
        complete: function () {},
        log: $('<p></p>')
    });
};

$(document).ready(function () {
    'use strict';
    $('#advancedQueryField').autogrow();
    $('#advancedQueryField').focus();

    $('#showAdvancedSearchHelp').click(function () {
        midas.showDialogWithContent(
            'Advanced Search Instructions',
            $('#instructionsContent').html(),
            false, {
                width: 700
            });
    });

    $('#advancedSearchButton').click(function () {
        midas.solr.searchQuery = $('#advancedQueryField').val();
        midas.solr.displayOffset = 0;
        midas.solr.solrOffset = 0;
        midas.solr.fetchPage();
    });

    $('.nextPageSearch').click(function () {
        midas.solr.fetchPage();
    });

    midas.solr.fetchTypes();
    $('#insertKeyButton').click(function () {
        var type = $('#typeCombo').val(),
            element = $('#elementCombo').val(),
            qualifier = $('#qualifierCombo').val();
        var key = type + '-' + element + '.' + qualifier + ': ';
        var queryField;
        if (type !== 'type' && element !== 'element' && qualifier !== 'qualifier') {
            queryField = $('#advancedQueryField');
            queryField.focus();
            queryField.val(queryField.val() + key);
        }
    });
});
