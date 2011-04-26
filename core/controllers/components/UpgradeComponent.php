<?php
class UpgradeComponent extends AppComponent
{ 
  
  protected $dir;
  protected $module;
  protected $db;
  protected $dbtype;
  protected $dbtypeShort;
  protected $init=false;
  
  /** init upgrade Componenet*/
  public function initUpgrade($module,$db,$dbtype)
    {
    if($module=='core')
      {
      $this->dir=BASE_PATH.'/core/database/upgrade';
      }
    else
      {
      $this->dir=BASE_PATH.'/modules/'.$module.'/database/upgrade';
      }
      
    $this->db=$db;
    $this->module=$module;
    $this->dbtype=$dbtype;
    switch ($dbtype)
      {
      case "PDO_MYSQL":
        $this->dbtypeShort='mysql';
        break;
      case "PDO_PGSQL":
        $this->dbtypeShort='pgsql';
        break;
      default:
        throw new Zend_Exception("Unknow database type");
        break;
      }
    $this->init=true;
    }//end init
  
    
  /** get Newest version */
  public function getNewestVersion($text=false)
    {
    if(!$this->init)
      {
      throw new Zend_Exception("Please init the component first");
      }
    $files=$this->getMigrationFiles();
    if(empty($files))
      {
      return 0;
      }
    $version='';
    foreach ($files as $key=>$f)
      {
      $version=$key;
      if($text)
        {
        $version=$f['versionText'];
        }
      }
    return $version;
    }//getNewestVersion
  
  /** get all migration files */
  public function getMigrationFiles() 
    {
    if(!$this->init)
      {
      throw new Zend_Exception("Please init the component first");
      }
    $files = array();
    if(file_exists($this->dir))
      {
      $d = dir($this->dir);
      while (false !== ($entry = $d->read())) 
        {
        if (preg_match('/^([0-9]+)(.)([0-9]+)(.)([0-9]+)\.php/i', $entry, $matches) ) 
          {
          $versionText = basename(str_replace(".php", "", $entry));
          $versionNumber = $this->transformVersionToNumeric($versionText);
          $files[$versionNumber] = array(
                'filename'=>$entry,
                'version'=>$versionNumber,
                'versionText'=>$versionText);
          }
        }
      $d->close();
      }    
    ksort($files);
    return $files;
    } //end getMigrationFiles
    
    /**transformVersionToNumeric*/
  public function transformVersionToNumeric($text)
    {
    $array=explode('.', $text);
    if(count($array)!=3)
      {
      throw new Zend_Exception("The version format shoud be 1.2.5. You set:".$text);
      }
    return (int)$array[0]*1000000+(int)$array[1]*1000+(int)$array[2];
    }// end transformVersionToNumeric

  /** upgrade*/
  public function upgrade($currentVersion)
    {
    if(!isset($currentVersion))
      {
      throw new Zend_Exception("Please set the current version");
      }
    if(!is_numeric($currentVersion))
      {
      $currentVersion=$this->transformVersionToNumeric($currentVersion);
      }
      
    $version=$this->getNewestVersion($text=false);
    
    if($currentVersion == $version||$version==0)
      {
      return false;
      }
        
    $migrations = $this->getMigrationFiles();
    
    foreach ($migrations as $migration) 
      {
      if($migration['version']>$currentVersion)
        {
        $this->_processFile($migration);
        }
      }
     
    require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
    $utility=new UtilityComponent();
    if($this->module=='core')
      {
      if(isset($migration))
        {
        $path=BASE_PATH.'/core/configs/database.local.ini';      
        $data=parse_ini_file ($path,true);
        if(file_exists($path.'.old'))
          {
          unlink( $path.'.old');
          }      
        rename($path, $path.'.old');
        $data['development']['version']=$migration['versionText'];
        $data['production']['version']=$migration['versionText'];
        $utility->createInitFile($path, $data);
        }
      }
    else
      {
      $path=BASE_PATH.'/modules/'.$this->module.'/configs/module.local.ini';
      $data=parse_ini_file ($path,true);
      if(file_exists($path.'.old'))
        {
        unlink( $path.'.old');
        }      
      rename($path, $path.'.old');
      $data['global']['version']=$migration['versionText'];
      $utility->createInitFile($path, $data);
      }
    return true;
    }//end upgrade
   
  /** get Class Name*/
  public function getClassName($filename)
    {
    $array=explode('.', str_replace('.php', '', basename($filename)));
    if(count($array)!=3)
      {
      throw new Zend_Exception("The version format shoud be 1.2.5. You set:".str_replace('.php', '', basename($filename)));
      }
      
    $classname='';  
    if($this->module!='core')
      {
      $classname=ucfirst($this->module).'_';
      }
    $classname.="Upgrade_";
    return $classname.$array[0].'_'.$array[1].'_'.$array[2];
    }//getClassName
  
  /** execute de upgrade*/
  protected function _processFile($migration) 
    {
    require_once BASE_PATH.'/library/MIDAS/models/MIDASUpgrade.php';
    $version = $migration['version'];
    $filename = $migration['filename'];
    $classname = $this->getClassName($filename);
    require_once($this->dir.'/'.$filename);
    if (!class_exists($classname, false)) 
      {
      throw new Zend_Exception("Could not find class '$classname' in file '$filename'");
      }
      
    $class = new $classname($this->db,$this->module);
    $class->preUpgrade();
    $class->{$this->dbtypeShort}();
    $class->postUpgrade();
    } 
} // end class
?>