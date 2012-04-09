var midas = midas || {};
midas.license = midas.license || {};

midas.license.newValidate = function(formData, jqForm, options) {
}

midas.license.newSuccess = function(responseText, statusText, xhr, form) {
    var resp = $.parseJSON(responseText);
    midas.createNotice(resp[1], 3000);
    window.location.replace(json.global.webroot+'/admin#ui-tabs-1');
    window.location.reload();
}

midas.license.existingValidate = function(formData, jqForm, options) {
}

midas.license.existingSuccess = function(responseText, statusText, xhr, form) {
    var resp = $.parseJSON(responseText);
    midas.createNotice(resp[1], 3000);
}

$(document).ready(function() {
    $('form.existingLicense').ajaxForm({
        beforeSubmit: midas.license.existingValidate,
        success: midas.license.existingSuccess
    });
    $('form.newLicense').ajaxForm({
        beforeSubmit: midas.license.newValidate,
        success: midas.license.newSuccess
    });

    $('input.deleteLicense').click(function() {
        var id = $(this).attr('element');
        var html = '';
        html += 'Do you really want to delete this license?';
        html += '<br/>';
        html += '<br/>';
        html += '<span style="float: right;">';
        html += '<input class="globalButton deleteLicenseYes" type="button" value="'+json.global.Yes+'"/>';
        html += '<input style="margin-left:15px;" class="globalButton deleteLicenseNo" type="button" value="'+json.global.No+'"/>';
        html += '</span>';
        midas.showDialogWithContent('Delete License', html, false);

        $('input.deleteLicenseYes').unbind('click').click(function() {
            $('div.MainDialog').dialog('close');
            midas.ajaxSelectRequest = $.ajax({
                type: 'POST',
                url: json.global.webroot+'/license/delete',
                data: {licenseId: id},
                success: function(jsonContent) {
                    var jsonResponse = jQuery.parseJSON(jsonContent);
                    midas.createNotice(jsonResponse[1], 3000);
                    if(jsonResponse[0]) {
                        $('div.licenseContainer[element='+id+']').remove();
                    }
                }
            });
        });
        $('input.deleteLicenseNo').unbind('click').click(function() {
            $('div.MainDialog').dialog('close');
        });
    });
});
