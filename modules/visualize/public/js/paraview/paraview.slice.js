var paraview;
var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.renderers = {};

midas.visualize.start = function () {
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

    midas.visualize.input = paraview.OpenDataFile({filename: file});
    paraview.Show();

    var imageData = paraview.GetDataInformation();
    midas.visualize.bounds = imageData.Bounds;
    midas.visualize.minVal = imageData.PointData.Arrays[0].Ranges[0][0];
    midas.visualize.maxVal = imageData.PointData.Arrays[0].Ranges[0][1];
    midas.visualize.imageWindow = [midas.visualize.minVal, midas.visualize.maxVal];

    if(midas.visualize.bounds.length != 6) {
        console.log('Invalid image bounds:');
        console.log(midas.visualize.bounds);
        return;
    }

    midas.visualize.midI = (midas.visualize.bounds[0] + midas.visualize.bounds[1]) / 2.0;
    midas.visualize.midJ = (midas.visualize.bounds[2] + midas.visualize.bounds[3]) / 2.0;
    midas.visualize.midK = Math.ceil((midas.visualize.bounds[4] + midas.visualize.bounds[5]) / 2.0) - 1;

    midas.visualize.activeView = paraview.CreateIfNeededRenderView();
    midas.visualize.activeView.setViewSize(container.width(), container.height());
    midas.visualize.activeView.setCenterAxesVisibility(false);
    midas.visualize.activeView.setOrientationAxesVisibility(false);
    midas.visualize.activeView.setCameraParallelProjection(true);
    midas.visualize.activeView.setCameraPosition([midas.visualize.midI,
                                                  midas.visualize.midJ,
                                                  midas.visualize.bounds[5] + 1]);
    midas.visualize.activeView.setCameraFocalPoint([midas.visualize.midI,
                                                    midas.visualize.midJ,
                                                    midas.visualize.midK]);
    midas.visualize.activeView.setCenterOfRotation(midas.visualize.activeView.getCameraFocalPoint());
    midas.visualize.activeView.setBackground([0.0, 0.0, 0.0]);
    midas.visualize.activeView.setBackground2([0.0, 0.0, 0.0]); //solid black background

    var lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    lookupTable.setRGBPoints([midas.visualize.minVal,
                              0.0, 0.0, 0.0,
                              midas.visualize.maxVal,
                              1.0, 1.0, 1.0]); //initial transfer function def
    lookupTable.setColorSpace(0); // 0 corresponds to RGB

    paraview.SetDisplayProperties({
        view: midas.visualize.activeView,
        Representation: 'Slice',
        ColorArrayName: 'MetaImage',
        Slice: midas.visualize.midK,
        LookupTable: lookupTable
    });
    paraview.Render();
    midas.visualize.activeView.setCameraParallelScale(
      Math.max(midas.visualize.midI, midas.visualize.midJ));

    midas.visualize.switchRenderer(true); // render in the div
    $('img.visuLoading').hide();
    container.show();
    midas.visualize.setupSliders();

    midas.visualize.updateSliceInfo(midas.visualize.midK);
    midas.visualize.updateWindowInfo([midas.visualize.minVal, midas.visualize.maxVal]);
    midas.visualize.disableMouseInteraction();
};

/**
 * Helper function to setup the slice and window/level sliders
 */
midas.visualize.setupSliders = function () {
    $('#sliceSlider').slider({
        min: midas.visualize.bounds[4],
        max: midas.visualize.bounds[5] - 1,
        value: midas.visualize.midK,
        change: function(event, ui) {
            midas.visualize.changeSlice(ui.value);
        },
        slide: function(event, ui) {
            midas.visualize.updateSliceInfo(ui.value);
        }
    });
    $('#windowLevelSlider').slider({
        range: true,
        min: midas.visualize.minVal,
        max: midas.visualize.maxVal,
        values: [midas.visualize.minVal, midas.visualize.maxVal],
        change: function(event, ui) {
            midas.visualize.changeWindow(ui.values);
        },
        slide: function(event, ui) {
            midas.visualize.updateWindowInfo(ui.values);
        }
    });
};

/**
 * Unregisters all mouse event handlers on the renderer
 */
midas.visualize.disableMouseInteraction = function () {
    var el = midas.visualize.renderers.current.view;
    el.onclick = null;
    el.onmousemove = null;
    el.onmousedown = null;
    el.onmouseup = null;
    el.oncontextmenu = null;
    el.ontouchstart = null;
    el.ontouchmove = null;
};

/**
 * Update the client GUI values for window and level, without
 * actually changing them in PVWeb
 */
midas.visualize.updateWindowInfo = function (values) {
    $('#windowLevelInfo').html('Window: '+values[0]+' - '+values[1]);
};

/** Make the actual request to PVWeb to set the window */
midas.visualize.changeWindow = function (values) {
    var lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    midas.visualize.imageWindow = values;
    lookupTable.setRGBPoints([values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0]);
};

midas.visualize.changeSlice = function (slice) {
    if(slice < midas.visualize.bounds[4] || slice > midas.visualize.bounds[5] - 1) {
        console.log('Invalid slice number: '+slice);
        return;
    }

    paraview.SetDisplayProperties({
        Slice: slice
    });
};

/**
 * Update the value of the current slice, without rendering the slice.
 */
midas.visualize.updateSliceInfo = function (slice) {
    $('#sliceInfo').html('Slice: ' + (slice+1) + ' of '+ midas.visualize.bounds[5]);
};

/**
 * Initialize or re-initialize the renderer within the DOM
 */
midas.visualize.switchRenderer = function (first) {
    if(midas.visualize.renderers.js == undefined) {
        midas.visualize.renderers.js = new JavaScriptRenderer("jsRenderer", "/PWService");
        midas.visualize.renderers.js.init(paraview.sessionId, midas.visualize.activeView.__selfid__);
        $('img.toolButton').show();
    }

    if(!first) {
        midas.visualize.renderers.current.unbindToElementId('renderercontainer');
    }
    midas.visualize.renderers.current = midas.visualize.renderers.js;
    midas.visualize.renderers.current.bindToElementId('renderercontainer');
    midas.visualize.renderers.current.start();  
};

$(window).load(function () {
    json = jQuery.parseJSON($('div.jsonContent').html());
    midas.visualize.start();
});

$(window).unload(function () {
    paraview.disconnect();
});

