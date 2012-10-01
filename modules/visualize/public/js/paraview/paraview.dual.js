var paraview;
var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.left = {};
midas.visualize.right = {};

midas.visualize.start = function () {
    // Create a paraview proxy
    var file = json.visualize.url;

    if(typeof Paraview != 'function') {
        alert('Paraview javascript was not fetched correctly from server.');
        return;
    }

    $('#leftLoadingStatus').html('Creating ParaView session on the server and loading plugins...');
    paraview = {};
    paraview.left = new Paraview('/PWService');
    paraview.left.errorListener = {
        manageError: function(error) {
            midas.createNotice('A ParaViewWeb error occurred; check the console for information', 4000, 'error');
            console.log(error);
            return true;
        }
    };
    $('#rightLoadingStatus').html('Creating ParaView session on the server and loading plugins...');
    paraview.right = new Paraview('/PWService');
    paraview.right.errorListener = {
        manageError: function(error) {
            midas.createNotice('A ParaViewWeb error occurred; check the console for information', 4000, 'error');
            console.log(error);
            return true;
        }
    };

    paraview.left.createSession("midas", "dual view left", "default");
    paraview.left.loadPlugins();
    paraview.right.createSession("midas", "dual view right", "default");
    paraview.right.loadPlugins();

    $('#leftLoadingStatus').html('Reading image data from files...');
    paraview.left.plugins.midascommon.AsyncOpenData(function (retVal) {
        midas.visualize._dataOpened('left', retVal)
    }, {
        filename: json.visualize.urls.left,
        otherMeshes: []
    });
    $('#rightLoadingStatus').html('Reading image data from files...');
    paraview.right.plugins.midascommon.AsyncOpenData(function (retVal) {
        midas.visualize._dataOpened('right', retVal)
    }, {
        filename: json.visualize.urls.right,
        otherMeshes: []
    });
};

midas.visualize._dataOpened = function (side, retVal) {
    midas.visualize[side].input = retVal.input;
    midas.visualize[side].bounds = retVal.imageData.Bounds;

    midas.visualize[side].maxDim = Math.max(midas.visualize[side].bounds[1] - midas.visualize[side].bounds[0],
                                           midas.visualize[side].bounds[3] - midas.visualize[side].bounds[2],
                                           midas.visualize[side].bounds[5] - midas.visualize[side].bounds[4]);
    midas.visualize[side].minVal = retVal.imageData.PointData.Arrays[0].Ranges[0][0];
    midas.visualize[side].maxVal = retVal.imageData.PointData.Arrays[0].Ranges[0][1];
    midas.visualize[side].imageWindow = [midas.visualize[side].minVal, midas.visualize[side].maxVal];

    midas.visualize[side].midI = (midas.visualize[side].bounds[0] + midas.visualize[side].bounds[1]) / 2.0;
    midas.visualize[side].midJ = (midas.visualize[side].bounds[2] + midas.visualize[side].bounds[3]) / 2.0;
    midas.visualize[side].midK = Math.floor((midas.visualize[side].bounds[4] + midas.visualize[side].bounds[5]) / 2.0);

    if(midas.visualize[side].bounds.length != 6) {
        console.log('Invalid image bounds ('+side+' image):');
        console.log(midas.visualize[side].bounds);
        return;
    }
    
    midas.visualize[side].defaultColorMap = [
       midas.visualize[side].minVal, 0.0, 0.0, 0.0,
       midas.visualize[side].maxVal, 1.0, 1.0, 1.0];
    midas.visualize[side].colorMap = midas.visualize[side].defaultColorMap;
    midas.visualize.currentSlice = midas.visualize[side].midK;
    midas.visualize.sliceMode = 'XY Plane';

    var params = {
        cameraFocalPoint: [midas.visualize[side].midI, midas.visualize[side].midJ, midas.visualize[side].midK],
        cameraPosition: [midas.visualize[side].midI, midas.visualize[side].midJ, midas.visualize[side].bounds[4] - 10],
        colorMap: midas.visualize[side].defaultColorMap,
        colorArrayName: json.visualize.colorArrayNames[side],
        sliceVal: midas.visualize.currentSlice,
        sliceMode: midas.visualize.sliceMode,
        parallelScale: Math.max(midas.visualize[side].bounds[1] - midas.visualize[side].bounds[0],
                                midas.visualize[side].bounds[3] - midas.visualize[side].bounds[2]) / 2.0,
        cameraUp: [0.0, -1.0, 0.0]
    };
    $('#'+side+'LoadingStatus').html('Initializing view state and renderer...');

    paraview[side].plugins.midasdual.AsyncInitViewState(function (retVal) {
        midas.visualize.initCallback(side, retVal)
    }, params);
};

midas.visualize.initCallback = function (side, retVal) {
    midas.visualize[side].lookupTable = retVal.lookupTable;
    midas.visualize[side].activeView = retVal.activeView;

    midas.visualize.switchRenderer(side); // render in the div
    $('img.'+side+'Loading').hide();
    $('#'+side+'LoadingStatus').html('').hide();
    $('#'+side+'Renderer').show();

    //midas.visualize.setupSliders();
    //midas.visualize.updateSliceInfo(midas.visualize[side].midK);
    //midas.visualize.updateWindowInfo([midas.visualize[side].minVal, midas.visualize[side].maxVal]);
    midas.visualize.disableMouseInteraction(side);

    if(typeof midas.visualize.postInitCallback == 'function') {
        midas.visualize.postInitCallback(side);
    }

    midas.visualize[side].renderer.updateServerSizeIfNeeded();
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
midas.visualize.disableMouseInteraction = function (side) {
    var el = midas.visualize[side].renderer.view;
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
midas.visualize.switchRenderer = function (side) {
    if(midas.visualize[side].renderer == undefined) {
        midas.visualize[side].renderer = new JavaScriptRenderer(side+'JsRenderer', '/PWService');
        midas.visualize[side].renderer.init(paraview[side].sessionId, midas.visualize[side].activeView.__selfid__);
    }

    midas.visualize[side].renderer.bindToElementId(side+'Renderer');
    var el = $('#'+side+'Renderer');
    midas.visualize[side].renderer.setSize(el.width(), el.height());
    midas.visualize[side].renderer.start();
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
            radius: midas.visualize.maxDim / 70.0, //make the sphere some small fraction of the image size
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
});

$(window).unload(function () {
    paraview.left.disconnect();
    paraview.right.disconnect();
});

