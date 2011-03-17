<?php

/**
 *  AJAX request for the admin Controller
 */
class AdminajaxController extends AppController
{
  public $_models=array();
  public $_daos=array();
  public $_components=array();
    
  /**
   * \fn serversidefilechooser()
   * \brief called by the server-side file chooser
   */
  function serversidefilechooserAction()
    {
    /*$userid = $this->CheckSession();
    if (!$this->User->isAdmin($userid))
      {
      echo "Administrative privileges required";
      exit ();
      }
      */
    
    // Display the tree
    $_POST['dir'] = urldecode($_POST['dir']);
    $files = array();
    if( strpos( strtolower(PHP_OS), 'win') !== false )
      {
      $files = array();
      for($c='A'; $c<='Z'; $c++)
        {
        if(is_dir($c . ':'))
          {
          $files[] = $c . ':';
          }
        }
      }
    else
      {
      $files[] = '/';
      }

    if( file_exists($_POST['dir']) || file_exists($files[0]) ) 
      {
      if(file_exists($_POST['dir']))
        {
        $files = scandir($_POST['dir']);
        }
      natcasesort($files);
      echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
      foreach( $files as $file ) 
        {
        if( file_exists( $_POST['dir'] . $file) && $file != '.' && $file != '..' && is_readable($_POST['dir'] . $file) )
          {
          if( is_dir($_POST['dir'] . $file) )
            {
            echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";  
            }
          else // not a directory: a file!
            {
            $ext = preg_replace('/^.*\./', '', $file); 
            echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "\">" . htmlentities($file) . "</a></li>";
            }              
          }
        }
      echo "</ul>"; 
      }
    else
      {
      echo "File ".$_POST['dir']." doesn't exist";
      }     
    // No views  
    exit();
    } // end function  serversidefilechooserAction
    
    
    
    
    
} // end class

  