var midas = midas || {};
midas.rest = midas.rest || {};

$(document).ready(function() {
    window.swaggerUi = new SwaggerUi({
    discoveryUrl:'/midas/core/apidocs/api-docs.json',
    apiKey:null,
    dom_id:"swagger-ui-container",
    supportHeaderParams: false,
    supportedSubmitMethods: ['get', 'post', 'put'],
    onComplete: function(swaggerApi, swaggerUi){
        if(console) {
            console.log("Loaded SwaggerUI")
            console.log(swaggerApi);
            console.log(swaggerUi);
        }
    },
    onFailure: function(data) {
        if(console) {
            console.log("Unable to Load SwaggerUI");
            console.log(data);
        }
    },
    docExpansion: "none"
    });

    window.swaggerUi.load();
});

