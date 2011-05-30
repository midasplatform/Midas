  $(document).ready(function() {
    $('a.moveCopyLink').click(function()
      {
        loadDialog("movecopy","/browse/movecopy/?move=false&items="+json.item.item_id);
        showDialog(json.item.message.movecopy);
      });
   
     $('a#itemDeleteLink').click(function()
    {
      var html='';
      html+=json.item.message['deleteMessage'];
      html+='<br/>';
      html+='<br/>';
      html+='<br/>';
      html+='<input style="margin-left:140px;" class="globalButton deleteItemYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
      html+='<input style="margin-left:50px;" class="globalButton deleteItemNo" type="button" value="'+json.global.No+'"/>';
      
      showDialogWithContent(json.item.message['delete'],html,false);
      
      $('input.deleteItemYes').unbind('click').click(function()
        { 
          location.replace(json.global.webroot+'/item/delete?itemId='+json.item.item_id);
        });
      $('input.deleteItemNo').unbind('click').click(function()
        {
           $( "div.MainDialog" ).dialog('close');
        });         
      
    });
    
    $('a.sharingLink').click(function(){
      loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
      showDialog(json.browse.share);
    });
    
    $('a.uploadRevisionLink').click(function(){
      loadDialog("uploadrevision"+$(this).attr('element'),"/upload/revision?itemId="+$(this).attr('element'));
      showDialog($(this).html());
    });
    
    //edit
    $('a.editItemLink').click(function(){
      loadDialog("editItem"+json.item.item_id,"/item/edit?itemId="+json.item.item_id);
        showDialog(json.browse.edit,false);
    });
        
    /** preview */
     $('a#itemPreviewLink').click(function(){
       
      var height = $(window).height()-100;
      var width = $(window).width()-10;
      var url  = json.global.webroot+"/visualize/?itemId="+json.item.item_id+'&height='+height+'&width='+width;
      var html = '<div id="fullscreenVisualize" >';
      html +=   '<div id="fullscreenPanel">test</div>';
      html +=   '<iframe name="fullscreenVisualizeIframe" height="'+height+'" width="'+width+'" id="fullscreenVisualizeIframe" src="'+url+'"></iframe>';
      html +=   '</div>';
      
      $('body').append(html);
      $('.MainDialog').hide();
      $('.TopDynamicBar').hide();
      $('.Topbar').hide();
      $('.Header').hide();
      $('.Wrapper').hide();
    });
  });
  