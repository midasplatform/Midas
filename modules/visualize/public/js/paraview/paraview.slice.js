// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var paraview;
var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.renderers = {};
midas.visualize.meshes = [];
midas.visualize.meshSlices = [];

midas.visualize.start = function () {
    'use strict';
    // Create a paraview proxy
    var file = json.visualize.url;
    var container = $('#renderercontainer');

    if (typeof Paraview != 'function') {
        alert('Paraview javascript was not fetched correctly from server.');
        return;
    }

    $('#loadingStatus').html('Creating ParaView session on the server and loading plugins...');
    paraview = new Paraview("/PWService");
    paraview.errorListener = {
        manageError: function (error) {
            if (error) {
                midas.createNotice('A ParaViewWeb error occurred; check the console for information', 4000, 'error');
                console.log(error);
                return false;
            }
        }
    };

    paraview.createSessionAsync("midas", "slice view", "default", function () {
        $('#loadingStatus').html('Reading image data from files...');
        paraview.callPluginMethod('midascommon', 'OpenData', {
            filename: json.visualize.url,
            otherMeshes: json.visualize.meshes
        }, midas.visualize._dataOpened);
    });
};

midas.visualize._dataOpened = function (view, retVal) {
    'use strict';
    midas.visualize.input = retVal.input;
    midas.visualize.bounds = retVal.imageData.Bounds;
    midas.visualize.extent = retVal.imageData.Extent;
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

    if (midas.visualize.bounds.length != 6) {
        console.log('Invalid image bounds:');
        console.log(midas.visualize.bounds);
        return;
    }

    midas.visualize.defaultColorMap = [
        midas.visualize.minVal, 0.0, 0.0, 0.0,
        midas.visualize.maxVal, 1.0, 1.0, 1.0
    ];
    midas.visualize.colorMap = midas.visualize.defaultColorMap;
    midas.visualize.currentSlice = midas.visualize.midK;
    midas.visualize.sliceMode = 'XY Plane';
    midas.visualize.cameraViewUp = [0.0, -1.0, 0.0];
    midas.visualize.cameraFocalPoint = [midas.visualize.midI, midas.visualize.midJ, midas.visualize.midK];
    midas.visualize.cameraPosition = [midas.visualize.midI, midas.visualize.midJ, midas.visualize.bounds[4] - 10];
    midas.visualize.cameraParallelScale =
        Math.max(midas.visualize.bounds[1] - midas.visualize.bounds[0],
            midas.visualize.bounds[3] - midas.visualize.bounds[2]) / 2.0;

    var rw = $('#renderercontainer');
    var params = {
        viewSize: [rw.width(), rw.height()],
        cameraFocalPoint: midas.visualize.cameraFocalPoint,
        cameraPosition: midas.visualize.cameraPosition,
        colorMap: midas.visualize.defaultColorMap,
        colorArrayName: json.visualize.colorArrayName,
        sliceVal: midas.visualize.currentSlice,
        sliceMode: midas.visualize.sliceMode,
        parallelScale: midas.visualize.cameraParallelScale,
        cameraUp: midas.visualize.cameraViewUp,
        meshes: midas.visualize.meshes,
        lineWidth: midas.visualize.maxDim / 100.0
    };
    $('#loadingStatus').html('Initializing view state and renderer...');
    paraview.callPluginMethod('midasslice', 'InitViewState', params, midas.visualize.initCallback);
};

midas.visualize.initCallback = function (view, retVal) {
    'use strict';
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

    if (typeof midas.visualize.postInitCallback == 'function') {
        midas.visualize.postInitCallback();
    }

    midas.visualize.renderers.current.updateServerSizeIfNeeded();
};

/**
 * Helper function to setup the slice and window/level sliders
 */
midas.visualize.setupSliders = function () {
    'use strict';
    $('#sliceSlider').slider({
        min: midas.visualize.bounds[4],
        max: midas.visualize.bounds[5],
        value: midas.visualize.midK,
        change: function (event, ui) {
            midas.visualize.changeSlice(ui.value);
        },
        slide: function (event, ui) {
            midas.visualize.updateSliceInfo(ui.value);
        }
    });
    $('#windowLevelSlider').slider({
        range: true,
        min: midas.visualize.minVal,
        max: midas.visualize.maxVal,
        values: [midas.visualize.minVal, midas.visualize.maxVal],
        change: function (event, ui) {
            midas.visualize.changeWindow(ui.values);
        },
        slide: function (event, ui) {
            midas.visualize.updateWindowInfo(ui.values);
        }
    });
};

/**
 * Unregisters all mouse event handlers on the renderer
 */
midas.visualize.disableMouseInteraction = function () {
    'use strict';
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
    'use strict';
    $('#boundsXInfo').html(midas.visualize.bounds[0] + ' .. ' + midas.visualize.bounds[1]);
    $('#boundsYInfo').html(midas.visualize.bounds[2] + ' .. ' + midas.visualize.bounds[3]);
    $('#boundsZInfo').html(midas.visualize.bounds[4] + ' .. ' + midas.visualize.bounds[5]);
    $('#scalarRangeInfo').html(midas.visualize.minVal + ' .. ' + midas.visualize.maxVal);
};

