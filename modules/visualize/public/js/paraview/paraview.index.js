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
  var stateController = {};
  
  var isControlVisible = false;
  var isOrientationAxisVisible = true;
  var isRotationAxisVisible = true;


  function start(){
      // Create a paraview proxy
      var file = json.visualize.url;
      var stateEnable = json.visualize.openState;
      paraview = new Paraview(serverUrl);
      paraview.createSession("midas", "midas webviz","default");
      if(stateEnable)
        {
        paraview.LoadState({filename: file});
        activeView = paraview.CreateIfNeededRenderView();
        }
      else
        {
        paraview.OpenDataFile({filename: file});
        activeView = paraview.CreateIfNeededRenderView();
        paraview.Show();
        }

      paraview.ResetCamera();
      activeView.setCenterOfRotation(activeView.getCameraFocalPoint());

      // Create renderers
      switchRenderer(true);
      $('img.visuLoading').hide();
      $('#renderercontainer').show();
  }


 $(document).ready(function() {
   json = jQuery.parseJSON($('div.jsonContent').html());
   start();
  });
   
   $(window).unload( function () {paraview.disconnect()} );
   
 
    function initController(){
    stateController.iso = paraview.FindSource({name: "Contour1" });
    console.log(stateController.iso);
}
 
   
    function resetCamera() {
        paraview.ResetCamera();
        activeView.setCenterOfRotation(activeView.getCameraFocalPoint());
        var width = json.visualize.width-50;
        var height = json.visualize.height-50;
        renderers.current.setSize(width, height);
    }
    
    
    
    function changeOrientationAxisVisibility() {
        isOrientationAxisVisible = !isOrientationAxisVisible;
        activeView.setOrientationAxesVisibility(isOrientationAxisVisible);
    }
    function changeRotationAxisVisibility() {
        isRotationAxisVisible = !isRotationAxisVisible;
        activeView.setCenterAxesVisibility(isRotationAxisVisible);
    }
    
    function isoUpdate(value){
    str = 'stateController.iso.setIsosurfaces(value)';
    eval(str);
    }
    function changeControlVisibility() {
    isControlVisible = !isControlVisible;
    $('#controlPanel').show();
    if(isControlVisible) {
        $('#renderercontainer').width($('#renderercontainer').width()-200);
        $('#renderercontainer').css('left','200px');
    } else {
        $('#renderercontainer').width($('#renderercontainer').width()-200);
        $('#renderercontainer').css('left','200px');
    }
    }

    
    function switchRenderer(first){
          var type = $('#renderer-type').val();
          if(type == 'js')
            {
            if(renderers.js == undefined)
              {
              renderers.js = new JavaScriptRenderer("jsRenderer", serverUrl);
              renderers.js.init(paraview.sessionId, activeView.__selfid__);
              $('img.toolButton').show();
              }
            }
          if(type == 'webgl')
            {
            if(renderers.webgl == undefined)
              {
              renderers.webgl = new WebGLRenderer("webglRenderer", serverUrl);
              renderers.webgl.init(paraview.sessionId, activeView.__selfid__);
              $('img.toolButton').hide();
              }
            }
          if(renderers[type]){
              if(!first)
                {
                renderers.current.unbindToElementId('renderercontainer');
                }
              renderers.current = renderers[type];
              renderers.current.bindToElementId('renderercontainer');
              renderers.current.start();
              var width = json.visualize.width;
              var height = json.visualize.height;
              renderers.current.setSize(width, height);
              if(type == 'js')
                {
                resetCamera();
                initController();
                }
          }
      }