var midas = midas || {};
midas.statistics = midas.statistics || {};
midas.statistics.mapMarkers = [];

/**
 * Remove all current markers from the map
 */
midas.statistics.clearMap = function() {
  if (midas.statistics.mapMarkers) {
    for (var i = 0; i < midas.statistics.mapMarkers.length; i++) {
      midas.statistics.mapMarkers[i].setMap(null);
    }
    midas.statistics.mapMarkers = [];
  }
}

/**
 * Parses the response from itemstatistics and populates the map with markers
 */
midas.statistics.populateMap = function(responseText, statusText, xhr, form) {
    try {
        var response = jQuery.parseJSON(responseText);
        midas.statistics.clearMap();

        for (var i = 0; i < response.downloads.length; i++) {
            var myLatlng = new google.maps.LatLng(response.downloads[i].latitude, response.downloads[i].longitude);
            var marker = new google.maps.Marker({position: myLatlng});
            midas.statistics.mapMarkers.push(marker);
        }
        midas.statistics.clusterer.clearMarkers();
        midas.statistics.clusterer.addMarkers(midas.statistics.mapMarkers);
        $('#filteredCount').html(response.downloads.length);
    } catch (e) {
        alert("An error occured. Please check the logs.");
        return false;
    } finally {
        $('input.filterButton').removeAttr('disabled');
        $('img#loadingStatistics').hide();
    }
}

$(document).ready(function() {
    var tabs = $("#tabsGeneric").tabs({
        select: function(event, ui) { }
    });
    $('#tabsGeneric').show();
    $('img.tabsLoading').hide();
    $('#tabsGeneric').bind('tabsshow', function(event, ui) {
        if(plotErrors._drawCount == 0) {
            plotErrors.replot();
        }
    });
    var errors = json.stats.downloads;
    $.each(errors, function(i, val) {
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
    midas.statistics.map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
    midas.statistics.clusterer = new MarkerClusterer(midas.statistics.map, midas.statistics.mapMarkers);

    // Set up smart date picker widget logic
    var dates = $("#startdate, #enddate").datepicker({
        defaultDate: "today",
        changeMonth: true,
        numberOfMonths: 1,
        onSelect: function(selectedDate) {
          var option = this.id == "startdate" ? "minDate" : "maxDate";
          var instance = $(this).data("datepicker");
          var date = $.datepicker.parseDate(
            instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
            selectedDate, instance.settings);
          dates.not(this).datepicker("option", option, date);
          },
        dayNamesMin: ["S", "M", "T", "W", "T", "F", "S"]
    });

    $('#downloadResultLimit').spinbox({
        min: 1,
        step: 1
    });

    $('#filterForm').ajaxForm({
        beforeSubmit: function(formData, jqForm, options) {
            $('input.filterButton').attr('disabled', 'disabled');
            $('img#loadingStatistics').show();
            return true;
        },
        success: midas.statistics.populateMap
    });

    $('#startdate').val(json.initialStartDate);
    $('#enddate').val(json.initialEndDate);
    $('#filterForm').submit();
});
