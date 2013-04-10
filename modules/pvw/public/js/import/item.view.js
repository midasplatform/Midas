var midas = midas || {};
midas.pvw = midas.pvw || {};

midas.pvw.sessionStarted = function (text) {
    $('div.MainDialog').dialog('close');
    try {
        var resp = $.parseJSON(text);
    }
    catch(e) {
        midas.createNotice('An error occurred, please check the logs', 4000, 'error');
        return;
    }
    if(resp && resp.instanceId) {
        window.location = json.global.webroot + '/pvw/paraview/' +
          midas.pvw.type + '?instanceId=' + resp.instanceId;
    }
    else {
        midas.createNotice('Instance creation failed: ' + resp.message);
        return;
    }
};

$(document).ready(function () {
    $('a.pvwLink').click(function () {
        var $this = $(this);
        midas.pvw.type = $this.attr('type');

        var html = '<div id="pvwProgressBar"></div>';
        html += '<div id="pvwProgressMessage"></div>';
        midas.showDialogWithContent('Loading visualization', html);
        midas.ajaxWithProgress($('#pvwProgressBar'),
                               $('#pvwProgressMessage'),
        json.global.webroot+'/pvw/paraview/startinstance',
        {itemId: json.item.item_id},
        midas.pvw.sessionStarted
        );
    });
});