var midas = midas || {};
midas.pvw = midas.pvw || {};

midas.pvw.start = function () {
    if(typeof midas.pvw.preInitCallback == 'function') {
        midas.pvw.preInitCallback();
    }
    pv = {};
    pv.connection = {
        sessionURL: 'ws://'+location.hostname+':'+midas.pvw.instance.port+'/ws',
        id: midas.pvw.instance.instance_id,
        sessionManagerURL: json.global.webroot + '/pvw/paraview/instance',
        interactiveQuality: 60
    };
    midas.pvw.loadData();
};

/** Callback for once the loadData RPC has returned */
midas.pvw.dataLoaded = function (resp) {
    midas.pvw.mainProxy = resp;
    pv.viewport.render();
    midas.pvw.waitingDialog('Starting surface rendering...');
    pv.connection.session.call('pv:surfaceRender')
                         .then(midas.pvw.surfaceRenderStarted)
                         .otherwise(midas.pvw.rpcFailure);
};

midas.pvw.surfaceRenderStarted = function (resp) {
    midas.pvw.bounds = resp.bounds;
    midas.pvw.nbPoints = resp.nbPoints;
    midas.pvw.nbCells = resp.nbCells;
    $('div.MainDialog').dialog('close');
    midas.pvw.populateInfo();
};

midas.pvw.populateInfo = function () {
    var bounds = midas.pvw.bounds;
    $('#boundsXInfo').html(bounds[0].toFixed(3)+' .. '+bounds[1].toFixed(3));
    $('#boundsYInfo').html(bounds[2].toFixed(3)+' .. '+bounds[3].toFixed(3));
    $('#boundsZInfo').html(bounds[4].toFixed(3)+' .. '+bounds[5].toFixed(3));
    $('#nbPointsInfo').html(midas.pvw.nbPoints);
    $('#nbCellsInfo').html(midas.pvw.nbCells);
};

midas.pvw.resetCamera = function () {
    pv.connection.session.call('pv:cameraPreset', '+x')
                        .then(function () {
                            pv.viewport.render();
                        })
                        .otherwise(midas.pvw.rpcFailure);
};

midas.pvw.toggleEdges = function () {
    pv.connection.session.call('pv:toggleEdges')
                        .then(function () {
                            pv.viewport.render();
                        })
                        .otherwise(midas.pvw.rpcFailure);
};
