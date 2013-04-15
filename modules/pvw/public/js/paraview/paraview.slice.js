var paraview;
var midas = midas || {};
midas.pvw = midas.pvw || {};
midas.pvw.sliceMode = 'XY Plane'; //Initial slice plane

midas.pvw.start = function () {
    if(typeof midas.pvw.preInitCallback == 'function') {
        midas.pvw.preInitCallback();
    }
    pv = {};
    pv.connection = {
        sessionURL: 'ws://'+location.hostname+':'+midas.pvw.instance.port+'/ws',
        id: midas.pvw.instance.instance_id,
        sessionManagerURL: json.global.webroot + '/pvw/paraview/instance',
        enableInteractions: false
    };
    midas.pvw.loadData();
};

/** Callback for once the loadData RPC has returned */
midas.pvw.dataLoaded = function (resp) {
    midas.pvw.mainProxy = resp;
    pv.viewport.render();
    midas.pvw.waitingDialog('Starting slice rendering...');
    pv.connection.session.call('pv:sliceRender', midas.pvw.sliceMode)
                         .then(midas.pvw.sliceRenderStarted)
                         .otherwise(midas.pvw.rpcFailure);
};

/** Callback from sliceRender rpc success */
midas.pvw.sliceRenderStarted = function (resp) {
    midas.pvw.bounds = resp.bounds;
    midas.pvw.slice = resp.sliceInfo.slice;
    midas.pvw.maxSlices = resp.sliceInfo.maxSlices;
    midas.pvw.scalarRange = resp.scalarRange;

    pv.viewport.render();

    $('div.MainDialog').dialog('close');
    midas.pvw.setupSliders();
    midas.pvw.updateSliceInfo(midas.pvw.slice);
    midas.pvw.populateInfo();
    midas.pvw.updateWindowInfo([midas.pvw.scalarRange[0], midas.pvw.scalarRange[1]]);
}

/**
 * Helper function to setup the slice and window/level sliders
 */
midas.pvw.setupSliders = function () {
    $('#sliceSlider').slider({
        min: 0,
        max: midas.pvw.maxSlices,
        value: midas.pvw.slice,
        change: function(event, ui) {
            midas.pvw.changeSlice(ui.value);
        },
        slide: function(event, ui) {
            midas.pvw.updateSliceInfo(ui.value);
        }
    });
    $('#windowLevelSlider').slider({
        range: true,
        min: midas.pvw.scalarRange[0],
        max: midas.pvw.scalarRange[1],
        values: [midas.pvw.scalarRange[0], midas.pvw.scalarRange[1]],
        change: function(event, ui) {
            midas.pvw.changeWindow(ui.values);
        },
        slide: function(event, ui) {
            midas.pvw.updateWindowInfo(ui.values);
        }
    });
};

/**
 * Display information about the volume
 */
midas.pvw.populateInfo = function () {
    $('#boundsXInfo').html(midas.pvw.bounds[0]+' .. '+midas.pvw.bounds[1]);
    $('#boundsYInfo').html(midas.pvw.bounds[2]+' .. '+midas.pvw.bounds[3]);
    $('#boundsZInfo').html(midas.pvw.bounds[4]+' .. '+midas.pvw.bounds[5]);
    $('#scalarRangeInfo').html(midas.pvw.scalarRange[0]+' .. '+midas.pvw.scalarRange[1]);
};

/**
 * Update the client GUI values for window and level, without
 * actually changing them in PVWeb
 */
midas.pvw.updateWindowInfo = function (values) {
    $('#windowLevelInfo').html('Window: '+values[0]+' - '+values[1]);
};

/** Make the actual request to PVWeb to set the window */
midas.pvw.changeWindow = function (values) {
    pv.connection.session.call('pv:changeWindow', [values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0])
                        .then(function () {
                            pv.viewport.render();
                        })
                        .otherwise(midas.pvw.rpcFailure);
};

/** Change the slice and run appropriate slice filter on any meshes in the scene */
midas.pvw.changeSlice = function (slice) {
    slice = parseInt(slice);
    midas.pvw.currentSlice = slice;

    pv.connection.session.call('pv:changeSlice', slice)
                        .then(function (resp) {
                            pv.viewport.render();
                        })
                        .otherwise(midas.pvw.rpcFailure)
};

/**
 * Update the value of the current slice, without rendering the slice.
 */
midas.pvw.updateSliceInfo = function (slice) {
    $('#sliceInfo').html('Slice: ' + slice + ' of '+ midas.pvw.maxSlices);
};

/**
 * Set the mode to point selection within the image.
 */
