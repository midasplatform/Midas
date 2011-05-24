<?php
class Visualize_IndexController extends Visualize_AppController
{
  public $_moduleComponents=array('Main');
  public $_models=array('Item');
  
  /** init */
  function init()
    {
    $itemId = $this->_getParam('itemId');
    $height = $this->_getParam('height');
    $width = $this->_getParam('width');
    if(!isset($height))
      {
      $height = 500;
      }
    if(!isset($width))
      {
      $width = 500;
      }
    $itemId = $this->_getParam('itemId');
    $itemDao = $this->Item->load($itemId);
    if($this->ModuleComponent->Main->canVisualizeWithParaview($itemDao))
      {
      $this->_redirect('/visualize/paraview/?itemId='.$itemId.'&height='.$height.'&width='.$width);
      }
    else
      {
      throw new Zend_Exception('Unable to visualize');
      }
    }
    
  /** index*/
  function indexAction()
    {
    
    }
  } // end class
?>
