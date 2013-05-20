var midas = midas || {};
midas.pvw = midas.pvw || {};

/**
 * When called, will update the view every 200 ms
 */
midas.pvw.startRefreshes = function () {
    pv.viewport.render(function () {
        window.setTimeout(midas.pvw.startRefreshes, 200);
    });
}

$(window).load(function () {
    pv = {};
    pv.connection = {
        sessionURL: 'ws://'+location.hostname+':'+json.pvw.instance.port+'/ws',
        id: json.pvw.instance.instance_id,
        enableInteractions: false,
        secret: json.pvw.instance.secret
    };

    paraview.connect(pv.connection, function(conn) {
        pv.connection = conn;
        pv.viewport = paraview.createViewport(pv.connection);
        pv.viewport.bind('#renderercontainer');

        $('#renderercontainer').show();
        midas.pvw.startRefreshes();

    }, function(code, msg) {
        midas.createNotice('Error: ' + msg, 3000, 'error');
        $('#renderercontainer').hide();
        $('div.viewMain').html('').append('<div class="midas-pvw-status">Paraview session closed: '+msg+'</div>');
    });
});
