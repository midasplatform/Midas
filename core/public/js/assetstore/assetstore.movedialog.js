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
      function (resp) {
          window.location = json.global.webroot+'/admin#tabs-assetstore';
          window.location.reload();
      });
});