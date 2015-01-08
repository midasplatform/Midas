// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */
/* global vtkWeb */

var midas = midas || {};
midas.pvw = midas.pvw || {};
var pv = pv || {};

/**
 * When called, will update the view every 200 ms
 */
midas.pvw.startRefreshes = function () {
    'use strict';
    pv.viewport.render(function () {
        window.setTimeout(midas.pvw.startRefreshes, 200);
    });
};

$(window).load(function () {
    'use strict';
    pv = {};
    pv.connection = {
        sessionURL: 'ws://' + location.hostname + ':' + json.pvw.instance.port + '/ws',
        id: json.pvw.instance.instance_id,
        enableInteractions: false,
        secret: json.pvw.instance.secret
    };

    vtkWeb.connect(pv.connection, function (conn) {
        pv.connection = conn;
        pv.viewport = vtkWeb.createViewport(pv.connection);
        pv.viewport.bind('#renderercontainer');

        $('#renderercontainer').show();
        midas.pvw.startRefreshes();

    }, function (code, msg) {
        midas.createNotice('Error: ' + msg, 3000, 'error');
        $('#renderercontainer').hide();
        $('div.viewMain').html('').append('<div class="midas-pvw-status">Paraview session closed: ' + msg + '</div>');
    });
});
