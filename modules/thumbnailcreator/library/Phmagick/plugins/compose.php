<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 20 rue de la Villette. 69328 Lyon, FRANCE
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
/*
    +--------------------------------------------------------------------------------------------+
    |   DISCLAIMER - LEGAL NOTICE -                                                              |
    +--------------------------------------------------------------------------------------------+
    |                                                                                            |
    |  This program is free for non comercial use, see the license terms available at            |
    |  http://www.francodacosta.com/licencing/ for more information                              |
    |                                                                                            |
    |  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; |
    |  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. |
    |                                                                                            |
    |  USE IT AT YOUR OWN RISK                                                                   |
    |                                                                                            |
    |                                                                                            |
    +--------------------------------------------------------------------------------------------+

*/
/**
 * phMagick - creating new images by joining several images or by grabbing frames
 *
 * @package    phMagick
 * @version    0.1.0
 * @author     Nuno Costa - sven@francodacosta.com
 * @copyright  Copyright (c) 2007
 * @license    http://www.francodacosta.com/phmagick/license/
 * @link       http://www.francodacosta.com/phmagick
 * @since      2008-03-13
 */
class phMagick_compose{
/**
     * Add's an watermark to an image
     *
     * @param $watermarkImage String - Image path
     * @param $gravity phMagickGravity - The placement of the watermark
     * @param $transparency Integer - 1 to 100 the tranparency of the watermark (100 = opaque)
     */
    function watermark(phmagick $p, $watermarkImage, $gravity = 'center', $transparency = 50){
        //composite -gravity SouthEast watermark.png original-image.png output-image.png
        $cmd   = $p->getBinary('composite');
        $cmd .= ' -dissolve ' . $transparency ;
        $cmd .= ' -gravity ' . $gravity ;
        $cmd .= ' ' . $watermarkImage ;
        $cmd .= ' "' . $p->getSource() .'"' ;
        $cmd .= ' "' . $p->getDestination() .'"' ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }

    /**
     *
     * Joins severall imagens in one tab strip
     *
     * @param $paths Array of Strings - the paths of the images to join
     */
    function tile(phmagick $p,  Array $paths = null, $tileWidth = '', $tileHeight=1){
        if( is_null($paths) ) {
            $paths = $p->getHistory(phMagickHistory::returnArray);
        }
        $cmd  = $p->getBinary('montage');
        $cmd .= ' -geometry x+0+0 -tile '.$tileWidth.'x'.$tileHeight.' ';
        $cmd .= implode(' ', $paths);
        $cmd .= ' "' . $p->getDestination() .'"' ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }

    /**
     * Attempts to create an image(s) from a File (PDF & Avi are supported on most systems)
     * it grabs the first frame / page from the source file
     * @param $file  String - the path to the file
     * @param $ext   String - the extention of the generated image
     */
    function acquireFrame(phmagick $p, $file, $frames=0){
       // $cmd = 'echo "" | '; //just a workarround for videos,
        //                    imagemagick first converts all frames then deletes all but the first
        $cmd = $p->getBinary('convert');
        $cmd .= ' "' . $file .'"['.$frames.']' ;
        $cmd .= ' "' . $p->getDestination().'"'  ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }
}
?>