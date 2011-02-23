<?php
class AppForm
  {
  /** constructor*/
  public function  __construct()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->webroot = $fc->getBaseUrl() ;
    }//end construct


    /** translation  */
  protected  function t($text)
    {
      if(Zend_Registry::get('configGlobal')->application->lang=='fr')
        {
        $translate=Zend_Registry::get('translater');
        $new_text=$translate->_($text);
        if($new_text==$text&&Zend_Registry::get('config')->mode->debug == 1)
          {
            $content=@file_get_contents(BASE_PATH."/tmp/report/translation-fr.csv");
            if(strpos($content,$text.";")==false)
              {
              $translationFile = BASE_PATH."/tmp/report/translation-fr.csv";
              $fh = fopen($translationFile, 'a');
              fwrite($fh, "\n$text;");
              fclose($fh);
              }
          }
        return $new_text;
        }
      return $text;
    }//en method t
  }//end class
  
  ?>