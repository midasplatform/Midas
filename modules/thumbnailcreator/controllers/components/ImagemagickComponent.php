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

include_once BASE_PATH.'/library/KWUtils.php';

if (extension_loaded('fileinfo') === false) {
    define('FILEINFO_NONE', 0);
    define('FILEINFO_SYMLINK', 2);
    define('FILEINFO_DEVICES', 8);
    define('FILEINFO_CONTINUE', 32);
    define('FILEINFO_PRESERVE_ATIME', 128);
    define('FILEINFO_RAW', 256);
    define('FILEINFO_MIME_TYPE', 16);
    define('FILEINFO_MIME_ENCODING', 1024);
    define('FILEINFO_MIME', 1040);

    /**
     * Return information about a given string.
     *
     * @param null|int|resource $finfo fileinfo resource
     * @param null|string $string content of the file to be checked
     * @param int $options fileinfo constant (FILEINFO_NONE | FILEINFO_MIME_TYPE | FILEINFO_MIME_ENCODING | FILEINFO_MIME)
     * @param null|resource $context context (not implemented)
     * @return false|string a textual description of the given string or false on failure
     */
    function finfo_buffer($finfo, $string = null, $options = FILEINFO_NONE, $context = null)
    {
        if ($finfo !== false && !is_null($string) && is_null($context)) {
            if ($options === FILEINFO_NONE && (($finfo & FILEINFO_MIME_TYPE) === FILEINFO_MIME_TYPE || ($finfo & FILEINFO_MIME_ENCODING) === FILEINFO_MIME_ENCODING)) {
                $options = $finfo;
            }

            if (($options & FILEINFO_MIME_TYPE) === FILEINFO_MIME_TYPE || ($options & FILEINFO_MIME_ENCODING) === FILEINFO_MIME_ENCODING) {
                $mimeEncoding = 'binary';

                if (substr($string, 0, 8) === "\x89PNG\x0d\x0a\x1a\x0a") {
                    $mimeType = 'image/png';
                } elseif (substr($string, 0, 6) === 'GIF87a' || substr($string, 0, 6) === 'GIF89a') {
                    $mimeType = 'image/gif';
                } elseif (substr($string, 0, 4) === "MM\x00\x2a" || substr($string, 0, 4) === "II\x2a\x00") {
                    $mimeType = 'image/tiff';
                } elseif (substr($string, 0, 4) === '8BPS') {
                    $mimeType = 'image/vnd.adobe.photoshop';
                } elseif (substr($string, 0, 3) === "\xFF\xD8\xFF") {
                    $mimeType = 'image/jpeg';
                } elseif (substr($string, 0, 2) === 'BM') {
                    $mimeType = 'image/bmp';
                } elseif (strpos($string, "\x00") !== false) {
                    $mimeType = 'application/octet-stream';
                } else {
                    $mimeEncoding = 'utf-8';
                    $mimeType = 'text/plain';
                }

                if (($options & FILEINFO_MIME) === FILEINFO_MIME) {
                    return $mimeType.'; charset='.$mimeEncoding;
                }

                if (($options & FILEINFO_MIME_TYPE) === FILEINFO_MIME_TYPE) {
                    return $mimeType;
                }

                return $mimeEncoding;
            }
        }

        return false;
    }

    /**
     * Close a given fileinfo resource.
     *
     * @param null|resource $finfo fileinfo resource
     * @return bool true on success or false on failure
     */
    function finfo_close($finfo)
    {
        return $finfo !== false;
    }

    /**
     * Return information about a given file.
     *
     * @param null|int|resource $finfo fileinfo resource
     * @param null|string $filename name of the file to be checked
     * @param int $options fileinfo constant (FILEINFO_NONE | FILEINFO_MIME_TYPE | FILEINFO_MIME_ENCODING | FILEINFO_MIME)
     * @param null|resource $context context (partially implemented)
     * @return false|string a textual description of the contents of the given file or false on failure
     */
    function finfo_file($finfo, $filename = null, $options = FILEINFO_NONE, $context = null)
    {
        if ($finfo !== false && !is_null($filename)) {
            if ($options === FILEINFO_NONE && (($finfo & FILEINFO_MIME_TYPE) === FILEINFO_MIME_TYPE || ($finfo & FILEINFO_MIME_ENCODING) === FILEINFO_MIME_ENCODING)) {
                $options = $finfo;
            }

            if (($options & FILEINFO_MIME_TYPE) === FILEINFO_MIME_TYPE || ($options & FILEINFO_MIME_ENCODING) === FILEINFO_MIME_ENCODING) {
                $mimeType = finfo_buffer($finfo, file_get_contents($filename, false, $context), $options, $context);

                if ($mimeType === false || $mimeType === 'application/octet-stream') {
                    $extension = strtolower(end(explode('.', basename($filename))));

                    switch ($extension) {
                        case 'bmp':
                            $mimeType = 'image/bmp';
                            break;
                        case 'gif':
                            $mimeType = 'image/gif';
                            break;
                        case 'ico':
                            $mimeType = 'image/x-icon';
                            break;
                        case 'jpe':
                        case 'jpeg':
                        case 'jpg':
                            $mimeType = 'image/jpeg';
                            break;
                        case 'png':
                            $mimeType = 'image/png';
                            break;
                        case 'psd':
                            $mimeType = 'image/vnd.adobe.photoshop';
                            break;
                        case 'tif':
                        case 'tiff':
                            $mimeType = 'image/tiff';
                            break;
                        case 'text':
                        case 'txt':
                            $mimeType = 'text/plain';
                            break;
                        default:
                            $mimeType = 'application/octet-stream';
                    }
                }

                $mimeEncoding = $mimeType === 'text/plain' ? 'utf-8' : 'binary';

                if (($options & FILEINFO_MIME) === FILEINFO_MIME) {
                    return $mimeType.'; charset='.$mimeEncoding;
                }

                if (($options & FILEINFO_MIME_TYPE) === FILEINFO_MIME_TYPE) {
                    return $mimeType;
                }

                return $mimeEncoding;
            }
        }

        return false;
    }

    /**
     * Create a new fileinfo resource.
     *
     * @param int $options fileinfo constant (FILEINFO_NONE | FILEINFO_MIME_TYPE | FILEINFO_MIME_ENCODING | FILEINFO_MIME)
     * @param null|string $magic name of a magic database file (not implemented)
     * @return null|int|resource a fileinfo resource on success or false on failure.
     */
    function finfo_open($options = FILEINFO_NONE, $magic = null)
    {
        return is_null($magic) ? $options : false;
    }

    /**
     * Set the magic configuration options (not implemented).
     *
     * @param null|int|resource $finfo fileinfo resource
     * @param int $options fileinfo constant (FILEINFO_NONE | FILEINFO_MIME_TYPE | FILEINFO_MIME_ENCODING | FILEINFO_MIME)
     * @return bool true on success or false on failure
     */
    function finfo_set_flags($finfo, $options)
    {
        return $finfo === $options;
    }
}

