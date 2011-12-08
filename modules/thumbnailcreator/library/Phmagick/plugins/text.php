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
 * phMagick - Text drawing functions
 *
 * @package    phMagick
 * @version    0.1.0
 * @author     Nuno Costa - sven@francodacosta.com
 * @copyright  Copyright (c) 2007
 * @license    http://www.francodacosta.com/phmagick/license/
 * @link       http://www.francodacosta.com/phmagick
 * @since      2008-03-13
 */
class phMagick_text{
/**
     * Draws an image with the submited string, usefull for water marks
     *
     * @param $text String - the text to draw an image from
     * @param $format phMagickTextObject - the text configuration
     */
    function fromString(phmagick $p, $text = '', phMagickTextObject $format = null){

        if(is_null($format)) $format = new phMagickTextObject();

        $cmd  = $p->getBinary('convert');

        if ($format->background !== false)
            $cmd .= ' -background "' . $format->background . '"';

        if ($format->color !== false)
            $cmd .= ' -fill "' . $format->color . '"' ;

        if ($format->font !== false)
            $cmd .= ' -font ' . $format->font ;

        if ($format->fontSize !== false)
            $cmd .= ' -pointsize ' . $format->fontSize ;

        if (($format->pText != '') && ($text = '') )
            $text = $format->pText ;

        $cmd .= ' label:"'. $text .'"';
        $cmd .= ' "' . $p->getDestination().'"' ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }
}


?>