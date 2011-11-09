$(document).ready(function() {

  initDashboard();
        function initDashboard() {

          var dashboard = $('#dashboard').dashboard({
            // layout class is used to make it possible to switch layouts
            layoutClass:'layout',
            // feed for the widgets which are on the dashboard when opened
            json_data : {
              url:json.global.webroot+"/core/public/jsonfeed/mywidgets.json"
            }


          }); // end dashboard call

          // the init builds the dashboard. This makes it possible to first unbind events before the dashboars is built.
          dashboard.init();
        }
      });
