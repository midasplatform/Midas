var midas = midas || {};

$(document).ready(function() {

  $("#browseTable").treeTable({
    onFirstInit: midas.enableRangeSelect,
    onNodeShow: midas.enableRangeSelect,
    onNodeHide: midas.enableRangeSelect
  });
  /**
   * Non-ajax'd pages (ones with only items in the view) do not get their
   * init callback made, so we manually initialize range selection
   * before the items are made visible asynchronously
   */
  $('input.treeCheckbox').enableCheckboxRangeSelection({
    onRangeSelect: function() {
      midas.genericCallbackCheckboxes($('#browseTable'));
      }
    });
  // Select/deslect all rows. If we are doing deselect all, we include hidden rows
  $('#browseTableHeaderCheckbox').click(function() {
    var selector = this.checked ? '.treeCheckbox:visible' : '.treeCheckbox';
    $('#browseTable').find(selector).prop("checked", this.checked);
    midas.genericCallbackCheckboxes($('#browseTable'));
  });

  $("img.tableLoading").hide();
  $("table#browseTable").show();
  midas.genericCallbackSelect($('div.defaultSideTrigger'));

  $( "#tabsGeneric" ).tabs();
  $("#tabsGeneric").show();

  if($('.pathBrowser li').length > 4)
    {
    while($('.pathBrowser li').length > 4)
      {
      $('.pathBrowser li:first').remove();
      }

    $('.pathBrowser li:first').before('<li>...</li>');
    }
  $('.pathBrowser').show();
});

//dependance: common/browser.js
midas.ajaxSelectRequest= '';
function callbackSelect(node)
  {
  midas.genericCallbackSelect(node);
  }

function callbackDblClick(node)
  {
  midas.genericCallbackDblClick(node);
  }

function callbackCheckboxes(node)
  {
  midas.genericCallbackCheckboxes(node);
  }
