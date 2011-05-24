// Set the web service base URL
var serverUrl = "/PWService";


  var renderers = {};
  var paraview;
  var activeView;
  var lineSource;
  var x1;
  var z1;
  var x2;
  var z2;

  function start(){
      // Create a paraview proxy
      var file = json.visualize.url;
      paraview = new Paraview(serverUrl);
      paraview.createSession("midas", "midas webviz","default");
      paraview.OpenDataFile({filename: file});
      activeView = paraview.CreateIfNeededRenderView();
      paraview.Show();

      paraview.ResetCamera();
      activeView.setCenterOfRotation(activeView.getCameraFocalPoint());

      // Create renderers


      renderers.js = new JavaScriptRenderer("jsRenderer", serverUrl);
      renderers.js.init(paraview.sessionId, activeView.__selfid__);
      // Use Flash as default
      renderers.current = renderers.js;
      var width = json.visualize.width-50;
      var height = json.visualize.height-50;
      renderers.current.setSize(width, height);
      renderers.current.bindToElementId("renderercontainer");
      renderers.current.start();

  }


 $(document).ready(function() {
   json = jQuery.parseJSON($('div.jsonContent').html());
   start();
  });
   
   $(window).unload( function () { paraview.disconnect() } );