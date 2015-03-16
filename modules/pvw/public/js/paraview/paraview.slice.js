// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.pvw = midas.pvw || {};
midas.pvw.sliceMode = 'XY Plane'; // Initial slice plane
midas.pvw.updateLock = false; // Lock for RPC calls to make sure we just do one at a time
midas.pvw.UPDATE_TIMEOUT_SECONDS = 5; // Max time the update lock can be held in seconds
var pv = pv || {};

/**
 * Attempts to acquire the upate lock. If it cannot, this returns false
 * and your operation should not run.  If it can, returns true.
 */
midas.pvw.acquireUpdateLock = function () {
    'use strict';
    if (midas.pvw.updateLock) {
        return false;
    }
    else {
        midas.pvw.updateLock = true;
        midas.pvw.lockExpireTimeout = window.setTimeout(function () {
            midas.pvw.updateLock = false;
        }, 1000 * midas.pvw.UPDATE_TIMEOUT_SECONDS);
        return true;
    }
};

midas.pvw.releaseUpdateLock = function () {
    'use strict';
    if (midas.pvw.lockExpireTimeout) {
        window.clearTimeout(midas.pvw.lockExpireTimeout);
    }
    midas.pvw.updateLock = false;
};

midas.pvw.start = function () {
    'use strict';
    if (typeof midas.pvw.preInitCallback == 'function') {
        midas.pvw.preInitCallback();
    }
    pv.connection.enableInteractions = false;
    midas.pvw.loadData();
};

/** Callback for once the loadData RPC has returned */
midas.pvw.dataLoaded = function (resp) {
    'use strict';
    midas.pvw.mainProxy = resp;
    pv.viewport.render();
    midas.pvw.waitingDialog('Starting slice rendering...');
    pv.connection.session.call('vtk:sliceRender', midas.pvw.sliceMode)
        .then(midas.pvw.sliceRenderStarted)
        .otherwise(midas.pvw.rpcFailure);
};

/** Callback from sliceRender rpc success */
midas.pvw.sliceRenderStarted = function (resp) {
    'use strict';
    midas.pvw.bounds = resp.bounds;
    midas.pvw.extent = resp.extent;
    midas.pvw.slice = resp.sliceInfo.slice;
    midas.pvw.maxSlices = resp.sliceInfo.maxSlices;
    midas.pvw.cameraParallelScale = resp.sliceInfo.cameraParallelScale;
    midas.pvw.scalarRange = resp.scalarRange;
    midas.pvw.center = resp.center;

    pv.viewport.render();

    $('div.MainDialog').dialog('close');
    midas.pvw.setupSliders();
    midas.pvw.updateSliceInfo(midas.pvw.slice);
    midas.pvw.populateInfo();
    midas.pvw.updateWindowInfo([midas.pvw.scalarRange[0], midas.pvw.scalarRange[1]]);
    midas.pvw.enableActions(json.pvw.operations);

    $('a.switchToVolumeView').attr('href', json.global.webroot + '/pvw/paraview/volume' + encodeURIComponent(window.location.search));
};

/**
 * Helper function to setup the slice and window/level sliders
 */
midas.pvw.setupSliders = function () {
    'use strict';
    $('#sliceSlider').slider({
        min: 0,
        max: midas.pvw.maxSlices,
        value: midas.pvw.slice,
        slide: function (event, ui) {
            midas.pvw.changeSlice(ui.value, true);
            midas.pvw.updateSliceInfo(ui.value);
        },
        change: function (event, ui) {
            midas.pvw.changeSlice(ui.value);
        }
    });
    $('#windowLevelSlider').slider({
        range: true,
        min: midas.pvw.scalarRange[0],
        max: midas.pvw.scalarRange[1],
        values: [midas.pvw.scalarRange[0], midas.pvw.scalarRange[1]],
        slide: function (event, ui) {
            if (midas.pvw.acquireUpdateLock()) {
                // TODO degrade quality for intermediate updates
                midas.pvw.changeWindow(ui.values);
            }
            midas.pvw.updateWindowInfo(ui.values);
        },
        change: function (event, ui) {
            midas.pvw.changeWindow(ui.values);
        }
    });
};

