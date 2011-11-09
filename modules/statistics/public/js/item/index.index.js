$(document).ready(function() {
  var tabs = $("#tabsGeneric").tabs({
    select: function(event, ui) { }
    });
  $('#tabsGeneric').show();
  $('img.tabsLoading').hide();
  $("#tabsGeneric").bind('tabsshow', function(event, ui) {
    if (plotErrors._drawCount == 0) {
      plotErrors.replot();
      }
    });
  var errors = json.stats.downloads;
  jQuery.each(errors, function(i, val) {
    errors[i][1] = parseInt(errors[i][1]);
  });

  var plotErrors = $.jqplot('chartDownloads', [errors], {
    title:'Number of downloads',
    gridPadding:{right:35},
    axes:{
      xaxis:{
        renderer:$.jqplot.DateAxisRenderer,
        tickOptions:{formatString:'%b %#d'},
        tickInterval:'1 day'
      }
    },
    series:[{lineWidth:4, markerOptions:{style:'square'}}]
  });

  var latlng = new google.maps.LatLng(0, 0);
  var myOptions = {
    zoom: 2,
    center: latlng,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  };
  var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
  placeDownloadPins(map);
});
