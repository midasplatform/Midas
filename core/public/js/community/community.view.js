  $(document).ready(function() {
    
    $( "#tabsGeneric" ).tabs({
      select: function(event, ui) {
        $('div.genericAction').show();
        $('div.genericCommunities').show();
        $('div.genericStats').show();
        $('div.viewInfo').hide();
        $('div.viewAction').hide();
        }
      });
    $("#tabsGeneric").show();
    $('img.tabsLoading').hide()
    
    $("#browseTable").treeTable();
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    
    
     $('a#communityDeleteLink').click(function()
    {
      var html='';
      html+=json.community.message['deleteMessage'];
      html+='<br/>';
      html+='<br/>';
      html+='<br/>';
      html+='<input style="margin-left:140px;" class="globalButton deleteCommunityYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
      html+='<input style="margin-left:50px;" class="globalButton deleteCommunityNo" type="button" value="'+json.global.No+'"/>';
      
      showDialogWithContent(json.community.message['delete'],html,false);
      
      $('input.deleteCommunityYes').unbind('click').click(function()
        { 
          location.replace(json.global.webroot+'/community/delete?communityId='+json.community.community_id);
        });
      $('input.deleteCommunityNo').unbind('click').click(function()
        {
           $( "div.MainDialog" ).dialog('close');
        });         
      
    });
  });
  
  
    //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('div.genericAction').hide();
      $('div.genericCommunities').hide();
      $('div.genericStats').hide();
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