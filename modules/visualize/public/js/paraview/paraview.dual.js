var paraview;
var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.left = {points: []};
midas.visualize.right = {points: []};
midas.visualize.camerasLocked = false;

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
    midas.visualize.pointColors = midas.visualize._generateColorList(8);
};

midas.visualize._dataOpened = function (side, retVal) {
    midas.visualize[side].input = retVal.input;
    midas.visualize[side].bounds = retVal.imageData.Bounds;
    midas.visualize[side].extent = retVal.imageData.Extent;

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
    
    // Store data representation properties
    midas.visualize[side].defaultColorMap = [
       midas.visualize[side].minVal, 0.0, 0.0, 0.0,
       midas.visualize[side].maxVal, 1.0, 1.0, 1.0];
    midas.visualize[side].colorMap = midas.visualize[side].defaultColorMap;
    midas.visualize.currentSlice = midas.visualize[side].midK;
    midas.visualize.sliceMode = 'XY Plane';

    // Store camera properties
    midas.visualize[side].cameraFocalPoint = 
      [midas.visualize[side].midI, midas.visualize[side].midJ, midas.visualize[side].midK];
    midas.visualize[side].cameraPosition = 
      [midas.visualize[side].midI, midas.visualize[side].midJ, midas.visualize[side].bounds[4] - 10];
    midas.visualize[side].cameraParallelScale = 
      Math.max(midas.visualize[side].bounds[1] - midas.visualize[side].bounds[0],
      midas.visualize[side].bounds[3] - midas.visualize[side].bounds[2]) / 2.0;
    midas.visualize[side].cameraViewUp = [0.0, -1.0, 0.0];
    
    var params = {
        cameraFocalPoint: midas.visualize[side].cameraFocalPoint,
        cameraPosition: midas.visualize[side].cameraPosition,
        colorMap: midas.visualize[side].defaultColorMap,
        colorArrayName: json.visualize.colorArrayNames[side],
        sliceVal: midas.visualize.currentSlice,
        sliceMode: midas.visualize.sliceMode,
        parallelScale: midas.visualize[side].cameraParallelScale,
        cameraUp: midas.visualize[side].cameraViewUp
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
    midas.visualize.disableMouseInteraction(side);

    if(side == 'left') { //sliders will be based on left image
        midas.visualize.setupSliders();
        midas.visualize.updateSliceInfo(midas.visualize.left.midK);
        midas.visualize.updateWindowInfo([midas.visualize.left.minVal, midas.visualize.left.maxVal]);
    }

    midas.visualize.enableActions(side, json.visualize.operations.split(';'));

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
        min: midas.visualize.left.bounds[4],
        max: midas.visualize.left.bounds[5],
        value: midas.visualize.left.midK,
        change: function(event, ui) {
            midas.visualize.changeSlice(ui.value);
        },
        slide: function(event, ui) {
            midas.visualize.updateSliceInfo(ui.value);
        }
    });
    $('#windowSlider').slider({
        range: true,
        min: midas.visualize.left.minVal,
        max: midas.visualize.left.maxVal,
        values: [midas.visualize.left.minVal, midas.visualize.left.maxVal],
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
    $('#windowInfo').html('Window: '+values[0]+' - '+values[1]);
};

/** Make the actual request to PVWeb to set the window */
midas.visualize.changeWindow = function (values) {
    paraview.left.plugins.midasdual.AsyncChangeWindow(function (retVal) {
        midas.visualize.left.lookupTable = retVal.lookupTable;
        midas.visualize.forceRefreshView('left');
    }, [values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0], json.visualize.colorArrayNames.left);
    paraview.right.plugins.midasdual.AsyncChangeWindow(function (retVal) {
        midas.visualize.right.lookupTable = retVal.lookupTable;
        midas.visualize.forceRefreshView('right');
    }, [values[0], 0.0, 0.0, 0.0, values[1], 1.0, 1.0, 1.0], json.visualize.colorArrayNames.right);
    midas.visualize.left.imageWindow = values;
    midas.visualize.right.imageWindow = values;
};

/** Change the slice and run appropriate slice filter on any meshes in the scene */
midas.visualize.changeSlice = function (slice) {
    slice = parseInt(slice);
    midas.visualize.currentSlice = slice;
    
    var params = {
        left: {
            volume: midas.visualize.left.input,
            slice: slice,
            sliceMode: midas.visualize.sliceMode
        },
        right: {
            volume: midas.visualize.right.input,
            slice: slice,
            sliceMode: midas.visualize.sliceMode
        }
    };

    paraview.left.plugins.midasdual.AsyncChangeSlice(function(retVal) {
        if(typeof midas.visualize.changeSliceCallback == 'function') {
            midas.visualize.changeSliceCallback(slice, 'left');
        }
        midas.visualize.forceRefreshView('left');
    }, params.left);
    paraview.right.plugins.midasdual.AsyncChangeSlice(function(retVal) {
        if(typeof midas.visualize.changeSliceCallback == 'function') {
            midas.visualize.changeSliceCallback(slice, 'right');
        }
        midas.visualize.forceRefreshView('right');
    }, params.right);
};

/**
 * Update the value of the current slice, without rendering the slice.
 */
midas.visualize.updateSliceInfo = function (slice) {
    var max;
    if(midas.visualize.sliceMode == 'XY Plane') {
        max = midas.visualize.left.bounds[5];
    }
    else if(midas.visualize.sliceMode == 'XZ Plane') {
        max = midas.visualize.left.bounds[3];
    }
    else { // YZ Plane
        max = midas.visualize.left.bounds[1];
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
 * Generate a list of fully saturated colors.
 * List will contain <size> color values that are RGB lists with each channel in [0, 1].
 */
midas.visualize._generateColorList = function (size) {
   var list = [];
   for(var i = 0; i < size; i++) {
      var hue = i*(1.0 / size);
      if(hue > 1.0) hue = 1.0;
      list.push(midas.visualize._hsvToRgb(hue, 1.0, 1.0));
   }
   return list;
};

/**
 * Helper function for converting HSV to RGB color space
 * HSV input values should be in [0, 1]
 * RGB output values will be in [0, 1]
 */
midas.visualize._hsvToRgb = function (h, s, v) {
    var r, g, b;

    var i = Math.floor(h * 6);
    var f = h * 6 - i;
    var p = v * (1 - s);
    var q = v * (1 - f * s);
    var t = v * (1 - (1 - f) * s);

    switch(i % 6) {
        case 0: r = v, g = t, b = p; break;
        case 1: r = q, g = v, b = p; break;
        case 2: r = p, g = v, b = t; break;
        case 3: r = p, g = q, b = v; break;
        case 4: r = t, g = p, b = v; break;
        case 5: r = v, g = p, b = q; break;
    }

    return [r, g, b];
};

/**
 * Set the mode to point selection within the image.
 */
midas.visualize.pointMapMode = function () {
    midas.createNotice('Click on the images to select points', 3500);

    // Bind click action on the render window
    $.each(['left', 'right'], function(i, side) {
        var el = $(midas.visualize[side].renderer.view);

        el.unbind('click').click(function (e) {
            var x, y, z;
            var pscale = midas.visualize[side].cameraParallelScale;
            var focus = midas.visualize[side].cameraFocalPoint;

            if(midas.visualize.sliceMode == 'XY Plane') {
                var top = focus[1] - pscale;
                var bottom = focus[1] + pscale;
                var left = focus[0] - pscale;
                var right = focus[0] + pscale;
                x = (e.offsetX / $(this).width()) * (right - left) + left;
                y = (e.offsetY / $(this).height()) * (bottom - top) + top;
                z = midas.visualize.currentSlice + midas.visualize[side].bounds[4] - midas.visualize[side].extent[4];
            }
            else if(midas.visualize.sliceMode == 'XZ Plane') {
                var top = focus[2] + pscale;
                var bottom = focus[2] - pscale;
                var left = focus[0] + pscale;
                var right = focus[0] - pscale;
                x = (e.offsetX / $(this).width()) * (right - left) + left;
                y = midas.visualize.currentSlice + midas.visualize[side].bounds[2] - midas.visualize[side].extent[2];
                z = (e.offsetY / $(this).height()) * (bottom - top) + top;
            }
            else if(midas.visualize.sliceMode == 'YZ Plane') {
                var top = focus[2] + pscale;
                var bottom = focus[2] - pscale;
                var left = focus[0] - pscale;
                var right = focus[0] + pscale;
                x = midas.visualize.currentSlice + midas.visualize[side].bounds[0] - midas.visualize[side].extent[0];
                y = (e.offsetX / $(this).width()) * (right - left) + left;
                z = (e.offsetY / $(this).height()) * (bottom - top) + top;
            }

            var surfaceColor = midas.visualize.pointColors[midas.visualize[side].points.length % midas.visualize.pointColors.length];
            var params = {
                point: [x, y, z],
                color: surfaceColor,
                radius: midas.visualize[side].maxDim / 85.0, //make the sphere some small fraction of the image size
                input: midas.visualize[side].input
            };
            paraview[side].plugins.midasdual.AsyncShowSphere(function (retVal) {
                midas.visualize[side].points.push({
                    object: retVal.glyph,
                    color: retVal.surfaceColor,
                    radius: retVal.radius,
                    x: x,
                    y: y,
                    z: z
                });
                midas.visualize.forceRefreshView(side);
            }, params);
        });
    });
};

/**
 * Force the renderer image to refresh from the server
 */
midas.visualize.forceRefreshView = function (side) {
    var el = $('#'+side+'Renderer');
    updateRendererSize(paraview[side].sessionId,
                       midas.visualize[side].activeView.__selfid__, el.width(), el.height());
};

/**
 * Enable point selection action
 */
midas.visualize._enablePointMap = function () {
    var button = $('#actionButtonTemplate').clone();
    button.removeAttr('id');
    button.addClass('pointSelectButton');
    button.appendTo('#rightRendererOverlay');
    button.qtip({
        content: 'Select points in the images'
    });
    button.show();

    button.click(function () {
        button.removeClass('actionInactive').addClass('actionActive');
        midas.visualize.pointMapMode();
    });

    var listButton = $('#actionButtonTemplate').clone();
    listButton.removeAttr('id');
    listButton.addClass('pointMapListButton');
    listButton.appendTo('#rightRendererOverlay');
    listButton.qtip({
        content: 'Show selected point map'
    });
    listButton.show();
    
    listButton.click(function () {
        midas.visualize.displayPointMap();
    });

    var camLinkButton = $('#actionButtonTemplate').clone();
    camLinkButton.removeAttr('id');
    camLinkButton.addClass('cameraLinkButton');
    camLinkButton.appendTo('#rightRendererOverlay');
    camLinkButton.qtip({
        content: 'Lock with left side camera'
    });
    camLinkButton.show();
    
    camLinkButton.click(function () {
        midas.visualize.camerasLocked = !midas.visualize.camerasLocked;
        if(midas.visualize.camerasLocked) {
            camLinkButton.removeClass('actionInactive').addClass('actionActive');
            midas.visualize.setCameraMode('left');
        }
        else {
            camLinkButton.addClass('actionInactive').removeClass('actionActive');
            midas.visualize.setCameraMode('right');
        }
    });
};

/**
 * Apply default camera parameters from the left side to the right, or use the right side defaults
 */
midas.visualize.setCameraMode = function (side) {
    midas.visualize.right.cameraFocalPoint = 
      [midas.visualize[side].midI, midas.visualize[side].midJ, midas.visualize[side].midK];
    midas.visualize.right.cameraPosition = 
      [midas.visualize[side].midI, midas.visualize[side].midJ, midas.visualize[side].bounds[4] - 10];
    midas.visualize.right.cameraParallelScale = 
      Math.max(midas.visualize[side].bounds[1] - midas.visualize[side].bounds[0],
      midas.visualize[side].bounds[3] - midas.visualize[side].bounds[2]) / 2.0;

    var params = {
        cameraFocalPoint: midas.visualize.right.cameraFocalPoint,
        cameraPosition: midas.visualize.right.cameraPosition,
        cameraParallelScale: midas.visualize.right.cameraParallelScale
    };

    paraview.right.plugins.midascommon.AsyncMoveCamera(function () {
        midas.visualize.forceRefreshView('right');
    }, params);
};

/**
 * Enable the specified set of operations in the view
 * Options:
 *   -pointMap: select a single point in the image
 */
midas.visualize.enableActions = function (side, operations) {
    if(side == 'right') {
        $.each(operations, function(k, operation) {
            if(operation == 'pointMap') {
                midas.visualize._enablePointMap();
            }
            else if(operation != '') {
                alert('Unsupported operation: '+operation);
            }
        });
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
        midas.visualize.forceRefreshView(side);
    }, params);
};

/**
 * Display all the fiducial points selected in each image
 */
midas.visualize.displayPointMap = function () {
    var dialog = $('#pointListDialogTemplate').clone();
    dialog.removeAttr('id');
    midas.showDialogWithContent('Fiducial point mapping',
          dialog.html(), false, {modal: false, width: 460});
    var container = $('div.MainDialog');
    var tbody = container.find('.pointListTable tbody');

    $.each(midas.visualize.left.points, function(idx, point) {
        var rightPoint = midas.visualize.right.points[idx];
        var tr = '<tr><td><div class="colorSwatchL"></div><span class="leftPointValue">('+point.x.toFixed(1)+', '+
          point.y.toFixed(1)+', '+point.z.toFixed(1)+')</span></td><td><span class="rightPointValue">';
        if(rightPoint == undefined) {
            tr += '<i>None</i>';
        }
        else {
            tr += '<div class="colorSwatchR"></div>('+
              midas.visualize.right.points[idx].x.toFixed(1)+', '+
              midas.visualize.right.points[idx].y.toFixed(1)+', '+
              midas.visualize.right.points[idx].z.toFixed(1)+')';
        }
        tr += '</span></td><td class="pointMapActions"><button class="highlightPoints';
        if(point.highlighted) {
            tr += ' highlightOn';
        }
        tr += '">Highlight</button>'+
              '<button class="deletePoints">Delete</buttons>';
        tr = $(tr); //create element from the html
        tr.find('div.colorSwatchL').css('background-color',
          'rgb('+Math.round(point.color[0]*255)+','+Math.round(point.color[1]*255)+','+
          Math.round(point.color[2]*255)+')');
        if(rightPoint != undefined) {
            tr.find('div.colorSwatchR').css('background-color',
              'rgb('+Math.round(rightPoint.color[0]*255)+','+
              Math.round(rightPoint.color[1]*255)+','+
              Math.round(rightPoint.color[2]*255)+')');
        }

        // Bind delete button
        tr.find('button.deletePoints').click(function () {
            tr.remove();
            paraview.left.plugins.midascommon.AsyncDeleteSource(function () {
                midas.visualize.forceRefreshView('left');
            }, {source: point.object});

            midas.visualize._removePointFromList('left', point);

            if(rightPoint != undefined) {
                paraview.right.plugins.midascommon.AsyncDeleteSource(function () {
                    midas.visualize.forceRefreshView('right');
                }, {source: rightPoint.object});

                midas.visualize._removePointFromList('right', point);
            }
        });

        // Bind highlight button
        tr.find('button.highlightPoints').click(function () {
            if($(this).hasClass('highlightOn')) {
               $(this).removeClass('highlightOn');
               midas.visualize.left.points[idx].highlighted = false;
               paraview.left.plugins.midasdual.AsyncUpdateSphere(function () {
                  midas.visualize.forceRefreshView('left');
               }, {
                   radius: point.radius,
                   source: point.object
               });

               if(rightPoint) {
                  midas.visualize.right.points[idx].highlighted = false;
                  paraview.right.plugins.midasdual.AsyncUpdateSphere(function () {
                      midas.visualize.forceRefreshView('right');
                  }, {
                      radius: rightPoint.radius,
                      source: rightPoint.object
                  });
               }
            }
            else {
               $(this).addClass('highlightOn');
               midas.visualize.left.points[idx].highlighted = true;
               paraview.left.plugins.midasdual.AsyncUpdateSphere(function () {
                  midas.visualize.forceRefreshView('left');
               }, {
                   radius: point.radius * 2.5,
                   source: point.object
               });

               if(rightPoint) {
                  midas.visualize.right.points[idx].highlighted = true;
                  paraview.right.plugins.midasdual.AsyncUpdateSphere(function () {
                      midas.visualize.forceRefreshView('right');
                  }, {
                      radius: rightPoint.radius * 2.5,
                      source: rightPoint.object
                  });
               }
            }
        });
        tbody.append(tr);
    });
    container.find('button.processButton').unbind('click').click(function () {
        if(typeof midas.visualize.processPointMapHandler == 'function') {
            midas.visualize.processPointMapHandler();
        }
        else {
            midas.createNotice('No point map processing handler has been registered', 3000, 'warning');
        }
    });
    container.find('button.closeButton').click(function() {
        $('div.MainDialog').dialog('close');
    });
};

/**
 * Helper function to remove a point from the fiducial lists at global scope
 */
midas.visualize._removePointFromList = function(side, pointToRemove) {
    var spliceIdx = -1;
    $.each(midas.visualize[side].points, function(idx, point) {
        if(point.object.__selfid__ == pointToRemove.object.__selfid__) {
            spliceIdx = idx;
        }
    });
    midas.visualize[side].points.splice(spliceIdx, 1);
};

$(window).load(function () {
    if(typeof midas.visualize.preInitCallback == 'function') {
        midas.visualize.preInitCallback();
    }

    json = jQuery.parseJSON($('div.jsonContent').html());
    midas.visualize.start();
});

$(window).unload(function () {
    paraview.left.disconnect();
    paraview.right.disconnect();
});

