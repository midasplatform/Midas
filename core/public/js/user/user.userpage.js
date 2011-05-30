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
    $('img.tabsLoading').hide()
    
    $("#browseTable").treeTable();
    $("img.tableLoading").hide();
    $("table#browseTable").show();
  });
  
  
    //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('div.genericAction').hide();
      $('div.genericCommunities').hide();
      $('div.genericStats').hide();
      $('div.biographyBlock').hide();
      $('div.websiteBlock').hide();
      $('div.viewInfo').show();
      $('div.viewAction').show();
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
    