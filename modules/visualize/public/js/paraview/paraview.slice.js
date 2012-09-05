// Set the web service base URL

var renderers = {};
var paraview;
var activeView;
var lineSource;
var bounds;
var stateController = {};

var isControlVisible = false;

function start(){
    // Create a paraview proxy
    var file = json.visualize.url;

    if(typeof Paraview != 'function') {
        alert('Paraview javascript was not fetched correctly from server.');
        return;
    }

    paraview = new Paraview("/PWService");
    paraview.errorListener = {
        manageError: function(error) {
            //alert('A ParaViewWeb error occurred; check the console for information');
            console.log(error);
            return true;
        }
    };
    paraview.createSession("midas", "slice viz", "default");
    
    var input = paraview.OpenDataFile({filename: file});
    paraview.Show();
    paraview.Hide();

    bounds = paraview.GetDataInformation().Bounds;
    if(bounds.length != 6) {
        console.log('Invalid image bounds:');
        console.log(bounds);
        return;
    }

    var midI = (bounds[0] + bounds[1]) / 2.0;
    var midJ = (bounds[2] + bounds[3]) / 2.0;
    var midK = (bounds[4] + bounds[5]) / 2;
    var sliceFilter = paraview.ExtractSubset({
      Input: input,
      SampleRateI: 1,
      SampleRateJ: 1,
      SampleRateK: 1,
      VOI: [bounds[0], bounds[1], bounds[2], bounds[3], midK, midK + 1]
    });
    paraview.Show({proxy: sliceFilter});
    
    activeView = paraview.CreateIfNeededRenderView();
    activeView.setViewSize(800, 640);
    activeView.setCenterAxesVisibility(false);
    activeView.setOrientationAxesVisibility(false);
    activeView.setCameraParallelProjection(true);
    activeView.setCameraPosition([midI, midJ, midK + 1]);
    activeView.setCameraFocalPoint([midI, midJ, midK]);
    activeView.setCenterOfRotation(activeView.getCameraFocalPoint());
        
    paraview.SetDisplayProperties({
        proxy: sliceFilter,
        view: activeView,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage'
    });
    paraview.Render();
    
    switchRenderer(true); // render in the div
    $('img.visuLoading').hide();
    $('img#bigScreenshot').hide();
    $('#renderercontainer').show();
    console.log(paraview);
}


$(window).load(function () {
    json = jQuery.parseJSON($('div.jsonContent').html());
    start();
});

$(window).unload(function () {
    paraview.disconnect();
});

function resetCamera () {
    paraview.ResetCamera();
    activeView.setCenterOfRotation(activeView.getCameraFocalPoint());
    renderers.current.setSize(800, 640);
}

function isoUpdate (value) {
    stateController.iso.setIsosurfaces(value);
}

function switchRenderer (first) {
    if(renderers.js == undefined) {
        renderers.js = new JavaScriptRenderer("jsRenderer", "/PWService");
        renderers.js.init(paraview.sessionId, activeView.__selfid__);
        $('img.toolButton').show();
    }

    if(!first) {
        renderers.current.unbindToElementId('renderercontainer');
    }
    renderers.current = renderers.js;
    renderers.current.bindToElementId('renderercontainer');
    resetCamera();
    renderers.current.start();
    
}

