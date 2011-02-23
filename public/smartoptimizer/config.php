<?php
/*
 * SmartOptimizer Configuration File
 */
if(file_exists('../../application/configs/application.local.ini'))
	{
	$config=parse_ini_file('../../application/configs/application.local.ini');
	}
else
	{
	$config=parse_ini_file('../../application/configs/application.ini');
	}
if(isset($config['smartoptimizer'])&&$config['smartoptimizer']==1)
{
//use this to set gzip compression On or Off
$settings['gzip'] = true;

//use this to set Minifier On or Off
$settings['minify'] = true;

//use this to set file concatenation On or Off
$settings['concatenate'] = true;

//specifies whether to emebed files included in css files using the data URI scheme or not 
$settings['embed'] = true;
}
else
{
//use this to set gzip compression On or Off
$settings['gzip'] = false;

//use this to set Minifier On or Off
$settings['minify'] = false;

//use this to set file concatenation On or Off
$settings['concatenate'] = false;

//specifies whether to emebed files included in css files using the data URI scheme or not 
$settings['embed'] = false;
}

//base dir (a relative path to the base directory)
$settings['baseDir'] = '../';

//Encoding of your js and css files. (utf-8 or iso-8859-1)
$settings['charSet'] = 'utf-8'; 

//Show error messages if any error occurs (true or false)
$settings['debug'] = false;


//use this to set gzip compression level (an integer between 1 and 9)
$settings['compressionLevel'] = 9;

//these types of files will not be gzipped nor minified
$settings['gzipExceptions'] = array('gif','jpeg','jpg','png','swf'); 



//separator for files to be concatenated
$settings['separator'] = ',';

//specifies whether to emebed files included in css files using the data URI scheme or not 
$settings['embed'] = true;

//The maximum size of an embedded file. (use 0 for unlimited size)
$settings['embedMaxSize'] = 5120; //5KB

//these types of files will not be embedded
$settings['embedExceptions'] = array('htc'); 

//to set server-side cache On or Off
$settings['serverCache'] = true;

//if you change it to false, the files will not be checked for modifications and always cached files will be used (for better performance)
$settings['serverCacheCheck'] = true;

//cache dir
$settings['cacheDir'] = '../../tmp/cache/smartoptimizer/';

//prefix for cache files
$settings['cachePrefix'] = 'so_';

//to set client-side cache On or Off
$settings['clientCache'] = true;

//Setting this to false will force the browser to use cached files without checking for changes.
$settings['clientCacheCheck'] = false;
?>