/**
 * Update the client GUI values for window and level, without
 * actually changing them in PVWeb
 */
midas.visualize.updateWindowInfo = function (values) {
    'use strict';
    $('#windowLevelInfo').html('Window: ' + values[0] + ' - ' + values[1]);
};

/** Make the actual request to PVWeb to set the window */
midas.visualize.changeWindow = function (values) {
    'use strict';
    paraview.callPluginMethod('midasslice', 'ChangeWindow', [
            [values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0],
            json.visualize.colorArrayName
        ],
        function (view, retVal) {
            midas.visualize.lookupTable = retVal.lookupTable;
            midas.visualize.forceRefreshView();
        });
    midas.visualize.imageWindow = values;
};

/** Change the slice and run appropriate slice filter on any meshes in the scene */
midas.visualize.changeSlice = function (slice) {
    'use strict';
    slice = parseInt(slice);
    midas.visualize.currentSlice = slice;

    var params = {
        volume: midas.visualize.input,
        slice: slice,
        sliceMode: midas.visualize.sliceMode,
        meshes: midas.visualize.meshes,
        lineWidth: midas.visualize.maxDim / 100.0
    };

    paraview.callPluginMethod('midasslice', 'ChangeSlice', params, function (view, retVal) {
        midas.visualize.meshSlices = retVal.meshSlices;
        if (typeof midas.visualize.changeSliceCallback == 'function') {
            midas.visualize.changeSliceCallback(slice);
        }
        midas.visualize.forceRefreshView();
    });
};

/**
 * Update the value of the current slice, without rendering the slice.
 */
midas.visualize.updateSliceInfo = function (slice) {
    'use strict';
    var max;
    if (midas.visualize.sliceMode == 'XY Plane') {
        max = midas.visualize.bounds[5];
    }
    else if (midas.visualize.sliceMode == 'XZ Plane') {
        max = midas.visualize.bounds[3];
    }
    else { // YZ Plane
        max = midas.visualize.bounds[1];
    }
    $('#sliceInfo').html('Slice: ' + slice + ' of ' + max);
};

/**
 * Initialize or re-initialize the renderer within the DOM
 */
