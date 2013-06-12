var paraview;
var midas = midas || {};
midas.pvw = midas.pvw || {};
midas.pvw.sliceMode = 'XY Plane'; //Initial slice plane
midas.pvw.updateLock = false; // Lock for RPC calls to make sure we just do one at a time
midas.pvw.UPDATE_TIMEOUT_SECONDS = 5; // Max time the update lock can be held in seconds

midas.pvw.currentCanvas = []; // Store all the [voxelIndex, labelValue] tuples until canvas is cleared
midas.pvw.canvasStack = []; // Array to store canvas history (used for undo/redo)
midas.pvw.canvasStackIndex = -1; // Current canvas's index (0-based) in canvasStack (used for undo/redo)
midas.pvw.colorLabelMapping = { // simpleColorPicker's colors to paint labels mapping table
  // use Slicer's 'GenericAnotamyColors' preset colors
  '#80AE80': 1, '#F1D691': 2, '#B17A65': 3, '#6FB8D2': 4, '#D8654F': 5, '#DD8265': 6,
  '#90EE90': 7, '#C06858': 8, '#DCF514': 9, '#4E3F00': 10, '#FFFADC': 11, '#E6DC46': 12};
midas.pvw.paintLabel = 1; // default paint label
midas.pvw.pdfsegForegroundLabel = 1; // default PDF segmentation foreground label
midas.pvw.pdfsegBackgroundLabel = 2; // default PDF segmentation background label
midas.pvw.pdfsegBarrierLabel = 3; // default PDF segmentation barrier label

/**
 * Attempts to acquire the upate lock. If it cannot, this returns false
 * and your operation should not run.  If it can, returns true.
 */
midas.pvw.acquireUpdateLock = function () {
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
    if (midas.pvw.lockExpireTimeout) {
        window.clearTimeout(midas.pvw.lockExpireTimeout);
    }
    midas.pvw.updateLock = false;
};

midas.pvw.start = function () {
    if (typeof midas.pvw.preInitCallback === 'function') {
        midas.pvw.preInitCallback();
    }
    pv.connection.enableInteractions = false;
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
    midas.pvw.extent = resp.extent;
    midas.pvw.slice = resp.sliceInfo.slice;
    midas.pvw.labelmapOpacity = resp.sliceInfo.labelmapOpacity;
    midas.pvw.maxSlices = resp.sliceInfo.maxSlices;
    midas.pvw.cameraParallelScale = resp.sliceInfo.cameraParallelScale;
    midas.pvw.scalarRange = resp.scalarRange;
    midas.pvw.center = resp.center;

    pv.viewport.render();

    $('div.MainDialog').dialog('close');
    midas.pvw.setupSliders(midas.pvw.labelmapOpacity);
    midas.pvw.updateSliceInfo(midas.pvw.slice);
    midas.pvw.populateInfo();
    if (midas.pvw.labelmapOpacity !== null) {
        midas.pvw.updateLabelmapOpacityInfo(midas.pvw.labelmapOpacity);
    }
    else {
        midas.pvw.updateWindowInfo([midas.pvw.scalarRange[0], midas.pvw.scalarRange[1]]);
    }
    midas.pvw.enableActions(json.pvw.operations);
    if (typeof midas.pvw.postInitCallback == 'function') {
        midas.pvw.postInitCallback();
    }

    $('a.switchToVolumeView').attr('href', json.global.webroot + '/pvw/paraview/volume' + window.location.search);
};

/**
 * Helper function to setup the slice and window/level sliders
 */