/** Component used to create thumbnails using phMagick library (on top of ImageMagick) */
class Thumbnailcreator_ImagemagickComponent extends AppComponent
{
    public $moduleName = 'thumbnailcreator';

    /**
     * Create a 100x100 thumbnail from an item.
     * Echoes an error message if a problem occurs (for the scheduler log)
     *
     * @param array|ItemDao $item item to create the thumbnail for
     * @param null|string $inputFile file to thumbnail. If none is specified, uses the first bitstream in the head revision of the item.
     */
    public function createThumbnail($item, $inputFile = null)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        if (is_array($item)) {
            $item = $itemModel->load($item['item_id']);
        }

        $revision = $itemModel->getLastRevision($item);
        $bitstreams = $revision->getBitstreams();

        if (count($bitstreams) < 1) {
            return;
        }

        $bitstream = $bitstreams[0];
        $fullPath = null;
        $name = $bitstream->getName();

        if ($inputFile) {
            $fullPath = $inputFile;
        } else {
            $fullPath = $bitstream->getFullPath();
        }

        try {
            $pathThumbnail = $this->createThumbnailFromPath($name, $fullPath, MIDAS_THUMBNAILCREATOR_SMALL_THUMBNAIL_SIZE, MIDAS_THUMBNAILCREATOR_SMALL_THUMBNAIL_SIZE, true);
        } catch (phMagickException $exc) {
            return;
        } catch (Exception $exc) {
            return;
        }

