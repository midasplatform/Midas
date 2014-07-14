// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

 $(document).ready(function () {
     var defaultTest = $('.installHelp').html();
     var helpText = new Array();
     helpText['name'] = '<h4>Name</h4>Please select the name of your installation of MIDAS';
     helpText['description'] = '<h4>Description</h4>Provide a description for search engines.';
     helpText['keywords'] = '<h4>Keywords</h4>Provide keywords for search engines.';
     helpText['lang'] = '<h4>Language</h4>Please select the default language. Currently, English and French are available.';
     helpText['env'] = '<h4>Environment</h4>Development is slower, but it will show you all the information needed to debug the application<br/><br/>Production is faster, but it hides the errors.';
     helpText['time'] = '<h4>Timezone</h4>Please select the timezone of your server.';
     helpText['optimizer'] = '<h4>SmartOptimizer</h4>SmartOptimizer (previously named JSmart) is a PHP library that enhances your website performance by optimizing the front end using techniques such as minifying, compression, caching, concatenation and embedding.';
     helpText['assetstore'] = '<h4>Default Assetstore</h4>Please select a default assetstore. An assestore is the location where the uploaded files are stored.';

     $('.installName').hover(function () {
         $('.installHelp').html(helpText['name'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
     $('.installDescription').hover(function () {
         $('.installHelp').html(helpText['description'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
     $('.installKeywords').hover(function () {
         $('.installHelp').html(helpText['keywords'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
     $('.installLang').hover(function () {
         $('.installHelp').html(helpText['lang'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
     $('.installEnvironment').hover(function () {
         $('.installHelp').html(helpText['env'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
     $('.installTimezone').hover(function () {
         $('.installHelp').html(helpText['time'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
     $('.installSmartoptimizer').hover(function () {
         $('.installHelp').html(helpText['optimizer'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
     $('.installAssetstrore').hover(function () {
         $('.installHelp').html(helpText['assetstore'])
     }, function () {
         $('.installHelp').html(defaultTest)
     });
 });
