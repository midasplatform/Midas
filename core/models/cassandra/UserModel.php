<?php
/**
 * \class UserModel
 * \brief Cassandra Model
 */
class UserModel extends MIDASUserModel
{
  /** Get a user by email */
  function getByEmail($email)
    {
    // We get from the table emailuser
    try 
      {
      $emailuser = new ColumnFamily($this->database->getDB(), 'emailuser'); 
      $userid = $emailuser->get($email);
      
      //$dao= $this->initDao('User',);
      }
    catch(cassandra_NotFoundException $e) 
      {
      return false;  
      }      
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }  
      
    return $dao;
    } // end getByEmail()
    
     
}
?>
