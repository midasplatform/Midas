// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.visualize = midas.visualize || {};

midas.visualize.validateConfig = function (formData, jqForm, options) {};

midas.visualize.successConfig = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if (jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

$(document).ready(function () {
    'use strict';
    $('#customtmp').qtip({
        content: 'Temp directory for the module to use. If you leave this empty, it will use the Midas temporary directory.'
    });

    $('#useparaview').qtip({
        content: 'Check this box if you want to use a ParaViewWeb server to visualize Midas data.'
    });

    $('#userwebgl').qtip({
        content: 'Check this box to enable the Midas WebGL viewer.'
    });

    $('#usesymlinks').qtip({
        content: 'Check this box if you want to symlink the data into the ParaView working directory.  This is much faster, but requires the underlying OS to support the operation.'
    });

    $('#pwapp').qtip({
        content: 'Set this to the parent URL under which the ParaViewWeb server is running. Example: http:// localhost:8080/'
    });

    $('#pvbatch').qtip({
        content: 'Set this to the location on disk of the pvbatch executable provided by ParaView.'
    });

    $('#paraviewworkdir').qtip({
        content: 'Set this to the path that the ParaView server is using as its temp directory.'
    });

    $('#configForm').ajaxForm({
        beforeSubmit: midas.visualize.validateConfig,
        success: midas.visualize.successConfig
    });
});
