// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var pv;
var midas = midas || {};
midas.pvw = midas.pvw || {};

midas.pvw.PRESET_TRANSFER_RGBPOINTS = {
    "Grayscale": [0.0, 0, 0, 0,
        1.0, 1, 1, 1
    ],
    "X-Ray": [0.0, 1, 1, 1,
        1.0, 0, 0, 0
    ],
    "Rainbow": [0.0, 1.0, 0.0, 0.0,
        0.166667, 1.0, 0.0, 1.0,
        0.333333, 0.0, 0.0, 1.0,
        0.5, 0.0, 1.0, 1.0,
        0.666667, 0.0, 1.0, 0.0,
        0.833333, 1.0, 1.0, 0.0,
        1.0, 1.0, 0.0, 0.0
    ],
    "Rainbow (Desaturated)": [0.0, 0.2784313725490196, 0.2784313725490196, 0.8588235294117647,
        0.1428, 0.0, 0.0, 0.3607843137254902,
        0.2857, 0.0, 1.0, 1.0,
        0.4286, 0.0, 0.5019607843137255, 0.0,
        0.5714, 1.0, 1.0, 0.0,
        0.7143, 1.0, 0.3803921568627451, 0.0,
        0.8571, 0.4196078431372549, 0.0, 0.0,
        1.0, 0.8784313725490196, 0.30196078431372547, 0.30196078431372547
    ],
    "Yellow-Orange-Brown": [0, 1.0, 1.0, 0.8313725490196079,
        0.33333, 0.996078431372549, 0.8509803921568627, 0.5568627450980392,
        0.66667, 0.996078431372549, 0.6, 0.1607843137254902,
        1.0, 0.8, 0.2980392156862745, 0.00784313725490196
    ],
    "Qualitative Accent 1": [0, 0.4980392156862745, 0.788235294117647, 0.4980392156862745,
        0.1428, 0.7450980392156863, 0.6823529411764706, 0.8313725490196079,
        0.2857, 0.9921568627450981, 0.7529411764705882, 0.5254901960784314,
        0.4286, 1.0, 1.0, 0.6,
        0.5714, 0.2196078431372549, 0.4235294117647059, 0.6901960784313725,
        0.7143, 0.9411764705882353, 0.00784313725490196, 0.4980392156862745,
        0.8571, 0.7490196078431373, 0.3568627450980392, 0.09019607843137255,
        1.0, 0.4, 0.4, 0.4
    ],
    "Qualitative Accent 2": [0, 0.4, 0.7607843137254902, 0.6470588235294118,
        0.1428, 0.9882352941176471, 0.5529411764705883, 0.3843137254901961,
        0.2857, 0.5529411764705883, 0.6274509803921569, 0.796078431372549,
        0.4286, 0.9058823529411765, 0.5411764705882353, 0.7647058823529411,
        0.5714, 0.6509803921568628, 0.8470588235294118, 0.32941176470588235,
        0.7143, 1.0, 0.8509803921568627, 0.1843137254901961,
        0.8571, 0.8980392156862745, 0.7686274509803922, 0.5803921568627451,
        1.0, 0.7019607843137254, 0.7019607843137254, 0.7019607843137254
    ]
};

/**
 * Display the subset of the volume defined by the bounds list
 * of the form [xMin, xMax, yMin, yMax, zMin, zMax]
 */
midas.pvw.renderSubgrid = function (bounds) {
    'use strict';
    midas.pvw.subgridBounds = bounds;
    var container = $('div.MainDialog');
    container.find('img.extractInProgress').show();
    container.find('button.extractSubgridApply').attr('disabled', 'disabled');
    pv.connection.session.call('vtk:extractSubgrid', bounds)
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
    'use strict';
    $('#boundsXInfo').html(midas.pvw.bounds[0] + ' .. ' + midas.pvw.bounds[1]);
    $('#boundsYInfo').html(midas.pvw.bounds[2] + ' .. ' + midas.pvw.bounds[3]);
    $('#boundsZInfo').html(midas.pvw.bounds[4] + ' .. ' + midas.pvw.bounds[5]);
    $('#scalarRangeInfo').html(midas.pvw.scalarRange[0] + ' .. ' + midas.pvw.scalarRange[1]);
};

/**
 * Setup the object list widget
 */
/*
midas.visualize.setupObjectList = function () {
    'use strict';
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
};*/

midas.pvw._setupColorPresets = function (container) {
    'use strict';
    var presetSelect = container.find('select.scmPresets');
    var html = '<option value="">Use preset...</option>';
    $.each(midas.pvw.PRESET_TRANSFER_RGBPOINTS, function (name, points) {
        html += '<option value="' + name + '">' + name + '</option>';
    });
    presetSelect.html(html);
    presetSelect.change(function () {
        midas.pvw.changeColorPreset(container, $(this));
    });
};

