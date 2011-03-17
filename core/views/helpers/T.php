<?php
class  Zend_View_Helper_T
{
  /** translation helper */
    function t($text)
    {
      if(Zend_Registry::get('configGlobal')->application->lang=='fr')
        {
        $translate=Zend_Registry::get('translater');
        $new_text=$translate->_($text);
        if($new_text==$text&&$this->isDebug())
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

      /**
   * @method public  isDebug()
   * Is Debug mode ON
   * @return boolean
   */
  public function isDebug()
    {
    $config = Zend_Registry::get('config');
    if ($config->mode->debug == 1)
      {
      return true;
      }
    else
      {
      return false;
      }
    }

    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class