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
        
   var currentElement = json.item.item_id;
   
   function highlightCurrentPreview()
   {
     $('#fullscreenVisualize a.linkedcontentLink').css('font-weight','normal');
     $('#fullscreenVisualize a.linkedcontentLink[element='+currentElement+']').css('font-weight','bold');
   }
    /** preview */
     $('a#itemPreviewLink').click(function(){
       
      var height = $(window).height()-100;
      var width = 900;
      var url  = json.global.webroot+"/visualize/?itemId="+json.item.item_id+'&height='+height+'&width='+width;
      var html = '<div id="fullscreenVisualize" >';
      html +=   '<div id="fullscreenPanel">';
      html +=   '<a class="closeVisuButton" ><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> </a>';
      html +=   '<br/>';
      html +=   '<a class="previousVisu">Prev</a> - <a class="nextVisu">Nxt</a>';
      html +=   '<br/>';
      html +=   $('div.viewSameLocation').html();
      html +=   '</div>';
      html +=   '<iframe name="fullscreenVisualizeIframe" height="'+height+'" width="'+width+'" id="fullscreenVisualizeIframe" src="'+url+'"></iframe>';
      html +=   '</div>';
      
      $('.Wrapper').append(html);
      
      $('#fullscreenVisualize a.linkedcontentLink[preview=false]').remove();
      $('#fullscreenVisualize a.linkedcontentLink[preview=true]').removeAttr('href');
      
      highlightCurrentPreview();
      
      $('#fullscreenVisualize a.linkedcontentLink').click(function(){
        var height = $(window).height()-100;
        var width = 900;
        var url = json.global.webroot+"/visualize/?height="+height+'&width='+width+'&itemId='+$(this).attr('element');
        $('iframe#fullscreenVisualizeIframe').attr('src', url);
        currentElement = $(this).attr('element');
        highlightCurrentPreview();
      });
      
      $('a.previousVisu').click(function(){
        var obj = $('#fullscreenVisualize a.linkedcontentLink[element='+currentElement+']').parents('li').prev().find('a');
        var height = $(window).height()-100;
        var width = 900;
        var url = json.global.webroot+"/visualize/?height="+height+'&width='+width+'&itemId='+obj.attr('element');
        $('iframe#fullscreenVisualizeIframe').attr('src', url);
        currentElement = obj.attr('element');
        highlightCurrentPreview();
      });
      $('a.nextVisu').click(function(){
        var obj = $('#fullscreenVisualize a.linkedcontentLink[element='+currentElement+']').parents('li').next().find('a');
        var height = $(window).height()-100;
        var width = 900;
        var url = json.global.webroot+"/visualize/?height="+height+'&width='+width+'&itemId='+obj.attr('element');
        $('iframe#fullscreenVisualizeIframe').attr('src', url);
        currentElement = obj.attr('element');
        highlightCurrentPreview();
      });
      
      $('.MainDialog').hide();
      $('.TopDynamicBar').hide();
      $('.Topbar').show();
      $('.Header').hide();
      $('.SubWrapper').hide();
      $('#fullscreenVisualize a.closeVisuButton').click(function(){
        $('#fullscreenVisualize').remove();
        $('.Header').show();
        $('.SubWrapper').show();
      });
    });
  });
  