midas.pvw.changeColorPreset = function (container, select) {
    'use strict';
    var name = select.val();
    if (name == '') {
        return;
    }
    var colorList = midas.pvw.PRESET_TRANSFER_RGBPOINTS[name];
    if ((colorList.length % 4) !== 0) {
        alert('Invalid color list length: ' + name);
    }
    // Map points into the actual scalar range
    var modifiedColorList = [];
    var range = midas.pvw.scalarRange[1] - midas.pvw.scalarRange[0];
    for (var i = 0; i < colorList.length; i += 4) {
        var interpScalar = midas.pvw.scalarRange[0] + colorList[i] * range;
        modifiedColorList.push(interpScalar, colorList[i + 1], colorList[i + 2], colorList[i + 3]);
    }
    midas.pvw.renderPointList(modifiedColorList);
};

/**
 * Setup the color mapping controls
 */
midas.pvw.setupColorMapping = function () {
    'use strict';
    var dialog = $('#scmDialogTemplate').clone();
    dialog.removeAttr('id');
    $('#scmEditAction').click(function () {
        midas.showDialogWithContent('Scalar color mapping',
            dialog.html(), false, {
                modal: false,
                width: 360
            });
        var container = $('div.MainDialog');
        var pointListDiv = container.find('div.rgbPointList');
        midas.pvw._setupColorPresets(container);

        midas.pvw.renderPointList = function (colorMap) {
            pointListDiv.html(''); // clear any existing points out
            for (var i = 0; i < colorMap.length; i += 4) {
                var rgbPoint = $('#scmPointMapTemplate').clone();
                var r = Math.round(255 * colorMap[i + 1]);
                var g = Math.round(255 * colorMap[i + 2]);
                var b = Math.round(255 * colorMap[i + 3]);
                rgbPoint.removeAttr('id').appendTo(pointListDiv).show();
                rgbPoint.find('input.scmScalarValue').val(colorMap[i]);
                rgbPoint.find('button.scmDeletePoint').show().click(function () {
                    $(this).parents('div.rgbPointContainer').remove();
                });
                rgbPoint.find('.scmColorPicker').ColorPicker({
                    color: {
                        r: r,
                        g: g,
                        b: b
                    },
                    onSubmit: function (hsb, hex, rgb, el) {
                        $(el).css('background-color', 'rgb(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ')');
                        $(el).ColorPickerHide();
                    }
                }).css('background-color', 'rgb(' + r + ',' + g + ',' + b + ')');
            }
        };
        midas.pvw.renderPointList(midas.pvw.colorMap);

        container.find('button.scmAddPoint').unbind('click').click(function () {
            var rgbPoint = $('#scmPointMapTemplate').clone();
            rgbPoint.removeAttr('id').appendTo(pointListDiv).show();
            rgbPoint.find('input.scmScalarValue').val(midas.pvw.defaultColorMap[0]);
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
                onSubmit: function (hsb, hex, rgb, el) {
                    $(el).css('background-color', 'rgb(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ')');
                    $(el).ColorPickerHide();
                }
            }).css('background-color', 'rgb(0, 0, 0)');
        });

        container.find('button.scmClose').unbind('click').click(function () {
            container.dialog('close');
        });
        container.find('button.scmReset').unbind('click').click(function () {
            pointListDiv.html('');
            midas.pvw.renderPointList(midas.pvw.defaultColorMap);
        });
        container.find('button.scmApply').unbind('click').click(function () {
            var colorMap = [];
            $.each(pointListDiv.find('div.rgbPointContainer'), function (idx, template) {
                var scalar = parseFloat($(template).find('input.scmScalarValue').val());
                var tokens = $(template).find('div.scmColorPicker')
                    .css('background-color').match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
                colorMap.push(scalar, parseFloat(tokens[1]) / 255.0, parseFloat(tokens[2]) / 255.0,
                    parseFloat(tokens[3]) / 255.0);
            });
            midas.pvw.colorMap = colorMap;
            pv.connection.session.call('vtk:updateColorMap', colorMap)
                .then(function () {
                    pv.viewport.render();
                })
                .otherwise(midas.pvw.rpcFailure);
        });
    });
};

/**
 * Setup the scalar opacity function controls
 */