/**
 * Display information about the volume
 */
midas.pvw.populateInfo = function () {
    'use strict';
    $('#boundsXInfo').html(midas.pvw.bounds[0] + ' .. ' + midas.pvw.bounds[1]);
    $('#boundsYInfo').html(midas.pvw.bounds[2] + ' .. ' + midas.pvw.bounds[3]);
    $('#boundsZInfo').html(midas.pvw.bounds[4] + ' .. ' + midas.pvw.bounds[5]);
    $('#scalarRangeInfo').html(midas.pvw.scalarRange[0] + ' .. ' + midas.pvw.scalarRange[1]);
};

/**
 * Update the client GUI values for window and level, without
 * actually changing them in PVWeb
 */
midas.pvw.updateWindowInfo = function (values) {
    'use strict';
    $('#windowLevelInfo').html('Window: ' + values[0] + ' - ' + values[1]);
};

/** Make the actual request to PVWeb to set the window */
midas.pvw.changeWindow = function (values) {
    'use strict';
    pv.connection.session.call('vtk:changeWindow', [values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0])
        .then(function () {
            pv.viewport.render();
            midas.pvw.releaseUpdateLock();
        })
        .otherwise(midas.pvw.rpcFailure);
};

/** Change the slice and run appropriate slice filter on any meshes in the scene */
midas.pvw.changeSlice = function (slice, degradeQuality) {
    'use strict';
    slice = parseInt(slice);
    midas.pvw.slice = slice;

    if (midas.pvw.acquireUpdateLock()) {
        pv.connection.session.call('vtk:changeSlice', slice)
            .then(function (resp) {
                if (degradeQuality) {
                    pv.viewport.render(null, {
                        quality: 50
                    });
                }
                else {
                    pv.viewport.render();
                }
                midas.pvw.releaseUpdateLock();
            })
            .otherwise(midas.pvw.rpcFailure);
    }
    else if (!degradeQuality) {
        // If this is a non-interactive fetch, we should poll the lock until it unlocks and then
        // fetch a full-res frame.  This will happens after sliding stops.
        window.setTimeout(midas.pvw.changeSlice, 20, slice);
    }
};

/**
 * Update the value of the current slice, without rendering the slice.
 */
midas.pvw.updateSliceInfo = function (slice) {
    'use strict';
    $('#sliceInfo').html('Slice: ' + slice + ' of ' + midas.pvw.maxSlices);
};

/**
 * Set the mode to point selection within the image.
 */
