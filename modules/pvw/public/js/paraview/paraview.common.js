var midas = midas || {};
midas.pvw = midas.pvw || {};

/**
 * When startinstance returns, this is called.
 * It sets the instance in global scope and calls midas.pvw.start()
 */
midas.pvw._commonStart = function (text) {
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
        midas.pvw.start();
    }
    else {
        // TODO put this somewhere on the page besides in the notice
        midas.createNotice('Instance creation failed: ' + resp.message, 4000, resp.status);
    }
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

$(window).load(function () {
    if(paraview) {
        var html = '<div id="pvwProgressBar"></div>';
        html += '<div id="pvwProgressMessage"></div>';
        midas.showDialogWithContent('Starting ParaViewWeb instance', html);
        midas.ajaxWithProgress($('#pvwProgressBar'),
                               $('#pvwProgressMessage'),
        json.global.webroot+'/pvw/paraview/startinstance',
        {itemId: json.pvw.item.item_id},
        midas.pvw._commonStart);
    }
    else {
        midas.pvw.showStatus('Error: paraview-all.js was not loaded correctly. '
          + 'Make sure you symlink to the <b>www</b> directory in your ParaView build directory '
          + 'as <b>modules/pvw/public/import</b>.');
    }
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
