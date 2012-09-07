var midas = midas || {};

var renderers = {};
var paraview;
var activeView;
var input;
var bounds, midI, midJ, midK;
var minVal, maxVal, imageWindow;
var stateController = {};

function start () {
    // Create a paraview proxy
    var file = json.visualize.url;
    var container = $('#renderercontainer');

    if(typeof Paraview != 'function') {
        alert('Paraview javascript was not fetched correctly from server.');
        return;
    }

    paraview = new Paraview("/PWService");
    paraview.errorListener = {
        manageError: function(error) {
            midas.createNotice('A ParaViewWeb error occurred; check the console for information', 4000, 'error');
            console.log(error);
            return true;
        }
    };
    paraview.createSession("midas", "slice viz", "default");

    input = paraview.OpenDataFile({filename: file});
    paraview.Show();
    paraview.Hide();

    var imageData = paraview.GetDataInformation();
    bounds = imageData.Bounds;
    minVal = imageData.PointData.Arrays[0].Ranges[0][0];
    maxVal = imageData.PointData.Arrays[0].Ranges[0][1];

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
    activeView.setViewSize(container.width(), container.height());
    activeView.setCenterAxesVisibility(false);
    activeView.setOrientationAxesVisibility(false);
    activeView.setCameraParallelProjection(true);
    activeView.setCameraPosition([midI, midJ, bounds[5] + 1]);
    activeView.setCameraFocalPoint([midI, midJ, midK]);
    activeView.setCenterOfRotation(activeView.getCameraFocalPoint());

    var lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    lookupTable.setRGBPoints([minVal, 0.0, 0.0, 0.0, maxVal, 1.0, 1.0, 1.0]); //initial transfer function def
    lookupTable.setColorSpace(0); // 0 corresponds to RGB

    paraview.SetDisplayProperties({
        proxy: sliceFilter,
        view: activeView,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage',
        LookupTable: lookupTable
    });
    paraview.Render();
    activeView.setCameraParallelScale(Math.max(midI, midJ));

    switchRenderer(true); // render in the div
    $('img.visuLoading').hide();
    container.show();
    setupSliders();

    updateSliceInfo(midK);
    updateWindowInfo([minVal, maxVal]);
    disableMouseInteraction();
}

/**
 * Helper function to setup the slice and window/level sliders
 */
function setupSliders () {
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
    $('#windowLevelSlider').slider({
        range: true,
        min: minVal,
        max: maxVal,
        values: [minVal, maxVal],
        change: function(event, ui) {
            changeWindow(ui.values);
        },
        slide: function(event, ui) {
            updateWindowInfo(ui.values);
        }
    });
}

/**
 * Unregisters all mouse event handlers on the renderer
 */
function disableMouseInteraction () {
    var el = renderers.current.view;
    el.onclick = null;
    el.onmousemove = null;
    el.onmousedown = null;
    el.onmouseup = null;
    el.oncontextmenu = null;
    el.ontouchstart = null;
    el.ontouchmove = null;
}

/**
 * Update the client GUI values for window and level, without
 * actually changing them in PVWeb
 */
function updateWindowInfo(values) {
    $('#windowLevelInfo').html('Window: '+values[0]+' - '+values[1]);
}

/** Make the actual request to PVWeb to set the window */
function changeWindow(values) {
    var lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    imageWindow = values;
    lookupTable.setRGBPoints([values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0]);

    paraview.Render();
}

function changeSlice (slice) {
    if(slice < bounds[4] || slice > bounds[5] - 1) {
        console.log('Invalid slice number: '+slice);
        return;
    }

    var prevSlice = paraview.GetActiveSource();
    activeView.setCameraFocalPoint([midI, midJ, slice]);
    var sliceFilter = paraview.ExtractSubset({
      Input: input,
      SampleRateI: 1,
      SampleRateJ: 1,
      SampleRateK: 1,
      VOI: [bounds[0], bounds[1], bounds[2], bounds[3], slice, slice + 1]
    });
    var lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    // set current window state on this slice
    lookupTable.setRGBPoints([imageWindow[0], 0.0, 0.0, 0.0, imageWindow[1], 1.0, 1.0, 1.0]);
    lookupTable.setColorSpace(0); // 0 corresponds to RGB

    paraview.SetDisplayProperties({
        proxy: sliceFilter,
        view: activeView,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage',
        LookupTable: lookupTable
    });
    paraview.Show({proxy: sliceFilter}); //show the next slice
    paraview.Hide({proxy: prevSlice}); //hide the previous slice
}

/**
 * Update the value of the current slice, without rendering the slice.
 */
function updateSliceInfo(slice) {
    $('#sliceInfo').html('Slice: '+(slice+1)+' of '+bounds[5]);
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
    renderers.current.start();
    
}

$(window).load(function () {
    json = jQuery.parseJSON($('div.jsonContent').html());
    start();
});

$(window).unload(function () {
    paraview.disconnect();
});

