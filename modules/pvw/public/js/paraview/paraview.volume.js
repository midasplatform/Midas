var paraview, pv;
var midas = midas || {};
midas.visualize = midas.visualize || {};
midas.pvw = midas.pvw || {};

midas.pvw.IDLE_TIMEOUT = 10 * 60 * 1000; // 10 minute idle timeout

/**
 * Async callback from the plugin's Initialize function.
 * Sets the return variables in the javascript global scope
 * for use in other functions, and starts the render window
 */
midas.visualize.initCallback = function (view, retVal) {
    midas.visualize.sof = retVal.sof;
    midas.visualize.lookupTable = retVal.lookupTable;
    midas.visualize.activeView = retVal.activeView;

    midas.visualize.switchRenderer(true); // render in the div
    $('img.visuLoading').hide();
    $('#loadingStatus').html('').hide();
    $('#renderercontainer').show();

    midas.visualize.populateInfo();
    midas.visualize.setupExtractSubgrid();
    midas.visualize.setupScalarOpacity();
    midas.visualize.setupColorMapping();
    midas.visualize.setupOverlay();
    midas.visualize.setupObjectList();

    if(typeof midas.visualize.postInitCallback == 'function') {
        midas.visualize.postInitCallback();
    }

    midas.visualize.renderers.current.updateServerSizeIfNeeded(); //force a view refresh
    midas.visualize.forceRefreshView();
}

/**
 * Display the subset of the volume defined by the bounds list
 * of the form [xMin, xMax, yMin, yMax, zMin, zMax]
 */
