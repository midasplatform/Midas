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

/** Utility component */
class UtilityComponent extends AppComponent
{
    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recursively loops through and builds up an XML document.
     *
     * @param array $data
     * @param string $rootNodeName what you want the root node to be - defaults to data.
     * @param null|SimpleXMLElement $xml should only be used recursively
     * @return string XML
     */
    public function toXml($data, $rootNodeName = 'data', $xml = null)
    {
        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if (ini_get('zend.ze1_compatibility_mode') == 1) {
            ini_set('zend.ze1_compatibility_mode', 0);
        }

        if ($xml == null) {
            $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><".$rootNodeName.' />');
        }

        // loop through the data passed in.
        foreach ($data as $key => $value) {
            // no numeric keys in our xml please!
            if (is_numeric($key)) {
                // make string key...
                $key = 'unknownNode_'.(string) $key;
            }

            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z]/i', '', $key);

            // if there is another array found recursively call this function
            if (is_array($value)) {
                $node = $xml->addChild($key);
                // recursive call.
                $this->toXml($value, $rootNodeName, $node);
            } else {
                // add single node.
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $xml->addChild($key, $value);
            }
        }

        // pass back as string. or simple xml object if you want!
        return $xml->asXML();
    }

    /**
     * Get all the modules.
     *
     * @return array
     */
    public function getAllModules()
    {
        $modules = array();
        if (file_exists(BASE_PATH.'/modules/') && opendir(BASE_PATH.'/modules/')
        ) {
            $array = $this->_initModulesConfig(BASE_PATH.'/modules/');
            $modules = array_merge($modules, $array);
        }

        if (file_exists(BASE_PATH.'/privateModules/') && opendir(BASE_PATH.'/privateModules/')
        ) {
            $array = $this->_initModulesConfig(BASE_PATH.'/privateModules/');
            $modules = array_merge($modules, $array);
        }

        return $modules;
    }

    /**
     * Helper method to extract tokens from request URI's in path form,
     * e.g. download/folder/123/folder_name, starting after the action name.
     * Returns the token as a list.
     *
     * @return array
     */
    public static function extractPathParams()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $allTokens = preg_split('@/@', $request->getPathInfo(), null, PREG_SPLIT_NO_EMPTY);

        $tokens = array();
        $i = 0;
        if ($request->getModuleName() != 'default') {
            $i++;
        }
        if ($request->getControllerName() != 'index') {
            $i++;
        }
        if ($request->getActionName() != 'index') {
            $i++;
        }
        $max = count($allTokens);
        for (; $i < $max; $i++) {
            $tokens[] = $allTokens[$i];
        }

        return $tokens;
    }

    /**
     * find modules configuration in a folder.
     *
     * @param string $dir
     * @return array
     */
    private function _initModulesConfig($dir)
    {
        $handle = opendir($dir);
        $modules = array();
        while (false !== ($file = readdir($handle))) {
            if (is_dir($dir.$file) && file_exists($dir.$file.'/configs/module.ini')
            ) {
                $config = new Zend_Config_Ini($dir.$file.'/configs/module.ini', 'global', true);
                $config->db = array();
                if (!file_exists($dir.$file.'/database') || (!file_exists($dir.$file.'/database/mysql') && !file_exists($dir.$file.'/database/pgsql') && !file_exists($dir.$file.'/database/sqlite'))) {
                    $config->db->PDO_MYSQL = true;
                    $config->db->PDO_PGSQL = true;
                    $config->db->PDO_SQLITE = true;
                } else {
                    $handleDB = opendir($dir.$file.'/database');
                    if (file_exists($dir.$file.'/database')) {
                        while (false !== ($fileDB = readdir($handleDB))) {
                            if (file_exists($dir.$file.'/database/'.$fileDB.'/')) {
                                switch ($fileDB) {
                                    case 'mysql':
                                        $config->db->PDO_MYSQL = true;
                                        break;
                                    case 'pgsql':
                                        $config->db->PDO_PGSQL = true;
                                        break;
                                    case 'sqlite':
                                        $config->db->PDO_SQLITE = true;
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
                }
                $modules[$file] = $config;
            }
        }
        closedir($handle);

        return $modules;
    }

    /**
     * format long names.
     *
     * @param string  $name
     * @param int $nchar
     * @return string
     */
    public static function sliceName($name, $nchar)
    {
        if (strlen($name) > $nchar) {
            $toremove = (strlen($name)) - $nchar;
            if ($toremove < 8) {
                return $name;
            }
            $name = substr($name, 0, 5).'...'.substr($name, 8 + $toremove);

            return $name;
        }

        return $name;
    }

    /**
     * create init file.
     *
     * @param string $path
     * @param array $data
     * @return string
     * @throws Zend_Exception
     */
    public static function createInitFile($path, $data)
    {
        if (!is_writable(dirname($path))) {
            throw new Zend_Exception('Unable to write in: '.dirname($path));
        }
        if (file_exists($path)) {
            unlink($path);
        }

        if (!is_array($data) || empty($data)) {
            throw new Zend_Exception('Error in parameter: data, it should be a non-empty array');
        }
        $text = '';

        foreach ($data as $delimiter => $d) {
            $text .= '['.$delimiter."]\n";
            foreach ($d as $field => $value) {
                if ($value == 'true' || $value == 'false') {
                    $text .= $field.'='.$value."\n";
                } else {
                    $text .= $field.'="'.str_replace('"', "'", $value)."\"\n";
                }
            }
            $text .= "\n\n";
        }
        $fp = fopen($path, 'w');
        fwrite($fp, $text);
        fclose($fp);

        return $text;
    }

    /**
     * PHP md5_file is very slow on large file. If md5 sum is on the system we use it.
     *
     * @param string $filename
     * @return string
     */
    public static function md5file($filename)
    {
        // If we have md5 sum
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $md5sumCommand = $settingModel->getValueByName('md5sum_command');

        if (!empty($md5sumCommand)) {
            $result = exec($md5sumCommand.' '.$filename);
            $resultarray = explode(' ', $result);

            return $resultarray[0];
        }

        return md5_file($filename);
    }

    /**
     * Check if the php function/extension are available.
     *
     * @param array $phpextensions should have the following format:
     *   array(
     *     "ExtensionOrFunctionName" => array( EXT_CRITICAL , $message or EXT_DEFAULT_MSG ),
     *   );
     *
     * The unavailable function/extension are returned (array of string)
     * @return array
     * @throws Zend_Exception
     */
    public static function checkPhpExtensions($phpextensions)
    {
        $phpextension_missing = array();
        foreach ($phpextensions as $name => $param) {
            $is_loaded = extension_loaded($name);
            $is_func_exists = function_exists($name);
            if (!$is_loaded && !$is_func_exists) {
                $is_critical = $param[0];
                $message = '<b>'.$name."</b>: Unable to find '".$name."' php extension/function. ";
                $message .= ($param[1] === false ? 'Fix the problem and re-run the install script.' : $param[1]);
                if ($is_critical) {
                    throw  new Zend_Exception($message);
                }
                $phpextension_missing[$name] = $message;
            }
        }

        return $phpextension_missing;
    }

    /**
     * Get size in bytes of the file. This also supports files over 2GB in Windows,
     * which is not supported by PHP's filesize().
     *
     * @param string $path path of the file to check
     * @return int
     */
    public static function fileSize($path)
    {
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            $filesystem = new COM('Scripting.FileSystemObject');
            $file = $filesystem->GetFile($path);

            return $file->Size();
        } else {
            return filesize($path);
        }
    }

    /**
     * Format file size. Rounds to 1 decimal place and makes sure
     * to use 3 or less digits before the decimal place.
     *
     * @param int $sizeInBytes
     * @param string $separator
     * @return string
     * @throws Zend_Exception
     */
    public static function formatSize($sizeInBytes, $separator = ',')
    {
        $suffix = 'B';
        if (Zend_Registry::get('configGlobal')->application->lang == 'fr') {
            $suffix = 'o';
        }
        if ($sizeInBytes >= 1073741824000) {
            $sizeInBytes = number_format($sizeInBytes / 1099511627776, 1, '.', $separator);

            return $sizeInBytes.' T'.$suffix;
        } elseif ($sizeInBytes >= 1048576000) {
            $sizeInBytes = number_format($sizeInBytes / 1073741824, 1, '.', $separator);

            return $sizeInBytes.' G'.$suffix;
        } elseif ($sizeInBytes >= 1024000) {
            $sizeInBytes = number_format($sizeInBytes / 1048576, 1, '.', $separator);

            return $sizeInBytes.' M'.$suffix;
        } else {
            $sizeInBytes = number_format($sizeInBytes / 1024, 1, '.', $separator);

            return $sizeInBytes.' K'.$suffix;
        }
    }

    /**
     * Safe delete function. Checks if the file can be deleted.
     *
     * @param string $filename
     * @return false|void
     */
    public static function safedelete($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }
        unlink($filename);
    }

    /**
     * Function to run a SQL script.
     *
     * @param Zend_Db_Adapter_Abstract $db
     * @param string $sqlfile
     * @return true
     * @throws Zend_Exception
     */
    public static function run_sql_from_file($db, $sqlfile)
    {
        $db->getConnection();
        $sql = '';
        $lines = file($sqlfile);
        foreach ($lines as $line) {
            if (trim($line) != '' && substr(trim($line), 0, 2) != '--' && substr($line, 0, 1) != '#'
            ) {
                $sql .= $line;
            }
        }
        $queries = explode(';', $sql);
        foreach ($queries as $query) {
            try {
                $db->query($query);
            } catch (Zend_Exception $exception) {
                if (trim($query) != '') {
                    throw new Zend_Exception('Unable to connect.');
                }
            }
        }

        return true;
    }

    /**
     * Get the data directory.
     *
     * @param string $subDirectory
     * @param bool $createDirectory
     * @return string
     * @throws Zend_Exception
     */
    public static function getDataDirectory($subDirectory = '', $createDirectory = true)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');

        try {
            $dataDirectory = $settingModel->getValueByName('data_directory');
        } catch (Exception $e) {
            $dataDirectory = null;
        }

        if (!isset($dataDirectory) || empty($dataDirectory)) {
            if (getenv('midas_data_path') !== false) {
                $dataDirectory = getenv('midas_data_path');
            } else {
                $dataDirectory = BASE_PATH.'/data';
            }
        }

        if ($subDirectory == '') {
            $path = $dataDirectory.'/';
        } else {
            $path = $dataDirectory.'/'.$subDirectory.'/';
        }

        if ($createDirectory && is_writable($dataDirectory) && !file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return self::realpath($path);
    }

    /**
     * get the midas temporary directory, appending the param $subdir, which
     * defaults to "misc".
     *
     * @param string $subDirectory
     * @param bool $createDirectory
     * @return string
     * @throws Zend_Exception
     */
    public static function getTempDirectory($subDirectory = 'misc', $createDirectory = true)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');

        try {
            $tempDirectory = $settingModel->getValueByName('temp_directory');
        } catch (Exception $e) {
            // if the setting model hasn't been installed, or there is no
            // value in the settings table for this, provide a default
            $tempDirectory = null;
        }

        if (!isset($tempDirectory) || empty($tempDirectory)) {
            if (getenv('midas_temp_path') !== false) {
                $tempDirectory = getenv('midas_temp_path');
            } else {
                $tempDirectory = BASE_PATH.'/tmp';
            }
        }

        $path = $tempDirectory.'/'.$subDirectory;

        if ($createDirectory && is_writable($tempDirectory) && !file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return self::realpath($path);
    }

    /**
     * get the midas cache directory.
     *
     * @return string
     */
    public static function getCacheDirectory()
    {
        return self::getTempDirectory('cache');
    }

    /**
     * install a module.
     *
     * @param string $moduleName
     * @throws Zend_Exception
     */
    public function installModule($moduleName)
    {
        // TODO, The module installation process needs some improvement.
        $allModules = $this->getAllModules();
        $version = $allModules[$moduleName]->version;

        $installScript = BASE_PATH.'/modules/'.$moduleName.'/database/InstallScript.php';
        $installScriptExists = file_exists($installScript);
        if ($installScriptExists) {
            require_once BASE_PATH.'/core/models/MIDASModuleInstallScript.php';
            require_once $installScript;

            $classname = ucfirst($moduleName).'_InstallScript';
            if (!class_exists($classname, false)) {
                throw new Zend_Exception('Could not find class "'.$classname.'" in file "'.$installScript.'"');
            }

            $class = new $classname();
            $class->preInstall();
        }

        try {
            $configDatabase = Zend_Registry::get('configDatabase');
            $db = Zend_Registry::get('dbAdapter');

            switch ($configDatabase->database->adapter) {
                case 'PDO_MYSQL':
                    if (file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$version.'.sql')) {
                        $this->run_sql_from_file(
                            $db,
                            BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$version.'.sql'
                        );
                    }
                    break;
                case 'PDO_PGSQL':
                    if (file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/pgsql/'.$version.'.sql')) {
                        $this->run_sql_from_file(
                            $db,
                            BASE_PATH.'/modules/'.$moduleName.'/database/pgsql/'.$version.'.sql'
                        );
                    }
                    break;
                case 'PDO_SQLITE':
                    if (file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/sqlite/'.$version.'.sql')) {
                        $this->run_sql_from_file(
                            $db,
                            BASE_PATH.'/modules/'.$moduleName.'/database/sqlite/'.$version.'.sql'
                        );
                    }
                    break;
                default:
                    break;
            }
        } catch (Zend_Exception $exc) {
            $this->getLogger()->warn($exc->getMessage());
        }

        if ($installScriptExists) {
            $class->postInstall();
        }

        require_once dirname(__FILE__).'/UpgradeComponent.php';
        $upgrade = new UpgradeComponent();
        $db = Zend_Registry::get('dbAdapter');
        $dbtype = Zend_Registry::get('configDatabase')->database->adapter;
        $upgrade->initUpgrade($moduleName, $db, $dbtype);
        $upgrade->upgrade($version);
    }

    /**
     * Will remove all "unsafe" html tags from the text provided.
     *
     * @deprecated since 3.3.0
     * @param string $text text to filter
     * @return string text stripped of all unsafe tags
     */
    public static function filterHtmlTags($text)
    {
        $allowedTags = array(
            'a',
            'b',
            'br',
            'i',
            'p',
            'strong',
            'table',
            'thead',
            'tbody',
            'th',
            'tr',
            'td',
            'ul',
            'ol',
            'li',
            'style',
            'div',
            'span',
        );
        $allowedAttributes = array('href', 'class', 'style', 'type', 'target');
        $stripTags = new Zend_Filter_StripTags($allowedTags, $allowedAttributes);

        return $stripTags->filter($text);
    }

    /**
     * Convert the given text from Markdown or Markdown Extra to HTML.
     *
     * @param string $text Markdown or Markdown Extra text
     * @return string HTML text
     */
    public static function markdown($text)
    {
        $extra = new \ParsedownExtra();

        return $extra->text($text);
    }

    /**
     * INTERNAL FUNCTION
     * This is used to suppress warnings from being written to the output and the
     * error log. Users should not call this function; see beginIgnoreWarnings().
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return true
     */
    public static function ignoreErrorHandler($errno, $errstr, $errfile, $errline)
    {
        return true;
    }

    /**
     * Normally, PHP warnings are echoed by our default error handler.  If you expect them to happen
     * from, for instance, an underlying library, but want to eat them instead of echoing them, wrap
     * the offending lines in beginIgnoreWarnings() and endIgnoreWarnings().
     */
    public static function beginIgnoreWarnings()
    {
        set_error_handler('UtilityComponent::ignoreErrorHandler'); // must not print and log warnings
    }

    /**
     * See documentation of UtilityComponent::beginIgnoreWarnings().
     * Calling this restores the normal warning handler.
     */
    public static function endIgnoreWarnings()
    {
        restore_error_handler();
    }

    /**
     * Recursively delete a directory on disk.
     *
     * @param string $dir
     */
    public static function rrmdir($dir)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir.'/'.$object) == 'dir') {
                    self::rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }

    /**
     * Send an email.
     *
     * @deprecated since version 3.2.17
     * @param array|string $to "To" email address or addresses
     * @param string $subject subject
     * @param string $body body
     * @returns bool true on success
     * @throws Zend_Exception
     */
    public static function sendEmail($to, $subject, $body)
    {
        return Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_SEND_MAIL_MESSAGE',
            array(
                'to' => $to,
                'subject' => $subject,
                'html' => $body,
                'event' => 'legacy_send_email',
            )
        );
    }

    /**
     * Get the host name of this instance.
     *
     * @return string
     * @throws Zend_Exception
     */
    public static function getServerURL()
    {
        if (Zend_Registry::get('configGlobal')->environment == 'testing') {
            return 'http://localhost';
        }
        $currentPort = '';
        $prefix = 'http://';

        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
            $currentPort = ':'.$_SERVER['SERVER_PORT'];
        }
        if ((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']))) {
            $prefix = 'https://';
        }

        return $prefix.$_SERVER['SERVER_NAME'].$currentPort;
    }

    /**
     * Generate a medium-strength random string of the given length.
     *
     * @deprecated since 3.3.0
     * @param int $length length of the generated string
     * @param string $characters characters to use to generate the string
     * @return string
     */
    public static function generateRandomString($length, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');

        return $randomComponent->generateString($length, $characters);
    }

    /**
     * Allows the current PHP process to use unlimited memory.
     */
    public static function disableMemoryLimit()
    {
        ini_set('memory_limit', '-1');
    }

    /**
     * Test whether the specified port is listening on the specified host.
     * Return true if the connection is accepted, false otherwise.
     *
     * @param int $port port to test
     * @param string $host host name, default is localhost
     * @return bool
     */
    public static function isPortListening($port, $host = 'localhost')
    {
        self::beginIgnoreWarnings();
        $conn = fsockopen($host, $port);
        self::endIgnoreWarnings();

        if (is_resource($conn)) {
            fclose($conn);

            return true;
        }

        return false;
    }

    /**
     * Limits the maximum execution time.
     *
     * @param int $seconds
     */
    public static function setTimeLimit($seconds)
    {
        self::beginIgnoreWarnings();
        set_time_limit($seconds);
        self::endIgnoreWarnings();
    }

    /**
     * Returns available space on filesystem or disk partition.
     *
     * @param string $directory
     * @return float
     */
    public static function diskFreeSpace($directory)
    {
        self::beginIgnoreWarnings();
        $result = disk_free_space($directory);
        self::endIgnoreWarnings();

        return $result;
    }

    /**
     * Returns the total size of a filesystem or disk partition.
     *
     * @param string $directory
     * @return float
     */
    public static function diskTotalSpace($directory)
    {
        self::beginIgnoreWarnings();
        $result = disk_total_space($directory);
        self::endIgnoreWarnings();

        return $result;
    }

    /**
     * Returns canonical absolute path.
     *
     * @param string $path
     * @return string
     */
    public static function realpath($path)
    {
        self::beginIgnoreWarnings();
        $realpath = realpath($path);
        self::endIgnoreWarnings();

        if ($realpath === false) {
            return $path;
        }

        return $realpath;
    }
}
