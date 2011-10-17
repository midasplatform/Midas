
  $(document).ready(function() {
    $('div.feedThumbnail img').fadeTo("slow",0.4);
    $('div.feedThumbnail img').mouseover(function(){
        $(this).fadeTo("fast",1);
    });
 
    $('div.feedThumbnail img').mouseout(function(){
        $(this).fadeTo("fast",0.4);
    });
    
    
    
  });
  
