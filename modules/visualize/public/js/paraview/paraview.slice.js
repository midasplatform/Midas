var paraview;
var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.renderers = {};
midas.visualize.meshes = [];
midas.visualize.meshSlices = [];

midas.visualize.start = function () {
    // Create a paraview proxy
    var file = json.visualize.url;
    var container = $('#renderercontainer');

    if(typeof Paraview != 'function') {
        alert('Paraview javascript was not fetched correctly from server.');
        return;
    }

    $('#loadingStatus').html('Creating ParaView session on the server and loading plugins...');
    paraview = new Paraview("/PWService");
    paraview.errorListener = {
        manageError: function(error) {
            midas.createNotice('A ParaViewWeb error occurred; check the console for information', 4000, 'error');
            console.log(error);
            return true;
        }
    };

    paraview.createSession("midas", "slice view", "default");
    paraview.loadPlugins();

    $('#loadingStatus').html('Reading image data from files...');
    paraview.plugins.midascommon.AsyncOpenData(midas.visualize._dataOpened, {
        filename: json.visualize.url,
        otherMeshes: json.visualize.meshes
    });
};

midas.visualize._dataOpened = function (retVal) {
    midas.visualize.input = retVal.input;
    midas.visualize.bounds = retVal.imageData.Bounds;
    midas.visualize.meshes = retVal.meshes;

    midas.visualize.maxDim = Math.max(midas.visualize.bounds[1] - midas.visualize.bounds[0],
                                      midas.visualize.bounds[3] - midas.visualize.bounds[2],
                                      midas.visualize.bounds[5] - midas.visualize.bounds[4]);
    midas.visualize.minVal = retVal.imageData.PointData.Arrays[0].Ranges[0][0];
    midas.visualize.maxVal = retVal.imageData.PointData.Arrays[0].Ranges[0][1];
    midas.visualize.imageWindow = [midas.visualize.minVal, midas.visualize.maxVal];

    midas.visualize.midI = (midas.visualize.bounds[0] + midas.visualize.bounds[1]) / 2.0;
    midas.visualize.midJ = (midas.visualize.bounds[2] + midas.visualize.bounds[3]) / 2.0;
    midas.visualize.midK = Math.floor((midas.visualize.bounds[4] + midas.visualize.bounds[5]) / 2.0);

    if(midas.visualize.bounds.length != 6) {
        console.log('Invalid image bounds:');
        console.log(midas.visualize.bounds);
        return;
    }

    midas.visualize.defaultColorMap = [
       midas.visualize.minVal, 0.0, 0.0, 0.0,
       midas.visualize.maxVal, 1.0, 1.0, 1.0];
    midas.visualize.colorMap = midas.visualize.defaultColorMap;
    midas.visualize.currentSlice = midas.visualize.midK;
    midas.visualize.sliceMode = 'XY Plane';

    var params = {
        cameraFocalPoint: [midas.visualize.midI, midas.visualize.midJ, midas.visualize.midK],
        cameraPosition: [midas.visualize.midI, midas.visualize.midJ, midas.visualize.bounds[4] - 10],
        colorMap: midas.visualize.defaultColorMap,
        colorArrayName: json.visualize.colorArrayName,
        sliceVal: midas.visualize.currentSlice,
        sliceMode: midas.visualize.sliceMode,
        parallelScale: Math.max(midas.visualize.bounds[1] - midas.visualize.bounds[0],
                                midas.visualize.bounds[3] - midas.visualize.bounds[2]) / 2.0,
        cameraUp: [0.0, -1.0, 0.0],
        meshes: midas.visualize.meshes,
        lineWidth: midas.visualize.maxDim / 100.0
    };
    $('#loadingStatus').html('Initializing view state and renderer...');
    paraview.plugins.midasslice.AsyncInitViewState(midas.visualize.initCallback, params);
};

