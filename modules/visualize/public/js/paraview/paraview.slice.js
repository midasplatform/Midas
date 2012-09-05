// Set the web service base URL

var renderers = {};
var paraview;
var activeView;
var input;
var bounds, midI, midJ, midK;
var stateController = {};

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
    
    input = paraview.OpenDataFile({filename: file});
    paraview.Show();
    paraview.Hide();

    bounds = paraview.GetDataInformation().Bounds;
    if(bounds.length != 6) {
        console.log('Invalid image bounds:');
        console.log(bounds);
        return;
    }

    midI = (bounds[0] + bounds[1]) / 2.0;
    midJ = (bounds[2] + bounds[3]) / 2.0;
    midK = Math.ceil((bounds[4] + bounds[5]) / 2.0) - 1;

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
    activeView.setCameraPosition([midI, midJ, bounds[5] + 1]);
    activeView.setCameraFocalPoint([midI, midJ, midK]);
    activeView.setCenterOfRotation(activeView.getCameraFocalPoint());

    paraview.SetDisplayProperties({
        proxy: sliceFilter,
        view: activeView,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage'
    });
    paraview.Render();
    activeView.setCameraParallelScale(Math.max(midI, midJ));
    
    switchRenderer(true); // render in the div
    $('img.visuLoading').hide();
    $('img#bigScreenshot').hide();
    $('#renderercontainer').show();
    $('#sliceSlider').slider({
        min: bounds[4],
        max: bounds[5] - 1,
        value: midK,
        change: function(event, ui) {
            changeSlice(ui.value);
        },
        slide: function(event, ui) {
            updateSliceInfo(ui.value);
        }
    });

    updateSliceInfo(midK);
    disableMouseInteraction();
}

/**
 * Unregisters all mouse event handlers on the renderer
 */
function disableMouseInteraction() {
    var el = renderers.current.view;
    el.onclick = null;
    el.onmousemove = null;
    el.onmousedown = null;
    el.onmouseup = null;
    el.oncontextmenu = null;
    el.ontouchstart = null;
    el.ontouchmove = null;
}

function changeSlice (slice) {
    if(slice < bounds[4] || slice > bounds[5] - 1) {
        console.log('Invalid slice number: '+slice);
        return;
    }

    paraview.Hide(); //hide the previous slice
    activeView.setCameraFocalPoint([midI, midJ, slice]);
    var sliceFilter = paraview.ExtractSubset({
      Input: input,
      SampleRateI: 1,
      SampleRateJ: 1,
      SampleRateK: 1,
      VOI: [bounds[0], bounds[1], bounds[2], bounds[3], slice, slice + 1]
    });
    paraview.SetDisplayProperties({
        proxy: sliceFilter,
        view: activeView,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage'
    });
    paraview.Show({proxy: sliceFilter}); //show the next slice
}

/**
 * Update the value of the current slice, without rendering the slice.
 */
function updateSliceInfo(slice) {
    $('#sliceInfo').html('Slice: '+(slice+1)+' of '+bounds[5]);
}

function resetCamera () {
    paraview.ResetCamera();
    activeView.setCenterOfRotation(activeView.getCameraFocalPoint());
    activeView.setCameraFocalPoint([midI, midJ, midK]);
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
//    resetCamera();
    renderers.current.start();
    
}

$(window).load(function () {
    $('#sliceSlider').slider();
    json = jQuery.parseJSON($('div.jsonContent').html());
    start();
});

$(window).unload(function () {
    paraview.disconnect();
});


