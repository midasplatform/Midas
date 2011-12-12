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
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date$
Version:   $Revision$

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php

// parse and check arguments
foreach($args as $key => $val)
  {
  switch ($key)
    { 
    case 'identifier':
      $identifier = $val; 
      if (!is_valid_uri($identifier))
        {
        $errors .= oai_error('badArgument', $key, $val);
        }
      break;

    case 'metadataPrefix':
      if (is_array($METADATAFORMATS[$val])
        && isset($METADATAFORMATS[$val]['myhandler']))
        {
        $metadataPrefix = $val;
        $inc_record  = $METADATAFORMATS[$val]['myhandler'];
        }
      else
        {
        $errors .= oai_error('cannotDisseminateFormat', $key, $val);
        }
      break;

    default:
      $errors .= oai_error('badArgument', $key, $val);
    }
  }

if (!isset($args['identifier']))
  {
  $errors .= oai_error('missingArgument', 'identifier');
  }
if (!isset($args['metadataPrefix']))
  {
  $errors .= oai_error('missingArgument', 'metadataPrefix');
  } 

$itemModel = $modelLoader->loadModel('Item');
$itemRevisionModel = $modelLoader->loadModel('ItemRevision');
require_once BASE_PATH.'/core/controllers/components/UuidComponent.php';
$uuiComponent = new UuidComponent();
  
// remove the OAI part to get the identifier
if(empty($errors))
  {
  $uuid = str_replace($oaiprefix, '', $identifier);
  if ($uuid == '')
    {
    $errors .= oai_error('idDoesNotExist', '', $identifier);
    }

  $element = $uuiComponent->getByUid($uuid);
  
  if ($element == false || !$element instanceof ItemDao)
    {
    $errors .= oai_error('idDoesNotExist', '', $identifier); 
    }
  elseif(!$itemModel->policyCheck($element, null, MIDAS_POLICY_READ))
    {
    $errors .= oai_error('idDoesNotExist', '', $identifier); 
    }
}

// break and clean up on error
if ($errors != '')
  {
  oai_exit();
  }

$output .= "  <GetRecord>\n";

if($element)
  {  
  $identifier = $oaiprefix.$element->getUuid();
  $datestamp = formatDatestamp($element->getDateUpdate()); 
  // print Header
  $output .= '  <record>'."\n";
  $output .= '  <header';
  $output .='>'."\n";

  // use xmlrecord since we include stuff from database;
  $output .= xmlrecord($identifier, 'identifier', '', 3);
  $output .= xmlformat($datestamp, 'datestamp', '', 3);

  $folders = $element->getFolders();

  if (empty($folders))
    {
    $errors .= oai_error('resourceIdDoesNotExist', '', $record[1]); 
    }

  foreach($folders as $folder)
    {
    $setspec = $setspecprefix.str_replace('/', '_', $folder->getUuid());
    $output .= xmlrecord($setspec, 'setSpec', '', 3);
    }
  $output .= '   </header>'."\n"; 

  // return the metadata record itself
  $revision = $itemModel->getLastRevision($element);
  $metadata = $itemRevisionModel->getMetadata($revision);

  include(BASE_PATH.'/modules/oai/library/oai/'.$inc_record); 

  $output .= '  </record>'."\n"; 
  } 
else
  {
  // we should never get here
  oai_error('idDoesNotExist');
  }

// End GetRecord
$output .= ' </GetRecord>'."\n"; 
?>
