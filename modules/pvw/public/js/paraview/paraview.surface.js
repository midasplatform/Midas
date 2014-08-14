// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.pvw = midas.pvw || {};
var pv = pv || {};

midas.pvw.start = function () {
    'use strict';
    if (typeof midas.pvw.preInitCallback == 'function') {
        midas.pvw.preInitCallback();
    }
    midas.pvw.renderer = $('#rendererSelect').val();
    pv.connection.renderer = midas.pvw.renderer;
    midas.pvw.loadData();
};

/** Callback for once the loadData RPC has returned */
midas.pvw.dataLoaded = function (resp) {
    'use strict';
    pv.viewport.render();
    midas.pvw.waitingDialog('Starting surface rendering...');
    pv.connection.session.call('vtk:surfaceRender')
        .then(midas.pvw.surfaceRenderStarted)
        .otherwise(midas.pvw.rpcFailure);
};

midas.pvw.surfaceRenderStarted = function (resp) {
    'use strict';
    midas.pvw.bounds = resp.bounds;
    midas.pvw.nbPoints = resp.nbPoints;
    midas.pvw.nbCells = resp.nbCells;
    $('div.MainDialog').dialog('close');
    midas.pvw.populateInfo();
    $('#rendererSelect').removeAttr('disabled').change(function () {
        if ($(this).val() != midas.pvw.renderer) {
            midas.pvw.renderer = $(this).val();
            pv.connection.renderer = midas.pvw.renderer;
            pv.viewport.unbind();
            pv.viewport = vtkWeb.createViewport(pv.connection);
            pv.viewport.bind('#renderercontainer');
            pv.viewport.render();
        }
    });
};

midas.pvw.populateInfo = function () {
    'use strict';
    var bounds = midas.pvw.bounds;
    $('#boundsXInfo').html(bounds[0].toFixed(3) + ' .. ' + bounds[1].toFixed(3));
    $('#boundsYInfo').html(bounds[2].toFixed(3) + ' .. ' + bounds[3].toFixed(3));
    $('#boundsZInfo').html(bounds[4].toFixed(3) + ' .. ' + bounds[5].toFixed(3));
    $('#nbPointsInfo').html(midas.pvw.nbPoints);
    $('#nbCellsInfo').html(midas.pvw.nbCells);
};

midas.pvw.resetCamera = function () {
    'use strict';
    pv.connection.session.call('vtk:cameraPreset', '+x')
        .then(function () {
            if (midas.pvw.renderer == 'webgl') {
                pv.viewport.invalidateScene();
            }
            pv.viewport.render();
        })
        .otherwise(midas.pvw.rpcFailure);
};

midas.pvw.toggleEdges = function () {
    'use strict';
    pv.connection.session.call('vtk:toggleEdges')
        .then(function () {
            if (midas.pvw.renderer == 'webgl') {
                pv.viewport.invalidateScene();
            }
            pv.viewport.render();
        })
        .otherwise(midas.pvw.rpcFailure);
};
