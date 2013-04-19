<?php

/**
 * Upgrade 3.2.13 move userapi and token to core
 */
class Upgrade_3_2_13 extends MIDASUpgrade
{

  public function preUpgrade()
    {

    }

  public function mysql()
    {
     $this->db->query("CREATE TABLE IF NOT EXISTS `api_userapi` (
       `userapi_id` bigint(20) NOT NULL AUTO_INCREMENT,
       `user_id` bigint(20) NOT NULL,
       `apikey` varchar(40) NOT NULL,
       `application_name` varchar(256) NOT NULL,
       `token_expiration_time` int(11) NOT NULL,
       `creation_date` timestamp NULL DEFAULT NULL,
       PRIMARY KEY (`userapi_id`)
       )");
     $this->db->query("RENAME TABLE `api_userapi` to `userapi`");

     $this->db->query("CREATE TABLE IF NOT EXISTS `api_token` (
       `token_id` bigint(20) NOT NULL AUTO_INCREMENT,
       `userapi_id` bigint(20) NOT NULL,
       `token` varchar(40) NOT NULL,
       `expiration_date` timestamp NULL DEFAULT NULL,
       PRIMARY KEY (`token_id`)
       )");
     $this->db->query("RENAME TABLE `api_token` to `token`");
    }

  public function pgsql()
    {
    $this->db->query("CREATE TABLE api_userapi (
      userapi_id serial PRIMARY KEY,
      user_id bigint NOT NULL,
      apikey character varying(40) NOT NULL,
      application_name character varying(256) NOT NULL,
      token_expiration_time integer NOT NULL,
      creation_date timestamp without time zone
      )");
    $this->db->query("ALTER TABLE api_userapi_userapi_id_seq RENAME TO userapi_userapi_id_seq");
    $this->db->query("ALTER TABLE api_userapi RENAME TO userapi");
    $this->db->query("ALTER INDEX api_userapi_pkey RENAME TO userapi_pkey");

    $this->db->query("CREATE TABLE api_token (
      token_id serial PRIMARY KEY,
      userapi_id bigint NOT NULL,
      token character varying(40) NOT NULL,
      expiration_date timestamp without time zone
      )");
    $this->db->query("ALTER TABLE api_token_token_id_seq RENAME TO token_token_id_seq");
    $this->db->query("ALTER TABLE api_token RENAME TO token");
    $this->db->query("ALTER INDEX api_token_pkey RENAME TO token_pkey");
    }

  public function postUpgrade()
    {
    $userModel = MidasLoader::loadModel('User');
    $userapiModel = MidasLoader::loadModel('Userapi');

    //limit this to 100 users; there shouldn't be very many when api is installed
    $users = $userModel->getAll(false, 100, 'admin');
    foreach($users as $user)
      {
      $userApiDao = $userapiModel->getByAppAndEmail('Default', $user->getEmail());
      if($userApiDao != false)
        {
        $userDefaultApiKey = $userApiDao->getApikey();
        if(!empty($userDefaultApiKey))
          {
          continue;
          }
        }
        $userapiModel->createDefaultApiKey($user);
      }
    }

}
?>
