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
midas.solr.displayResults = function(items) {
    for(var idx in items) {
        var item = items[idx];
        var result = $('#itemResultTemplate').clone().show().removeAttr('id');
        result.find('a.itemLink')
          .attr('href', json.global.webroot+'/item/'+item.id)
          .html(item.name);
        result.appendTo('#resultsArea');
    }
};

/**
 * Fetch a page of search results based on the current shared variable state
 */
midas.solr.fetchPage = function() {
    $('.nextPageSearch').hide();
    $('#resultsArea').html('');
    $('img.resultsLoading').show();
    var params = {
        query: midas.solr.searchQuery,
        displayOffset: midas.solr.displayOffset,
        solrOffset: midas.solr.solrOffset,
        limit: midas.solr.PAGE_LIMIT
    };
    $.post(json.global.webroot+'/solr/advanced/submit', params, function (responseText) {
        $('img.resultsLoading').hide();
        try {
            var resp = $.parseJSON(responseText);
        } catch(e) {
            midas.createNotice('Internal error occurred, contact an administrator');
            return;
        }
        if(resp.status == 'error') {
            midas.createNotice(resp.message, 3000, resp.status);
        }
        else {
            midas.solr.displayOffset = resp.displayOffset;
            midas.solr.total = resp.totalResults;
            midas.solr.displayResults(resp.items);
            midas.solr.solrOffset = resp.solrOffset; //iterate

            if(midas.solr.solrOffset < midas.solr.total) {
                $('.nextPageSearch').show();
            }
        }
    });
};

$(document).ready(function() {
    $('#advancedQueryField').autogrow();
    $('#advancedQueryField').focus();

    $('#showAdvancedSearchHelp').click(function () {
        midas.showDialogWithContent(
          'Advanced Search Instructions',
          $('#instructionsContent').html(),
          false,
          {width: 700});
    });

    $('#advancedSearchButton').click(function () {
        midas.solr.searchQuery = $('#advancedQueryField').val();
        midas.solr.displayOffset = 0;
        midas.solr.solrOffset = 0;
        midas.solr.fetchPage();
    });

    $('.nextPageSearch').click(function() {
        midas.solr.fetchPage();
    });
});
