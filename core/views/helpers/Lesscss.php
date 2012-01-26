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

class  Zend_View_Helper_Lesscss
{
  /** compile and include less file */
  function lesscss($pathLessScript, $variables = array())
    {
    $in = str_replace($this->view->webroot, BASE_PATH, $pathLessScript);
    $module = "core";
    $nameArray = explode("/", $pathLessScript);
    foreach($nameArray as $key => $value)
      {
      if($value == "modules" || $value == "privateModules")
        {
        $module = $nameArray[$key+1];
        break;
        }
      }

    $outFilename = $module.'-'.basename($pathLessScript).'.css';
    $out = BASE_PATH.'/data/compiledCSS/'.$outFilename;

    if (true || !is_file($out) || filemtime($in) > filemtime($out))
      {
			$less = new lessc();
      try
        {
        file_put_contents($out, $less->parse(file_get_contents($in), $variables));
        }
      catch (Exception $ex)
        {
        throw new Zend_Exception($ex->getMessage());
        }
      }
    return '<link type="text/css" rel="stylesheet" href="'.$this->view->webroot.'/data/compiledCss/'.$outFilename.'?'.filemtime($out).'" />';
    }

    /** does nothing*/
    public function nothing()
      {

      }


    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class