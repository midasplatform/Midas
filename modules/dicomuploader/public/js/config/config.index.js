var midas = midas || {};
midas.dicomuploader = midas.dicomuploader || {};

midas.dicomuploader.validateConfig = function (formData, jqForm, options) {
}

midas.dicomuploader.successConfig = function (responseText, statusText, xhr, form) {
    try {
        var jsonResponse = jQuery.parseJSON(responseText);
    } catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if(jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if(jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}


$(document).ready(function() {
    $("div#tmpdir").qtip({
          content: 'The file-system location of the DICOM uploader temporary work directory. (required)',
          show: 'mouseover',
          hide: 'mouseout',
          position: {
                target: 'mouse',
                my: 'bottom left',
                viewport: $(window), // Keep the qtip on-screen at all times
                effect: true // Disable positioning animation
             }
         })

    $('#configForm').ajaxForm({
        beforeSubmit: midas.dicomuploader.validateConfig,
        success: midas.dicomuploader.successConfig
    });
});
