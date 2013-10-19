/*global $*/
/*global document*/
/*global ajaxWebApi*/
/*global json*/
/*global window*/
var midas = midas || {};
midas.readmes = midas.readmes || {};

(function() {
    'use strict';

    midas.readmes.getForCommunity = function (id) {
        $.ajax({
            url: json.global.webroot + '/rest/readmes/community/' + id,
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
        midas.readmes.getForCommunity(json.community.community_id);
    });

})();