midas.visualize.switchRenderer = function (first) {
    'use strict';
    if (midas.visualize.renderers.js == undefined) {
        midas.visualize.renderers.js = new JavaScriptRenderer("jsRenderer", "/PWService");
        midas.visualize.renderers.js.enableWebSocket(paraview, 'ws://' + json.visualize.hostname + ':' + json.visualize.wsport + '/PWService/Websocket');
        midas.visualize.renderers.js.init(paraview.sessionId, midas.visualize.activeView.__selfid__);
        $('img.toolButton').show();
    }

    if (!first) {
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
    'use strict';
    midas.createNotice('Click on the image to select a point', 3500);

    // Bind click action on the render window
    var el = $(midas.visualize.renderers.current.view);
    el.unbind('click').click(function (e) {
        var x, y, z;
        var pscale = midas.visualize.cameraParallelScale;
        var focus = midas.visualize.cameraFocalPoint;

        if (midas.visualize.sliceMode == 'XY Plane') {
            var top = focus[1] - pscale;
            var bottom = focus[1] + pscale;
            var left = focus[0] - pscale;
            var right = focus[0] + pscale;
            x = (e.offsetX / $(this).width()) * (right - left) + left;
            y = (e.offsetY / $(this).height()) * (bottom - top) + top;
            z = midas.visualize.currentSlice + midas.visualize.bounds[4] - midas.visualize.extent[4];
        }
        else if (midas.visualize.sliceMode == 'XZ Plane') {
            var top = focus[2] + pscale;
            var bottom = focus[2] - pscale;
            var left = focus[0] + pscale;
            var right = focus[0] - pscale;
            x = (e.offsetX / $(this).width()) * (right - left) + left;
            y = midas.visualize.currentSlice + midas.visualize.bounds[2] - midas.visualize.extent[2];
            z = (e.offsetY / $(this).height()) * (bottom - top) + top;
        }
        else if (midas.visualize.sliceMode == 'YZ Plane') {
            var top = focus[2] + pscale;
            var bottom = focus[2] - pscale;
            var left = focus[0] - pscale;
            var right = focus[0] + pscale;
            x = midas.visualize.currentSlice + midas.visualize.bounds[0] - midas.visualize.extent[0];
            y = (e.offsetX / $(this).width()) * (right - left) + left;
            z = (e.offsetY / $(this).height()) * (bottom - top) + top;
        }

        var html = 'You have selected the point:<p><b>(' + x.toFixed(1) + ', ' + y.toFixed(1) + ', ' + z.toFixed(1) + ')</b></p>' + 'Click OK to proceed or Cancel to re-select a point';
        html += '<br/><br/><div style="float: right;"><button id="pointSelectOk">OK</button>';
        html += '<button style="margin-left: 15px" id="pointSelectCancel">Cancel</button></div>';
        midas.showDialogWithContent('Confirm Point Selection', html, false, {
            modal: false
        });

        $('#pointSelectOk').unbind('click').click(function () {
            if (typeof midas.visualize.handlePointSelect == 'function') {
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
            radius: midas.visualize.maxDim / 100.0, // make the sphere some small fraction of the image size
            objectToDelete: midas.visualize.glyph ? midas.visualize.glyph : false,
            input: midas.visualize.input
        };
        paraview.callPluginMethod('midasslice', 'ShowSphere', params, function (view, retVal) {
            midas.visualize.glyph = retVal.glyph;
            midas.visualize.forceRefreshView();
        });
    });
};

/**
 * Force the renderer image to refresh from the server
 */
midas.visualize.forceRefreshView = function () {
    'use strict';
    midas.visualize.renderers.js.forceRefresh();
};

/**
 * Set an action as active
 * @param button The button to display as active (all others will become inactive)
 * @param callback The function to call when this button is activated
 */
midas.visualize.setActiveAction = function (button, callback) {
    'use strict';
    $('.actionActive').addClass('actionInactive').removeClass('actionActive');
    button.removeClass('actionInactive').addClass('actionActive');
    callback();
};

/**
 * Enable point selection action
 */
midas.visualize._enablePointSelect = function () {
    'use strict';
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
    'use strict';
    $.each(operations, function (k, operation) {
        if (operation == 'pointSelect') {
            midas.visualize._enablePointSelect();
        }
        else if (operation != '') {
            alert('Unsupported operation: ' + operation);
        }
    });
};

/**
 * Toggle the visibility of any controls overlaid on top of the render container
 */
midas.visualize.toggleControlVisibility = function () {
    'use strict';
    if ($('#sliceControlContainer').is(':visible')) {
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
    'use strict';
    if (midas.visualize.sliceMode == sliceMode) {
        return; // nothing to do, already in this mode
    }

    var slice, min, max;
    if (sliceMode == 'XY Plane') {
        slice = Math.floor(midas.visualize.midK);
        midas.visualize.cameraParallelScale =
            Math.max(midas.visualize.bounds[1] - midas.visualize.bounds[0],
                midas.visualize.bounds[3] - midas.visualize.bounds[2]) / 2.0;
        midas.visualize.cameraPosition = [midas.visualize.midI, midas.visualize.midJ, midas.visualize.bounds[4] - 10];
        midas.visualize.cameraViewUp = [0.0, -1.0, 0.0];
        min = midas.visualize.bounds[4];
        max = midas.visualize.bounds[5];
    }
    else if (sliceMode == 'XZ Plane') {
        slice = Math.floor(midas.visualize.midJ);
        midas.visualize.cameraParallelScale =
            Math.max(midas.visualize.bounds[1] - midas.visualize.bounds[0],
                midas.visualize.bounds[5] - midas.visualize.bounds[4]) / 2.0;
        midas.visualize.cameraPosition = [midas.visualize.midI, midas.visualize.bounds[3] + 10, midas.visualize.midK];
        midas.visualize.cameraViewUp = [0.0, 0.0, 1.0];
        min = midas.visualize.bounds[2];
        max = midas.visualize.bounds[3];
    }
    else { // YZ Plane
        slice = Math.floor(midas.visualize.midI);
        midas.visualize.cameraParallelScale =
            Math.max(midas.visualize.bounds[3] - midas.visualize.bounds[2],
                midas.visualize.bounds[5] - midas.visualize.bounds[4]) / 2.0;
        midas.visualize.cameraPosition = [midas.visualize.bounds[1] + 10, midas.visualize.midJ, midas.visualize.midK];
        midas.visualize.cameraViewUp = [0.0, 0.0, 1.0];
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
        change: function (event, ui) {
            midas.visualize.changeSlice(ui.value);
        },
        slide: function (event, ui) {
            midas.visualize.updateSliceInfo(ui.value);
        }
    });

    var params = {
        volume: midas.visualize.input,
        slice: slice,
        sliceMode: sliceMode,
        meshes: midas.visualize.meshes,
        lineWidth: midas.visualize.maxDim / 100.0,
        parallelScale: midas.visualize.cameraParallelScale,
        cameraPosition: midas.visualize.cameraPosition,
        cameraUp: midas.visualize.cameraViewUp
    };
    paraview.callPluginMethod('midasslice', 'ChangeSliceMode', params, function (view, retVal) {
        midas.visualize.meshSlices = retVal.meshSlices;
        midas.visualize.forceRefreshView();
    });
};

$(window).load(function () {
    'use strict';
    if (typeof midas.visualize.preInitCallback == 'function') {
        midas.visualize.preInitCallback();
    }

    json = $.parseJSON($('div.jsonContent').html());
    midas.visualize.start();
    midas.visualize.enableActions(json.visualize.operations.split(';'));
    $(document).unbind('keypress').keydown(function (event) {
        if (event.which == 67) { // the 'c' key
            midas.visualize.toggleControlVisibility();
            event.preventDefault();
        }
    });
});

$(window).unload(function () {
    'use strict';
    paraview.disconnect();
});
