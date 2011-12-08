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
 * phMagick - Color manipulation function
 *
 * @package    phMagick
 * @version    0.1.0
 * @author     Nuno Costa - sven@francodacosta.com
 * @copyright  Copyright (c) 2007
 * @license    http://www.francodacosta.com/phmagick/license/
 * @link       http://www.francodacosta.com/phmagick
 * @since      2008-03-13
 */
class phMagick_color {
	   function darken(phmagick $p ,$alphaValue = 50){
        $percent = 100 - (int) $alphaValue;

        //get original file dimentions

        list ($width, $height) = $p->getInfo();

        $cmd = $p->getBinary('composite');
        $cmd .=  ' -blend  ' . $percent . ' ';
        $cmd .= '"'.$p->getSource().'"';
        $cmd .= ' -size '. $width .'x'. $height.' xc:black ';
        $cmd .= '-matte "' . $p->getDestination().'"' ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }

    /**
     *
     *  Brightens an image, defualt: 50%
     *
     * @param $imageFile String - Physical path of the umage file
     * @param $newFile String - Physical path of the generated image
     * @param $alphaValue Integer - 100: white , 0: original color (no change)
     * @return boolean - True: success
     */
    function brighten(phmagick $p, $alphaValue = 50){

        $percent = 100 - (int) $alphaValue;

        //get original file dimentions

        list ($width, $height) = $p->getInfo();

        $cmd = $p->getBinary('composite');
        $cmd .=  ' -blend  ' . $percent . ' ';
        $cmd .= '"'.$p->getSource().'"';
        $cmd .= ' -size '. $width .'x'. $height.' xc:white ';
        $cmd .= '-matte "' . $p->getDestination().'"' ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }
    
    /**
     * Convert's the image to grayscale
     */
//    function toGrayScale(phmagick $p){
//        $cmd  = $p->getBinary('convert');
//        $cmd .= ' "' . $p->getSource() .'"';
//        $cmd .= ' -colorspace Gray  ';
//        $cmd .= ' "' . $p->getDestination().'"' ;
//
//        $p->execute($cmd);
//        $p->setSource($p->getDestination());
//        $p->setHistory($p->getDestination());
//        return  $p ;
//    }

	function toGreyScale(phmagick $p, $enhance=2){
		$cmd   = $p->getBinary('convert');
		$cmd .= ' -modulate 100,0 ' ;
		$cmd .= ' -sigmoidal-contrast '.$enhance.'x50%' ;
		$cmd .= ' -background "none" "' . $p->getSource().'"' ;
		$cmd .= ' "' . $p->getDestination() .'"';
		
		$p->execute($cmd);
		$p->setSource($p->getDestination());
		$p->setHistory($p->getDestination());
		return  $p ;
	}

    /**
     * Inverts the image colors
     */
    function invertColors(phmagick $p){
        $cmd  = $p->getBinary('convert');
        $cmd .= ' "' . $p->getSource() .'"';
        $cmd .= ' -negate ';
        $cmd .= ' "' . $p->getDestination() .'"';

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }
    
    function sepia(phmagick $p, $tone = 90){
		$cmd   = $p->getBinary('convert');
		$cmd .= ' -sepia-tone '. $tone .'% ' ;
		$cmd .= ' -modulate 100,50 ' ;
		$cmd .= ' -normalize ' ;
		$cmd .= ' -background "none" "' . $p->getSource() .'"' ;
		$cmd .= ' "' . $p->getDestination().'"' ;
	     $p->execute($cmd);
	     $p->setSource($p->getDestination());
	     $p->setHistory($p->getDestination());
	    return  $p ;
    }
    
	function autoLevels(phmagick $p){
		$cmd  = $p->getBinary('convert');
		$cmd .= ' -normalize ' ;
		$cmd .= ' -background "none" "' . $p->getSource().'"'  ;
		$cmd .= ' "' . $p->getDestination() .'"' ;
		
		$p->execute($cmd);
		$p->setSource($p->getDestination());
		$p->setHistory($p->getDestination());
		return  $p ;
	}
	
}
?>