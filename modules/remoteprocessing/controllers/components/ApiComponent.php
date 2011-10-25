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

/** Component for api methods */
class Remoteprocessing_ApiComponent extends AppComponent
{
  /**
   * Register a server
   * @param email (Optional)
   * @param apikey (Optional)
   * @param securitykey Set in configuration
   * @param os (Optional) Operating System
   * @return Array (token, apikey and email)
   */
  public function registerserver($args)
    {
    $os = '';
    $apikey = '';
    $email = '';
    $securitykey = '';
    if(isset($args['os']))
      {
      $os = $args['os'];
      }
    if(isset($args['apikey']))
      {
      $apikey = $args['apikey'];
      }
    if(isset($args['email']))
      {
      $email = $args['email'];
      }
    if(isset($args['securitykey']))
      {
      $securitykey = $args['securitykey'];
      }

    $modulesConfig=Zend_Registry::get('configsModules');
    $checkSecuritykey = $modulesConfig['remoteprocessing']->securitykey;
    if(empty($securitykey) || $securitykey != $checkSecuritykey)
      {
      throw new Exception('Error security key.', MIDAS_INVALID_PARAMETER);
      }

    $modelLoader = new MIDAS_ModelLoader();
    $userModel = $modelLoader->loadModel('User');
    $groupModel = $modelLoader->loadModel('Group');
    $Api_UserapiModel = $modelLoader->loadModel('Userapi', 'api');
    if(empty($apikey))
      {
      if(empty($os))
        {
        throw new Exception('Error os parameter.', MIDAS_INVALID_PARAMETER);
        }
      $email = uniqid().'@foo.com';
      $userDao = $userModel->createUser($email, uniqid(), 'Processing', 'Server');
      $userDao->setPrivacy(MIDAS_USER_PRIVATE);
      $userDao->setCompany($os); //used to set operating system
      $userModel->save($userDao);

      $serverGroup = $groupModel->load(MIDAS_GROUP_SERVER_KEY);
      $groupModel->addUser($serverGroup, $userDao);
      $userapiDao = $Api_UserapiModel->createKey($userDao, 'remoteprocessing', '100');
      $apikey = $userapiDao->getApikey();

      Zend_Registry::get('notifier')->callback('CALLBACK_REMOTEPROCESSING_CREATESERVER', $userDao->toArray());
      }

    $tokenDao = $Api_UserapiModel->getToken($email, $apikey, 'remoteprocessing');
    if(empty($tokenDao))
      {
      throw new Exception('Unable to authenticate.Please check credentials.', MIDAS_INVALID_PARAMETER);
      }

    $data['token'] = $tokenDao->getToken();
    $data['email'] = $email;
    $data['apikey'] = $apikey;
    return $data;
    }

  /**
   * The client Ping Midas and MIDAS tells what it should do
   * @param token
   * @return Array
   */
  public function keepaliveserver($args)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
    if($userDao == false)
      {
      throw new Exception('Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER);
      }

    $groupModel = $modelLoad->loadModel('Group');
    $groupServer = $groupModel->load(MIDAS_GROUP_SERVER_KEY);
    $users = $groupServer->getUsers();

    $isServer = false;
    foreach($users as $user)
      {
      if($user->getKey() == $userDao->getKey())
        {
        $isServer = true;
        }
      }

    if($isServer == false)
      {
      throw new Exception('Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER);
      }

    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $jobs = $jobModel->getBy(MIDAS_REMOTEPROCESSING_OS_WINDOWS, '');

    if(empty($jobs))
      {
      $paramsReturn['action'] = 'wait';
      }
    else
      {
      $paramsReturn['action'] = 'process';
      $params = $jobs[0]->getParams();
      $paramsReturn['params'] = JsonComponent::decode($jobs[0]->getParams());
      $paramsReturn['script'] = $jobs[0]->getScript();
      $paramsReturn['params']['job_id'] = $jobs[0]->getKey();
      $paramsReturn['params'] = JsonComponent::encode($paramsReturn['params']);
      //$jobs[0]->setStatus(MIDAS_REMOTEPROCESSING_STATUS_STARTED);
      $jobModel->save($jobs[0]);
      }

    return $paramsReturn;
    }

  /**
   * The client sends the results to MIDAS (put request)
   * @param token
   */
  public function resultsserver($args)
    {
    if($_SERVER['REQUEST_METHOD'] != 'POST')
      {
      throw new Exception('Should be a put request.', MIDAS_INVALID_PARAMETER);
      }
    $modelLoad = new MIDAS_ModelLoader();
    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
    if($userDao == false)
      {
      throw new Exception('Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER);
      }
    $groupModel = $modelLoad->loadModel('Group');
    $groupServer = $groupModel->load(MIDAS_GROUP_SERVER_KEY);
    $users = $groupServer->getUsers();

    $isServer = false;
    foreach($users as $user)
      {
      if($user->getKey() == $userDao->getKey())
        {
        $isServer = true;
        }
      }

    if($isServer == false)
      {
      throw new Exception('Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER);
      }

    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $jobs = $jobModel->getBy(MIDAS_REMOTEPROCESSING_OS_WINDOWS, '');
    if(!file_exists(BASE_PATH.'/tmp/remoteprocessing'))
      {
      mkdir(BASE_PATH.'/tmp/remoteprocessing');
      }

    $destionation = BASE_PATH.'/tmp/remoteprocessing/'.rand(1, 1000).time();
    while(file_exists($destionation))
      {
      $destionation = BASE_PATH.'/tmp/remoteprocessing/'.rand(1, 1000).time();
      }
    mkdir($destionation);
    move_uploaded_file($_FILES['file']['tmp_name'], $destionation."/results.zip");

    if(file_exists($destionation."/results.zip"))
      {
      mkdir($destionation.'/content');
      $target_directory= $destionation.'/content';
      $filter = new Zend_Filter_Decompress(array(
        'adapter' => 'Zip',
        'options' => array(
          'target' => $target_directory,
            )
        ));
      $compressed = $filter->filter($destionation."/results.zip");
      if($compressed && file_exists($target_directory.'/parameters.txt'))
        {
        $info = file_get_contents($target_directory.'/parameters.txt');
        $info = JsonComponent::decode($info);
        $job_id = $info['job_id'];
        $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
        $jobDao = $jobModel->load($job_id);
        $jobDao->setStatus(MIDAS_REMOTEPROCESSING_STATUS_DONE);
        $jobModel->save($jobDao);
        $info['pathResults'] = $destionation.'/content';
        $info['log'] = file_get_contents($target_directory.'/log.txt');
        $info['userKey'] = $userDao->getKey();
        Zend_Registry::get('notifier')->callback($info['resultCallback'], $info);
        }
      else
        {
        throw new Exception('Error, unable to unzip results.', MIDAS_INVALID_PARAMETER);
        }
      }
    else
      {
      throw new Exception('Error, unable to find results.', MIDAS_INVALID_PARAMETER);
      }
    //$this->rrmdir($destionation);
    return array();
    }


    /** recursively delete a folder*/
  private function rrmdir($dir)
    {
    if(is_dir($dir))
      {
      $objects = scandir($dir);
      }

    foreach($objects as $object)
      {
      if($object != "." && $object != "..")
        {
        if(filetype($dir."/".$object) == "dir")
          {
          $this->rrmdir($dir."/".$object);
          }
        else
          {
          unlink($dir."/".$object);
          }
        }
      }
     reset($objects);
     rmdir($dir);
   }

} // end class




