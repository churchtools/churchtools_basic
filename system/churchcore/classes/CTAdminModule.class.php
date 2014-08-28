<?php

class CTAdminModule extends CTAbstractModule {
  function getMasterData() {

  }
  function saveLogo($params) {
    if ($params["filename"]==null)
      db_query("delete from {cc_config} where name='site_logo'");
    else
      db_query("insert into {cc_config} (name, value) values ('site_logo', :filename) on duplicate key update value=:filename",
          array(":filename"=>$params["filename"]));
  }
}