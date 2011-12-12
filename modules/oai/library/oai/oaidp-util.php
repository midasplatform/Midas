<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
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
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date$
Version:   $Revision$

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php

function get_token()
{
  list($usec, $sec) = explode(" ", microtime());
  return ((int)($usec*1000) + (int)($sec*1000));
}

function oai_error($code, $argument = '', $value = '')
{
  global $request;
  global $request_err;

  switch ($code)
    {
    case 'badArgument' :
      $text = "The argument '$argument' (value='$value') included in the request is not valid.";
      break;

    case 'badGranularity' :
      $text = "The value '$value' of the argument '$argument' is not valid.";
      $code = 'badArgument';
      break;

    case 'badResumptionToken' :
      $text = "The resumptionToken '$value' does not exist or has already expired.";
      break;

    case 'badRequestMethod' :
      $text = "The request method '$argument' is unknown.";
      $code = 'badVerb';
      break;

    case 'badVerb' :
      $text = "The verb '$argument' provided in the request is illegal.";
      break;

    case 'cannotDisseminateFormat' :
      $text = "The metadata format '$value' given by $argument is not supported by this repository.";
      break;

    case 'exclusiveArgument' :
      $text = 'The usage of resumptionToken as an argument allows no other arguments.';
      $code = 'badArgument';
      break;

    case 'idDoesNotExist' :
      $text = "The value '$value' of the identifier is illegal for this repository.";
      if (!is_valid_uri($value))
        {
  $code = 'badArgument';
  }
      break;

    case 'missingArgument' :
      $text = "The required argument '$argument' is missing in the request.";
      $code = 'badArgument';
      break;

    case 'noRecordsMatch' :
      $text = 'The combination of the given values results in an empty list.';
      break;

    case 'noMetadataFormats' :
      $text = 'There are no metadata formats available for the specified item.';
      break;

    case 'noVerb' :
      $text = 'The request does not provide any verb.';
      $code = 'badVerb';
      break;

    case 'noSetHierarchy' :
      $text = 'This repository does not support sets.';
      break;

    case 'sameArgument' :
      $text = 'Do not use them same argument more than once.';
      $code = 'badArgument';
      break;

    case 'sameVerb' :
      $text = 'Do not use verb more than once.';
      $code = 'badVerb';
      break;

    default:
      $text = "Unknown error: code: '$code', argument: '$argument', value: '$value'";
      $code = 'badArgument';
    }

  if ($code == 'badVerb' || $code == 'badArgument')
    {
    $request = $request_err;
    }
  $error = ' <error code="'.xmlstr($code, 'iso8859-1', false).'">'.xmlstr($text, 'iso8859-1', false)."</error>\n";
  return $error;
}

function xmlstr($string, $charset = 'iso8859-1', $xmlescaped = 'false')
{
  $xmlstr = stripslashes(trim($string));
  // just remove invalid characters
  $pattern ="/[\x-\x8\xb-\xc\xe-\x1f]/";
  $xmlstr = preg_replace($pattern, '', $xmlstr);

  // escape only if string is not escaped
  if (!$xmlescaped)
    {
    $xmlstr = htmlspecialchars($xmlstr, ENT_QUOTES);
    }

  if ($charset != "utf-8")
    {
    $xmlstr = utf8_encode($xmlstr);
    }

  return $xmlstr;
}

// will split a string into elements and return XML
// supposed to print values from database
function xmlrecord($sqlrecord, $element, $attr = '', $indent = 0)
{
  global $SQL;
  global $xmlescaped;
  global $charset;

  $str = '';

  if ($attr != '')
    {
    $attr = ' '.$attr;
    }
  if ($sqlrecord != '')
    {
    if (isset($SQL['split']))
      {
      $temparr = explode($SQL['split'], $sqlrecord);
      foreach ($temparr as $val)
        {
  $str .= str_pad('', $indent).'<'.$element.$attr.'>'.xmlstr($val, $charset, $xmlescaped).'</'.$element.">\n";
  }
      return $str;
      }
    else
      {
      return str_pad('', $indent).'<'.$element.$attr.'>'.xmlstr($sqlrecord, $charset, $xmlescaped).'</'.$element.">\n";
      }
    }
  else
    {
    return '';
    }
}

function xmlelement($element, $attr = '', &$indent, $open = true)
{
  global $SQL;

  if ($attr != '')
    {
    $attr = ' '.$attr;
    }
  if ($open)
    {
    $indent += 2;
    return str_pad('', $indent).'<'.$element.$attr.'>'."\n";
    }
  else
    {
    $indent -= 2;
    return str_pad('', $indent).'</'.$element.'>'."\n";
    }
}