midas.pvw.setupSliders = function (labelmapOpacity) {
    $('#sliceSlider').slider({
        min: 0,
        max: midas.pvw.maxSlices,
        value: midas.pvw.slice,
        slide: function(event, ui) {
            midas.pvw.changeSlice(ui.value, true);
            midas.pvw.updateSliceInfo(ui.value);
        },
        change: function(event, ui) {
            midas.pvw.changeSlice(ui.value);
        }
    });
    if (labelmapOpacity !== null) {
        $('#labelmapOpacitySlider').slider({
            min: 0,
            max: 100,
            value: midas.pvw.labelmapOpacity * 100,
            slide: function(event, ui) {
                if(midas.pvw.acquireUpdateLock()) {
                    midas.pvw.changeLabelmapOpacity(ui.value / 100);
                }
                midas.pvw.updateLabelmapOpacityInfo(ui.value / 100);
            },
            change: function(event, ui) {
                midas.pvw.changeLabelmapOpacity(ui.value / 100);
            }
        });

    }
    else {
        $('#windowLevelSlider').slider({
            range: true,
            min: midas.pvw.scalarRange[0],
            max: midas.pvw.scalarRange[1],
            values: [midas.pvw.scalarRange[0], midas.pvw.scalarRange[1]],
            slide: function(event, ui) {
                if(midas.pvw.acquireUpdateLock()) {
                    // TODO degrade quality for intermediate updates
                    midas.pvw.changeWindow(ui.values);
                }
                midas.pvw.updateWindowInfo(ui.values);
            },
            change: function(event, ui) {
                midas.pvw.changeWindow(ui.values);
            }
        });
    }
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

/**
 * Update the client GUI values for labelmap opacity, without
 * actually changing them in PVWeb
 */
midas.pvw.updateLabelmapOpacityInfo = function (opacity) {
    $('#labelmapOpacityInfo').html('Labelmap Opacity: '+opacity);
};

/** Make the actual request to PVWeb to set the window */
midas.pvw.changeWindow = function (values) {
    pv.connection.session.call('pv:changeWindow', [values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0])
                        .then(function () {
                            pv.viewport.render();
                            midas.pvw.releaseUpdateLock();
                        })
                        .otherwise(midas.pvw.rpcFailure);
};

/** Make the actual request to PVWeb to set the labelmap opacity */
midas.pvw.changeLabelmapOpacity = function (opacity) {
    pv.connection.session.call('pv:changeLabelmapOpacity', opacity)
                        .then(function () {
                            pv.viewport.render();
                            midas.pvw.releaseUpdateLock();
                        })
                        .otherwise(midas.pvw.rpcFailure);
};

/** Change the slice and run appropriate slice filter on any meshes in the scene */
midas.pvw.changeSlice = function (slice, degradeQuality) {
    slice = parseInt(slice);
    midas.pvw.slice = slice;

    if(midas.pvw.acquireUpdateLock()) {
        pv.connection.session.call('pv:changeSlice', slice)
                            .then(function (resp) {
                                if(degradeQuality) {
                                    pv.viewport.render(null, {quality: 50});
                                }
                                else {
                                    pv.viewport.render();
                                }
                                midas.pvw.releaseUpdateLock();
                            })
                            .otherwise(midas.pvw.rpcFailure)
    }
    else if(!degradeQuality) {
        // If this is a non-interactive fetch, we should poll the lock until it unlocks and then
        // fetch a full-res frame.  This will happens after sliding stops.
        window.setTimeout(midas.pvw.changeSlice, 20, slice);
    }
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
    var el = $('#renderercontainer .mouse-listener');
    el.unbind('click').click(function (e) {
        var x, y, z;
        var pscale = midas.pvw.cameraParallelScale;
        var focus = midas.pvw.center;
        // offsetX and offsetY undefined in Firefox 4
        var offX = typeof e.offsetX === "undefined" ?  e.pageX - $(e.target).offset().left : e.offsetX;
        var offY = typeof e.offsetY === "undefined" ?  e.pageY - $(e.target).offset().top : e.offsetY;
            
        if(midas.pvw.sliceMode == 'XY Plane') {
            var top = focus[1] - pscale;
            var bottom = focus[1] + pscale;
            var left = focus[0] - pscale;
            var right = focus[0] + pscale;
            x = (offX / $(this).width()) * (right - left) + left;
            y = (offY / $(this).height()) * (bottom - top) + top;
            var a = (midas.pvw.slice + midas.pvw.extent[4]) / (midas.pvw.extent[5] - midas.pvw.extent[4]);
            z = a * (midas.pvw.bounds[5] - midas.pvw.bounds[4]) + midas.pvw.bounds[4];
        }
        else if(midas.pvw.sliceMode == 'XZ Plane') {
            var top = focus[2] + pscale;
            var bottom = focus[2] - pscale;
            var left = focus[0] + pscale;
            var right = focus[0] - pscale;
            x = (offX / $(this).width()) * (right - left) + left;
            var a = (midas.pvw.slice + midas.pvw.extent[2]) / (midas.pvw.extent[3] - midas.pvw.extent[2]);
            y = a * (midas.pvw.bounds[3] - midas.pvw.bounds[2]) + midas.pvw.bounds[2];
            z = (offY / $(this).height()) * (bottom - top) + top;
        }
        else if(midas.pvw.sliceMode == 'YZ Plane') {
            var top = focus[2] + pscale;
            var bottom = focus[2] - pscale;
            var left = focus[0] - pscale;
            var right = focus[0] + pscale;
            var a = (midas.pvw.slice + midas.pvw.extent[0]) / (midas.pvw.extent[1] - midas.pvw.extent[0]);
            x = a * (midas.pvw.bounds[1] - midas.pvw.bounds[0]) + midas.pvw.bounds[2];
            y = (offX / $(this).width()) * (right - left) + left;
            z = (offY / $(this).height()) * (bottom - top) + top;
        }

        var html = 'You have selected the point:<p><b>('
                 +x.toFixed(2)+', '+y.toFixed(2)+', '+z.toFixed(2)+')</b></p>'
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
            color: [1.0, 0.0, 0.0]
        };
        pv.connection.session.call('pv:showSphere', params)
                             .then(pv.viewport.render)
                             .otherwise(midas.pvw.rpcFailure);
    });
};