midas.pvw.renderSubgrid = function (bounds) {
    midas.pvw.subgridBounds = bounds;
    var container = $('div.MainDialog');
    container.find('img.extractInProgress').show();
    container.find('button.extractSubgridApply').attr('disabled', 'disabled');
    pv.connection.session.call('pv:extractSubgrid', bounds)
                               .then(function (resp) {
                                    pv.viewport.render();
                                    container.find('img.extractInProgress').hide();
                                    container.find('button.extractSubgridApply').removeAttr('disabled');
                                    $('div.MainDialog').dialog('close');
                                })
                               .otherwise(midas.pvw.rpcFailure);
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
 * Get the plot data from the scalar opacity function
 */
midas.visualize.getSofCurve = function () {
    var points = paraview.GetProperty(midas.visualize.sof, 'Points');
    var curve = [];
    for(var i = 0; i < points.length; i++) {
        curve[i] = [points[4*i], points[4*i+1]];
    }
    return curve;
};

/**
 * Setup the object list widget
 */
midas.visualize.setupObjectList = function () {
    var dialog = $('#objectListTemplate').clone();
    dialog.removeAttr('id');
    $('#objectListAction').click(function () {
        midas.showDialogWithContent('Objects in the scene',
          dialog.html(), false, {modal: false, width: 430});
        var container = $('div.MainDialog');
        var objectList = container.find('table.objectList tbody');
        var html = '<tr><td><input type="checkbox" vis="volume" element="'+json.visualize.item.item_id+'" ';
        if(json.visualize.visible) {
            html += 'checked="checked" ';
        }
        html += ' /></td><td>Volume</td><td>'+json.visualize.item.name+'</td></tr>';
        objectList.append(html);
        $.each(json.visualize.meshes, function(idx, mesh) {
            var html = '<tr><td><input type="checkbox" vis="surface" element="'+mesh.item.item_id+'" ';
            if(mesh.visible) {
                html += 'checked="checked" ';
            }
            html += ' /></td><td>Surface</td><td>'+mesh.item.name+'</td></tr>';
            objectList.append(html);
        });

        container.find('button.objectListClose').click(function () {
            $('div.MainDialog').dialog('close');
        });
        container.find('button.objectListApply').click(function () {
            $.each(objectList.find('input[type=checkbox]'), function(idx, checkbox) {
                midas.visualize.toggleObjectVisibility($(checkbox));
            });
        });
    });
};

/**
 * Force the renderer image to refresh from the server
 */
midas.visualize.forceRefreshView = function () {
    midas.visualize.renderers.js.forceRefresh();
};

midas.visualize.toggleObjectVisibility = function(checkbox) {
    var type = checkbox.attr('vis');
    var itemId = checkbox.attr('element');
    var proxy;
    if(type == 'volume') {
        if(midas.visualize.subgrid) {
            proxy = midas.visualize.subgrid;
        }
        else {
            proxy = midas.visualize.input;
        }
    }
    else if(type == 'surface') {
        $.each(midas.visualize.meshes, function(k, mesh) {
            if(mesh.item.item_id == itemId) {
                proxy = mesh.source;
                mesh.visible = checkbox.is(':checked');
            }
        });
    }

    if(checkbox.is(':checked')) {
        paraview.Show({proxy: proxy});
    }
    else {
        paraview.Hide({proxy: proxy});
    }
    midas.visualize.forceRefreshView();
};

/**
 * Setup the color mapping controls
 */
midas.visualize.setupColorMapping = function () {
    var dialog = $('#scmDialogTemplate').clone();
    dialog.removeAttr('id');
    $('#scmEditAction').click(function () {
        midas.showDialogWithContent('Scalar color mapping',
          dialog.html(), false, {modal: false, width: 360});
        var container = $('div.MainDialog');
        var pointListDiv = container.find('div.rgbPointList');

        function renderPointList (colorMap) {
            for(var i = 0; i < colorMap.length; i += 4) {
                var rgbPoint = $('#scmPointMapTemplate').clone();
                var r = Math.round(255*colorMap[i+1]);
                var g = Math.round(255*colorMap[i+2]);
                var b = Math.round(255*colorMap[i+3])
                rgbPoint.removeAttr('id').appendTo(pointListDiv).show();
                rgbPoint.find('input.scmScalarValue').val(colorMap[i]);
                if(i < 8) { // first two values must be present (min and max)
                    rgbPoint.find('input.scmScalarValue').attr('disabled', 'disabled');
                }
                else {
                    rgbPoint.find('button.scmDeletePoint').show().click(function () {
                        $(this).parents('div.rgbPointContainer').remove();
                    });
                }
                rgbPoint.find('.scmColorPicker').ColorPicker({
                    color: {
                        r: r,
                        g: g,
                        b: b
                    },
                    onSubmit: function(hsb, hex, rgb, el) {
                        $(el).css('background-color', 'rgb('+rgb.r+','+rgb.g+','+rgb.b+')');
                    }
                }).css('background-color', 'rgb('+r+','+g+','+b+')');
            }
        };
        renderPointList(midas.visualize.colorMap);

        container.find('button.scmAddPoint').unbind('click').click(function () {
            var rgbPoint = $('#scmPointMapTemplate').clone();
            rgbPoint.removeAttr('id').appendTo(pointListDiv).show();
            rgbPoint.find('input.scmScalarValue').val(midas.visualize.defaultColorMap[0]);
            rgbPoint.removeAttr('id').appendTo(pointListDiv).show();
            rgbPoint.find('button.scmDeletePoint').show().click(function () {
                rgbPoint.remove();
            });
            rgbPoint.find('.scmColorPicker').ColorPicker({
                color: {
                    r: 0,
                    g: 0,
                    b: 0
                },
                onSubmit: function(hsb, hex, rgb, el) {
                    $(el).css('background-color', 'rgb('+rgb.r+','+rgb.g+','+rgb.b+')');
                }
            }).css('background-color', 'rgb(0, 0, 0)');
        });

        container.find('button.scmClose').unbind('click').click(function () {
            container.dialog('close');
        });
        container.find('button.scmReset').unbind('click').click(function () {
            pointListDiv.html('');
            renderPointList(midas.visualize.defaultColorMap);
        });
        container.find('button.scmApply').unbind('click').click(function () {
            var colorMap = [];
            $.each(pointListDiv.find('div.rgbPointContainer'), function(idx, template) {
                var scalar = parseFloat($(template).find('input.scmScalarValue').val());
                var tokens = $(template).find('div.scmColorPicker')
                  .css('background-color').match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
                colorMap.push(scalar, parseFloat(tokens[1]) / 255.0, parseFloat(tokens[2]) / 255.0,
                  parseFloat(tokens[3]) / 255.0);
            });
            midas.visualize.colorMap = colorMap;
            paraview.callPluginMethod('midascommon', 'UpdateColorMap', {
                colorArrayName: json.visualize.colorArrayName,
                colorMap: colorMap
            } , function() {
                midas.visualize.forceRefreshView();
            });
        });
    });
};

/**
 * Setup the scalar opacity function controls
 */
midas.visualize.setupScalarOpacity = function () {
    var dialog = $('#sofDialogTemplate').clone();
    dialog.removeAttr('id');
    $('#sofEditAction').click(function () {
        midas.showDialogWithContent('Scalar opacity function',
          dialog.html(), false, {modal: false, width: 500});
        var container = $('div.MainDialog');
        container.find('div.sofPlot').attr('id', 'sofChartDiv');

        midas.visualize.sofPlot = $.jqplot('sofChartDiv', [midas.visualize.getSofCurve()], {
            series:[{showMarker:true}],
            axes: {
                xaxis: {
                    min: midas.visualize.minVal,
                    max: midas.visualize.maxVal,
                    label: 'Scalar value',
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    labelOptions: {
                        fontSize: '8pt'
                    }
                },
                yaxis: {
                    min: 0,
                    max: 1,
                    label: 'Opacity',
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    labelOptions: {
                        angle: 270,
                        fontSize: '8pt'
                    },
                    tickInterval: 1.0
                }
            },
            grid: {
                drawGridlines: false
            },
            cursor: {
                show: true,
                style: 'pointer',
                tooltipLocation:'se'
            },
            highlighter: {
                show: true,
                sizeAdjust: 7.5
            }
        });
        container.find('button.sofClose').click(function () {
            $('div.MainDialog').dialog('close');
        });
        container.find('button.sofApply').click(function () {
            midas.visualize.applySofCurve();
        });
        container.find('button.sofReset').click(function () {
            midas.visualize.sofPlot.series[0].data = [
              [midas.visualize.minVal, 0],
              [midas.visualize.maxVal, 1]];
            midas.visualize.sofPlot.replot();
            midas.visualize.setupSofPlotBindings();
            container.find('div.sofPointEdit').hide();
        });
        midas.visualize.setupSofPlotBindings();
    });
};

/**
 * Called when the "apply" button on the sof dialog is clicked;
 * updates the sof in paraview based on the jqplot curve
 */
midas.visualize.applySofCurve = function () {
    // Create the scalar opacity transfer function
    var points = [];
    var curve = midas.visualize.sofPlot.series[0].data;
    for(var idx in curve) {
        points.push(curve[idx][0], curve[idx][1], 0.5, 0.0);
    }

    midas.visualize.sof = paraview.CreatePiecewiseFunction({
        Points: points
    });

    paraview.SetDisplayProperties({
        ScalarOpacityFunction: midas.visualize.sof
    });
    midas.visualize.forceRefreshView();
};

/**
 * Must call this anytime a redraw or replot is called on the sof plot
 */
midas.visualize.setupSofPlotBindings = function () {

    // Clicking an existing point should let you change its values
    $('#sofChartDiv').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
        var container = $('div.MainDialog').find('div.sofPointEdit');
        container.find('input.scalarValueEdit').val(data[0]);
        container.find('input.opacityValueEdit').val(data[1]);
        container.show();

        container.find('button.pointUpdate').unbind('click').click(function () {
            var s = parseFloat(container.find('input.scalarValueEdit').val());
            var o = parseFloat(container.find('input.opacityValueEdit').val());
            midas.visualize.sofPlot.series[0].data[pointIndex] = [s, o];
            midas.visualize.sofPlot.replot();
            midas.visualize.setupSofPlotBindings();
        });
        container.find('button.pointDelete').unbind('click').click(function () {
            midas.visualize.sofPlot.series[0].data.splice(pointIndex, 1);
            midas.visualize.sofPlot.replot();
            midas.visualize.setupSofPlotBindings();
        });
    });

    // Clicking on the plot (except on an existing point) should add a new one
    $('#sofChartDiv').bind('jqplotClick', function (ev, seriesIndex, pointIndex, data) {
        if(data) {
            return; // we use the data click handler for this
        }
        $('div.MainDialog').find('div.sofPointEdit').hide();
        // insert new data point in between closest x-axis values
        var inserted = false;
        var newData = [midas.visualize.sofPlot.series[0].data[0]];

        for(var i = 1; i < midas.visualize.sofPlot.series[0].data.length; i++) {
            if(!inserted && pointIndex.xaxis < midas.visualize.sofPlot.series[0].data[i][0]) {
                inserted = true;
                newData.push([pointIndex.xaxis, pointIndex.yaxis]);
            }
            newData.push(midas.visualize.sofPlot.series[0].data[i]);
        }
        if(!inserted) {
            newData.push([pointIndex.xaxis, pointIndex.yaxis]);
        }
        midas.visualize.sofPlot.series[0].data = newData;
        midas.visualize.sofPlot.replot();
        midas.visualize.setupSofPlotBindings();
    });
};

