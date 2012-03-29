/**
 * !!! IMPORTANT !!!
 * Only place layout-independent javascript code in this file.
 * If your code refers to DOM elements of a layout, it should be placed in the javascript
 * file corresponding to that layout (e.g. midas.layout.js).
 */

$(document).ready(function() {
    // Right-sidebar view actions hover style (must be done in javascript due to parent refs)
    $('div.viewAction li a').hover(function () {
        $(this).parents('li').css('background-color', '#E5E5E5');
        }, function() {
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
    var sidebar = $('div.viewSideBar');
    var viewMain = $('div.viewMain');
    var topOffset = viewMain.length > 0 ? viewMain.offset().top : 0;

    if(sidebar.height() + 10 < $(window).height()) {
        var padding = 5 + Math.max(0, $(window).scrollTop() - topOffset);
        sidebar.css('padding-top', padding+'px');
    } else {
        sidebar.css('padding-top', '5px');
    }
});

// trim name by the number of character
function sliceFileName (name, nchar) {
    if(name.length>nchar) {
        var toremove = name.length - nchar;
        if(toremove < 13) {
            return name;
        }
        name = name.substring(0, 10)+'...'+name.substring(13 + toremove);
        return name;
    }
  return name;
}

// trim name by the number of pixel
function trimName (name, padding) {
    if(name.length * 7 + padding > 350) {
        var toremove = (name.length * 7 + padding - 350) / 8;
        if(toremove < 13) {
            return 'error';
        }
        name = name.substring(0, 10)+'...'+name.substring(name.length + 13 - toremove);
        return name;
    }
    return name;
}
