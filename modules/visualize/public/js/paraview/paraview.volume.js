var paraview;
var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.renderers = {};

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
    paraview.createSession("midas", "volume render", "default");

    midas.visualize.input = paraview.OpenDataFile({filename: file});
    paraview.Show();

    var imageData = paraview.GetDataInformation();
    midas.visualize.bounds = imageData.Bounds;
    midas.visualize.minVal = imageData.PointData.Arrays[0].Ranges[0][0];
    midas.visualize.maxVal = imageData.PointData.Arrays[0].Ranges[0][1];
    midas.visualize.imageWindow = [midas.visualize.minVal, midas.visualize.maxVal];

    midas.visualize.midI = (midas.visualize.bounds[0] + midas.visualize.bounds[1]) / 2.0;
    midas.visualize.midJ = (midas.visualize.bounds[2] + midas.visualize.bounds[3]) / 2.0;
    midas.visualize.midK = Math.floor((midas.visualize.bounds[4] + midas.visualize.bounds[5]) / 2.0);

    if(midas.visualize.bounds.length != 6) {
        console.log('Invalid image bounds:');
        console.log(midas.visualize.bounds);
        return;
    }

    midas.visualize.activeView = paraview.CreateIfNeededRenderView();
    midas.visualize.activeView.setCameraFocalPoint([midas.visualize.midI,
                                                    midas.visualize.midJ,
                                                    midas.visualize.midK]);
    midas.visualize.activeView.setCameraPosition([
      midas.visualize.midI + 1.5*midas.visualize.bounds[1],
      midas.visualize.midJ,
      midas.visualize.midK]);
    midas.visualize.activeView.setCameraViewUp([0.0, 0.0, 1.0]);
    midas.visualize.activeView.setCameraParallelProjection(false);
    midas.visualize.activeView.setCenterOfRotation(midas.visualize.activeView.getCameraFocalPoint());
    midas.visualize.activeView.setBackground([0.0, 0.0, 0.0]);
    midas.visualize.activeView.setBackground2([0.0, 0.0, 0.0]); //solid black background

    midas.visualize.lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    midas.visualize.lookupTable.setRGBPoints(
      [midas.visualize.minVal,
       0.0, 0.0, 0.0,
       midas.visualize.maxVal,
       1.0, 1.0, 1.0]); //initial transfer function def
    midas.visualize.lookupTable.setScalarRangeInitialized(1.0);
    midas.visualize.lookupTable.setColorSpace(0); // 0 corresponds to RGB

    // Create the scalar opacity transfer function
    midas.visualize.sof = paraview.CreatePiecewiseFunction({
        Points: [midas.visualize.minVal, 0.0, 0.5, 0.0,
                 midas.visualize.maxVal, 1.0, 0.75, 0.0]
    });

    paraview.SetDisplayProperties({
        view: midas.visualize.activeView,
        ScalarOpacityFunction: midas.visualize.sof,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage',
        LookupTable: midas.visualize.lookupTable
    });

    midas.visualize.switchRenderer(true); // render in the div
    $('img.visuLoading').hide();
    container.show();
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
 * Display the subset of the volume defined by the bounds list
 * of the form [xMin, xMax, yMin, yMax, zMin, zMax]
 */
