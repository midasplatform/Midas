$(document).ready(function() {

  $("#browseTable").treeTable({
    onFirstInit: enableRangeSelect,
    onNodeShow: enableRangeSelect,
    onNodeHide: enableRangeSelect
  });
  /**
   * Non-ajax'd pages (ones with only items in the view) do not get their
   * init callback made, so we manually initialize range selection
   * before the items are made visible asynchronously
   */
  $('input.treeCheckbox').enableCheckboxRangeSelection({
    onRangeSelect: function() {
      genericCallbackCheckboxes($('#browseTable'));
      }
    });
  // Select/deslect all rows. If we are doing deselect all, we include hidden rows
  $('#browseTableHeaderCheckbox').click(function() {
    var selector = this.checked ? '.treeCheckbox:visible' : '.treeCheckbox';
    $('#browseTable').find(selector).prop("checked", this.checked);
    genericCallbackCheckboxes($('#browseTable'));
  });

  $("img.tableLoading").hide();
  $("table#browseTable").show();
  genericCallbackSelect($('div.defaultSideTrigger'));

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
var ajaxSelectRequest='';
function callbackSelect(node)
  {
  genericCallbackSelect(node);
  }

function callbackDblClick(node)
  {
  genericCallbackDblClick(node);
  }

function callbackCheckboxes(node)
  {
  genericCallbackCheckboxes(node);
  }
