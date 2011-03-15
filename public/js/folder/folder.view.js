  $(document).ready(function() {

    $("#browseTable").treeTable();
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    genericCallbackSelect($('div.defaultSideTrigger'));
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