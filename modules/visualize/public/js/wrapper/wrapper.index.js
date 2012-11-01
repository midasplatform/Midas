function highlightCurrentPreview (currentElement) {
    $('#fullscreenVisualize a.linkedcontentLink').css('font-weight','normal');
    $('#fullscreenVisualize a.linkedcontentLink[element='+currentElement+']').css('font-weight','bold');
}

function createInfoAjaxVisualize (itemId) {
    $('img.infoLoading').show();
    $('div.ajaxInfoElement').html('');
    $.ajax({
        type: "POST",
        url: json.global.webroot+'/browse/getelementinfo',
        data: {type: 'item', id: itemId},
        success: function(jsonContent){
            midas.createInfo(jsonContent);
            $('img.infoLoading').hide();
        }
    });
}

$(document).ready(function () {
      var currentElement = json.item.item_id;

      /** create preview page */
      var height = $(window).height()-100;
      var width = 800;
      var url  = json.global.webroot+"/visualize/?itemId="+json.item.item_id+'&height='+height+'&width='+width+'&viewMode='+json.viewMode;
      var html = '<div id="fullscreenVisualize" style="min-width:1200px">';
      html +=   '<div id="fullscreenPanel">';
      html +=   '<div style="float:left;margin-right:2px;" class="genericBigButton ">';
      html +=   '<a style="float:left;" class="closeVisuButton"><img style="float:left;margin-right:2px;" alt="" src="'+json.global.coreWebroot+'/public/images/icons/back.png">Back</a></div>';
      html +=   '<br/>';
      html +=   '<br/>';
      if($('div.viewSameLocation').length > 0) {
          html +=   '<a class="previousVisu"><img alt="" src="'+json.global.webroot+'/modules/visualize/public/images/back.png"/></a>  <a class="nextVisu"><img alt="" src="'+json.global.webroot+'/modules/visualize/public/images/next.png"/></a>';
          html +=   '<br/>';
          html +=   $('div.viewSameLocation').html();
      }
      html +=   '<div class="ajaxInfoElementWrapper">';
      html +=   '<h1>Info</h1>';
      html +=   '<img class="infoLoading" style="display:none;" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>';
      html +=   ' <div class="ajaxInfoElement">';
      html +=   ' </div>';
      html +=   '</div>';

      html +=   '</div>';
      html +=   '<iframe name="fullscreenVisualizeIframe" height="'+height+'" width="'+width+'" id="fullscreenVisualizeIframe" src="'+url+'"></iframe>';
      html +=   '</div>';

      $('.Wrapper').append(html);

      $('#fullscreenVisualize a.linkedcontentLink[preview=false]').parents('li').remove();
      $('#fullscreenVisualize a.linkedcontentLink[preview=true]').removeAttr('href');

      highlightCurrentPreview(currentElement);
      createInfoAjaxVisualize(json.item.item_id);

      $('#fullscreenVisualize a.linkedcontentLink').click(function () {
          var height = $(window).height()-100;
          var width = 900;
          var url = json.global.webroot+"/visualize/?height="+height+'&width='+width+'&itemId='+$(this).attr('element');
          $('iframe#fullscreenVisualizeIframe').attr('src', url);
          currentElement = $(this).attr('element');
          highlightCurrentPreview(currentElement);
          createInfoAjaxVisualize(currentElement);

          var obj = $('#fullscreenVisualize a.linkedcontentLink[element='+currentElement+']');
          var objTmp = obj.parents('li').prev().find('a');
          $('a.nextVisu').show();
          $('a.previousVisu').show();
          if(objTmp.length == 0) {
              $('a.previousVisu').hide();
          }

          objTmp = obj.parents('li').next().find('a');
          if(objTmp.length == 0) {
              $('a.nextVisu').hide();
          }
      });

      $('a.previousVisu').click(function(){
        var obj = $('#fullscreenVisualize a.linkedcontentLink[element='+currentElement+']').parents('li').prev().find('a');
        var height = $(window).height()-100;
        var width = 900;
        var url = json.global.webroot+"/visualize/?height="+height+'&width='+width+'&itemId='+obj.attr('element');
        $('iframe#fullscreenVisualizeIframe').attr('src', url);
        currentElement = obj.attr('element');
        highlightCurrentPreview(currentElement);
        createInfoAjaxVisualize(currentElement);
        $('a.nextVisu').show();

        obj = obj.parents('li').prev().find('a');
        if(obj.length == 0)
          {
          $('a.previousVisu').hide();
          }
      });
      $('a.nextVisu').click(function(){
        $('a.previousVisu').show();
        var obj = $('#fullscreenVisualize a.linkedcontentLink[element='+currentElement+']').parents('li').next().find('a');
        var height = $(window).height()-100;
        var width = 900;
        var url = json.global.webroot+"/visualize/?height="+height+'&width='+width+'&itemId='+obj.attr('element');
        $('iframe#fullscreenVisualizeIframe').attr('src', url);
        currentElement = obj.attr('element');
        highlightCurrentPreview(currentElement);
        createInfoAjaxVisualize(currentElement);

        obj = obj.parents('li').next().find('a');
        if(obj.length == 0)
          {
          $('a.nextVisu').hide();
          }
      });

      $('.MainDialog').hide();
      $('.TopDynamicBar').hide();
      $('.Topbar').show();
      //$('.Header').hide();
      $('.SubWrapper').hide();
      $('#fullscreenVisualize a.closeVisuButton').click(function(){
        window.location.replace(json.global.webroot+"/item/"+json.item.item_id);
      });
});

