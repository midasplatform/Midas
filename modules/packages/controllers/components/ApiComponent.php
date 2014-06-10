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
class Packages_ApiComponent extends AppComponent
  {

  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }

  /**
   * Helper function to get the user from token or session authentication
   */
  private function _getUser($args)
    {
    $authComponent = MidasLoader::loadComponent('Authentication');
    return $authComponent->getUser($args, null);
    }

  /**
   * Read in the streamed uploaded file and write it to a temporary file.
   * Returns the name of the temporary file.
   */
  private function _readUploadedFile($prefix)
    {
    set_time_limit(0);
    $inputfile = 'php://input';
    $tmpfile = tempnam(BASE_PATH.'/tmp/misc', $prefix);
    $in = fopen($inputfile, 'rb');
    $out = fopen($tmpfile, 'wb');

    $bufSize = 1024 * 1024;

    $size = 0;
    // read from input and write into file
    while(connection_status() == CONNECTION_NORMAL && ($buf = fread($in, $bufSize)))
      {
      $size += strlen($buf);
      fwrite($out, $buf);
      }
    fclose($in);
    fclose($out);

    return $tmpfile;
    }

  /**
   * Get a filtered list of available extensions
   * @param extension_id (Optional) The extension id
   * @param os (Optional) The target operating system of the package (linux | win | macosx)
   * @param arch (Optional) The os chip architecture (i386 | amd64)
   * @param submissiontype (Optional) Dashboard model used to submit (nightly | experimental | continuous)
   * @param packagetype (Optional) The package type (installer | data | extension)
   * @param productname (Optional) The product name (Example: Slicer)
   * @param category (Optional) The category (Example: Segmentation, Diffusion.Denoising)
   * @param codebase (Optional) The codebase name (Example: Slicer4)
   * @param revision (Optional) The revision of the package
   * @param application_revision (Optional) The application revision the package was built against
   * @param release (Optional) Release identifier associated with a package.
   If not set, it will return both released and non-released packages.
   * @param order (Optional) What parameter to order results by (revision | packagetype | submissiontype | arch | os)
   * @param direction (Optional) What direction to order results by (asc | desc).  Default asc
   * @param limit (Optional) Limit result count. Must be a positive integer.
   * @return An array of extension daos
   */
  public function extensionList($args)
    {
    $extensionsModel = MidasLoader::loadModel('Extension', 'packages');
    $itemModel = MidasLoader::loadModel('Item');

    $extensions = $extensionsModel->get($args);
    $daos = $extensions['extensions'];
    $results = array();

    foreach($daos as $dao)
      {
      $revision = $itemModel->getLastRevision($dao->getItem());
      $bitstreams = $revision->getBitstreams();
      $bitstream = $bitstreams[0];

      $results[] = array('extension_id' => $dao->getKey(),
                         'item_id' => $dao->getItemId(),
                         'os' => $dao->getOs(),
                         'arch' => $dao->getArch(),
                         'revision' => $dao->getRevision(),
                         'application_revision' => $dao->getApplicationRevision(),
                         'repository_type' => $dao->getRepositoryType(),
                         'repository_url' => $dao->getRepositoryUrl(),
                         'submissiontype' => $dao->getSubmissiontype(),
                         'package' => $dao->getPackagetype(),
                         'productname' => $dao->getProductname(),
                         'category' => $dao->getCategory(),
                         'description' => $dao->getDescription(),
                         'screenshots' => $dao->getScreenshots(),
                         'contributors' => $dao->getContributors(),
                         'homepage' => $dao->getHomepage(),
                         'development_status' => $dao->getDevelopmentStatus(),
                         'enabled' => $dao->getEnabled(),
                         'codebase' => $dao->getCodebase(),
                         'release' => $dao->getRelease(),
                         'date_creation' => $dao->getItem()->getDateCreation(),
                         'bitstream_id' => $bitstream->getKey(),
                         'name' => $bitstream->getName(),
                         'md5' => $bitstream->getChecksum(),
                         'size' => $bitstream->getSizebytes()
                         );
      }
    return $results;
    }

  /**
   * Upload an extension package
   * @param os The target operating system of the package
   * @param arch The os chip architecture (i386, amd64, etc)
   * @param name The name of the package (ie installer name)
   * @param repository_type The type of the repository (svn, git)
   * @param repository_url The url of the repository
   * @param revision The svn or git revision of the extension
   * @param application_revision The revision of the application that the extension was built against
   * @param submissiontype Whether this is from a nightly, experimental, continuous, etc dashboard
   * @param packagetype Installer, data, etc
   * @param productname The product name (Ex: Slicer)
   * @param codebase The codebase name (Ex: Slicer4)
   * @param description Text describing the extension
   * @param release (Optional) Release identifier (Ex: 0.0.1, 0.0.2, 0.1)
   * @param icon_url (Optional) The url of the icon for the extension
   * @param development_status (Optional) Arbitrary description of the status of the extension (stable, active, etc)
   * @param category (Optional) Category under which to place the extension. Subcategories should be delimited by . character.
                                If none is passed, will render under the Miscellaneous category.
   * @param enabled (Optional) Boolean indicating if the extension should be automatically enabled after its installation
   * @param homepage (Optional) The url of the extension homepage
   * @param screenshots (Optional) Space-separate list of URLs of screenshots for the extension
   * @param contributors (Optional) List of contributors of the extension
   * @return Status of the upload
   */
  public function extensionUpload($args)
    {
    $this->_checkKeys(array('os',
                            'arch',
                            'name',
                            'revision',
                            'repository_type',
                            'repository_url',
                            'application_revision',
                            'submissiontype',
                            'packagetype',
                            'productname',
                            'codebase',
                            'description'), $args);

    $userDao = $this->_getUser($args);
    if($userDao === false)
      {
      throw new Exception('Invalid user authentication', -1);
      }

    $tmpfile = $this->_readUploadedFile('extension');

    $settingModel = MidasLoader::loadModel('Setting');
    $folderModel = MidasLoader::loadModel('Folder');
    $key = 'extensions.'.$args['submissiontype'].'.folder';
    $folderId = $settingModel->getValueByName($key, 'packages');

    if(!$folderId || !is_numeric($folderId))
      {
      unlink($tmpfile);
      throw new Exception('You must configure a folder id for key '.$key, -1);
      }
    $folder = $folderModel->load($folderId);

    if(!$folder)
      {
      unlink($tmpfile);
      throw new Exception('Folder with id '.$folderId.' does not exist', -1);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
      {
      unlink($tmpfile);
      throw new Exception('Invalid policy on folder '.$folderId, -1);
      }

    $uploadComponent = MidasLoader::loadComponent('Upload');
    $extensionModel = MidasLoader::loadModel('Extension', 'packages');
    $extensionDao = $extensionModel->matchExistingExtension($args);
    if($extensionDao == null)
      {
      $item = $uploadComponent->createUploadedItem($userDao, $args['name'], $tmpfile, $folder);

      // Set the revision comment to the extension's revision
      $itemModel = MidasLoader::loadModel('Item');
      $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
      $itemRevision = $itemModel->getLastRevision($item);
      $itemRevision->setChanges($args['revision']);
      $itemRevisionModel->save($itemRevision);

      if(!$item)
        {
        throw new Exception('Failed to create item', -1);
        }
      $extensionDao = MidasLoader::newDao('ExtensionDao', 'packages');
      }
    else
      {
      $item = $extensionDao->getItem();
      $uploadComponent->createNewRevision($userDao, $args['name'], $tmpfile, $args['revision'], $item->getKey());
      }

    $extensionDao->setItemId($item->getKey());
    $extensionDao->setSubmissiontype($args['submissiontype']);
    $extensionDao->setPackagetype($args['packagetype']);
    $extensionDao->setOs($args['os']);
    $extensionDao->setArch($args['arch']);
    $extensionDao->setRevision($args['revision']);
    $extensionDao->setRepositoryType($args['repository_type']);
    $extensionDao->setRepositoryUrl($args['repository_url']);
    $extensionDao->setApplicationRevision($args['application_revision']);
    $extensionDao->setProductname($args['productname']);
    $extensionDao->setCodebase($args['codebase']);
    $extensionDao->setDescription($args['description']);
    if(array_key_exists('release', $args))
      {
      $extensionDao->setRelease($args['release']);
      }
    if(array_key_exists('icon_url', $args))
      {
      $extensionDao->setIconUrl($args['icon_url']);
      }
    if(array_key_exists('development_status', $args))
      {
      $extensionDao->setDevelopmentStatus($args['development_status']);
      }
    if(array_key_exists('category', $args))
      {
      $extensionDao->setCategory($args['category']);
      }
    if(array_key_exists('enabled', $args))
      {
      $extensionDao->setEnabled($args['enabled']);
      }
    if(array_key_exists('homepage', $args))
      {
      $extensionDao->setHomepage($args['homepage']);
      }
    if(array_key_exists('screenshots', $args))
      {
      $extensionDao->setScreenshots($args['screenshots']);
      }
    if(array_key_exists('contributors', $args))
      {
      $extensionDao->setContributors($args['contributors']);
      }

    $extensionModel->save($extensionDao);

    return array('extension' => $extensionDao);
    }

  /**
   * Get a filtered list of available packages
   * @param os (Optional) The target operating system of the package (linux | win | macosx)
   * @param arch (Optional) The os chip architecture (i386 | amd64)
   * @param submissiontype (Optional) Dashboard model used to submit (nightly | experimental | continuous)
   * @param packagetype (Optional) The package type (installer | data | extension)
   * @param productname (Optional) The product name (Example: Slicer)
   * @param codebase (Optional) The codebase name (Example: Slicer4)
   * @param revision (Optional) The revision of the package
   * @param release (Optional) Release identifier associated with a package.
   If not set, it will return both released and non-released packages.
   * @param order (Optional) What parameter to order results by (revision | packagetype | submissiontype | arch | os)
   * @param direction (Optional) What direction to order results by (asc | desc).  Default asc
   * @param limit (Optional) Limit result count. Must be a positive integer.
   * @return An array of packages
   */
  public function packageList($args)
    {
    $packagesModel = MidasLoader::loadModel('Package', 'packages');
    $itemModel = MidasLoader::loadModel('Item');

    $daos = $packagesModel->get($args);

    $results = array();
    foreach($daos as $dao)
      {
      $revision = $itemModel->getLastRevision($dao->getItem());
      $bitstreams = $revision->getBitstreams();
      $bitstreamsArray = array();
      foreach($bitstreams as $bitstream)
        {
        $bitstreamsArray[] = array('bitstream_id' => $bitstream->getKey(),
                                   'name' => $bitstream->getName(),
                                   'md5' => $bitstream->getChecksum(),
                                   'size' => $bitstream->getSizebytes());
        }

      $results[] = array('package_id' => $dao->getKey(),
                         'item_id' => $dao->getItemId(),
                         'os' => $dao->getOs(),
                         'arch' => $dao->getArch(),
                         'revision' => $dao->getRevision(),
                         'submissiontype' => $dao->getSubmissiontype(),
                         'package' => $dao->getPackagetype(),
                         'name' => $dao->getItem()->getName(),
                         'productname' => $dao->getProductname(),
                         'codebase' => $dao->getCodebase(),
                         'release' => $dao->getRelease(),
                         'checkoutdate' => $dao->getCheckoutdate(),
                         'date_creation' => $dao->getItem()->getDateCreation(),
                         'bitstreams' => $bitstreamsArray);
      }
    return $results;
    }

  /**
   * Upload a core package
   * @param os The target operating system of the package
   * @param arch The os chip architecture (i386, amd64, etc)
   * @param name The name of the package (ie installer name)
   * @param revision The svn or git revision of the installer
   * @param submissiontype Whether this is from a nightly, experimental, continuous, etc dashboard
   * @param packagetype The type of the package (zip installer, NSIS installer, OSX Bundle, Source, etc)
   * @param folderId The id of the folder to upload into
   * @param applicationId The id of the application that this package corresponds to
   * @param productname The product name (Ex: Slicer)
   * @param codebase The codebase name (Ex: Slicer4)
   * @param release (Optional) Release identifier (Ex: 4.0.0, 4.2)
   * @param checkoutdate (Optional) The checkout date of the repository that the package was built from
   * @return Status of the upload
   */
  public function packageUpload($args)
    {
    $this->_checkKeys(array('os',
                            'arch',
                            'name',
                            'revision',
                            'submissiontype',
                            'packagetype',
                            'productname',
                            'codebase',
                            'applicationId',
                            'folderId'), $args);

    $userDao = $this->_getUser($args);
    if($userDao === false)
      {
      throw new Exception('Invalid user authentication', -1);
      }

    $tmpfile = $this->_readUploadedFile('package');

    $folderModel = MidasLoader::loadModel('Folder');
    $communityModel = MidasLoader::loadModel('Community');
    $applicationModel = MidasLoader::loadModel('Application', 'packages');

    $folderId = $args['folderId'];
    $applicationId = $args['applicationId'];

    $folder = $folderModel->load($folderId);
    $application = $applicationModel->load($applicationId);

    if(!$folder)
      {
      unlink($tmpfile);
      throw new Exception('Folder with id '.$folderId.' does not exist', -1);
      }
    if(!$application)
      {
      unlink($tmpfile);
      throw new Exception('Application with id '.$applicationId.' does not exist', -1);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
      {
      unlink($tmpfile);
      throw new Exception('Invalid policy on folder '.$folderId, -1);
      }
    if(!$communityModel->policyCheck($application->getProject()->getCommunity(), $userDao, MIDAS_POLICY_WRITE))
      {
      unlink($tmpfile);
      throw new Exception('Must have write access into the project to upload packages to application');
      }

    $uploadComponent = MidasLoader::loadComponent('Upload');
    $item = $uploadComponent->createUploadedItem($userDao, $args['name'], $tmpfile, $folder);

    if(!$item)
      {
      throw new Exception('Failed to create item', -1);
      }
    $packageModel = MidasLoader::loadModel('Package', 'packages');
    $packageDao = MidasLoader::newDao('PackageDao', 'packages');
    $packageDao->setItemId($item->getKey());
    $packageDao->setApplicationId($application->getKey());
    $packageDao->setSubmissiontype($args['submissiontype']);
    $packageDao->setPackagetype($args['packagetype']);
    $packageDao->setOs($args['os']);
    $packageDao->setArch($args['arch']);
    $packageDao->setRevision($args['revision']);
    $packageDao->setProductname($args['productname']);
    $packageDao->setCodebase($args['codebase']);
    if(array_key_exists('release', $args))
      {
      $packageDao->setRelease($args['release']);
      }
    if(array_key_exists('checkoutdate', $args))
      {
      $packageDao->setCheckoutdate($args['checkoutdate']);
      }
    else
      {
      $packageDao->setCheckoutdate(date("Y-m-d H:i:s"));
      }
    $packageModel->save($packageDao);

    return array('package' => $packageDao);
    }

  /**
   * Call this to download the version of the client script corresponding to this server instance.
   * @param client (Optional) Which client to download (default is cmake)
   * @return The script containing functions to allow package uploads
   */
  public function scriptDownload($args)
    {
    $client = array_key_exists('client', $args) ? $args['client'] : 'cmake';
    $path = BASE_PATH.'/modules/packages/public/clients/'.$client;
    if(!$client || !is_dir($path))
      {
      throw new Exception('Could not find directory for client '.$client, -1);
      }
    $file = $path.'/MidasAPIScript.'.$client;
    if(!file_exists($file))
      {
      throw new Exception('Could not find script file '.$file, -1);
      }
    // our scripts should never be large enough to have to worry about memory constraints
    echo file_get_contents($file);
    exit();
    }
  } // end class
