  $(document).ready(function() {
    
    $( "#tabsGeneric" ).tabs({
      select: function(event, ui) {
        $('div.genericAction').show();
        $('div.genericCommunities').show();
        $('div.genericStats').show();
        $('div.biographyBlock').show();
        $('div.websiteBlock').show();
        $('div.viewInfo').hide();
        $('div.viewAction').hide();
        }
      });
    $("#tabsGeneric").show();
    $('img.tabsLoading').hide();
    
    $("#browseTable").treeTable({
      onFirstInit: midas.enableRangeSelect,
      onNodeShow: midas.enableRangeSelect,
      onNodeHide: midas.enableRangeSelect
    });

    midas.browser.enableSelectAll();

    $("img.tableLoading").hide();
    $("table#browseTable").show();
  });
  
  
    //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('div.genericAction').show();
      $('div.genericCommunities').hide();
      $('div.genericStats').hide();
      $('div.biographyBlock').hide();
      $('div.websiteBlock').hide();
      $('div.viewInfo').show();
      $('div.viewAction').show();

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
    