/**
 * Setup the extract subgrid controls
 */
midas.pvw.setupExtractSubgrid = function () {
    var dialog = $('#extractSubgridDialogTemplate').clone();
    dialog.removeAttr('id');
    $('#extractSubgridAction').click(function () {
        midas.showDialogWithContent('Select subgrid bounds',
          dialog.html(), false, {modal: false, width: 560});
        var container = $('div.MainDialog');
        container.find('.sliderX').slider({
            range: true,
            min: midas.pvw.bounds[0],
            max: midas.pvw.bounds[1],
            values: [midas.pvw.bounds[0], midas.pvw.bounds[1]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinX').val(ui.values[0]);
                container.find('.extractSubgridMaxX').val(ui.values[1]);
            }
        });
        container.find('.sliderY').slider({
            range: true,
            min: midas.pvw.bounds[2],
            max: midas.pvw.bounds[3],
            values: [midas.pvw.bounds[2], midas.pvw.bounds[3]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinY').val(ui.values[0]);
                container.find('.extractSubgridMaxY').val(ui.values[1]);
            }
        });
        container.find('.sliderZ').slider({
            range: true,
            min: midas.pvw.bounds[4],
            max: midas.pvw.bounds[5],
            values: [midas.pvw.bounds[4], midas.pvw.bounds[5]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinZ').val(ui.values[0]);
                container.find('.extractSubgridMaxZ').val(ui.values[1]);
            }
        });

        // setup spinboxes with feedback into the range sliders
        container.find('.extractSubgridMinX').spinbox({
            min: midas.pvw.bounds[0],
            max: midas.pvw.bounds[1]
        }).change(function () {
            container.find('.sliderX').slider('option', 'values',
              [$(this).val(), container.find('.extractSubgridMaxX').val()]);
        }).val(midas.pvw.bounds[0]);
        container.find('.extractSubgridMaxX').spinbox({
            min: midas.pvw.bounds[0],
            max: midas.pvw.bounds[1]
        }).change(function () {
            container.find('.sliderX').slider('option', 'values',
              [container.find('.extractSubgridMinX').val(), $(this).val()]);
        }).val(midas.pvw.bounds[1]);
        container.find('.extractSubgridMinY').spinbox({
            min: midas.pvw.bounds[2],
            max: midas.pvw.bounds[3]
        }).change(function () {
            container.find('.sliderY').slider('option', 'values',
              [$(this).val(), container.find('.extractSubgridMaxY').val()]);
        }).val(midas.pvw.bounds[2]);
        container.find('.extractSubgridMaxY').spinbox({
            min: midas.pvw.bounds[2],
            max: midas.pvw.bounds[3]
        }).change(function () {
            container.find('.sliderY').slider('option', 'values',
              [container.find('.extractSubgridMinY').val(), $(this).val()]);
        }).val(midas.pvw.bounds[3]);
        container.find('.extractSubgridMinZ').spinbox({
            min: midas.pvw.bounds[4],
            max: midas.pvw.bounds[5]
        }).change(function () {
            container.find('.sliderZ').slider('option', 'values',
              [$(this).val(), container.find('.extractSubgridMaxZ').val()]);
        }).val(midas.pvw.bounds[4]);
        container.find('.extractSubgridMaxZ').spinbox({
            min: midas.pvw.bounds[4],
            max: midas.pvw.bounds[5]
        }).change(function () {
            container.find('.sliderZ').slider('option', 'values',
              [container.find('.extractSubgridMinZ').val(), $(this).val()]);
        }).val(midas.pvw.bounds[5]);

        // setup button actions
        container.find('button.extractSubgridApply').click(function () {
            midas.pvw.renderSubgrid([
              parseInt(container.find('.extractSubgridMinX').val()),
              parseInt(container.find('.extractSubgridMaxX').val()),
              parseInt(container.find('.extractSubgridMinY').val()),
              parseInt(container.find('.extractSubgridMaxY').val()),
              parseInt(container.find('.extractSubgridMinZ').val()),
              parseInt(container.find('.extractSubgridMaxZ').val())
            ]);
        });
        container.find('button.extractSubgridClose').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });
};

