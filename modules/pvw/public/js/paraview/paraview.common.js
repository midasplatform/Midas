var midas = midas || {};
midas.pvw = midas.pvw || {};

midas.pvw.bgColor = {r: 255, g: 255, b: 255};

midas.pvw.instructionsContent = '<h4>Camera Interaction</h4>' +
  '<p><b>Left-click and drag</b> to rotate.</p>' +
  '<p><b>Right-click and drag</b> to zoom.</p>' +
  '<p><b>Alt+click and drag</b> to pan.</p>';

/**
 * Binds the action of selecting a background color
 */
midas.pvw.setupBgColor = function () {
    $('#bgColor').click(function () {
        var html = '<div class="bgColorPicker"></div>';
        midas.showDialogWithContent('Change background color',
          html, false, {modal: false, width: 380});
        var container = $('div.MainDialog');
        container.find('div.bgColorPicker').ColorPicker({
            flat: true,
            color: midas.pvw.bgColor,
            onSubmit: function(hsb, hex, rgb, el) {
                midas.pvw.bgColor = rgb;
                container.dialog('close');
                pv.connection.session.call('pv:changeBgColor', [rgb.r / 255.0, rgb.g / 255.0, rgb.b / 255.0])
                                     .then(pv.viewport.render);
            }
        });
    });
};

/**
 * When startinstance returns, this is called.
 * It sets the instance in global scope and calls midas.pvw.start()
 */
midas.pvw._commonStart = function (text) {
    midas.pvw.setupBgColor();

    $('div.MainDialog').dialog('close');
    try {
        var resp = $.parseJSON(text);
    }
    catch(e) {
        midas.createNotice('An error occurred, please check the logs', 4000, 'error');
        return;
    }
    if(resp && resp.status == 'ok' && resp.instance) {
        midas.pvw.instance = resp.instance;
        pv = {};
        pv.connection = {
            sessionURL: 'ws://'+location.hostname+':'+midas.pvw.instance.port+'/ws',
            id: midas.pvw.instance.instance_id,
            sessionManagerURL: json.global.webroot + '/pvw/paraview/instance',
            secret: midas.pvw.instance.secret,
            interactiveQuality: 50
        };
        midas.pvw.start();
    }
    else {
        midas.pvw.showStatus('Instance creation failed: ' + resp.message);
    }
};

/**
 * Once you have set up pv.connection, you can call this to load your data.
 * When done, it will call midas.pvw.dataLoaded.
 * This function also sets up the
 */
midas.pvw.loadData = function () {
    paraview.connect(pv.connection, function(conn) {
        pv.connection = conn;
        pv.viewport = paraview.createViewport(pv.connection);
        pv.viewport.bind('#renderercontainer');

        $('#renderercontainer').show();

        midas.pvw.waitingDialog('Loading data into scene...');
        pv.connection.session.call('pv:loadData')
                             .then(midas.pvw.dataLoaded)
                             .otherwise(midas.pvw.rpcFailure);
    }, function(msg) {
        midas.createNotice('Error: ' + msg, 3000, 'error');
    });
};

/**
 * Display a status message on the screen
 */
midas.pvw.showStatus = function (statusText) {
    $('.midas-pvw-status').remove();
    $('div.viewMain').append('<div class="midas-pvw-status">'+statusText+'</div>');
};

/**
 * Remove the status message from the screen
 */
midas.pvw.hideStatus = function () {
    $('.midas-pvw-status').remove();
};

/**
 * If an rpc failure occurs, this handles the error
 */
midas.pvw.rpcFailure = function (err) {
    $('div.MainDialog').dialog('close');
    console.log(err);
    midas.createNotice('A ParaViewWeb exception occurred, check your browser console', 4000, 'error');
};

/** Show an indeterminate loading dialog with a message */
midas.pvw.waitingDialog = function(text) {
    var html = '<img alt="" style="margin-right: 9px;" '+
               'src="'+json.global.coreWebroot+'/public/images/icons/loading.gif" /> ' + text;

    midas.showDialogWithContent('Please wait', html);
};

$(window).load(function () {
    if(paraview) {
        if(!json.pvw.meshIds) {
            json.pvw.meshIds = [];
        }
        // Add some logic to check for idle and close the pvw session after IDLE_TIMEOUT expires
        midas.pvw.lastAction = new Date().getTime();
        midas.pvw.idleInterval = setInterval(midas.pvw.testIdle, 15000); // every 15 seconds, check idle status
        $('body').mousemove(function () {
            midas.pvw.lastAction = new Date().getTime();
        });

        var html = '<div id="pvwProgressBar"></div>';
        html += '<div id="pvwProgressMessage"></div>';
        midas.showDialogWithContent('Starting ParaViewWeb instance', html);
        midas.ajaxWithProgress($('#pvwProgressBar'),
                               $('#pvwProgressMessage'),
        json.global.webroot+'/pvw/paraview/startinstance',
        {
            itemId: json.pvw.item.item_id,
            meshes: json.pvw.meshIds.join(';')
        },
        midas.pvw._commonStart);
    }
    else {
        midas.pvw.showStatus('Error: paraview-all.js was not loaded correctly. '
          + 'Make sure you symlink to the <b>www</b> directory in your ParaView build directory '
          + 'as <b>modules/pvw/public/import</b>.');
    }
    $('a.pvwInstructions').click(function () {
        midas.showDialogWithContent('Instructions', midas.pvw.instructionsContent, false);
    });
});

window.onunload = function () {
    if(midas.pvw.instance && pv.connection) {
        // Sadly we have to do this synchronously so the browser fulfills the request before leaving
        $.ajax({
            url: json.global.webroot + '/pvw/paraview/instance/' + midas.pvw.instance.instance_id,
            async: false,
            type: 'DELETE'
        });
    }
};
