// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.rest = midas.rest || {};

$(document).ready(function () {
    'use strict';
    window.swaggerUi = new SwaggerUi({
        discoveryUrl: json.global.webroot + '/apidocs/',
        apiKey: null,
        dom_id: "swagger-ui-container",
        supportHeaderParams: false,
        supportedSubmitMethods: ['get', 'post', 'put', 'delete'],
        onComplete: function (swaggerApi, swaggerUi) {
            if (console) {
                console.log("Loaded SwaggerUI");
                console.log(swaggerApi);
                console.log(swaggerUi);
            }
        },
        onFailure: function (data) {
            if (console) {
                console.log("Unable to Load SwaggerUI");
                console.log(data);
            }
        },
        docExpansion: "none"
    });

    window.swaggerUi.load();
});
