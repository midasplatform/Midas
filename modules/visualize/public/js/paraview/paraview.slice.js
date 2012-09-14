var paraview;
var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.renderers = {};
midas.visualize.meshes = [];

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
    midas.visualize.midK = Math.floor((midas.visualize.bounds[4] + midas.visualize.bounds[5]) / 2.0);

    midas.visualize.currentSlice = midas.visualize.midK;

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
//    midas.visualize.activeView.setCameraViewUp([0, -1, 0]);
    midas.visualize.activeView.setCenterOfRotation(midas.visualize.activeView.getCameraFocalPoint());
    midas.visualize.activeView.setBackground([0.0, 0.0, 0.0]);
    midas.visualize.activeView.setBackground2([0.0, 0.0, 0.0]); //solid black background
    midas.visualize.activeView.setCameraParallelScale(
      Math.max(midas.visualize.midI, midas.visualize.midJ));

    var lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    lookupTable.setRGBPoints([midas.visualize.minVal,
                              0.0, 0.0, 0.0,
                              midas.visualize.maxVal,
                              1.0, 1.0, 1.0]); //initial transfer function
    lookupTable.setColorSpace(0); // 0 corresponds to RGB

    paraview.SetDisplayProperties({
        view: midas.visualize.activeView,
        Representation: 'Slice',
        ColorArrayName: 'MetaImage',
        Slice: midas.visualize.midK,
        SliceMode: 'XY Plane',
        LookupTable: lookupTable
    });

    // Load other meshes
    $.each(json.visualize.meshes, function(k, mesh) {
        var src = paraview.OpenDataFile({filename: mesh.path});
        paraview.Show({proxy: src});
        paraview.SetDisplayProperties({
            proxy: src,
            view: midas.visualize.activeView,
            Representation: 'Surface'
        });

        midas.visualize.meshes.push({
            path: mesh.path,
            itemId: mesh.itemId,
            source: src
        });
    });
    paraview.SetActiveSource([midas.visualize.input]);

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
        max: midas.visualize.bounds[5],
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
    if(slice < midas.visualize.bounds[4] || slice > midas.visualize.bounds[5]) {
        midas.createNotice('Invalid slice number: ' + slice, 3000, 'error');
        return;
    }

    paraview.SetDisplayProperties({
        Slice: slice
    });
    midas.visualize.currentSlice = slice;

    if(typeof midas.visualize.changeSliceCallback == 'function') {
        midas.visualize.changeSliceCallback(slice);
    }
};

/**
 * Update the value of the current slice, without rendering the slice.
 */
midas.visualize.updateSliceInfo = function (slice) {
    $('#sliceInfo').html('Slice: ' + slice + ' of '+ midas.visualize.bounds[5]);
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
    var el = $('#renderercontainer');
    midas.visualize.renderers.current.setSize(el.width(), el.height());
    midas.visualize.renderers.current.start();  
};

/**
 * Set the mode to point selection within the image.
 */
midas.visualize.pointSelectMode = function () {
    midas.createNotice('Click on the image to select a point', 3500);

    // Bind click action on the render window
    var el = $(midas.visualize.renderers.current.view);
    el.unbind('click').click(function (e) {
        var x = (midas.visualize.bounds[1] - midas.visualize.bounds[0]) * (e.offsetX / $(this).width());
        x -= midas.visualize.bounds[0];
        var y = (midas.visualize.bounds[3] - midas.visualize.bounds[2]) * (e.offsetY / $(this).height());
        y -= midas.visualize.bounds[2];

        var html = 'You have selected the point:<p><b>('
                 +x.toFixed(1)+', '+y.toFixed(1)+', '+midas.visualize.currentSlice.toFixed(1)+')</b></p>'
                 +'Click OK to proceed or Cancel to re-select a point';
        html += '<br/><br/><div style="float: right;"><button id="pointSelectOk">OK</button>';
        html += '<button style="margin-left: 15px" id="pointSelectCancel">Cancel</button></div>';
        midas.showDialogWithContent('Confirm Point Selection', html, false);

        $('#pointSelectOk').unbind('click').click(function () {
            if(typeof midas.visualize.handlePointSelect == 'function') {
                midas.visualize.handlePointSelect([x, y, midas.visualize.currentSlice]);
            }
            else {
                midas.createNotice('No point selection handler function has been loaded', 4000, 'error');
            }
        });
        $('#pointSelectCancel').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });      
    });
};

/**
 * Set an action as active
 * @param button The button to display as active (all others will become inactive)
 * @param callback The function to call when this button is activated
 */
midas.visualize.setActiveAction = function (button, callback) {
    $('.actionActive').addClass('actionInactive').removeClass('actionActive');
    button.removeClass('actionInactive').addClass('actionActive');
    callback();
};

/**
 * Enable point selection action
 */
midas.visualize._enablePointSelect = function () {
    var button = $('#actionButtonTemplate').clone();
    button.removeAttr('id');
    button.addClass('pointSelectButton');
    button.appendTo('#rendererOverlay');
    button.qtip({
        content: 'Select a single point in the image'
    });
    button.show();

    button.click(function () {
        midas.visualize.setActiveAction($(this), midas.visualize.pointSelectMode);
    });
};

/**
 * Enable the specified set of operations in the view
 * Options:
 *   -pointSelect: select a single point in the image
 */
midas.visualize.enableActions = function (operations) {
    $.each(operations, function(k, operation) {
        if(operation == 'pointSelect') {
            midas.visualize._enablePointSelect();
        }
        else if(operation != '') {
            alert('Unsupported operation: '+operation);
        }
    });
};

/**
 * Toggle the visibility of any controls overlaid on top of the render container
 */
midas.visualize.toggleControlVisibility = function () {
    if($('#sliceControlContainer').is(':visible')) {
        $('#sliceControlContainer').hide();
        $('#windowLevelControlContainer').hide();
        $('#rendererOverlay').hide();
    }
    else {
        $('#sliceControlContainer').show();
        $('#windowLevelControlContainer').show();
        $('#rendererOverlay').show();
    }
};

$(window).load(function () {
    if(typeof midas.visualize.preInitCallback == 'function') {
        midas.visualize.preInitCallback();
    }

    json = jQuery.parseJSON($('div.jsonContent').html());
    midas.visualize.start();
    midas.visualize.enableActions(json.visualize.operations.split(';'));
    $(document).unbind('keypress').keydown(function (event) {
        if(event.which == 67) { // the 'c' key
            midas.visualize.toggleControlVisibility();
            event.preventDefault();
        }
    });

    if(typeof midas.visualize.postInitCallback == 'function') {
        midas.visualize.postInitCallback();
    }
});

$(window).unload(function () {
    paraview.disconnect();
});