// takes either an array or a string and outputs them as XML entities
function xmlformat($record, $element, $attr = '', $indent = 0)
{
  global $charset;
  global $xmlescaped;
    
  if ($attr != '')
    {
    $attr = ' '.$attr;
    }
  
  $str = '';
  if (is_array($record))
    {
    foreach  ($record as $val)
      {
      $str .= str_pad('', $indent).'<'.$element.$attr.'>'.xmlstr($val, $charset, $xmlescaped).'</'.$element.">\n";
      }
    return $str;
    }
  elseif ($record != '')
    {
    return str_pad('', $indent).'<'.$element.$attr.'>'.xmlstr($record, $charset, $xmlescaped).'</'.$element.">\n";
    }
  else
  {
  return '';
  }
}

function xmlgetinfo($dc_type_id, $value)
{
  global $output;

  $indent = 6;

  if($dc_type_id > 0 &&  $dc_type_id < 7)
    {
    $output .= xmlrecord($value, 'dc:creator', '', $indent);
    } 
  if($dc_type_id > 6 &&  $dc_type_id < 9)
    {
    $output .= xmlrecord($value, 'dc:coverage', '', $indent);
    } 
  if($dc_type_id == 9)
    {
    $output .= xmlrecord($value, 'dc:creator', '', $indent);
    } 
  else if($dc_type_id > 9 &&  $dc_type_id < 17)
    {
    $output .= xmlrecord($value, 'dc:date', '', $indent);
    }
  else if($dc_type_id > 16 &&  $dc_type_id < 26)
    {
    $output .= xmlrecord($value, 'dc:identifier', '', $indent);
    }
  else if($dc_type_id > 25 &&  $dc_type_id < 33)
    {
    $output .= xmlrecord($value, 'dc:description', '', $indent);
    }
  else if($dc_type_id > 32 &&  $dc_type_id < 37)
    {
    $output .= xmlrecord($value, 'dc:format', '', $indent);
    }
  else if($dc_type_id > 36 &&  $dc_type_id < 39)
    {
    $output .= xmlrecord($value, 'dc:language', '', $indent);
    }
  else if($dc_type_id == 39)
    {
    $output .= xmlrecord($value, 'dc:publisher', '', $indent);
    }
  else if($dc_type_id > 39 &&  $dc_type_id < 53)
    {
    $output .= xmlrecord($value, 'dc:relation', '', $indent);
    }
  else if($dc_type_id > 52 &&  $dc_type_id < 55)
    {
    $output .= xmlrecord($value, 'dc:rights', '', $indent);
    }
  else if($dc_type_id > 55 &&  $dc_type_id < 57)
    {
    $output .= xmlrecord($value, 'dc:source', '', $indent);
    }
  else if($dc_type_id > 56 &&  $dc_type_id < 64)
    {
    $output .= xmlrecord($value, 'dc:subject', '', $indent);
    }
  else if($dc_type_id > 63 &&  $dc_type_id < 66)
    {
    $output .= xmlrecord($value, 'dc:title', '', $indent);
    }
  else if($dc_type_id == 66)
    {
    $output .= xmlrecord($value, 'dc:type', '', $indent);
    }
  else if($dc_type_id > 66 &&  $dc_type_id < 69)
    {
    $output .= xmlrecord($value, 'dc:type', '', $indent);
    }
}

function date2UTCdatestamp($date)
{
  global $granularity;

  if ($date == '') return '';
  
  switch ($granularity)
    {
    case 'YYYY-MM-DDThh:mm:ssZ':
      // we assume common date ("YYYY-MM-DD") 
      // or datetime format ("YYYY-MM-DD hh:mm:ss")
      // or datetime format with timezone YYYY-MM-DD hh:mm:ss+02
      // or datetime format with GMT timezone YYYY-MM-DD hh:mm:ssZ
      // or datetime format with timezone YYYY-MM-DDThh:mm:ssZ
      // or datetime format with microseconds and
      //             with timezone YYYY-MM-DD hh:mm:ss.xxx+02
      // with all variations as above
      // in the database
      if (strstr($date, ' ') || strstr($date, 'T'))
        {
        $checkstr = '/([0-9]{4})(-)([0-9]{1,2})(-)([0-9]{1,2})([T ])([0-9]{2})(:)([0-9]{2})(:)([0-9]{2})(\.?)(\d*)([Z+-]{0,1})([0-9]{0,2})$/';
  $val = preg_match($checkstr, $date, $matches);
  if (!$val)
    {
    // show that we have an error
    return "0000-00-00T00:00:00Z";
    }
    // date is datetime format
    /*
    * $matches for "2005-05-26 09:30:51.123+02"
    *  [0] => 2005-05-26 09:30:51+02
    *  [1] => 2005
    *  [2] => -
    *  [3] => 05
    *  [4] => -
    *  [5] => 26
    *  [6] =>
    *  [7] => 09
    *  [8] => :
    *  [9] => 30
    *  [10] => :
    *  [11] => 51
    *  [12] => .
    *  [13] => 123
    *  [14] => +
    *  [15] => 02
    */
    if ($matches[14] == '+' || $matches[14] == '-')
      {
      // timezone is given
      // format ("YYYY-MM-DD hh:mm:ss+01")
      $tz = $matches[15];
      if ($tz != '')
        {
        //$timestamp = mktime($h, $min, $sec, $m, $d, $y);
        $timestamp = mktime($matches[7], $matches[9], $matches[11], $matches[3], $matches[5], $matches[1]);
        // add, subtract timezone offset to get GMT , 3600 sec = 1 h
        if ($matches[14] == '-')
          {
          // we are before GMT, thus we need to add
          $timestamp += (int) $tz * 3600; 
          }
        else
          {
          // we are after GMT, thus we need to subtract
          $timestamp -= (int) $tz * 3600; 
          }              
        return strftime("%Y-%m-%dT%H:%M:%SZ", $timestamp);
        }
      }
    elseif ($matches[14] == 'Z')
      {
      return str_replace(' ', 'T', $date);
      }        
    return str_replace(' ', 'T', $date).'Z';
        }
      else
        {
  // date is date format
  // granularity 'YYYY-MM-DD' should be used...
  return $date.'T00:00:00Z';
  }
      break;

    case 'YYYY-MM-DD':
      if (strstr($date, ' '))
        {
  // date is datetime format
  list($date, $time) = explode(" ", $date);
  return $date;
  }
      else
        {
  return $date;
  }
      break;

    default: die("Unknown granularity!");
    }
}

