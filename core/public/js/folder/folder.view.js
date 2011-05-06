  $(document).ready(function() {

    $("#browseTable").treeTable();
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    genericCallbackSelect($('div.defaultSideTrigger'));
    
    $( "#tabsGeneric" ).tabs();
    $("#tabsGeneric").show();
    
    if($('.pathBrowser li').length>4)
      {
        while($('.pathBrowser li').length>4)
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