var midas = midas || {};
midas.packages = midas.packages || {};

midas.packages.showRelease = function(event, ui) {
    if(!ui.newContent.hasClass('packagesFetched')) {
        ui.newContent.addClass('packagesFetched');
        ui.newContent.html('<img alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif" />');
        var release = ui.newContent.attr('element');
        $.post(json.global.webroot+'/packages/application/getpackages', {
          release: release,
          applicationId: json.applicationId
        }, function(data) {
            midas.packages.renderPackages(release, $.parseJSON(data));
        });
    }
};

/**
 * Render the packages under the appropriate release section using the package widget template
 */
midas.packages.renderPackages = function(release, packages) {
    var container = $('div.releaseEntry[element="'+release+'"]');
    container.html('');
    var table = $('#packageListTemplate').clone();
    table.attr('id', 'packageList'+release);
    table.show();

    $.each(packages, function(k, val) {
        var html = '<tr>';
        html += '<td>'+midas.packages.transformOs(val.os)+' '+val.arch+'</td>';
        html += '<td>'+val.packagetype+'</td>';
        html += '<td><a href="'+json.global.webroot+'/download?items='+val.item_id+'">'+
          '<img alt="" src="'+json.global.webroot+'/modules/packages/public/images/package.png"/> Download</a> / ';
        html += '<a href="'+json.global.webroot+'/item/'+val.item_id+'">'+
          '<img alt="" src="'+json.global.coreWebroot+'/public/images/icons/page_white_go.png"/> View</a> / ';
        html += '<a href="'+json.global.webroot+'/statistics/item?id='+val.item_id+'">'+
          '<img alt="" src="'+json.global.webroot+'/modules/statistics/public/images/chart_bar.png"/> Stats</a></td>';
        html += '</tr>';
        table.find('tbody').append(html);
    });
    table.appendTo(container);
};

/**
 * Call with a simple os string; returns the html that should be rendered for that os
 */
midas.packages.transformOs = function(os) {
    // todo transform os
    return os;
};

$(document).ready(function () {
    if(json.openRelease) {
        midas.packages.openRelease = json.openRelease;
        $('div.releaseEntry[element="'+json.openRelease+'"]').addClass('packagesFetched');
        midas.packages.renderPackages(json.openRelease, json.latestReleasePackages);
    }

    $('#packageList').accordion({
        clearStyle: true,
        collapsible: true,
        autoHeight: false
    }).bind('accordionchange', midas.packages.showRelease);
    $('#packageList').show();
});
