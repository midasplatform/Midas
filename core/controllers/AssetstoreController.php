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
  public $_models = array('Assetstore', 'Bitstream', 'Progress', 'Setting');
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
    }

  /** change default assetstore*/
  function defaultassetstoreAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();
    $change = $this->getParam("submitDefaultAssetstore");
    $element = $this->getParam("element");
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

  /** delete an assetstore */
  function deleteAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();
    $assetstoreId = $this->getParam("assetstoreId");
    if(isset($assetstoreId))
      {
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

  /** edit an assetstore */
  function editAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();
    $assetstoreId = $this->getParam("assetstoreId");
    $assetstoreName = $this->getParam("assetstoreName");
    $assetstorePath = $this->getParam("assetstorePath");
    if(!is_dir($assetstorePath))
      {
      echo JsonComponent::encode(array(false, 'The path provided is not a valid directory'));
      return false;
      }
    if(!is_writable($assetstorePath))
      {
      echo JsonComponent::encode(array(false, 'The specified directory is not writable'));
      return false;
      }
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
      echo json_encode(array('error' => 'Missing or invalid form values.'));
      return false;
      }

    if($this->getRequest()->isPost() && $form->isValid($_POST))
      {
      // Save the assetstore in the database
      $assetstoreDao = new AssetstoreDao();
      $assetstoreDao->setName($form->name->getValue());
      $assetstoreDao->setPath($form->basedirectory->getValue());
      $assetstoreDao->setType($form->assetstoretype->getValue());

      if(!is_dir($assetstoreDao->getPath()))
        {
        echo JsonComponent::encode(array('error' => 'The path provided is not a valid directory'));
        return false;
        }
      if(!is_writable($assetstoreDao->getPath()))
        {
        echo JsonComponent::encode(array('error' => 'The specified directory is not writable'));
        return false;
        }

      try
        {
        $this->Assetstore->save($assetstoreDao);
        }
      catch(Zend_Exception $ze)
        {
        echo JsonComponent::encode(array('error' => $ze->getMessage()));
        return false;
        }

      $totalSpace = UtilityComponent::diskTotalSpace($assetstoreDao->getPath());
      $freeSpace = UtilityComponent::diskFreeSpace($assetstoreDao->getPath());

      echo JsonComponent::encode(array(
        'msg' => 'The assetstore has been added.',
        'assetstore_id' => $assetstoreDao->getAssetstoreId(),
        'assetstore_name' => $assetstoreDao->getName(),
        'assetstore_type' => $assetstoreDao->getType(),
        'totalSpace' => $totalSpace,
        'totalSpaceText' => $this->Component->Utility->formatSize($totalSpace),
        'freeSpace' => $freeSpace,
        'freeSpaceText' => $this->Component->Utility->formatSize($freeSpace)));
      return true;
      }

    echo json_encode(array('error' => 'Bad request.'));
    return false;
    } // end import action

  /**
   * Prompt an admin user to move files from one assetstore to another
   * @param srcAssetstoreId The assetstore id to move files from
   */
  function movedialogAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();

    $srcAssetstoreId = $this->getParam('srcAssetstoreId');

    if(!$srcAssetstoreId)
      {
      throw new Zend_Exception('Must provide srcAssetstoreId parameter');
      }

    $srcAssetstore = $this->Assetstore->load($srcAssetstoreId);

    if(!($srcAssetstore instanceof AssetstoreDao))
      {
      throw new Zend_Exception('Invalid srcAssetstoreId');
      }
    $this->view->assetstores = $this->Assetstore->getAll();
    $this->view->srcAssetstore = $srcAssetstore;
    }

  /**
   * Move all bitstreams from one assetstore into another. Asynchronous progress enabled.
   * @param srcAssetstoreId The id of the source assetstore
   * @param dstAssetstoreId The id of the destination assetstore
   */
  function movecontentsAction()
    {
    $this->requireAdminPrivileges();
    $this->disableView();
    $this->disableLayout();

    $srcAssetstoreId = $this->getParam('srcAssetstoreId');
    $dstAssetstoreId = $this->getParam('dstAssetstoreId');

    if(!$srcAssetstoreId || !$dstAssetstoreId)
      {
      throw new Zend_Exception('Must provide srcAssetstoreId and dstAssetstoreId parameters');
      }
    if($srcAssetstoreId == $dstAssetstoreId)
      {
      return;
      }
    $srcAssetstore = $this->Assetstore->load($srcAssetstoreId);
    $dstAssetstore = $this->Assetstore->load($dstAssetstoreId);

    if(!($srcAssetstore instanceof AssetstoreDao) || !($dstAssetstore instanceof AssetstoreDao))
      {
      throw new Zend_Exception('Invalid srcAssetstoreId or dstAssetstoreId');
      }

    if($this->progressDao)
      {
      $this->progressDao->setMaximum($this->Bitstream->countAll($srcAssetstore));
      $this->progressDao->setMessage('Moving all bitstreams...');
      $this->Progress->save($this->progressDao);
      }

    $this->Assetstore->moveBitstreams($srcAssetstore, $dstAssetstore, $this->progressDao);

    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Bitstreams moved'));
    }
  } // end class