function checkDateFormat($date)
{
  global $granularity;
  global $message;

  if ($granularity == 'YYYY-MM-DDThh:mm:ssZ')
    {
    $checkstr = '/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})T([0-9]{2}):([0-9]{2}):([0-9]{2})Z$/i';
    }
  else
    {
    $checkstr = '/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}$)/i';
    }

  if (preg_match($checkstr, $date, $regs))
    {
    if (checkdate($regs[2], $regs[3], $regs[1]))
      {  
      return 1;
      }
    else
      {
      $message = "Invalid Date: $date is not a valid date.";
      return 0;
      }
    }
  else
    {
    $message = "Invalid Date Format: $date does not comply to the date format $granularity.";
    return 0;
    }
}

function formatDatestamp($datestamp)
{
  global $granularity;

  $datestamp = date2UTCdatestamp($datestamp); 
  if (!checkDateFormat($datestamp))
    {
    if ($granularity == 'YYYY-MM-DD')
      {
      return '2002-01-01';
      }
    else
      {
      return '2002-01-01T00:00:00Z';
      }
    }
  else
    {
    return $datestamp;
    }
}

function oai_close()
{
  global $compress;

  echo "</OAI-PMH>\n";

  if ($compress)
    {
    ob_end_flush();
    }
}

function oai_exit()
{
  global $CONTENT_TYPE;
  global $xmlheader;
  global $request;
  global $errors;
  
  if(!Zend_Registry::get('configGlobal')->environment == 'testing')
    {
    header('Content-Type: text/plain');
    }
  echo $xmlheader;
  echo $request;
  echo $errors;
  if(!Zend_Registry::get('configGlobal')->environment == 'testing')
    {
    oai_close();
    exit();
    }  
}

function php_is_at_least($version)
{
  list($c_r, $c_mj, $c_mn) = explode('.', phpversion());
  list($v_r, $v_mj, $v_mn) = explode('.', $version);

  if ($c_r >= $v_r && $c_mj >= $v_mj && $c_mn >= $v_mn)
    {
    return TRUE;
    }
  else
    {
    return FALSE;
    }
}

function is_valid_uri($url)
{
  return((bool)preg_match("'^[^:]+:(?://)?(?:[a-z_0-9-]+[\.]{1})*(?:[a-z_0-9-]+\.)[a-z]{2,3}.*$'i", $url));
}

function metadataHeader($prefix)
{
  global $METADATAFORMATS;
  global $XMLSCHEMA;

  $myformat = $METADATAFORMATS[$prefix];

  $str = '     <'.$prefix;
  if ($myformat['record_prefix'])
    {
    $str .= ':'.$myformat['record_prefix'];
    }
  $str .= "\n".'       xmlns:'.$prefix.'="'.$myformat['metadataNamespace'].'"'."\n";
  if ($myformat['record_prefix'] && $myformat['record_namespace'])
    {
    $str .= '       xmlns:'.$myformat['record_prefix'].'="'.$myformat['record_namespace'].'"'."\n";
    }
  $str .= '       xmlns:xsi="'.$XMLSCHEMA.'"'."\n".
    '       xsi:schemaLocation="'.$myformat['metadataNamespace']."\n".
    '       '.$myformat['schema'].'">'."\n";

  return $str;
}
?>
