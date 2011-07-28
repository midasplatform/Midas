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

// please change to the according metadata prefix you use 
$prefix = 'oai_dc';

$output .= '   <metadata>'."\n";
$output .= metadataHeader($prefix);

foreach($metadata as $m)
  {
  xmlgetinfo($m->getMetadataId(), $m->getValue());
  }

// Here, no changes need to be done
$output .= '     </'.$prefix;
if (isset($METADATAFORMATS[$prefix]['record_prefix']))
  {
  $output .= ':'.$METADATAFORMATS[$prefix]['record_prefix'];
  }
$output .= ">\n";
$output .= '   </metadata>'."\n";
?>