midas.visualize.initCallback = function (retVal) {
    midas.visualize.lookupTable = retVal.lookupTable;
    midas.visualize.activeView = retVal.activeView;
    midas.visualize.meshSlices = retVal.meshSlices;

    midas.visualize.switchRenderer(true); // render in the div
    $('img.visuLoading').hide();
    $('#loadingStatus').html('').hide();
    $('#renderercontainer').show();

    midas.visualize.setupSliders();
    midas.visualize.updateSliceInfo(midas.visualize.midK);
    midas.visualize.updateWindowInfo([midas.visualize.minVal, midas.visualize.maxVal]);
    midas.visualize.populateInfo();
    midas.visualize.disableMouseInteraction();

    if(typeof midas.visualize.postInitCallback == 'function') {
        midas.visualize.postInitCallback();
    }

    midas.visualize.renderers.current.updateServerSizeIfNeeded();
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
 * Display information about the volume
 */
midas.visualize.populateInfo = function () {
    $('#boundsXInfo').html(midas.visualize.bounds[0]+' .. '+midas.visualize.bounds[1]);
    $('#boundsYInfo').html(midas.visualize.bounds[2]+' .. '+midas.visualize.bounds[3]);
    $('#boundsZInfo').html(midas.visualize.bounds[4]+' .. '+midas.visualize.bounds[5]);
    $('#scalarRangeInfo').html(midas.visualize.minVal+' .. '+midas.visualize.maxVal);
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
    paraview.plugins.midasslice.AsyncChangeWindow(function (retVal) {
        midas.visualize.lookupTable = retVal.lookupTable;
        paraview.sendEvent('Render', ''); //force a view refresh
    }, [values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0], json.visualize.colorArrayName);
    midas.visualize.imageWindow = values;
};

/** Change the slice and run appropriate slice filter on any meshes in the scene */
midas.visualize.changeSlice = function (slice) {
    slice = parseInt(slice);
    midas.visualize.currentSlice = slice;
    
    var params = {
        volume: midas.visualize.input,
        slice: slice,
        sliceMode: midas.visualize.sliceMode,
        meshes: midas.visualize.meshes,
        lineWidth: midas.visualize.maxDim / 100.0
    };

    paraview.plugins.midasslice.AsyncChangeSlice(function(retVal) {
        midas.visualize.meshSlices = retVal.meshSlices;
        if(typeof midas.visualize.changeSliceCallback == 'function') {
            midas.visualize.changeSliceCallback(slice);
        }
        paraview.sendEvent('Render', ''); //force a view refresh
    }, params);
};

/**
 * Update the value of the current slice, without rendering the slice.
 */
midas.visualize.updateSliceInfo = function (slice) {
    var max;
    if(midas.visualize.sliceMode == 'XY Plane') {
        max = midas.visualize.bounds[5];
    }
    else if(midas.visualize.sliceMode == 'XZ Plane') {
        max = midas.visualize.bounds[3];
    }
    else { // YZ Plane
        max = midas.visualize.bounds[1];
    }
    $('#sliceInfo').html('Slice: ' + slice + ' of '+ max);
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
    var bounds = midas.visualize.bounds; //alias the variable for shorthand
    el.unbind('click').click(function (e) {
        var x, y, z;
        if(midas.visualize.sliceMode == 'XY Plane') {
            var longLength = Math.max(bounds[1] - bounds[0], bounds[3] - bounds[2]);
            var arWidth = (bounds[1] - bounds[0]) / longLength;
            var arHeight = (bounds[3] - bounds[2]) / longLength;

            x = (bounds[1] - bounds[0]) * ((e.offsetX - ($(this).width() * (1-arWidth) / 2.0)) / ($(this).width() * arWidth));
            x -= bounds[0];
            
            y = (bounds[3] - bounds[2]) * ((e.offsetY - ($(this).height() * (1-arHeight) / 2.0)) / ($(this).height() * arHeight));
            y -= bounds[2];
            
            z = midas.visualize.currentSlice;
        }
        else if(midas.visualize.sliceMode == 'XZ Plane') {
            var longLength = Math.max(bounds[1] - bounds[0], bounds[5] - bounds[4]);
            var arWidth = (bounds[1] - bounds[0]) / longLength;
            var arHeight = (bounds[5] - bounds[4]) / longLength;

            x = (bounds[1] - bounds[0]) * ((e.offsetX - ($(this).width() * (1-arWidth) / 2.0)) / ($(this).width() * arWidth));
            x = bounds[1] - x;
            x -= midas.visualize.bounds[0];
            
            y = midas.visualize.currentSlice;
            
            z = (bounds[5] - bounds[4]) * ((e.offsetY - ($(this).height() * (1-arHeight) / 2.0)) / ($(this).height() * arHeight));
            z = bounds[5] - z;
            z -= bounds[4];
        }
        else if(midas.visualize.sliceMode == 'YZ Plane') {
            var longLength = Math.max(bounds[1] - bounds[0], bounds[5] - bounds[4]);
            var arWidth = (bounds[1] - bounds[0]) / longLength;
            var arHeight = (bounds[5] - bounds[4]) / longLength;

            x = midas.visualize.currentSlice;
            
            y = (bounds[3] - bounds[2]) * ((e.offsetX - ($(this).width() * (1-arWidth) / 2.0)) / ($(this).width() * arWidth));
            y -= bounds[2];
            
            z = (bounds[5] - bounds[4]) * ((e.offsetY - ($(this).height() * (1-arHeight) / 2.0)) / ($(this).height() * arHeight));
            z = bounds[5] - z;
            z -= bounds[4];
        }

        var html = 'You have selected the point:<p><b>('
                 +x.toFixed(1)+', '+y.toFixed(1)+', '+z.toFixed(1)+')</b></p>'
                 +'Click OK to proceed or Cancel to re-select a point';
        html += '<br/><br/><div style="float: right;"><button id="pointSelectOk">OK</button>';
        html += '<button style="margin-left: 15px" id="pointSelectCancel">Cancel</button></div>';
        midas.showDialogWithContent('Confirm Point Selection', html, false, {modal: false});

        $('#pointSelectOk').unbind('click').click(function () {
            if(typeof midas.visualize.handlePointSelect == 'function') {
                midas.visualize.handlePointSelect([x, y, z]);
            }
            else {
                midas.createNotice('No point selection handler function has been loaded', 4000, 'error');
            }
        });
        $('#pointSelectCancel').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });

        var params = {
            point: [x, y, z],
            color: [1.0, 0.0, 0.0],
            radius: midas.visualize.maxDim / 100.0, //make the sphere some small fraction of the image size
            objectToDelete: midas.visualize.glyph ? midas.visualize.glyph : false,
            input: midas.visualize.input
        };
        paraview.plugins.midasslice.AsyncShowSphere(function (retVal) {
            midas.visualize.glyph = retVal.glyph;
            paraview.sendEvent('Render', ''); //force a view refresh
        }, params);
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

/**
 * Change the slice mode. Valid values are 'XY Plane', 'XZ Plane', 'YZ Plane'
 */
midas.visualize.setSliceMode = function (sliceMode) {
    if(midas.visualize.sliceMode == sliceMode) {
        return; //nothing to do, already in this mode
    }

    var slice, parallelScale, cameraPosition, min, max;
    if(sliceMode == 'XY Plane') {
        slice = Math.floor(midas.visualize.midK);
        parallelScale = Math.max(midas.visualize.bounds[1] - midas.visualize.bounds[0],
                                 midas.visualize.bounds[3] - midas.visualize.bounds[2]) / 2.0;
        cameraPosition = [midas.visualize.midI, midas.visualize.midJ, midas.visualize.bounds[4] - 10];
        cameraUp = [0.0, -1.0, 0.0];
        min = midas.visualize.bounds[4];
        max = midas.visualize.bounds[5];
    }
    else if(sliceMode == 'XZ Plane') {
        slice = Math.floor(midas.visualize.midJ);
        parallelScale = Math.max(midas.visualize.bounds[1] - midas.visualize.bounds[0],
                                 midas.visualize.bounds[5] - midas.visualize.bounds[4]) / 2.0;
        cameraPosition = [midas.visualize.midI, midas.visualize.bounds[3] + 10, midas.visualize.midK];
        cameraUp = [0.0, 0.0, 1.0];
        min = midas.visualize.bounds[2];
        max = midas.visualize.bounds[3];
    }
    else { // YZ Plane
        slice = Math.floor(midas.visualize.midI);
        parallelScale = Math.max(midas.visualize.bounds[3] - midas.visualize.bounds[2],
                                 midas.visualize.bounds[5] - midas.visualize.bounds[4]) / 2.0;
        cameraPosition = [midas.visualize.bounds[1] + 10, midas.visualize.midJ, midas.visualize.midK];
        cameraUp = [0.0, 0.0, 1.0];
        min = midas.visualize.bounds[0];
        max = midas.visualize.bounds[1];
    }
    midas.visualize.currentSlice = slice;
    midas.visualize.sliceMode = sliceMode;
    midas.visualize.updateSliceInfo(slice);
    $('#sliceSlider').slider('destroy').slider({
        min: min,
        max: max,
        value: slice,
        change: function(event, ui) {
            midas.visualize.changeSlice(ui.value);
        },
        slide: function(event, ui) {
            midas.visualize.updateSliceInfo(ui.value);
        }
    });
    
    var params = {
        volume: midas.visualize.input,
        slice: slice,
        sliceMode: sliceMode,
        meshes: midas.visualize.meshes,
        lineWidth: midas.visualize.maxDim / 100.0,
        parallelScale: parallelScale,
        cameraPosition: cameraPosition,
        cameraUp: cameraUp
    };
    paraview.plugins.midasslice.AsyncChangeSliceMode(function (retVal) {
        midas.visualize.meshSlices = retVal.meshSlices;
        paraview.sendEvent('Render', ''); //force a view refresh
    }, params);
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
});

$(window).unload(function () {
    paraview.disconnect();
});