midas.pvw.pointSelectMode = function () {
    'use strict';
    midas.createNotice('Click on the image to select a point', 3500);

    // Bind click action on the render window
    var el = $('#renderercontainer .mouse-listener');
    el.unbind('click').click(function (e) {
        var x, y, z;
        var pscale = midas.pvw.cameraParallelScale;
        var focus = midas.pvw.center;
        var top, bottom, left, right;
        var a;

        if (midas.pvw.sliceMode == 'XY Plane') {
            top = focus[1] - pscale;
            bottom = focus[1] + pscale;
            left = focus[0] - pscale;
            right = focus[0] + pscale;
            x = (e.offsetX / $(this).width()) * (right - left) + left;
            y = (e.offsetY / $(this).height()) * (bottom - top) + top;
            a = (midas.pvw.slice + midas.pvw.extent[4]) / (midas.pvw.extent[5] - midas.pvw.extent[4]);
            z = a * (midas.pvw.bounds[5] - midas.pvw.bounds[4]) + midas.pvw.bounds[4];
        }
        else if (midas.pvw.sliceMode == 'XZ Plane') {
            top = focus[2] + pscale;
            bottom = focus[2] - pscale;
            left = focus[0] + pscale;
            right = focus[0] - pscale;
            x = (e.offsetX / $(this).width()) * (right - left) + left;
            a = (midas.pvw.slice + midas.pvw.extent[2]) / (midas.pvw.extent[3] - midas.pvw.extent[2]);
            y = a * (midas.pvw.bounds[3] - midas.pvw.bounds[2]) + midas.pvw.bounds[2];
            z = (e.offsetY / $(this).height()) * (bottom - top) + top;
        }
        else if (midas.pvw.sliceMode == 'YZ Plane') {
            top = focus[2] + pscale;
            bottom = focus[2] - pscale;
            left = focus[0] - pscale;
            right = focus[0] + pscale;
            a = (midas.pvw.slice + midas.pvw.extent[0]) / (midas.pvw.extent[1] - midas.pvw.extent[0]);
            x = a * (midas.pvw.bounds[1] - midas.pvw.bounds[0]) + midas.pvw.bounds[2];
            y = (e.offsetX / $(this).width()) * (right - left) + left;
            z = (e.offsetY / $(this).height()) * (bottom - top) + top;
        }

        var html = 'You have selected the point:<p><b>(' + x.toFixed(2) + ', ' + y.toFixed(2) + ', ' + z.toFixed(2) + ')</b></p>' + 'Click OK to proceed or Cancel to re-select a point';
        html += '<br/><br/><div style="float: right;"><button id="pointSelectOk">OK</button>';
        html += '<button style="margin-left: 15px" id="pointSelectCancel">Cancel</button></div>';
        midas.showDialogWithContent('Confirm Point Selection', html, false, {
            modal: false
        });

        $('#pointSelectOk').unbind('click').click(function () {
            if (typeof midas.pvw.handlePointSelect == 'function') {
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
            color: [1.0, 0.0, 0.0]
        };
        pv.connection.session.call('vtk:showSphere', params)
            .then(pv.viewport.render)
            .otherwise(midas.pvw.rpcFailure);
    });
};

/**
 * Set an action as active
 * @param button The button to display as active (all others will become inactive)
 * @param callback The function to call when this button is activated
 */
midas.pvw.setActiveAction = function (button, callback) {
    'use strict';
    $('.actionActive').addClass('actionInactive').removeClass('actionActive');
    button.removeClass('actionInactive').addClass('actionActive');
    callback();
};

/**
 * Enable point selection action
 */
midas.pvw._enablePointSelect = function () {
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
        midas.pvw.setActiveAction($(this), midas.pvw.pointSelectMode);
    });
};

/**
 * Enable the specified set of operations in the view
 * Options:
 *   -pointSelect: select a single point in the image
 */
midas.pvw.enableActions = function (operations) {
    'use strict';
    $.each(operations, function (k, operation) {
        if (operation == 'pointSelect') {
            midas.pvw._enablePointSelect();
        }
        else if (operation != '') {
            alert('Unsupported operation: ' + operation);
        }
    });
};

/**
 * Toggle the visibility of any controls overlaid on top of the render container
 */
midas.pvw.toggleControlVisibility = function () {
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
midas.pvw.setSliceMode = function (sliceMode) {
    'use strict';
    if (midas.pvw.sliceMode == sliceMode) {
        return; // nothing to do, already in this mode
    }
    midas.pvw.sliceMode = sliceMode;
    pv.connection.session.call('vtk:setSliceMode', midas.pvw.sliceMode)
        .then(midas.pvw.sliceModeChanged)
        .otherwise(midas.pvw.rpcFailure);
};

/** Callback from setSliceMode rpc */
midas.pvw.sliceModeChanged = function (resp) {
    'use strict';
    pv.viewport.render();
    midas.pvw.slice = resp.slice;
    midas.pvw.maxSlices = resp.maxSlices;
    midas.pvw.cameraParallelScale = resp.cameraParallelScale;
    midas.pvw.updateSliceInfo(resp.slice);
    $('#sliceSlider').slider('destroy').slider({
        min: 0,
        max: midas.pvw.maxSlices,
        value: resp.slice,
        slide: function (event, ui) {
            midas.pvw.changeSlice(ui.value, true);
            midas.pvw.updateSliceInfo(ui.value);
        },
        change: function (event, ui) {
            midas.pvw.changeSlice(ui.value);
        }
    });
};
