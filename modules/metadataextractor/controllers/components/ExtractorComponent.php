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

/** Extractor component for the metadataextractor module */
class Metadataextractor_ExtractorComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'metadataextractor';

    /** extract metadata */
    public function extract($revision)
    {
        /** @var ItemRevisionModel $itemRevisionModel */
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
        $revision = $itemRevisionModel->load($revision['itemrevision_id']);
        if (!$revision) {
            return;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) != 1) {
            return;
        }
        $bitstream = $bitstreams[0];
        $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));

        /** @var MetadataModel $metadataModel */
        $metadataModel = MidasLoader::loadModel('Metadata');
        if ($ext == 'pdf') {
            $pdf = Zend_Pdf::load($bitstream->getFullPath());
            foreach ($pdf->properties as $name => $property) {
                $name = strtolower($name);
                try {
                    $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'misc', $name);
                    if (!$metadataDao) {
                        $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'misc', $name, '');
                    }
                    $metadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT, 'misc', $name, $property);
                } catch (Zend_Exception $exc) {
                    echo $exc->getMessage();
                }
            }
        } else {
            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');
            $command = $settingModel->getValueByName(METADATAEXTRACTOR_HACHOIR_METADATA_COMMAND_KEY, $this->moduleName);
            exec(str_replace("'", '"', $command).' "'.$bitstream->getFullPath().'"', $output);

            if (!isset($output[0]) || $output[0] != 'Metadata:') {
                return;
            }
            unset($output[0]);
            foreach ($output as $out) {
                $out = substr($out, 2);
                $pos = strpos($out, ': ');
                $name = strtolower(substr($out, 0, $pos));
                $value = substr($out, $pos + 2);
                try {
                    $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'misc', $name);
                    if (!$metadataDao) {
                        $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'misc', $name, '');
                    }
                    $metadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT, 'misc', $name, $value);
                } catch (Zend_Exception $exc) {
                    echo $exc->getMessage();
                }
            }
        }
    }
}