midas.pvw.pointSelectMode = function () {
    midas.createNotice('Click on the image to select a point', 3500);

    // Bind click action on the render window
    var el = $(midas.pvw.renderers.current.view);
    el.unbind('click').click(function (e) {
        var x, y, z;
        var pscale = midas.pvw.cameraParallelScale;
        var focus = midas.pvw.cameraFocalPoint;

        if(midas.pvw.sliceMode == 'XY Plane') {
            var top = focus[1] - pscale;
            var bottom = focus[1] + pscale;
            var left = focus[0] - pscale;
            var right = focus[0] + pscale;
            x = (e.offsetX / $(this).width()) * (right - left) + left;
            y = (e.offsetY / $(this).height()) * (bottom - top) + top;
            z = midas.pvw.currentSlice + midas.pvw.bounds[4] - midas.pvw.extent[4];
        }
        else if(midas.pvw.sliceMode == 'XZ Plane') {
            var top = focus[2] + pscale;
            var bottom = focus[2] - pscale;
            var left = focus[0] + pscale;
            var right = focus[0] - pscale;
            x = (e.offsetX / $(this).width()) * (right - left) + left;
            y = midas.pvw.currentSlice + midas.pvw.bounds[2] - midas.pvw.extent[2];
            z = (e.offsetY / $(this).height()) * (bottom - top) + top;
        }
        else if(midas.pvw.sliceMode == 'YZ Plane') {
            var top = focus[2] + pscale;
            var bottom = focus[2] - pscale;
            var left = focus[0] - pscale;
            var right = focus[0] + pscale;
            x = midas.pvw.currentSlice + midas.pvw.bounds[0] - midas.pvw.extent[0];
            y = (e.offsetX / $(this).width()) * (right - left) + left;
            z = (e.offsetY / $(this).height()) * (bottom - top) + top;
        }

        var html = 'You have selected the point:<p><b>('
                 +x.toFixed(1)+', '+y.toFixed(1)+', '+z.toFixed(1)+')</b></p>'
                 +'Click OK to proceed or Cancel to re-select a point';
        html += '<br/><br/><div style="float: right;"><button id="pointSelectOk">OK</button>';
        html += '<button style="margin-left: 15px" id="pointSelectCancel">Cancel</button></div>';
        midas.showDialogWithContent('Confirm Point Selection', html, false, {modal: false});

        $('#pointSelectOk').unbind('click').click(function () {
            if(typeof midas.pvw.handlePointSelect == 'function') {
                midas.pvw.handlePointSelect([x, y, z]);
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
            radius: midas.pvw.maxDim / 100.0, //make the sphere some small fraction of the image size
            objectToDelete: midas.pvw.glyph ? midas.pvw.glyph : false,
            input: midas.pvw.input
        };
        paraview.callPluginMethod('midasslice', 'ShowSphere', params, function (view, retVal) {
            midas.pvw.glyph = retVal.glyph;
            midas.pvw.forceRefreshView();
        });
    });
};

/**
 * Set an action as active
 * @param button The button to display as active (all others will become inactive)
 * @param callback The function to call when this button is activated
 */
midas.pvw.setActiveAction = function (button, callback) {
    $('.actionActive').addClass('actionInactive').removeClass('actionActive');
    button.removeClass('actionInactive').addClass('actionActive');
    callback();
};

/**
 * Enable point selection action
 */
midas.pvw._enablePointSelect = function () {
    var button = $('#actionButtonTemplate').clone();
    button.removeAttr('id');
    button.addClass('pointSelectButton');
    button.appendTo('#rendererOverlay');
    button.qtip({
        content: 'Select a single point in the image'
    });
    button.show();

    button.click(function () {
        midas.pvw.setActiveAction($(this), midas.pvw.pointSelectMode);
    });
};

/**
 * Enable the specified set of operations in the view
 * Options:
 *   -pointSelect: select a single point in the image
 */
midas.pvw.enableActions = function (operations) {
    $.each(operations, function(k, operation) {
        if(operation == 'pointSelect') {
            midas.pvw._enablePointSelect();
        }
        else if(operation != '') {
            alert('Unsupported operation: '+operation);
        }
    });
};

/**
 * Toggle the visibility of any controls overlaid on top of the render container
 */
midas.pvw.toggleControlVisibility = function () {
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
midas.pvw.setSliceMode = function (sliceMode) {
    if(midas.pvw.sliceMode == sliceMode) {
        return; //nothing to do, already in this mode
    }
    midas.pvw.sliceMode = sliceMode;
    pv.connection.session.call('pv:setSliceMode', midas.pvw.sliceMode)
                         .then(midas.pvw.sliceModeChanged)
                         .otherwise(midas.pvw.rpcFailure);
};

/** Callback from setSliceMode rpc */
midas.pvw.sliceModeChanged = function (resp) {
    pv.viewport.render();
    midas.pvw.slice = resp.slice;
    midas.pvw.maxSlices = resp.maxSlices;
    midas.pvw.updateSliceInfo(resp.slice);
    $('#sliceSlider').slider('destroy').slider({
        min: 0,
        max: midas.pvw.maxSlices,
        value: resp.slice,
        change: function(event, ui) {
            midas.pvw.changeSlice(ui.value);
        },
        slide: function(event, ui) {
            midas.pvw.updateSliceInfo(ui.value);
        }
    });
};
