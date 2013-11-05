/*global $*/
/*global document*/
/*global ajaxWebApi*/
/*global json*/
/*global window*/
var midas = midas || {};
midas.readmes = midas.readmes || {};

(function() {
    'use strict';

    midas.readmes.getForFolder = function (id) {
        $.ajax({
            url: json.global.webroot + '/rest/readmes/folder/' + id,
            success: function (retVal) {
                $('.viewMain').append('<hr />' + retVal.data.text);
            },
            error: function (retVal) {
                midas.createNotice(retVal.message, 3000, 'error');
            },
            complete: function () {
            },
            log: $('<p></p>')
        });
    };

    $(document).ready(function () {
        midas.readmes.getForFolder(json.folder.folder_id);
    });

})();
