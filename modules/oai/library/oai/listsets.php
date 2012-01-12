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
$folderModel = $modelLoader->loadModel('Folder');
// parse and check arguments
foreach($args as $key => $val)
  {
  switch ($key)
    { 
    case 'resumptionToken':
      $resumptionToken = $val;
      $errors .= oai_error('badResumptionToken', $key, $val); 
      break;

    default:
      $errors .= oai_error('badArgument', $key, $val);
    }
  }

// break and clean up on error
if ($errors != '')
  {
  oai_exit();
  }


$output .= "  <ListSets>\n";


$collections = $folderModel->getAll();
if (empty($collections))
  {
  $errors .= oai_error('noRecordsMatch'); 
  }

foreach($collections as $collection)
  {
  if(!$collection instanceof FolderDao || !$folderModel->policyCheck($collection, null, MIDAS_POLICY_READ))
    {
    continue;
    }
  $setSpecs = array();
  $colletion_id = $collection->getFolderId();
  $setSpecs[] = $collection->getUuid();

  $name = $collection->getName();
  $description = $collection->getDescription();

  xmlset($setSpecs, $name, $description);
  }
$output .= "  </ListSets>\n"; 


function xmlset($setSpecs, $name, $desc)
{
  global $output;

  $output .= "   <set>\n";

  for($i = 0 ; $i<count($setSpecs) ; $i++)
    {
    $output .= xmlformat($setSpecs[$i], 'setSpec', '', 4);
    }

  $output .= xmlformat($name, 'setName', '', 4);
  
  if (isset($val['setDescription']) && $val['setDescription'] != '')
    {
    $output .= "    <setDescription>\n";
    $prefix = 'oai_dc';
    $output .= metadataHeader($prefix);
    $output .= xmlrecord($desc, 'dc:description', '', 7);
    $output .= '     </'.$prefix;
    if (isset($METADATAFORMATS[$prefix]['record_prefix']))
      {
      $output .= ':'.$METADATAFORMATS[$prefix]['record_prefix'];
      }
    $output .= ">\n";
    $output .= "    </setDescription>\n";
    }
  $output .= "   </set>\n";
}
?>
