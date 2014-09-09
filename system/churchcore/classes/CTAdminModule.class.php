<?php

class CTAdminModule extends CTAbstractModule {
  function getMasterData() {

  }
  function saveLogo($params) {
    if ($params["filename"]==null)
      db_query("DELETE from {cc_config} 
                WHERE name='site_logo'");
    else
      db_query("INSERT INTO {cc_config} (name, value) 
                VALUES ('site_logo', :filename) 
                ON DUPLICATE KEY UPDATE value=:filename",
          array(":filename"=>$params["filename"]));
  }
}