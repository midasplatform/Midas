  $(document).ready(function() {

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
    
    
  });
