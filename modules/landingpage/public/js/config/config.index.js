var midas = midas || {};
midas.ldap = midas.ldap || {};
midas.ldap.config = midas.ldap.config || {};

midas.ldap.config.validateConfig = function (formData, jqForm, options) {
    'use strict';
    return;
};

midas.ldap.config.successConfig = function (responseText,
                                            statusText,
                                            xhr,
                                            form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = jQuery.parseJSON(responseText);
    } catch (e) {
        createNotice("An error occured. Please check the logs.");
        return false;
    }
    if (jsonResponse === null) {
        createNotice('Error',4000);
        return false;
    }
    if(jsonResponse[0]) {
        createNotice(jsonResponse[1],4000);
        return true;
    } else {
        createNotice(jsonResponse[1],4000);
        return true;
    }
};

$(document).ready(function () {
    'use strict';
    $('#configForm').ajaxForm({beforeSubmit: midas.ldap.config.validateConfig,
                               success: midas.ldap.config.successConfig});
});