/**
 * Set the mode to paint on the image.
 */
midas.pvw.paintMode = function () {
    midas.createNotice('Hold down left mouse button and move mouse to paint on the image.', 4000);
    // Bind mousedown and mouseup actions on the render window
    var el = $('#renderercontainer .mouse-listener');
    var moved = false;
    el.unbind('mousedown').mousedown(function (e) {
        // Chrome sets cursor to text while dragging, and we don't like this default setting
        e.originalEvent.preventDefault();
        moved = false;
        var xPadding, yPadding, realOffsetX, realOffsetY
        var i, j, k, idx_flat;
        var pscale = midas.pvw.cameraParallelScale;
        var planePoints = (midas.pvw.extent[3] - midas.pvw.extent[2] + 1) *  (midas.pvw.extent[1] - midas.pvw.extent[0] + 1);
        var rowPoints = midas.pvw.extent[1] - midas.pvw.extent[0] + 1;
        var lastMoveTimestamp;
        // Start paiting after mousedown->mousemove
        $(this).bind('mousemove', function (event){
            moved = true;
            // offsetX and offsetY undefined in Firefox 4
            var offX = typeof event.offsetX === "undefined" ?  event.pageX - $(event.target).offset().left : event.offsetX;
            var offY = typeof event.offsetY === "undefined" ?  event.pageY - $(event.target).offset().top : event.offsetY;
            // Convert html pixel to image data's (i,j,k) index
            if( midas.pvw.sliceMode == 'XY Plane') {
                xPadding = (1 - (midas.pvw.bounds[1] - midas.pvw.bounds[0]) / (2 * pscale)) / 2 * $(this).width();
                yPadding = (1 - (midas.pvw.bounds[3] - midas.pvw.bounds[2]) / (2 * pscale)) / 2 * $(this).height();
                realOffsetX = offX - xPadding;
                realOffsetY = offY - yPadding;
                // The image is displayed in a square in html canvas, but the bounds in x-axis and y-axis may not be same.
                // The real bounds of the image in x-axis is [xPadding, $(this).width - xPadding]
                // The real bounds of the image in y-axis is [yPadding, $(this).width - yPadding]
                if ( realOffsetX < 0 || offX > ($(this).width() - xPadding) ||
                     realOffsetY < 0 || offY > ($(this).height() - yPadding)) {
                     return; // ouf of image boundary
                     }
                // CameraViewUp = [0, -1, 0] 
                i = Math.floor(realOffsetX / ($(this).width() - 2 * xPadding) * (midas.pvw.extent[1] - midas.pvw.extent[0])) + midas.pvw.extent[0];
                j = Math.floor(realOffsetY / ($(this).height() - 2 * yPadding) * (midas.pvw.extent[3] - midas.pvw.extent[2])) + midas.pvw.extent[2];
                k = midas.pvw.slice;
            }
            else if(midas.pvw.sliceMode == 'XZ Plane') {
                xPadding = (1 - (midas.pvw.bounds[1] - midas.pvw.bounds[0]) / (2 * pscale)) / 2 * $(this).width();
                yPadding = (1 - (midas.pvw.bounds[5] - midas.pvw.bounds[4]) / (2 * pscale)) / 2 * $(this).height();
                realOffsetX = offX - xPadding;
                realOffsetY = offY - yPadding;
                if ( realOffsetX < 0 || offX > ($(this).width() - xPadding) ||
                     realOffsetY < 0 || offY > ($(this).height() - yPadding)) {
                     return; // ouf of image boundary
                     }
                // CameraViewUp = [0, 0, 1]     
                i = Math.floor( (1 - realOffsetX / ($(this).width() - 2 * xPadding)) * (midas.pvw.extent[1] - midas.pvw.extent[0])) + midas.pvw.extent[0];
                j = midas.pvw.slice;
                k = Math.floor( (1 - realOffsetY / ($(this).height() - 2 * yPadding)) * (midas.pvw.extent[5] - midas.pvw.extent[4])) + midas.pvw.extent[4];
            }
            else if(midas.pvw.sliceMode == 'YZ Plane') {
                xPadding = (1 - (midas.pvw.bounds[3] - midas.pvw.bounds[2]) / (2 * pscale)) / 2 * $(this).width();
                yPadding = (1 - (midas.pvw.bounds[5] - midas.pvw.bounds[4]) / (2 * pscale)) / 2 * $(this).height();
                realOffsetX = offX - xPadding;
                realOffsetY = offY - yPadding;
                if ( realOffsetX < 0 || offX > ($(this).width() - xPadding) ||
                     realOffsetY < 0 || offY > ($(this).height() - yPadding)) {
                     return; // ouf of image boundary
                     }
                // CameraViewUp = [0, 0, 1]     
                i = midas.pvw.slice;
                j = Math.floor( realOffsetX / ($(this).width() - 2 * xPadding) * (midas.pvw.extent[3] - midas.pvw.extent[2])) + midas.pvw.extent[2];
                k = Math.floor( (1 - realOffsetY / ($(this).height() - 2 * yPadding)) * (midas.pvw.extent[5] - midas.pvw.extent[4])) + midas.pvw.extent[4];
            }
            // Get flat index from (i,j,k) index
            flatIdx = (k - midas.pvw.extent[4]) * planePoints + (j - midas.pvw.extent[2]) * rowPoints + (i - midas.pvw.extent[0]);
            midas.pvw.currentCanvas.push([flatIdx, midas.pvw.paintLabel]);
            // Show cursor trail to compensate for the delay from PVW rpc call
            $('<img>').attr({'src': json.global.moduleWebroot+'/public/images/paintBrushSmall.png'})
                      .css({
                          'position':'absolute',
                          // paintBrushSmall.png is 8x8, and brush head is on bottom left corner
                          'top': offY - 8,
                          'left': offX
                      })
                      .appendTo($('#renderercontainer'))
                      .fadeOut(3000, 'linear', function (){
                          $(this).remove();
                      });
            // Send one update canvas request per second to avoid overwhelming PVW with rpc calls
            if(!lastMoveTimestamp || (event.timeStamp - lastMoveTimestamp > 1000)) {
                midas.pvw.changeCanvas();
                lastMoveTimestamp = event.timeStamp;
            }
        });
    });
    el.unbind('mouseup').mouseup(function (e) {
        // Stop painting after mouseup
        $(this).unbind('mousemove');
        if(!moved) {
            return; // ignore it if it only has a mouse click but has no mouse move
        }
        // When user undo paint and re-paint, withdraw previously stored canvas after canvasStackIndex
        if (midas.pvw.canvasStackIndex < (midas.pvw.canvasStack.length - 1)) {
            midas.pvw.canvasStack.splice(midas.pvw.canvasStackIndex + 1);
            if ($('.redoButton').hasClass('actionActive')) {
                $('.redoButton').removeClass('actionActive').addClass('actionInactive');
            }
        }
        // Save current canvas to canvas stack
        midas.pvw.canvasStack[++midas.pvw.canvasStackIndex] = midas.pvw.currentCanvas.slice(0);
        if ($('.undoButton').hasClass('actionInactive')) {
            $('.undoButton').removeClass('actionInactive').addClass('actionActive');
        }
        // Update canvas after each mousemove->mouseup
        midas.pvw.changeCanvas();
    });
};

