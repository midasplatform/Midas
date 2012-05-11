var midas = midas || {};
midas.packages = midas.packages || {};

midas.packages.showPlatform = function (event, ui) {
    if(!ui.newContent.hasClass('packagesFetched')) {
        ui.newContent.addClass('packagesFetched');
        ui.newContent.html('<img alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif" />');
        var os = ui.newContent.attr('os');
        var arch = ui.newContent.attr('arch');
        $.post(json.global.webroot+'/packages/package/latest', {
          os: os,
          arch: arch,
          applicationId: json.applicationId
        }, function(data) {
            midas.packages.renderPackages(os, arch, $.parseJSON(data));
        });
    }
};

/**
 * Render the packages under the appropriate section using the package widget template
 */
midas.packages.renderPackages = function (os, arch, packages) {
    var container = $('div.platformContainer[os="'+os+'"][arch="'+arch+'"]');
    container.html('');
    console.log(packages);
};

/**
 * Call with a simple os string; returns the html that should be rendered for that os
 */
midas.packages.transformOs = function (os) {
    // todo transform os
    return os;
};

midas.packages.transformArch = function (arch) {
    // todo transform arch
    return arch;
};

$(document).ready(function () {
    $('#platformList h3').each(function () {
        var el = $(this);
        el.find('a').html(midas.packages.transformOs(el.attr('os')) + ' ' +
                          midas.packages.transformArch(el.attr('arch')));
    });
    // TODO platform detection and figure out which tab to open by default
    $('#platformList').accordion({
        clearStyle: true,
        collapsible: true,
        autoHeight: false,
        active: false
    }).bind('accordionchange', midas.packages.showPlatform);
    $('#platformList').show();
});
