// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {

    json = jQuery.parseJSON($('div.jsonContent').html());
    if (json.type == 'mp3') {
        $("#jquery_jplayer_1").jPlayer({
            ready: function () {
                $(this).jPlayer("setMedia", {
                    mp3: json.global.webroot + "/download?items=" + json.itemId
                });
            }
        })
    }
    if (json.type == 'm4a') {
        $("#jquery_jplayer_1").jPlayer({
            ready: function () {
                $(this).jPlayer("setMedia", {
                    m4a: json.global.webroot + "/download?items=" + json.itemId
                });
                supplied = 'm4a, oga';

            },
            ended: function (event) {

            },
            swfPath: json.global.moduleWebroot + "/public/js/jquery",
            supplied: "m4a, oga"
        })
    }
    if (json.type == 'm4v') {
        $("#jquery_jplayer_1").jPlayer({
            ready: function () {
                $(this).jPlayer("setMedia", {
                    m4v: json.global.webroot + "/download?items=" + json.itemId
                });
            },
            ended: function (event) {
                $(this).jPlayer("play");
            },
            swfPath: json.global.webroot + "/modules/visualize/public/js/jquery",
            solution: "flash, html",
            supplied: "m4v, ogv"
        });
    }
});
