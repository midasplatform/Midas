  $(document).ready(function() {

    $("#browseTable").treeTable();
    
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    
    $( "#tabs" ).tabs({
      select: function(event, ui) {
        $('div.userAction').show();
        $('div.userCommunities').show();
        $('div.userStats').show();
        $('div.viewInfo').hide();
        $('div.viewAction').hide();
        }
      });
  });
  
  
    //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('div.userAction').hide();
      $('div.userCommunities').hide();
      $('div.userStats').hide();
      $('div.viewInfo').show();
      $('div.viewAction').show();
      genericCallbackSelect(node);  
    }

    function callbackDblClick(node)
    {
      genericCallbackDblClick(node);
    }