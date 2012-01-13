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

/**
 *  Assetstore Controller
 *  Assetstore Controller
 */
class AssetstoreController extends AppController
  {

  public $_models = array('Assetstore', 'Setting');
  public $_daos = array('Assetstore');
  public $_components = array('Utility');
  public $_forms = array('Assetstore');

  /**
   * @method init()
   *  Init Controller
   */
  function init()
    {
    $this->view->menu = "assetstore";
    }  // end init()

  /**
   * \fn indexAction()
   * \brief Index Action (first action when we access the application)
   */
  function indexAction()
    {

    }// end indexAction


  /** change default assetstore*/
  function defaultassetstoreAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();
    $change = $this->_getParam("submitDefaultAssetstore");
    $element = $this->_getParam("element");
    if(isset($change) && isset($element))
      {
      $assetstore = $this->Assetstore->load($element);
      if($assetstore != false)
        {
        $this->Setting->setConfig('default_assetstore', (string)$assetstore->getKey());
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      }
    echo JsonComponent::encode(array(false, $this->t('Error')));
    }//defaultassetstoreAction


  /** delete an assetstore assetstore*/
  function deleteAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();
    $assetstoreId = $this->_getParam("assetstoreId");
    if(isset($assetstoreId))
      {
      set_time_limit(0); // No time limit since import can take a long time
      $assetstore = $this->Assetstore->load($assetstoreId);
      if($assetstore != false)
        {
        $this->Assetstore->delete($assetstore);
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      }
    echo JsonComponent::encode(array(false, $this->t('Error')));
    }//deleteAction

  /** edit an assetstore assetstore*/
  function editAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();
    $assetstoreId = $this->_getParam("assetstoreId");
    $assetstoreName = $this->_getParam("assetstoreName");
    $assetstorePath = $this->_getParam("assetstorePath");
    if(isset($assetstoreId) && !empty($assetstorePath) && file_exists($assetstorePath) && !empty($assetstoreName))
      {
      $assetstore = $this->Assetstore->load($assetstoreId);
      if($assetstore != false)
        {
        $assetstore->setName($assetstoreName);
        $assetstore->setPath($assetstorePath);
        try
          {
          $this->Assetstore->save($assetstore);
          }
        catch(Zend_Exception $ze)
          {
          echo JsonComponent::encode(array(false, $ze->getMessage()));
          return;
          }
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      }
    echo JsonComponent::encode(array(false, $this->t('Error')));
    }//editAction

  /**
   * \fn indexAction()
   * \brief called from ajax
   */
  function addAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();

    $form = $this->Form->Assetstore->createAssetstoreForm();
    if($this->getRequest()->isPost() && !$form->isValid($_POST))
      {
      echo json_encode(array('error' => 'The form is invalid. Missing values.'));
      return false;
      }

    if($this->getRequest()->isPost() && $form->isValid($_POST))
      {
      // Save the assetstore in the database
      $assetstoreDao = new AssetstoreDao();
      $assetstoreDao->setName($form->name->getValue());
      $assetstoreDao->setPath($form->basedirectory->getValue());
      $assetstoreDao->setType($form->assetstoretype->getValue());
      try
        {
        $this->Assetstore->save($assetstoreDao);
        }
      catch(Zend_Exception $ze)
        {
        echo json_encode(array('error' => $ze->getMessage()));
        return false;
        }

      echo json_encode(array('msg' => 'The assetstore has been added.',
                       'assetstore_id' => $assetstoreDao->getAssetstoreId(),
                       'assetstore_name' => $assetstoreDao->getName(),
                       'assetstore_type' => $assetstoreDao->getType(),
                       'totalSpace' => disk_total_space($assetstoreDao->getPath()),
                       'totalSpaceText' => $this->Component->Utility->formatSize(disk_total_space($assetstoreDao->getPath())),
                       'freeSpace' => disk_free_space($assetstoreDao->getPath()),
                       'freeSpaceText' => $this->Component->Utility->formatSize(disk_free_space($assetstoreDao->getPath())),
                       ));
      return true;
      }

    echo json_encode(array('error' => 'Bad request.'));
    return false;
    } // end import action


} // end class