/**
 * Update painting volume
 */
midas.pvw.changeCanvas = function (){
    pv.connection.session.call('pv:changeCanvas', midas.pvw.currentCanvas, midas.pvw.slice)
                         .then(function () {
                             pv.viewport.render();
                             midas.pvw.releaseUpdateLock();
                         })
                         .otherwise(midas.pvw.rpcFailure);
}

/**
 * Export painting volume to local disk and then upload it into the same 
 * direcotry as the input item on Midas server using Pydas.
 */
midas.pvw.exportCanvas = function (fileName){
    ajaxWebApi.ajax({
        // Get destination folder
        method: 'midas.item.get',
        args: 'id='+json.pvw.item.item_id,
            success: function(itemInfo) {
                ajaxWebApi.ajax({
                    // Get Pydas required parameters
                    method: 'midas.pyslicer.get.pydas.params',
                    success: function(pydasParams) {
                        pv.connection.session.call('pv:exportCanvas', pydasParams.data.email, pydasParams.data.apikey, pydasParams.data.url, fileName, itemInfo.data.folder_id)
                          .then(midas.pvw.startPDFSegmentation)
                          .otherwise(midas.pvw.rpcFailure);
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        midas.createNotice(XMLHttpRequest.message, '4000', 'error');
                    }
                });
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                midas.createNotice(XMLHttpRequest.message, '4000', 'error');
            }
    });
};

