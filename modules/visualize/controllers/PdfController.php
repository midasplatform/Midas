<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

class Visualize_PdfController extends Visualize_AppController
{
  public $_models = array('Item', 'ItemRevision', 'Bitstream');
  /** index */
  public function indexAction()
    {
    $this->disableLayout();
    $this->disableView();
    $itemid = $this->_getParam('itemId');
    $item = $this->Item->load($itemid);
    
    if($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("This item doesn't exist  or you don't have the permissions.");
      }   
    
    $revision = $this->Item->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();    
    if(count($bitstreams) != 1)
      {
      throw new Zend_Exception('Error');
      }
    $this->bistream = $bitstreams[0];
    echo "<html>";
    echo "<object height='750' width='750' type='application/pdf' data='".$this->view->webroot."/download?items=".$item->getKey()."'> <param name='src' value='".$this->view->webroot."/download?items=".$item->getKey()."'/></object>";
    
    echo "</html>";
    }
} // end class
?>
