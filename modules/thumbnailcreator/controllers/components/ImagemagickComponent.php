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

/** Component used to create thumbnails using phMagick library (on top of ImageMagick) */
class Thumbnailcreator_ImagemagickComponent extends AppComponent
{
    public $moduleName = 'thumbnailcreator';

    /**
     * Create a 100x100 thumbnail from an item.
     * Echoes an error message if a problem occurs (for the scheduler log).
     *
     * @param array|ItemDao $item item to create the thumbnail for
     * @param null|string $inputFile file to thumbnail. If none is specified, uses the first bitstream in the head revision of the item
     */
    public function createThumbnail($item, $inputFile = null)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        if (is_array($item)) {
            $item = $itemModel->load($item['item_id']);
        }

        $revision = $itemModel->getLastRevision($item);
        if ($revision === false) {
            return;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
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
     * Create a thumbnail for the given file with the given width and height.
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
        $useThumbnailer = (int) $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_USE_THUMBNAILER_KEY, $this->moduleName);
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

        /** @var MimeTypeComponent $mimeTypeComponent */
        $mimeTypeComponent = MidasLoader::loadComponent('MimeType');
        $mimeType = $mimeTypeComponent->getType($fullPath, $name);

        if ($provider === 'phmagick') {
            $imageMagickPath = $settingModel->getValueByName(MIDAS_THUMBNAILCREATOR_IMAGE_MAGICK_KEY, $this->moduleName);

            switch ($mimeType) {
                case 'application/pdf':
                case 'application/vnd.rn-realmedia':
                case 'video/mp4':
                case 'video/mpeg':
                case 'video/quicktime':
                case 'video/x-flv':
                case 'video/x-msvideo':
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
            if ($mimeType === 'image/gif'
                || $mimeType === 'image/jpeg'
                || $mimeType === 'image/png'
                || ($provider === MIDAS_THUMBNAILCREATOR_PROVIDER_IMAGICK
                    && ($mimeType === 'image/bmp'
                        || $mimeType === 'image/tiff'
                        || $mimeType === 'image/vnd.adobe.photoshop'
                        || $mimeType === 'image/x-icon'))) {
                try {
                    $manager = new \Intervention\Image\ImageManager(array('driver' => $provider));
                    $image = $manager->make($fullPath);

                    if ($height === 0) {
                        $image->widen($width);
                    } elseif ($width === 0) {
                        $image->heighten($height);
                    } else {
                        $image->resize($width, $height);
                    }

                    $image->save($pathThumbnail);
                } catch (\RuntimeException $exception) {
                    Zend_Registry::get('logger')->err($exception->getMessage());
                    throw new Zend_Exception('Thumbnail creation failed');
                }
            } else {
                throw new Zend_Exception('Unsupported image format');
            }
        } else {
            throw new Zend_Exception('No thumbnail provider has been selected');
        }

        return $pathThumbnail;
    }

    /**
     * Use thumbnailer to pre-process a bitstream to generate a jpeg file.
     * Echoes an error message if a problem occurs (for the scheduler log).
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
     * Return an array of the form [Is_Ok, Message].
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
