<?php
class  Zend_View_Helper_Userthumbnail
{
    /** translation helper */
    function userthumbnail($thumbnail, $id = '')
    {
    if(!empty($thumbnail) && strpos($thumbnail, 'http://') === false)
      {
      echo "<img id='{$id}' class='thumbnailSmall' src='{$this->view->webroot}/{$thumbnail}' alt=''/>";
      }
    else if(!empty($thumbnail) && strpos($thumbnail, 'http://') !== false)
      {
      echo "<img id='{$id}' class='thumbnailSmall' src='{$thumbnail}' alt=''/>";
      }
    else
      {
      echo "<img id='{$id}' class='thumbnailSmall' src='{$this->view->coreWebroot}/public/images/icons/unknownUser.png' alt=''/>";
      }
    }
    

    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class