/**
 * Call this with setInterval to regularly test if the
 * user has been idle for too long, and if so, kill the pvw session
 */
midas.pvw.testIdle = function () {
    var curr = new Date().getTime();
    if(curr - midas.pvw.lastAction > midas.pvw.IDLE_TIMEOUT) {
        midas.pvw.stopSession();
    }
};

midas.pvw.start = function () {
    if(typeof midas.visualize.preInitCallback == 'function') {
        midas.visualize.preInitCallback();
    }
    pv = {};
    pv.connection = {
        sessionURL: 'ws://'+location.hostname+':'+midas.pvw.instance.port+'/ws',
        id: midas.pvw.instance.instance_id,
        sessionManagerURL: json.global.webroot + '/pvw/paraview/instance'
    };
    paraview.connect(pv.connection, function(conn) {
        pv.connection = conn;
        window.session = pv.connection.session;
        pv.viewport = paraview.createViewport(pv.connection);
        pv.viewport.bind('#renderercontainer');

        $('#renderercontainer').show();
        $('img.visuLoading').hide();

        midas.pvw.waitingDialog('Loading data into scene...');
        pv.connection.session.call('pv:loadData')
                             .then(midas.pvw.dataLoaded)
                             .otherwise(midas.pvw.rpcFailure);
    }, function(msg) {
        midas.createNotice('Error: ' + msg, 3000, 'error');
    });

    // Add some logic to check for idle and close the pvw session after IDLE_TIMEOUT expires
    midas.pvw.lastAction = new Date().getTime();
    midas.pvw.idleInterval = setInterval(midas.pvw.testIdle, 15000); // every 15 seconds, check idle status
    $('body').mousemove(function () {
        midas.pvw.lastAction = new Date().getTime();
    });
};

