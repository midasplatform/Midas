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

    var lookupTable = paraview.GetLookupTableForArray('MetaImage', 1);
    lookupTable.setRGBPoints([midas.visualize.minVal,
                              0.0, 0.0, 0.0,
                              midas.visualize.maxVal,
                              1.0, 1.0, 1.0]); //initial transfer function def
    lookupTable.setScalarRangeInitialized(1.0);
    lookupTable.setColorSpace(0); // 0 corresponds to RGB

    // Create the scalar opacity transfer function
    var sof = paraview.CreatePiecewiseFunction({
        Points: [midas.visualize.minVal, 0.0, 0.5, 0.0,
                 midas.visualize.maxVal, 1.0, 0.75, 0.0]
    });

    paraview.SetDisplayProperties({
        view: midas.visualize.activeView,
        ScalarOpacityFunction: sof,
        Representation: 'Volume',
        ColorArrayName: 'MetaImage',
        LookupTable: lookupTable
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
 * Display information about the volume
 */
midas.visualize.populateInfo = function () {
    $('#boundsXInfo').html(midas.visualize.bounds[0]+' .. '+midas.visualize.bounds[1]);
    $('#boundsYInfo').html(midas.visualize.bounds[2]+' .. '+midas.visualize.bounds[3]);
    $('#boundsZInfo').html(midas.visualize.bounds[4]+' .. '+midas.visualize.bounds[5]);
    $('#scalarRangeInfo').html(midas.visualize.minVal+' .. '+midas.visualize.maxVal);
};

$(window).load(function () {
    if(typeof midas.visualize.preInitCallback == 'function') {
        midas.visualize.preInitCallback();
    }

    json = jQuery.parseJSON($('div.jsonContent').html());
    midas.visualize.start();
    midas.visualize.populateInfo();

    if(typeof midas.visualize.postInitCallback == 'function') {
        midas.visualize.postInitCallback();
    }
});

$(window).unload(function () {
    paraview.disconnect();
});