midas.pvw.setupScalarOpacity = function () {
    'use strict';
    var dialog = $('#sofDialogTemplate').clone();
    dialog.removeAttr('id');
    $('#sofEditAction').click(function () {
        midas.showDialogWithContent('Scalar opacity function',
            dialog.html(), false, {
                modal: false,
                width: 500
            });
        var container = $('div.MainDialog');
        container.find('div.sofPlot').attr('id', 'sofChartDiv');

        midas.pvw.sofPlot = $.jqplot('sofChartDiv', [midas.pvw.getSofCurve(midas.pvw.sof)], {
            series: [{
                showMarker: true
            }],
            axes: {
                xaxis: {
                    min: midas.pvw.scalarRange[0],
                    max: midas.pvw.scalarRange[1],
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
                tooltipLocation: 'se'
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
            midas.pvw.applySofCurve();
        });
        container.find('button.sofReset').click(function () {
            midas.pvw.sofPlot.series[0].data = [
                [midas.pvw.scalarRange[0], 0],
                [midas.pvw.scalarRange[1], 1]
            ];
            midas.pvw.sofPlot.replot();
            midas.pvw.setupSofPlotBindings();
            container.find('div.sofPointEdit').hide();
        });
        midas.pvw.setupSofPlotBindings();
    });
};

/**
 * Get the plot data from the scalar opacity function
 */
midas.pvw.getSofCurve = function (points) {
    'use strict';
    var curve = [];
    for (var i = 0; i < points.length; i++) {
        curve[i] = [points[4 * i], points[4 * i + 1]];
    }
    return curve;
};

/**
 * Called when the "apply" button on the sof dialog is clicked;
 * updates the sof in paraview based on the jqplot curve
 */
midas.pvw.applySofCurve = function () {
    'use strict';
    // Create the scalar opacity transfer function
    var points = [];
    var curve = midas.pvw.sofPlot.series[0].data;
    for (var idx in curve) {
        points.push(curve[idx][0], curve[idx][1], 0.5, 0.0);
    }

    midas.pvw.sof = points;
    pv.connection.session.call('vtk:updateSof', points)
        .then(function () {
            pv.viewport.render();
        })
        .otherwise(midas.pvw.rpcFailure);
};

/**
 * Must call this anytime a redraw or replot is called on the sof plot
 */
midas.pvw.setupSofPlotBindings = function () {
    'use strict';
    // Clicking an existing point should let you change its values
    $('#sofChartDiv').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
        var container = $('div.MainDialog').find('div.sofPointEdit');
        container.find('input.scalarValueEdit').val(data[0]);
        container.find('input.opacityValueEdit').val(data[1]);
        container.show();

        container.find('button.pointUpdate').unbind('click').click(function () {
            var s = parseFloat(container.find('input.scalarValueEdit').val());
            var o = parseFloat(container.find('input.opacityValueEdit').val());
            midas.pvw.sofPlot.series[0].data[pointIndex] = [s, o];
            midas.pvw.sofPlot.replot();
            midas.pvw.setupSofPlotBindings();
        });
        container.find('button.pointDelete').unbind('click').click(function () {
            midas.pvw.sofPlot.series[0].data.splice(pointIndex, 1);
            midas.pvw.sofPlot.replot();
            midas.pvw.setupSofPlotBindings();
        });
    });

    // Clicking on the plot (except on an existing point) should add a new one
    $('#sofChartDiv').bind('jqplotClick', function (ev, seriesIndex, pointIndex, data) {
        if (data) {
            return; // we use the data click handler for this
        }
        $('div.MainDialog').find('div.sofPointEdit').hide();
        // insert new data point in between closest x-axis values
        var inserted = false;
        var newData = [midas.pvw.sofPlot.series[0].data[0]];

        for (var i = 1; i < midas.pvw.sofPlot.series[0].data.length; i++) {
            if (!inserted && pointIndex.xaxis < midas.pvw.sofPlot.series[0].data[i][0]) {
                inserted = true;
                newData.push([pointIndex.xaxis, pointIndex.yaxis]);
            }
            newData.push(midas.pvw.sofPlot.series[0].data[i]);
        }
        if (!inserted) {
            newData.push([pointIndex.xaxis, pointIndex.yaxis]);
        }
        midas.pvw.sofPlot.series[0].data = newData;
        midas.pvw.sofPlot.replot();
        midas.pvw.setupSofPlotBindings();
    });
};

/**
 * Setup the extract subgrid controls
 */
