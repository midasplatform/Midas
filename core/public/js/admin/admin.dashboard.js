var midas = midas || {};
midas.admin = midas.admin || {};

$(document).ready(function () {
    $('.databaseIntegrityWrapper').accordion({
        clearStyle: true,
        collapsible: true,
        active: false,
        autoHeight: false
    }).show();
    $('.databaseIntegrityWrapper').close();
    $('button.removeOrphans').click(function () {
        var html = '<div id="cleanupProgress"></div>';
        html += '<div id="cleanupProgressMessage"></div>';
        midas.showDialogWithContent('Cleaning orphaned resources', html, false, {width: 400});
        var model = $(this).attr('element');

        midas.ajaxWithProgress($('#cleanupProgress'),
            $('#cleanupProgressMessage'),
            json.global.webroot+'/admin/removeorphans',
            {model: model},
            function(text) {
                var retVal = $.parseJSON(text);
                if(retVal === null) {
                    midas.createNotice('Error occurred, check the logs', 2500, 'error');
                }
                else {
                    midas.createNotice(retVal.message, 3000, retVal.status);
                }
                $('div.MainDialog').dialog('close');
                $('td.n'+model).html('0');
            });
    });
});
