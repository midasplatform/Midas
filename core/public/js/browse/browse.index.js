  $(document).ready(function() {

    $("#browseTable").treeTable();
    
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    
    $('div.feedThumbnail img').fadeTo("slow",0.2);
    $('div.feedThumbnail img').mouseover(function(){
        $(this).fadeTo("fast",1);
    });
 
    $('div.feedThumbnail img').mouseout(function(){
        $(this).fadeTo("fast",0.2);
    });
    
    $('a.createCommunity').click(function()
    {
      if(json.global.logged)
        {
        loadDialog("createCommunity","/community/create");
        showDialog(json.community.createCommunity,false);
        }
      else
        {
        createNotive(json.community.contentCreateLogin,4000)
        $("div.TopDynamicBar").show('blind');
        loadAjaxDynamicBar('login','/user/login');
        }
    });
   
      
    $('.itemBlock').click(function(){
        $(location).attr('href',($('> .itemTitle',this).attr('href')));
       }) 
    
  });
  
  //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('div.defaultSide').hide();
      $('div.viewAction').show();
      $('div.viewInfo').show();
      $('div.ajaxInfoElement').show();
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