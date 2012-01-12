<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/
/*
 * SmartOptimizer Configuration File
 */
if(file_exists('../../configs/application.local.ini'))
	{
	$config=parse_ini_file('../../configs/application.local.ini');
	}
else
	{
	$config=parse_ini_file('../../configs/application.ini');
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
$settings['embed'] = false;
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

//Encoding of your js and css files. FOR THE LOVE OF GOD ONLY USE UTF8
$settings['charSet'] = 'utf-8'; 

//Show error messages if any error occurs (true or false)
$settings['debug'] = false;


//use this to set gzip compression level (an integer between 1 and 9)
$settings['compressionLevel'] = 9;

//these types of files will not be gzipped nor minified
$settings['gzipExceptions'] = array('gif','jpeg','jpg','png','swf'); 



//separator for files to be concatenated
$settings['separator'] = ',';

//The maximum size of an embedded file. (use 0 for unlimited size)
$settings['embedMaxSize'] = 5120; //5KB

//these types of files will not be embedded
$settings['embedExceptions'] = array('htc'); 

//to set server-side cache On or Off
$settings['serverCache'] = true;

//if you change it to false, the files will not be checked for modifications and always cached files will be used (for better performance)
$settings['serverCacheCheck'] = true;

//cache dir
$settings['cacheDir'] = '../../../tmp/cache/smartoptimizer/';

//prefix for cache files
$settings['cachePrefix'] = 'so_';

//to set client-side cache On or Off
$settings['clientCache'] = true;

//Setting this to false will force the browser to use cached files without checking for changes.
$settings['clientCacheCheck'] = true;

?>