        if (file_exists($pathThumbnail)) {
            $itemModel->replaceThumbnail($item, $pathThumbnail);
        }
    }

    /**
     * Create a thumbnail for the given file with the given width and height
     *
     * @param string $name name of the image to create the thumbnail of
     * @param string $fullPath absolute path to the image to create the thumbnail of
     * @param int $width width to resize to (Set to 0 to preserve aspect ratio)
     * @param int $height height to resize to (Set to 0 to preserve aspect ratio)
     * @param bool $exact This will preserve aspect ratio by using a crop after the resize
     * @return string path where the thumbnail was created
     * @throws phMagickException
     * @throws Zend_Exception
     */
    public function createThumbnailFromPath($name, $fullPath, $width, $height, $exact = true)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $provider = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_PROVIDER_KEY, $this->moduleName);
        $useThumbnailer = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_USE_THUMBNAILER_KEY, $this->moduleName);
        $preprocessedFormats = array('mha', 'nrrd');
        $ext = strtolower(substr(strrchr($name, '.'), 1));

        if ($useThumbnailer === 1 && $provider === 'phmagick' && in_array($ext, $preprocessedFormats)) {
            // pre-process the file to get a temporary JPEG file and then feed it to ImageMagick later.
            $preprocessedJpeg = $this->preprocessByThumbnailer($name, $fullPath);
            if (isset($preprocessedJpeg) && file_exists($preprocessedJpeg)) {
                $fullPath = $preprocessedJpeg;
                $ext = strtolower(substr(strrchr($preprocessedJpeg, '.'), 1));
            }
        }

        // create destination
        $tmpPath = UtilityComponent::getTempDirectory('thumbnailcreator');
        $format = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_FORMAT_KEY, $this->moduleName);

        if ($format === 'jpeg') {
            $format = MIDAS_THUMBNAILCREATOR_FORMAT_JPG;
        }

        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $destination = $tmpPath.'/'.$randomComponent->generateInt().'.'.$format;

        while (file_exists($destination)) {
            $destination = $tmpPath.'/'.$randomComponent->generateInt().'.'.$format;
        }

        $pathThumbnail = $destination;

        if ($provider === 'phmagick') {
            $imageMagickPath = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_IMAGE_MAGICK_KEY, $this->moduleName);

            switch ($ext) {
                case 'pdf':
                case 'mpg':
                case 'mpeg':
                case 'mp4':
                case 'm4v':
                case 'avi':
                case 'mov':
                case 'flv':
                case 'rm':
                    // If this is a video, we have to have the file extension, so symlink it
                    if (function_exists('symlink') && symlink($fullPath, $fullPath.'.'.$ext)
                    ) {
                        $p = new phmagick('', $pathThumbnail);
                        $p->setImageMagickPath($imageMagickPath);
                        $p->acquireFrame($fullPath.'.'.$ext, 0);

                        if ($exact) {
                            // preserve aspect ratio by performing a crop after the resize
                            $p->resizeExactly($width, $height);
                        } else {
                            $p->resize($width, $height);
                        }

                        unlink($fullPath.'.'.$ext);
                    }

                    break;
                default:
                    // Otherwise it is just a normal image
                    $p = new phmagick($fullPath, $pathThumbnail);
                    $p->setImageMagickPath($imageMagickPath);

                    if ($exact) {
                        // preserve aspect ratio by performing a crop after the resize
                        $p->resizeExactly($width, $height);
                    } else {
                        $p->resize($width, $height);
                    }

                    break;
            }

            // delete temporary file generated in pre-process step
            if (isset($preprocessedJpeg) && file_exists($preprocessedJpeg)) {
                unlink($preprocessedJpeg);
            }
        } elseif ($provider === MIDAS_THUMBNAILCREATOR_PROVIDER_GD || $provider === MIDAS_THUMBNAILCREATOR_PROVIDER_IMAGICK) {
            try {
                $manager = new \Intervention\Image\ImageManager(array('driver' => $provider));
                $image = $manager->make($fullPath);

                if ($height === 0) {
                    $image->widen($width);
                } elseif ($width === 0) {
                    $image->heighten($height);
                } else  {
                    $image->resize($width, $height);
                }

                $image->save($pathThumbnail);
            } catch (\RuntimeException $exception) {
                Zend_Registry::get('logger')->err($exception->getMessage());
                throw new Zend_Exception('Thumbnail creation failed');
            }
        } else {
            throw new Zend_Exception('No thumbnail provider has been selected');
        }

        return $pathThumbnail;
    }

    /**
     * Use thumbnailer to pre-process a bitstream to generate a jpeg file.
     * Echoes an error message if a problem occurs (for the scheduler log)
     *
     * @param string $name name of the image to be pre-processed
     * @param string $fullPath absolute path to the image to be pre-processed
     * @return string
     * @throws Zend_Exception
     */
    public function preprocessByThumbnailer($name, $fullPath)
    {
        $tmpPath = UtilityComponent::getTempDirectory('thumbnailcreator');

        if (!file_exists($tmpPath)) {
            throw new Zend_Exception(
                'Temporary thumbnail dir does not exist: '.$tmpPath
            );
        }

        $copyDestination = $tmpPath.'/'.$name;
        copy($fullPath, $copyDestination);
        $jpegDestination = $tmpPath.'/'.$name.'.jpg';

        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');

        while (file_exists($jpegDestination)) {
            $jpegDestination = $tmpPath.'/'.$name.$randomComponent->generateInt().'.jpg';
        }

        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $thumbnailerPath = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_THUMBNAILER_KEY, $this->moduleName);
        $thumbnailerParams = array($copyDestination, $jpegDestination);
        $thumbnailerCmd = KWUtils::prepareExeccommand($thumbnailerPath, $thumbnailerParams);

        if (KWUtils::isExecutable($thumbnailerPath)) {
            KWUtils::exec($thumbnailerCmd);
        } else {
            unlink($copyDestination);
            throw new Zend_Exception(
                'Thumbnailer does not exist or you do not have execute permission. Please check the configuration of thumbnailcreator module.'
            );
        }

        if (!file_exists($jpegDestination)) {
            unlink($copyDestination);
            throw new Zend_Exception('Problem executing thumbnailer on your system');
        } else {
            unlink($copyDestination);

            return $jpegDestination;
        }
    }

    /**
     * Check if ImageMagick is available on the path specified
     * Return an array of the form [Is_Ok, Message]
     *
     * @return array
     */
    public function isImageMagickWorking()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $provider = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_PROVIDER_KEY, $this->moduleName);

        if ($provider === MIDAS_THUMBNAILCREATOR_PROVIDER_GD) {
            if (extension_loaded('gd')) {
                return array(true, 'GD PHP extension is loaded');
            }

            return array(false, 'GD PHP extension is not loaded');
        }

        if ($provider === MIDAS_THUMBNAILCREATOR_PROVIDER_IMAGICK) {
            if (extension_loaded('imagick')) {
                return array(true, 'ImageMagick PHP extension is loaded');
            }

            return array(false, 'ImageMagick PHP extension is not loaded');
        }

        if ($provider === MIDAS_THUMBNAILCREATOR_PROVIDER_PHMAGICK) {
            $imageMagickPath = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_IMAGE_MAGICK_KEY, $this->moduleName);

            if (empty($imageMagickPath)) {
                return array(false, 'No ImageMagick path has been set');
            }

            if (strpos(strtolower(PHP_OS), 'win') === 0) {
                $ext = '.exe';
            } else {
                $ext = '';
            }

            if (file_exists($imageMagickPath.'/convert'.$ext)) {
                $cmd = $imageMagickPath.'/convert'.$ext;
            } elseif (file_exists($imageMagickPath.'/im-convert'.$ext)) {
                $cmd = $imageMagickPath.'/im-convert'.$ext;
            } else {
                return array(false, 'Neither convert nor im-convert found at '.$imageMagickPath);
            }

            exec($cmd, $output, $returnValue);

            if (count($output) > 0) {
                // version line should look like: "Version: ImageMagick 6.4.7 2008-12-04 Q16 http://www.imagemagick.org"
                list($versionLine) = $output;

                // split version by spaces
                $versionChunks = explode(' ', $versionLine);

                // assume version is the third element
                $version = $versionChunks[2];

                // get major, minor and patch number
                list($major) = explode('.', $version);

                if ($major < 6) {
                    $text = '<b>ImageMagick</b> ('.$version.') is found. A version greater than 6.0 is required.';

                    return array(false, $text);
                }

                return array(true, $cmd.' (version '.$version.')');
            }

            return array(false, 'No output from '.$cmd);
        }

        return array(false, 'No provider has been selected');
    }
}