midas.pvw.setupExtractSubgrid = function () {
    'use strict';
    var dialog = $('#extractSubgridDialogTemplate').clone();
    dialog.removeAttr('id');
    $('#extractSubgridAction').click(function () {
        midas.showDialogWithContent('Select subgrid bounds',
            dialog.html(), false, {
                modal: false,
                width: 560
            });
        var container = $('div.MainDialog');
        container.find('.sliderX').slider({
            range: true,
            min: midas.pvw.extent[0],
            max: midas.pvw.extent[1],
            values: [midas.pvw.subgridBounds[0], midas.pvw.subgridBounds[1]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinX').val(ui.values[0]);
                container.find('.extractSubgridMaxX').val(ui.values[1]);
            }
        });
        container.find('.sliderY').slider({
            range: true,
            min: midas.pvw.extent[2],
            max: midas.pvw.extent[3],
            values: [midas.pvw.subgridBounds[2], midas.pvw.subgridBounds[3]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinY').val(ui.values[0]);
                container.find('.extractSubgridMaxY').val(ui.values[1]);
            }
        });
        container.find('.sliderZ').slider({
            range: true,
            min: midas.pvw.extent[4],
            max: midas.pvw.extent[5],
            values: [midas.pvw.subgridBounds[4], midas.pvw.subgridBounds[5]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinZ').val(ui.values[0]);
                container.find('.extractSubgridMaxZ').val(ui.values[1]);
            }
        });

        // setup spinboxes with feedback into the range sliders
        container.find('.extractSubgridMinX').spinbox({
            min: midas.pvw.extent[0],
            max: midas.pvw.extent[1]
        }).change(function () {
            container.find('.sliderX').slider('option', 'values', [$(this).val(), container.find('.extractSubgridMaxX').val()]);
        }).val(midas.pvw.subgridBounds[0]);
        container.find('.extractSubgridMaxX').spinbox({
            min: midas.pvw.extent[0],
            max: midas.pvw.extent[1]
        }).change(function () {
            container.find('.sliderX').slider('option', 'values', [container.find('.extractSubgridMinX').val(), $(this).val()]);
        }).val(midas.pvw.subgridBounds[1]);
        container.find('.extractSubgridMinY').spinbox({
            min: midas.pvw.extent[2],
            max: midas.pvw.extent[3]
        }).change(function () {
            container.find('.sliderY').slider('option', 'values', [$(this).val(), container.find('.extractSubgridMaxY').val()]);
        }).val(midas.pvw.subgridBounds[2]);
        container.find('.extractSubgridMaxY').spinbox({
            min: midas.pvw.extent[2],
            max: midas.pvw.extent[3]
        }).change(function () {
            container.find('.sliderY').slider('option', 'values', [container.find('.extractSubgridMinY').val(), $(this).val()]);
        }).val(midas.pvw.subgridBounds[3]);
        container.find('.extractSubgridMinZ').spinbox({
            min: midas.pvw.extent[4],
            max: midas.pvw.extent[5]
        }).change(function () {
            container.find('.sliderZ').slider('option', 'values', [$(this).val(), container.find('.extractSubgridMaxZ').val()]);
        }).val(midas.pvw.subgridBounds[4]);
        container.find('.extractSubgridMaxZ').spinbox({
            min: midas.pvw.extent[4],
            max: midas.pvw.extent[5]
        }).change(function () {
            container.find('.sliderZ').slider('option', 'values', [container.find('.extractSubgridMinZ').val(), $(this).val()]);
        }).val(midas.pvw.subgridBounds[5]);

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

midas.pvw.start = function () {
    'use strict';
    if (typeof midas.pvw.preInitCallback == 'function') {
        midas.pvw.preInitCallback();
    }
    midas.pvw.loadData();
};

/** Callback for once the loadData RPC has returned */
midas.pvw.dataLoaded = function (resp) {
    'use strict';
    midas.pvw.mainProxy = resp;
    pv.viewport.render();
    midas.pvw.waitingDialog('Starting volume rendering...');
    pv.connection.session.call('vtk:volumeRender')
        .then(midas.pvw.vrStarted)
        .otherwise(midas.pvw.rpcFailure);
};

/** After volume rendering has started successfully, this gets called */
midas.pvw.vrStarted = function (resp) {
    'use strict';
    midas.pvw.bounds = resp.bounds;
    midas.pvw.extent = resp.extent;
    midas.pvw.subgridBounds = resp.extent;
    midas.pvw.scalarRange = resp.scalarRange;
    midas.pvw.sof = resp.sofPoints;
    midas.pvw.colorMap = resp.rgbPoints;
    midas.pvw.defaultColorMap = resp.rgbPoints;

    pv.viewport.render();
    $('div.MainDialog').dialog('close');
    midas.pvw.populateInfo();
    midas.pvw.setupOverlay();
    midas.pvw.setupExtractSubgrid();
    midas.pvw.setupScalarOpacity();
    midas.pvw.setupColorMapping();

    $('a.switchToSliceView').attr('href', json.global.webroot + '/pvw/paraview/slice' + encodeURIComponent(window.location.search));
};

/** Bind the renderer overlay buttons */
midas.pvw.setupOverlay = function () {
    'use strict';
    $('button.cameraPreset').click(function () {
        pv.connection.session.call('vtk:cameraPreset', $(this).attr('type'))
            .then(pv.viewport.render())
            .otherwise(midas.pvw.rpcFailure);
    });
};