/**
 * Callback from rpc exportCanvas
 */
midas.pvw.startPDFSegmentation = function (resp) {
    if(typeof midas.pvw.handlePDFSegmentation == 'function') {
        midas.pvw.handlePDFSegmentation(
          resp.labelmap_item_id, [midas.pvw.pdfsegForegroundLabel, midas.pvw.pdfsegBackgroundLabel]);
    }
    else {
       midas.createNotice('No TubeTK PDF segmentation handler function has been loaded', 4000, 'error');
    }
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
 * Enable paint action
 */
midas.pvw._enablePaint = function () {
    // simpleColorPicker settings
    $.fn.simpleColorPicker.defaults.showHexField = false;
    var colorPickertColors = new Array();
    for (var key in midas.pvw.colorLabelMapping) {
        colorPickertColors.push(key);
    }
    $.fn.simpleColorPicker.defaults.colors = colorPickertColors;

    // Buttons to select PDF segmentation input labels
    $('div#rendererOverlay').append(
        '<div class="pdfsegInput" id="foregroundLabelDiv"><label>Foreground Label</label> <button id="foregroundLabel" value=' + colorPickertColors[0] + '/></div>')
    $('#foregroundLabel').simpleColorPicker({
        onColorChange : function(id, newValue) {
            midas.pvw.pdfsegForegroundLabel = midas.pvw.colorLabelMapping[newValue.toUpperCase()];
            midas.pvw.paintLabel = midas.pvw.pdfsegForegroundLabel;
        }
    });
    $('div#foregroundLabelDiv .simpleColorPicker-picker').click(function() {
        midas.pvw.paintLabel = midas.pvw.pdfsegForegroundLabel; 
        })
        .qtip({
            content: 'Click to set foreground label color and use it as the paint color.',
            position: {
                my: 'left center',
                at: 'right center'
            }
        });
    $('div#rendererOverlay').append(
        '<div class="pdfsegInput" id="backgroundLabelDiv"><label>Background Label</label> <button id="backgroundLabel" value=' + colorPickertColors[1] + '/></div>')
    $('#backgroundLabel').simpleColorPicker({
        onColorChange : function(id, newValue) {
            midas.pvw.pdfsegBackgroundLabel = midas.pvw.colorLabelMapping[newValue.toUpperCase()];
            midas.pvw.paintLabel = midas.pvw.pdfsegBackgroundLabel;
        }
    });
    $('div#backgroundLabelDiv .simpleColorPicker-picker').click(function() {
        midas.pvw.paintLabel = midas.pvw.pdfsegBackgroundLabel; 
        })
        .qtip({
            content: 'Click to set background label color and use it as the paint color.',
            position: {
                my: 'left center',
                at: 'right center'
            }
        });
    $('div#rendererOverlay').append(
        '<div class="pdfsegInput" id="barrierLabelDiv"><label>Barrier Label</label> <button id="barrierLabel" value=' + colorPickertColors[2] + '/></div>')
    $('#barrierLabel').simpleColorPicker({
        onColorChange : function(id, newValue) {
            midas.pvw.pdfsegBarrierLabel = midas.pvw.colorLabelMapping[newValue.toUpperCase()];
            midas.pvw.paintLabel = midas.pvw.pdfsegBarrierLabel;
        }
    });
    $('div#barrierLabelDiv .simpleColorPicker-picker').click(function() {
        midas.pvw.paintLabel = midas.pvw.pdfsegBarrierLabel; 
        })
        .qtip({
            content: 'Click to set barrier label color and use it as the paint color.',
            position: {
                my: 'left center',
                at: 'right center'
            }
        });

    // Button to undo painting
    $('div#rendererOverlay').append('<br/><div class="pdfsegInput" id="undoButtonDiv"/>')
    var undoButton = $('<button class="undoButton actionInactive"/>');
    undoButton.appendTo('#undoButtonDiv');
    undoButton.qtip({
        content: 'Undo',
        position: {
            my: 'left center',
            at: 'right center'
        }
    });
    undoButton.show();
    undoButton.unbind('click').click(function () {
        if (midas.pvw.canvasStackIndex >= 0) {
            var temp = midas.pvw.canvasStack[--midas.pvw.canvasStackIndex];
            midas.pvw.currentCanvas = typeof temp === "undefined" ? [] : temp.slice(0);
            midas.pvw.changeCanvas();
            if ($('.redoButton').hasClass('actionInactive')) {
                $('.redoButton').removeClass('actionInactive').addClass('actionActive');
            }
        }
        if (midas.pvw.canvasStackIndex < 0 && $(this).hasClass('actionActive')) {
            $(this).removeClass('actionActive').addClass('actionInactive');
        }
    });
    // Button to redo painting
    $('div#rendererOverlay').append('<div class="pdfsegInput" id="redoButtonDiv"/>')
    var redoButton = $('<button class="redoButton actionInactive"/>');
    redoButton.appendTo('#redoButtonDiv');
    redoButton.qtip({
        content: 'Redo',
        position: {
            my: 'left center',
            at: 'right center'
        }
    });
    redoButton.show();
    redoButton.unbind('click').click(function () {
        if (midas.pvw.canvasStackIndex < (midas.pvw.canvasStack.length - 1)) {  
            midas.pvw.currentCanvas = midas.pvw.canvasStack[++midas.pvw.canvasStackIndex].slice(0);
            midas.pvw.changeCanvas();
            if ($('.undoButton').hasClass('actionInactive')) {
                $('.undoButton').removeClass('actionInactive').addClass('actionActive');
            }
        }
        if (midas.pvw.canvasStackIndex >= (midas.pvw.canvasStack.length - 1) && $(this).hasClass('actionActive')) {
            $(this).removeClass('actionActive').addClass('actionInactive');
        }
    });
    // Button to clear existing painting
    $('div#rendererOverlay').append('<div class="pdfsegInput" id="clearButtonDiv"/>')
    var clearButton = $('<button class="clearButton"/>');
    clearButton.appendTo('#clearButtonDiv');
    clearButton.qtip({
        content: 'Clear label map',
        position: {
            my: 'left center',
            at: 'right center'
        }
    });
    clearButton.show();
    clearButton.unbind('click').click(function () {
        $('div.MainDialog').dialog('close');
        html = '<div style="float: right;">';
        html += '<button class="globalButton clearLabelmapYes">Yes</button>';
        html += '<button style="margin-left: 15px;" class="globalButton clearLabelmapNo">No</button>';
        html += '</div>';
        midas.showDialogWithContent('Do you really want to clear all labels on all slices?', html, false);
        $('button.clearLabelmapYes').unbind('click').click(function () {
            midas.pvw.currentCanvas = [];
            midas.pvw.canvasStack = [];
            midas.pvw.canvasStackIndex = -1;
            $('.undoButton').removeClass('actionActive').addClass('actionInactive');
            $('.redoButton').removeClass('actionActive').addClass('actionInactive');
            midas.pvw.changeCanvas();
            $('div.MainDialog').dialog('close');
        });
        $('button.clearLabelmapNo').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });

    // Button to start TubeTK PDF Segmentation
    $('div#rendererOverlay').append('<br/><div class="pdfsegInput" id="segmenterButtonDiv"/>')
    var pipelineButton = $('<button class="segmenterButton"/>');
    pipelineButton.appendTo('#segmenterButtonDiv');
    pipelineButton.qtip({
        content: 'Click to start the PDF Segmentation',
        position: {
            my: 'left center',
            at: 'right center'
        }
    });
    pipelineButton.show();
    pipelineButton.click(function () {
        $('div.MainDialog').dialog('close');
        html= '<div><input style="width: 400px;" type="text" id="inputLabelmapName" value="'
             +json.pvw.item.name+'_pdfseg_input" /></div><br/><br/>';
        html+= '<div id="savingPleaseWait" style="display: none;">';
        html+= '<span>Saving current painting as the intial label map</span>';
        html+= '<img src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>';  
        html+='</div>';
        html+= '<div style="float: right;">';
        html+= '<button class="globalButton saveInputLabelmapYes">Save</button>';
        html+= '<button style="margin-left: 15px;" class="globalButton saveInputLabelmapNo">Cancel</button>';
        html+= '</div>';
        midas.showDialogWithContent('Choose name for initial label map item', html, false);
        $('#inputLabelmapName').focus();
        $('#inputLabelmapName').select();
        $('button.saveInputLabelmapYes').unbind('click').click(function () {
            // Paraview can only save MetaImage file in .mhd format
            var outputItemName = $('#inputLabelmapName').val() + '-label.mhd';
            $('#savingPleaseWait').show();
            midas.pvw.exportCanvas(outputItemName);
        });

        $('button.saveInputLabelmapNo').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });
};

/**
 * Enable the specified set of operations in the view
 * Options:
 *   -pointSelect: select a single point in the image
 *   -paint: paint on the image
 */
midas.pvw.enableActions = function (operations) {
    $.each(operations, function(k, operation) {
        if(operation == 'pointSelect') {
            midas.pvw._enablePointSelect();
        }
        else if(operation == 'paint') {
            midas.pvw._enablePaint();
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
        $('#labelmapOpacityControlContainer').hide();
        $('#rendererOverlay').hide();
    }
    else {
        $('#sliceControlContainer').show();
        if (midas.pvw.labelmapOpacity !== null) {
            $('#labelmapOpacityControlContainer').show();
        } else {
            $('#windowLevelControlContainer').show();
        }
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
    midas.pvw.cameraParallelScale = resp.cameraParallelScale;
    midas.pvw.updateSliceInfo(resp.slice);
    $('#sliceSlider').slider('destroy').slider({
        min: 0,
        max: midas.pvw.maxSlices,
        value: resp.slice,
        slide: function(event, ui) {
            midas.pvw.changeSlice(ui.value, true);
            midas.pvw.updateSliceInfo(ui.value);
        },
        change: function(event, ui) {
            midas.pvw.changeSlice(ui.value);
        }
    });
};
