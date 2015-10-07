// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */
/* global SwaggerUi */

var midas = midas || {};
midas.rest = midas.rest || {};

$(document).ready(function () {
    'use strict';
    window.swaggerUi = new SwaggerUi({
        discoveryUrl: json.global.webroot + '/apidocs/',
        apiKey: null,
        dom_id: 'swagger-ui-container',
        supportHeaderParams: false,
        supportedSubmitMethods: ['get', 'post', 'put', 'delete'],
        docExpansion: 'none'
    });

    window.swaggerUi.load();
});
