var midas = midas || {};
midas.assetstore = midas.assetstore || {};

$('#moveBitstreamsConfirm').click(function () {
    $(this).attr('disabled', 'disabled');
    var params = {
        srcAssetstoreId: $('#srcAssetstoreId').val(),
        dstAssetstoreId: $('#dstAssetstoreId').val()
    };
    midas.ajaxWithProgress(
      $('#moveBitstreamsProgressBar'),
      $('#moveBitstreamsProgressMessage'),
      json.global.webroot+'/assetstore/movecontents',
      params,
      function (text) {
          $('div.MainDialog').dialog('close');
          $('#moveBitstreamsConfirm').removeAttr('disabled');
          var resp = $.parseJSON(text);
          midas.createNotice(resp.message, 3000, resp.status);
      });
});
