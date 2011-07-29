<?php
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
      break;
  
    default:
      $errors .= oai_error('badArgument', $key, $val);
    }
}

if (isset($args['identifier']))
{
  $itemModel = $modelLoader->loadModel('Item');
  $itemRevisionModel = $modelLoader->loadModel('ItemRevision');
  require_once BASE_PATH.'/core/controllers/components/UuidComponent.php';
  $uuiComponent = new UuidComponent();
  // remove the OAI part to get the identifier
  $uuid = str_replace($oaiprefix, '', $identifier); 

  $element = $uuiComponent->getByUid($uuid);
  if ($element == false || !$element instanceof ItemDao)
    {
    $errors .= oai_error('idDoesNotExist', 'identifier', $identifier);
    }
  elseif(!$itemModel->policyCheck($element, null, MIDAS_POLICY_READ))
    {
    $errors .= oai_error('idDoesNotExist', 'identifier', $identifier);
    }   
}

//break and clean up on error
if ($errors != '')
  {
  oai_exit();
  }

// currently it is assumed that an existing identifier
// can be served in all available metadataformats...
if (is_array($METADATAFORMATS))
  {
  $output .= " <ListMetadataFormats>\n";
  foreach($METADATAFORMATS as $key=>$val)
    {
    $output .= "  <metadataFormat>\n";
    $output .= xmlformat($key, 'metadataPrefix', '', 3);
    $output .= xmlformat($val['schema'], 'schema', '', 3);
    $output .= xmlformat($val['metadataNamespace'], 'metadataNamespace', '', 3);
    $output .= "  </metadataFormat>\n";
    }
  $output .= " </ListMetadataFormats>\n"; 
  }
else
  {
  $errors .= oai_error('noMetadataFormats'); 
  oai_exit();
  }

?>
