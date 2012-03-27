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
