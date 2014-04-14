/**
 * This is the main javascript file for the empty layout.  It should
 * not contain references to any DOM elements from other layouts.
 */
var midas = midas || {};
var json;
 
// Prevent error if console.log is called
if (typeof console != "object") {
  var console = {
      'log': function () {}
  };
}

$(function () {
    // Parse json content
    // jQuery 1.8 has weird bugs when using .html() here, use the old-style innerHTML here
   json = jQuery.parseJSON($('div.jsonContent')[0].innerHTML);
});
