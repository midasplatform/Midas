// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global JavaScriptRenderer */
/* global json */

var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.renderers = {};

var paraview;

midas.visualize.start = function () {
    'use strict';
    // Create a paraview proxy

    if (typeof Paraview != 'function') {
        alert('Unable to connect to the Paraview server. Please contact an administrator.');
        return;
    }

    $('#loadingStatus').html('Creating ParaView session on the server and loading plugins...');
    paraview = new Paraview("/PWService");
    paraview.errorListener = {
        manageError: function (error) {
            if (error) {
                midas.createNotice('A ParaViewWeb error occurred.', 4000, 'error');
                return false;
            }
        }
    };

    paraview.createSessionAsync("midas", "surface view", "default", function () {
        $('#loadingStatus').html('Reading image data from files...');
        paraview.callPluginMethod('midascommon', 'OpenData', {
            filename: json.visualize.url,
            otherMeshes: []
        }, midas.visualize._dataOpened);
    });
};

midas.visualize._dataOpened = function (view, retVal) {
    'use strict';
    $('#loadingStatus').html('Initializing view state and renderer...');
    midas.visualize.imageData = retVal.imageData;
    midas.visualize.input = retVal.input;

    var rw = $('#renderercontainer');
    paraview.callPluginMethod('midassurface', 'InitViewState', {
        viewSize: [rw.width(), rw.height()]
    }, midas.visualize.initCallback);
    midas.visualize.populateInfo();
};

midas.visualize.populateInfo = function () {
    'use strict';
    var bounds = midas.visualize.imageData.Bounds;
    $('#boundsXInfo').html(bounds[0].toFixed(3) + ' .. ' + bounds[1].toFixed(3));
    $('#boundsYInfo').html(bounds[2].toFixed(3) + ' .. ' + bounds[3].toFixed(3));
    $('#boundsZInfo').html(bounds[4].toFixed(3) + ' .. ' + bounds[5].toFixed(3));
    $('#nbPointsInfo').html(midas.visualize.imageData.NbPoints);
    $('#nbCellsInfo').html(midas.visualize.imageData.NbCells);
};

midas.visualize.initCallback = function (view, retVal) {
    'use strict';
    $('#loadingStatus').html('').hide();
    midas.visualize.activeView = retVal.activeView;

    // Create renderers
    midas.visualize.switchRenderer(true, 'js');
    $('img.visuLoading').hide();
    $('#renderercontainer').show();
    $('#rendererSelect').removeAttr('disabled').change(function () {
        midas.visualize.switchRenderer(false, $(this).val());
    });
};

midas.visualize.resetCamera = function () {
    'use strict';
    paraview.callPluginMethod('midassurface', 'ResetCamera', {}, function () {
        midas.visualize.forceRefreshView();
    });
};

midas.visualize.toggleEdges = function () {
    'use strict';
    paraview.callPluginMethod('midassurface', 'ToggleEdges', {
        input: midas.visualize.input
    }, function () {
        midas.visualize.forceRefreshView();
    });
};

/**
 * Force the renderer image to refresh from the server
 */
midas.visualize.forceRefreshView = function () {
    'use strict';
    midas.visualize.renderers.js.forceRefresh();
};

midas.visualize.switchRenderer = function (first, type) {
    'use strict';
    if (type == 'js') {
        if (midas.visualize.renderers.js === undefined) {
            midas.visualize.renderers.js = new JavaScriptRenderer('jsRenderer', '/PWService');
            midas.visualize.renderers.js.enableWebSocket(paraview, 'ws://' + json.visualize.hostname + ':' + json.visualize.wsport + '/PWService/Websocket');
            midas.visualize.renderers.js.init(paraview.sessionId, midas.visualize.activeView.__selfid__);
        }
    }
    else if (type == 'webgl') {
        if (midas.visualize.renderers.webgl === undefined) {
            paraview.updateConfiguration(true, 'JPEG', 'WebGL');
            midas.visualize.renderers.webgl = new WebGLRenderer('webglRenderer', '/PWService');
            midas.visualize.renderers.webgl.init(paraview.sessionId, midas.visualize.activeView.__selfid__);
        }
    }

    if (!first) {
        midas.visualize.renderers.current.unbindToElementId('renderercontainer');
    }
    midas.visualize.renderers.current = midas.visualize.renderers[type];
    midas.visualize.renderers.current.type = type;
    midas.visualize.renderers.current.bindToElementId('renderercontainer');
    var el = $('#renderercontainer');
    midas.visualize.renderers.current.setSize(el.width(), el.height());
    midas.visualize.renderers.current.start();
    if (type == 'js') {
        midas.visualize.renderers.current.updateServerSizeIfNeeded();
    }
    else {
        paraview.updateConfiguration(true, 'JPEG', 'WebGL');
    }
};

$(window).load(function () {
    'use strict';
    json = $.parseJSON($('div.jsonContent').html());
    midas.visualize.start();
});

$(window).unload(function () {
    'use strict';
    paraview.disconnect();
});
