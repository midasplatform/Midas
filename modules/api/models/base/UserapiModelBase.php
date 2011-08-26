<?php
abstract class Api_UserapiModelBase extends Api_AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'api_userapi';
    $this->_key = 'userapi_id';

    $this->_mainData= array(
        'userapi_id'=>  array('type'=>MIDAS_DATA),
        'user_id'=>  array('type'=>MIDAS_DATA),
        'apikey'=>  array('type'=>MIDAS_DATA),
        'application_name'=>  array('type'=>MIDAS_DATA),
        'token_expiration_time'=>  array('type'=>MIDAS_DATA),
        'creation_date'=>  array('type'=>MIDAS_DATA),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
        );
    $this->initialize(); // required
    } // end __construct()

  abstract function createKeyFromEmailPassword($appname,$email,$password);
  abstract function getByAppAndEmail($appname,$email);
  abstract function getByAppAndUser($appname,$userDao);
  abstract function getToken($email,$apikey,$appname);
  abstract function getUserapiFromToken($token);
  abstract function getByUser($userDao);

  /**
   * Create the user's default API key
   * @param string $userDao the user
   * @return success boolean
   */
  function createDefaultApiKey($userDao)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception('Error parameter: must be a userDao object');
      }

    // Remove prior default api key(s)
    $rowset = $this->database->fetchAll($this->database->select()
                                                       ->where('user_id = ?', $userDao->getKey())
                                                       ->where('application_name = ?', 'Default'));
    foreach($rowset as $row)
      {
      $userApiDao= $this->initDao('Userapi', $row,'api');
      $this->delete($userApiDao);
      }

    // Save new default key
    $key = md5($userDao->getEmail().$userDao->getPassword().'Default');
    $this->loadDaoClass('UserapiDao','api');
    $userApiDao=new Api_UserapiDao();
    $userApiDao->setUserId($userDao->getKey());
    $userApiDao->setApplicationName('Default');
    $userApiDao->setApikey($key);
    $userApiDao->setTokenExpirationTime(100);
    $userApiDao->setCreationDate(date('c'));
    $this->save($userApiDao);
    }

     /** Create a new API key */
  function createKey($userDao,$applicationname,$tokenexperiationtime)
    {
    if(!$userDao instanceof UserDao||!is_string($applicationname)||!is_string($tokenexperiationtime) || empty($applicationname))
      {
      throw new Zend_Exception("Error parameter");
      }

    // Check that the applicationname doesn't exist for this user
    $userapiDao=$this->getByAppAndUser($applicationname, $userDao);
    if(!empty($userapiDao))
      {
      return false;
      }
    $now = date("c");

    // We generate a challenge
    $keychars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $length = 40;

    // seed with microseconds
    function make_seed_recoverpass()
      {
      list($usec, $sec) = explode(' ', microtime());
      return (float) $sec + ((float) $usec * 100000);
      }
    srand(make_seed_recoverpass());

    $key = "";
    $max=strlen($keychars)-1;
    for ($i=0;$i<$length;$i++)
      {
      $key .= substr($keychars, rand(0, $max), 1);
      }

    $this->loadDaoClass('UserapiDao','api');
    $userApiDao=new Api_UserapiDao();
    $userApiDao->setUserId($userDao->getKey());
    $userApiDao->setApikey($key);
    $userApiDao->setApplicationName($applicationname);
    $userApiDao->setTokenExpirationTime($tokenexperiationtime);
    $userApiDao->setCreationDate($now);

    $this->save($userApiDao);
    return $userApiDao;
    }//end createKey

} // end class AssetstoreModelBase
?>
