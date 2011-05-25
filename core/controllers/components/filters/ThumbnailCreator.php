<?php
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date: 2010-11-15 17:14:43 +0100 (lun., 15 nov. 2010) $
Version:   $Revision: 3162 $

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php
/** Thumbnail creator */
class ThumbnailCreator extends AppFilters
{
  var $description = 'Create thumbnail from files';
  var $_binaryName = 'ThumbnailCreator';
  var $settings = array('width' => 100,       // width in pixels
                        'height' => 100,      // height in pixels
                        'resource' => false); //
  var $_version = '1.2';

  /** Constructor */
  function __construct()
    {
    exec('convert', $output, $returnvalue);
    $this->convert = 'convert';
    if(count($output) == 0)
      {
      $this->convert = 'im-convert';
      exec('im-convert', $output, $returnvalue);
      }
    if(count($output) == 0)
      {
      throw new Zend_Exception("Unable to detect image magick" );
      }
    $this->_exe = "";
    }

  /** */
  function setResource($bool = false)
    {
    $settings['resource'] =  $bool;
    }

  /** Execute filter */
  function process()
    {
    //parent::preProcess();
    // no input file ... no processing
    if(!file_exists($this->inputFile))
      {
      return false;
      }
    // escaping input file is done later

    // create new output file
    if(!isset($this->outputFile) || $this->outputFile == '')
      {
      $tmpPath = BASE_PATH.'/data/thumbnail/'.rand(1, 1000);
      if(!file_exists(BASE_PATH.'/data/thumbnail/'))
        {
        throw new Zend_Exception("Problem thumbnail path: ".BASE_PATH.'/data/thumbnail/');
        }
      if(!file_exists($tmpPath))
        {
        mkdir($tmpPath);
        }
      $tmpPath .= '/'.rand(1, 1000);
      if(!file_exists($tmpPath))
        {
        mkdir($tmpPath);
        }
      $destionation = $tmpPath."/".rand(1, 1000).'.jpeg';
      while(file_exists($destionation))
        {
        $destionation = $tmpPath."/".rand(1, 1000).'.jpeg';
        }
      $this->outputFile = $destionation;
      }

    // file extension (priority to the input name)
    if($this->inputName != '')
      {
      $path_info = pathinfo($this->inputName);
      }
    else
      {
      $path_info = pathinfo($this->inputFile);
      }
    if(!isset($path_info['extension']))
      {
      $path_info['extension'] = "";  
      }
    $extension = strtolower($path_info['extension']);
    
    $ret = false;
    switch($extension)
      {
      case "dcm":
      case "mha":
      case "nrrd":
      case "":
        //$ret = $this->_processMetaImage($extension);
        break;
      case "pdf":
        $this->inputFile .= "[0]";    // first page only
        $ret = $this->_processStandard();
        break;
      case "mpg":
      case "mpeg":
      case "mp4":
      case "avi":
      case "mov":
      case "flv":
      case "mp4":
      case "rm":
        //$ret = $this->_processVideo();
        break;
      default:
        $ret = $this->_processStandard();
      }
    //parent::postProcess();
    return $ret;
    }

  /** For DICOM, MHA */
  private function _processMetaImage($extension)
    {
    // local variables
    $tempInputFilename = $this->tempFile($extension);
    $tempOutputFilename = $this->tempFile('jpg');  // force output to JPEG

    // If we are on windows we should move the dataset (no symlink)
    if($this->_OS == "windows" && !$this->settings['resource'])
      {
      rename($this->inputFile, $tempInputFilename); // This is scary
      }
    elseif(!$this->settings['resource']) // linux & mac
      {
      symlink($this->inputFile, $tempInputFilename);
      }

    // create intermediate image
    $opt = '';
    if(!$this->settings['resource'])
      {
      $opt = "--noseries";
      }
    $cmd = $this->_binaryPath." ".$opt." \"".$tempInputFilename."\" \"".$tempOutputFilename."\" ".$this->_stdErrRedirect;
    exec($cmd, $this->outputString, $retval = null);

    // rename back (win) / delete simlink (unix) - the original dataset
    if(!$this->settings['resource'])
      {
      if($this->_OS == "windows")
        {
        rename($tempInputFilename, $this->inputFile); // This is scary
        }
      else
        {
        unlink($tempInputFilename); // unlink the symlink
        }
      }
    if($retval != 0)
      {
      Kwutils::error("Failed to run command [".$cmd."] - return [".$retval."] - outputs[" .
        implode('', $this->outputString)."]");
      return false;
      }

    // create thumbnail from intermediate image
    $this->inputFile = $tempOutputFilename;
    $ret = $this->_processStandard();

    // delete temporary filename
    $this->deleteFile($tempOutputFilename);

    return $ret;
    }

  /** For videos */
  private function _processVideo()
    {
    // retrieve parameters
    $h = $this->settings['height'];
    $w = $this->settings['width'];

    // Get video length in second
    $cmd = "ffmpeg".$this->_exe." -i \"".$this->inputFile."\" ".$this->_stdErrRedirect;
    exec($cmd, $output, $retval = null);
    $output = implode("", $output);
    preg_match('/Duration: ([0-9]{2}):([0-9]{2}):([^ ,])+/', $output, $matches);
    if(empty($matches))
      {
      Kwutils::error("Unable to find video duration when executing [".$cmd."] output:".$output);
      return false;
      }
    $time = str_replace("Duration: ", "", $matches[0]);
    $time_breakdown = explode(":", $time);
    $total_seconds = round(($time_breakdown[0] * 60 * 60) + ($time_breakdown[1] * 60) + $time_breakdown[2]);
    $middleTime = ($total_seconds / 2);

    // execute external program
    $cmd = "ffmpeg".$this->_exe." -i ".$this->inputFile." -vframes 1 -s \"".$w."x".$h."\" -ss ".$middleTime." ".$this->outputFile." ".$this->_stdErrRedirect;
    exec($cmd, $this->outputString, $retval = null);

    if($retval != 0)
      {
      //Kwutils::error("Trying to execute [$cmd]");
      return false;
      }
    return true;
    }

  /** For general case (jpg, png, bmp, ...) */
  private function _processStandard()
    {
    // retreive parameters
    $h = $this->settings['height'];
    $w = $this->settings['width'];

    // execute external program
    $cmd = $this->convert.$this->_exe." \"".$this->inputFile."\" -thumbnail \"".$w."x".$h. "\" -gravity center -background black -extent ".$w."x".$h." \"".$this->outputFile."\"";
    exec($cmd, $this->outputString, $retval);
    if($retval != 0)
      {
      //Kwutils::error("Trying to execute [$cmd]");
      return false;
      }
    return true;
    }
}