midas.pvw.rpcFailure = function (err) {
    $('div.MainDialog').dialog('close');
    console.log(err);
    midas.createNotice('A ParaViewWeb exception occurred, check your browser console', 4000, 'error');
};

/** Callback for once the loadData RPC has returned */
midas.pvw.dataLoaded = function (resp) {
    midas.pvw.mainProxy = resp;
    pv.viewport.render();
    midas.pvw.waitingDialog('Starting volume rendering...');
    pv.connection.session.call('pv:volumeRender')
                         .then(midas.pvw.vrStarted)
                         .otherwise(midas.pvw.rpcFailure);
};

/** After volume rendering has started successfully, this gets called */
midas.pvw.vrStarted = function (resp) {
    midas.pvw.bounds = resp.bounds;
    midas.pvw.subgridBounds = resp.bounds;
    midas.pvw.scalarRange = resp.scalarRange;
    midas.pvw.sof = resp.sofPoints;
    midas.pvw.rgbPoints = resp.rgbPoints;

    pv.viewport.render();
    $('div.MainDialog').dialog('close');
    midas.pvw.populateInfo();
    midas.pvw.setupOverlay();
    midas.pvw.setupExtractSubgrid();
};

/** Bind the renderer overlay buttons */
midas.pvw.setupOverlay = function () {
    $('button.cameraPreset').click(function () {
        pv.connection.session.call('pv:cameraPreset', $(this).attr('type'))
                             .then(pv.viewport.render())
                             .otherwise(midas.pvw.rpcFailure);
    });
};

/**
 * Call this to kill the pvw session and print a helpful message about it
 * to the view.
 */
midas.pvw.stopSession = function () {
    if(pv.connection) {
        paraview.stop(pv.connection); // TODO must call this synchronously!!!
        pv.connection = null;
    }
    if(midas.pvw.idleInterval) {
        clearInterval(midas.pvw.idleInterval);
        midas.pvw.idleInterval = null;
    }
    var html = 'Your ParaViewWeb session was ended, either due to an error or because you went idle '
             + 'for more than ' + (midas.pvw.IDLE_TIMEOUT / 60000) + ' minutes.';
    $('#loadingStatus').html(html).show();
    $('#renderercontainer').hide();
};

/** Show an indeterminate loading dialog with a message */
midas.pvw.waitingDialog = function(text) {
    var html = '<img alt="" style="margin-right: 9px;" '+
               'src="'+json.global.coreWebroot+'/public/images/icons/loading.gif" /> ' + text;

    midas.showDialogWithContent('Please wait', html);
};