midas.visualize.renderSubgrid = function (bounds) {
    if(midas.visualize.subgrid) {
      paraview.Hide({proxy: midas.visualize.subgrid});
    }
    paraview.SetActiveSource([midas.visualize.input]);
    midas.visualize.subgrid = paraview.ExtractSubset({
        VOI: bounds
    });
    paraview.SetDisplayProperties({
        proxy: midas.visualize.subgrid,
        view: midas.visualize.activeView,
        ScalarOpacityFunction: midas.visualize.sof,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage',
        LookupTable: midas.visualize.lookupTable
    });
    paraview.Hide({proxy: midas.visualize.input});
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
 * Setup the actions for volume rendering controls
 */
midas.visualize.setupActions = function () {
    // setup the extract subgrid controls
    var dialog = $('#extractSubgridDialogTemplate').clone();
    dialog.removeAttr('id');
    $('#extractSubgridAction').click(function () {
        midas.showDialogWithContent('Select subgrid bounds',
          dialog.html(), false, {modal: false, width: 560});
        var container = $('div.MainDialog');
        container.find('.sliderX').slider({
            range: true,
            min: midas.visualize.bounds[0],
            max: midas.visualize.bounds[1],
            values: [midas.visualize.bounds[0], midas.visualize.bounds[1]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinX').val(ui.values[0]);
                container.find('.extractSubgridMaxX').val(ui.values[1]);
            }
        });
        container.find('.sliderY').slider({
            range: true,
            min: midas.visualize.bounds[2],
            max: midas.visualize.bounds[3],
            values: [midas.visualize.bounds[2], midas.visualize.bounds[3]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinY').val(ui.values[0]);
                container.find('.extractSubgridMaxY').val(ui.values[1]);
            }
        });
        container.find('.sliderZ').slider({
            range: true,
            min: midas.visualize.bounds[4],
            max: midas.visualize.bounds[5],
            values: [midas.visualize.bounds[4], midas.visualize.bounds[5]],
            slide: function (event, ui) {
                container.find('.extractSubgridMinZ').val(ui.values[0]);
                container.find('.extractSubgridMaxZ').val(ui.values[1]);
            }
        });

        // setup spinboxes with feedback into the range sliders
        container.find('.extractSubgridMinX').spinbox({
            min: midas.visualize.bounds[0],
            max: midas.visualize.bounds[1]
        }).change(function () {
            container.find('.sliderX').slider('option', 'values',
              [$(this).val(), container.find('.extractSubgridMaxX').val()]);
        }).val(midas.visualize.bounds[0]);
        container.find('.extractSubgridMaxX').spinbox({
            min: midas.visualize.bounds[0],
            max: midas.visualize.bounds[1]
        }).change(function () {
            container.find('.sliderX').slider('option', 'values',
              [container.find('.extractSubgridMinX').val(), $(this).val()]);
        }).val(midas.visualize.bounds[1]);
        container.find('.extractSubgridMinY').spinbox({
            min: midas.visualize.bounds[2],
            max: midas.visualize.bounds[3]
        }).change(function () {
            container.find('.sliderY').slider('option', 'values',
              [$(this).val(), container.find('.extractSubgridMaxY').val()]);
        }).val(midas.visualize.bounds[2]);
        container.find('.extractSubgridMaxY').spinbox({
            min: midas.visualize.bounds[2],
            max: midas.visualize.bounds[3]
        }).change(function () {
            container.find('.sliderY').slider('option', 'values',
              [container.find('.extractSubgridMinY').val(), $(this).val()]);
        }).val(midas.visualize.bounds[3]);
        container.find('.extractSubgridMinZ').spinbox({
            min: midas.visualize.bounds[4],
            max: midas.visualize.bounds[5]
        }).change(function () {
            container.find('.sliderZ').slider('option', 'values',
              [$(this).val(), container.find('.extractSubgridMaxZ').val()]);
        }).val(midas.visualize.bounds[4]);
        container.find('.extractSubgridMaxZ').spinbox({
            min: midas.visualize.bounds[4],
            max: midas.visualize.bounds[5]
        }).change(function () {
            container.find('.sliderZ').slider('option', 'values',
              [container.find('.extractSubgridMinZ').val(), $(this).val()]);
        }).val(midas.visualize.bounds[5]);

        // setup button actions
        container.find('button.extractSubgridApply').click(function () {
            midas.visualize.renderSubgrid([
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

$(window).load(function () {
    if(typeof midas.visualize.preInitCallback == 'function') {
        midas.visualize.preInitCallback();
    }

    json = jQuery.parseJSON($('div.jsonContent').html());
    midas.visualize.start(); // do the initial rendering
    midas.visualize.populateInfo();
    midas.visualize.setupActions();

    if(typeof midas.visualize.postInitCallback == 'function') {
        midas.visualize.postInitCallback();
    }
});

$(window).unload(function () {
    paraview.disconnect();
});

