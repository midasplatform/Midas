 
  $(document).ready(function() {
   var defaultTest=$('.installHelp').html();
   var helpText=new Array();
   helpText['name']='<h4>Name</h4>Please select the name of your installation of MIDAS';
   helpText['lang']='<h4>Language</h4>Please select the default language. Currently, english and french are available.';
   helpText['env']='<h4>Environment</h4>Developpement is slower but it will show you all the informations needed to debug the application<br/><br/>Production is faster but it hides the errors.';
   helpText['process']='<h4>Process</h4>On the fly: All the data processing will be done on the fly. The application will be slower<br/><br/>External: This mode allows you  the process the data using and external tool like cron.<br/> To lunch the processing, use wget:<br/>'+$('.hiddenProcces').html();
   helpText['time']='<h4>Timezone</h4>Please select the timezone of your server';
   helpText['optimizer']='<h4>SmartOptimizer</h4>SmartOptimizer  is a PHP library that enhances your website performance by optimizing the front end using techniques such as minifying, compression, caching, concatenation and embedding.  (previously named JSmart) is a PHP library that enhances your website performance by optimizing the front end using techniques such as minifying, compression, caching, concatenation and embedding. ';
   helpText['assetstore']='<h4>Default Assetstore</h4>Please select a default assetstore. An assestore is the location where the uploaded files are store.';
   
   $('.installName').hover(function(){ $('.installHelp').html(helpText['name'])}, function(){ $('.installHelp').html(defaultTest)});
   $('.installLang').hover(function(){ $('.installHelp').html(helpText['lang'])}, function(){ $('.installHelp').html(defaultTest)});
   $('.installEnvironment').hover(function(){ $('.installHelp').html(helpText['env'])}, function(){ $('.installHelp').html(defaultTest)});
   $('.installProcess').hover(function(){ $('.installHelp').html(helpText['process'])}, function(){ $('.installHelp').html(defaultTest)});
   $('.installTimezone').hover(function(){ $('.installHelp').html(helpText['time'])}, function(){ $('.installHelp').html(defaultTest)});
   $('.installSmartoptimizer').hover(function(){ $('.installHelp').html(helpText['optimizer'])}, function(){ $('.installHelp').html(defaultTest)});
   $('.installAssetstrore').hover(function(){ $('.installHelp').html(helpText['assetstore'])}, function(){ $('.installHelp').html(defaultTest)});
  });
  
  