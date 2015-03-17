// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.fixedSidebar = false;

/**
 * !!! IMPORTANT !!!
 * Only place layout-independent javascript code in this file.
 * If your code refers to DOM elements of a layout, it should be placed in the javascript
 * file corresponding to that layout (e.g. midas.layout.js).
 */

$(document).ready(function () {
    'use strict';
    // Right-sidebar view actions hover style (must be done in javascript due to parent refs)
    $('div.viewAction li a').hover(function () {
        $(this).parents('li').css('background-color', '#E5E5E5');
    }, function () {
        $(this).parents('li').css('background-color', 'white');
    });

    // Render qtips
    $('[qtip]').qtip({
        content: {
            attr: 'qtip'
        }
    });
});

/**
 * When the window scrolls, we should move the sidebar view to the top of the page.
 * Requires layout to work.
 */
$(window).scroll(function () {
    'use strict';
    var sidebar = $('div.viewSideBar');
    if (sidebar.length === 0) {
        return;
    }
    var viewMain = $('div.viewMain');
    if (viewMain.length === 0) {
        return;
    }
    var topOffset = viewMain.length > 0 ? viewMain.offset().top : 0;
    var fixed = $(window).scrollTop() - topOffset > 0;

    if (fixed && !midas.fixedSidebar && sidebar.height() + 45 < $(window).height()) {
        var left = sidebar.offset().left;
        sidebar.css('position', 'fixed')
            .css('top', '0px')
            .css('left', left + 'px');
        midas.fixedSidebar = true;
    }
    else if (!fixed && midas.fixedSidebar) {
        sidebar.css('position', 'static');
        midas.fixedSidebar = false;
    }
});

/**
 * Format a number of bytes to a human readable string.
 */
midas.formatBytes = function (sizeBytes) {
    'use strict';
    // If it's > 1GB, report to two decimal places, otherwise just one.
    var precision = sizeBytes > 1073741824 ? 2 : 1;
    for (var i = 0; sizeBytes > 1024; i += 1) {
        sizeBytes /= 1024;
    }

    return sizeBytes.toFixed(precision) + ' ' + ['B', 'KB', 'MB', 'GB', 'TB'][i];
};

// trim name by the number of character
function sliceFileName(name, nchar) {
    'use strict';
    if (name.length > nchar) {
        var toremove = name.length - nchar;
        if (toremove < 13) {
            return name;
        }
        name = name.substring(0, 10) + '...' + name.substring(13 + toremove);
        return name;
    }
    return name;
}

// trim name by the number of pixel
function trimName(name, padding) {
    'use strict';
    if (name.length * 7 + padding > 350) {
        var toremove = (name.length * 7 + padding - 350) / 8;
        if (toremove < 13) {
            return 'error';
        }
        name = name.substring(0, 10) + '...' + name.substring(name.length + 13 - toremove);
        return name;
    }
    return name;
}
