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

// server status
define("MIDAS_DICOM_STORESCP_IS_RUNNING", 1);
define("MIDAS_DICOM_DCMQRSCP_IS_RUNNING", 2);
define("MIDAS_DICOM_SERVER_NOT_RUNNING", 0);
define("MIDAS_DICOM_SERVER_NOT_SUPPORTED", -1);
// default subdirectories and files
define("LOG_DIR", "/logs");
define("PROCESSING_DIR", "/processing");
define("PACS_DIR", "/pacs");
define("DCMQRSCP_CFG_FILE", "/dcmqrscp_midas.cfg");

?>
