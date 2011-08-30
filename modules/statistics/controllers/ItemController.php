<?php

class Statistics_ItemController extends Statistics_AppController
{  
   public $_moduleModels=array('Download');
   public $_models=array('Item');
   public $_components = array('Utility');
   /** index action*/
   function indexAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
      
    $item = $this->Item->load($_GET['id']);
    if(!$item)
      {
      throw new Zend_Exception("Item doesn't exist");
      }
      
    $downloads = $this->Statistics_Download->getDownloads($item, date( 'c', strtotime ('-20 day'.date( 'Y-m-j G:i:s'))), date('c'));
    $arrayDownload = array();
    $format = 'Y-m-j'; 
    for($i = 0; $i<21; $i++)
      {
      $key =  date($format, strtotime(date( 'c', strtotime ('-'.$i.' day'.date( 'Y-m-j G:i:s')))));
      $arrayDownload[$key] = 0;
      }
    foreach($downloads as $download)
      {
      $key =  date($format, strtotime($download->getDate()));
      $arrayDownload[$key]++;
      }
      
    $jqplotArray = array();
    foreach($arrayDownload as $key => $value)
      {
      $jqplotArray[] = array($key.' 8:00AM', $value);
      }
    $this->view->json['stats']['downloads'] = $jqplotArray;
    $this->view->itemDao = $item;
    } 
    
}//end class