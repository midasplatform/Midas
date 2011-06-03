  $(document).ready(function() {
    
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
      if($('div.viewSameLocation').length >0)
        {
        html +=   '<a class="previousVisu">Prev</a> - <a class="nextVisu">Nxt</a>';
        html +=   '<br/>';

        html +=   $('div.viewSameLocation').html();
        }
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