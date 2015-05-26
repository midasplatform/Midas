<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** Index */
class Oai_IndexController extends Oai_AppController
{
    public $_moduleModels = array();
    public $_models = array('Setting');
    public $_components = array();

    /** Before filter */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->apiEnable = true;
    }

    /** Post Dispatch */
    public function postDispatch()
    {
        parent::postDispatch();
        if (!$this->isTestingEnv()) {
            ob_clean();
        }
    }

    /**
     * Index function.
     *
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function indexAction()
    {
        // Need to define some variables global so they can
        // be accessed by the OAI classes
        global $output;
        global $xmlheader;
        global $errors;
        global $granularity;
        global $SQL;
        global $METADATAFORMATS;
        global $XMLSCHEMA;

        $output = '';
        $errors = '';

        if ($this->isTestingEnv()) {
            $_SERVER['SERVER_NAME'] = 'localhost';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            unset($_GET['enabledModules']);
        }
        $MY_URI = 'http://'.$_SERVER['SERVER_NAME'].$this->view->webroot.'/oai';
        $compression = array('gzip', 'deflate');
        $XMLHEADER = '<?xml version="1.0" encoding="UTF-8"?>
      <OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"
               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
               http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">'."\n";

        $responseDate = gmstrftime('%Y-%m-%dT%H:%M:%S').'Z';
        $xmlheader = $XMLHEADER.' <responseDate>'.$responseDate."</responseDate>\n";

        $repositoryName = $this->Setting->getValueByName(OAI_REPOSITORY_NAME_KEY, $this->moduleName);
        $baseURL = $MY_URI;
        $protocolVersion = '2.0';
        $adminEmail = $this->Setting->getValueByName(OAI_ADMIN_EMAIL_KEY, $this->moduleName);
        $earliestDatestamp = 'T00:00:00Z';
        $deletedRecord = 'persistent';
        $granularity = 'YYYY-MM-DDThh:mm:ssZ';
        $show_identifier = false;

        $repositoryIdentifier = $this->Setting->getValueByName(OAI_REPOSITORY_IDENTIFIER_KEY, $this->moduleName);
        $delimiter = ':';
        $idPrefix = '';
        $oaiprefix = 'oai'.$delimiter.$repositoryIdentifier.$delimiter.$idPrefix;
        $setspecprefix = 'hdl_';

        $METADATAFORMATS = array(
            'oai_dc' => array(
                'metadataPrefix' => 'oai_dc',
                'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
                'metadataNamespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
                'myhandler' => 'record_dc.php',
                'record_prefix' => 'dc',
                'record_namespace' => 'http://purl.org/dc/elements/1.1/',
            ),
        );

        $MAXIDS = 5;
        $MAXRECORDS = 5;
        $tokenValid = 24 * 3600;
        $expirationdatetime = gmstrftime('%Y-%m-%dT%H:%M:%SZ', time() + $tokenValid);
        $SQL['split'] = ';';
        $XMLSCHEMA = 'http://www.w3.org/2001/XMLSchema-instance';

        $MidasTempDirectory = $this->getTempDirectory();

        require_once BASE_PATH.'/modules/oai/library/oai/oaidp-util.php';
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $args = $_GET;
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $args = $_POST;
        } else {
            $errors .= oai_error('badRequestMethod', $_SERVER['REQUEST_METHOD']);
        }

        // Some fixes for CakePHP
        unset($args['url']);

        $reqattr = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                $reqattr .= ' '.$key.'="'.htmlspecialchars(stripslashes($val)).'"';
            }
        }

        // in case register_globals is on, clean up polluted global scope
        $verbs = array('from', 'identifier', 'metadataPrefix', 'set', 'resumptionToken', 'until');
        foreach ($verbs as $val) {
            unset($$val);
        }

        $request = ' <request'.$reqattr.'>'.$MY_URI."</request>\n";
        $request_err = ' <request>'.$MY_URI."</request>\n";

        if (is_array($compression)) {
            $compress = false;
        }

        if (isset($args['verb'])) {
            switch ($args['verb']) {
                case 'GetRecord':
                    unset($args['verb']);
                    include BASE_PATH.'/modules/oai/library/oai/getrecord.php';
                    break;

                case 'Identify':
                    unset($args['verb']);
                    // we never use compression in Identify
                    $compress = false;
                    include BASE_PATH.'/modules/oai/library/oai/identify.php';
                    break;

                case 'ListIdentifiers':
                    unset($args['verb']);
                    include BASE_PATH.'/modules/oai/library/oai/listidentifiers.php';
                    break;

                case 'ListMetadataFormats':
                    unset($args['verb']);
                    include BASE_PATH.'/modules/oai/library/oai/listmetadataformats.php';
                    break;

                case 'ListRecords':
                    unset($args['verb']);
                    include BASE_PATH.'/modules/oai/library/oai/listrecords.php';
                    break;

                case 'ListSets':
                    unset($args['verb']);
                    include BASE_PATH.'/modules/oai/library/oai/listsets.php';
                    break;

                default:
                    // we never use compression with errors
                    $compress = false;
                    $errors .= oai_error('badVerb', $args['verb']);
            } /* switch */
        } else {
            $errors .= oai_error('noVerb');
        }

        if ($errors != '' && $this->isTestingEnv()) {
            echo $errors;
        } elseif ($errors != '') {
            oai_exit();
        }

        if ($compress) {
            ob_start('ob_gzhandler');
        }

        $this->disableLayout();
        $this->disableView();
        if (!$this->isTestingEnv()) {
            header('Content-Type: text/plain');
        }
        echo $xmlheader;
        echo $request;
        echo $output;

        if (!$this->isTestingEnv()) {
            oai_close();
            exit;
        }
    }
}
