<?php

require_once 'Zend/File/Transfer/Adapter/Http.php';

/**
 * We override the Zend http transfer adapter class to fix the
 * _detectMimeType method from the abstract adapter.  See issue 324.
 */
class Zend_File_Transfer_Adapter_HttpFixed extends Zend_File_Transfer_Adapter_Http
{
  protected function _detectMimeType($value)
    {
        if (file_exists($value['tmp_name'])) {
            $file = $value['tmp_name'];
        } else {
            return null;
        }

        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            if (!empty($value['options']['magicFile'])) {
                $mime = @finfo_open($const, $value['options']['magicFile']);
            }

            if (empty($mime)) {
                $mime = @finfo_open($const);
            }

            if (!empty($mime)) {
                $result = finfo_file($mime, $file);
            }

            unset($mime);
        }

        if (empty($result) && (function_exists('mime_content_type')
            && ini_get('mime_magic.magicfile'))) {
            $result = mime_content_type($file);
        }

        if (empty($result)) {
            $result = 'application/octet-stream';
        }

        return $result;
    }
}
